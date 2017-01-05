<?php
/**
 * #PADC#
 * ミッション.
 */

class Mission extends BaseMasterModel {
	const TABLE_NAME = "padc_missions";
	const VER_KEY_GROUP = "padcmission";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// ミッションタブカテゴリ
	const TAB_CATEGORY_NORMAL      = 0;	// 通常
	const TAB_CATEGORY_SPECIAL     = 1;	// 特殊
	
	// ミッション条件種類
	const CONDITION_TYPE_TOTAL_LOGIN     = 1;	// 通算ログイン数
	const CONDITION_TYPE_USER_RANK       = 2;	// 到達ランク
	const CONDITION_TYPE_DUNGEON_CLEAR   = 3;	// 特定ダンジョンクリア
	//const CONDITION_TYPE_FLOOR_CLEAR      = 4;	// 特定フロアクリア
	const CONDITION_TYPE_LOGIN_STREAK    = 5;	// 連続ログイン数（デイリーミッションへ移行するのでもう使用しない）
	const CONDITION_TYPE_BOOK_COUNT      = 6;	// 図鑑登録数
	const CONDITION_TYPE_CARD_EVOLVE     = 7;	// 進化合成回数
	const CONDITION_TYPE_CARD_COMPOSITE  = 8;	// 強化合成回数
	// デイリー系
	const CONDITION_TYPE_DAILY_FLOOR_CLEAR				= 101;	// 特定フロアクリア
	//const CONDITION_TYPE_DAILY_FLOOR_CLEAR_RANKING	= 102;	// 特定フロアクリア（ランキングダンジョン）
	const CONDITION_TYPE_DAILY_CLEAR_COUNT_NORMAL		= 103;	// ダンジョンフロアクリア回数（ノーマルダンジョン）
	const CONDITION_TYPE_DAILY_CLEAR_COUNT_SPECIAL		= 104;	// ダンジョンフロアクリア回数（スペシャルダンジョン）
	const CONDITION_TYPE_DAILY_GACHA_FRIEND			    = 105;	// ガチャ回数（友情ポイントガチャ）
	const CONDITION_TYPE_DAILY_GACHA_GOLD				= 106;	// ガチャ回数（魔法石ガチャ）
	const CONDITION_TYPE_DAILY_CARD_COMPOSITE			= 107;	// モンスターの強化回数
	const CONDITION_TYPE_DAILY_CARD_EVOLVE				= 108;	// モンスターの進化回数
	//const CONDITION_TYPE_DAILY_CARD_CREATE			= 109;	// モンスターの生成回数
	//const CONDITION_TYPE_DAILY_CARD_PLUS				= 110;	// モンスターのプラス強化回数
	//const CONDITION_TYPE_DAILY_CARD_SKILL_UP			= 111;	// モンスターのスキルレベルアップ回数
	//const CONDITION_TYPE_DAILY_STAMINA_PRESENT		= 112;	// スタミナプレゼント回数
	//const CONDITION_TYPE_DAILY_ALL_CLEAR				= 113;	// 全デイリーミッション達成
	const CONDITION_TYPE_DAILY_LOGIN_STREAK			    = 114;	// 連続ログイン数

	// ミッション種類
	const MISSION_TYPE_NORMAL           = 0;	// ノーマル
	const MISSION_TYPE_LIMIT            = 1;	// 緊急
	const MISSION_TYPE_DAILY            = 2;	// デイリーミッション
	const MISSION_TYPE_SPECIAL          = 3;	// 特殊（チュートリアル）
	
	// ミッション条件キー文字列
	const CONDITION_KEY_TOTAL_LOGIN     = 'login';	// 通算ログイン数
	const CONDITION_KEY_USER_RANK       = 'rank';	// 到達ランク
	const CONDITION_KEY_DUNGEON_CLEAR   = 'dungeon';	// 特定ダンジョンクリア
	const CONDITION_KEY_FLOOR_CLEAR     = 'floor';	// 特定フロアクリア
	const CONDITION_KEY_LOGIN_STREAK    = 'login_streak';	// 連続ログイン数
	const CONDITION_KEY_LOGIN_PERIOD    = 'login_period';	// 連続ログイン期間ID
	const CONDITION_KEY_BOOK_COUNT      = 'book';	// 図鑑登録数
	const CONDITION_KEY_CARD_EVOLVE     = 'card_evolve';	// 進化合成回数
	const CONDITION_KEY_CARD_COMPOSITE  = 'card_composite';	// 強化合成回数
	
