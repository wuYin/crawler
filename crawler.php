<?php
require_once 'functions.php';

date_default_timezone_set('Asia/Shanghai');
ini_set('memory_limit', MAX_ALLOW_MEMORY . "B");

$timestamp = 0;

// 参数检查
$shortID = $argv[1];
if (!is_numeric($shortID)) {
	exit('Usage like: php crawler.php 5441');
}

// 连接到数据库
$conn = connectDB();

// 转换热门主播的短房间号为真实房间号
$roomID = getRealRoomID($shortID);

// 保存直播录像
recordVideo($roomID);

// 获取弹幕服务器的地址
$server = getServer($roomID);

// 连接到弹幕服务器
$socket = connectServer($server['ip'], $server['port'], $roomID);

// 处理弹幕和礼物消息
decodeMessage($socket);

analysisDanmu($conn, $roomID);
analysisGifts($conn, $roomID);
