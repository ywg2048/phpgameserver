<?php
/**
 * TencentPOBT環境AndroidQQ定数（全体）.
 */
class GlobalEnv extends BaseEnv {

  const ENV = 'padctencentpobtadrqq';
  // CSVのprefix
  const ENV_PREFIX = 'tencentpobtadrqq';

  // リージョン
  const REGION = 'JP';

  // DB
  const SHARE_DSN			= "mysql:host=gamedb.aqqshare.zlmc.db; dbname=db_zlmc_gamedb_share; port=10010;";
  const SHARE_READ_DSN		= "mysql:host=gamedb.aqqshare.zlmc.db; dbname=db_zlmc_gamedb_share; port=10010;";
  const SERIAL_DSN			= "mysql:host=gamedb.aqqshare.zlmc.db; dbname=db_zlmc_gamedb_share; port=10010;";
  const LOG_DSN				= "mysql:host=gamedb.aqqlog.zlmc.db; dbname=db_zlmc_gamedb_log; port=10011;";
  const LOG_READ_DSN		= "mysql:host=gamedb.aqqlog.zlmc.db; dbname=db_zlmc_gamedb_log; port=10011;";
  const CARD_LOG_DSN		= "mysql:host=gamedb.aqqlog.zlmc.db; dbname=db_zlmc_gamedb_log; port=10011;";
  const DUNGEON_LOG_DSN		= "mysql:host=gamedb.aqqlog.zlmc.db; dbname=db_zlmc_gamedb_log; port=10011;";
  const DB_USERNAME			= "zlmc";
  const DB_PASSWORD			= "Zlmc@2015++";
  const SERIAL_DB_USERNAME	= "zlmc";
  const SERIAL_DB_PASSWORD	= "Zlmc@2015++";
  const LOG_USERNAME		= "zlmc";
  const LOG_PASSWORD		= "Zlmc@2015++";
  const DB_HOST				= "cbt3_db";
  const DB_NAME				= "db_zlmc_gamedb_share";
  const LOG_DB_NAME			= "zlmc";

  // #PADC# ----------begin----------
  // TlogDB
  const TLOG_DB_DSN			= "mysql:host=spider.game.mocpub.db; dbname=db_zlmc_online; port=25000;";
  const TLOG_DB_USERNAME	= "zlmc";
  const TLOG_DB_PASSWORD	= "Zlmc@2015++";
  // #PADC# ----------end----------

  // timezone
  const DB_SET_TIMEZONE = FALSE;
  const DB_TIMEZONE = "Asia/Shanghai";

  // Memcache キーのprefix
  const MEMCACHE_PREFIX = 'padc_';
  const MEMCACHE_ADMIN_PREFIX = 'padc_admin_';

  // ユーザー登録可能なDB
  const REGISTERED_DB_NUMBER = 10; // 使用可能なUserDB台数

  // #PADC# -----------begin----------
  const REDIS_AUTH_USE = true;
  const REDIS_PASS = 'Red@zlmc33';
  const REDIS_POOL_TIME = 2;// redis接続をpoolさせる時間(sec)
  // #PADC# -----------end----------

  // DBのDSN(Read)一覧を返す.
  public static function getReadDbList() {
    return array(
      'mysql:host=gamedb.aqquser00.zlmc.db; dbname=db_zlmc_gamedb_user; port=10000;' => 1,
      'mysql:host=gamedb.aqquser01.zlmc.db; dbname=db_zlmc_gamedb_user; port=10001;' => 2,
      'mysql:host=gamedb.aqquser02.zlmc.db; dbname=db_zlmc_gamedb_user; port=10002;' => 3,
      'mysql:host=gamedb.aqquser03.zlmc.db; dbname=db_zlmc_gamedb_user; port=10003;' => 4,
      'mysql:host=gamedb.aqquser04.zlmc.db; dbname=db_zlmc_gamedb_user; port=10004;' => 5,
      'mysql:host=gamedb.aqquser05.zlmc.db; dbname=db_zlmc_gamedb_user; port=10005;' => 6,
      'mysql:host=gamedb.aqquser06.zlmc.db; dbname=db_zlmc_gamedb_user; port=10006;' => 7,
      'mysql:host=gamedb.aqquser07.zlmc.db; dbname=db_zlmc_gamedb_user; port=10007;' => 8,
      'mysql:host=gamedb.aqquser08.zlmc.db; dbname=db_zlmc_gamedb_user; port=10008;' => 9,
      'mysql:host=gamedb.aqquser09.zlmc.db; dbname=db_zlmc_gamedb_user; port=10009;' => 10,
    );
  }

  // DBのDSN(Write)一覧を返す.
  public static function getWriteDbList() {
    return array(
      'mysql:host=gamedb.aqquser00.zlmc.db; dbname=db_zlmc_gamedb_user; port=10000;' => 1,
      'mysql:host=gamedb.aqquser01.zlmc.db; dbname=db_zlmc_gamedb_user; port=10001;' => 2,
      'mysql:host=gamedb.aqquser02.zlmc.db; dbname=db_zlmc_gamedb_user; port=10002;' => 3,
      'mysql:host=gamedb.aqquser03.zlmc.db; dbname=db_zlmc_gamedb_user; port=10003;' => 4,
      'mysql:host=gamedb.aqquser04.zlmc.db; dbname=db_zlmc_gamedb_user; port=10004;' => 5,
      'mysql:host=gamedb.aqquser05.zlmc.db; dbname=db_zlmc_gamedb_user; port=10005;' => 6,
      'mysql:host=gamedb.aqquser06.zlmc.db; dbname=db_zlmc_gamedb_user; port=10006;' => 7,
      'mysql:host=gamedb.aqquser07.zlmc.db; dbname=db_zlmc_gamedb_user; port=10007;' => 8,
      'mysql:host=gamedb.aqquser08.zlmc.db; dbname=db_zlmc_gamedb_user; port=10008;' => 9,
      'mysql:host=gamedb.aqquser09.zlmc.db; dbname=db_zlmc_gamedb_user; port=10009;' => 10,
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
		static::REDIS_POINT			=> array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
    	static::REDIS_DEFAULT		=> array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
		static::REDIS_SHARE			=> array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
		static::REDIS_USER			=> array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
    );
    return $servers[$className];
  }

  // Redis(参照用)の接続先一覧を返す.
  public static function getRedisServerRead($className) {
    $servers = array(
		static::REDIS_POINT => array(
			array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
		),
    	static::REDIS_DEFAULT => array(
			array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
		),
		static::REDIS_SHARE => array(
			array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
		),
		static::REDIS_USER => array(
			array('endpoint' => 'rediscluster0.aqq.zlmc.db', 'port' => 50002),
		),
    );
    return $servers[$className];
  }
}