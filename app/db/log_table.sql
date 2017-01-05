-- ダンジョンクリア履歴
CREATE TABLE `user_log_clear_dungeon` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dungeon_floor_id` int(11) DEFAULT NULL,
  `sneaked_at` datetime DEFAULT NULL,
  `cleared_time` int(11) DEFAULT NULL,
  `deck` text,
  `data` text,
  `continue_cnt` int(11) NOT NULL DEFAULT 0,
  `nxc` int(11) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `id_on_user_log_clear_dungeon` (`id`),
  KEY `user_id_on_user_log_clear_dungeon` (`user_id`),
  KEY `dungeon_floor_id_on_user_log_clear_dungeon` (`dungeon_floor_id`),
  KEY `created_at_on_user_log_clear_dungeon` (`created_at`)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8;

-- ダンジョンクリア履歴のパーティション初期設定(日付は変えてください)
-- ALTER TABLE user_log_clear_dungeon
-- PARTITION BY RANGE columns(created_at) 
-- (PARTITION cd20120524 VALUES LESS THAN ('2012-05-25'), 
--  PARTITION cdover VALUES LESS THAN MAXVALUE) ;

-- ダンジョンクリア履歴に対しcronでのパーティション追加(日付は変えてください)
-- ALTER TABLE user_log_clear_dungeon
-- REORGANIZE PARTITION cdover INTO (
-- PARTITION cd20120525 VALUES LESS THAN ('2012-05-26'),
-- PARTITION cdover VALUES LESS THAN MAXVALUE
-- ) ;

-- 不正疑惑ダンジョンクリア履歴
CREATE TABLE `user_log_clear_dungeon_check` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dungeon_floor_id` int(11) DEFAULT NULL,
  `sneaked_at` datetime DEFAULT NULL,
  `cleared_time` int(11) DEFAULT NULL,
  `deck` text,
  `data` text,
  `continue_cnt` int(11) NOT NULL DEFAULT '0',
  `nxc` int(11) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `id_on_user_log_clear_dungeon_check` (`id`),
  KEY `user_id_on_user_log_clear_dungeon_check` (`user_id`),
  KEY `dungeon_floor_id_on_user_log_clear_dungeon_check` (`dungeon_floor_id`),
  KEY `created_at_on_user_log_clear_dungeon_check` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- 日本版ではCOMPRESSED未実施
ALTER TABLE user_log_clear_dungeon_check ROW_FORMAT=COMPRESSED;

-- 合成/進化/売却履歴
CREATE TABLE `user_log_modify_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mode_flg` tinyint(4) NOT NULL,
  `data` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `id_on_user_log_modify_cards` (`id`),
  KEY `user_id_on_user_log_modify_cards` (`user_id`),
  KEY `created_at_on_user_log_modify_cards` (`created_at`)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8;

-- 合成/進化/売却履歴のパーティション初期設定(日付は変えてください)
-- ALTER TABLE user_log_modify_cards
-- PARTITION BY RANGE COLUMNS(created_at) 
-- (PARTITION mc20120524 VALUES LESS THAN ('2012-05-25'), 
-- PARTITION mcover VALUES LESS THAN MAXVALUE) ;

-- 合成/進化/売却履歴に対しcronでのパーティション追加(日付は変えてください)
-- ALTER TABLE user_log_modify_cards
-- REORGANIZE PARTITION mcover INTO (
-- PARTITION mc20120525 VALUES LESS THAN ('2012-05-26'),
-- PARTITION mcover VALUES LESS THAN MAXVALUE
-- ) ;

-- ログイン履歴
CREATE TABLE `user_log_login` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `data` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `user_id_on_user_log_login` (`user_id`),
  KEY `ip_on_user_log_login` (`ip`),
  KEY `created_at_on_user_log_login` (`created_at`)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8;

-- ログイン履歴のパーティション初期設定(日付は変えてください)
-- ALTER TABLE user_log_login
-- PARTITION BY RANGE COLUMNS(created_at) 
-- (PARTITION li20120613 VALUES LESS THAN ('2012-06-14'), 
-- PARTITION liover VALUES LESS THAN MAXVALUE) ;

-- ログイン履歴に対しcronでのパーティション追加(日付は変えてください)
-- ALTER TABLE user_log_login
-- REORGANIZE PARTITION liover INTO (
-- PARTITION li20120614 VALUES LESS THAN ('2012-06-15'),
-- PARTITION liover VALUES LESS THAN MAXVALUE
-- ) ;


