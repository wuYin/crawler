<?php
require_once 'functions.php';

$timestamp = 0;

$shortID = $argv[1];
if (!is_numeric($shortID)) {
	exit('Usage like: php crawler.php 5441');
}

$conn   = connectDB();
$roomID = getRealRoomID($shortID);

// recordVideo($roomID);

$server  = getServer($roomID);
$socket  = connectServer($server['ip'], $server['port'], $roomID);
$message = decodeMessage($socket);
// analysisDanmu($conn, $roomID);
// analysisGifts($conn, $roomID);

decodeMessage($socket);