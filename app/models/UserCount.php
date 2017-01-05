<?php
/**
 * #PADC#
 * ユーザーカウントデータ
 * ミッションのクリアに利用するカウントなどを保持するクラス
 */
class UserCount extends BaseModel
{
	const TABLE_NAME = "user_counts";

	// 累計
	const TYPE_CLEAR_NORMAL	= 1;	// ノーマルダンジョンクリア回数
	const TYPE_CLEAR_SPECIAL	= 2;	// スペシャルダンジョンクリア回数
	const TYPE_GACHA_FRIEND	= 3;	// 友情ポイントガチャ回数
	const TYPE_GACHA_GOLD		= 4;	// 魔法石ガチャ回数
	const TYPE_CARD_COMPOSITE	= 5;	// 強化合成回数
	const TYPE_CARD_EVOLVE		= 6;	// 進化合成回数
	const TYPE_CARD_CREATE		= 7;	// モンスター生成回数
	const TYPE_CARD_PLUS		= 8;	// プラス合成回数
	const TYPE_CARD_SKILL_UP	= 9;	// スキルアップ合成回数
	const TYPE_STAMINA_PRESENT	= 10;	// スタミナプレゼント回数
	
	// デイリー
	const TYPE_DAILY_CLEAR_NORMAL		= 101;	// ノーマルダンジョンクリア回数
	const TYPE_DAILY_CLEAR_SPECIAL		= 102;	// スペシャルダンジョンクリア回数
	const TYPE_DAILY_GACHA_FRIEND		= 103;	// 友情ポイントガチャ回数
	const TYPE_DAILY_GACHA_GOLD		= 104;	// 魔法石ガチャ回数
	const TYPE_DAILY_CARD_COMPOSITE	= 105;	// 強化合成回数
	const TYPE_DAILY_CARD_EVOLVE		= 106;	// 進化合成回数
	const TYPE_DAILY_CARD_CREATE		= 107;	// モンスター生成回数
	const TYPE_DAILY_CARD_PLUS			= 108;	// プラス合成回数
	const TYPE_DAILY_CARD_SKILL_UP		= 109;	// スキルアップ合成回数
	const TYPE_DAILY_STAMINA_PRESENT	= 110;	// スタミナプレゼント回数
	
	
	// ID、ユーザーID、各ミッション判定用カウント
	protected static $columns = array(
		'id',
		'user_id',
		'clear_normal',
		'clear_normal_daily',
		'clear_special',
		'clear_special_daily',
		'gacha_friend',
		'gacha_friend_daily',
		'gacha_gold',
		'gacha_gold_daily',
		'card_composite',
		'card_composite_daily',
		'card_evolve',
		'card_evolve_daily',
		'card_create',
		'card_create_daily',
		'card_plus',
		'card_plus_daily',
		'card_skill_up',
		'card_skill_up_daily',
		'stamina_present',
		'stamina_present_daily',
		'daily_reset_at',
	);

	/**
	 * 日替わりでカウントするものだけリセットする
	 */
	public function resetDailyCount() {
		// デイリー系のカラムだけリセット
		$this->clear_normal_daily = 0;
		$this->clear_special_daily = 0;
		$this->gacha_friend_daily = 0;
		$this->gacha_gold_daily = 0;
		$this->card_composite_daily = 0;
		$this->card_evolve_daily = 0;
		$this->card_create_daily = 0;
		$this->card_plus_daily = 0;
		$this->card_skill_up_daily = 0;
		$this->stamina_present_daily = 0;
	}
	

