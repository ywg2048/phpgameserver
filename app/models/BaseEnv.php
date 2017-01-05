<?php
/**
 * 環境記述クラスのベースクラス.
 * @author kusanagi@banana-systems.com
 */
abstract class BaseEnv {

  const DB_SET_TIMEZONE=FALSE;
  const DB_TIMEZONE="";

  const DB_READ=1;
  const DB_WRITE=2;

  const REDIS_PURCHASE_IOS = 1;
  const REDIS_PURCHASE_ADR = 2;
  const REDIS_PURCHASE_AMZ = 3;
  const REDIS_POINT = 4;
  const REDIS_WSCORE = 5;

  // #PADC# -----------begin----------
  const REDIS_DEFAULT	= 0;
  const REDIS_SHARE		= 1001;// マスターデータ系のキャッシュ
  const REDIS_USER		= 1002;// ユーザデータ系のキャッシュ
  // #PADC# -----------end----------

  // #PADC# -----------begin----------
  const REDIS_AUTH_USE = false;
  const REDIS_PASS = null;

  const debug_user_backtrace = false;
  const debug_share_backtrace = false;
  const DB_ATTR_PERSISTENT = true;
  // #PADC# -----------end----------
  static $redis_connection_array = array();
  /**
   * コールされたAPIのバージョンをBaseActionで設定する.
   */
  public static function setRev($rev) {
    static::getRev($rev);
  }

  /**
   * コールされたAPIのバージョンを取得(設定)する.
   */
  public static function getRev($setrev = null) {
    static $env_api_revision;
    if(!is_null($setrev)){
      // BaseActionで設定.
      $env_api_revision = $setrev;
    }elseif(empty($env_api_revision)){
      $env_api_revision = 0;
    }
    return $env_api_revision;
  }

  /**
   * #PADC#
   * Memcacheからredisに置き換え
   * Redisへの接続をラップする形にしているが基本的にはgetRedisForXXXを使用する
   * 
   * --Memcacheオブジェクトを取得する.
   */
  public static function getMemcache($servertype=BaseEnv::REDIS_SHARE) {
  	try
  	{
	  	$redis = new Redis();

	    $servers = array();
	    $server = (object)static::getRedisServer($servertype);
	  	$result = $redis->pconnect($server->endpoint,$server->port,Env::REDIS_POOL_TIME);
	  	if(static::REDIS_AUTH_USE)
	  	{
	  		$redis->auth(static::REDIS_PASS);
	  	}
  		if($result == true)
  		{
		    $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
  		}
  		else
  		{
  			throw new PadException (RespCode::UNKNOWN_ERROR,'redis connect error');
  		}
  	}
  	catch(Exception $e)
  	{
		global $logger;
		$logger->log("RespCode:" . $e->getCode() . " " . $e->getFile() . "(" . $e->getLine() . ") 'message' => '" . $e->getMessage() . "'", Zend_Log::NOTICE);
  	}

    return $redis;

    /*
  	$memcache = new Memcached(Env::MEMCACHE_PREFIX);
    if (0 == count($memcache->getServerList())) {
      $servers = array();
      foreach (static::getMemcachedServers() as $host => $port) {
        $servers[] = array($host, $port);
      }
      $memcache->addServers($servers);
      $memcache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE,  true);
      $memcache->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS, 12);
      $memcache->setOption(Memcached::OPT_RETRY_TIMEOUT,      1);

      $memcache->setOption(Memcached::OPT_CONNECT_TIMEOUT, 100); // miliseconds
      $memcache->setOption(Memcached::OPT_NO_BLOCK, 1);
      $memcache->setOption(Memcached::OPT_POLL_TIMEOUT, 100);    // miliseconds
      $memcache->setOption(Memcached::OPT_SEND_TIMEOUT, 100000); // microseconds
      $memcache->setOption(Memcached::OPT_RECV_TIMEOUT, 100000); // microseconds
    }
    return $memcache;
    */
  }

