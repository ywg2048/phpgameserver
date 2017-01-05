<?php
/**
 * define the costs of every level up 
 * 
 * @author zhudesheng
 * @package PADC
 */
class VipCost extends BaseMasterModel
{
	const TABLE_NAME="padc_vip_cost";
	protected static $columns = array(
		'id',
		'amount',
		'description',
	);
	
	/**
	 * get vip costs,first check memcache,if not exist,find database and store result in memcache server
	 * if no infomation in database return null
	 * 
	 * @param string $extra this is dedicate whether or not output description infomation,default false
	 * @return empty array | array ,success return array,else empty array will be returned,if extra is true,will
	 * output array container costs and descriptions
	 */
	public static function getAllVipCosts($extra=FALSE){
		 $vip_costs = self::getAll();
		 $costs = array();
		 $description = array();
		 if(!empty($vip_costs)){
		 	
		 	foreach($vip_costs as $vip_cost){
		 		$costs[(int)$vip_cost->id - 1] = (int)$vip_cost->amount;
		 	}
		 	if($extra){
		 		foreach ($vip_costs as $vip_cost){
		 			$description[] = $vip_cost->description;
		 		}
		 		return array($costs,$description);
		 	}
		 }
		 return $costs;
	}
	/**
	 * get max vip level
	 * 
	 * @return int|NULL if have cost meta data ,return int value,else return null
	 */
	public static function getMaxVipLv(){
		$vip_costs = self::getAll();
		return count($vip_costs);
		/*if($vip_costs){
			$lv = array();
			foreach ($vip_costs as $vip_cost){
				$lv[] = $vip_cost->vip_lv;
			}
			return max($lv);
		}else{
			return null;
		}*/
	}
	
}