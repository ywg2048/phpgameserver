<?php
/**
 * ログインストリークボーナス.
 */

class LoginStreakBonus extends BaseMasterModel {
  const TABLE_NAME = "login_streak_bonuses";
  const VER_KEY_GROUP = "lsb";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'days',
    'bonus_id',
    'amount',
  );

  /**
   * 指定された日数に対応するボーナスのリストを返す.
   * ボーナスがない場合は空のリストを返す.
   */
  public static function getBonuses($days){
    $apply_bonuses = array();
    $bonuses = static::getLoginStreakBonus();
    foreach($bonuses as $bonus){
      if($bonus->days == $days){
        $apply_bonuses[] = $bonus;
      }
    }
    return $apply_bonuses;
  }

  /**
   * ボーナスの配列を指定ユーザに適用する.
   * @return 適用後のUserオブジェクト.
   */
  // #PADC# パラメータ追加
  public static function applyBonuses($user, $bonuses, $pdo, $token) {
    foreach($bonuses as $bonus) {
    	// #PADC# パラメータ追加
    	$user->applyBonus($bonus->bonus_id, $bonus->amount, $pdo, null, $token);
    }
    return $user;
  }

  /**
   * ログインストリークボーナスを返す.
   */
  public static function getLoginStreakBonus() {
    $bonuses = self::getAll();
    return $bonuses;
  }

}
