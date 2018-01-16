<?php

require_once 'const.php';


// 连接到弹幕服务器
function connectServer($ip, $port, $roomID) {
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_connect($socket, $ip, $port);
	$str = packMsg($roomID, UID);
	socket_write($socket, $str, strlen($str));
	return $socket;
}


// 发送心跳包
function sendHeartBeatPkg($socket) {
	$str = pack('NnnNN', 16, 16, 1, ACTION_HEARTBEAT, 1);
	socket_write($socket, $str, strlen($str));
}


// 打包请求
function packMsg($roomID, $uid) {
	$data = json_encode(['roomid' => $roomID, 'uid' => $uid]);
	return pack('NnnNN', 16 + strlen($data), 16, 1, ACTION_ENTRY, 1) . $data;
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


// 获取直播间真实房间号
function getRealRoomID($shortID) {
	$resp = json_decode(file_get_contents(ROOM_INIT_API . $shortID), true);
	if ($resp['code']) {
		exit($shortID . ' : ' . $resp['msg']);
	}
	return $resp['data']['room_id'];
}


// 连接到数据库
function connectDB() {
	$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
	if (mysqli_connect_error()) {
		echo mysqli_connect_error();
	}
	mysqli_set_charset($conn, 'utf8');
	return $conn;
}

// 插入弹幕数据
function insertDanmu($conn, $info) {
	$sql = "INSERT INTO danmu (uid, uname, `level`, content) VALUES ({$info[2][0]}, '{$info[2][1]}', {$info[4][0]}, '$info[1]')";
	$res = $conn->query($sql);
	if ($res === false) {
		echo 'SQL insert error: ' . $sql . PHP_EOL . $conn->error;
		exit;
	}
	return $conn->insert_id;
}


function insertGift($conn, $data) {
	$sql = "INSERT INTO gifts (uid, gift_id, gift_name, price, num) VALUES (
							  {$data['uid']}, {$data['giftId']},  '{$data['giftName']}', {$data['price']}, {$data['num']})";
	$res = $conn->query($sql);
	if ($res === false) {
		echo 'SQL insert error: ' . $sql . PHP_EOL . $conn->error;
		exit;
	}
	return $conn->insert_id;
}

function pr($var) {
	print_r($var);
	exit;
}