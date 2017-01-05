<?php
/**
 * 総ログイン日数ボーナス.
 */

class LoginTotalCountBonus extends BaseMasterModel {
	  const TABLE_NAME = "login_total_count_bonuses";
	  const VER_KEY_GROUP = "ltcb";
	  const MEMCACHED_EXPIRE = 86400; // 24時間.
	
	  protected static $columns = array(
	    'id',
	    'days',
	  	//#PADC#
	  	'ym',
	  	'pickup_flag',
	    'bonus_id',
	  	//#PADC#
	  	'piece_id',
	    'amount',
	    'plus_hp',
	    'plus_atk',
	    'plus_rec',
	  );
	  
	/**
	 * #PADC# get login bonuses
	 * 
	 * @param int $login_days
	 * @return empty array | array ,if no matched items,will return a empty array,else a array with bonuses
	 */
	public static function getLoginMonBonuses($login_days){
		$bonuses = array();
		$date = getdate();
		$time = (int)$date['year'] * 100 + (int)$date['mon'];
		$params = array('ym'=>$time);
		$values = static::getAllBy($params);
		if(!$values)return $bonuses;
		foreach ($values as $value){
			if($login_days == $value->days){
				$bonuses[] = $value;
			}
		}
	  	return $bonuses;
	  }

	/**
	 * #PADC# get all bonuses for master data download
	 * 
	 * @throws PadException
	 * @return array 
	 */
	public static function getAllBonuses(){
		$item = array();
		$bonuses = static::findAllBy(array());
		if(!$bonuses) return $item;
		foreach ($bonuses as $bonus){
			//目玉商品
			$pickup_flag = (isset($bonus->pickup_flag) && $bonus->pickup_flag)? 1 : 0;
			
			if($bonus->bonus_id == BaseBonus::COIN_ID){
				$item[(int)$bonus->ym][(int)$bonus->days]['coin'] = array((int)$bonus->amount, $pickup_flag);
			}elseif($bonus->bonus_id == BaseBonus::MAGIC_STONE_ID){
				$item[(int)$bonus->ym][(int)$bonus->days]['gold'] = array((int)$bonus->amount, $pickup_flag);
			}else if($bonus->bonus_id == BaseBonus::FRIEND_POINT_ID){
				$item[(int)$bonus->ym][(int)$bonus->days]['fripnt'] = array((int)$bonus->amount, $pickup_flag);
			}elseif($bonus->bonus_id == BaseBonus::STAMINA_ID){
				$item[(int)$bonus->ym][(int)$bonus->days]['stamina'] = array((int)$bonus->amount, $pickup_flag);
			}elseif ($bonus->bonus_id ==BaseBonus::CONTINUE_ID){
				$item[(int)$bonus->ym][(int)$bonus->days]['continue'] = array((int)$bonus->amount, $pickup_flag);
			}elseif ($bonus->bonus_id ==BaseBonus::ROUND_ID){
				$item[(int)$bonus->ym][(int)$bonus->days]['round'] = array((int)$bonus->amount, $pickup_flag);
			}else if($bonus->bonus_id == BaseBonus::PIECE_ID) {
				$item[(int)$bonus->ym][(int)$bonus->days]['piece'] = array((int)$bonus->piece_id,(int)$bonus->amount, $pickup_flag);
			}
		}
		return $item;
	}
 
	  /**
	   * 指定された日数に対応するボーナスを返す.
	   * ボーナスがない場合は　null を返す.
	   */
	  public static function getBonus($days){
	    $bonuses = static::getLoginTotalCountBonus();
	    foreach($bonuses as $bonus){
	      // コインボーナスおよび友情ポイントボーナスは即配布.
	      if($bonus->days == $days && ($bonus->bonus_id == BaseBonus::COIN_ID || $bonus->bonus_id == BaseBonus::FRIEND_POINT_ID || $bonus->bonus_id == BaseBonus::MEDAL_ID)){
	        return $bonus;
	      }
	    }
	    return NULL;
	  }
	
	  /**
	   * 指定された日数に対応するボーナスを返す.
	   * ボーナスがない場合は　null を返す.
	   */
	  public static function getBonuses($days){
	    $apply_bonuses = array();
	    $bonuses = static::getLoginTotalCountBonus();
	    foreach($bonuses as $bonus){
	      // カードボーナスおよび魔法石ボーナスは運営メールによる配布.
	      if($bonus->days == $days && ($bonus->bonus_id <= BaseBonus::MAX_CARD_ID || $bonus->bonus_id == BaseBonus::MAGIC_STONE_ID)){
	        $apply_bonuses[] = $bonus;
	      }
	    }
	    return $apply_bonuses;
	  }
	
	  /**
	   * ボーナスの配列を指定ユーザに適用する.
	   * @return 適用後のUserオブジェクト.
	   */
	  public static function applyBonuses($user, $bonuses, $pdo) {
	    foreach($bonuses as $bonus) {
	      $base_message = GameConstant::getParam("LoginTotalCountBonusMessage");
	      $message = sprintf($base_message, $bonus->days); // 通算%d日目のログインボーナスです！
	      $data = array();
	      if ($bonus->bonus_id <= BaseBonus::MAX_CARD_ID) {
	        $data["slv"] = isset($bonus->slv) ? (int)$bonus->slv : UserCard::DEFAULT_SKILL_LEVEL;
	        $data["ph"] = isset($bonus->plus_hp) ? (int)$bonus->plus_hp : 0;
	        $data["pa"] = isset($bonus->plus_atk) ? (int)$bonus->plus_atk : 0;
	        $data["pr"] = isset($bonus->plus_rec) ? (int)$bonus->plus_rec : 0;
	        $data["psk"] = 0;
	      }
	      UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS_TO_ALL, $bonus->bonus_id, $bonus->amount, $pdo, $message, $data);
	    }
	    return $user;
	  }
	
	  /**
	   * 総ログイン日数ボーナスを返す.
	   */
	  public static function getLoginTotalCountBonus() {
	    $bonuses = self::getAll();
	     return $bonuses;
	  }

}
