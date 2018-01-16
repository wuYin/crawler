<?php

const ROOM_INIT_API   = 'https://api.live.bilibili.com/room/v1/Room/room_init?id=';
const ROOM_SERVER_API = 'https://api.live.bilibili.com/api/player?id=cid:';

const ACTION_ENTRY     = 7;
const ACTION_HEARTBEAT = 2;
const UID              = 18466419;


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