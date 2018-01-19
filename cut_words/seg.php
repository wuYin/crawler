<?php
/**
 * Created by PhpStorm.
 * User: wuYin
 * Date: 18/1/18
 * Time: 21:08
 */
$sentence  = $argv[1];
$freqs     = generatePrefixDict();
$DAG       = getDAG($sentence);
$wordPreqs = $freqs['word_freqs'];
$totalFreq = $freqs['total_freq'];
$allWords = divideSentence($sentence, $DAG);
print_r($allWords);


// 生成前缀词典
function generatePrefixDict() {

	$file = fopen('./dict.txt', 'r');

	$wordFreqs = [];
	$totalFreq = 0;

	while (($line = fgets($file)) !== false) {
		$data             = explode(' ', trim($line));
		$word             = $data[0];
		$freq             = $data[1];
		$totalFreq        += $freq;
		$wordFreqs[$word] = $freq;        // 词作为 key, 频率为 value

		// 获取词语的所有前缀词
		$tmpWord = '';
		$words   = mbStrSplit($word);
		foreach ($words as $loc => $char) {
			$tmpWord .= $char;
			if (!array_key_exists($tmpWord, $wordFreqs)) {
				$wordFreqs[$tmpWord] = 0;
			}
		}
	}
	fclose($file);

	return ['word_freqs' => $wordFreqs, 'total_freq' => $totalFreq];
}


// 获取句子的 DAG 图
function getDAG($sentence) {
	global $freqs;

	$DAG           = [];
	$sentenceWords = mbStrSplit($sentence);
	$length        = count($sentenceWords);
	$wordPreqs     = $freqs['word_freqs'];

	for ($loc = 0; $loc < $length; $loc++) {
		$tempDAG = [];
		$nextLoc = $loc;

		$fragment = $sentenceWords[$loc];

		// 判断片段是否存在与前缀词典中，不在则跳出此循环词频为 0
		while ($nextLoc < $length && array_key_exists($fragment, $wordPreqs)) {
			if ($wordPreqs[$fragment] > 0) {
				$tempDAG[] = $nextLoc;
			}
			$nextLoc++;
			if ($nextLoc == $length) {
				continue;
			}
			$fragment .= $sentenceWords[$nextLoc];
		}
		if (empty($tempDAG)) {      // 无匹配的词
			$tempDAG[] = $loc;
		}
		$DAG[] = $tempDAG;
	}

	return $DAG;
}

// 将句子尽可能多的分割为最小粒度的词语
function divideSentence($sentence, $DAG) {
	$end   = -1;
	$words = [];
	foreach ($DAG as $loc => $nextLocs) {
		// 单独的字
		if ($loc > $end && count($nextLocs) == 1) {
			$word    = mb_substr($sentence, $loc, 1, 'UTF-8');
			$words[] = $word;
			$end     = $loc;        // 计词清零
		} else {
			// 向后有多个字可以成词
			foreach ($nextLocs as $nextLoc) {
				if ($nextLoc > $loc) {
					$word    = mb_substr($sentence, $loc, ($nextLoc - $loc) + 1, 'UTF-8');
					$words[] = $word;
					$end     = $nextLoc;        // 计词清零
				}
			}
		}
	}
	return $words;
}

// 返回后向分割的字串
function splitFromEnd($words, $end) {
	$str = '';
	foreach ($words as $loc => $word) {
		if ($loc >= $end) {
			$str .= $word;
		}
	}
	return $str;
}

// 中文的字符分割
function mbStrSplit($str) {
	return preg_split('/(?<!^)(?!$)/u', $str);
}

// 语义分割
function calculate($wordFreqs, $totalFreq, $sentence, $DAG) {
	$words          = mbStrSplit($sentence);
	$route          = [];
	$length         = count($words);
	$route[$length] = [0, 0];
	// 从后向前计算路径值
	for ($forward = $length - 1; $forward >= 0; $forward--) {

		foreach ($DAG[$forward] as $loc => $path) {
			// 计算对数概率得分
			$max       = 0;
			$tempStr   = splitFromEnd($words, $forward);
			$freq      = $wordFreqs[$tempStr];
			$wordScore = $freq == 0 ? 0 : log($freq);
			$result    = $wordScore - log($totalFreq) + $route[$forward + 1][0];
			$max       = $result > $max ? $result : $max;
		}
		$route[$forward] = [$max, $path];
	}
}