	// ミッション条件キー文字列（デイリーミッション）
	const CONDITION_KEY_DAILY_FLOOR_CLEAR			= 'daily_floor';	// 特定フロアクリア
	//const CONDITION_KEY_FLOOR_CLEAR_RANKING		= 'daily_floor_ranking';	// 特定フロアクリア（ランキングダンジョン）
	const CONDITION_KEY_DAILY_CLEAR_COUNT_NORMAL	= 'daily_clear_count_normal';	// フロアクリア回数（ノーマルダンジョン）
	const CONDITION_KEY_DAILY_CLEAR_COUNT_SPECIAL	= 'daily_clear_count_special';	// フロアクリア回数（スペシャルダンジョン）
	const CONDITION_KEY_DAILY_GACHA_FRIEND			= 'daily_gacha_friend';	// ガチャ回数（友情ポイントガチャ）
	const CONDITION_KEY_DAILY_GACHA_GOLD			= 'daily_gacha_gold';	// ガチャ回数（魔法石ガチャ）
	const CONDITION_KEY_DAILY_CARD_COMPOSITE		= 'daily_card_composite';	// モンスターの強化回数
	const CONDITION_KEY_DAILY_CARD_EVOLVE			= 'daily_card_evolve';	// モンスターの進化回数
	//const CONDITION_KEY_DAILY_CARD_CREATE			= 'daily_card_create';	// モンスターの生成回数
	//const CONDITION_KEY_DAILY_CARD_PLUS			= 'daily_card_plus';	// モンスターのプラス強化回数
	//const CONDITION_KEY_DAILY_CARD_SKILL_UP		= 'daily_card_skill_up';	// モンスターのスキルレベルアップ回数
	//const CONDITION_KEY_DAILY_STAMINA_PRESENT		= 'daily_stamina_present';	// スタミナプレゼント回数
	//const CONDITION_KEY_DAILY_ALL_CLEAR			= 'daily_all_clear';	// 全デイリーミッション達成
	const CONDITION_KEY_DAILY_LOGIN_STREAK			= 'daily_login_streak';	// 連続ログイン日数
	const CONDITION_KEY_DAILY_LOGIN_PERIOD			= 'daily_login_period';	// 連続ログイン期間ID
	
	
	// 遷移先ID
	const TRANSITION_ID_NONE                    = 0;	// 遷移先なし
	const TRANSITION_ID_SELECT_FLOOR_1          = 1;	// フロア選択（ダンジョンID）
	const TRANSITION_ID_SELECT_FLOOR_2          = 2;	// フロア選択（フロアID）
	const TRANSITION_ID_SELECT_BASE_CARD        = 3;	// ベースカード選択
	const TRANSITION_ID_SELECT_NORMAL_DUNGEON   = 4;	// ノーマルダンジョン選択
	const TRANSITION_ID_SELECT_SPECIAL_DUNGEON  = 5;	// スペシャルダンジョン選択
	const TRANSITION_ID_GACHA                   = 6;	// ガチャ
	
	// 有効時間帯に対する状態
	const TIME_ZONE_NG                 = 0;	// 時間外（厳密にいえば時間前）
	const TIME_ZONE_OK                 = 1;	// 有効時間内（有効時間帯設定なし）
	const TIME_ZONE_OVER               = 2;	// 時間帯オーバー
	
	

	// ID、ミッション種類、クリア条件、ミッション名、ミッション説明、報酬ID、報酬の数、報酬欠片ID、解放前提ミッションID、解放条件、ソートID、ミッション有効期間、削除フラグ
	// ※ prev_id は id より若い番号であること（クリア判定の都合上）
	protected static $columns = array(
		'id',
		'tab_category',
		'condition_type',
		'mission_type',
		'group_id',
		'transition_id',
		'clear_condition',
		'name',
		'description',
		'reward_text',
		'reward_img',
		'bonus_id',
		'amount',
		'piece_id',
		'prev_id',
		'open_condition',
		'sort_id',
		'time_zone_start',
		'time_zone_end',
		'begin_at',
		'finish_at',
		'del_flg',
	);
	
