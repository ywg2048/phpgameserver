<?php
/**
 * お勧めヘルパー取得用ユーティリティークラス
 */
class RecommendedHelperUtil {

	const HELPERS_COUNT = 12;
	const MEMCACHED_EXPIRE = 20; // 20秒.
	const MEMCACHED_EXPIRE_RAND_END = 30; // 30秒.

	const HELPER_MAX = 300;
	const HELPER_TRIM = 200;
	const RANGE_HELPER_END = 200;
	const RAND_RANGE = 8;

	/**
	 * 指定されたレベルの冒険者をHELPERS_COUNTの数だけ返す
	 */
	public static function findHelpers($user_id){
		$user_friend_data = User::getCacheFriendData($user_id, User::FORMAT_REV_1);
		// #PADC# PADC版ではクリアダンジョン数を基準にする
		$level = $user_friend_data['clear_dun'];
		$slot = rand(1, self::RAND_RANGE);
		$helpers = array();
		$key = CacheKey::getRecommendedHelperKey($level, $slot);
		// #PADC# ----------begin----------memcache→redisに切り替え
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if ($value === FALSE) {
			$value = static::createHelpers ( $level );
			if ($value) {
				$expire = mt_rand ( static::MEMCACHED_EXPIRE, static::MEMCACHED_EXPIRE_RAND_END );
				$redis = Env::getRedisForShare();
				$redis->set ( $key, $value, $expire );
			}
		}
		// #PADC# ----------end----------
		return $value;
	}

	private static function createHelpers($level){
		$helpers = array();
		$key = RedisCacheKey::getRecommendedHelperLevelKey($level);
		$redis = Env::getRedis(Env::REDIS_POINT);
		$helper_ids = $redis->lRange($key, 0, self::HELPER_TRIM);
		if($helper_ids === FALSE) {
			$helper_ids = array();
		}
		// 重複ユーザ削除.
		$helper_ids = array_unique($helper_ids);
		shuffle($helper_ids);
		// 12ユーザー分のフレンドデータを返す
		foreach($helper_ids as $helper_id){
			if($helper = User::getCacheFriendData($helper_id, User::FORMAT_REV_2)){
				$helpers[] = $helper;
			}
			if(count($helpers) >= (static::HELPERS_COUNT)){
				break;
			}
		}
		return $helpers;
	}

	/**
	 * #PADC#
	 * デバッグ機能用にランダム範囲で登録するか指定できるよう変更
	 * ダンジョンに潜入した場合、お勧めヘルパーの候補リストも更新
	 *
	 * @param unknown $user_id
	 * @param unknown $level
	 * @param string $is_rand
	 */
	public static function updateHelpersOfLevel($user_id, $level, $is_rand = true){
		// #PADC# ----------begin----------
		if ($is_rand) {
			$level_rand = static::getLevelRand($level);
		}
		else {
			$level_rand = $level;
		}
		// #PADC# ----------end----------
		$key = RedisCacheKey::getRecommendedHelperLevelKey($level_rand);
		$redis = Env::getRedis(Env::REDIS_POINT);
		$redis->lPush($key, $user_id);
		if (mt_rand(1, 50) == 1) {
			$len = $redis->lSize($key);
			if ($len > self::HELPER_MAX) {
				$redis->lTrim($key, 0, self::HELPER_TRIM);
			}
		}
	}

	// 画一化しないように、冒険者に出てくるユーザのレベル分散させる.
	public static function getLevelRand($level){
		// #PADC# ----------begin----------
		if($level <= 50){
			$rand = 10;
		}elseif($level <= 100){
			$rand = 15;
		}elseif($level <= 150){
			$rand = 20;
		}else{
			$rand = 25;
		}
		// レベルを分散させる
		return max($level + rand($rand * -1, $rand), 0);
		// #PADC# ----------end----------
	}

	// #PADC# ----------begin----------
	// 以下はPADW用の処理になるので、PADCでは利用することは無いです
	// #PADC# ----------end----------
	/**
	 * ダンジョンをクリアしているのユーザをHELPERS_COUNTの数だけ返す
	 */
	public static function findWHelpers($dungeon_floor_id) {
		$slot = rand(1, self::RAND_RANGE);
		$key = CacheKey::getRecommendedWHelperKey($dungeon_floor_id, $slot);
		// #PADC# ----------begin----------memcache→redisに切り替え
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if($value === FALSE) {
			$value = static::createWHelpers($dungeon_floor_id);
			if($value){
				$expire = mt_rand(static::MEMCACHED_EXPIRE, static::MEMCACHED_EXPIRE_RAND_END);
				$redis = Env::getRedisForShare();
				$redis->set($key, $value, $expire);
			}
		}
		// #PADC# ----------end----------
		return $value;
	}

	private static function createWHelpers($dungeon_floor_id){
		$helpers = array();
		$key = RedisCacheKey::getRecommendedWHelperDungeonFloorKey($dungeon_floor_id);
		$redis = Env::getRedis(Env::REDIS_POINT);
		$helper_ids = $redis->lRange($key, 0, self::RANGE_HELPER_END);
		if($helper_ids === FALSE) {
			$helper_ids = array();
		}
		// 重複ユーザ削除.
		$helper_ids = array_unique($helper_ids);
		shuffle($helper_ids);
		// 12ユーザー分のフレンドデータを返す
		foreach($helper_ids as $helper_id){
			if($helper = User::getCacheFriendData($helper_id, User::FORMAT_REV_2)){
				$helpers[] = $helper;
			}
			if(count($helpers) >= (static::HELPERS_COUNT)){
				break;
			}
		}
		return $helpers;
	}

	// ダンジョンをクリアした場合、お勧めヘルパーの候補リストも更新(W)
	public static function updateWHelpersOfDungeonFloor($user_id, $dungeon_floor_id){
		$key = RedisCacheKey::getRecommendedWHelperDungeonFloorKey($dungeon_floor_id);
		$redis = Env::getRedis(Env::REDIS_POINT);
		$redis->lPush($key, $user_id);
		if (mt_rand(1, 50) == 1) {
			$len = $redis->lSize($key);
			if ($len > self::HELPER_MAX) {
				$redis->lTrim($key, 0, self::HELPER_TRIM);
			}
		}
	}

}
