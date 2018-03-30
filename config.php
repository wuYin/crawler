<?php

/**
 * ffmpeg 的可执行文件路径
 * macOS 可使用 brew install ffmpeg 安装
 * linux 可到 ffmpeg.org/download.html#build-linux 下载，编译安装后指定 bin/ffmpeg 的绝对路径即可
 */
const FFMPEG_EXEC_PATH = 'ffmpeg';

/**
 * 录像的保存路径
 */
const VIDEO_SAVE_DIR = '/Users/wuyin/Desktop/';

/**
 * 最大可使用的内存
 */
const MAX_ALLOW_MEMORY = 4 * 1024 * 1024 * 1024;  // 4 GB