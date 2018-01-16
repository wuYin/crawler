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