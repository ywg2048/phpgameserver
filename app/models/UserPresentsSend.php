<?php
/**
 * #PADC#
 */
class UserPresentsSend extends BaseModel {
	const TABLE_NAME = "user_presents_send";
	const HAS_UPDATED_AT = FALSE;
	// columns
	protected static $columns = array (
			'sender_id',
			'receiver_id' 
	);
	
	/**
	 * Create sender's data
	 *
	 * @param int $sender_id        	
	 * @param int $receiver_id        	
	 * @param PDO $pdo_sender        	
	 */
	public static function sendPresent($sender_id, $receiver_id, $pdo_sender) {
		$present = new UserPresentsSend ();
		$present->sender_id = $sender_id;
		$present->receiver_id = $receiver_id;
		$present->create ( $pdo_sender );
	}
	
	/**
	 * ID of friends already send today
	 *
	 * @param int $user_id        	
	 * @return array
	 */
	public static function getFriendsSendToday($user_id) {
		$rRedis = Env::getRedisForUserRead();
		$key = CacheKey::getFriendsSendPresent ( $user_id );
		$fids = $rRedis->get ( $key );
		if (! $fids) {
			$pdo = Env::getDbConnectionForUserRead ( $user_id );
			
			$today_range = self::getTodayRange ();
			
			$sql = 'SELECT * FROM ' . static::TABLE_NAME;
			$sql .= ' WHERE sender_id = ?';
			$sql .= ' AND UNIX_TIMESTAMP(created_at) >= ?';
			$sql .= ' AND UNIX_TIMESTAMP(created_at) < ?';
			$values = array (
					$user_id,
					$today_range ['start'],
					$today_range ['end'] 
			);
			$stmt = $pdo->prepare ( $sql );
			$stmt->setFetchMode ( PDO::FETCH_CLASS, get_called_class () );
			$stmt->execute ( $values );
			$objs = $stmt->fetchAll ( PDO::FETCH_CLASS, get_called_class () );
			if (empty ( $objs )) {
				return array ();
			}
			
			$fids = array ();
			foreach ( $objs as $obj ) {
				$fids [] = $obj->receiver_id;
			}
			$expire = static::getSecendsToTomorrow ();
			if ($expire > 1) {
				$redis = Env::getRedisForUser();
				$redis->set ( $key, $fids, $expire );
			}
			return $fids;
		} else {
			return $fids;
		}
	}
	
	/**
	 * remove sender's data before today
	 *
	 * @param int $user_id        	
	 * @param PDO $pdo        	
	 */
	public static function removeExpired($user_id, $pdo = null) {
		if (! Env::ENABLE_DELETE_EXPIRED_STAMINA_PRESENTS) {
			return;
		}
		if (is_null ( $pdo )) {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		}
		$today_range = self::getTodayRange ();
		$sql = 'DELETE FROM ' . static::TABLE_NAME;
		$sql .= ' WHERE sender_id = ?';
		$sql .= ' AND UNIX_TIMESTAMP(created_at) < ?';
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				$user_id,
				$today_range ['start'] 
		);
		$result = $stmt->execute ( $values );
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		}
	}
	
	/**
	 * remove all sender's data before today
	 */
	public static function removeAllExpired($pdo) {
		$today_range = self::getTodayRange ();
		$sql = 'DELETE FROM ' . static::TABLE_NAME;
		$sql .= ' WHERE UNIX_TIMESTAMP(created_at) < ?';
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				$user_id,
				$today_range ['start'] 
		);
		$result = $stmt->execute ( $values );
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		}
	}
	
	/**
	 *
	 * @return number
	 */
	public static function getSecendsToTomorrow() {
		$today_range = self::getTodayRange ();
		return $today_range ['end'] - time ();
	}
	
	/**
	 *
	 * @return array
	 */
	public static function getTodayRange() {
		$cur_time = time ();
		$start = strtotime ( strftime ( '%Y-%m-%d ' . GameConstant::PRESENT_DAY_SWITCH_TIME, $cur_time ) );
		if ($cur_time >= $start) {
			$end = $start + 86400;
		} else {
			$end = $start;
			$start = $end - 86400;
		}
		return array (
				'start' => $start,
				'end' => $end 
		);
	}
	
	public static function getUnpresentedFriend($user_id, $rev){
		global $logger;
		$friends = Friend::getFriends ( $user_id, $rev );
		$logger->log('$friends:'.print_r($friends, true), 7);
		$exclude_ids = UserPresentsSend::getFriendsSendToday ( $user_id );
		$logger->log('$exclude_ids:'.print_r($exclude_ids, true), 7);
		$friends = self::exclude ( $friends, $exclude_ids, $rev );
		
		$logger->log('$friends:'.print_r($friends, true), 7);
		return $friends;
	}
	
	public static function getUnpresentedFriendIds($user_id, $rev){
		$friends = self::getUnpresentedFriend($user_id, $rev);
		$ids = array();
		foreach($friends as $friend){
			$pid = ($rev >= 2)? $friend [1] : $friend ['pid'];
			$ids []= $pid;
		}
		
		return $ids;
	}
	
	/**
	 *
	 * @param array $friends
	 * @param array $exclude_ids
	 * @param int $rev
	 * @return array
	 */
	private static function exclude($friends, $exclude_ids, $rev) {
		$res_friends = array();
		foreach ( $friends as $key => $friend ) {
			$pid = ($rev >= 2)? $friend [1] : $friend ['pid'];
			if (!in_array ( $pid, $exclude_ids )) {
				$res_friends[] = $friend;
			}
		}
		return $res_friends;
	}
}