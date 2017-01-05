<?php
/**
 * ユーザモデル.
 */
class User extends BaseUserModel {
	const TABLE_NAME = "users";
	const MEMCACHED_EXPIRE = 864000; // 10日間.

	const MAX_LOGIN_STREAK = 7;
	// #PADC# ----------begin----------
	const INIT_STAMINA			= 26;// #PADC_DY# 20→10→30→26→20
	const INIT_STAMINA_MAX		= 26;// #PADC_DY# 20→10→30→26→20
	const INIT_COST_MAX			= 20;
	const INIT_FRIEND_MAX		= 500;// #PADC# 20→500→50→500
	const INIT_CARD_MAX			= 20;
	const INIT_DECKS_MAX		= 7;// #PADC# 6→7
	const INIT_COIN				= 20000;// #PADC# 初期コイン
	const INIT_COMPOSITE_TUTORIAL_PIECE_ID	= 1;// #PADC# チュートリアルの強化で使用する欠片ID
	const INIT_COMPOSITE_TUTORIAL_PIECE_NUM	= 3;// #PADC# チュートリアルの強化で使用する欠片の初期所持数
	const INIT_RECEIVE_PRESENT		= 30;//#PADC# initial receive present count　10→30 
	const INIT_USER_BOOK_CARD_ID	= 1;// #PADC# 図鑑にのみ登録しておくモンスターID
	const INIT_LEADER_CARD_LEVEL	= 10;// #PADC# 初期リーダーカードのレベル
	const STAMINA_STOCK_MAX			= 999;// 体力上限　10009→9999
	const DECK_CARD_MAX				= 5;// #PADC# デッキにセットできるカードの上限
	const RANKING_POINT_MAX			= 999999999;
	const FRIEND_MAX_LIMIT			= 500;// フレンド枠数の上限
	const DEFAULT_LOGIN_PERIOD_ID	= 0;// デフォルトのログイン期間ID
	const COIN_MAX					= 2000000000;
	// #PADC_DY# 扫荡券最大值调整为99999999
	const ROUND_TICKET_MAX			= 99999999;// 周回チケットの上限
	const NAME_LENGTH_MAX			= 10;//名前上限
	// #PADC# ----------end----------

	//#PADC#
	//const AddGoldLogType = 3;

	const STATUS_NORMAL = 0;
	const STATUS_DEL = 1;
	const STATUS_BAN = 2;
	const STATUS_FRZ = 3;
	const STATUS_BAN_GS = 4;
	const STATUS_BAN_CR = 5;
	const STATUS_BAN_LTD = 6;

	const TYPE_IOS = 0;
	const TYPE_ANDROID = 1;
	const TYPE_AMAZON = 2;

	const AREA_JP = 0; // 日本
	const AREA_NA = 1; // 北米
	const AREA_KR = 2; // 韓国
	const AREA_EU = 3; // 欧州
	const AREA_HT = 4; // 香港台湾

	const STAMINA_RECOVER_INTERVAL_VER710 = 600; // recover 1 stamina per 10min(～Version7.1.*)
	const STAMINA_RECOVER_INTERVAL = 300; // recover 1 stamina per 5min

	// 友情ポイントの上限
	const MAX_FRIEND_POINT = 20000;
	const MAX_FRIEND_POINT_BEFORE740 = 10000;


	const W_INIT_STAMINA = 7;
	const W_STAMINA_MAX = 7;
	const W_STAMINA_RECOVER_INTERVAL = 900; // recover 1 stamina per 15min

	const W_INIT_ITEM_CAMP_1 = 6;
	const W_INIT_ITEM_CAMP_2 = 7;
	const W_INIT_ITEM_CAMP_3 = 8;

	const W_MAX_MEDAL = 999999;

	const MODE_NORMAL = 0;
	const MODE_W = 1;

	const PFLG_MODE_NORMAL = 0;
	const PFLG_MODE_ALL = 1;
	const PFLG_MODE_W = 2;

	// #PADC# ----------begin----------
	// 処罰状態
	const PUNISH_BAN = 0;
	const PUNISH_PLAY_BAN_NORMAL = 1;
	const PUNISH_PLAY_BAN_SPECIAL = 2;
	const PUNISH_PLAY_BAN_RANKING = 3;
	const PUNISH_PLAY_BAN_BUYDUNG = 4;
	const PUNISH_ZEROPROFIT = 5;
	const PUNISH_SLIENCE = 6;
	
	const QQ_ACCOUNT_NORMAL = 0;
	const QQ_ACCOUNT_VIP = 1;
	const QQ_ACCOUNT_SVIP = 2;
	
	const QQ_VIP_PURCHASE_BONUS = 1;
	const QQ_VIP_NOVICE_BONUS = 2;
	const QQ_SVIP_PURCHASE_BONUS = 4;
	const QQ_SVIP_NOVICE_BONUS = 8;
	const QQ_VIP_PURCHASE = 16;
	const QQ_SVIP_PURCHASE = 32;
	
	const QQ_VIP_BONUS_UNAVALIBLE = 0;
	const QQ_VIP_BONUS_AVALIBLE = 1;
	const QQ_VIP_BONUS_RECEIVED = 2;
	
	const NOT_GAME_CENTER = 0;
	const QQ_GAME_CENTER = 1;
	const WECHAT_GAME_CENTER = 2;
	const QQ_GAME_CENTER_YET = 3; 		   //曾经QQ游戏中心登录
	const WECHAT_GAME_CENTER_YET = 4;     //曾经WX游戏中心登录
	// #PADC# ----------end----------

	protected static $columns = array(
		'id',
		'name',
		'camp',
		'lv',
		'exp',
		'stamina',
		'stamina_max',
		'stamina_recover_time',
		'gold',
		'pgold',
		'coin',
		'fripnt',
		'ranking_point',
		'pbflg',
		'card_max',
		'li_last',
		'li_str',
		'li_max',
		'li_cnt',
		'li_days',
		// #PADC# ----------begin----------
		'li_mdays',
		'li_mission_date', // PADC版追加 連続ログインミッション報酬受け取り日時
		'li_period',
		// #PADC# ----------end----------
		'cost_max',
		'fr_cnt', // フレンド申請メール数
		'pback_cnt',
		'friend_max',
		'fricnt', // フレンド数
		'lc',
		// #PADC#
		'lc_ps', // 玩家leader的觉醒技能信息
		'ldeck', // PADC版追加 リーダーデッキ情報
		'us',
		'w_pflg',
		'w_stamina',
		'w_stamina_recover_time',
		'medal',
		'eq1_id',
		'eq1_lv',
		'eq2_id',
		'eq2_lv',
		'eq3_id',
		'eq3_lv',
		'w_pgetflg',
		'accessed_at',
		'accessed_on',
		'dev',
		'osv',
		'device_type',
		'area_id',
		'del_status',
		// #PADC# ----------begin----------
		'clear_dungeon_cnt', // PADC版追加 クリアダンジョン数
		'last_clear_dungeon_id', // PADC版追加 直近でクリアしたダンジョンID
		'vip_lv',
		'tp_gold',
		'round',
		'cont',
		'tss_end', //月額終わり時間
		'book_cnt', // PADC版追加 図鑑登録数
		'last_vip_weekly',//最後VIP weeklyボーナスを取得する時間
		'last_subs_daily',//最後月額課金デーリーボーナスを取得する時間
		'present_receive_max',//control how many stamina present user can receive
		'last_clear_normal_dungeon_id',// PADC版追加 直近でクリアしたノーマルダンジョンID
		'last_clear_sp_dungeon_id',// PADC版追加 直近でクリアしたスペシャルダンジョンID
		'punish_status',//処罰状態
		'reserve_gold',//IDIPより、魔法石変更値
		'last_lv_up',//前回レベルアップ時間
		'login_channel',//アプリからのログインチャンネル、TLOG用
		'device_id',//アプリからのデバイスID、TLOG用
		'qq_vip',//QQ会員
		'qq_vip_gift',//QQ会員ギフトバック受け取り状態
		'qq_vip_expire',//QQ会員終了時間
		'qq_svip_expire',//QQ超級会員終了時間
		'lqvdb_days',//last qq vip daily bonus days
		'game_center',
		// #PADC# ----------end----------
		// #PADC_DY# ----------begin----------
		'last_game_center', // 上一次领取游戏中心奖励的时间
		// #PADC_DY# ----------end----------
		'created_on',
        // #PADC_DY# ----------begin----------
        'first_charge_gift_received', // 是否领取首充礼包
        'first_charge_record', // 首充双倍记录
        'share_reward_at', // 上一次分享时间
		'tc_gold', // 累计魔法石消耗
		'last_p6_at', // 上一次充值大于6元时间
		'count_p6', // 连续充值大于6元天数
		'exchange_point', // 兑换点
		'exchange_list', // 上次兑换商品id列表
		'exchange_refresh_time', // 上次兑换列表自动刷新时间
		'exchange_record', // 已兑换id列表
		'tp_gold_period', // 活动期间购买的魔法石数量
		'tp_period_access_at',// tp_gold_period数据处理时间
		'tc_gold_period', // 活动期间消费的魔法石数量
		'tc_period_access_at',// tc_gold_period数据处理时间
		'tss_forever_end',// 永久月卡结束时间
		'last_forever_subs_daily', // 上次领取永久月卡时间
		'tss_forever_cnt', // 永久月卡已经领取次数 -1为未开通
        // #PADC_DY# ----------end----------
		'created_at',
		'updated_at',
	);

	/**
	 * 経験値の付与
	 */
	public function addExp($exp){
		$this->exp += $exp;
	}

	/**
	 * ゲーム内通貨付与
	 */
	public function addCoin($coin){
		$this->coin += $coin;
		$this->coin = min($this->coin,self::COIN_MAX);
	}

	/**
	 * (無料)課金通貨付与及び(無料/有料)課金通貨消費
	 */
	public function addGold($gold, $pdo = null){
		//#PADC# 有料課金通貨消費を記録
		$paid = array('gold' => 0, 'pgold' => 0);
		if($gold<0){//減算
			if($this->gold>=abs($gold)){//無料魔法石が残っている場合は先に消費
				$paid['gold'] = -$gold;
				$this->gold += $gold;
			}else{//無料魔法石だけで足りない場合は有料魔法石からも消費
				//#PADC#
				$paid['gold'] = $this->gold;
				$paid['pgold'] = -$gold - $this->gold;
				$this->pgold -= $paid['pgold'];
				$this->gold=0;
			}

			// #PADC_DY# 累计消耗 ----------begin----------
			$this->tc_gold += abs($gold);
			$activity = Activity::getByType(Activity::ACTIVITY_TYPE_TOTAL_CONSUM);
			if($activity) {
				$user_activity = UserActivity::findBy(array(
					'user_id' => $this->id,
					'activity_id' => $activity->id
				), $pdo);
				$this->tc_gold_period += abs($gold);
				$this->tc_period_access_at = static::timeToStr(time());
				if($activity->checkCondition($this->tc_gold_period, $this->vip_lv)
					&& (!$user_activity || !in_array($user_activity->status, array(UserActivity::STATE_CLEAR, UserActivity::STATE_RECEIVED)))) {
					UserActivity::updateStatus($this->id, $activity->id, UserActivity::STATE_CLEAR, $pdo);
				}
			}
			// #PADC_DY# -----------end-----------

			// #PADC_DY# 更新限时积分排行榜
			UserPointRanking::savePointToCurrentRanking($this->id, abs($gold), Activity::ACTIVITY_TYPE_CONSUM_POINT_RANKING, $pdo);
		}else{//加算
			$this->gold += $gold;
		}
		//#PADC#
		return $paid;
	}

	/**
	 * (有料)課金通貨付与
	 */
	public function addPGold($pgold){
		$this->pgold += $pgold;
	}

	/**
	 * 友情ポイント付与
	 */
	public function addFripnt($fripnt){
		$this->fripnt += $fripnt;

		// #PADC# ----------begin----------
		// 友情ポイントの上限チェック（getRevでの判定を削除）
		if($this->fripnt > User::MAX_FRIEND_POINT)
		{
			$this->fripnt = User::MAX_FRIEND_POINT;
		}
		// #PADC# ----------end----------
	}

	public function addRankingPoint($point)
	{
		$this->ranking_point = min($this->ranking_point + $point, User::RANKING_POINT_MAX);
	}

	/**
	 * add continue times
	 */
	public function setContinue($continue){
		$this->cont = $continue;
	}

	/**
	 * add round times
	 */
	public function addRound($round){
		$this->round += $round;
		$this->round = min($this->round,self::ROUND_TICKET_MAX);
	}
	/**
	 * メダル付与
	 */
	public function addMedal($medal){
		$this->medal += $medal;
		if($this->medal > self::W_MAX_MEDAL){
			$this->medal = self::W_MAX_MEDAL;
		}
	}

