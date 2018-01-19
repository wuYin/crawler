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

// echo '将语句分为各个有可能组合的词语...' . PHP_EOL;
// $allWords = divideAllWords($sentence, $DAG);
// print_r($allWords);

echo '将语句分为各个最细粒度的词语...' . PHP_EOL;
$semanticWords = divideSemanticWords($wordPreqs, $totalFreq, $sentence, $DAG);
print_r($semanticWords);


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
function divideAllWords($sentence, $DAG) {
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

// 返回分割的中文字串
function splitByIndexes($words, $start, $end) {
	$str = '';
	foreach ($words as $loc => $word) {
		if ($loc >= $start && $loc <= $end) {
			$str .= $word;
		}
	}
	return $str;
}

// 中文的字符分割
function mbStrSplit($str) {
	return preg_split('/(?<!^)(?!$)/u', $str);
}

// 最大概率的语义分割
function divideSemanticWords($wordFreqs, $totalFreq, $sentence, $DAG) {
	$words           = mbStrSplit($sentence);
	$length          = count($words);
	$routes[$length] = [0, 0];
	// 从后向前计算路径值
	for ($loc = $length - 1; $loc >= 0; $loc--) {
		$max        = -1000;
		$maxNextLoc = 0;
		foreach ($DAG[$loc] as $nextLoc) {
			// 计算对数概率得分
			$tempStr   = splitByIndexes($words, $loc, $nextLoc);
			$freq      = array_key_exists($tempStr, $wordFreqs) ? $wordFreqs[$tempStr] : 0;
			$wordScore = $freq == 0 ? 1 : $freq;
			$result    = log($wordScore) - log($totalFreq) + $routes[$nextLoc + 1][0];
			if ($result > $max) {
				$max        = $result;
				$maxNextLoc = $nextLoc;
			}
		}
		$routes[$loc] = [$max, $maxNextLoc];
	}

	$semanticWords = [];
	$startLoc      = 0;
	while ($startLoc != $length) {
		$route           = $routes[$startLoc];
		$semanticWords[] = splitByIndexes($words, $startLoc, $route[1]);
		if ($startLoc == $route[1]) {
			$startLoc++;
		} else {
			$startLoc = $route[1] + 1;      // 直接从下一个词开始
		}
	}
	return $semanticWords;
}