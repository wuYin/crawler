## 概述

使用 PHP 实现的 B 站直播间弹幕和礼物爬虫、弹幕分析与精彩时刻自动剪辑脚本。

抓取细节：[B 站直播间数据爬虫](https://wuyin.io/2018/03/31/bilibili-live-crawler-and-auto-edit-recording/#more)


## 前言

### 起因

想当初，我也想做一名 UP，自己做吃鸡的精彩集锦，遇到的第一个困难便是高能的精彩素材。可以守着主播的直播间，使用录屏软件录播，记下高能时刻，手动剪辑。也可以更高效：

- Crontab 定时任务检测直播间开播状态
- 截获直播间推流地址后，使用 FFmpeg 自动录播和剪辑
- 大多数用户只在主播有精彩操作时发弹幕送礼物，编写爬虫抓取直播间的弹幕、礼物数据
- 为弹幕做分词处理，“学不来”、“基本操作”、666、233…等词集中出现时，认为有精彩素材，同时还能做粉丝的弹幕行为分析等等。

### 依据

我和室友都经常看直播，但很少发弹幕和送礼物，只有在主播玩出很溜的操作或讲很好玩的事情时，才会发弹幕互动、送礼物支持。基于这个用户习惯，不难推断出在直播间的弹幕高峰或礼物高峰期，主播应该做了些好玩的事情，比如惊险吃鸡或全队被灭之类的…这些时间段都可以作为精彩时刻的素材。



### 使用

在 config.php 和 const.php 中配置数据库后执行 SQL 文件，指定下载好的  [FFmpeg](https://www.ffmpeg.org/download.html) 可执行文件位置。代码量 600 行左右，注释写得比较清楚，看源码就 ok



### 目录结构

```shell
> bilibili-live-crawler $ tree -L 2
.
├── README.md
├── config.php		# 配置文件：配置 FFmpeg 可执行文件的位置，录像的保存路径
├── const.php		# 常量文件：API 地址，定义数据库用户名和密码、弹幕激增的判定参数等
├── crawler.php		# 连接并抓取弹幕服务器的数据
├── cut_words
│   └── seg.php		# 分词脚本：将弹幕做分词处理，可用于生成本次直播的词图
├── db.sql		# 数据存储
├── edit.php		# 剪辑脚本
├── functions.php	# 公用函数
└── visual_data.php	# 直播数据可视化文件脚本
```



## 实现效果

### 弹幕、礼物和礼物数据爬虫：

<img src="https://contents.yinzige.com/crawler.png" width=70%/>

### 弹幕分析：

<img src="https://contents.yinzige.com/cuts.png" width=70%/>



### 弹幕数据统计：

<img src="https://contents.yinzige.com/visu.png" width=70%/>



### 根据弹幕和礼物高峰自动剪辑

<img src="https://contents.yinzige.com/edit_shell.png" width=70%/>



### 精彩集锦的素材
<img src="https://contents.yinzige.com/saved.png" width=70%/>