	/**
	 * #PADC#
	 * 初期カードのID配列を返す．
	 * デバッグ機能で利用できるようにpublicに変更
	 * @return multitype:multitype:number
	 */
	public function getInitCards(){
		// #PADC# ----------begin----------
		// CBT3:初期モンスターの選択はなくなったため全種同じ設定としておく
		$init_cards = array(
			"1" => array(2, 88, 48, 72, 104, 5, 9, 13, 17), // 0:火属性
			"2" => array(2, 88, 48, 72, 104, 5, 9, 13, 17), // 1:水属性
			"3" => array(2, 88, 48, 72, 104, 5, 9, 13, 17), // 2:樹属性
		);
		// #PADC# ----------end----------
		return $init_cards;
	}

	/**
	 * #PADC#
	 * 初期カードの欠片ID配列を返す．
	 * デバッグ機能で利用できるようにpublicに変更
	 * @return multitype:multitype:number
	 */
	public function getInitPieceIds(){
		// #PADC# ----------begin----------
		// CBT3:初期モンスターの選択はなくなったため全種同じ設定としておく
		$init_piece_ids = array(
			"1" => array(1, 37, 17, 29, 45, 2, 3, 4, 5), // 0:火属性
			"2" => array(1, 37, 17, 29, 45, 2, 3, 4, 5), // 1:水属性
			"3" => array(1, 37, 17, 29, 45, 2, 3, 4, 5), // 2:樹属性
		);
		// #PADC# ----------end----------
		return $init_piece_ids;
	}

	/**
	 * ユーザを新規登録します
	 */
	public function signup($userDevice, $name, $camp, $w_mode){
		global $logger;

		try{
			$pdo_share = Env::getDbConnectionForShare();
			$pdo_share->beginTransaction();
			// #PADC# -----begin-----
			// userdbIdの発行方法をuserIDの末尾で振り分ける形に変更
			$userDevice->create($pdo_share);
			$user_id = $userDevice->id;
			$dbid = Env::assignUserDbId($user_id);
			$userDevice->dbid = $dbid;
			$userDevice->update($pdo_share);
			// #PADC# -----end-----
			$redis = Env::getRedisForUser();
			$key = CacheKey::getDbIdFromUserDeviceKey($user_id);
			$redis->set($key, $dbid, static::MEMCACHED_EXPIRE);
			$pdo = Env::getDbConnectionForUserWrite($user_id, $dbid);
			$pdo->beginTransaction();
			$this->id = $user_id;
			$this->name = $name;
			$this->camp = $camp;
			$this->stamina = User::INIT_STAMINA;
			$this->stamina_max = User::INIT_STAMINA_MAX;
			// #PADC# コイン初期値調整
			$this->coin = User::INIT_COIN;
			$this->cost_max = User::INIT_COST_MAX;
			$this->friend_max = User::INIT_FRIEND_MAX;
			$this->card_max = User::INIT_CARD_MAX;
			$this->decks_max = User::INIT_DECKS_MAX;
			$current_time = static::timeToStr(time());
			$this->device_type = $userDevice->type;
			$this->accessed_at = $current_time;
			$this->accessed_on = $current_time;
			$this->created_on = $current_time;
			$this->li_str = 0;
			$this->li_cnt = 0;
			$this->li_days = 0;
			# PADC # -----------------begin--------------------
			$this->last_lv_up = $current_time;
			$this->li_mdays = 0;
			$this->vip_lv = 0;
			$this->tp_gold = 0;
			$this->round = 0;
			$this->cont = 0;
			$this->present_receive_max = User::INIT_RECEIVE_PRESENT;
			# PADC # -----------------begin--------------------
			$this->del_status = User::STATUS_NORMAL;
			// #PADC# ----------begin----------
			// CBT4のチュートリアル改修のため初期状態を変更
			$this->clear_dungeon_cnt = 0;
			// 初期モンスター数9体+図鑑のみ登録するモンスター1体
			$this->book_cnt = 10;
			// #PADC# ----------end----------
			// #PADC_DY# 初始化lv数值
			$this->lv = 1;
			if($w_mode == self::MODE_W){
				$this->w_pflg = self::PFLG_MODE_W; // Wでキャラ登録(2).
				$this->w_stamina = User::W_INIT_STAMINA;
				$this->w_stamina_recover_time = static::timeToStr(time());
				$this->medal = 0;
				// 初期アバター設定.
				$this->eq1_id = $this->getWInitAItem($camp);
				$this->eq1_lv = 1;
				$user = &$this;
				WUserAitem::composite($user, $user->eq1_id, $user->eq1_lv, $pdo);
			}else{
				$this->w_pflg = self::PFLG_MODE_NORMAL; // 通常モードでキャラ登録(0).
			}

			// 初期カード設定.
			$init_cards = $this->getInitCards();

			// リーダーカード、サブリーダーカードに同じものを設定
			$lc = array(
				1,
				$init_cards[$this->camp][0], // lc_id
				// #PADC_DY# ----------begin----------
				// 初始化的leader是10级
				self::INIT_LEADER_CARD_LEVEL, // lc_lv
				// #PADC_DY# -----------end-----------
				1, // lc_slv
				0, // lc_hp
				0, // lc_atk
				0, // lc_rec
				0, // lc_psk
			);
			$lc = array_merge($lc, $lc);
			$this->lc = join(",", $lc);

			// #PADC# ----------begin----------

			// 図鑑にのみ登録しておくモンスターを追加
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user_book->addCardId(self::INIT_USER_BOOK_CARD_ID);
			$user_book->update($pdo);

			// PADC版はリーダーだけでなくデッキ内のカード全てを情報として持つ
			$ldeck = array();
			$card_num = 1;
			foreach($init_cards[$this->camp] as $card_id){
				$ldeck[] = array(
						$card_num,
						$card_id, // lc_id
						1, // lc_lv
						1, // lc_slv
						0, // lc_hp
						0, // lc_atk
						0, // lc_rec
						0, // lc_psk
				);
				$card_num++;
				// #PADC# デッキにセットできる数を超えたらループを抜ける
				if($card_num > self::DECK_CARD_MAX)
				{
					break;
				}
			}
			// #PADC_DY# ----------begin----------
			// 初始化ldeck信息和实际卡牌不一致bug修复
			$ldeck[0][2] = self::INIT_LEADER_CARD_LEVEL;
			// #PADC_DY# -----------end-----------
			$this->ldeck = json_encode($ldeck);
			// #PADC# ----------end----------

			$this->create($pdo);

			UserDungeonFloor::createDefaultUserDungeonFloor($user_id, $pdo);

			$deck = new UserDeck();
			$deck->user_id = $user_id;

			// 初期カード追加.
			$add_cards = array();
			foreach($init_cards[$this->camp] as $k => $card_id){
				// #PADC# リーダーの初期レベル変更
				$card_level = UserCard::DEFAULT_LEVEL;
				if($k == 0)// 1体目（リーダー）のLVを変更
				{
					$card_level = self::INIT_LEADER_CARD_LEVEL;
				}
				$add_cards[] = UserCard::addCardsToUserReserve(
					$user_id,
					$card_id,
					$card_level,
					UserCard::DEFAULT_SKILL_LEVEL,
					$pdo,
					0, // plus_hp
					0, // plus_atk
					0, // plus_rec
					0, // psk
					$pdo_share
				);
			}
			$user_cards = UserCard::addCardsToUserFix($add_cards, $pdo);

			// #PADC# ----------begin----------
			// カード生成済みの欠片データを作成する
			$init_piece_ids = $this->getInitPieceIds();
			$add_pieces = array();
			foreach($init_piece_ids[$this->camp] as $piece_id){
				$add_result = UserPiece::addUserPieceToUserReserve($user_id, $piece_id, 0, $pdo, null, $pdo_share);
				$user_piece = $add_result['piece'];
				$user_piece->create_card = 1;

				// 指定の欠片の場合、強化用として指定数付与する
				if($piece_id == self::INIT_COMPOSITE_TUTORIAL_PIECE_ID)
				{
					$user_piece->addPiece(self::INIT_COMPOSITE_TUTORIAL_PIECE_NUM, $pdo);
				}
				$add_pieces[] = $user_piece;
			}
			UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, array(), $pdo);
			// #PADC# ----------end----------

			// #PADC# ----------begin----------
			// デッキセット保存.
			$decks = array();
			for($i = 0; $i < User::INIT_DECKS_MAX; $i++ ){
				// チーム2のみ5体モンスターをセット
				if ($i == 1) {
					$decks[] = array(sprintf("set_%02s", $i) => array(1, 2, 3, 4, 5));
				}
				else {
					$decks[] = array(sprintf("set_%02s", $i) => array(1, 0, 0, 0, 0));	// 初期デッキにリーダーをセット
				}
			}
			$deck->decks = json_encode($decks);
			$deck->deck_num = 1;
			// #PADC# ----------end----------
					
			$deck->create($pdo);
			
			// #PADC# ----------begin----------
			// ユーザ登録上限チェック用に当日登録されたユーザIDを更新
			$date = Padc_Time_Time::getDate("Y-m-d");
			$_params = array(
				'date' => $date,
			);
			$signupLimit = SignupLimit::findBy($_params,$pdo_share,true);
			if($signupLimit)
			{
				$signupLimit->last_user_id = $user_id;
				$signupLimit->update($pdo_share);

				// キャッシュも更新しておく
				$key = RedisCacheKey::getSignupLimitKey($date);
				$signupLimitData = array(
					'num'			=> $signupLimit->num,
					'last_user_id'	=> $signupLimit->last_user_id,
				);
				$jsonSignupLimitData = json_encode($signupLimitData);
				$redis->set($key,$jsonSignupLimitData,SignupLimit::MEMCACHED_EXPIRE);
			}
			// #PADC# ----------end----------



			$pdo->commit();
			$pdo_share->commit();
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if (isset($pdo) && $pdo->inTransaction()) {
				$pdo->rollback();
			}
			if ($pdo_share->inTransaction()) {
				$pdo_share->rollback();
			}
			throw $e;
		}

