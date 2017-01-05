<?php
/**
 * 
 * @author zhudesheng
 *
 */
class UserExchangeRemain extends BaseModel {
	const TABLE_NAME = "user_exchange_remain";
	const MEMCACHED_EXPIRE = 3600; // 1時間.
	protected static $columns = array (
			'id',
			'user_id',
			'item_id',
			'lineup_id',
			'remain' 
	);
	
	/**
	 *
	 * @param number $userId        	
	 * @param array $items        	
	 * @param PDO $pdo        	
	 * @return array
	 */
	public static function getRemains($user_id, $items, $groups, $pdo = null) {
		$cachedUserRemains = self::getUserRemainFromRedis ( $user_id );
		
		$limitedItemIds = array ();
		foreach ( $items as $item ) {
			if ($item->limit_num != 0) {
				$limitedItemIds [] = $item->id;
			}
		}
		
		// global $logger;$logger->log('groups:'.print_r($groups, true), 7);
		$lineupIds = array ();
		foreach ( $groups as $group ) {
			foreach ( $group as $lineup ) {
				$lineupIds [] = $lineup ['lineup_id'];
			}
		}
		
		$itemRemains = array ();
		foreach ( $cachedUserRemains as $cachedUserRemain ) {
			$itemId = $cachedUserRemain->item_id;
			$lineupId = $cachedUserRemain->lineup_id;
			if (in_array ( $itemId, $limitedItemIds ) && in_array ( $lineupId, $lineupIds )) {
				$itemRemains [$itemId] = $cachedUserRemain->remain;
			}
		}
		
		// global $logger;$logger->log ( '$itemRemains:' . print_r ( $itemRemains, true ), 7 );
		return $itemRemains;
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @return array
	 */
	public static function getUserRemainFromRedis($user_id, $pdo = null) {
		$key = CacheKey::getUserExchangeRemain ( $user_id );
		$rRedis = Env::getRedisForUserRead ();
		$userRemainsData = $rRedis->get ( $key );
		if ($userRemainsData === FALSE) {
			$userRemains = self::findAllBy ( array (
					'user_id' => $user_id 
			), null, null, $pdo );
			if (! $userRemains) {
				$userRemains = array ();
			}
			$userRemainsData = self::setUserRemainToRedis ( $key, $userRemains );
		}
		//global $logger;$logger->log('getUserRemainFromRedis:'.print_r(json_decode ( $userRemainsData ), true), 7);
		return json_decode ( $userRemainsData );
	}
	
	/**
	 *
	 * @param string $key        	
	 * @param array $user_remains        	
	 * @return string
	 */
	public static function setUserRemainToRedis($key, $user_remains ) {
		$redis = Env::getRedisForUser ();
		$userRemainsData = json_encode ( $user_remains );
		$redis->set ( $key, $userRemainsData, self::MEMCACHED_EXPIRE );
		return $userRemainsData;
	}
}