<?php

const ROOM_INIT_API   = 'https://api.live.bilibili.com/room/v1/Room/room_init?id=';
const ROOM_SERVER_API = 'https://api.live.bilibili.com/api/player?id=cid:';
const ROOM_PLAY_API   = 'https://api.live.bilibili.com/room/v1/Room/playUrl?cid=';

const ACTION_ENTRY     = 7;
const ACTION_HEARTBEAT = 2;
const UID              = 322892;

const DB_NAME   = 'bilibili';
const DB_HOST   = '127.0.0.1';
const DB_USER   = 'root';
const DB_PASSWD = '123456';


const GIFT_RATE = 1000;        // B 站礼物 1000 金瓜子 = 1 RMB


/**
 * 视频中高能时刻的判定参数
 * 如视频中任意连续的三分钟：m1, m2, m3
 * 若弹幕激增：m2 的弹幕量是 m1 的两倍及以上
 * 若弹幕骤减：m3 的弹幕量是 m2 的一半及以下
 * 则说明 m2 这一分钟内主播有高能精彩时刻
 * 加上高能的前因后果，m1, m2, m3 连续的三分钟视频就是高质量素材
 */
// 弹幕增减标准
const BARRAGE_RATE_UP   = 2;
const BARRAGE_RATE_DOWN = 0.5;

// 礼物增减标准
const GIFT_RATE_UP   = 2.5;
const GIFT_RATE_DOWN = 0.4;