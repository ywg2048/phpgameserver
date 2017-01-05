<?php
/**
 * exchange item class
 * 
 * @author zhudesheng
 * 
 */
class ExchangeItem extends BaseMasterModel {
	const TABLE_NAME = 'padc_exchange_items';
	const VER_KEY_GROUP = "padcexch";
	const MEMCACHED_EXPIRE = 3600; // 1時間.
	protected static $columns = array (
			'id',
			'ranking_point',
			'bonus_id',
			'amount',
			'piece_id',
			'add_amount',
			'limit_num',
			'group_id' 
	);
	
	/**
	 *
	 * @param PDO $pdoShare        	
	 * @return array
	 */
	public static function getActiveExchangeItems($groups, $pdoShare = null) {
		// $cachedLineups = ExchangeLineUp::getCachedLineups ( $pdoShare );
		//$groups = ExchangeLineUp::getLineupGroups ( $cachedLineups );
		$cachedItems = self::getCachedExchangeItems ( $groups, $pdoShare );
		$current_time = time ();
		$activeItems = array ();
		foreach ( $cachedItems as $cachedItem ) {
			$groupId = $cachedItem->group_id;
			$group = $groups [$groupId];
			foreach ( $group as $lineup ) {
				if ($lineup ['start'] <= $current_time && $lineup ['end'] >= $current_time) {
					$activeItems [] = $cachedItem;
					break;
				}
			}
		}
		// global $logger;$logger->log ( 'getActiveExchangeItems:' . print_r ( $activeItems, true ), 7 );
		return $activeItems;
	}
	
	/**
	 * get valid exchange items from padc_exchange_items table
	 *
	 * @param PDO $pdoShare        	
	 * @throws PadException
	 * @return array | empty array:
	 */
	public static function getCachedExchangeItems($groups, $pdoShare = null) {
		$key = MasterCacheKey::getExchangeItems ();
		$exchangeItems = apc_fetch ( $key );
		if (FALSE === $exchangeItems) {
			$groupIds = array_keys ( $groups );
			if (! empty ( $groupIds )) {
				$exchangeItems = self::findItemsByGroupIds ( $groupIds, $pdoShare );
			} else {
				$exchangeItems = array ();
			}
			
			apc_store ( $key, $exchangeItems, self::MEMCACHED_EXPIRE + static::add_apc_expire () );
		}
		// global $logger;$logger->log ( 'getCachedExchangeItems:' . print_r ( $exchangeItems, true ), 7 );
		return $exchangeItems;
	}
	
	/**
	 *
	 * @param array $group_ids        	
	 * @param PDO $pdoShare        	
	 * @throws PadException
	 * @return array:
	 */
	private static function findItemsByGroupIds($group_ids, $pdoShare) {
		if(!$pdoShare){
			$pdoShare = Env::getDbConnectionForShareRead ();
		}
		
		$sql = "select * from " . static::TABLE_NAME . " where group_id in (" . substr ( str_repeat ( "?,", count ( $group_ids ) ), 0, - 1 ) . ");";
		$stmt = $pdoShare->prepare ( $sql );
		$stmt->setFetchMode ( PDO::FETCH_CLASS, get_called_class () );
		if ($stmt->execute ( $group_ids )) {
			$results = $stmt->fetchAll ();
			if ($results) {
				// global $logger;$logger->log ( 'findItemsByGroupIds:'.print_r($results, true), 7 );
				return $results;
			} else {
				return array ();
			}
		} else {
			throw new PadException ( RespCode::UNKNOWN_ERROR, "find items failed" );
		}
	}
}