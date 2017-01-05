
-- ユーザ端末
CREATE TABLE user_devices (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, # FK to users table
  type TINYINT NOT NULL, # iOS=0, Android=1, Kindle=2
  uuid VARCHAR(255) NOT NULL,
  dbid TINYINT NOT NULL,
  version VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  updated_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY (type, uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE user_devices AUTO_INCREMENT = 123795846;

-- ダンジョン
CREATE TABLE dungeons (
  id INT NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  attr SMALLINT NOT NULL, -- 属性
  dtype TINYINT NOT NULL, -- ダンジョン種類
  dwday TINYINT NULL, -- 開放曜日
  dsort INT NULL, -- ダンジョン表示順
  reward_gold INT NOT NULL DEFAULT 1, -- 報酬魔法石個数
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- フロア
CREATE TABLE dungeon_floors (
  id INT NOT NULL PRIMARY KEY,
  dungeon_id INT NOT NULL,
  seq TINYINT NOT NULL, -- フロア番号
  name VARCHAR(255) NOT NULL,
  diff SMALLINT NOT NULL, -- 難易度
  sta SMALLINT NOT NULL, -- 必要スタミナ
  waves SMALLINT NOT NULL, -- wave数
  ext INT NOT NULL, -- フロア情報拡張用(端末で使用)
  sr INT NOT NULL, -- Sランクスコア
  last TINYINT(1) NOT NULL DEFAULT 0, -- ダンジョン内最終階か.
  prev_dungeon_floor_id INT NULL, -- 先行するフロアID. 対象のフロアをクリアした際に、このフロアを開放する.
  bgm1 INT NOT NULL DEFAULT 0, -- フィールドBGM番号
  bgm2 INT NOT NULL DEFAULT 0, -- ボスBGM番号
  eflag INT NOT NULL DEFAULT 0, -- 追加フロア情報
  fr INT NOT NULL DEFAULT 0, -- 縛りルール
  fr1 INT NOT NULL DEFAULT 0, -- 縛りルール引数1
  fr2 INT NOT NULL DEFAULT 0, -- 縛りルール引数2
  fr3 INT NOT NULL DEFAULT 0, -- 縛りルール引数3
  fr4 INT NOT NULL DEFAULT 0, -- 縛りルール引数4
  fr5 INT NOT NULL DEFAULT 0, -- 縛りルール引数5
  fr6 INT NOT NULL DEFAULT 0, -- 縛りルール引数6
  fr7 INT NOT NULL DEFAULT 0, -- 縛りルール引数7
  fr8 INT NOT NULL DEFAULT 0, -- 縛りルール引数8
  sort INT NOT NULL DEFAULT 0, -- ダンジョン内フロア並び替え番号
  start_at DATETIME, -- 解放日時
  open_rank SMALLINT NOT NULL DEFAULT 0, -- 開放ランク
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,

  UNIQUE(dungeon_id, seq),
  INDEX prev_id_on_dungeon_floor(prev_dungeon_floor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 宝箱
CREATE TABLE treasures (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  dungeon_floor_id INT NOT NULL,
  award_id INT, -- 中身の報酬種別番号.
  prob MEDIUMINT NOT NULL DEFAULT 0, -- 取得確率
  amount INT NOT NULL DEFAULT 1, -- 中身のアイテム数量(コインなどはこちらを使う)
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ウェーブ
CREATE TABLE waves (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  dungeon_floor_id INT NOT NULL,
  seq TINYINT NOT NULL, -- フロア内連番
  mons_max TINYINT NOT NULL DEFAULT 1, -- 最大登場モンスター数
  egg_prob MEDIUMINT NOT NULL DEFAULT 0, -- 卵取得確率
  tre_prob MEDIUMINT NOT NULL DEFAULT 0, -- 宝箱取得確率
  boss TINYINT(1) NOT NULL DEFAULT 0, -- ボスウェーブであれば1
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,

  UNIQUE(dungeon_floor_id, seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ウェーブに出現するモンスター
CREATE TABLE wave_monsters (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  wave_id INT NOT NULL,
  card_id INT NOT NULL,
  lv SMALLINT NOT NULL DEFAULT 1, -- 出現レベル
  lv_rnd SMALLINT, -- レベル乱数
  prob MEDIUMINT NULL, -- 出現確率
  boss TINYINT(1) NOT NULL DEFAULT 0, -- ボスか否か
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ダンジョン販売
CREATE TABLE dungeon_sales (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  font_color VARCHAR(6), -- 文字色
  panel_color VARCHAR(6), -- パネル色
  message TEXT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ダンジョン販売商品
CREATE TABLE dungeon_sale_commodities (
  id INT NOT NULL PRIMARY KEY,
  dungeon_sale_id INT NOT NULL, -- ダンジョン販売ID
  dungeon_id INT NOT NULL, -- ダンジョンID
  price INT NOT NULL, -- 値段
  open_hour SMALLINT NOT NULL, -- 開放時間
  message TEXT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- チャレンジダンジョン報酬
CREATE TABLE challenge_dungeon_bonus (
  id INT NOT NULL PRIMARY KEY,
  finish_at DATETIME NOT NULL,
  dungeon_id INT NOT NULL,
  seq TINYINT NOT NULL, -- フロア番号
  bonus_id INT NOT NULL,
  amount INT NOT NULL,
  plus_hp TINYINT,
  plus_atk TINYINT,
  plus_rec TINYINT,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- カード
CREATE TABLE cards (
  id INT NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  attr INT NOT NULL,
  sattr INT,
  spup INT,
  mt INT NOT NULL,
  mt2 INT,
  rare INT NOT NULL,
  grp INT NOT NULL,
  cost INT NOT NULL,
  size INT NOT NULL,
  mlv INT NOT NULL,
  mcost INT,
  ccost INT,
  scost INT,
  pmhpa INT,
  pmhpb INT,
  pmhpc FLOAT,
  pmhpd INT,
  patka INT,
  patkb INT,
  patkc FLOAT,
  patkd INT,
  preca INT,
  precb INT,
  precc FLOAT,
  precd INT,
  pexpa INT,
  pexpb FLOAT,
  pexpc INT,
  pexpd INT,
  skill INT,
  ska INT,
  skb INT,
  lskill INT,
  lska INT,
  lskb INT,
  acyc INT,
  drop_card_id1 INT,
  drop_prob1 INT,
  drop_card_id2 INT,
  drop_prob2 INT,
  drop_card_id3 INT,
  drop_prob3 INT,
  drop_card_id4 INT,
  drop_prob4 INT,
  emhpa INT,
  emhpb INT,
  emhpc FLOAT,
  emhpd INT,
  eatka INT,
  eatkb INT,
  eatkc FLOAT,
  eatkd INT,
  edefa INT,
  edefb INT,
  edefc FLOAT,
  edefd INT,
  coink INT,
  expk INT,
  gupc INT,
  gup1 INT,
  gup2 INT,
  gup3 INT,
  gup4 INT,
  gup5 INT,
  dev1 INT,
  dev2 INT,
  dev3 INT,
  dev4 INT,
  dev5 INT,
  estu INT NOT NULL,
  esturn2 INT,
  aip0 INT NOT NULL,
  aip1 INT,
  aip2 INT,
  aip3 INT,
  aip4 INT,
  ai0num INT,
  ai0aip INT,
  ai0rnd INT,
  ai1num INT,
  ai1aip INT,
  ai1rnd INT,
  ai2num INT,
  ai2aip INT,
  ai2rnd INT,
  ai3num INT,
  ai3aip INT,
  ai3rnd INT,
  ai4num INT,
  ai4aip INT,
  ai4rnd INT,
  ai5num INT,
  ai5aip INT,
  ai5rnd INT,
  ai6num INT,
  ai6aip INT,
  ai6rnd INT,
  ai7num INT,
  ai7aip INT,
  ai7rnd INT,
  ai8num INT,
  ai8aip INT,
  ai8rnd INT,
  ai9num INT,
  ai9aip INT,
  ai9rnd INT,
  ai10num INT,
  ai10aip INT,
  ai10rnd INT,
  ai11num INT,
  ai11aip INT,
  ai11rnd INT,
  ai12num INT,
  ai12aip INT,
  ai12rnd INT,
  ai13num INT,
  ai13aip INT,
  ai13rnd INT,
  ai14num INT,
  ai14aip INT,
  ai14rnd INT,
  ai15num INT,
  ai15aip INT,
  ai15rnd INT,
  ai16num INT,
  ai16aip INT,
  ai16rnd INT,
  ai17num INT,
  ai17aip INT,
  ai17rnd INT,
  ai18num INT,
  ai18aip INT,
  ai18rnd INT,
  ai19num INT,
  ai19aip INT,
  ai19rnd INT,
  ai20num INT,
  ai20aip INT,
  ai20rnd INT,
  ai21num INT,
  ai21aip INT,
  ai21rnd INT,
  ai22num INT,
  ai22aip INT,
  ai22rnd INT,
  ai23num INT,
  ai23aip INT,
  ai23rnd INT,
  ai24num INT,
  ai24aip INT,
  ai24rnd INT,
  ai25num INT,
  ai25aip INT,
  ai25rnd INT,
  ai26num INT,
  ai26aip INT,
  ai26rnd INT,
  ai27num INT,
  ai27aip INT,
  ai27rnd INT,
  ai28num INT,
  ai28aip INT,
  ai28rnd INT,
  ai29num INT,
  ai29aip INT,
  ai29rnd INT,
  ai30num INT,
  ai30aip INT,
  ai30rnd INT,
  ai31num INT,
  ai31aip INT,
  ai31rnd INT,
  ps0 SMALLINT,
  ps1 SMALLINT,
  ps2 SMALLINT,
  ps3 SMALLINT,
  ps4 SMALLINT,
  ps5 SMALLINT,
  ps6 SMALLINT,
  ps7 SMALLINT,
  ps8 SMALLINT,
  ps9 SMALLINT,
  gs SMALLINT,
  mg SMALLINT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザ登録時 陣営別初期カード
CREATE TABLE camp_initial_cards (
  id INT NOT NULL PRIMARY KEY,
  camp_id TINYINT NOT NULL, -- 陣営
  monster_id_1 INT,
  monster_id_2 INT,
  monster_id_3 INT,
  monster_id_4 INT,
  monster_id_5 INT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- レベルアップ 経験値及びボーナス
CREATE TABLE levelup_experience (
  id INT NOT NULL PRIMARY KEY,
  level INT NOT NULL,
  required_experience INT UNSIGNED NOT NULL,
  bonus_id INT NOT NULL,
  amount INT NOT NULL, -- 増加数
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ログインストリーク ボーナス
CREATE TABLE login_streak_bonuses (
  id INT NOT NULL PRIMARY KEY,
  days INT NOT NULL, -- 総ログイン日数
  bonus_id INT NOT NULL,
  amount INT NOT NULL, -- 個数/レベル
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 総ログイン日数 ボーナス
CREATE TABLE login_total_count_bonuses (
  id INT NOT NULL PRIMARY KEY,
  days INT NOT NULL, -- 連続ログイン日数
  bonus_id INT NOT NULL,
  amount INT NOT NULL, -- 個数/レベル
  plus_hp TINYINT,
  plus_atk TINYINT,
  plus_rec TINYINT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 時間限定ボーナス
CREATE TABLE limited_bonuses (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  dungeon_id INT NULL,
  dungeon_floor_id INT NULL,
  bonus_type TINYINT NOT NULL,
  args FLOAT NULL,
  target_id INT NULL,
  amount INT NULL,
  nm_eggprob INT NULL,
  message VARCHAR(255),
  area TINYINT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,

  INDEX bonus_on_dungeon(dungeon_id, dungeon_floor_id, bonus_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- スキル (ver3.1～)
CREATE TABLE skills (
  id INT NOT NULL PRIMARY KEY,
  name varchar(256) NOT NULL,    -- スキル名
  help varchar(256) NOT NULL,    -- スキル説明（実機用）
  sktp INT NOT NULL,    -- スキルの種類
  skp1 INT NOT NULL,    -- スキルパラメータ１
  skp2 INT NOT NULL,    -- スキルパラメータ２
  skp3 INT NOT NULL,    -- スキルパラメータ３
  skp4 INT NOT NULL,    -- スキルパラメータ４
  skp5 INT NOT NULL,    -- スキルパラメータ５
  skp6 INT NOT NULL,    -- スキルパラメータ６
  skp7 INT NOT NULL,    -- スキルパラメータ７
  skp8 INT NOT NULL,    -- スキルパラメータ８
  lcap INT NOT NULL,    -- レベルキャップ
  ctbs INT NOT NULL,    -- クールタイム　基礎値
  ctel INT NOT NULL,    -- クールタイム　レベル係数
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ガチャ
CREATE TABLE gacha_prizes (
  id INT NOT NULL PRIMARY KEY,
  gacha_id TINYINT NOT NULL,
  gacha_type TINYINT NOT NULL, -- 1: 通常, 2: 課金, 3: お礼, 4: 予備
  card_id INT NOT NULL, -- 景品カードID
  min_level INT NOT NULL, -- 出現最低レベル
  max_level INT NOT NULL, -- 出現最高レベル
  prob MEDIUMINT NOT NULL, -- 出現確率(1〜の偏差値)
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,

  UNIQUE(gacha_id, card_id),
  INDEX gacha_type_on_gacha(gacha_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- サーバ定数
CREATE TABLE game_constants(
  id INT NOT NULL PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  value VARCHAR(128) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- マスターデータのバージョン
CREATE TABLE versions(
  id INT NOT NULL PRIMARY KEY,
  version INT NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 期限付きログインメッセージ
CREATE TABLE login_messages (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 期限付きガチャメッセージ
CREATE TABLE gacha_messages (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 全ユーザボーナス
CREATE TABLE all_user_bonuses (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  device_type TINYINT DEFAULT NULL,
  bonus_id INT NOT NULL,
  amount INT NOT NULL,
  message text,
  distribution_at datetime DEFAULT NULL,
  slv TINYINT,
  plus_hp TINYINT,
  plus_atk TINYINT,
  plus_rec TINYINT,
  area TINYINT,
  bonus_type TINYINT NOT NULL DEFAULT 6,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE administrators (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(45) NOT NULL ,
  password VARCHAR(45) NOT NULL ,
  role VARCHAR(45) NULL ,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  UNIQUE INDEX `uniq_username` (`username` ASC) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ダンジョンごとの＋卵ドロップ率補正
CREATE TABLE dungeon_plus_drop (
  id INT NOT NULL PRIMARY KEY,    -- ダンジョンID
  drop_prob INT NOT NULL, -- ＋卵出現確率補正値
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 時間限定ボーナス（グループ）
CREATE TABLE limited_bonuses_group (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  dungeon_id INT NOT NULL,
  group_type TINYINT NOT NULL,
  group_id TINYINT NOT NULL,
  message VARCHAR(255),
  created_at datetime  NOT NULL,
  updated_at datetime  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 時間限定ボーナス（ダンジョン）
CREATE TABLE limited_bonuses_open_dungeon (
  id INT NOT NULL PRIMARY KEY,
  pattern INT NOT NULL,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  area TINYINT NULL,
  dungeon_id INT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 時間限定ボーナス（ダンジョンボーナス）
CREATE TABLE limited_bonuses_dungeon_bonus (
  id INT NOT NULL PRIMARY KEY,
  day TINYINT NOT NULL,
  start_hour TINYINT NOT NULL,
  end_hour TINYINT NOT NULL,
  dungeon_id INT NULL,
  bonus_type TINYINT NOT NULL,
  args FLOAT NULL,
  area TINYINT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 敵スキル
CREATE TABLE enemy_skills (
  id INT NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  help TEXT NOT NULL,
  `type` INT NOT NULL,
  skp1 INT NOT NULL,
  skp2 INT NOT NULL,
  skp3 INT NOT NULL,
  skp4 INT NOT NULL,
  skp5 INT NOT NULL,
  skp6 INT NOT NULL,
  skp7 INT NOT NULL,
  skp8 INT NOT NULL,
  ratio INT NOT NULL,
  aip0 INT NOT NULL,
  aip1 INT NOT NULL,
  aip2 INT NOT NULL,
  aip3 INT NOT NULL,
  aip4 INT NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- キャンペーンシリアルコード情報
CREATE TABLE campaign_serial_code (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL,
  finish_at DATETIME NOT NULL,
  serial_type TINYINT NOT NULL,
  memo TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- キャンペーンシリアルアイテム情報
CREATE TABLE campaign_serial_item (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  item_id INT NOT NULL,
  avatar_id INT NOT NULL,
  lv INT NOT NULL,
  plus_hp INT NOT NULL,
  plus_atk INT NOT NULL,
  plus_rec INT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ユーザーシリアル（付与履歴）
CREATE TABLE user_serials (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  campaign_id INT NOT NULL,
  serial VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  UNIQUE INDEX serial_on_user_serials(serial),
  INDEX campaign_id_on_user_serials(campaign_id),
  INDEX user_id_campaign_id_on_user_serials(user_id, campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 海外版翻訳テキスト
CREATE TABLE convert_text (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  master_type INT NOT NULL,
  org_text VARCHAR(255) NOT NULL,
  text VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 追加ガチャ
CREATE TABLE extra_gacha (
  id INT NOT NULL PRIMARY KEY,
  begin_at DATETIME NOT NULL, -- 開始日時
  finish_at DATETIME NOT NULL, -- 終了日時
  title VARCHAR(255), -- ボタン名
  message TEXT NOT NULL, -- ガチャ内部説明文
  bs VARCHAR(255), -- ボタン名サブ
  gacha_id INT NOT NULL, -- 引くガチャ列
  gacha_type INT NOT NULL, -- 引くガチャタイプ(0:魔法石 1:友情ポイント)
  price INT NOT NULL, -- ガチャ価格
  color VARCHAR(6), -- パネル色(RGB)
  gtype INT NOT NULL, -- 追加ガチャ種類
  area TINYINT NULL, -- 仕向地
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ダウンロード用MASTERデータ
CREATE TABLE download_master_data (
  id INT NOT NULL PRIMARY KEY,
  gzip_data MEDIUMBLOB,
  length INT,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- MASTERアップロードデータ履歴
CREATE TABLE master_csv_history (
  id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(45) NOT NULL, -- 管理者名
  table_name VARCHAR(255), -- テーブル名
  version INT, -- バージョン
  gzip_data MEDIUMBLOB, -- CSVデータ(GZIP)
  length INT, -- CSVデータ長
  max_id INT, -- 最大ID
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- サポートデータ
CREATE TABLE support_data (
  id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL, -- アカウントID
  secret_code VARCHAR(12), -- 秘密のコード（重複不可、ただし必須ではないのでUNIQUEにはしない）
  auth_type INT, -- 0:twitterAccount 1:FacebookAccount 2:GoogleAccount
  auth_data VARCHAR(255), -- アカウントごとに一意となる文字列（メールアドレス等個人情報に当たる場合は、ハッシュ関数を通す）
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL,
  UNIQUE KEY (user_id),
  INDEX sercret_code_on_support_data (secret_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 公認アカウントユーザ
CREATE TABLE official_users (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL, -- アカウントID
  memo TEXT,
  created_at DATETIME NOT NULL ,
  updated_at DATETIME NOT NULL ,
  INDEX user_id_on_official_users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 魔法石購入ボーナス
create table product_bonus_item (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  begin_at DATETIME NOT NULL, -- 開始日時
  finish_at DATETIME NOT NULL, -- 終了日時
  name varchar(255) NOT NULL, -- 商品名 "魔法石 1個(+4個おまけ) 初回のみ"
  ios_code varchar(255) NOT NULL, -- プロダクトID 
  adr_code varchar(255) NOT NULL, -- プロダクトID 
  amz_code varchar(255) NOT NULL, -- プロダクトID 
  ios_tag varchar(255) NOT NULL, -- 表示価格 "￥100"
  adr_tag varchar(255) NOT NULL, -- 表示価格 "￥100"
  amz_tag varchar(255) NOT NULL, -- 表示価格 "￥100"
  ios_price INT NOT NULL, -- 価格 100
  adr_price INT NOT NULL, -- 価格 100
  amz_price INT NOT NULL, -- 価格 100
  add_pgold_first INT NOT NULL, -- 付与魔法石(初回)
  add_pgold_2nd INT NOT NULL, -- 付与魔法石(2回目以降)
  area_id INT NOT NULL, -- エリアID
  updated_at DATETIME NOT NULL ,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
