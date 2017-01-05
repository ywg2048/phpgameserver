<?php
/**
 * create get delete be initialized account inheritaces
 * 
 * @author zhudesheng
 *
 */
class UserDevicesInherit extends BaseModel {
	const TABLE_NAME = "padc_user_devices_inherit";
	const MEMCACHED_EXPIRE = 86400;//just a day
	protected static $columns = array(
			'id',
			'oid',
			'type',
			'vip_lv',
			'tp_gold',
			'tss_end',
	);
	/**
	 * create a record to store be initaled account,and reset all inheritances cache 
	 * 
	 * @param array $params
	 */
	public static function SetInheritance($params,$pdo_share=null){
		$userDevicesInherit = new UserDevicesInherit;
		$userDevicesInherit->oid = $params['oid'];
		$userDevicesInherit->type = $params['type'];
		$userDevicesInherit->vip_lv = $params['vip_lv'];
		$userDevicesInherit->tp_gold = $params['tp_gold'];
		$userDevicesInherit->tss_end = $params['tss_end'];
		if(!$pdo_share){
			$pdo_share = Env::getDbConnectionForShare();
		}
		$userDevicesInherit->create($pdo_share);
		self::updateInheritancesCache($pdo_share);
	}
	/**
	 * delete used record and update all inheritances in cache
	 */
	public function unSetInheritance(){
		$pdo = Env::getDbConnectionForShare();
		$this->delete($pdo);
		self::updateInheritancesCache();
	}
	/**
	 * get inheritance for caller
	 * @param int $type
	 * @param string $openId
	 * @return boolean|mixed if not value found,return false,else return value
	 */
	public static function getInheritance($type,$openId){
		$heritances = self::getAllInheritance();
		foreach ($heritances as $heritance){
			if($heritance->oid == $openId && $heritance->type == $type  ){
				return $heritance;
			}
		}
		return false;
	}
	/**
	 * remove all old inheritances cache,and get all new inheritances
	 */
	public static function updateInheritancesCache($pdo_share = null){

		$key = CacheKey::getAllInheritance();
		$redis = Env::getRedisForUser();
		$redis->del($key);
		self::getAllInheritance($pdo_share);
	}
	/**
	 * cache all inheritance from table padc_user_devices_inherit,all inheritance will be cached a day,
	 * if some data is updated,update updated data in where data is changed 
	 */
	public static function getAllInheritance($pdo_share = null){
		$key = CacheKey::getAllInheritance();
		$rRedis = Env::getRedisForUserRead();
		$values = $rRedis->get($key);
		if($values == false){
			if(!$pdo_share){
				$pdo_share = Env::getDbConnectionForShare();
			}
			$values = UserDevicesInherit::findAllBy(array(),null,null,$pdo_share);
			$redis = Env::getRedisForUser();
			$redis->set($key,$values,static::MEMCACHED_EXPIRE);
		}
		return $values;
	}

}