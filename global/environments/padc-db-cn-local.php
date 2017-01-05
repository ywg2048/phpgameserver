<?php
/**
 * ローカル開発環境定数（全体）.
 */
class GlobalEnv extends BaseEnv {

  const ENV = 'padclocal';
  // CSVのprefix
  const ENV_PREFIX = 'local';

  // リージョン
  const REGION = 'JP';

  // DB
  const SHARE_DSN			= "mysql:host=127.0.0.1; dbname=localpad_share";
  const SHARE_READ_DSN		= "mysql:host=127.0.0.1; dbname=localpad_share";
  const SERIAL_DSN			= "mysql:host=127.0.0.1; dbname=localpad_share";
  const LOG_DSN				= "mysql:host=127.0.0.1; dbname=localpad_log";
  const LOG_READ_DSN		= "mysql:host=127.0.0.1; dbname=localpad_log";
  const CARD_LOG_DSN		= "mysql:host=127.0.0.1; dbname=localpad_log";
  const DUNGEON_LOG_DSN		= "mysql:host=127.0.0.1; dbname=localpad_log";
  const DB_USERNAME			= "padcnlocal";
  const DB_PASSWORD			= "padcnlocal";
  const SERIAL_DB_USERNAME	= "padcnlocal";
  const SERIAL_DB_PASSWORD	= "padcnlocal";
  const LOG_USERNAME		= "padcnlocal";
  const LOG_PASSWORD		= "padcnlocal";
  const DB_HOST				= "127.0.0.1";
  const DB_NAME				= "devpad_share";
  const LOG_DB_NAME			= "padcnlocal";

  // #PADC# ----------begin----------
  // TlogDB
  const TLOG_DB_DSN			= "mysql:host=127.0.0.1; dbname=localpad_db_zlmc_online";
  const TLOG_DB_USERNAME	= "padcnlocal";
  const TLOG_DB_PASSWORD	= "padcnlocal";
  // #PADC# ----------end----------

  // timezone
  const DB_SET_TIMEZONE = TRUE;
  const DB_TIMEZONE = "Asia/Tokyo";

  // Memcache キーのprefix
  const MEMCACHE_PREFIX = 'localpad_';
  const MEMCACHE_ADMIN_PREFIX = 'localpad_admin_';

  // #PADC# -----begin----- 
  // ユーザー登録可能なDB
//  const REGISTERED_DB_IDS = "1";// PADC版では振り分け方法変更するため未使用
  const REGISTERED_DB_NUMBER = 2; // 使用可能なUserDB台数
  // #PADC# -----end-----

  // #PADC# -----------begin----------
  const REDIS_AUTH_USE = false;
  const REDIS_PASS = 'xxx';
  const REDIS_POOL_TIME = 2;// redis接続をpoolさせる時間(sec)
  // #PADC# -----------end----------
  
  // DBのDSN(Read)一覧を返す.
  public static function getReadDbList() {
    return array(
      'mysql:host=127.0.0.1; dbname=localpad_user1' => 1,
      'mysql:host=127.0.0.1; dbname=localpad_user2' => 2,
    );
  }

  // DBのDSN(Write)一覧を返す.
  public static function getWriteDbList() {
    return array(
      'mysql:host=127.0.0.1; dbname=localpad_user1' => 1,
      'mysql:host=127.0.0.1; dbname=localpad_user2' => 2,
    );
  }

  // Memcachedの接続先一覧を返す.
  public static function getMemcachedServers() {
    return array(
	    '127.0.0.1' => 11211,
    );
  }

  // Redisの接続先一覧を返す.
  public static function getRedisServer($className) {
	$servers = array(
		static::REDIS_PURCHASE_IOS	=> array('endpoint' => '127.0.0.1', 'port' => 6379),
		static::REDIS_PURCHASE_ADR	=> array('endpoint' => '127.0.0.1', 'port' => 6379),
		static::REDIS_PURCHASE_AMZ	=> array('endpoint' => '127.0.0.1', 'port' => 6379),
		static::REDIS_POINT			=> array('endpoint' => '127.0.0.1', 'port' => 6379),
		static::REDIS_WSCORE		=> array('endpoint' => '127.0.0.1', 'port' => 6379),
    	// #PADC# ----------begin----------
    	static::REDIS_DEFAULT		=> array('endpoint' => '127.0.0.1', 'port' => 6379),
		static::REDIS_SHARE			=> array('endpoint' => '127.0.0.1', 'port' => 6379),
		static::REDIS_USER			=> array('endpoint' => '127.0.0.1', 'port' => 6379),
    	// #PADC# ----------end----------
    );
	return $servers[$className];
  }

  // Redis(参照用)の接続先一覧を返す.
  public static function getRedisServerRead($className) {
	$servers = array(
		static::REDIS_PURCHASE_IOS => array(
			array('endpoint' => '127.0.0.1', 'port' => 6379),
		),
		static::REDIS_PURCHASE_ADR => array(
			array('endpoint' => '127.0.0.1', 'port' => 6379),
		),
		static::REDIS_PURCHASE_AMZ => array(
			array('endpoint' => '127.0.0.1', 'port' => 6379),
		),
		static::REDIS_POINT => array(
			array('endpoint' => '127.0.0.1', 'port' => 6379),
		),
		static::REDIS_WSCORE => array(
			array('endpoint' => '127.0.0.1', 'port' => 6379),
		),
   		// #PADC# ----------begin----------
    	static::REDIS_DEFAULT => array(
    		array('endpoint' => '127.0.0.1', 'port' => 6379),
    	),
    	static::REDIS_SHARE => array(
        	array('endpoint' => '127.0.0.1', 'port' => 6379),
    	),
    	static::REDIS_USER => array(
        	array('endpoint' => '127.0.0.1', 'port' => 6379),
		),
   		// #PADC# ----------end----------
	);
    return $servers[$className];
  }
}