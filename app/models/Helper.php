<?php

/**
 * ヘルパー関連
 */
class Helper extends BaseModel {
	const TABLE_NAME = "helpers";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array('user_id1', 'user_id2', 'used_at');

	public static function useHelper($user_id1, $user_id2){
		$point = 0;
		// #PADC# ----------begin----------
		$statusFriend = Friend::getFriendStatus($user_id1, $user_id2);
		// #PADC# ----------end----------
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id1);
			$pdo->beginTransaction();
			// #PADC# ----------begin----------memcache→redisに切り替え
			$rRedis = Env::getRedisForUserRead();
			$key = CacheKey::getUseHelperKey($user_id1, $user_id2);

			$current_time = time();
			// 今日初回のヘルプか
			$isFirstHelpToday = FALSE;
			$value = $rRedis->get($key);
			if($value){
				if(!static::isSameDay_AM4(static::strToTime($value), $current_time)){
					$isFirstHelpToday = TRUE;
				}
			}else{
				$isFirstHelpToday = TRUE;
			}
			$user = User::find($user_id1, $pdo, TRUE);
			if($isFirstHelpToday){
				// 助っ人への加算ポイントはRedisで管理する（get_player_dataで実際に加算）
				$helper_key = RedisCacheKey::getFriendPointKey($user_id2);
				$redis = Env::getRedis(Env::REDIS_POINT);
				$value = $redis->get($helper_key);
				if($value){
					list($fripntadd, $fp_by_frd, $fp_by_non_frd) = $value;
				}else {
					$fripntadd = 0;
					$fp_by_frd = 0;
					$fp_by_non_frd = 0;
				}
				// #PADC# ----------begin----------
				switch ($statusFriend) {
					case Friend::FRIEND_STATUS_NONE:
						$point = GameConstant::getParam("FriendPointForNonFriend");
						$fp_by_non_frd++;
						break;
					case Friend::FRIEND_STATUS_NORMAL:
						$point = GameConstant::getParam("FriendPointForFriend");
						$fp_by_frd++;
						break;
					case Friend::FRIEND_STATUS_SNS:
						$point = GameConstant::getParam("FriendPointForSNSFriend");
						$fp_by_frd++;
						break;
				}
				// #PADC# ----------end----------
				// #PADC#
				$fripnt_before = $user->fripnt;
				$fripntadd += $point;
				$value = array($fripntadd, $fp_by_frd, $fp_by_non_frd);
				$user->addFripnt($point);
				// #PADC#
				$fripnt_after = $user->fripnt;
				$user->accessed_at = User::timeToStr(time());
				$user->accessed_on = $user->accessed_at;
				$user->update($pdo);
				$redis->set($helper_key, $value);	// 無限に保存

				// #PADC# TLOG friend point
				UserTlog::sendTlogMoneyFlow($user, $fripnt_after - $fripnt_before, Tencent_Tlog::REASON_REQUEST_HELP, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT);
				UserTlog::sendTlogSnsFlow($user_id1, 1, Tencent_Tlog::SNSTYPE_HELP, $user_id2);
			}
			$pdo->commit();
			$redis = Env::getRedisForUser();
			$redis->set($key, User::timeToStr($current_time), static::MEMCACHED_EXPIRE);
		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}

		return array($user, $point);
	}

	/**
	 * 助っ人(パズドラW)
	 */
	public static function useWHelper($user, $friend_id){
		$medal = 0;

		// #PADC# ----------begin----------memcache→redisに切り替え
		$rRedis = Env::getRedisForUserRead();
		$key = CacheKey::getUseWHelperKey($user->id, $friend_id);

		$current_time = time();
		// 今日初回のヘルプか
		$isFirstHelpToday = FALSE;
		$value = $rRedis->get($key);
		if($value){
			if(!static::isSameDay_AM4(static::strToTime($value), $current_time)){
				$isFirstHelpToday = TRUE;
			}
		}else{
			$isFirstHelpToday = TRUE;
		}
		if($isFirstHelpToday){
			// 助っ人への加算メダルはRedisで管理する（pw_getdataで実際に加算）
			$helper_key = RedisCacheKey::getWMedalKey($friend_id);
			$redis = Env::getRedis(Env::REDIS_POINT);
			$value = $redis->get($helper_key);
			if($value){
				list($medal_add, $p_by_frd, $p_by_non_frd) = $value;
			}else {
				$medal_add = 0;
				$p_by_frd = 0;
				$p_by_non_frd = 0;
			}
			$isFriend = Friend::isFriend($user->id, $friend_id);
			if($isFriend){
				$medal = GameConstant::getParam("MedalForFriend");
				$p_by_frd++;
			}else{
				$medal = GameConstant::getParam("MedalForNonFriend");
				$p_by_non_frd++;
			}
			$medal_add += $medal;
			$value = array($medal_add, $p_by_frd, $p_by_non_frd);
			if(($user->medal + $medal) > User::W_MAX_MEDAL){
				$medal = User::W_MAX_MEDAL - $user->medal;
			}
			$user->addMedal($medal);

			$redis = Env::getRedisForUser();
			$redis->set($helper_key, $value);
			// #PADC# ----------end----------
		}
		$memcache->set($key, User::timeToStr($current_time), static::MEMCACHED_EXPIRE);

		return array($user, $medal);
	}

	// #PADC# ----------begin----------
	// 日付判定が他のモデルでも利用できるようBaseModel.phpへ移動
	// 午前4時に日替り判断
	//private static function isSameDay_AM4($time1, $time2){
	//	return strftime('%y%m%d', $time1 - 14400) == strftime('%y%m%d', $time2 - 14400);
	//}
	// #PADC# ----------end----------

}
