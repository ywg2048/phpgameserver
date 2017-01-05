<?php
/**
 * download vip lv up ,weekly and cost data
 * 
 * first,get all bonuses and vip cost from apc,if apc expired,get costs and bonuses from databases,arrange them,the format
 * like below:
 * {"res":0,
 * "cost":[60,300],
 * "description":["xxxx","yyyy"],
 * "lvup":[{"piece":[[10008,1],[10009,1],[10010,1]]},{"piece":[[10010,1],[10009,1],[10008,1]]}}],
 * "weekly":[{"coin":300,"piece":[[10001,1]]},{"cont":1,"piece":[[10001,2]],"coin":300}}
 * 
 * @author zhudesheng
 *
 */
class DownloadVipData extends BaseAction {
	const MEMCACHED_EXPIRE = 86400;//24 hours
  	// #PADC#
  	const MAIL_RESPONSE = FALSE;
	//http://pad.localhost/api.php?action=donwload_vip_lv_up_bonusdata&pid=1&sid=1$key=jset
	public function action($params){
		$key = MasterCacheKey::getDownloadVipData();
		$value = apc_fetch($key);
		if(!$value){
			$bonuses = VipBonus::getAllBonuses();
			list($costs,$descritions) = VipCost::getAllVipCosts(true);
			if(!$bonuses || !$costs){
				$value = array();
			}else{
				//bonuses will be divied daily bonuses and level up bonuses
				list($weekly,$lvup) = self::divideBonuses($bonuses);
				//arrange data like above data format
				$result_costs = $costs;
				$result_decription = $descritions;
				$result_lvup = self::arrangeLvUp($lvup);
				$result_weekly = self::arrangeWeekly($weekly);
				$value = array('res'=>RespCode::SUCCESS,'cost'=>$result_costs,'description'=>$result_decription,'lvup'=>$result_lvup,'weekly'=>$result_weekly);
			}
			$value = json_encode($value);
				
			apc_store($key, $value,static::MEMCACHED_EXPIRE + VipBonus::add_apc_expire());
		}
		return $value;
	}
	/**
	 * divide bonuses
	 * 
	 * @param array $bonuses
	 * @return array two arrays,a daily bonuses,a level up bonuses
	 */
	public static function divideBonuses($bonuses){
		foreach ($bonuses as $bonus){
			if ($bonus->bonus_type)//daily bonus
			{
				$item_weekly[] = $bonus;
			}else{
				$item_lvup[] = $bonus;//lv up bonus
			}
		}
		return array($item_weekly,$item_lvup);
	}	
	
	/**
	 * level up arrange
	 * @param array $bonuses
	 * @return array arranged array
	 */
	protected static function arrangelvUp($bonuses){
		$item_daily = array();
		
		foreach ($bonuses as $bonus){
			$item[(int)$bonus->vip_lv -1]['piece'][] = array((int)$bonus->piece_id,(int)$bonus->amount);
		}
		foreach ($item as &$it){
			usort($it['piece'], 'compPiece');
		}
		return $item;
	}
	
	/**
	 * weekly bonuses arrange
	 * @param array $bonuses
	 * @return array arranged array
	 */
	protected static function arrangeWeekly($bonuses){
		$item = array();
		foreach ($bonuses as $key => $bonus){
			if($bonus->bonus_id == BaseBonus::COIN_ID){
				$item[(int)$bonus->vip_lv-1]['coin'] = (int)$bonus->amount;
			}else if($bonus->bonus_id == BaseBonus::FRIEND_POINT_ID){
				$item[(int)$bonus->vip_lv-1]['fripnt'] = (int)$bonus->amount;
			}elseif ($bonus->bonus_id ==BaseBonus::CONTINUE_ID){
				$item[(int)$bonus->vip_lv-1]['continue'] = (int)$bonus->amount;
			}
			elseif ($bonus->bonus_id ==BaseBonus::ROUND_ID){
				$item[(int)$bonus->vip_lv-1]['round'] = (int)$bonus->amount;
			}
			else if($bonus->bonus_id == BaseBonus::PIECE_ID) {
				$item[(int)$bonus->vip_lv-1]['piece'][] = array((int)$bonus->piece_id,(int)$bonus->amount);
			}

			foreach ($item as &$it){
				if(isset($it['piece'])){
					usort($it['piece'], 'compPiece');
				}
			}
				
		}
		return $item;
	}
}

function compPiece($a, $b){
	if($a[0] > $b[0]) {
		return 1;
	}else if($a[0] < $b[0]){
		return -1;
	}else{
		return 0;
	}
}

