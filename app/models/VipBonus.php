<?php
/**
* Padc vip bonuses
* contained vip level up bonuses and vip daily login bonuses
*
* @author zhudesheng
*
*/
class VipBonus extends BaseMasterModel
{
	const MEMCACHED_EXPIRE = 86400;//24 hours
	const TABLE_NAME="padc_vip_bonuses";
	const VER_KEY_GROUP = "padcvip";
	const BONUS_TYPE_LVUP = 0;
	const BONUS_TYPE_WEEKLY = 1;

	protected static $columns = array(
			'id',
			'vip_lv',
			'bonus_type',
			'bonus_id',
			'piece_id',
			'amount',
	);
	/***
	 * get all vip bonuses data,not use memcache,because data be cached with other data
	 * @throws PadException
	 * @return array | null
	 */
	public static function getAllBonuses(){
			$pdo = Env::getDbConnectionForShareRead();
			return  static::findAllBy(array(),static::$columns[1],null,$pdo,null);
		}
		
	/**
	 * get weekly vip bonuses,if bonuses not in apc,then find memcache,if not in memcache
	 * query database,then store in memcache and apc,memcache and apc have one day
	 * @param int $vip_lv
	 * @return array | false | empty array, if have some wrong return false,if not find anything,return empty array,else
	 * retrun array
	 */
	public static function getWeeklyVipBonuses($vip_lv){
		$params = array(
				'vip_lv' => $vip_lv,
				'bonus_type' => self::BONUS_TYPE_WEEKLY,
		);
		return self::getAllBy($params);
	}
	
	/**
	 * get a lv bonuses when lv up,
	 * @param int $blv
	 * @return false | empty array| object stored in array: level up awards
	 */	
	public static function getLvUpBonuses($blv){
		$params = array('vip_lv'=>$blv,'bonus_type'=>VipBonus::BONUS_TYPE_LVUP);
		return self::getAllBy($params);
	}
	/**
	 * get user available bonuses level
	 * 
	 * @param int $user_id
	 * @param object $pdo
	 * @throws PadException
	 * @return array if none available bonus level,return empty array,otherwise return a level array like this
	 * array(1,2,3),1,2,3 level is available level
	 */
	public static function getAvailableBonusLevel($user_id,$pdo=null){
		//get pdo object
		if(!$pdo){
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		//find user object,if null throw a exception
		$user=User::find($user_id);
		if(!$user)throw new PadException(RespCode::USER_NOT_FOUND,"not find user");
		$vip_lv = (int)$user->vip_lv;
		//get max vip bonus level,if null throw a exception
		$max_lv = (int)VipCost::getMaxVipLv();
		if(!$max_lv) throw new PadException(RespCode::UNKNOWN_ERROR,"no cost data");
		//compare player vip level and received vip bonuses to get available vip bonus level 
		if($vip_lv >= 1 && $vip_lv <= $max_lv){
			$levels = range(1, (int)$vip_lv);
			$received = UserVipBonus::getReceivedBonuses($user_id,$pdo);
			if($received){
				$levels = array_diff($levels, $received);
				$levels = array_values($levels);
			}
		}
		else{
			$levels = array();
		}
		
		return $levels;		
	}
	
	/**
	 * 
	 * @param unknown $bonuses
	 * @return array
	 */
	public static function arrangeVipColumns($bonuses){
		$item = array();
		foreach ($bonuses as $key => $bonus){
			if($bonus->bonus_id == BaseBonus::COIN_ID){
				$item[] = VipBonus::subArrangeVipColumns("coin", (int)$bonus->amount);
			}else if($bonus->bonus_id == BaseBonus::FRIEND_POINT_ID){
				$item[] = VipBonus::subArrangeVipColumns("friend point", (int)$bonus->amount);
			}elseif ($bonus->bonus_id ==BaseBonus::CONTINUE_ID){
				$item[] = VipBonus::subArrangeVipColumns("continue", (int)$bonus->amount);
			}elseif ($bonus->bonus_id ==BaseBonus::ROUND_ID){
				$item[] = VipBonus::subArrangeVipColumns("round", (int)$bonus->amount);
			}
			else if($bonus->bonus_id == BaseBonus::PIECE_ID) {
				$item[] = VipBonus::subArrangeVipColumns("piece", (int)$bonus->amount);
				}
			}
		return $item;
	}
		
	/**
	 * apply vip bonuses
	 * 
	 * @param array $bonuses
	 * @param object $pdo
	 * @param array $token
	 * @param object $user
	 * @return boolean|array if have error or no card,return false or null,return array only have user_card
	 */
	public static function applyVipBonuses($bonuses,$pdo,$token,$user,$rev){
		if(empty($bonuses))return false;
		$res = array();
		foreach ($bonuses as $bonus){
			$result = $user->applyBonus($bonus->bonus_id, $bonus->amount, $pdo,null, $token, $bonus->piece_id);
			if(!empty($result)){
				$res[] = User::arrangeBonusResponse($result,$rev);
			}
		}
		return $res;
	}
}