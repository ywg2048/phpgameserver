<?php
/**
 * アカウントBan時の表示メッセージ
 */
class UserBanMessage extends BaseModel {
	const TABLE_NAME = "user_ban_messages";
	protected static $columns = array (
			'id',
			'user_id',
			'message',
			// #PADC# ----------begin----------
			'end_time',
			'play_ban_normal_message',
			'play_ban_normal_end_time',
			'play_ban_special_message',
			'play_ban_special_end_time',
			'play_ban_ranking_message',
			'play_ban_ranking_end_time',
			'play_ban_buydung_message',
			'play_ban_buydung_end_time',
			'zeroprofit_message',
			'zeroprofit_end_time',
			'ban_mail_message',
			'ban_mail_end_time'
	);
	// #PADC# ----------end----------
	
	/**
	 * ユーザー処罰指定
	 *
	 * @param number $user_id        	
	 * @param array $punish_list        	
	 * @param PDO $pdo        	
	 * @throws PadException
	 * @throws Exception
	 */
	public static function punishUser($user_id, $punish_list, $pdo) {
		$user = User::find ( $user_id, $pdo, true );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'User not found!' );
		}
		
		// allways update punish
		//$need_update = false;
		//foreach ( $punish_list as $punish ) {
		//	$cachedPunish = UserBanMessage::getCachedPunish ( $user_id, $punish ['type'] );
		//	if ($cachedPunish === FALSE || $cachedPunish ['msg'] != $punish ['message']) {
		//		$need_update = true;
		//		break;
		//	}
		//}
		//if (! $need_update) {
		//	return;
		//}
		
		$create = false;
		$user_ban_message = UserBanMessage::findBy ( array (
				'user_id' => $user_id 
		), $pdo, true );
		if (! $user_ban_message) {
			$user_ban_message = new UserBanMessage ();
			$user_ban_message->user_id = $user_id;
			$create = true;
		}
		
		foreach ( $punish_list as $punish ) {
			$punish_type = $punish ['type'];
			$end_time = $punish ['end_time'];
			$message = $punish ['message'];
			if ($punish_type == User::PUNISH_BAN) {
				$user->del_status = User::STATUS_BAN;
				
				$user_ban_message->message = $message;
				$user_ban_message->end_time = BaseModel::timeToStr ( $end_time );
			} else {
				$user->punish_status |= 1 << ($punish_type - 1);
				
				$var_message = self::getMessageName($punish_type);
				$user_ban_message->$var_message = $message;
				$var_end_time = self::getEndTimeName($punish_type);
				$user_ban_message->$var_end_time = BaseModel::timeToStr ( $end_time );
			}
			self::cachePunish ( $user_id, $punish_type, $end_time, $message );
		}
		
		$user->update ( $pdo );
		if ($create) {
			$user_ban_message->create ( $pdo );
		} else {
			$user_ban_message->update ( $pdo );
		}
	}
	
	/**
	 * ユーザー処罰解除
	 *
	 * @param number $user_id        	
	 * @param number $punish_type        	
	 * @param PDO $pdo        	
	 * @throws PadException
	 */
	public static function relievePunish($user_id, $punish_types, $pdo) {
		$user = User::find ( $user_id, $pdo, true );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'User not found!' );
		}
		
		$user_ban_message = UserBanMessage::findBy ( array (
				'user_id' => $user_id 
		), $pdo, true );
		if (! $user_ban_message) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'User ban message not found!' );
		}
		
		foreach ( $punish_types as $punish_type ) {
			if ($punish_type == User::PUNISH_BAN) {
				$user->del_status = User::STATUS_NORMAL;
				$user_ban_message->message = null;
				$user_ban_message->end_time = null;
			} else {
				$user->punish_status &= ! (1 << ($punish_type - 1));

				$var_message = self::getMessageName($punish_type);
				$user_ban_message->$var_message = null;
				$var_end_time = self::getEndTimeName($punish_type);
				$user_ban_message->$var_end_time = null;
			}
			self::removeCachedPunish ( $user_id, $punish_type );
		}
		
		$user->update ( $pdo );
		$user_ban_message->update ( $pdo );
	}
	
	/**
	 * ユーザー処罰情報取得、期限切れたら、処罰解除
	 *
	 * @param number $user_id        	
	 * @param number $punish_type        	
	 * @param PDO $pdo        	
	 * @return array
	 */
	public static function getPunishInfo($user_id, $punish_type, $pdo = null) {
		$infos = self::getMultiPunishInfo($user_id, array($punish_type), $pdo);
		return $infos[$punish_type];
	}
	
	/**
	 * 
	 * @param number $user_id
	 * @param array $punish_types
	 * @param PDO $pdo
	 * @throws PadException
	 * @return array
	 */
	public static function getMultiPunishInfo($user_id, $punish_types, $pdo = null) {
		//global $logger;
		//$logger->log('$punish_types:'.print_r($punish_types, true), 7);
		if (! $pdo) {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		}
		
		$user = User::find ( $user_id, $pdo );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'User not found!' );
		}
		
		$infos = array ();
		$all_cache_found = true;
		foreach ( $punish_types as $punish_type ) {
			$cached_punish = self::getCachedPunish ( $user_id, $punish_type );
			if ($cached_punish !== FALSE) {
				$infos [$punish_type] = $cached_punish;
				$infos [$punish_type]['msg'] = self::formatBanMessage($infos [$punish_type]['msg'], $infos [$punish_type]['end']);
			} else {
				$all_cache_found = false;
			}
		}
		
		//$logger->log('cached info:'.print_r($infos, true), 7);
		if ($all_cache_found) {
			return $infos;
		}
		
		$user_ban_message = UserBanMessage::findBy ( array (
				'user_id' => $user_id 
		), $pdo );
		if (! $user_ban_message) {
			return null;
		}
		
		foreach ( $punish_types as $punish_type ) {
			//$logger->log('check type:'.$punish_type, 7);
			if(isset($infos[$punish_type])){
				continue;
			}
			
			$cur_status = false;
			$expired = false;
			$cur_time = time ();
			$end_time = 0;
			if ($punish_type == User::PUNISH_BAN && $user->del_status == User::STATUS_BAN) {
				$cur_status = true;
				$end_time = $user_ban_message->end_time;
				if (BaseModel::strToTime ( $user_ban_message->end_time ) < $cur_time) {
					$expired = true;
				}
			} else {
				$bits = 1 << ($punish_type - 1);
				if ($user->punish_status & $bits == $bits) {
					$cur_status = true;
					$end_time_name = self::getEndTimeName ( $punish_type );
					$end_time = BaseModel::strToTime ( $user_ban_message->$end_time_name );
					if ($end_time < $cur_time) {
						$expired = true;
					}
				}
			}
			
			$next_status = $cur_status && ! $expired;
			
			if ($cur_status && $expired) {
				self::relievePunish ( $user_id, array (
						$punish_type 
				), $pdo );
			}
			
			$info = array ();
			if ($next_status) {
				$message_name = self::getMessageName ( $punish_type );
				$res ['msg'] = self::formatBanMessage($user_ban_message->$message_name, $end_time);
				$res ['end'] = $end_time;
			}
			
			//$logger->log('$info:'.print_r($info, true), 7);
			$infos[$punish_type] = $info;
		}
		return $infos;
	}
	
	/**
	 *
	 * @param number $punish_type        	
	 * @return string
	 */
	private static function getEndTimeName($punish_type) {
		$names = array (
				'end_time',
				'play_ban_normal_end_time',
				'play_ban_special_end_time',
				'play_ban_ranking_end_time',
				'play_ban_buydung_end_time',
				'zeroprofit_end_time',
				'ban_mail_end_time'
		);
		return $names [$punish_type];
	}
	
	/**
	 *
	 * @param number $punish_type        	
	 * @return string
	 */
	private static function getMessageName($punish_type) {
		$names = array (
				'message',
				'play_ban_normal_message',
				'play_ban_special_message',
				'play_ban_ranking_message',
				'play_ban_buydung_message',
				'zeroprofit_message',
				'ban_mail_message'
		);
		return $names [$punish_type];
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param number $punish_type        	
	 * @return array
	 */
	public static function getCachedPunish($user_id, $punish_type) {
		$redis = Env::getRedisForUserRead ();
		$key = CacheKey::getUserBanMessage ( $user_id, $punish_type );
		$user_ban_message = $redis->get ( $key );
		return $user_ban_message;
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param number $punish_type        	
	 * @param string $end_time        	
	 * @param string $message        	
	 */
	public static function cachePunish($user_id, $punish_type, $end_time, $message) {
		$redis = Env::getRedisForUser ();
		$key = CacheKey::getUserBanMessage ( $user_id, $punish_type );
		$redis->set ( $key, array (
				'end' => $end_time,
				'msg' => $message 
		), $end_time - time () );
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param number $punish_type        	
	 */
	public static function removeCachedPunish($user_id, $punish_type) {
		$redis = Env::getRedisForUser ();
		$key = CacheKey::getUserBanMessage ( $user_id, $punish_type );
		if ($redis->exists ( $key )) {
			$redis->del ( $key );
		}
	}

	/**
	 * 
	 * @param string $msg
	 * @param int $end_time
	 * @return string
	 */
	public static function formatBanMessage($msg, $end_time){
		return $msg . "\n\n" . strftime('%Y-%m-%d %H:%M为止', $end_time);
	}
}