-- 魔宝石加算履歴
CREATE TABLE `user_log_add_gold` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type_flg` tinyint(4) NOT NULL,
  `data` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `id_on_user_log_add_gold` (`id`),
  KEY `user_id_on_user_log_add_gold` (`user_id`),
  KEY `created_at_on_user_log_add_gold` (`created_at`)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8;

-- 魔宝石加算履歴のパーティション初期設定(日付は変えてください)
-- ALTER TABLE user_log_add_gold
-- PARTITION BY RANGE COLUMNS(created_at) 
-- (PARTITION ag20120613 VALUES LESS THAN ('2012-06-14'), 
-- PARTITION agover VALUES LESS THAN MAXVALUE) ;

-- 魔宝石加算履歴に対しcronでのパーティション追加(日付は変えてください)
-- ALTER TABLE user_log_add_gold
-- REORGANIZE PARTITION agover INTO (
-- PARTITION ag20120614 VALUES LESS THAN ('2012-06-15'),
-- PARTITION agover VALUES LESS THAN MAXVALUE
-- ) ;

-- ユーザ毎の累計課金額
CREATE TABLE `user_log_purchase_amount` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id_on_amount_logs` (`user_id`),
  KEY `amount_on_amount_logs` (`amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- ダンジョン毎の潜入/クリア人数、魔石消費数/魔石消費人数
CREATE TABLE `dungeon_log_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dungeon_floor_id` int(11) DEFAULT NULL,
  `sneaked_cnt` int(11) DEFAULT 0,
  `cleared_cnt` int(11) DEFAULT 0,
  `all_continue_cnt` int(11) DEFAULT 0,
  `unique_continue_cnt` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dungeon_floor_id_on_dungeon_log_data` (`dungeon_floor_id`),
  KEY `created_at_on_dungeon_log_data` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 管理者の補填実行履歴
CREATE TABLE `admin_log_support` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_username` text,
  `user_id` int(11) NOT NULL,
  `bonus_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `data` text,
  `memo` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at_on_admin_log_support` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
alter table admin_log_support add column changed_user_id int(11) default null after user_id;

-- アクティブユーザ数集計 デイリー
CREATE TABLE `daily_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_daily_active_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 ウィークリー
CREATE TABLE `weekly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`from_date`),
  KEY `from_date_on_weekly_active_user_count` (`from_date`),
  KEY `to_date_on_weekly_active_user_count` (`to_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 マンスリー
CREATE TABLE `monthly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_monthly_active_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 デイリー iOS
CREATE TABLE `ios_daily_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_ios_daily_active_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 デイリー Android
CREATE TABLE `adr_daily_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_adr_daily_active_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 ウィークリー iOS
CREATE TABLE `ios_weekly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`from_date`),
  KEY `from_date_on_ios_weekly_active_user_count` (`from_date`),
  KEY `to_date_on_ios_weekly_active_user_count` (`to_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 ウィークリー Android
CREATE TABLE `adr_weekly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`from_date`),
  KEY `from_date_on_adr_weekly_active_user_count` (`from_date`),
  KEY `to_date_on_adr_weekly_active_user_count` (`to_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 マンスリー iOS
CREATE TABLE `ios_monthly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_ios_monthly_active_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 マンスリー Android
CREATE TABLE `adr_monthly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_adr_monthly_active_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 デイリー DB別
CREATE TABLE `server_daily_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `server` tinyint(4) NOT NULL,
  `device_type` tinyint(4) NOT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`,`server`,`device_type`),
  KEY `date_on_server_daily_active_user_count` (`date`),
  KEY `server_on_server_daily_active_user_count` (`server`),
  KEY `device_type_on_server_daily_active_user_count` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 デイリー Kindle
CREATE TABLE `amz_daily_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_amz_daily_active_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 ウィークリー Kindle
CREATE TABLE `amz_weekly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`from_date`),
  KEY `from_date_on_amz_weekly_active_user_count` (`from_date`),
  KEY `to_date_on_amz_weekly_active_user_count` (`to_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- アクティブユーザ数集計 マンスリー Kindle
CREATE TABLE `amz_monthly_active_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_amz_monthly_active_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 魔法石所持数集計 デイリー
CREATE TABLE `daily_possession_gold` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_year` smallint(6) NOT NULL,
  `target_month` tinyint(4) NOT NULL,
  `target_day` tinyint(4) NOT NULL,
  `device_type` tinyint(4) NOT NULL,
  `all_gold_cnt` int(11) NOT NULL,
  `purchase_gold_cnt` int(11) NOT NULL,
  `nopurchase_gold_cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `target_year` (`target_year`,`target_month`,`target_day`,`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

CREATE TABLE user_unfair_monitoring
(
	id int NOT NULL AUTO_INCREMENT,
	user_id int NOT NULL,
	ip varchar(255),
	dev varchar(255),
	osv varchar(255),
	monitoring_status smallint,
	monitoring_start_time datetime,
	check_last_time datetime,
	updated_at datetime,
  	created_at datetime,
	PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;
CREATE INDEX user_unfair_monitoring_idx1 ON user_unfair_monitoring(user_id);

CREATE TABLE user_unfair_monitoring_history
(
	id int NOT NULL AUTO_INCREMENT,
	user_id int,
	before_ip varbinary(255),
	before_dev varchar(255),
	before_osv varchar(255),
	after_ip varchar(255),
	after_dev varchar(255),
	after_osv varchar(255),
	updated_at datetime,
  	created_at datetime,
	PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;
CREATE INDEX user_unfair_monitoring_history_idx1 ON user_unfair_monitoring_history(user_id);

CREATE TABLE `support_csv_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `version` int(11) DEFAULT NULL,
  `gzip_data` mediumblob,
  `length` int(11) DEFAULT NULL,
  `max_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `access_block_log_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) NOT NULL,
  `server_info` text NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `access_block_log_data_idx1` (`ip`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

-- 管理者ログイン履歴
CREATE TABLE admin_log_login (
  id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(45) NOT NULL, -- 管理者名
  ip VARCHAR(255) NOT NULL, -- IP
  user_agent TEXT NOT NULL, -- ユーザエージェント
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 デイリー
CREATE TABLE `daily_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_daily_playing_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 マンスリー
CREATE TABLE `monthly_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_monthly_playing_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 デイリー iOS
CREATE TABLE `ios_daily_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_ios_daily_playing_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 デイリー Android
CREATE TABLE `adr_daily_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_adr_daily_playing_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 デイリー Kindle
CREATE TABLE `amz_daily_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_area` (`date`,`area`),
  KEY `date_on_amz_daily_playing_user_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 マンスリー iOS
CREATE TABLE `ios_monthly_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_ios_monthly_playing_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 マンスリー Android
CREATE TABLE `adr_monthly_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_adr_monthly_playing_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 マンスリー Kindle
CREATE TABLE `amz_monthly_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `area` tinyint(4) DEFAULT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `year_area` (`year`,`month`,`area`),
  KEY `year_month_on_amz_monthly_playing_user_count` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- プレイングユーザ数集計 デイリー DB別
CREATE TABLE `server_daily_playing_user_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `server` tinyint(4) NOT NULL,
  `device_type` tinyint(4) NOT NULL,
  `cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`,`server`,`device_type`),
  KEY `date_on_server_daily_playing_user_count` (`date`),
  KEY `server_on_server_daily_playing_user_count` (`server`),
  KEY `device_type_on_server_daily_playing_user_count` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- OS間データ移行集計 デイリー 
CREATE TABLE `daily_change_device_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `device_from` tinyint(4) NOT NULL,
  `device_to` tinyint(4) NOT NULL,
  `user_cnt` int(11) NOT NULL,
  `gold_cnt` int(11) NOT NULL,
  `pgold_cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_and_os` (`date`,`device_from`,`device_to`),
  KEY `date_on_daily_change_device_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- AREA間データ移行集計 デイリー 
CREATE TABLE `daily_change_area_count` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `area_from` tinyint(4) NOT NULL,
  `area_to` tinyint(4) NOT NULL,
  `user_cnt` int(11) NOT NULL,
  `gold_cnt` int(11) NOT NULL,
  `pgold_cnt` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_and_area` (`date`,`area_from`,`area_to`),
  KEY `date_on_daily_change_area_count` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 見込み売上集計 マンスリー
CREATE TABLE `prospective_monthly_sales` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `ios_start_date` datetime DEFAULT NULL,
  `ios_end_date` datetime DEFAULT NULL,
  `adr_start_date` datetime DEFAULT NULL,
  `adr_end_date` datetime DEFAULT NULL,
  `amz_start_date` datetime DEFAULT NULL,
  `amz_end_date` datetime DEFAULT NULL,
  `ios_pr_amount` bigint(11) NOT NULL,
  `adr_pr_amount` bigint(11) NOT NULL,
  `amz_pr_amount` bigint(11) NOT NULL,
  `tmp1_pr_amount` bigint(11) NOT NULL,
  `tmp2_pr_amount` bigint(11) NOT NULL,
  `ios_real_amount` bigint(11) NOT NULL,
  `adr_real_amount` bigint(11) NOT NULL,
  `amz_real_amount` bigint(11) NOT NULL,
  `tmp1_real_amount` bigint(11) NOT NULL,
  `tmp2_real_amount` bigint(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `yearmonth` (`year`,`month`),
  KEY `year_month_on_prospective_monthly_sales` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザサポート用メモ
CREATE TABLE support_memo
(
  `user_id` int NOT NULL,
  `message` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- メール削除ログ集約
CREATE TABLE user_log_delete_mail_aggregate (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT,
  received_at DATETIME NOT NULL,
  data TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX user_id_on_user_log_delete_mail_aggregate(user_id),
  INDEX sender_id_on_user_log_delete_mail_aggregate(sender_id),
  INDEX received_at_user_id_on_user_log_delete_mail_aggregate(received_at, user_id),
  INDEX received_at_sender_id_on_user_log_delete_mail_aggregate(received_at, sender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ダンジョン潜入履歴
-- 本番はRedshiftで運用するのでRDSにはテーブル作成しない
CREATE TABLE user_log_sneak_dungeon (
`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`user_id` int(11) NOT NULL,
`dungeon_floor_id` int(11) DEFAULT NULL,
`device_type` tinyint(4) NOT NULL,
`area_id` tinyint(4) NOT NULL DEFAULT 0,
`sneaked_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- メダルガチャのログ(パズドラW)
CREATE TABLE w_user_log_medal_gacha (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  data TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX user_id_on_w_user_log_medal_gacha(user_id),
  INDEX created_at_on_w_user_log_medal_gacha(created_at)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8;

-- ダンジョンクリア履歴(パズドラW）
CREATE TABLE `w_user_log_clear_dungeon` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dungeon_floor_id` int(11) DEFAULT NULL,
  `cleared_time` int(11) DEFAULT NULL,
  `sneaked_at` datetime DEFAULT NULL,
  `eggs` text,
  `aitems` text,
  `helper_user_id` int(11),
  `helper_aitems` text,
  `continue_cnt` int(11) NOT NULL DEFAULT 0,
  `data` TEXT,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `id_on_w_user_log_clear_dungeon` (`id`),
  KEY `user_id_on_w_user_log_clear_dungeon` (`user_id`),
  KEY `dungeon_floor_id_on_w_user_log_clear_dungeon` (`dungeon_floor_id`),
  KEY `created_at_on_w_user_log_clear_dungeon` (`created_at`)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8;

-- マスターデータ承認ログ
CREATE TABLE assent_master_data (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  date DATETIME NOT NULL,
  type TINYINT NOT NULL,
  assent_id BIGINT NOT NULL,
  flg TINYINT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 不正な購入履歴
CREATE TABLE `invalid_purchase_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `device_type` TINYINT NOT NULL DEFAULT 0, -- デバイスタイプ # iOS=0, Android=1, Kindle=2
  `product_id` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(511) DEFAULT NULL,
  `amz_id` varchar(255) DEFAULT NULL,
  `receipt` text,
  `data` text,
  `signature` text,
  `status` varchar(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  KEY `user_id_on_invalid_purchase_logs` (`user_id`),
  KEY `tid_on_invalid_purchase_logs` (`transaction_id`(255)),
  KEY `pid_on_invalid_purchase_logs` (`product_id`),
  KEY `created_at_on_invalid_purchase_logs` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 管理ユーザ変更履歴
CREATE TABLE admin_log_user_edit (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `userid` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `role` varchar(45) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `user_agent` text NOT NULL,
  `target_userid` int(11) NOT NULL,
  `target_username` varchar(45) NOT NULL,
  `target_role` varchar(45) NOT NULL,
  `action` tinyint(4) NOT NULL,
  `message` varchar(256) NOT NULL,
  `old_status` varchar(45) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