	// クリア条件、開放条件の配列
	public $_clear_conditions = null;
	public $_open_conditions = null;
	

	/**
	 * 指定された種類のミッションデータをMemcacheから取得する.
	 * もしMemcacheに登録されていなければ、データベースから取得してMemcacheにセットする.
	 *
	 * @return モデルオブジェクトの配列.
	 */
	public static function getByConditionTypeForMemcache($condition_type) {
		if (!is_array($condition_type)) {
			$condition_type = array($condition_type);
		}
		$key = static::getByConditionTypeKey($condition_type);
		// Memcahced に接続して、値を取得する.
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if(FALSE === $value) {
			// Memcached に値がセットされていなければ、データベースから取得して Memcached にセットする.
			$pdo = Env::getDbConnectionForShare();
			// SQLの組み立て.
			$values = array();
			foreach($condition_type as $v) {
				$values[] = $v;
			}
			$sql = "SELECT * FROM " . static::TABLE_NAME;
			$sql .= " WHERE condition_type IN (" . str_repeat('?,', count($condition_type) - 1) . "?)";
			$stmt = $pdo->prepare($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
			$stmt->execute($values);
			$value = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

			if(Env::ENV !== "production"){
				global $logger;
				$logger->log(("sql_query: ".$sql."; bind: ".join(",",$values)), Zend_Log::DEBUG);
			}

			if($value) {
				$redis = Env::getRedisForShare();
				$redis->set($key, $value, static::MEMCACHED_EXPIRE);
			}
		}
		return $value;
	}

	/**
	 * キャッシュに条件にあう全レコードをセットする際のキーを返す.
	 */
	protected static function getByConditionTypeKey($condition_type) {
		$cond = "condition_type";
		if (is_array($condition_type)) {
			foreach($condition_type as $v) {
				$cond .= "_" . (string) $v;
			}
		}
		else {
			$cond .= "_" . (string) $condition_type;
		}
		return Env::MEMCACHE_PREFIX . get_called_class() . self::getVerKey() . '_allrecord_' . $cond;
	}

	/**
	 * 連続ログインミッションを取得する際のキーを返す.
	 */
	protected static function getLoginStreakMissionKey($day, $period) {
		return Env::MEMCACHE_PREFIX . get_called_class() . self::getVerKey() . '_login_streak_mission_' . $period . '_' . $day;
	}

	/**
	 * クリア条件を配列にして返す
	 */
	public function getClearConditions() {
		return self::parseConditions($this->clear_condition);
	}

	/**
	 * 解放条件を配列にして返す
	 */
	public function getOpenConditions() {
		return self::parseConditions($this->open_condition);
	}

	/**
	 * json文字列から条件となるキーの内容だけ抽出して配列にして返す
	 */
	private function parseConditions($str_json) {
		$conditions = array();
		if ($str_json) {
			$jdata = json_decode($str_json);
			$condition_key = array(
				Mission::CONDITION_KEY_TOTAL_LOGIN,
				Mission::CONDITION_KEY_USER_RANK,
				Mission::CONDITION_KEY_DUNGEON_CLEAR,
				Mission::CONDITION_KEY_FLOOR_CLEAR,
				//Mission::CONDITION_KEY_LOGIN_STREAK,
				//Mission::CONDITION_KEY_LOGIN_PERIOD,
				Mission::CONDITION_KEY_BOOK_COUNT,
				Mission::CONDITION_KEY_CARD_EVOLVE,
				Mission::CONDITION_KEY_CARD_COMPOSITE,
				
				Mission::CONDITION_KEY_DAILY_FLOOR_CLEAR,
				//Mission::CONDITION_KEY_FLOOR_CLEAR_RANKING,
				Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_NORMAL,
				Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_SPECIAL,
				Mission::CONDITION_KEY_DAILY_GACHA_FRIEND,
				Mission::CONDITION_KEY_DAILY_GACHA_GOLD,
				Mission::CONDITION_KEY_DAILY_CARD_COMPOSITE,
				Mission::CONDITION_KEY_DAILY_CARD_EVOLVE,
				//Mission::CONDITION_KEY_DAILY_CARD_CREATE,
				//Mission::CONDITION_KEY_DAILY_CARD_PLUS,
				//Mission::CONDITION_KEY_DAILY_CARD_SKILL_UP,
				//Mission::CONDITION_KEY_DAILY_STAMINA_PRESENT,
				//Mission::CONDITION_KEY_DAILY_ALL_CLEAR,
				Mission::CONDITION_KEY_DAILY_LOGIN_STREAK,
				Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD,
			);
			foreach ($condition_key as $key) {
				if (isset($jdata->$key)) {
					$conditions[$key] = $jdata->$key;
				}
			}
		}
		return $conditions;
	}

	/**
	 * 有効時間内かを返す
	 * 削除フラグのチェックも行う
	 */
	public function isEnabled($time){
		// 削除フラグが立っていたら無効
		if ($this->del_flg) {
			return false;
		}
		
		// 有効期間が設定されていない場合は無制限
		$begin_at = static::strToTime($this->begin_at);
		$finish_at = static::strToTime($this->finish_at);
		if ($begin_at > 0 && $finish_at > 0) {
			if($begin_at <= $time && $time <= $finish_at){
				return true;
			}
			return false;
		}
		return true;
	}

	/**
	 * 有効時間帯の設定がされているかどうかを返します
	 */
	public function isSetTimeZone(){
		// デイリーミッションであり、かつ受け取り可能な時間帯が設定されている場合 trueを返す
		if ($this->mission_type == Mission::MISSION_TYPE_DAILY && $this->time_zone_start != null && $this->time_zone_end != null) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * 有効時間帯に対してどういう状態かを返す
	 * ※時間の扱いについて例えば12:00の場合は1200として扱います
	 */
	public function getCheckTimeZone($check_time){
		// 有効時間帯の設定が無ければ有効とみなす
		if (!$this->isSetTimeZone()) {
			return Mission::TIME_ZONE_OK;
		}
		
		// 時間と分のみを扱う
		$check_time = Date("Hi", $check_time);
		
		// AM4時で日替わり判定が入るのでそれを考慮して値をずらす
		$time_zone_start = ($this->time_zone_start >= 400) ? $this->time_zone_start : $this->time_zone_start + 2400;
		$time_zone_end = ($this->time_zone_end >= 400) ? $this->time_zone_end : $this->time_zone_end + 2400;
		
		if ($time_zone_start <= $check_time && $check_time <= $time_zone_end) {
			return Mission::TIME_ZONE_OK;
		}
		else {
			if ($time_zone_end < $check_time) {
				return Mission::TIME_ZONE_OVER;
			}
			else {
				return Mission::TIME_ZONE_NG;
			}
		}
	}

	/**
	 * 連続ログインのミッションデータを返す
	 * 複数あることは想定してないです
	 * 
	 * INFO:旧処理なのでもう利用しません
	 */
	public static function getLoginStreakMisson($day, $period) {
		$now = time();
		$key = static::getLoginStreakMissionKey($day, $period);
		// Memcahced に接続して、値を取得する.
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if(FALSE === $value || !$value->isEnabled($now)) {
			$value = FALSE;

			$login_streak_missions = self::getByConditionTypeForMemcache(self::CONDITION_TYPE_LOGIN_STREAK);
			foreach ($login_streak_missions as $mission) {
				if (!$mission->isEnabled($now)) {
					continue;
				}
				$conditions = $mission->getClearConditions();
				if (array_key_exists(Mission::CONDITION_KEY_LOGIN_STREAK, $conditions) && $conditions[Mission::CONDITION_KEY_LOGIN_STREAK] == $day) {
					if (array_key_exists(Mission::CONDITION_KEY_LOGIN_PERIOD, $conditions)) {
						if ($conditions[Mission::CONDITION_KEY_LOGIN_PERIOD] == $period) {
							$value = $mission;
							break;
						}
					}
					else if ($period == User::DEFAULT_LOGIN_PERIOD_ID) {
						$value = $mission;
						break;
					}
				}
			}

			$redis = Env::getRedisForShare();
			if($value) {
				$redis->set($key, $value, static::MEMCACHED_EXPIRE);
			}
			else {
				$redis->delete($key);
			}
		}
		return $value;
	}

	/**
	 * 次の日の連続ログインのミッションデータを返す
	 */
	public static function getLoginStreakMissonsNextDay($user_login_str, $user_period) {
		
		// 24時間後の時刻
		$next_day = time() + 86400;
		
		$check_day = 0;
		$check_period = $user_period;
		$max_login_streack = User::MAX_LOGIN_STREAK;
		// 次の日のログイン期間データを取得
		$nday_period = LoginPeriod::getLoginPeriod($next_day);
		if ($nday_period) {
			$max_login_streack = $nday_period->max_login_streak;
			if ($nday_period->id != $user_period) {
				$check_period = $nday_period->id;
				$user_login_str = 0;
			}
		}
		else {
			if(User::DEFAULT_LOGIN_PERIOD_ID != $user_period)
			{
				$check_period = User::DEFAULT_LOGIN_PERIOD_ID;
				$user_login_str = 0;
			}
		}
		$check_day = min($user_login_str+1, $max_login_streack);
		
		
		$key = static::getLoginStreakMissionKey($check_day, $check_period);
		// Memcahced に接続して、値を取得する.
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if(FALSE === $value) {
			$value = array();
		
			$login_streak_missions = self::getByConditionTypeForMemcache(self::CONDITION_TYPE_DAILY_LOGIN_STREAK);
			foreach ($login_streak_missions as $mission) {
				if (!$mission->isEnabled($next_day)) {
					continue;
				}
				$conditions = $mission->getClearConditions();
				if (array_key_exists(Mission::CONDITION_KEY_DAILY_LOGIN_STREAK, $conditions) && $conditions[Mission::CONDITION_KEY_DAILY_LOGIN_STREAK] == $check_day) {
					$login_periods = array(	User::DEFAULT_LOGIN_PERIOD_ID);
					if (array_key_exists(Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD, $conditions)) {
						if (is_array($conditions[Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD])) {
							$login_periods = $conditions[Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD];
						}
						else {
							$login_periods = array(	$conditions[Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD]);
						}
					}
					
					if (in_array($check_period, $login_periods)) {
						$value[] = $mission;
					}
				}
			}
		
			$redis = Env::getRedisForShare();
			$redis->set($key, $value, static::MEMCACHED_EXPIRE);
		}
		return array($value, $user_login_str, $check_day);
	}
	
	/**
	 * ダンジョンフロアクリア情報が必要なミッションキー配列を返す
	 */
	public static function getDungeonFloorClearKeys() {
		$array = array(
			Mission::CONDITION_KEY_DUNGEON_CLEAR,
			Mission::CONDITION_KEY_FLOOR_CLEAR,
			Mission::CONDITION_KEY_DAILY_FLOOR_CLEAR,
			//Misson::CONDITION_KEY_FLOOR_CLEAR_RANKING,
		);
		return $array;
	}
	/**
	 * ユーザーカウント情報が必要なミッションキー配列を返す
	 */
	public static function getUserCountKeys() {
		$array = array(
			Mission::CONDITION_KEY_CARD_EVOLVE,
			Mission::CONDITION_KEY_CARD_COMPOSITE,
			Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_NORMAL,
			Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_SPECIAL,
			Mission::CONDITION_KEY_DAILY_GACHA_FRIEND,
			Mission::CONDITION_KEY_DAILY_GACHA_GOLD,
			Mission::CONDITION_KEY_DAILY_CARD_COMPOSITE,
			Mission::CONDITION_KEY_DAILY_CARD_EVOLVE,
			//Misson::CONDITION_KEY_DAILY_CARD_CREATE,
			//Misson::CONDITION_KEY_DAILY_CARD_PLUS,
			//Misson::CONDITION_KEY_DAILY_CARD_SKILL_UP,
			//Misson::CONDITION_KEY_DAILY_STAMINA_PRESENT,
		);
		return $array;
	}
}
