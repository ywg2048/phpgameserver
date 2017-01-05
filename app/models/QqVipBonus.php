<?php
class QqVipBonus extends BaseMasterModel {
	const TABLE_NAME = "padc_qq_vip_bonuses";
	const VER_KEY_GROUP = "padcqvb";
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	const TYPE_QQ_VIP_LOGIN_BONUS = 1;
	const TYPE_QQ_VIP_PURCHASE_BONUS = 2;
	const TYPE_QQ_VIP_NOVICE_BONUS = 3;
	const INDEX_MAX = 6;
	const DEBUG_EXPIRE = 3600;
	protected static $columns = array (
			'id',
			'type',
			'qq_vip',
			'bonus_id',
			'piece_id',
			'amount,' 
	);
	
	/**
	 *
	 * @param number $type        	
	 * @param number $qq_vip        	
	 * @return array
	 */
	public static function getBonuses($type, $qq_vip) {
		$result = array ();
		$bonuses = static::getAll ();
		foreach ( $bonuses as $bonus ) {
			if ($bonus->type == $type && $bonus->qq_vip == $qq_vip) {
				$result [] = $bonus;
			}
		}
		return $result;
	}
	
	/**
	 *
	 * @return array
	 */
	public static function getDownloadData() {
		$items = array (array(), array(), array(), array(), array(), array());
		$bonuses = static::getAll ();
		if (! $bonuses)
			return $items;
		foreach ( $bonuses as $bonus ) {
			$item = null;
			if ($bonus->bonus_id == BaseBonus::COIN_ID) {
				$item = array (
						'coin' => ( int ) $bonus->amount 
				);
			} elseif ($bonus->bonus_id == BaseBonus::MAGIC_STONE_ID) {
				$item = array (
						'gold' => ( int ) $bonus->amount 
				);
			} else if ($bonus->bonus_id == BaseBonus::FRIEND_POINT_ID) {
				$item = array (
						'fripnt' => ( int ) $bonus->amount 
				);
			} elseif ($bonus->bonus_id == BaseBonus::ROUND_ID) {
				$item = array (
						'round' => ( int ) $bonus->amount 
				);
			} else if ($bonus->bonus_id == BaseBonus::PIECE_ID) {
				$item = array (
						'piece' => array (
								( int ) $bonus->piece_id,
								( int ) $bonus->amount 
						) 
				);
			}
			if (isset ( $item )) {
				$idx = self::getDownloadDataIndex ( $bonus );
				if($idx >= 0 && $idx <= self::INDEX_MAX){
					$items [$idx] [] = $item;
				}else{
					global $logger;
					$logger->log('data error: type='.$bonus->type.' qq_vip='.$bonus->qq_vip, Zend_Log::WARN);
				}
			}
		}
		return $items;
	}
	
	/**
	 *
	 * @param QqVipBonus $bonus        	
	 * @return number
	 */
	private static function getDownloadDataIndex($bonus) {
		return ($bonus->type - 1) * 2 + $bonus->qq_vip - 1;
	}
	
	//-------------for debug-------------------------------
	public static function setDebugValue($user_id, $qq_vip){
		$redis = Env::getRedisForUser();
		$key = self::getDebugCacheKey($user_id);
		$redis->set($key, $qq_vip, QqVipBonus::DEBUG_EXPIRE);
	}
	
	public static function getDebugValue($user_id){
		$rRedis = Env::getRedisForUserRead();
		$key = self::getDebugCacheKey($user_id);
		return $rRedis->get($key);
	}
	
	public static function clearDebugValue($user_id){
		$redis = Env::getRedisForUser();
		$key = self::getDebugCacheKey($user_id);
		$redis->delete ( $key );
	}
	
	private static function getDebugCacheKey($user_id){
		return Env::MEMCACHE_PREFIX . 'debug_qq_vip_' . $user_id;
	}
	//-----------------------------------------------------
}
