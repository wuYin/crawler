<?php

require_once 'config.php';
require_once 'const.php';


// 连接到数据库
function connectDB() {
	$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
	if (mysqli_connect_error()) {
		echo 'MySQL connect error: ' . mysqli_connect_error();
		exit;
	}
	mysqli_set_charset($conn, 'utf8mb4');
	echo '数据库连接成功' . PHP_EOL;
	return $conn;
}

// 获取直播间真实房间号
function getRealRoomID($shortID) {
	$resp = json_decode(file_get_contents(ROOM_INIT_API . $shortID), true);
	if ($resp['code']) {
		exit($shortID . ' : ' . $resp['msg']);
	}
	$roomID = $resp['data']['room_id'];
	echo $shortID . ' 的真实房间号为：' . $roomID . PHP_EOL;
	return $roomID;
}

// 获取弹幕服务器的 ip 和端口号
function getServer($roomID) {
	$xmlResp = '<xml>' . file_get_contents(ROOM_SERVER_API . $roomID) . '</xml>';
	$parser  = xml_parser_create();
	xml_parse_into_struct($parser, $xmlResp, $resp, $index);
	$domain = $resp[$index['DM_SERVER'][0]]['value'];
	$ip     = gethostbyname($domain);
	$port   = $resp[$index['DM_PORT'][0]]['value'];
	return ['ip' => $ip, 'port' => $port];
}

// 打包请求
function packMsg($roomID, $uid) {
	$data = json_encode(['roomid' => $roomID, 'uid' => $uid]);
	return pack('NnnNN', 16 + strlen($data), 16, 1, ACTION_ENTRY, 1) . $data;
}

// 连接到弹幕服务器
function connectServer($ip, $port, $roomID) {
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_connect($socket, $ip, $port);
	$str = packMsg($roomID, UID);
	socket_write($socket, $str, strlen($str));
	return $socket;
}


// 解码服务器返回的数据消息
function decodeMessage($socket) {
	while (true) {
		while ($out = socket_read($socket, 16)) {
			$res = @unpack('N', $out);
			if ($res[1] != 16) {
				break;
			}
			unset($out);
		}
		if ($res[1] > MAX_ALLOW_MEMORY) {
			echo '丢弃异常消息包' . PHP_EOL;
			continue;
		}
		$message = @socket_read($socket, $res[1] - 16);
		parseRespJson($message);
		unset($message, $res);

		global $timestamp;
		if (time() - $timestamp > 30) {
			sendHeartBeatPkg($socket);
			$timestamp = time();
		}
	}
	socket_close($socket);
}

// 解析直播间弹幕、礼物信息
function parseRespJson($resp) {
	global $roomID;
	global $conn;
	$resp = json_decode($resp, true);
	switch ($resp['cmd']) {
		case 'DANMU_MSG':
			// 弹幕消息
			echo $resp['info'][2][1] . " : " . $resp['info'][1] . PHP_EOL;
			insertDanmu($conn, $roomID, $resp['info']);
			break;
		case 'SEND_GIFT':
			// 直播间送礼物信息
			$data = $resp['data'];
			echo $data['uname'] . ' 赠送' . $data['num'] . '份' . $data['giftName'] . PHP_EOL;
			insertGift($conn, $roomID, $resp['data']);
			break;
		case 'WELCOME':
			// 直播间欢迎信息
			break;
		default:
			// 新添加的消息类型
	}
}

// 发送心跳包
function sendHeartBeatPkg($socket) {
	$str = pack('NnnNN', 16, 16, 1, ACTION_HEARTBEAT, 1);
	socket_write($socket, $str, strlen($str));
}


// 插入弹幕数据
function insertDanmu($conn, $roomID, $info) {
	$sql = "INSERT INTO danmu (room_id, uid, uname, `level`, content) VALUES ($roomID, {$info[2][0]}, '{$info[2][1]}', {$info[4][0]}, '$info[1]')";
	$res = $conn->query($sql);
	if ($res === false) {
		echo 'SQL insert error: ' . $sql . PHP_EOL . $conn->error;
		exit;
	}
	return $conn->insert_id;
}

// 插入礼物数据
function insertGift($conn, $roomID, $data) {
	$sql = "INSERT INTO gifts (room_id, uid, gift_id, gift_name, price, num) VALUES (
							  $roomID, {$data['uid']}, {$data['giftId']},  '{$data['giftName']}', {$data['price']}, {$data['num']})";
	$res = $conn->query($sql);
	if ($res === false) {
		echo 'SQL insert error: ' . $sql . PHP_EOL . $conn->error;
		exit;
	}
	return $conn->insert_id;
}


// 录视频
function recordVideo($roomID) {
	$resp = json_decode(file_get_contents(ROOM_PLAY_API . $roomID), true);
	if (!$resp['data']) {
		exit('无法录制视频，请确认房间号无误');
	}

	// 第一个视频源不易连接上，使用第二、三个较好
	$wsURL    = $resp['data']['durl'][1]['url'];
	$start    = date('Y-m-d_H.i.s', time());
	$savePath = VIDEO_SAVE_DIR . $roomID . '_' . $start . '.mp4';
	$cmd      = FFMPEG_EXEC_PATH . ' -i "' . $wsURL . '" -y -vcodec copy -acodec copy -f mp4 "' . $savePath . '" > /dev/null 2>&1 & ';
	exec($cmd);
	echo '开始录制 ' . $roomID . ' 直播间的视频，已保存到 ' . $savePath . PHP_EOL;
}


// 分析弹幕的数量数据
function analysisDanmu($conn, $roomID) {
	$sql = 'SELECT created_at FROM danmu WHERE room_id = ' . $roomID;
	$res = $conn->query($sql);
	if (!$res->num_rows) {
		return [];
	}
	$set = [];
	while ($time = $res->fetch_assoc()) {
		@$set[date('H:i', strtotime($time['created_at']))]++;
	}
	return $set;
}

// 分析礼物的数量数据
function analysisGiftNums($conn, $roomID) {
	$sql = 'SELECT created_at, price, num FROM gifts WHERE room_id = ' . $roomID;
	$res = $conn->query($sql);
	if (!$res->num_rows) {
		return [];
	}
	$set = [];
	while ($gift = $res->fetch_assoc()) {
		@$set[date('H:i', strtotime($gift['created_at']))] += $gift['num'];
	}
	return $set;
}


// 分析礼物的价值数据
function analysisGiftValues($conn, $roomID) {
	$sql = 'SELECT created_at, price, num FROM gifts WHERE room_id = ' . $roomID;
	$res = $conn->query($sql);
	if (!$res->num_rows) {
		return [];
	}
	$set = [];
	while ($gift = $res->fetch_assoc()) {
		@$set[date('H:i', strtotime($gift['created_at']))] += ($gift['price'] * $gift['num']) / GIFT_RATE;
	}
	// 价值总和四舍五入
	foreach ($set as $i => $value) {
		$set[$i] = ceil($value);
	}
	return $set;
}

// 内存单位转换
function convert($size) {
	$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
	return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

// 调试函数
function pr($var) {
	print_r($var);
	exit;
}