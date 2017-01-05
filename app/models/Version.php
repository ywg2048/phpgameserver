<?php
/**
 * マスターデータのバージョン.
 */

class Version extends BaseMasterModel {
  const TABLE_NAME = "versions";
  const VER_KEY_GROUP = "ver";
  const MEMCACHED_EXPIRE = 86400; // 24時間.
  const MIN_RAND_NUM = 0;
  const MAX_RAND_NUM = 9;

  protected static $columns = array(
    'id',
  	'name',
    'version'
  );

  /**
   * 指定したIDに対するバージョンを返す.
   * @param integer $name 対象のID.
   */
  public static function getVersion($name,$pdo = null) {
    static $versions;
    if(!isset($versions[$name])){
      $key = MasterCacheKey::getVersionKey(mt_rand(static::MIN_RAND_NUM, static::MAX_RAND_NUM));
      // #PADC# ----------begin----------memcache→redisに切り替え
      $rRedis = Env::getRedisForShareRead();
      $versions = $rRedis->get($key);
      if ($versions == false) {
        $objs = Version::findAllBy(array(),null,null,$pdo);
        $versions = array();
        foreach ($objs as $v) {
          $versions[$v->name] = (int)$v->version;
        }
        $redis = Env::getRedisForShare();
        $redis->set($key, $versions, static::MEMCACHED_EXPIRE);
      }
      // #PADC# ----------end----------
    }
    $version = isset($versions[$name]) ? $versions[$name] : 0;
    return $version;
  }

  /**
   * 指定したクラスに対する version の値を1増加させる.
   * CSVデータがあるときはCSV履歴テーブルに保存する.
   */
  public static function increment($pdo, $class_name, $identity, $csv_data_gz, $csv_length, $max_id, $diff = null, $convert_table_name = null) {
    $version = Version::findBy(array('name' => constant($class_name."::VER_KEY_GROUP")), $pdo, TRUE);
    if($version) {
      $version->version += 1;
      $version->update($pdo);
    } else {
      $version = new Version();
      $version->name = constant($class_name."::VER_KEY_GROUP");
      $version->version = 1;
      $version->create($pdo);
    }

    // アップロードCSV履歴
    $masterCsvHistory = new MasterCsvHistory();
    $masterCsvHistory->username = $identity->username;
    $masterCsvHistory->table_name = empty($convert_table_name) ? constant($class_name."::TABLE_NAME") : constant($class_name."::TABLE_NAME")."_".$convert_table_name;
    $masterCsvHistory->version = $version->version;
    $masterCsvHistory->gzip_data = $csv_data_gz;
    $masterCsvHistory->length = $csv_length;
    $masterCsvHistory->max_id = $max_id;
    $masterCsvHistory->diff = $diff ? $diff : "";
    $masterCsvHistory->create($pdo);

    // キャッシュ再作成.
    $objs = self::findAllBy(array(), null, null, $pdo);
    $versions = array();
    foreach ($objs as $v) {
      $versions[$v->name] = (int)$v->version;
    }

    // #PADC# ----------begin----------memcache→redisに切り替え
    $redis = Env::getRedisForShare();
    foreach(range(static::MIN_RAND_NUM, static::MAX_RAND_NUM) as $id){
      $key = MasterCacheKey::getVersionKey($id);
      $redis->set($key, $versions, static::MEMCACHED_EXPIRE);
    }
    // #PADC# ----------end----------
  }

  /**
   * バージョンのインクリメントを確認する.
   * CSVデータのアップロード時に呼ばれる.
   */
  public static function verifyVerKey(){
    // 比較用キャッシュ作成.
    $objs = self::findAllBy(array());
    $versions = array();
    foreach ($objs as $v) {
      $versions[$v->name] = (int)$v->version;
    }
    // #PADC# ----------begin----------memcache→redisに切り替え
    $rRedis = Env::getRedisForShare();
    foreach(range(static::MIN_RAND_NUM, static::MAX_RAND_NUM) as $id){
      $key = MasterCacheKey::getVersionKey($id);
      $ret[$key] = ($rRedis->get($key) == $versions);
    }
    // #PADC# ----------end----------
    return $ret;
  }
    // #PADC# ----------begin----------
  public static function updateCache()
  {
    $pdo = Env::getDbConnectionForShareRead();
    // キャッシュ再作成.
    $objs = self::findAllBy(array(), null, null, $pdo);
    $versions = array();
    foreach ($objs as $v) {
      $versions[$v->name] = (int)$v->version;
    }
    $redis = Env::getRedisForShare();
    foreach(range(static::MIN_RAND_NUM, static::MAX_RAND_NUM) as $id){
      $key = MasterCacheKey::getVersionKey($id);
      $redis->set($key, $versions, static::MEMCACHED_EXPIRE);
    }
  }
  // #PADC# ----------end----------
}

