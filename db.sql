CREATE TABLE danmu (
  id         INT          NOT NULL PRIMARY KEY AUTO_INCREMENT,
  room_id    INT          NOT NULL             DEFAULT 0,
  uid        INT          NOT NULL             DEFAULT 0,
  uname      VARCHAR(255) NOT NULL             DEFAULT '' ,
  level      TINYINT      NOT NULL             DEFAULT 0
  COMMENT '用户等级 ul',
  content    VARCHAR(255) NOT NULL             DEFAULT ''
  COMMENT '弹幕内容',
  created_at TIMESTAMP    NOT NULL             DEFAULT NOW(),
  updated_at TIMESTAMP    NOT NULL             DEFAULT NOW() ON UPDATE NOW()
)
  CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE gifts (
  id         INT          NOT NULL PRIMARY KEY AUTO_INCREMENT,
  room_id    INT          NOT NULL             DEFAULT 0,
  uid        INT          NOT NULL             DEFAULT 0,
  gift_id    INT          NOT NULL             DEFAULT 0,
  gift_name  VARCHAR(255) NOT NULL             DEFAULT '',
  price      INT          NOT NULL             DEFAULT 0.0,
  num        INT          NOT NULL             DEFAULT 0,
  created_at TIMESTAMP    NOT NULL             DEFAULT NOW(),
  updated_at TIMESTAMP    NOT NULL             DEFAULT NOW() ON UPDATE NOW()
)
  CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;