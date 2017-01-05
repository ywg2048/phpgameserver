-- ユーザ
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  camp TINYINT NOT NULL DEFAULT -1,
  lv SMALLINT NOT NULL DEFAULT 1,
  exp INT UNSIGNED NOT NULL DEFAULT 0,
  stamina SMALLINT NOT NULL DEFAULT 10,
  stamina_max SMALLINT NOT NULL DEFAULT 10,
  stamina_recover_time TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', -- スタミナが全快する日時
  gold INT NOT NULL DEFAULT 0, -- 魔法石(無料)
  pgold INT NOT NULL DEFAULT 0, -- 魔法石(有料)
  coin INT NOT NULL DEFAULT 0, -- ゲーム内通貨
  fripnt SMALLINT NOT NULL DEFAULT 0, -- 友情ポイント
  pbflg INT NOT NULL DEFAULT 0, -- お礼返しを送ったアカウントID
  card_max SMALLINT NOT NULL DEFAULT 50, -- 最大カード所有数
  li_last DATETIME,
  li_str SMALLINT NOT NULL DEFAULT 0,
  li_max SMALLINT NOT NULL DEFAULT 0,
  li_cnt SMALLINT NOT NULL DEFAULT 0,
  li_days SMALLINT NOT NULL DEFAULT 0,
  cost_max SMALLINT NOT NULL DEFAULT 20,
  fr_cnt SMALLINT NOT NULL DEFAULT 0, -- フレンド申請受信数
  pback_cnt SMALLINT NOT NULL DEFAULT 0, -- お礼返し保持数
  friend_max SMALLINT NOT NULL DEFAULT 20,
  fricnt SMALLINT NOT NULL DEFAULT 0, -- フレンド数
  lc VARCHAR(255) NULL, -- [cuid,id,lv,slv,hp,atk,rec,psk]のリーダーカード配列
  us INT NOT NULL DEFAULT 0, -- ユーザー設定(ビット配列)
  w_pflg tinyint(4) NOT NULL DEFAULT '0', -- たまドラモードの利用フラグ 0:未利用 1:利用済み
  w_stamina smallint(6) NOT NULL DEFAULT '5',
  w_stamina_recover_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  medal int(11) NOT NULL DEFAULT '0', -- たまドラモードメダル
  eq1_id smallint(6) NOT NULL, -- たまドラモード アバター：頭
  eq1_lv tinyint(4) NOT NULL DEFAULT '0', -- たまドラモード アバター：頭レベル
  eq2_id smallint(6) NOT NULL, -- たまドラモード アバター：持ち物
  eq2_lv tinyint(4) NOT NULL DEFAULT '0', -- たまドラモード アバター：持ち物レベル
  eq3_id smallint(6) NOT NULL, -- たまドラモード アバター：カラ
  eq3_lv tinyint(4) NOT NULL DEFAULT '0', -- たまドラモード アバター：カラレベル
  w_pgetflg tinyint(4) NOT NULL DEFAULT '0', -- その日初めて pw_getpdate を実行したかフラグ 0:実行済み 1:未実行 
  accessed_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', -- 最終アクセス時間
  accessed_on DATE NOT NULL, -- 最終アクセス日
  dev VARCHAR(255), -- 機種名
  osv VARCHAR(255), -- OSバージョン
  device_type TINYINT NOT NULL DEFAULT 0, -- デバイスタイプ # iOS=0, Android=1, Kindle=2
  area_id TINYINT NOT NULL DEFAULT 0,  -- 0:日本 1:北米 2:韓国 3:欧州 4:香港/台湾
  del_status TINYINT NOT NULL DEFAULT 0, -- 削除状態 0:normal 1:del 2:ban 3:freeze
  created_on DATE NOT NULL,  -- 登録日
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  
  INDEX lv_accessed_at_on_users(lv, accessed_at desc),
  INDEX device_type_on_users(device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
PARTITION BY LINEAR HASH (id) PARTITIONS 16;

-- デッキ
DROP TABLE IF EXISTS user_deck;
CREATE TABLE user_deck (
  id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  deck_num TINYINT NOT NULL DEFAULT 0,
  decks TEXT, -- デッキリストのJSON文字列
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  
  UNIQUE KEY user_id_on_user_deck (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザが保持しているカード
DROP TABLE IF EXISTS user_cards;
CREATE TABLE user_cards (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  card_id SMALLINT NOT NULL,
  cuid INT UNSIGNED NOT NULL, -- ユーザ&カードごとにユニークなID
  exp MEDIUMINT UNSIGNED NOT NULL DEFAULT 0, -- カード経験値
  lv TINYINT NOT NULL DEFAULT 0, -- レベル
  slv TINYINT NOT NULL DEFAULT 0, -- スキルレベル
  equip1 TINYINT NULL, -- 装備1
  equip2 TINYINT NULL, -- 装備2
  equip3 TINYINT NULL, -- 装備3
  equip4 TINYINT NULL, -- 装備4
  mcnt MEDIUMINT UNSIGNED NOT NULL DEFAULT 0, -- 合成回数
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', -- 取得日時
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (user_id, cuid),
  INDEX id_on_user_cards(id),
  INDEX card_id_lv_on_user_cards(card_id,lv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
PARTITION BY LINEAR HASH (user_id) PARTITIONS 16;

-- ユーザが潜入中のダンジョン
DROP TABLE IF EXISTS user_dungeons;
CREATE TABLE user_dungeons (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  dungeon_id SMALLINT NOT NULL,
  dungeon_floor_id INT NOT NULL, 
  cm TINYINT(1) NOT NULL DEFAULT 0, 
  hash VARCHAR(255) NOT NULL,
  btype TINYINT NULL, -- ボーナスタイプ
  barg INT NULL, -- ボーナス倍率
  beat_bonuses TEXT NOT NULL, -- クリア時獲得ボーナス(卵or宝箱)を表現したJSON文字列
  exp INT NOT NULL DEFAULT 0, -- クリア時獲得経験値
  coin INT NOT NULL DEFAULT 0, -- クリア時獲得コイン
  gold INT NOT NULL DEFAULT 0, -- クリア時獲得ゴールド
  lvup TINYINT(1) NOT NULL DEFAULT 0, -- クリア時レベルアップ有無
  spent_stamina MEDIUMINT NOT NULL DEFAULT 0, -- 潜入時に消費したスタミナ量
  stamina_spent_at TIMESTAMP NULL, -- スタミナ消費日時(正常に潜入したかどうかを管理)
  cleared_at TIMESTAMP NULL, -- クリア日時(ボーナス付与済みかどうかを管理)
  sneak_time VARCHAR(15) NULL, -- 初回潜入時間(正常に潜入したかどうかを管理)
  helper_card_id SMALLINT NULL, -- 助っ人のリーダーモンスター
  helper_card_lv TINYINT NULL, -- 助っ人のリーダーモンスターLV
  continue_cnt SMALLINT NOT NULL DEFAULT 0, -- コンテニュー回数
  sr INT NOT NULL DEFAULT 0, -- Sスコアランク（スコア対象ダンジョン以外は0）
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',

  UNIQUE(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザのフロア攻略状態.
DROP TABLE IF EXISTS user_dungeon_floors;
CREATE TABLE user_dungeon_floors (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  dungeon_id INT NOT NULL,
  dungeon_floor_id INT NOT NULL,
  first_played_at DATETIME NULL,
  cleared_at DATETIME NULL,
  cm1_first_played_at DATETIME NULL,
  cm1_cleared_at DATETIME NULL,
  cm2_first_played_at DATETIME NULL,
  cm2_cleared_at DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (user_id, dungeon_id, dungeon_floor_id),
  INDEX id_on_user_dungeon_floors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
PARTITION BY LINEAR HASH (user_id) PARTITIONS 16;

-- メール
DROP TABLE IF EXISTS user_mails;
CREATE TABLE user_mails (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type TINYINT NOT NULL, -- 0:none, 1:friend request, 2:thank-you gift, 3:message
  user_id INT NOT NULL,
  sender_id INT,
  message TEXT,
  data text,
  bonus_id SMALLINT,
  amount SMALLINT,
  slv TINYINT NOT NULL DEFAULT 1,
  plus_hp TINYINT NOT NULL DEFAULT 0,
  plus_atk TINYINT NOT NULL DEFAULT 0,
  plus_rec TINYINT NOT NULL DEFAULT 0,
  offered TINYINT NOT NULL DEFAULT 0, -- bonus 付与済みであれば 1
  fav TINYINT NOT NULL DEFAULT 0, -- お気に入りフラグ 1:お気に入り
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY(user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- フレンド
DROP TABLE IF EXISTS friends;
CREATE TABLE friends (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id1 INT NOT NULL,
  user_id2 INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  
  UNIQUE(user_id1, user_id2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 全ユーザボーナス付与履歴
DROP TABLE IF EXISTS all_user_bonus_histories;
CREATE TABLE all_user_bonus_histories (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  all_user_bonus_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE(user_id, all_user_bonus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- コンティニュー
DROP TABLE IF EXISTS user_continue;
CREATE TABLE user_continue (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  hash VARCHAR(255) NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0, -- コンティニュー実行済みであれば1
  data TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_and_hash(user_id, hash),
  INDEX created_at_used_on_user_continue(created_at, used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザが保持しているカードのシーケンス
DROP TABLE IF EXISTS user_card_seq;
CREATE TABLE user_card_seq (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  max_cuid INT ,
  UNIQUE(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- レアガチャのログ
DROP TABLE IF EXISTS user_log_rare_gacha;
CREATE TABLE user_log_rare_gacha (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  data TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_on_user_log_rare_gacha(user_id),
  INDEX created_at_on_user_log_rare_gacha(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 追加ガチャのログ
DROP TABLE IF EXISTS user_log_extra_gacha;
CREATE TABLE user_log_extra_gacha (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  extra_gacha_id TINYINT NOT NULL,
  data TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX user_id_on_user_log_extra_gacha(user_id),
  INDEX extra_gacha_id_on_user_log_extra_gacha(extra_gacha_id),
  INDEX created_at_on_user_log_extra_gacha(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 魔法石削除（決済取り消し）のログ
DROP TABLE IF EXISTS user_log_del_magic_stone;
CREATE TABLE user_log_del_magic_stone (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  data TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_on_user_log_del_magic_stone(user_id),
  INDEX created_at_on_user_log_del_magic_stone(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- メール削除のログ
DROP TABLE IF EXISTS user_log_delete_mail;
CREATE TABLE user_log_delete_mail (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  data TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_on_user_log_delete_mail(user_id),
  INDEX created_at_on_user_log_delete_mail(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- スタミナ回復購入のログ
DROP TABLE IF EXISTS user_log_buy_stamina;
CREATE TABLE user_log_buy_stamina (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  data TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_on_user_log_buy_stamina(user_id),
  INDEX created_at_on_user_log_buy_stamina(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- カードスロット拡張のログ
DROP TABLE IF EXISTS user_log_expand_num_cards;
CREATE TABLE user_log_expand_num_cards (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  data TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_on_user_log_expand_num_cards(user_id),
  INDEX created_at_on_user_log_expand_num_cards(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- フレンド数拡張のログ
DROP TABLE IF EXISTS user_log_buy_friend_max;
CREATE TABLE user_log_buy_friend_max (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL ,
  data TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_on_user_log_user_log_buy_friend_max(user_id),
  INDEX created_at_on_user_log_user_log_buy_friend_max(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザーコラボレーション（付与履歴）
DROP TABLE IF EXISTS user_collaboration;
CREATE TABLE user_collaboration (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  `type` INT NOT NULL,    -- 付与するカードタイプ
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX user_id_typed_on_user_collaboration(user_id, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザーアップロードデータ
CREATE TABLE user_upload_data (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id int NOT NULL, -- アカウントID
  `type` int NOT NULL, -- アップロードデータ種類 0:図鑑,1:モンスターお気に入り,2:フレンドお気に入り&フレンド使用回数
  dcnt int NOT NULL, -- アップロードカウンタ
  data text DEFAULT NULL, -- データ文字列
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY (user_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 名前変更ログ
DROP TABLE IF EXISTS user_log_change_name;
CREATE TABLE `user_log_change_name` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `data` text,
  `admin_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id_on_user_log_change_name` (`user_id`),
  KEY `created_at_on_user_log_change_name` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 機種変更データ
CREATE TABLE change_device_data (
  id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL, -- アカウントID
  code VARCHAR(12), -- 機種更コード
  updated_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 機種変更履歴
CREATE TABLE `user_log_change_device` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `changed_user_id` int(11) DEFAULT NULL,
  `data` text,
  `admin_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id_on_user_log_change_device` (`user_id`),
  KEY `created_at_on_user_log_change_device` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザーダンジョンスコア
CREATE TABLE user_dungeon_scores (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  dungeon_floor_id INT NOT NULL,
  high_score INT NOT NULL, -- ハイスコア
  high_score_at DATETIME NOT NULL, -- ハイスコア達成日時
  srank_at DATETIME, -- Sランク達成日時
  updated_at timestamp NOT NULL,
  created_at timestamp NOT NULL,
  UNIQUE KEY (user_id, dungeon_floor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザーアカバンメッセージ
CREATE TABLE user_ban_messages (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message varchar(255),
  updated_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 購入したダンジョン
CREATE TABLE user_buy_dungeon (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  dungeon_id INT NOT NULL,
  expire_at INT NOT NULL,
  buy_at DATETIME NOT NULL ,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  UNIQUE KEY (user_id, dungeon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 購入したダンジョン履歴
CREATE TABLE user_buy_dungeon_history (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  dungeon_id INT NOT NULL,
  expire_at INT NOT NULL,
  buy_at DATETIME NOT NULL ,
  before_coin INT NOT NULL,
  after_coin INT NOT NULL,
  updated_at DATETIME NOT NULL ,
  created_at DATETIME NOT NULL ,
  INDEX user_id_on_user_buy_dungeon_history(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

