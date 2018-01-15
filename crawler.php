<?php
/**
 * Created by PhpStorm.
 * User: wuYin
 * Date: 18/1/15
 * Time: 13:20
 */

const ROOM_INIT_API   = 'https://api.live.bilibili.com/room/v1/Room/room_init?id=';
const ROOM_SERVER_API = 'https://api.live.bilibili.com/api/player?id=cid:';

const ACTION_ENTRY = 7;
const UID          = 18466419;

$timestamp = 0;

$shortID = $argv[1];
if (!is_numeric($shortID)) {
	exit('Usage like: php crawler.php 5441');
}
$roomID  = getRealRoomID($shortID);
$server  = getServer($roomID);
$socket  = connectServer($server['ip'], $server['port'], $roomID);
$message = decodeMessage($socket);


// 解码服务器返回的数据消息
function decodeMessage($socket) {
	while (true) {
		while ($out = socket_read($socket, 16)) {
			$res = unpack('N', $out);
			if ($res[1] != 16) {
				break;
			}
		}
		$message = socket_read($socket, $res[1] - 16);
		parseRespJson($message);

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
	$resp = json_decode($resp, true);
	switch ($resp['cmd']) {
		case 'DANMU_MSG':
			// 弹幕消息
			echo $resp['info'][2][1] . " : " . $resp['info'][1] . PHP_EOL;
			break;
		case 'WELCOME':
			// 直播间欢迎信息
			echo '欢迎' . $resp['data']['uname'] . '进入直播间' . PHP_EOL;
			break;
		case 'SEND_GIFT':
			// 直播间送礼物信息
			$data = $resp['data'];
			echo $data['uname'] . ' 赠送' . $data['num'] . '份' . $data['giftName'] . PHP_EOL;
			break;
		default:
			// 新添加的消息类型
	}
}


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
	$str = pack('NnnNN', 16, 16, 1, 2, 1);
	socket_write($socket, $str, strlen($str));
}


// 打包请求
function packMsg($roomID, $uid) {
	$data = json_encode(['roomid' => $roomID, 'uid' => $uid]);
	return pack('NnnNN', 16 + strlen($data), 16, 1, 7, 1) . $data;
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

// 自定义调试函数
function pr($var) {
	var_dump($var);
	exit;
}