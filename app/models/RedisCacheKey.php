<?php
/**
 *　Redisキャッシュキー生成Utilクラス
 */
class RedisCacheKey {

  public static function getRedisPurchaseIos($transaction_id) {
    return Env::MEMCACHE_PREFIX . 'ios_tran_id' . $transaction_id; 
  }

  public static function getRedisPurchaseAdr($transaction_id) {
    return Env::MEMCACHE_PREFIX . 'adr_tran_id' . $transaction_id; 
  }

  public static function getRedisPurchaseAmz($transaction_id) {
    return Env::MEMCACHE_PREFIX . 'amz_tran_id' . $transaction_id; 
  }

  public static function getFriendPointKey($user_id) {
  	return Env::MEMCACHE_PREFIX . 'friend_point_key_' . $user_id;
  }

  public static function getRecommendedHelperLevelKey($level){
    return Env::MEMCACHE_PREFIX . 'recm_hlpr_' . $level;
  }

  public static function getWMedalKey($user_id) {
  	return Env::MEMCACHE_PREFIX . 'medal_key_' . $user_id;
  }
  
  public static function getRecommendedWHelperDungeonFloorKey($dungeon_floor_id){
  	return Env::MEMCACHE_PREFIX . 'w_recm_hlpr_df_' . $dungeon_floor_id;
  }
  
  public static function getWDungeonScoreKeyDaily($user_id, $dungeon_floor_id, $day){
    return Env::MEMCACHE_PREFIX . 'w_sd_' . $dungeon_floor_id . "_" . $day . "_" . $user_id;
  }
  
  public static function getWDungeonScoreKeyWeekly($user_id, $dungeon_floor_id, $day){
    return Env::MEMCACHE_PREFIX . 'w_sw_' . $dungeon_floor_id . "_" . $day . "_" . $user_id;
  }
  
  public static function getWDungeonScoreKeyMonthly($user_id, $dungeon_floor_id, $day){
    return Env::MEMCACHE_PREFIX . 'w_sm_' . $dungeon_floor_id . "_" . $day . "_" . $user_id;
  }

  // #PADC# ----------begin----------
  // ユーザ登録制限情報
  public static function getSignupLimitKey($date) {
  	return Env::MEMCACHE_PREFIX . 'signup_limit_' . $date;
  }
  // ユーザ登録上限かどうか
  public static function getSignupUserLimit($date) {
  	return Env::MEMCACHE_PREFIX . 'signup_user_limit_' . $date;
  }
  // 用户扭蛋次数计数器
  public static function getGachaDailyPlayCounter($user_id, $gacha_id, $is_single = true) {
    $prefix = $is_single ? "1" : "10";
    return Env::MEMCACHE_PREFIX . $prefix . '_gacha_p_d_cnt_' . $user_id . "_" . $gacha_id;
  }
  // 用户关卡挑战次数计数器
  public static function getDungeonDailyChallengeCounter($user_id, $dungeon_floor_id) {
    return Env::MEMCACHE_PREFIX . 'dungeon_f_d_cnt' . $user_id . $dungeon_floor_id;
  }

  public static function getPointRankingKey($ranking_id)
  {
    return Env::MEMCACHE_PREFIX . 'pt_ranking_' . $ranking_id;
  }
  //排名关卡的key
  public static function getRankingDungeonKey($ranking_id)
  {
    return Env::MEMCACHE_PREFIX . 'ranking_dungeon_' . $ranking_id;
  }
  
  public static function getPointTopRankingCacheKey($ranking_id, $limit)
  {
    return Env::MEMCACHE_PREFIX . 'pt_top_ranking_' . $ranking_id . '_' . $limit;
  }
  // 设置排名关卡top,CacheKey
  public static function getTopRankingDungeonCacheKey($ranking_id, $limit)
  {
    return Env::MEMCACHE_PREFIX . 'top_ranking_dungeon_' . $ranking_id . '_' . $limit;
  }
  // デバッグユーザ
  public static function getDebugUserKey() {
  	return Env::MEMCACHE_PREFIX . 'debug_users';
  }
  // #PADC# ----------end----------
}

?>