	public function addCount($type, $add = 1)
	{
		switch ($type) {
			case UserCount::TYPE_CLEAR_NORMAL:
				$this->clear_normal += $add;
				break;
			case UserCount::TYPE_CLEAR_SPECIAL:
				$this->clear_special += $add;
				break;
			case UserCount::TYPE_GACHA_FRIEND:
				$this->gacha_friend += $add;
				break;
			case UserCount::TYPE_GACHA_GOLD:
				$this->gacha_gold += $add;
				break;
			case UserCount::TYPE_CARD_COMPOSITE:
				$this->card_composite += $add;
				break;
			case UserCount::TYPE_CARD_EVOLVE:
				$this->card_evolve += $add;
				break;
			case UserCount::TYPE_CARD_CREATE:
				$this->card_create += $add;
				break;
			case UserCount::TYPE_CARD_PLUS:
				$this->card_plus += $add;
				break;
			case UserCount::TYPE_CARD_SKILL_UP:
				$this->card_skill_up += $add;
				break;
			case UserCount::TYPE_STAMINA_PRESENT:
				$this->stamina_present += $add;
				break;
				
			case UserCount::TYPE_DAILY_CLEAR_NORMAL:
				$this->clear_normal_daily += $add;
				break;
			case UserCount::TYPE_DAILY_CLEAR_SPECIAL:
				$this->clear_special_daily += $add;
				break;
			case UserCount::TYPE_DAILY_GACHA_FRIEND:
				$this->gacha_friend_daily += $add;
				break;
			case UserCount::TYPE_DAILY_GACHA_GOLD:
				$this->gacha_gold_daily += $add;
				break;
			case UserCount::TYPE_DAILY_CARD_COMPOSITE:
				$this->card_composite_daily += $add;
				break;
			case UserCount::TYPE_DAILY_CARD_EVOLVE:
				$this->card_evolve_daily += $add;
				break;
			case UserCount::TYPE_DAILY_CARD_CREATE:
				$this->card_create_daily += $add;
				break;
			case UserCount::TYPE_DAILY_CARD_PLUS:
				$this->card_plus_daily += $add;
				break;
			case UserCount::TYPE_DAILY_CARD_SKILL_UP:
				$this->card_skill_up_daily += $add;
				break;
			case UserCount::TYPE_DAILY_STAMINA_PRESENT:
				$this->stamina_present_daily += $add;
				break;
				
			default:
				break;
		}
	}
	
	/**
	 * 指定のユーザーのデータを取得する
	 * ユーザーデータが無い場合は作成される
	 * 
	 * 日付変更タイミングでデイリーミッションのチェックをされた場合を考慮して
	 * 時間判定でカウントリセットしたデータを返す
	 */
	public static function getByUserId($user_id, $check_time = null, $pdo = null)
	{
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		
		if($check_time == null) {
			$check_time = time();
		}
		
		$user_count = UserCount::findBy(array("user_id"=>$user_id), $pdo);
		if (!$user_count) {
			// 存在しなければ作成した後、取得し直し
			$user_count = new UserCount();
			$user_count->user_id = $user_id;
			$user_count->create($pdo);
			
			$user_count = UserCount::findBy(array("user_id"=>$user_id), $pdo);
		}
		
		$daily_reset_at = BaseModel::strToTime($user_count->daily_reset_at);
		// リセット時間チェック（日付が違う場合はリセット）
		if (!BaseModel::isSameDay_AM4($check_time, $daily_reset_at)) {
			$user_count->resetDailyCount();
			$user_count->daily_reset_at = BaseModel::timeToStr($check_time);
		}

		return $user_count;
	}

	public static function incrUserGachaDailyPlayCount($user_id, $gacha_id, $is_single = true)
	{
		$key = RedisCacheKey::getGachaDailyPlayCounter($user_id, $gacha_id, $is_single);
		$redis = Env::getRedis(Env::REDIS_USER);

		$expired_at = BaseModel::getNextDayAM4Timestamp();
		$cnt = $redis->incr($key);
		$redis->expireAt($key, $expired_at);

		return $cnt;
	}

	public static function incrUserDungeonDailyChallengeCount($user_id, $dungeon_floor_id, $cleared_at)
	{
		$key = RedisCacheKey::getDungeonDailyChallengeCounter($user_id, $dungeon_floor_id);
		$redis = Env::getRedis(Env::REDIS_USER);

		$expired_at = BaseModel::getNextDayAM4Timestamp($cleared_at);
		$cnt = $redis->incr($key);
		$redis->expireAt($key, $expired_at);

		return $cnt;
	}
}
