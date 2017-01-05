<?php
/**
 * exchange line up class
 * 
 * @author zhudesheng
 *
 */
class ExchangeLineUp extends BaseMasterModel {
	const TABLE_NAME = "padc_exchange_lineup";
	const VER_KEY_GROUP = "padcexch";
	const MEMCACHED_EXPIRE = 3600; // 1時間.
	protected static $columns = array (
			'id',
			'start_time',
			'end_time',
			'campaign_flag',
			'group_id' 
	);
	
	/**
	 *
	 * @param PDO $pdoShare        	
	 * @return array
	 */
	public static function getActiveLineup($cachedLineups) {
		// $cachedLineups = self::getCachedLineups ( $pdoShare );
		$start = 0;
		$end = 0;
		$current_time = time ();
		foreach ( $cachedLineups as $lineup ) {
			$lineupStart = BaseModel::strToTime ( $lineup->start_time );
			$lineupEnd = BaseModel::strToTime ( $lineup->end_time );
			if ($lineupStart <= $current_time && $lineupEnd >= $current_time) {
				if ($lineup->campaign_flag == 0) {
					if ($start == 0 || $start < $lineupStart) {
						$start = $lineupStart;
					}
					if ($end == 0 || $end > $lineupEnd) {
						$end = $lineupEnd;
					}
				}
			}
		}
		return array (
				$start,
				$end 
		);
	}
	
	/**
	 * get cached exchange lineups (include lineups start soon)
	 *
	 * @param PDO $pdoShare        	
	 * @throws PadException
	 * @return array | Empty array
	 */
	public static function getCachedLineups($pdoShare = null) {
		$key = MasterCacheKey::getExchangeLineup ();
		$lineups = apc_fetch ( $key );
		if (FALSE === $lineups) {
			
			if (! $pdoShare) {
				$pdoShare = Env::getDbConnectionForShareRead ();
			}
			
			$currentTime = time ();
			$startDatetime = date ( 'Y-m-d H:i:s', $currentTime + self::MEMCACHED_EXPIRE * 2 );
			$endDatetime = date ( 'Y-m-d H:i:s', $currentTime );
			// global $logger;$logger->log('find lineups start_time<='.$startDatetime.' and end_time>='.$endDatetime, 7);
			
			$sql = "select * from " . static::TABLE_NAME . " where ? >= start_time and ? <= end_time;";
			
			$stmt = $pdoShare->prepare ( $sql );
			$stmt->setFetchMode ( PDO::FETCH_CLASS, get_called_class () );
			if ($stmt->execute ( array (
					$startDatetime,
					$endDatetime 
			) )) {
				$lineups = $stmt->fetchAll ();
				apc_store ( $key, $lineups, self::MEMCACHED_EXPIRE - static::add_apc_expire () );
			} else {
				throw new PadException ( RespCode::UNKNOWN_ERROR, "fetch lineup ids failed" );
			}
		}
		//global $logger;$logger->log('getCachedLineups:'.print_r($lineups, true), 7);
		return $lineups;
	}
	
	/**
	 * get active exchange groups
	 *
	 * @param string $pdoShare        	
	 * @return array
	 */
	public static function getLineupGroups($lineups) {
		// $lineups = self::getCachedLineups ();
		$current_time = time ();
		$groups = array ();
		foreach ( $lineups as $lineup ) {
			$groupId = $lineup->group_id;
			$lineupStart = BaseModel::strToTime ( $lineup->start_time );
			$lineupEnd = BaseModel::strToTime ( $lineup->end_time );
			// if ($current_time + self::MEMCACHED_EXPIRE * 2 >= $lineupStart && $current_time <= $lineupEnd && ! array_key_exists ( $groupId, $groups )) {
			//if (! array_key_exists ( $groupId, $groups )) {
			$groups [$groupId] [] = array (
					'start' => $lineupStart,
					'end' => $lineupEnd,
					'lineup_id' => $lineup->id
			);
			//}
		}
		
		// global $logger;$logger->log('find groups start_time <= '.BaseModel::timeToStr($current_time + self::MEMCACHED_EXPIRE * 2).' and end_time >= '.BaseModel::timeToStr($current_time), 7);
		//global $logger;$logger->log('$groups:'.print_r($groups, true), 7);
		return $groups;
	}
}