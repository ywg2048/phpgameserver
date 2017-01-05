<?php
/**
 * #PADC#
 * デバッグユーザ
 */

class DebugUser extends BaseMasterModel {
	const TABLE_NAME = "padc_debug_users";
	const VER_KEY_GROUP = "padc_debug_users";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// メンテナンス突破できるかどうか
	const MAINTENANCE_NORMAL	= 0;// 通常ユーザ同様メンテナンス状態
	const MAINTENANCE_FREE		= 1;// メンテ突破

	// チートチェック判定をスルーするか
	const CHEAT_CHECK_NORMAL	= 0;// 通常ユーザ同様
	const CHEAT_CHECK_FREE		= 1;// チート判定スルー（零収益にならない）

	// DROP内容変更するか
	const DROP_CHANGE_OFF		= 0;// 通常ユーザ同様
	const DROP_CHANGE_ON		= 1;// DROP内容変更判定あり

	// スキルアップ確率変更するか
	const SKILL_UP_CHANGE_OFF		= 0;// 通常ユーザ同様
	const SKILL_UP_CHANGE_ON		= 1;// スキルアップ確率変更あり
	
	
	protected static $columns = array(
		'user_id',
		'open_id',
		'maintenance_flag',
		'cheatcheck_flag',
		'dropchange_flag',
		'drop_round_prob',
		'drop_plus_prob',
		'skillup_change_flag',
		'skillup_change_prob',
		'memo',
	);

	static public function getDebugUsers() {
		$rRedis = Env::getRedisForShareRead();
		$key = RedisCacheKey::getDebugUserKey();
		$debugUsers = $rRedis->get($key);
		if($debugUsers === FALSE)
		{
			// OpenID、UserIDをキーとしてデバッグユーザ情報をキャッシュに保存しておく
			$debugUsers = DebugUser::findAllBy(array());
			$setDebugUsers = array();
			foreach($debugUsers as $debugUser)
			{
				if($debugUser->open_id)
				{
					$setDebugUsers[$debugUser->open_id] = $debugUser;
				}
				if($debugUser->user_id)
				{
					$setDebugUsers[$debugUser->user_id] = $debugUser;
				}
			}
			$debugUsers = $setDebugUsers;

			$redis = Env::getRedisForShare();
			$redis->set($key,$setDebugUsers,DebugUser::MEMCACHED_EXPIRE);
		}
		return $debugUsers;
	}


	static public function isCheatCheckDebugUser($user_id) {
		$debugUsers = self::getDebugUsers();
		if(isset($debugUsers[$user_id]) && $debugUsers[$user_id]->cheatcheck_flag == DebugUser::CHEAT_CHECK_FREE){
			return true;
		}
		else {
			return false;
		}
	}

	static public function isDropChangeDebugUser($user_id) {
		$debugUsers = self::getDebugUsers();
		if(isset($debugUsers[$user_id]) && $debugUsers[$user_id]->dropchange_flag == DebugUser::DROP_CHANGE_ON){
			return true;
		}
		else {
			return false;
		}
	}

	static public function getDropChangeProb($user_id) {
		$debugUsers = self::getDebugUsers();
		if (isset($debugUsers[$user_id])) {
			$debugUser = $debugUsers[$user_id];
			if ($debugUser->dropchange_flag == DebugUser::DROP_CHANGE_ON) {
				return array($debugUser->drop_round_prob, $debugUser->drop_plus_prob);
			}
		}
		return array(0, 0);
	}

	static public function isSkillUpChangeDebugUser($user_id) {
		$debugUsers = self::getDebugUsers();
		if(isset($debugUsers[$user_id]) && $debugUsers[$user_id]->skillup_change_flag == DebugUser::SKILL_UP_CHANGE_ON){
			return true;
		}
		else {
			return false;
		}
	}

	static public function getSkillUpChangeProb($user_id) {
		$debugUsers = self::getDebugUsers();
		if (isset($debugUsers[$user_id])) {
			$debugUser = $debugUsers[$user_id];
			if ($debugUser->skillup_change_flag == DebugUser::SKILL_UP_CHANGE_ON) {
				return $debugUser->skillup_change_prob;
			}
		}
		return 0;
	}

}