  // #PADC# ----------begin----------
  // TODO: 動作確認用
  // データスナップショット作成
  public static function getsnapshots($data)
  {
	$snapshot_writer	= new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_redis_snapshot.log");
	$snapshot_format	= '%message%'.PHP_EOL;
	$snapshot_formatter	= new Zend_Log_Formatter_Simple($snapshot_format);
	$snapshot_writer->setFormatter($snapshot_formatter);
	$snapshot_logger	= new Zend_Log($snapshot_writer);
	$snapshot_logger->log($data, Zend_Log::DEBUG);
  }
  // #PADC# ----------end----------

  public static function getDbConnectionInstance($dsn)
  {
  	static $instances = array();
  	$ident = md5($dsn);
  	try
  	{
  		$instances[$ident] = new PDO($dsn, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
  	}
  	catch(Exception $e)
  	{
  		global $logger;
  		$logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
  		$instances[$ident] = new PDO($dsn, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
  	}
  	$instances[$ident]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  	if(static::DB_SET_TIMEZONE==TRUE){
  		static::setDbTimezone(static::DB_TIMEZONE, $instances[$ident]);
  	}
  	return $instances[$ident];
  }

  /**
   * 読み込み用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForUserRead($user_id ,$dbid = null) {
    if(self::debug_user_backtrace)
    {
      global $logger;
      $logger->log(json_encode(debug_backtrace()), Zend_Log::NOTICE);
    }

    $dsn = static::getUserDbDSN($user_id, static::DB_READ, $dbid);
    $pdo = static::getDbConnectionInstance($dsn);
    return $pdo;
  }

  /**
   * ユーザーDBのIDを割り当てる.
   * @return dbid
   */
  public static function assignUserDbId($user_id) {
    // #PADC# -----begin-----
    // 発行されたuseridを、ユーザDB台数で割った余りの数値のユーザDBに振り分ける（割り切れた場合はEnv::REGISTERED_DB_NUMBERを使用）
  	$userdb_id = $user_id % Env::REGISTERED_DB_NUMBER;
  	if($userdb_id == 0)
  	{
  		$userdb_id = Env::REGISTERED_DB_NUMBER;
  	}
    return $userdb_id;
    // #PADC# -----end-----
  }

  // DBのDSN(Read)一覧を返す.
  public static function getAllReadDSN() {
    if(static::MODE == "APP"){
      // APPはWriteのみ.
      return static::getWriteDbList();
    }elseif(static::MODE == "ADMIN"){
      return static::getReadDbList();
    }
  }

  // DBのDSN(Write)一覧を返す.
  public static function getAllWriteDSN() {
    return static::getWriteDbList();
  }

  /**
   * 書き込み用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForUserWrite($user_id ,$dbid = null) {
    if(self::debug_user_backtrace)
    {
      global $logger;
      $logger->log(json_encode(debug_backtrace()), Zend_Log::NOTICE);
    }
    $dsn = static::getUserDbDSN($user_id, static::DB_WRITE, $dbid);
    $pdo = static::getDbConnectionInstance($dsn);
    return $pdo;
  }

  public static function getUserDbDSN($user_id, $db, $dbid = null) {
    if(!$user_id){
      throw new PadException(RespCode::UNKNOWN_ERROR, "user_id none.");
    }
    if($db == static::DB_READ) {
      $dsns = static::getAllReadDSN();
    }elseif($db == static::DB_WRITE) {
      $dsns = static::getAllWriteDSN();
    }else{
      throw new PadException(RespCode::UNKNOWN_ERROR);
    }
    if(!$dbid){
      static $user_dbid;
      if(isset($user_dbid[$user_id]) && $user_dbid[$user_id] > 0){
        $dbid = $user_dbid[$user_id];
      }else{
      	// #PADC# ----------begin----------memcache→redisに切り替え
        $rRedis = Env::getRedisForUserRead();
        $key = CacheKey::getDbIdFromUserDeviceKey($user_id);
        $dbid = $rRedis->get($key);
        if(FALSE === $dbid){
          // cacheに存在しないのでDBから検索
          $pdo_share = Env::getDbConnectionForShare();
          $user_device = UserDevice::findBy(array('id' => $user_id), $pdo_share);
          if(!$user_device){
            // USER_NOT_FOUND.
            $last_dsn = array_keys($dsns);
            $dsn = array_pop($last_dsn);
            return $dsn;
          }
          $dbid = $user_device->dbid;
          $redis = Env::getRedisForUser();
          $redis->set($key, $dbid, 864000); // 10日間.
        }
        // #PADC# ----------end----------
        $user_dbid[$user_id] = $dbid;
      }
    }
    foreach($dsns as $dsn => $id){
      if($id == $dbid){
        return $dsn;
      }
    }
  }

  /**
   * 読み込み用のDBコネクションを全て取得する.
   * @return PDOの配列
   */
  public static function getDbConnectionForUserAll() {
    $dsns = static::getAllReadDSN();
    $pdos = array();
    foreach($dsns as $dsn => $user_range){
      try
      {
        $pdo = new PDO($dsn, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
      }
      catch(Exception $e)
      {
        global $logger;
        $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
        $pdo = new PDO($dsn, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
      }
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if(static::DB_SET_TIMEZONE==TRUE){
        static::setDbTimezone(static::DB_TIMEZONE, $pdo);
      }
      $pdos[] = $pdo;
    }
    return $pdos;
  }

  /**
   * 書き込み用のDBコネクションを全て取得する.
   * @return PDOの配列
   */
  public static function getDbConnectionForUserAllWrite() {
    $dsns = static::getAllWriteDSN();
    $pdos = array();
    foreach($dsns as $dsn => $user_range){
      try
      {
        $pdo = new PDO($dsn, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
      }
      catch(Exception $e)
      {
        global $logger;
        $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
        $pdo = new PDO($dsn, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
      }
      
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      if(static::DB_SET_TIMEZONE==TRUE){
        static::setDbTimezone(static::DB_TIMEZONE, $pdo);
      }
      $pdos[] = $pdo;
    }
    return $pdos;
  }

  /**
   * 共有用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForShare() {
    if(self::debug_share_backtrace)
    {
      global $logger;
      $logger->log(json_encode(debug_backtrace()), Zend_Log::NOTICE); 
    }
    try
    {
      $pdo = new PDO(static::SHARE_DSN, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::SHARE_DSN, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * 読み込み用の共有DBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForShareRead() {
    if(self::debug_share_backtrace)
    {
      global $logger;
      $logger->log(json_encode(debug_backtrace()), Zend_Log::NOTICE);
    }
//    $pdo = new PDO(static::SHARE_READ_DSN, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;' ,PDO::ATTR_PERSISTENT => true));
    try
    {
      $pdo = new PDO(static::SHARE_READ_DSN, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::SHARE_READ_DSN, static::DB_USERNAME, static::DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * ログ用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForLog() {
    try
    {
      $pdo = new PDO(static::LOG_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::LOG_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * 読み込み用のログDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForLogRead() {
//    $pdo = new PDO(static::LOG_READ_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;' ,PDO::ATTR_PERSISTENT => true));
    try
    {
      $pdo = new PDO(static::LOG_READ_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::LOG_READ_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * カードログ用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForCardLog() {
    try
    {
      $pdo = new PDO(static::CARD_LOG_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::CARD_LOG_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * ダンジョンログ用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForDungeonLog() {
    try
    {
      $pdo = new PDO(static::DUNGEON_LOG_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::DUNGEON_LOG_DSN, static::LOG_USERNAME, static::LOG_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * アーケード連携用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForAclink() {
    try
    {
      $pdo = new PDO(static::ACLINK_DSN, static::ACLINK_USERNAME, static::ACLINK_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::ACLINK_DSN, static::ACLINK_USERNAME, static::ACLINK_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * シリアル用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForSerial() {
    try
    {
      $pdo = new PDO(static::SERIAL_DSN, static::SERIAL_DB_USERNAME, static::SERIAL_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(static::SERIAL_DSN, static::SERIAL_DB_USERNAME, static::SERIAL_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if(static::DB_SET_TIMEZONE==TRUE){
      static::setDbTimezone(static::DB_TIMEZONE, $pdo);
    }
    return $pdo;
  }

  /**
   * #PADC#
   * Tlog用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getDbConnectionForTlog() {
    try
    {
      $pdo = new PDO(Env::TLOG_DB_DSN, Env::TLOG_DB_USERNAME, Env::TLOG_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }
    catch(Exception $e)
    {
      global $logger;
      $logger->log(json_encode('reconnect'), Zend_Log::NOTICE);
      $pdo = new PDO(Env::TLOG_DB_DSN, Env::TLOG_DB_USERNAME, Env::TLOG_DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;',PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT));
    }

  	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  	if(static::DB_SET_TIMEZONE==TRUE){
  		static::setDbTimezone(static::DB_TIMEZONE, $pdo);
  	}
  	return $pdo;
  }

  /**
   * timezoneを設定する.
   */
  public static function setDbTimezone($tz, $pdo){
    $sql = "SET time_zone = '$tz'";
    $pdo->exec($sql);
  }

  /**
   * #PADC#
   * Redisオブジェクト（マスター系）の取得
   */
  public static function getRedisForShare()
  {
  	return self::getRedis(Env::REDIS_SHARE);
  }

  /**
   * #PADC#
   * Redisオブジェクト（Read用マスター系）の取得
   */
  public static function getRedisForShareRead()
  {
  	return self::getRedisForRead(Env::REDIS_SHARE);
  }

  /**
   * #PADC#
   * Redisオブジェクト（ユーザ系）の取得
   */
  public static function getRedisForUser()
  {
  	return self::getRedis(Env::REDIS_USER);
  }

  /**
   * #PADC#
   * Redisオブジェクト（Read用ユーザ系）の取得
   */
  public static function getRedisForUserRead()
  {
  	return self::getRedisForRead(Env::REDIS_USER);
  }

  /**
   * #PADC# $classNameのデフォルト値を追加
   * KVS(Redis)へのコネクションを確立し、Redisオブジェクトを取得する
   * @return Redis
   */
  public static function getRedis($className=BaseEnv::REDIS_SHARE) {
    $con_name = $className."write"; 
    if(isset(self::$redis_connection_array[$con_name]))
    {
      $redis = self::$redis_connection_array[$con_name];
    }
    else
    {
      $server = (object)static::getRedisServer($className);
      $redis=new Redis();
      $redis->pconnect($server->endpoint, $server->port, Env::REDIS_POOL_TIME);
      if(static::REDIS_AUTH_USE)
      {
        $redis->auth(static::REDIS_PASS);
      }
      $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
      self::$redis_connection_array[$con_name] = $redis;
    }
    
    return $redis;
  }

  /**
   * #PADC# $classNameのデフォルト値を追加
   * KVS(Redis)(参照用)へのコネクションを確立し、Redisオブジェクトを取得する
   * @return Redis
   */
  public static function getRedisForRead($className=BaseEnv::REDIS_SHARE) {
  	// #PADC# ----------begin----------接続先参照方法調整
    $con_name = $className."read"; 
    if(isset(self::$redis_connection_array[$con_name]))
    {
      $redis = self::$redis_connection_array[$con_name];
    }
    else
    { 
    	$servers	= static::getRedisServerRead($className);
    	$serverId	= array_rand($servers);
      $server		= (object)$servers[$serverId];
      // #PADC# ----------end----------
      $redis=new Redis();
      $redis->pconnect($server->endpoint, $server->port, Env::REDIS_POOL_TIME);
      if(static::REDIS_AUTH_USE)
      {
        $redis->auth(static::REDIS_PASS);
      }
      // #PADC# 書き込み用でシリアライズしているため参照時もシリアライズする
      $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
      self::$redis_connection_array[$con_name] = $redis;
    }
    return $redis;
  }

  /**
   * Redshift用のDBコネクションを取得する.
   * @return PDO
   */
  public static function getRedshift() {
    $pdo = new PDO(
      static::REDSHIFT_DSN,
      static::REDSHIFT_DB_NAME,
      static::REDSHIFT_DB_PASSWORD,
      array(PDO::ATTR_PERSISTENT => self::DB_ATTR_PERSISTENT)
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
  }

  public static function getGearman() {
    return static::getGearmanServer();
  }

  // WebServerのhost一覧を返す.
  public static function getWebServers() {
    $dnses = array();
    return $dnses;
  }
}
