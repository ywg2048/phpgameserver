<?php
/**
 * アクセスブロックログデータに関するモデル
 */
class AccessBlockLogData extends BaseModel {

  /**　テーブル名 */
  const TABLE_NAME = "access_block_log_data";
  /** カウンターの有効期限 */
  const IP_ADDR_COUNTER_SEC = 300;
  /** ブロックの有効期限 */
  const ACCESS_BLOCK_SEC = 180;
  /** アクセスブロックするしきい値 */
  const ACCESS_BLOCK_BORDER_COUNT = 2;
  /** カラム名一覧　*/
  protected static $columns = array(
    'id',
    'ip',
    'server_info',
    'updated_at',
    'created_at'
  );

  /**
   * #PADC# memcache → redis
   * アクセスブロックカウンタをインクリメントする。ブロック対象と判断された場合、ログとしてレコードを生成する。
   * 
   * @param  string $ip_addr     IPアドレス
   * @param  array $server_info  $_SERVER変数
   * @param  Redis $redis  Redisオブジェクト
   * @return void
   */
  public static function increment($ip_addr, $server_info, $redis = null) {

    if(is_null($redis)) {
        $redis = Env::getRedisForUser();
    }

    $ip_addr_counter = $redis->get(CacheKey::getIpAddrCounter($ip_addr));
    $ip_addr_counter = isset($ip_addr_counter) ? $ip_addr_counter + 1 : 1;
    $redis->set(CacheKey::getIpAddrCounter($ip_addr), $ip_addr_counter, self::IP_ADDR_COUNTER_SEC);

    if($ip_addr_counter >= self::ACCESS_BLOCK_BORDER_COUNT) {
        // 次回からはアクセスブロックで制御する
        $redis->set(CacheKey::getAccessBlock($ip_addr), true, self::ACCESS_BLOCK_SEC);

        $pdo_log = Env::getDbConnectionForLog();
        $access_block_log_data = new self();
        $access_block_log_data->ip = $ip_addr;
        $access_block_log_data->server_info = json_encode($server_info);
        $access_block_log_data->create($pdo_log);
    }
  }

  /**
   * #PADC# memcache → redis
   * アクセスブロックカウンタをデクリメントする。
   * 
   * @param  string $ip_addr     IPアドレス
   * @param  Redis $redis  Redisオブジェクト
   * @return void
   */
  public static function decrement($ip_addr, $redis = null) {

    if(is_null($redis)) {
        $redis = Env::getRedisForUser();
    }

    $ip_addr_counter = $redis->get(CacheKey::getIpAddrCounter($ip_addr));
    if(!is_numeric($ip_addr_counter)) {
        return;
    }

    if($ip_addr_counter > 1) {
      $redis->set(CacheKey::getIpAddrCounter($ip_addr), $ip_addr_counter - 1, self::IP_ADDR_COUNTER_SEC);
    } else {
      $redis->delete(CacheKey::getIpAddrCounter($ip_addr));
    }
  }
}