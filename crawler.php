<?php
require_once 'functions.php';

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
	$conn = connectDB();
	switch ($resp['cmd']) {
		case 'DANMU_MSG':
			// 弹幕消息
			insertDanmu($conn, $resp['info']);
			break;
		case 'SEND_GIFT':
			// 直播间送礼物信息
			insertGift($conn, $resp['data']);
			break;
		case 'WELCOME':
			// 直播间欢迎信息
			break;
		default:
			// 新添加的消息类型
	}
}