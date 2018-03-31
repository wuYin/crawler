<?php

require_once 'functions.php';

// 参数检查
$roomID    = $argv[1];
$videoPath = $argv[2];
if (!is_numeric($roomID) || !file_exists($videoPath)) {
	exit('Usage like: php edit.php 5441 /Users/wuyin/Desktop/5441_2018-03-30_18.30.57.mp4');
}

// 连接数据库
$conn = connectDB();

// 取出弹幕数量与礼物价值
$barrageCounts = analysisDanmu($conn, $roomID);
$giftValues    = analysisGiftValues($conn, $roomID);
// 因为直播间刷礼物的大部分都来自那几位土豪，礼物的数量和弹幕的数量不太对齐
// 参考价值不高，不用来判定精彩时刻
// $giftCounts    = analysisGiftNums($conn, $roomID);

if (empty($barrageCounts) || empty($giftValues)) {
	exit('暂无直播间的数据');
}

reset($barrageCounts);
$startLiveTime = strtotime(key($barrageCounts));


$barrageFunTimes = getFunTimes($barrageCounts, BARRAGE_RATE_UP, BARRAGE_RATE_DOWN);
$giftFunTimes    = getFunTimes($giftValues, GIFT_RATE_UP, GIFT_RATE_DOWN);
$funTimes        = mergeFunTimes($barrageFunTimes, $giftFunTimes);

// 剪辑视频
editVideo($roomID, $startLiveTime, $funTimes, $videoPath);


/**
 * 根据弹幕曲线获取精彩时刻
 *
 * @param $counts   array 时刻弹幕数量礼物数量的数组
 * @param $upStd    float 激增判定的标准
 * @param $downStd  float 骤减判定参数
 * @return array          精彩时刻的数组
 */
function getFunTimes($counts, $upStd, $downStd) {
	$times    = array_keys($counts);
	$len      = count($times);
	$funTimes = [];

	$preTime  = $times[0];
	$preCount = $counts[$preTime];

	for ($i = 1; $i < $len - 1; $i++) {
		$curTime  = $times[$i];
		$curCount = $counts[$curTime];

		$nextTime  = $times[$i + 1];
		$nextCount = $counts[$nextTime];

		// 弹幕激增或骤减
		$upRate   = $curCount / $preCount;  // 激增比例
		$downRate = $nextCount / $curCount; // 骤减比例
		if ($upRate >= $upStd && $downRate <= $downStd) {
			array_push($funTimes, $preTime);
		}

		$preTime  = $curTime;
		$preCount = $curTime;
	}
	return $funTimes;
}

/**
 * 合并精彩时刻
 *
 * @param $barrageFunTimes  array 弹幕对应的精彩时刻
 * @param $giftFunTimes     array 礼物对应的精彩时刻
 * @return array                  综合判断的精彩时刻
 */
function mergeFunTimes($barrageFunTimes, $giftFunTimes) {
	if (empty($barrageFunTimes) || empty($giftFunTimes)) {
		return array_merge($barrageFunTimes, $giftFunTimes);
	}

	$res = [];
	foreach ($barrageFunTimes as $time) {
		// 检查前后一分钟的误差
		$preTime  = date('H:i', strtotime($time) - 60);
		$nextTime = date('H:i', strtotime(time()) + 60);

		if (in_array($preTime, $giftFunTimes) || in_array($nextTime, $giftFunTimes)) {
			array_push($res, $time);
		}
	}

	return array_unique($res);
}


/**
 * 剪辑视频
 *
 * @param $startLiveTime string 开播的时间
 * @param $times         array  精彩时刻
 * @param $path          string 录制视频的路径
 */
function editVideo($roomID, $startLiveTime, $times, $path) {
	if (empty($times)) {
		return;
	}

	// 录像的保存文件夹
	$saveDir = './videos/' . $roomID . '/' . date('Y-m-d', $startLiveTime) . '/' . date('H.i.s', $startLiveTime);
	shell_exec('mkdir -p ' . $saveDir);
	foreach ($times as $time) {
		$diff      = strtotime($time) - $startLiveTime;
		$editStart = $diff > 60 ? $diff : strtotime($time);
		$start     = date('H:i:s', $editStart);
		$saveFile  = $saveDir . '/' . str_replace(':', '.', $time) . '.mp4';
		$cmd       = FFMPEG_EXEC_PATH . ' -i ' . $path . ' -ss ' . $start . ' -t 120 ' . ' -vcodec copy -acodec copy ' .
			$saveFile . '  > ffmper_edit.log 2>&1 & ';
		exec($cmd);
		echo '完成视频 ' . $start . ' 开始的剪辑' . PHP_EOL;
	}
	echo '剪辑完成 :)' . PHP_EOL;
}