		//每个新注册的玩家，都有新手嘉年华的参与机会
		UserCarnivalInfo::initialUserCarnivalInfo($user_id,$pdo);
	}

	/**
	 * ユーザの経験値が次のレベルに必要な経験値に達しているか返す.
	 * @return true for 達している. false for 達していない.
	 */
	public function isExpReachedNextLevel(){
		$nextLevel = LevelUp::get($this->lv+1);
		if( $nextLevel && $this->exp >= $nextLevel->required_experience ){
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * ユーザの経験値が次の次のレベルに必要な経験値に達しているか返す.
	 * @return true for 達している. false for 達していない.
	 */
	public function isExpReachedDoubleNextLevel(){
		$nextLevel = LevelUp::get($this->lv+2);
		if( $nextLevel && $this->exp >= $nextLevel->required_experience ){
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 指定されたレベルの経験値に達していればレベルアップする.
	 * レベルアップボーナスの振込処理も行う.
	 * @param $nextLevel ユーザの次のレベルのLevelUpオブジェクト
	 * @return レベルアップしたらTRUE, そうでなければFALSEを返す.
	 */
	public function levelUp($nextLevel){
		if( $nextLevel && $this->exp >= $nextLevel->required_experience ){
			$this->lv += 1;
			if( $nextLevel->bonus_id !== 0 ){
				switch($nextLevel->bonus_id){
					case BaseBonus::STAMINA_ID:
						if($this->stamina_max < self::STAMINA_STOCK_MAX){
							$this->stamina_max += $nextLevel->amount;
						}
						break;
					case BaseBonus::COST_ID:
						$this->cost_max += $nextLevel->amount;
						break;
					case BaseBonus::FRIEND_MAX_ID:
						$this->friend_max += $nextLevel->amount;
						break;
					default:
						global $logger;
						$logger->log("Unkown LevelUp Bonus ID : " . $nextLevel->bonus_id, Zend_Log::INFO);
				}
			}
			// スタミナ全回復
			if($this->stamina < $this->stamina_max){
				$this->stamina = $this->stamina_max;
			}
			// 回復時間が未来の可能性があるので現在時間にセットし全回復済みとする
			$this->stamina_recover_time = static::timeToStr(time());

			return TRUE;
		}
		return FALSE;
	}


	/**
	 * 現在のスタミナ値(時間回復考慮後)を返す.
	 */
	public function getStamina() {
		// #PADC# ----------begin----------
		// getRevでの判定を削除
		if($this->stamina >= (int)$this->stamina_max){
			// スタミナオーバーフロー時はキャップしない.
			return (int)$this->stamina;
		}
		// #PADC# ----------end----------
		$current_time = time();
		$recover_time = User::strToTime($this->stamina_recover_time);
		if($current_time >= $recover_time){
				// 全回復
				$this->stamina = (int)$this->stamina_max;
		}else{
			$used_stamina = $this->stamina_max - $this->stamina;
			$seconds_to_recover = $used_stamina * User::getStaminaRecoverInterval();
			$used_time = $recover_time - $seconds_to_recover;
			if($current_time >= $used_time){
				$stamina_recovered =  (int)(( $current_time - $used_time ) / User::getStaminaRecoverInterval());
				$this->stamina = (int)min($this->stamina + $stamina_recovered, $this->stamina_max);
			}
		}
		return (int)$this->stamina;
	}


	/**
	 * 指定量スタミナを消費する.
	 * 回復日時を再計算して書き換える.
	 */
	public function useStamina($used_stamina) {
		$stmod = (User::strToTime($this->stamina_recover_time) - time()) % User::getStaminaRecoverInterval(); // 余りを最後に足し戻してあげる

		if(($this->stamina_max - $this->stamina) <= 0) {
			$stmod = -1;
		}

		$this->stamina = $this->getStamina() - $used_stamina;

		// #PADC#  ----------begin----------
		if($this->stamina < 0){
			$this->stamina = 0;
		}

		if($this->stamina >= $this->stamina_max)
		{
			// 上限超えることは可能なので、スキップ
			//$this->stamina = $this->stamina_max;
			$stmod = -1;
		}
		if($this->stamina > User::STAMINA_STOCK_MAX){
			$this->stamina = User::STAMINA_STOCK_MAX;
		}
		// #PADC# ----------end----------

		if($this->stamina < $this->stamina_max ){
			$seconds_to_recover = ($this->stamina_max - $this->stamina) * User::getStaminaRecoverInterval();
		}else{
			$seconds_to_recover = 0;
		}

		if($stmod >= 0) {
			$this->stamina_recover_time = User::timeToStr(time() + $seconds_to_recover - (User::getStaminaRecoverInterval() - $stmod));
		} else {
			//#PADC#
			$this->stamina_recover_time = User::timeToStr(time() + $seconds_to_recover);
		}
	}

	/**
	 * スタミナ数変更
	 * @param number $stamina
	 */
	public function addStamina($stamina){
		$this->useStamina(-$stamina);
	}

	/**
	 * ユーザのデッキを返す.
	 * ※利用している箇所がありません
	 */
	public function getUserDeck(){
		return UserDeck::findBy(array('user_id'=>$this->id));
	}

	/**
	 * リーダーカードのレベルとスキルレベルを書き換える.
	 * ※利用している箇所がありません
	 */
	public function setLeaderCardLvAndSlv($lv, $slv) {
		$this->lc_lv = $lv;
		$this->lc_slv = $slv;
	}

	/**
	 * 指定されたコインを消費できるときに限りTRUEを返す.
	 */
	public function checkHavingCoin($coin) {
		if($this->coin >= $coin) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 指定されたユーザにお礼返しを送る.
	 * @param $pdo 書き込み用PDO
	 */
	public function sendThankYouGift($target_user_id, $pdo) {
		if($this->pbflg > 0){
			throw new PadException(RespCode::ALREADY_SENT_PRESENT, "User(id=$this->id) attempted to send a gift to a user(id=$target_user_id)");
		}
		if($this->lv < 20){
			throw new PadException(RespCode::UNKNOWN_ERROR, "PresentGacha cheat.");
		}
		$this->pbflg = $target_user_id;
		// メール送信
		UserMail::sendThankYouGift($this->id, $target_user_id, $pdo);
	}


	/**
	 * お礼返しメールを受け取る
	 * @param $pdo 書き込み用PDO
	 */
	public function receiveThankYouGift($pdo) {
		if($this->pback_cnt < 1){
			throw new PadException(RespCode::UNKNOWN_ERROR, "User(id=$this->id) doesn't hold any thank-you gifts.");
		}
		// メール送信
		$sender_id = UserMail::receiveThankYouGift($this->id, $pdo);
		$this->pback_cnt--;
	return $sender_id;
	}

	/**
	 * 指定された魔石を消費できるときに限りTRUEを返す.
	 */
	public function checkHavingGold($gold) {
		if(($this->gold+$this->pgold) >= $gold) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * ユーザー間メールの受信拒否が設定されているときにTRUEを返す.
	 */
	public function checkRejectUserMail() {
		if(($this->us & bindec('00000001')) == bindec('00000001')) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * #PADC# ユーザー間SNSメッセージの受信拒否が設定されているときにTRUEを返す.
	 * 
	 * @return boolean
	 */
	public function checkRejectUserSnsMsg(){
		if(($this->us & bindec('00001000')) == bindec('00001000')) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * check whether or not have enough ranking point
	 * @param int $target_point
	 * @return boolean
	 */
	public function checkHaveRankingPoint($target_point){
		if($target_point <= $this->ranking_point){
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * フレンドの申請拒否が設定されているときにTRUEを返す.
	 */
	public function checkRejectFriendRequest($mode = 0) {
		if($mode == User::MODE_NORMAL && ($this->us & bindec('00000010')) == bindec('00000010')) {
			return TRUE;
		} else if ($mode == User::MODE_W && ($this->us & bindec('00000100')) == bindec('00000100')) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 指定された友情ポイントを消費できるときに限りTRUEを返す.
	 */
	public function checkHavingFripnt($point) {
		if($this->fripnt >= $point) {
			return TRUE;
		}
		return FALSE;
	}

	// #PADC# add parameters
	public static function login($type, $uuid, $area, $version, $dev, $osv, $wmode, $camp, $openid, $channel_id, $token, $device_id=null, $game_center = User::NOT_GAME_CENTER){
		global $logger;

		// #PADC# ----------begin---------- add $openid
		$user_id = UserDevice::getUserIdFromUserDeviceKey($type, $uuid, $openid);
		if ($user_id === FALSE) {
			throw new PadException(RespCode::USER_NOT_FOUND, "Login Error type=$type uuid=$uuid oid=$openid. __NO_TRACE");
		}
		$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);
		$ptype = $userDeviceData['pt'];
		// #PADC# ----------end----------

		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			if ( $user === FALSE ) {
				throw new PadException(RespCode::USER_NOT_FOUND);
			}

			// #PADC# ----------begin----------
			if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$user_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			}else {
				$user_ip = $_SERVER["REMOTE_ADDR"];
			}

			// #PADC# verify user ----------begin----------
			if(ENV::CHECK_TENCENT_LOGIN || $token ['check_tencent'] != null){
				try{
					$verifyResult = Tencent_MsdkApi::verifyLogin($openid, $token['access_token'], $user_ip, $ptype);
				}catch(MsdkConnectionException $e){
					throw new PadException(RespCode::TENCENT_NETWORK_ERROR, $e->getMessage());
				}catch(MsdkApiException $e){
					throw new PadException(RespCode::TENCENT_API_ERROR, $e->getMessage());
				}
			}
			
			//#PADC# update qq vip
			$user->getQqVip($token, $pdo);
			//$user->getFriendVips($token, $user_ip, $pdo);

			// #PADC_DY# ----------begin----------
			// QQ VIP新手礼包以邮件的形式发放给玩家
			if ($user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, $user->qq_vip) == User::QQ_VIP_BONUS_AVALIBLE){
				if ($user->qq_vip == User::QQ_ACCOUNT_VIP){
					$subreason = Tencent_Tlog::SUBREASON_QQ_VIP_NOVICE_BONUS;
					$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_VIP_NOVICE_BONUS;
					$message = GameConstant::getParam("QQVipNoviceBonusMessage");
				}else {
					$subreason = Tencent_Tlog::SUBREASON_QQ_SVIP_NOVICE_BONUS;
					$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_SVIP_NOVICE_BONUS;
					$message = GameConstant::getParam("QQSvipNoviceBonusMessage");
				}

				$bonuses = QqVipBonus::getBonuses(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, $user->qq_vip);
				foreach	($bonuses as $bonus){
					UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, $bonus->bonus_id, $bonus->amount, $pdo, $message, null, $bonus->piece_id);
				}

				$user->setQqVipBonusReceived(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, $user->qq_vip);

				UserTlog::beginTlog($user, array(
						'money_reason' => Tencent_Tlog::REASON_QQ_VIP_BONUS,
						'money_subreason' => $subreason,
						'item_reason' => Tencent_Tlog::ITEM_REASON_QQ_VIP_BONUS,
						'item_subreason' => $itemSubReason,
				));

				UserTlog::commitTlog($user);
			}
			// #PADC_DY# ----------end----------
			
			//aq idip update gold
			$user->checkGoldFromIDIP($token, $pdo);

			//tencent game center
			$tempGameCenter = (int)$game_center;
			if($tempGameCenter == $user::NOT_GAME_CENTER ){
				//如果这次前端传过来的值是0，那么这次返回game_center的值使用上次的值
			}else{
				$user->game_center = $tempGameCenter;
			}

			// #PADC# ----------end----------

			// アカバン対応.
			if($user->del_status != User::STATUS_NORMAL){
				$pdo->commit();
				return array($user, 0);
			}

			if ($user->w_pflg == self::PFLG_MODE_NORMAL && $wmode == self::MODE_W) {
				if(is_null($camp)){
					// 初めて遊ぶモードの時はNOT_PLAY_LOGIN_MODE(37)を返す.
					throw new PadException(RespCode::NOT_PLAY_LOGIN_MODE, "Login Error NOT_PLAY_LOGIN_MODE(W) user_id:$user->id. __NO_TRACE");
				}else{
					// campがついているときはWの初期登録処理
					$user->w_pflg = self::PFLG_MODE_ALL;
					$user->w_stamina = User::W_INIT_STAMINA;
					$user->w_stamina_recover_time = static::timeToStr(time());
					// 初期アバター設定.
					$user->eq1_id = $user->getWInitAItem($camp);
					$user->eq1_lv = 1;
					list($succeed, $user, $res_user_aitem, $medal) = WUserAitem::composite($user, $user->eq1_id, $user->eq1_lv, $pdo);
				}
			}
			if ($user->w_pflg == self::PFLG_MODE_W && $wmode == self::MODE_NORMAL) {
				if(is_null($camp)){
					// 初めて遊ぶモードの時はNOT_PLAY_LOGIN_MODE(37)を返す.
					throw new PadException(RespCode::NOT_PLAY_LOGIN_MODE, "Login Error NOT_PLAY_LOGIN_MODE(NORMAL) user_id:$user->id. __NO_TRACE");
				}else{
					$user->w_pflg = self::PFLG_MODE_ALL;
					if($user->camp != $camp){
						// campが異なるときは属性と初期配布モンスター再設定.
						$user->camp = $camp;
						$init_cards = $user->getInitCards();
						// リーダーカード、サブリーダーカードに同じものを設定
						$lc = array(
							1,
							$init_cards[$user->camp][0], // lc_id
							1, // lc_lv
							1, // lc_slv
							0, // lc_hp
							0, // lc_atk
							0, // lc_rec
							0, // lc_psk
						);
						$lc = array_merge($lc, $lc);
						$user->lc = join(",", $lc);
						// 初期モンスターのカードIDを書き換える.
						$cuid = 1;
						foreach($init_cards[$user->camp] as $card_id){
							$user_card = UserCard::findBy(array(
								"user_id" => $user->id,
								"cuid" => $cuid++,
							), $pdo);
							$user_card->card_id = $card_id;
							$user_card->update($pdo);
						}
						// #PADC# ----------begin----------memcache→redisに切り替え
						$redis = Env::getRedisForUser();
						$key = CacheKey::getUserFriendDataFormatKey2($user->id);
						$redis->delete($key);
						// #PADC# ----------end----------
					}
				}
			}

			$current_time = time();
			//#PADC# ---------------begin------------------
			if($user->li_last){
				$li_last_time = static::strToTime($user->li_last);
			}else{
				$li_last_time = null;
			}
			//#PADC# ---------------end------------------

			$user->li_cnt++; // ログイン回数累計

			$user->accessed_at = User::timeToStr($current_time); // アクセス時間
			$user->accessed_on = $user->accessed_at;

			$user->area_id = self::getAreaIdByName($area); // エリアid
			$user->dev = $dev; // デバイス
			$user->osv = $osv; // OSバージョン

			$old_li_str = $user->li_str;
			$old_li_days = $user->li_days;

			// ログイン日数
			// #PADC#
			if($user->li_last == NULL || !User::isSameDay_AM4($li_last_time, $current_time)){
				$user->li_days++;

                // #PADC_DY# ----------begin----------
				//重置前日游戏中心状态
				if($tempGameCenter == $user::NOT_GAME_CENTER ){
					//如果今日第一次登录不是游戏中心启动
					if($user->game_center == $user::QQ_GAME_CENTER){  			//如果昨天是QQ游戏中心登录的
						$user->game_center =  $user::QQ_GAME_CENTER_YET; 			//那么状态设置为曾经
					}else if($user->game_center == $user::WECHAT_GAME_CENTER)	{ 	//如果昨天是微信中心登录的
						$user->game_center =  $user::WECHAT_GAME_CENTER_YET;		//那么状态设置为曾经
					}else{
							//进入这里表示昨天的登录状态不是中心登录，或者曾经中心登录，这个是game_center保持原样
					}
				}else{
					$user->game_center = $tempGameCenter;
				}
                // 重置前日进入关卡次数
                UserDungeonFloor::resetDailyPlayTimes($user_id, $pdo);
                
                // 重置每日活动及分享状态
                $activities = Activity::getAllBy(array('del_flg' => 0));
				$activity_ids = array();

				// 获得当前开放的累计充值活动
				$total_charge_activity = Activity::getByType(Activity::ACTIVITY_TYPE_TOTAL_CHARGE);
				// 当前无累计充值活动开放
				if(empty($total_charge_activity)){
					// 重置累计充值字段
					$user->tp_gold_period = 0;
				} else {
					// 当前有累计充值活动，但是上次充值时间在该次充值时间开始之前，重置累计充值字段
					if(static::strToTime($user->tp_period_access_at) < static::strToTime($total_charge_activity->begin_at)){
						$user->tp_gold_period = 0;
					}
				}
				// 获得当前开放的累计消费活动
				$total_consum_activity = Activity::getByType(Activity::ACTIVITY_TYPE_TOTAL_CONSUM);
				// 当前无累计消费活动开放
				if(empty($total_consum_activity)){
					// 重置累计消费字段
					$user->tc_gold_period = 0;
				}else{
					// 当前有累计消费字段，但是上次消费时间在本次活动开启之前，重置累计消费字段
					if(static::strToTime($user->tc_period_access_at) < static::strToTime($total_consum_activity->begin_at)) {
						$user->tc_gold_period = 0;
					}
				}

				// 连续每日充值状态重置
				$continuous_charge_activity = Activity::getByType(Activity::ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED);
				if(empty($continuous_charge_activity)){
					$user->count_p6 = 0;
				}else{
					if(static::strToTime($user->last_p6_at) < static::strToTime($continuous_charge_activity->begin_at)) {
						$user->count_p6 = 0;
					}
				}


                foreach($activities as $activity) {
                    if(($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY || $activity->activity_type == Activity::ACTIVITY_TYPE_SHARE
						/* || $activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE */) && $activity->isEnabled($current_time)) {
						$activity_ids[] = $activity->id;
                    } elseif($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_LOGIN && $activity->isEnabled($current_time)) {
						UserActivity::updateStatus($user_id, $activity->id, UserActivity::STATE_CLEAR, $pdo);
					} elseif($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE && $activity->isEnabled($current_time)) {
						$user_activity = UserActivity::findBy((array('user_id' => $user->id, 'activity_id' => $activity->id)),$pdo);
						if($user_activity && $user_activity->status == UserActivity::STATE_RECEIVED) {
							$activity_ids[] = $activity->id;
						}
					}
                }
				if(count($activity_ids)>0) {
					UserActivity::dailyReset($user_id, $activity_ids, $pdo);
				}
				// 重置连续登录天数
				if(!BaseModel::isSameDay_AM4(BaseModel::strToTime($user->last_p6_at), BaseModel::strToTime('-1 day'))) {
					$user->count_p6 = 0;
				}
                // #PADC_DY# -----------end-----------
			}

			// ログインストリーク
			$force_login_streak_bonus = FALSE;
			// #PADC#
			if($user->li_last == NULL || User::isSameDay_AM4($li_last_time, $current_time - 86400)){

				// #PADC# ----------begin----------
				$max_login_streack = User::MAX_LOGIN_STREAK;
				$loginPeriod = LoginPeriod::getActiveLoginPeriod();
				if($loginPeriod)
				{
					// 保存しているログイン期間IDの期間外であれば継続ログインをきって、保存しているログイン期間IDを更新する
					$max_login_streack = $loginPeriod->max_login_streak;
					if($loginPeriod->id != $user->li_period)
					{
						$user->li_period = $loginPeriod->id;
						$user->li_str = 0;
					}
				}
				else
				{
					// 設定されていない場合、デフォルト値を参照
					if(self::DEFAULT_LOGIN_PERIOD_ID != $user->li_period)
					{
						$user->li_period = self::DEFAULT_LOGIN_PERIOD_ID;
						$user->li_str = 0;
					}
				}
				// #PADC# ----------end----------

				$user->li_str = min($user->li_str+1, $max_login_streack);
				$user->li_max = max($user->li_max, $user->li_str);
				// 連続して MAX_LOGIN_STREAK に達してもボーナスが得られるようにする.
				$force_login_streak_bonus = TRUE;
			}else if(!User::isSameDay_AM4(static::strToTime($user->li_last), $current_time)){

				// #PADC# ----------begin----------
				$loginPeriod = LoginPeriod::getActiveLoginPeriod();
				if($loginPeriod)
				{
					$user->li_period = $loginPeriod->id;
				}
				else
				{
					$user->li_period = self::DEFAULT_LOGIN_PERIOD_ID;
				}
				// #PADC# ----------end----------

				$user->li_str = 1;// #PADC# 連続ログインが途切れたら1日目に戻す
			}

			// 最終ログインから何日経過したか
			// #PADC#
			if($user->li_last !== NULL && !User::isSameDay_AM4($li_last_time, $current_time)){
				$login_interval = ($current_time - static::strToTime($user->li_last));
				$log_data['li_val'] = floor($login_interval / 86400);
			}else{
				$log_data['li_val'] = 0; // 後で消したい.
			}

			$user->li_last = User::timeToStr($current_time);

			$bonuses = array();
			//#PADC#------------------begin--------------
			// 連続ログインボーナスはミッションに移ったので元の処理をコメントアウト
			//if($old_li_str != $user->li_str || $force_login_streak_bonus == TRUE){
			//	$bonuses = LoginStreakBonus::getBonuses($user->li_str);
			//	if($user->w_pflg > 0 && $old_li_days != 0){
			//		// Wモード用ログインボーナス1000メダル(初日以外).
			//		$w_login_streak_medal = GameConstant::getParam("WLoginStreakMedal");
			//		$user->addMedal($w_login_streak_medal);
			//		// その日初めて pw_getpdate を実行したかフラグ 0:実行済み 1:未実行
			//		$user->w_pgetflg = 1;
			//	}
			//}
			//# PADC # -----------------end--------------------
			if($old_li_days != $user->li_days){
				//# PADC # -----------------begin--------------------
				if(!$li_last_time || $user->li_mdays == 0 || !User::isSameMonth_AM4($li_last_time, $current_time)){
					$user->li_mdays = 1;
				}else{
					$user->li_mdays++;
				}
				$loginBonuses = LoginTotalCountBonus::getLoginMonBonuses((int)$user->li_mdays);
				foreach ($loginBonuses as $loginBonus){
					$loginTotalCountBonusMessage = GameConstant::getParam("LoginTotalCountBonusMessage");
					$message = sprintf($loginTotalCountBonusMessage, $user->li_mdays);
					// 修改累计登录奖励为直接发放（hehe）
					$user->applyBonus($loginBonus->bonus_id, $loginBonus->amount, $pdo, null, $token, $loginBonus->piece_id);
					// UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, $loginBonus->bonus_id, $loginBonus->amount, $pdo,$message,null,$loginBonus->piece_id);
				}

				//# PADC # -----------------end--------------------
			}
			// #PADC_DY# ----------begin----------
			//game Center login bonus
			if(!static::isSameDay_AM4(static::strToTime($user->last_game_center), $current_time)){
				if($user->game_center == User::QQ_GAME_CENTER || $user->game_center == User::WECHAT_GAME_CENTER){
					$gameCenterLoginBonusMessage = GameConstant::getParam("GameCenterLoginBonusMessage");
					$gameCenterMessage = ($user->game_center == User::QQ_GAME_CENTER)? GameConstant::getParam("QQGameCenter") : GameConstant::getParam("WechatGameCenter");
					$message = sprintf($gameCenterLoginBonusMessage, $gameCenterMessage);
					//ひとまずコイン1000固定
					UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, GameConstant::getParam("GameCenterLoginBonusId"), GameConstant::getParam("GameCenterLoginBonusAmount"), $pdo, $message);
					$user->last_game_center = static::timeToStr($current_time);
				}

				$platform_type = UserDevice::getUserPlatformType($user->id);
				if($platform_type == UserDevice::PTYPE_GUEST){
					$guestLoginBonusMessage = GameConstant::getParam("GuestLoginBonusMessage");
					$guestCenterMessage = GameConstant::getParam("GuestGameCenter");
					$message = sprintf($guestLoginBonusMessage, $guestCenterMessage);
					//ひとまずコイン1000固定
					UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, GameConstant::getParam("GameCenterLoginBonusId"), GameConstant::getParam("GameCenterLoginBonusAmount"), $pdo, $message);
					$user->last_game_center = static::timeToStr($current_time);
				}
			}
			// #PADC_DY# ----------end----------


			//#PADC#------------------begin--------------
			//qq vip login bonus
			if($user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_LOGIN_BONUS) == User::QQ_VIP_BONUS_AVALIBLE){
				$qqVipLoginBonuses = QqVipBonus::getBonuses(QqVipBonus::TYPE_QQ_VIP_LOGIN_BONUS, $user->qq_vip);
				foreach($qqVipLoginBonuses as $qvlBonus){
					$base_message = GameConstant::getParam("QQVipLoginBonusMessage");
					$bonus_message = ($user->qq_vip == User::QQ_ACCOUNT_SVIP)? GameConstant::getParam("QQSvipLoginBonus"):GameConstant::getParam("QQVipLoginBonus");
					$message = sprintf($base_message,$bonus_message, $bonus_message);
					UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, $qvlBonus->bonus_id, $qvlBonus->amount, $pdo,$message,null,$qvlBonus->piece_id);
				}
				$user->lqvdb_days = $user->li_days;
			}
			//continue must set zero when next week is came
			if(!VipBonus::isSameWeek_AM4(BaseModel::strToTime($user->last_vip_weekly))){
				$user->cont = 0;
			}
			//#PADC#------------------end-----------------
			$gold_before=$user->gold;
			$pgold_before=$user->pgold;
			$coin_before = $user->coin;
			//#PADC#------------------begin--------------
			// 連続ログインボーナスはミッションに移ったので元の処理をコメントアウト
			//// 連続ログインボーナスは即時配布.
			//if(isset($bonuses)){
			//	// #PADC# パラメータ追加
			//	LoginStreakBonus::applyBonuses($user, $bonuses, $pdo, $token);
			//}
			//#PADC#------------------end-----------------
			$gold_after=$user->gold;
			$pgold_after=$user->pgold;
			$coin_after = $user->coin;

			$user->device_type = $type;
			//#PADC#------------------begin--------------
			// MY : ランキング報酬の付与
			/*
			$n_rewards = RankingReward::getReward(User::timeToStr(time()),$user_id);
			if($n_rewards)
			{
				RankingReward::applyRewardsMail($user,$n_rewards,$pdo);
			}
			*/
			
			//login channel & device id for logout tlog
			$user->login_channel = $channel_id;
			$user->device_id = $device_id;
			
			//#PADC#------------------end-----------------
			$user->update($pdo);

			//#PADC# tencent bonus
			TencentBonus::applyBonuses($user, $pdo);
			$mails = User::getMailCount($user_id, $wmode, $pdo, TRUE);

			if($gold_before < $gold_after){
				UserLogAddGold::log($user->id, UserLogAddGold::TYPE_LOGIN, $gold_before, $gold_after, $pgold_before, $pgold_after, $user->device_type);

				// #PADC# TLOG
				UserTlog::sendTlogMoneyFlow($user, $pgold_after + $gold_after - $pgold_before - $gold_before, Tencent_Tlog::REASON_BONUS, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($gold_after - $gold_before), abs($pgold_after - $pgold_before), 0, Tencent_Tlog::SUBREASON_LOGIN_BONUS);

				// #PADC#
				$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
			}
			// #PADC# Tlog ----------being----------
			if($coin_before < $coin_after){
				UserTlog::sendTlogMoneyFlow($user, $coin_after - $coin_before, Tencent_Tlog::REASON_BONUS, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, 0, Tencent_Tlog::SUBREASON_LOGIN_BONUS);
			}
			// #PADC# ----------end----------

			$log_data['type'] = (int)$type;
			$log_data['uuid'] = $uuid;
			$log_data['area'] = $area;
			$log_data['version'] = $version;
			$log_data['dev'] = $dev;
			$log_data['osv'] = $osv;
			$log_data['wmode'] = (int)$wmode;

			// #PADC# get user ip move up

			// ログインチェックで使用されているユーザはログイン履歴を残さない.
			$login_check_users = Env::getLoginCheckUsers();
			if ( !in_array( $user->id, $login_check_users ) ) {
				UserLogLogin::log($user->id, $user_ip, $log_data);
			}

			// #PADC# Tlog
			UserTlog::sendTlogLogin($user, $channel_id, $device_id, $game_center);
			if($game_center == self::QQ_GAME_CENTER){
				$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_PRIVILEDGE_STARTUP, $token);
			}
			$pdo->commit();

			// #PADC#
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_LOGIN, $token);
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_VIP_LEVEL, $token, $user->vip_lv);
		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		return array($user, $mails);
	}

	/**
	 * userに対するdevice_typeを返します.
	 */
	public function getUserDevice(){
		return $this->device_type;
	}

	public function getAreaId(){
		return $this->area_id;
	}

	/*
	 * 仕向地の文字列をidに変換.
	 */
	public static function getAreaIdByName($area){
		$area_id = "";
		switch($area){
			case 'ja':
			case 'JP':
				$area_id = self::AREA_JP;
				break;
			case 'na':
			case 'NA':
				$area_id = self::AREA_NA;
				break;
			case 'kr':
			case 'KR':
				$area_id = self::AREA_KR;
				break;
			case 'eu':
			case 'EU':
				$area_id = self::AREA_EU;
				break;
			case 'ht':
			case 'HT':
				$area_id = self::AREA_HT;
				break;
			default:
				$area_id = self::AREA_JP;
		}
		return $area_id;
	}
	
	/**
	 * ボーナスを付与したら、上限数に超えるか
	 * 
	 * @param number $bonusId
	 * @param number $amount
	 * @param number $pieceId
	 * @return boolean
	 */
	public function checkBonusLimit($bonusId, $amount, $pieceId = 0, $pdo = null){
		if($pdo == null){
			$pdo = Env::getDbConnectionForUserRead ( $this->id );
		}
		if($bonusId == BaseBonus::COIN_ID){
			return ($this->coin + $amount > self::COIN_MAX);
		}else if($bonusId == BaseBonus::FRIEND_POINT_ID){
			return ($this->fripnt + $amount > self::MAX_FRIEND_POINT);
		}else if($bonusId == BaseBonus::ROUND_ID){
			return ($this->round + $amount > self::ROUND_TICKET_MAX);
		}else if($bonusId == BaseBonus::PIECE_ID) {
			$user_piece = UserPiece::findBy(array(
					'user_id' => $this->id,
					'piece_id' => $pieceId
			), $pdo, false);
			if($user_piece == FALSE)
			{
				return false;
			}
			return ($user_piece->num + $amount >= UserPiece::NUM_MAX);
		}
		return false;
	}

	/**
	 * ボーナスを付与する.
	 * TODO: ログ出力.
	 * @param integer $bonusId
	 * @param integer $amount
	 * @param PDO $pdo
	 */
	// #PADC# ----------begin---------- 
	// パラメータ追加
	public function applyBonus($bonusId, $amount, $pdo, $ex_params = null, $token, $piece_id = null) {

		$result = array();
		$result['no'] = $bonusId;
		$result['amount'] = $amount;

		if($bonusId <= BaseBonus::MAX_CARD_ID) {
			// カードボーナス.
			$plus_hp = isset($ex_params["ph"]) ? $ex_params["ph"] : 0;
			$plus_atk = isset($ex_params["pa"]) ? $ex_params["pa"] : 0;
			$plus_rec = isset($ex_params["pr"]) ? $ex_params["pr"] : 0;
			$psk = isset($ex_params["psk"]) ? $ex_params["psk"] : 0;
			$slv = isset($ex_params["slv"]) ? $ex_params["slv"] : UserCard::DEFAULT_SKILL_LEVEL;
			$user_card = UserCard::addCardToUser($this->id, $bonusId, $amount, $slv, $pdo, $plus_hp, $plus_atk, $plus_rec, $psk);
			$result["user_card"] = $user_card;
		} elseif($bonusId == BaseBonus::COIN_ID) {
			// コインボーナス.
			$this->addCoin($amount);
			$result["coin"] = $this->coin;
		} elseif($bonusId == BaseBonus::MAGIC_STONE_ID) {
			// (無料)魔石ボーナス.
			// #PADC# Add free gold to Tencent server
			$this->presentGold($amount, $token);
			$result['gold'] = ($this->gold+$this->pgold);
		} elseif($bonusId == BaseBonus::PREMIUM_MAGIC_STONE_ID) {
			// (有料)魔石ボーナス.
			$this->addPGold($amount);
			$result['gold'] = ($this->gold+$this->pgold);
			//bonus_idが有償魔法石の場合は無償魔法石のコードに変換して返す
			$result['no']=(int)BaseBonus::MAGIC_STONE_ID;
		} elseif($bonusId == BaseBonus::FRIEND_POINT_ID) {
			// 友情ポイントボーナス.
			$this->addFripnt($amount);
			$result ['fripnt'] = $this->fripnt;
		}
		elseif($bonusId == BaseBonus::STAMINA_ID)
		{
			// スタミナの現在値を計算後に加算する
			$this->getStamina();
			$this->stamina += $amount;
			$this->stamina_max += $amount;
			if ($this->stamina_max > self::STAMINA_STOCK_MAX) {
				$this->stamina_max = self::STAMINA_STOCK_MAX;
			}
			if ($this->stamina > self::STAMINA_STOCK_MAX) {
				$this->stamina = self::STAMINA_STOCK_MAX;
			}
			$result['sta'] = $this->stamina;
			$result['sta_max'] = $this->stamina_max;
			$result['sta_time'] = strftime("%y%m%d%H%M%S", strtotime($this->stamina_recover_time));
		}
		elseif($bonusId == BaseBonus::STAMINA_RECOVER_ID)
		{
			$this->addStamina($amount);
			$result['sta'] = $this->stamina;
			$result['sta_time'] = strftime("%y%m%d%H%M%S", strtotime($this->stamina_recover_time));
		}
		elseif($bonusId == BaseBonus::FRIEND_MAX_ID)
		{
			$this->friend_max += $amount;
			if ($this->friend_max > self::FRIEND_MAX_LIMIT) {
				$this->friend_max = self::FRIEND_MAX_LIMIT;
			}
			$result['frimax'] = $this->friend_max;
		}
		elseif($bonusId == BaseBonus::EXP_ID) {
			$this->addExp($amount);
			$result['exp'] = $this->exp;
		} elseif($bonusId == BaseBonus::MEDAL_ID) {
			// メダルボーナス.
			$this->addMedal($amount);
			$result['m'] = $this->medal;
		} else if($bonusId == BaseBonus::AVATAR_ID) {
			$avatar_id = isset($ex_params["aid"]) ? $ex_params["aid"] : 0;
			$avatar_lv = isset($ex_params["alv"]) ? $ex_params["alv"] : 1;
			$result = WUserAitem::addBonusAvatar($this, $pdo, $avatar_id, $avatar_lv);
		}
		else if($bonusId == BaseBonus::RANKING_POINT_ID)
		{
			$this->addRankingPoint($amount);
			$result['ranking_point'] = $this->ranking_point;
		}
		else if($bonusId == BaseBonus::CONTINUE_ID){
			$this->setContinue($amount);
			$result['continue'] = $this->cont;
		}
		else if($bonusId == BaseBonus::ROUND_ID){
			$this->addRound($amount);
			$result['round'] = $this->round;
		}
		else if($bonusId == BaseBonus::PIECE_ID) {
			//$piece_id = isset($ex_params["pid"]) ? $ex_params["pid"] : 0;
			if ($piece_id > 0) {
				// カケラ
				$add_result = UserPiece::addUserPieceToUser(
						$this->id,
						$piece_id,
						$amount,
						$pdo
				);
				$result["user_piece"] = $add_result["piece"];

				//#PADC#
				UserTlog::setTlogData(array(
					'piece' => array(
						array(
							'id' => $piece_id,
							'add' => $amount,
							'num' => $add_result["piece"]->num,
						),
					),
				));
				//cardがない可能性があります
				$result["user_card"] = isset($add_result["card"]) ? $add_result["card"] : null;
				if(isset($result["user_card"])){
					// 図鑑登録数の更新
					$user_book = UserBook::getByUserId($this->id, $pdo);
					$this->book_cnt = $user_book->getCountIds();

					//#PADC#
					UserTlog::setTlogData(array(
						'card' => array(
							$result["user_card"]->card_id
						),
					));
				}
			}
			else {
				$result["user_piece"] = null;
				$result["user_card"] = null;
			}
		}
		// #PADC_DY# ----------begin----------
		else if($bonusId == BaseBonus::USER_EXP){
			$this->addExp($amount);
			$result["exp"] = $this->exp;
		}
		else if($bonusId == BaseBonus::USER_VIP_EXP){
			$this->addVipExp($amount);
			$this->refreshVipLv($token);
			$result["tp_gold"] = $this->tp_gold;
			$result["vip_lv"] = $this->vip_lv;
		}
		// #PADC_DY# -----------end-----------
		return $result;
	}

	public static function arrangeBonusResponse($result, $rev)
	{
		if(empty($result) == false)
		{
			if(isset($result["user_piece"])){
				$result['piece'] = UserPiece::arrangeColumn($result["user_piece"], $rev);
			}
			if(array_key_exists("user_piece",$result))
			{
				unset($result["user_piece"]);
			}
			if (isset($result["user_card"])) {
				$result['card'] = GetUserCards::arrangeColumn($result["user_card"],$rev);
			}
			if(array_key_exists("user_card",$result))
			{
				unset($result["user_card"]);
			}
		}
		return $result;
	}
	// #PADC# ----------end----------
	/**
	 * モードに応じたユーザーのメール件数を返す.
	 */
	public static function getMailCount($user_id, $wmode, $pdo = null, $use_cache = TRUE){
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		$value = FALSE;
		$key = CacheKey::getMailCountKey($user_id);
		if($use_cache){
			$rRedis = Env::getRedisForUserRead();
			$value = $rRedis->get($key);
		}
		if($value == FALSE) {
			list($mail_cnt, $w_mail_cnt) = UserMail::countTypeBy($user_id, $pdo);
			$redis = Env::getRedisForUser();
			$redis->set($key, array($mail_cnt, $w_mail_cnt), static::MEMCACHED_EXPIRE);
		} else {
			list($mail_cnt, $w_mail_cnt) = $value;
		}
		if($wmode == User::MODE_NORMAL){
			return $mail_cnt;
		}elseif($wmode == User::MODE_W){
			return $w_mail_cnt;
		}
		return 0;
	}

	/*
	 * メール件数のキャッシュを削除 ※static.
	 */
	public static function resetMailCount($user_id) {
		$redis = Env::getRedisForUser();
		$key = CacheKey::getMailCountKey($user_id);
		$redis->delete($key);
	}

	// #PADC# ----------begin----------
	// 日付判定が他のモデルでも利用できるようBaseModel.phpへ移動
	//private static function isSameDay($time1, $time2){
	//	return strftime('%y%m%d', $time1) == strftime('%y%m%d', $time2);
	//}
	//
	//private static function isSameMonth($time1, $time2){
	//	return strftime('%y%m', $time1) == strftime('%y%m', $time2);
	//}
	//
	//// 午前4時に日替り判断
	//private static function isSameDay_AM4($time1, $time2){
	//	return strftime('%y%m%d', $time1 - 14400) == strftime('%y%m%d', $time2 - 14400);
	//}
	// #PADC# ----------end----------

	// スタミナ回復時間.
	public static function getStaminaRecoverInterval() {
		// #PADC# ----------begin----------
		// スタミナ回復時間を600秒→300秒に変更（getRevでの判定を削除）
		return User::STAMINA_RECOVER_INTERVAL;
		// #PADC# ----------end----------
	}

	// W版スタミナ回復時間.
	public static function getWStaminaRecoverInterval() {
		return User::W_STAMINA_RECOVER_INTERVAL;
	}

	/**
	 * 指定されたメダルを消費できるときに限りTRUEを返す.
	 */
	public function checkHavingMedal($medal) {
		if($this->medal >= $medal) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 現在のスタミナ値(時間回復考慮後)を返す(W).
	 */
	public function getWStamina() {
		$current_time = time();
		$recover_time = User::strToTime($this->w_stamina_recover_time);
		if($current_time >= $recover_time){
			// 全回復
			$this->w_stamina = User::W_STAMINA_MAX;
		}else{
			$used_stamina = User::W_STAMINA_MAX - $this->w_stamina;
			$seconds_to_recover = $used_stamina * User::getWStaminaRecoverInterval();
			$used_time = $recover_time - $seconds_to_recover;
			if($current_time >= $used_time){
				$stamina_recovered =  (int)(( $current_time - $used_time ) / User::getWStaminaRecoverInterval());
				$this->w_stamina = (int)min($this->w_stamina + $stamina_recovered, User::W_STAMINA_MAX);
			}
		}
		return (int)$this->w_stamina;
	}

	/**
	 * 指定量スタミナを消費するを返す(W).
	 * 回復日時を再計算して書き換える.
	 */
	public function useWStamina($used_stamina) {
			$stmod = (User::strToTime($this->w_stamina_recover_time) - time()) % User::getWStaminaRecoverInterval(); // 余りを最後に足し戻してあげる
			if((User::W_STAMINA_MAX - $this->w_stamina) <= 0) {
				$stmod = -1;
			}
			$this->w_stamina = $this->getWStamina() - $used_stamina;
			$seconds_to_recover = (User::W_STAMINA_MAX - $this->w_stamina) * User::getWStaminaRecoverInterval();
			if($stmod >= 0) {
				$this->w_stamina_recover_time = User::timeToStr(time() + $seconds_to_recover - (User::getWStaminaRecoverInterval() - $stmod));
			} else {
				$this->w_stamina_recover_time = User::timeToStr(time() + $seconds_to_recover);
			}
	}

	public function getWInitAItem($camp) {
		if($camp == 1){
			$ava_head = User::W_INIT_ITEM_CAMP_1;
		}elseif($camp == 2){
			$ava_head = User::W_INIT_ITEM_CAMP_2;
		}elseif($camp == 3){
			$ava_head = User::W_INIT_ITEM_CAMP_3;
		}
		return $ava_head;
	}

	/**
	 * アバターを変更.
	 */
	public static function changeAvatar($user_id, $avatar) {
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);

			// "aa,bb,cc"を分割
			$aids = explode(",", $avatar);
			$user_aitems = WUserAitem::findByAitems($user_id, $aids, $pdo);
			$eq1_id = 0;
			$eq1_lv = 0;
			$eq2_id = 0;
			$eq2_lv = 0;
			$eq3_id = 0;
			$eq3_lv = 0;
			$bonus_ids = array();
			$bonus_card = null;

			// 頭
			if (!empty($aids[0])) {
				foreach ($user_aitems as $ua) {
					if ($aids[0] == $ua->aitem_id) {
						$head_item = WAvatarItem::get($ua->aitem_id);
						break;
					}
				}
				if (!(isset($head_item) && $head_item->region == WAvatarItem::REGION_HEAD)) {
					throw new PadException(RespCode::UNKNOWN_ERROR); // 不正なアバターが設定されたとき.
				}
				$eq1_id = $ua->aitem_id;
				$eq1_lv = $ua->lv;
				$bonus_ids[] = $head_item->bonus_id;
			}
			// 持ち物
			if (!empty($aids[1])) {
				foreach ($user_aitems as $ua) {
					if ($aids[1] == $ua->aitem_id) {
						$weapon_item = WAvatarItem::get($ua->aitem_id);
						break;
					}
				}
				if (!(isset($weapon_item) && $weapon_item->region == WAvatarItem::REGION_WEAPON)) {
					throw new PadException(RespCode::UNKNOWN_ERROR); // 不正なアバターが設定されたとき.
				}
				$eq2_id = $ua->aitem_id;
				$eq2_lv = $ua->lv;
				$bonus_ids[] = $weapon_item->bonus_id;
			}
			// カラ
			if (!empty($aids[2])) {
				foreach ($user_aitems as $ua) {
					if ($aids[2] == $ua->aitem_id) {
						$shell_item = WAvatarItem::get($ua->aitem_id);
						break;
					}
				}
				if (!(isset($shell_item) && $shell_item->region == WAvatarItem::REGION_SHELL)) {
					throw new PadException(RespCode::UNKNOWN_ERROR); // 不正なアバターが設定されたとき.
				}
				$eq3_id = $ua->aitem_id;
				$eq3_lv = $ua->lv;
				$bonus_ids[] = $shell_item->bonus_id;
			}

			$user->eq1_id = $eq1_id;
			$user->eq1_lv = $eq1_lv;
			$user->eq2_id = $eq2_id;
			$user->eq2_lv = $eq2_lv;
			$user->eq3_id = $eq3_id;
			$user->eq3_lv = $eq3_lv;
			$user->update($pdo);

			// アバターボーナス
			$cnt_bonus_ids = array_count_values($bonus_ids);
			$bonus_id = array_search(WAvatarItem::AVATAR_BONUS_COUNT, $cnt_bonus_ids);

			if ($bonus_id) {
				$history = WUserAvatarBonusHistory::findBy(array('user_id'=>$user->id, 'bonus_id'=>$bonus_id), $pdo);
				if (empty($history)) {
					$param = array("slv" => 1, "plus" => array(0, 0, 0, 0));
					$param['message'] = "アバターボーナスメッセージ";
					UserMail::sendAdminMailBonus($user->id, UserMail::TYPE_ADMIN_BONUS_NORMAL, $bonus_id, 1, $pdo, $param);
					$history = new WUserAvatarBonusHistory();
					$history->user_id = $user->id;
					$history->bonus_id = $bonus_id;
					$history->create($pdo);
					$bonus_card = Card::get($bonus_id);
				}
			}

			$pdo->commit();
		} catch(Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		return array($user, $bonus_card);
	}

	// データスナップショット作成
	public static function getsnapshots($user_id,$log_date) {
		$pdo = Env::getDbConnectionForUserRead($user_id);
		$sql = "SELECT * FROM ". self::TABLE_NAME ." WHERE id = ?";
		$bind_param = array($user_id);
		list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
		$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$snapshot_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_user_snapshot.log");
		$snapshot_format = '%message%'.PHP_EOL;
		$snapshot_formatter = new Zend_Log_Formatter_Simple($snapshot_format);
		$snapshot_writer->setFormatter($snapshot_formatter);
		$snapshot_logger = new Zend_Log($snapshot_writer);
		foreach($values as $value) {
			$value=preg_replace('/"/', '""',$value);
			$value['name'] = '"' . $value['name'] . '"';
			$snapshot_logger->log($log_date.",".implode(",",$value), Zend_Log::DEBUG);
		}
	}

	// ENVからユーザデバイスタイプを返す.
	public static function getDeviceTypeOfEnv(){
		if(Env::ENABLED_OS == 'ios'){
			$device_type = User::TYPE_IOS;
		}elseif(Env::ENABLED_OS == 'android'){
			$device_type = User::TYPE_ANDROID;
		}elseif(Env::ENABLED_OS == 'amazon'){
			$device_type = User::TYPE_AMAZON;
		}
		return $device_type;
	}

	/**
	 * 
	 * @param array $token
	 * @param PDO $pdo
	 * @throws PadException
	 * @return array
	 */
	public function getBalance($token, $pdo) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $this->id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];

		$this->checkGoldFromIDIP($token, $pdo);
			
		if (($this->device_type == UserDevice::TYPE_ADR || Env::ENABLE_IOS_MIDAS) && Env::CHECK_TENCENT_TOKEN) {
			try {
			//$present_subscribe_result = Tencent_MsdkApi::presentSubscribe(2, $openid, $token, $ptype, $this->device_type);
			//$query_subscribe_result = Tencent_MsdkApi::querySubscribe($openid, $token, $ptype, $this->device_type);
			$balance_result = Tencent_MsdkApi::getBalance ( $openid, $token, $ptype, $this->device_type );
			} catch ( MsdkConnectionException $e ) {
				throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
			} catch ( MsdkApiException $e ) {
				throw new PadException ( RespCode::TENCENT_API_ERROR, $e->getMessage());
			}

			$gold = $balance_result ['gen_balance'];
			$pgold = $balance_result ['balance'] - $gold;
			//$pgold = $this->pgold + 300;//for debug
			$tss_list = $balance_result ['tss_list'];
			if ($this->pgold != $pgold || $this->gold != $gold) {
				$this->onGoldChange($gold, $pgold, $token, $pdo);
			}

			// ios月額課金処理に統一
			//if($this->device_type == UserDevice::TYPE_ADR){
			//	SubscriptionBonus::checkSubscriptionAdr($this, $tss_list, $pdo);
			//}
		}

		$gold_sum = $this->gold + $this->pgold;
		//#PADC -add 'user'=>$user
		return array(
				'sum'=> $gold_sum,
				'tss_end' => isset($this->tss_end)? static::strToTime($this->tss_end) : null,
		);
	}
	
	/**
	 * #PADC# get user balance from tencent server
	 *
	 * sync gold and pgold with the tencent server and count tp_gold number for vip lv up,be carefull
	 * vip_lv only level up,do not level down.even if tp_gold is less than the costs of the vip levels
	 *
	 * @param string $user_id
	 * @param array $token
	 * @param PDO $pdo
	 * @throws PadException
	 * @throws Exception
	 * @return array a array contains sum and user object
	 */
	public static function getUserBalance($user_id, $token, $pdo = null) {
		if(!isset($pdo)){
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		}
		$user = User::find ( $user_id, $pdo, TRUE );
		
		$result = $user->getBalance($token, $pdo);
		$result['user'] = $user;
		return $result;
	}
	
	/**
	 * #PADC# 魔法石変更する時に実行します。
	 *
	 * @param number $gold 無料魔法石
	 * @param number $pgold　有料魔法石
	 * @param PDO $pdo
	 * @throws Exception
	 */
	public function onGoldChange($gold, $pgold, $token, $pdo){
		$gold_before = ( int ) $this->gold;
		$pgold_before = ( int ) $this->pgold;

		$this->pgold = $pgold;
		$this->gold = $gold;

		$gold_after = ( int ) $this->gold;
		$pgold_after = ( int ) $this->pgold;
		$add_gold = $pgold_after - $pgold_before;
		// only record added value.no minus value.the meaning of tp_gold is total pay gold
		if($add_gold > 0){
			$this->tp_gold += $add_gold;

			//检查玩家是否得到魔法转盘的机会
			UserGoldDialInfo::updateChance($this->id,$add_gold);

			/**compare user tp_gold with vip_cost to get player next vip level*/
			list($isLvUp, $levelCost) = $this->refreshVipLv($token);
			//add level changed tlog
			UserTlog::sendTlogVipLevel($this, $add_gold, $isLvUp, $levelCost);
			// 检查是否有累计充值活动
			$activities = Activity::getAllBy(array('del_flg' => 0,'activity_type' => Activity::ACTIVITY_TYPE_TOTAL_CHARGE));
			$isOpen = false;
			if($activities){
				foreach($activities as $activity) {
					// 如果在活动开启期间，则记录用户的购买数量
					if($activity->isEnabled(time())){
						$isOpen = true;
						break;
					}
				}
				if($isOpen){
					$this->tp_gold_period += $add_gold;
					$this->tp_period_access_at = static::timeToStr(time());
				}
			}
		}

		try {
			$pdo->beginTransaction ();
			$this->update ( $pdo );

			//#PADC#
			UserLogAddGold::log ( $this->id, UserLogAddGold::TYPE_PURCHASE, $gold_before, $gold_after, $pgold_before, $pgold_after, $this->device_type );
			// #PADC# Tlog
			UserTlog::sendTlogMoneyFlow ( $this, $pgold_after + $gold_after - $pgold_before - $gold_before, Tencent_Tlog::REASON_PURCHASE, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($gold_after - $gold_before), abs($pgold_after - $pgold_before) );
			// #PADC#
			if($add_gold > 0){
				$this->reportScore(Tencent_MsdkApi::SCORE_TYPE_PURCHASE, $token, $add_gold);
				
				// #PADC_DY# ----------begin----------
				$this->addChargeReward($add_gold, $token, $pdo); // 首充礼包 & 首充双倍 & 累计充值 & 单笔充值
				// #PADC_DY# -----------end-----------
			}else{
				$this->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
			}
			
			$pdo->commit ();

			// ios 月額課金調理に統一
			//if($this->device_type == UserDevice::TYPE_IOS){
			SubscriptionBonus::checkSubscription($this, $pgold_after - $pgold_before, $pdo);
			//}
			// 永久月卡
			// SubscriptionBonus::checkForeverSubscription($this, $pgold_after - $pgold_before, $pdo);
			// #PADC# PDOException → Exception
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
	}

	/**
	 * #PADC#
	 * whether or not vip level up
	 * @return boolean
	 */
	public function refreshVipLv($token){
		$level_up = false;
		$vip_costs = VipCost::getAllVipCosts();
		//if not have vip_costs data,no vip level up
		if(!empty($vip_costs)){
			$new_level = $this->vip_lv;
			for($lv = $new_level + 1; isset($vip_costs[$lv - 1]) && $this->tp_gold >= $vip_costs[$lv - 1]; $lv++);
			if($this->vip_lv < $lv - 1){
				$this->vip_lv = $lv - 1;
				//if user update his vip level,he can get double weekly bonus
				$this->last_vip_weekly = '0000-00-00 00:00:00';
				$level_up = true;
			}
		}
		$level_cost = isset($vip_costs[$this->vip_lv])? $vip_costs[$this->vip_lv] : 0;
                if ($level_up) {
                    $this->reportScore(Tencent_MsdkApi::SCORE_TYPE_VIP_LEVEL, $token, $this->vip_lv);
                }
		return array($level_up, $level_cost);
	}

	/**
	 * #PADC# 月額期間内か
	 *
	 * @return boolean
	 */
	public function duringSubscription(){
		return isset($this->tss_end) && time() < static::strToTime($this->tss_end);
	}

	/**
	 * #PADC# 今日の月額ボーナス受取ったか
	 *
	 * @return boolean
	 */
	public function isGetSubscriptionBonusToday(){
		return BaseModel::isSameDay_AM4(time(), BaseModel::strToTime($this->last_subs_daily));
	}

	/**
	 * #PADC# 今日の永久月額ボーナス受取ったか
	 *
	 * @return boolean
	 */
	public function isGetForeverSubscriptionBonusToday(){
		return BaseModel::isSameDay_AM4(time(), BaseModel::strToTime($this->last_forever_subs_daily));
	}

	/**
	 * #PADC# 魔法石を消費
	 */
	public function payGold($gold, $token, $pdo = null) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $this->id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];

		$paid = $this->addGold ( - $gold, $pdo );
		$gold_paid = $paid ['gold'] + $paid ['pgold'];

		if ($gold_paid > 0 && (Env::CHECK_TENCENT_TOKEN || $token ['check_tencent'] != null) && ($this->device_type == UserDevice::TYPE_ADR || Env::ENABLE_IOS_MIDAS)) {
			try {
				$pay_result = Tencent_MsdkApi::payGold ( $gold_paid, $openid, $token, $ptype, $this->device_type );
				return $pay_result['billno'];
			} catch ( Exception $e ) {
				$this->addPGold ( $paid ['pgold'] );
				$this->addGold ( $paid ['gold'], $pdo );
				if ($e instanceof MsdkConnectionException) {
					throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
				} else if ($e instanceof MsdkApiException) {
					throw new PadException ( RespCode::TENCENT_API_ERROR, $e->getMessage () );
				}
			}
		}
	}

	/**
	 * #PADC# 魔法石消費を取り消す
	 */
	public function cancelPay($gold, $billno, $token) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $this->id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];

		if ((Env::CHECK_TENCENT_TOKEN || $token ['check_tencent'] != null) && ($this->device_type == UserDevice::TYPE_ADR || Env::ENABLE_IOS_MIDAS)) {
			try {
				Tencent_MsdkApi::cancelPay ( $gold, $billno, $openid, $token, $ptype, $this->device_type );
			} catch ( MsdkConnectionException $e ) {
				throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
			} catch ( MsdkApiException $e ) {
				throw new PadException ( RespCode::TENCENT_API_ERROR, $e->getMessage () );
			}
		}
	}

	/**
	 * #PADC# 応用活動の資格を調べる
	 */
	public static function queryQualify($user_id, $token) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$type = $userDeviceData ['t'];

		if ((Env::CHECK_TENCENT_TOKEN || $token ['check_tencent'] != null) && ($type == UserDevice::TYPE_ADR || Env::ENABLE_IOS_MIDAS)) {
			try {
				$result = Tencent_MsdkApi::queryQualify ( $openid, $token, $ptype, $type );
			} catch ( MsdkConnectionException $e ) {
				throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
			} catch ( MsdkApiException $e ) {
				throw new PadException ( RespCode::TENCENT_API_ERROR, $e->getMessage () );
			}
		} else {
			$result = array ();
		}
		return $result;
	}

	/**
	 * #PADC# 無料魔法石付与
	 */
	public function presentGold($gold, $token, $pdo = null) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $this->id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$type = $userDeviceData['t'];

		if ((Env::CHECK_TENCENT_TOKEN || $token ['check_tencent'] != null) && ($type == UserDevice::TYPE_ADR || Env::ENABLE_IOS_MIDAS)) {
			try {
				Tencent_MsdkApi::present ( $openid, $token, $ptype, $type, $gold );
			} catch ( MsdkConnectionException $e ) {
				throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
			} catch ( MsdkApiException $e ) {
				throw new PadException ( RespCode::TENCENT_API_ERROR, $e->getMessage () );
			}
		}

		$this->addGold ( $gold, $pdo );
	}

	/**
	 * #PADC# フレンドopenidリスト取得
	 */
	public static function getTencentFriendsOpenIds($user_id, $token){
		$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);
		$openid	= $userDeviceData['oid'];
		$ptype	= $userDeviceData['pt'];

		if((Env::CHECK_TENCENT_TOKEN || $token ['check_tencent'] != null) && Env::CHECK_TENCENT_LOGIN){
			if($ptype == UserDevice::PTYPE_WECHAT && !Env::CHECK_TENCENT_WECHAT_FRIEND){
				return array();
			}
			try{
				return Tencent_MsdkApi::getFriendsOpenIds($openid, $ptype, $token);
			}catch(MsdkConnectionException $e){
				//throw new PadException(RespCode::TENCENT_NETWORK_ERROR, $e->getMessage());
			}catch(MsdkApiException $e){
				//throw new PadException(RespCode::TENCENT_API_ERROR, $e->getMessage());
			}
			return array();
		}else{
			return array();
		}
	}

	/**
	 * #PADC# スタミナプレセントを増加する
	 *
	 * @return boolean
	 */
	public function addPresentStamina(){
		if($this->getStamina() >= self::STAMINA_STOCK_MAX){
			return FALSE;
		}
		$this->addStamina( 1 );
		return TRUE;
	}

	/**
	 * ユーザーをオフラインさせます。
	 *
	 * @param number $user_id
	 */
	public static function offline($user_id){
		$redis = Env::getRedisForUser();
		$sessionKey = CacheKey::getUserSessionKey($user_id);
		$redis->delete($sessionKey);
		return;
	}

	/**
	 * ユーザーをオフラインさせます。
	 *
	 * @param number $user_id
	 */
	public static function kickOff($user_id){
		$redis = Env::getRedisForUser();
		$sessionKey = CacheKey::getUserSessionKey($user_id);
		$sessionValue = 'KICK_OFF';
		$redis->set($sessionKey, $sessionValue, 3600);
	}

	/**
	 *
	 * @return boolean
	 */
	public function isVipWeeklyBonusAvailable(){
		return !BaseModel::isSameWeek_AM4(BaseModel::strToTime($this->last_vip_weekly)) && $this->vip_lv >= 1;
	}
	
	/**
	 * #PADC# IDIP魔法石変更のリクエストがあれば、魔法石数を更新します。
	 * 
	 * @param array $token
	 * @param PDO $pdo
	 */
	public function checkGoldFromIDIP($token, $pdo){
		if($this->reserve_gold != 0){
			$gold_before=$this->gold + $this->pgold;
			$reserve_glod = abs($this->reserve_gold);
			if($this->reserve_gold > 0){
				$this->presentGold($reserve_glod, $token);
			}else{
				if($reserve_glod > $this->gold + $this->pgold){
					$reserve_glod = $this->gold + $this->pgold;
				}
				$this->payGold($reserve_glod, $token, $pdo);
			}
		
			$this->reserve_gold = 0;
			
			$this->update($pdo);
			
			$gold_after=$this->gold + $this->pgold;
			if($gold_before != $gold_after){
				UserTlog::sendTlogMoneyFlow($this, $gold_after - $gold_before, Tencent_Tlog::REASON_IDIP, Tencent_Tlog::MONEY_TYPE_DIAMOND, $gold_after - $gold_before );
				
				$this->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
			}
		}
	}
	
	/**
	 * 
	 * @param number $scoreType
	 * @param array $token
	 */
	public function reportScore($scoreType, $token, $data = 0){
		if(!Env::ENABLE_QQ_REPORT_SCORE){
			return;
		}
		
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $this->id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$type = $userDeviceData['t'];
		try{
			if($scoreType == Tencent_MsdkApi::SCORE_TYPE_LEVEL){
				Tencent_MsdkApi::reportLevel($openid, $this->lv, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_GOLD){
				Tencent_MsdkApi::reportGold($openid, $this->gold + $this->pgold, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_RANKING){
				// #PADC_DY# ----------begin----------
				// Tencent_MsdkApi::reportRankingScore($openid,  $this->clear_dungeon_cnt, $type, $ptype, $token);
				Tencent_MsdkApi::reportRankingScore($openid,  $this->lv, $type, $ptype, $token);
				// #PADC_DY# ----------end----------
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_LOGIN){
				Tencent_MsdkApi::reportLogin($openid,  time(), $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_SIGNUP){
				$cardNum = UserCard::countUserCards($this->id);
				// #PADC_DY# ----------begin----------
				// Tencent_MsdkApi::reportSignup($openid,  time(), $this->clear_dungeon_cnt, 0, $cardNum, $type, $ptype, $token);
				Tencent_MsdkApi::reportSignup($openid,  time(), $this->lv, 0, $cardNum, $type, $ptype, $token);
				// #PADC_DY# ----------end----------
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_PURCHASE){
				Tencent_MsdkApi::reportPurchase($openid,  $this->gold + $this->pgold, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_CARDS){
				Tencent_MsdkApi::reportCards($openid,  $data, $type, $ptype, $token);
                        // #PADC_DY# ----------begin----------
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_PRIVILEDGE_STARTUP){
				Tencent_MsdkApi::reportPriviledgeStartup($openid, time() , $type, $ptype, $token); //TODO change 0 to certain value
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_TEAM_POWER){
				Tencent_MsdkApi::reportTeamPower($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_IP_HARD){
				Tencent_MsdkApi::reportIpHardPlayedCount($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_IP_NORMAL){
				Tencent_MsdkApi::reportIpNormalPlayedCount($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_IP_EASY){
				Tencent_MsdkApi::reportIpEasyPlayedCount($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_IP_GACHA_SINGLE){
				Tencent_MsdkApi::reportGachaSingleCount($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_IP_GACHA_TEN){
				Tencent_MsdkApi::reportGachaTenCount($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_VIP_LEVEL){
				Tencent_MsdkApi::reportVipLevel($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_IP_HELL){
				//地狱级
				Tencent_MsdkApi::reportIpHellPlayedCount($openid, $data, $type, $ptype, $token);
			}else if($scoreType == Tencent_MsdkApi::SCORE_TYPE_IP_SUPERHELL){
				//超级地狱级
				Tencent_MsdkApi::reportIpSuperHellPlayedCount($openid, $data, $type, $ptype, $token);
			}
			// #PADC_DY# ----------end-----------
		}catch(MsdkConnectionException $e){
		}catch(MsdkApiException $e){
		}
	}
	
	/**
	 * 
	 * @param number $userId
	 * @param string $accessToken
	 */
	public static function reportUserCardNum($userId, $accessToken, $pdo = null){
		if(!Env::ENABLE_QQ_REPORT_SCORE){
			return;
		}
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $userId );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$type = $userDeviceData['t'];
		try{
			$cardNum = UserCard::countUserCards($userId, $pdo);
			Tencent_MsdkApi::reportCards($openid, $cardNum, $type, $ptype, array('access_token' => $accessToken));
		}catch(MsdkConnectionException $e){
		}catch(MsdkApiException $e){
		}
	}
	
	/**
	 * 
	 * @param array $token
	 * @param PDO $pdo
	 * @throws PadException
	 */
	public function getQqVip($token, $pdo = null, $isLogin = true){
		$user_id = $this->id;
		if(!$pdo){
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		}
		
		if(!Env::ENABLE_QQ_VIP){
			if($this->qq_vip != 0 || $this->qq_vip_expire != null || $this->qq_svip_expire != 0){
				$this->qq_vip = 0;
				$this->qq_vip_expire = 0;
				$this->qq_svip_expire = 0;
				$this->update($pdo);
			}
			return;
		}
		
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];

		$vip_result = false;
		try {
			$vip_result = Tencent_MsdkApi::getVipInfo($openid, $token, $ptype);
		} catch ( MsdkConnectionException $e ) {
			//throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
		} catch ( MsdkApiException $e ) {
			//throw new PadException ( RespCode::TENCENT_API_ERROR, $e->getMessage () );
		}
		
		if($vip_result && !$vip_result['is_lost']){
			$vip = User::QQ_ACCOUNT_NORMAL;
			if($vip_result['is_svip']){
				$vip = User::QQ_ACCOUNT_SVIP;
			}else if($vip_result['is_qq_vip']){
				$vip = User::QQ_ACCOUNT_VIP;
			}
			
			if(Env::ENABLE_QQ_VIP_DEBUG){
				$debug_vip = QqVipBonus::getDebugValue($this->id);
				if($debug_vip === User::QQ_ACCOUNT_NORMAL || $debug_vip == User::QQ_ACCOUNT_VIP || $debug_vip == User::QQ_ACCOUNT_SVIP ){
					$vip = $debug_vip;
					if($vip == 1){
						$vip_result['qq_vip_end'] = $this->qq_vip_expire + 1000;
					}
					if($vip == 2){
						$vip_result['qq_svip_end'] = $this->qq_svip_expire + 1000;
					}
				}
			}
			
			//test
			//$vip = User::QQ_ACCOUNT_SVIP;
			//$this->onQqVipPurchase(User::QQ_ACCOUNT_SVIP);

			if(!$isLogin){
				if($vip == User::QQ_ACCOUNT_SVIP && $vip_result['qq_svip_end'] > $this->qq_svip_expire){
					$this->onQqVipPurchase(User::QQ_ACCOUNT_SVIP);
				}
				if($vip == User::QQ_ACCOUNT_VIP && $vip_result['qq_vip_end'] > $this->qq_vip_expire){
					$this->onQqVipPurchase(User::QQ_ACCOUNT_VIP);
				}
			}

			$this->qq_vip = $vip;
			$this->qq_vip_expire = (int)$vip_result['qq_vip_end'];
			$this->qq_svip_expire = (int)$vip_result['qq_svip_end'];
			
			$this->update($pdo);
		}
	}
	
	/**
	 * 
	 * @param number $qq_vip
	 */
	private function onQqVipPurchase($qq_vip){
		if($qq_vip == User::QQ_ACCOUNT_SVIP){
			if(!($this->qq_vip_gift & User::QQ_SVIP_PURCHASE)){
				$this->qq_vip_gift |= User::QQ_SVIP_PURCHASE;
			}
		}else if($qq_vip == User::QQ_ACCOUNT_VIP){
			if(!($this->qq_vip_gift & User::QQ_VIP_PURCHASE)){
				$this->qq_vip_gift |= User::QQ_VIP_PURCHASE;
			}
		}
	}
	
	/**
	 * 
	 * @param array $token
	 * @param stirng $userip
	 * @param array $friends_ids
	 * @throws PadException
	 */
	public function getFriendVips($token, $userip = null, $pdo = null, $pdo_share = null, $friends_ids = null){
		if(!Env::ENABLE_QQ_VIP){
			return false;
		}
		
		$user_id = $this->id;
		
		if(!isset($userip)){
			if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$userip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			}else {
				$userip = $_SERVER["REMOTE_ADDR"];
			}
		}
		
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		
		if(!$friends_ids){
			$friends_ids = Friend::getFriendids($user_id, $pdo);
		}
		$fcnt = count($friends_ids);
		
		$fopenids = UserDevice::getOpenids($friends_ids, $pdo_share);
		$openids = array();
		foreach($fopenids as $uid => $oid){
			$openids[] = $oid;
		}
		
		$result = false;
		try {
			$result = Tencent_MsdkApi::getFriendsVipInfo($openid, $token, $userip, $openids, $ptype);
		} catch ( MsdkConnectionException $e ) {
			//throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
		} catch ( MsdkApiException $e ) {
			//throw new PadException ( RespCode::TENCENT_API_ERROR, $e->getMessage () );
		}
		
		if($result && !$result['is_lost'] && $fcnt == count($result['lists'])){
			//global $logger;
			//$logger->log('friends vip result:'.print_r($result, true), 7);
			$fvips = array();
			for($i = 0; $i < $fcnt; $i++){
				if($result['lists'][$i]['is_qq_svip']){
					$fvips[$friends_ids[$i]] = User::QQ_ACCOUNT_SVIP;
				}elseif($result['lists'][$i]['is_qq_vip']){
					$fvips[$friends_ids[$i]] = User::QQ_ACCOUNT_VIP;
				}else{
					$fvips[$friends_ids[$i]] = User::QQ_ACCOUNT_NORMAL;
				}
			}
			//$logger->log('$fvips:'.print_r($fvips, true), 7);
			return $fvips;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * @param number $user_id
	 * @param array $token
	 * @param string $userip
	 * @param PDO $pdo
	 * @param PDO $pdo_share
	 * @param array $friends_ids
	 */
	public static function getUserFriendVips($user_id, $token, $userip = null, $pdo = null, $pdo_share = null, $friends_ids = null){
		if(!$pdo){
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		$user = User::find($user_id, $pdo);
		return $user->getFriendVips($token, $userip, $pdo, $pdo_share, $friends_ids);
	}
	
	/**
	 * 
	 * @param number $bonus_type
	 * @return boolean
	 */
	public function checkQqVipBonusAvalible($bonus_type, $qq_vip = null){
		if($bonus_type == QqVipBonus::TYPE_QQ_VIP_LOGIN_BONUS){
			if($this->qq_vip < User::QQ_ACCOUNT_VIP){
				return User::QQ_VIP_BONUS_UNAVALIBLE;
			}else{
				return ($this->lqvdb_days != $this->li_days)? User::QQ_VIP_BONUS_AVALIBLE : User::QQ_VIP_BONUS_RECEIVED;
			}
		}else if($bonus_type == QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS){
			if($this->device_type == UserDevice::TYPE_IOS){
				return User::QQ_VIP_BONUS_UNAVALIBLE;
			}
			if($qq_vip == User::QQ_ACCOUNT_SVIP){
				if($this->qq_vip_gift & User::QQ_SVIP_PURCHASE_BONUS){
					return User::QQ_VIP_BONUS_RECEIVED;
				}else{
					return ($this->qq_vip == User::QQ_ACCOUNT_SVIP 
							&& $this->qq_vip_gift & User::QQ_SVIP_PURCHASE)? 
							User::QQ_VIP_BONUS_AVALIBLE : User::QQ_VIP_BONUS_UNAVALIBLE;
				}
			}else if($qq_vip == User::QQ_ACCOUNT_VIP){
				if($this->qq_vip_gift & User::QQ_VIP_PURCHASE_BONUS){
					return User::QQ_VIP_BONUS_RECEIVED;
				}else{
					return ($this->qq_vip == User::QQ_ACCOUNT_VIP
							&& $this->qq_vip_gift & User::QQ_VIP_PURCHASE)?
							User::QQ_VIP_BONUS_AVALIBLE : User::QQ_VIP_BONUS_UNAVALIBLE;
				}
			}else{
				return User::QQ_VIP_BONUS_UNAVALIBLE;
			}
		}else if($bonus_type == QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS){
			if($qq_vip == User::QQ_ACCOUNT_SVIP){
				if($this->qq_vip_gift & User::QQ_SVIP_NOVICE_BONUS){
					return User::QQ_VIP_BONUS_RECEIVED;
				}else{
					return ($this->qq_vip == User::QQ_ACCOUNT_SVIP) ? User::QQ_VIP_BONUS_AVALIBLE : User::QQ_VIP_BONUS_UNAVALIBLE;
				}
			}else if($qq_vip == User::QQ_ACCOUNT_VIP){
				if($this->qq_vip_gift & User::QQ_VIP_NOVICE_BONUS){
					return User::QQ_VIP_BONUS_RECEIVED;
				}else{
					return ($this->qq_vip == User::QQ_ACCOUNT_VIP) ? User::QQ_VIP_BONUS_AVALIBLE : User::QQ_VIP_BONUS_UNAVALIBLE;
				}
			}else{
				return User::QQ_VIP_BONUS_UNAVALIBLE;
			}
		}else{
			return User::QQ_VIP_BONUS_UNAVALIBLE;
		}
	}
	
	/**
	 * 
	 * @param number $bonus_type
	 * @param number $qq_vip
	 */
	public function setQqVipBonusReceived($bonus_type, $qq_vip){
		if($bonus_type == QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS){
			if($qq_vip == User::QQ_ACCOUNT_SVIP){
				$this->qq_vip_gift |= User::QQ_SVIP_PURCHASE_BONUS;
			}else if($qq_vip == User::QQ_ACCOUNT_VIP){
				$this->qq_vip_gift |= User::QQ_VIP_PURCHASE_BONUS;
			}
		}else if($bonus_type == QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS){
			if($qq_vip == User::QQ_ACCOUNT_SVIP){
				$this->qq_vip_gift |= User::QQ_SVIP_NOVICE_BONUS;
			}else if($qq_vip == User::QQ_ACCOUNT_VIP){
				$this->qq_vip_gift |= User::QQ_VIP_NOVICE_BONUS;
			}
		}
	}
    
    // #PADC_DY# ----------begin----------
	// 首充记录
    public function getFirstChargeRecord() {
        if(!empty($this->first_charge_record)) {
            return array_map('intval', json_decode($this->first_charge_record, true));
        } else {
            return array();
        }
    }

	// 增加首充记录
    public function addFirstChargeRecord($type) {
        $first_charge_record = $this->getFirstChargeRecord();
        if (!in_array($type, $first_charge_record)) {
            $first_charge_record[] = $type;
            $first_charge_record = array_unique($first_charge_record);
            sort($first_charge_record);
            $this->first_charge_record = json_encode($first_charge_record);
        }
    }
	
	// 首充礼包 & 累计充值 & 单笔充值
	public function addChargeReward($add_gold, $token, $pdo) {
		if($add_gold <= 0) {
			return false;
		}

		// 累计充值 & 每日充值 & 连续5日每日充值
		$activities = Activity::getAllBy(array('del_flg' => 0));
		foreach ($activities as $activity) {
			if (($activity->activity_type == Activity::ACTIVITY_TYPE_FIRST_CHARGE && $activity->isEnabled(time()) && $this->first_charge_gift_received == 0)
				|| ($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CHARGE && $activity->checkCondition($this->tp_gold_period, $this->vip_lv))
				|| ($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE && $activity->checkCondition($add_gold, $this->vip_lv))
				|| ($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED && $activity->checkCondition($this->count_p6, $this->vip_lv))) {
				$user_activity = UserActivity::findBy(array(
					'user_id' => $this->id,
					'activity_id' => $activity->id
				), $pdo);

				// 计算连续充值天数
				if($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE) {
					// 标志位表示是否是昨天
					$charge_ext = BaseModel::isSameDay_AM4(BaseModel::strToTime($this->last_p6_at), BaseModel::strToTime('-1 day'));
					if($user_activity){
						// 如果是昨天
						if($charge_ext){
							$this->last_p6_at = BaseModel::timeToStr(time());
							$this->count_p6 += 1;
						} else {
							// 如果不是昨天也不是今天，上次连续充值次数都重置为1
							if(!BaseModel::isSameDay_AM4(BaseModel::strToTime($this->last_p6_at), time())){
								$this->last_p6_at = BaseModel::timeToStr(time());
								$this->count_p6 = 1;
							}
						}
					} else {
						// 第一次充值超过6元
						$this->last_p6_at = BaseModel::timeToStr(time());
						$this->count_p6 = 1;
					}
					/* TODO 疑似存在bug的逻辑。
					if(!$user_activity && !$charge_ext) {
						$this->last_p6_at = BaseModel::timeToStr(time());
						$this->count_p6 = 1;
					} elseif($user_activity && $user_activity->status == UserActivity::STATE_NONE && $charge_ext) {
						$this->last_p6_at = BaseModel::timeToStr(time());
						$this->count_p6 += 1;
					}
					*/
				}

				if(!$user_activity || !in_array($user_activity->status, array(UserActivity::STATE_CLEAR, UserActivity::STATE_RECEIVED))) {
					UserActivity::updateStatus($this->id, $activity->id, UserActivity::STATE_CLEAR, $pdo);
				}
			}
		}

		$this->update($pdo);
	}

	// 兑换记录
	public function getExchangeRecord() {
		if(!empty($this->exchange_record)) {
			return array_map('intval', json_decode($this->exchange_record, true));
		} else {
			return array();
		}
	}

	// 是否已经售罄检查
	public function alreadySoldOut($product_id) {
		return in_array($product_id, $this->getExchangeRecord());
	}

	// 增加兑换记录
	public function addExchangeRecord($product_id) {
		$exchange_record = $this->getExchangeRecord();
		if (!in_array($product_id, $exchange_record)) {
			$exchange_record[] = $product_id;
			$exchange_record = array_unique($exchange_record);
			sort($exchange_record);
			$this->exchange_record = json_encode($exchange_record);
		}
	}

	// 刷新后情况购买记录
	public function emptyExchangeRecord(){
		$this->exchange_record = "";
	}

	// 增加玩家经验
	public function addVipExp($vip_exp){
		$this->tp_gold += $vip_exp;
	}
	// #PADC_DY# -----------end-----------
}
