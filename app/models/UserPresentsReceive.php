<?php
/**
 * #PADC#
 */
class UserPresentsReceive extends BaseModel {
	const TABLE_NAME = "user_presents_receive";
	const HAS_UPDATED_AT = TRUE;
	const STATE_UNRECEIVED = 0;
	const STATE_RECEIVED = 1;
	const STATE_UNRECEIVED_BACK = 2;
	
	// columns
	protected static $columns = array (
			'id',
			'sender_id',
			'receiver_id',
			'status',
			'expire_at' 
	);
	
	/**
	 *
	 * @param int $present_id        	
	 * @param int $sender_id        	
	 * @param int $receiver_id        	
	 * @param bool $send_back        	
	 */
	public static function sendPresent($sender_id, $receiver_id, $pdo_receiver, $send_back = false) {
		$present = new UserPresentsReceive ();
		$present->sender_id = $sender_id;
		$present->receiver_id = $receiver_id;
		$present->status = ($send_back) ? static::STATE_UNRECEIVED_BACK : static::STATE_UNRECEIVED;
		$present->expire_at = User::timeToStr ( time () + GameConstant::PRESENT_RECEIVE_EXPIRE );
		$present->create ( $pdo_receiver );
	}
	
	/**
	 *
	 * @param int $receiver_id        	
	 * @return array
	 */
	public static function getUnreceivedPresents($receiver_id) {
		$pdo = Env::getDbConnectionForUserRead ( $receiver_id );
		
		$sql = "SELECT * FROM " . static::TABLE_NAME;
		$sql .= " WHERE receiver_id = ? AND status != ? AND NOW() < expire_at";
		$sql .= " ORDER BY created_at ASC";
		$values = array (
				$receiver_id,
				static::STATE_RECEIVED 
		);
		$stmt = $pdo->prepare ( $sql );
		$stmt->setFetchMode ( PDO::FETCH_CLASS, get_called_class () );
		$stmt->execute ( $values );
		$objs = $stmt->fetchAll ( PDO::FETCH_CLASS, get_called_class () );
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		}
		
		return $objs;
	}
	
	/**
	 *
	 * @param PDO $pdo        	
	 */
	public function receive($pdo) {
		$this->status = UserPresentsReceive::STATE_RECEIVED;
	}
	
	/**
	 * 期限切れ、受け取り済みデータ削除
	 */
	public static function removeExpired($user_id, $pdo = null) {
		if (! Env::ENABLE_DELETE_EXPIRED_STAMINA_PRESENTS) {
			return;
		}
		if (is_null ( $pdo )) {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		}
		$sql = 'DELETE FROM ' . static::TABLE_NAME;
		$sql .= ' WHERE receiver_id = ? AND ( status = ? OR NOW() >= expire_at)';
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				$user_id,
				static::STATE_RECEIVED 
		);
		$result = $stmt->execute ( $values );
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		}
	}
	
	/**
	 * 期限切れ、受け取り済みデータすべて削除
	 */
	public static function removeAllExpired($pdo) {
		$sql = 'DELETE FROM ' . static::TABLE_NAME;
		$sql .= 'WHERE status = ? OR NOW() >= expire_at';
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				static::STATE_RECEIVED 
		);
		$result = $stmt->execute ( $values );
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		}
	}
	/**
	 * return already received present id and sender_id on game's today,first check whethor or not in memcache,if
	 * not get it from database,then cache it.
	 *
	 * @param int $user_id        	
	 * @return array
	 */
	public static function getReceivedPresentIdsOnToday($user_id) {
		$rRedis = ENV::getRedisForUserRead();
		$key = CacheKey::getReceivedPresentIds ( $user_id );
		$present_ids = $rRedis->get ( $key );
		if (empty ( $present_ids )) {
			// $today is a array,have two key start and end
			$today_range = UserPresentsSend::getTodayRange ();
			$sql = "SELECT * FROM " . static::TABLE_NAME . " WHERE receiver_id =? AND status = ? AND UNIX_TIMESTAMP(updated_at) > ?";
			$value = array (
					$user_id,
					static::STATE_RECEIVED,
					$today_range ['start'] 
			);
			$pdo = ENV::getDbConnectionForUserRead ( $user_id );
			$stmt = $pdo->prepare ( $sql );
			$stmt->setFetchMode ( PDO::FETCH_CLASS, get_called_class () );
			$stmt->execute ( $value );
			$objs = $stmt->fetchAll ( PDO::FETCH_CLASS, get_called_class () );
			$result = array ();
			foreach ( $objs as $obj ) {
				$result [] = $obj->id;
			}
			$present_ids = $result;
			if (! empty ( $present_ids )) {
				self::cacheReceivedIds ( $user_id, $present_ids );
			}
		}
		return $present_ids;
	}
	/**
	 * convenience for cache value,only need stored value and user_id as input,
	 *
	 * @param int $user_id        	
	 * @param array $value        	
	 */
	public static function cacheReceivedIds($user_id, $value) {
		$key = CacheKey::getReceivedPresentIds ( $user_id );
		$expire = UserPresentsSend::getSecendsToTomorrow ();
		if ($expire > 1) {
			$redis = Env::getRedisForUser();
			$redis->set ( $key, $value, $expire );
		}
		return;
	}
	
	/**
	 *
	 * @param number $user_id        	
	 */
	public static function getPresentLimit($user) {
		if ($user->present_receive_max <= 0) {
			return 1;
		}
		$today_received_ids = UserPresentsReceive::getReceivedPresentIdsOnToday ( $user->id );
		$receive_count = count ( $today_received_ids );
		if($receive_count >= $user->present_receive_max){
			return 0;
		}else{
			return ($user->present_receive_max - $receive_count);
		}
	}
}
