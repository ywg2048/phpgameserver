<?php
/**
 * tlog
 */
require_once (LIB_DIR . '/Tencent/Tlog/Autoload.php');
class Tencent_Tlog {
	
	// ADDORREDUCE
	const ADD = 0;
	const REDUCE = 1;
	
	// ログの種類
	const LOG_TYPE_SERVERSTATE = 0;
	const LOG_TYPE_PLAYERREGISTER = 1; // ユーザ登録
	const LOG_TYPE_PLAYERLOGIN = 2; // ユーザログイン
	const LOG_TYPE_PLAYERLOGOUT = 3;
	const LOG_TYPE_MONEYFLOW = 4; // 通貨数変更
	const LOG_TYPE_ITEMFLOW = 5; // アイテム（欠片）数変更
	const LOG_TYPE_PLAYEREXPFLOW = 6; // 経験値変更(現状プレイヤー経験値が無くなる)
	const LOG_TYPE_SNSFLOW = 7; // SNSイヴェント
	const LOG_TYPE_ROUNDFLOW = 8; // ダンジョンクリア
	const LOG_TYPE_IDIPFLOW = 9; // IDIP動作
	const LOG_TYPE_DECKFLOW = 10; // デッキ変更
	const LOG_TYPE_SNEAKDUNGEON = 11; // ダンジョン潜入
	const LOG_TYPE_GUIDEFLOW = 12; // チュートリアル進捗
	const LOG_TYPE_MISSIONFLOW = 13; // ミッション
	const LOG_TYPE_SHARE = 14; // share
	const LOG_TYPE_MONTHLYREWARD = 15; // monthly reward
	const LOG_TYPE_COMPOSITE = 16; // cards composite
	const LOG_TYPE_EVOLUTION = 17; // cards evolution
	const LOG_TYPE_VIPLEVELUP = 18; // vip level up
	const LOG_TYPE_RANKING = 19; // ranking
	const LOG_TYPE_FAILEDSNEAK = 20;
	const LOG_TYPE_SEC_ROUND_START_FLOW = 21;
	const LOG_TYPE_SEC_ROUND_END_FLOW = 22;
	const LOG_TYPE_SEC_TALK_FLOW = 23;
	const LOG_TYPE_CHANGE_NAME = 24;
	const LOG_TYPE_MONTHLY_CARD = 25;
	const LOG_TYPE_LOG_ANTI_DATA = 26;
	const LOG_TYPE_EXCHANGE_ITEM = 27; // #PADC_DY#
	const LOG_TYPE_FOREVER_MONTHLY_CARD = 28; // 永久月卡
	const LOG_TYPE_AWAKESKILL = 29; // 玩家觉醒
	const LOG_TYPE_CARNIVAL_PRIZE_TYPE = 30;  //新手嘉年华的领取奖励时，对应的完成任务类型

	// iMoneyType
	const MONEY_TYPE_MONEY = 0; // コイン
	const MONEY_TYPE_DIAMOND = 1; // 魔法石
	const MONEY_TYPE_FRIEND_POINT = 2; // フレンドポイント
	const MONEY_TYPE_NONE   =3;
	                                   
	// 魔法石（コイン）変更原因
	const REASON_PURCHASE = 1; // 課金
	const REASON_DUNGEON = 2; // ダンジョンボーナス
	const REASON_BONUS = 3;
	const REASON_BUY_STAMINA = 4; // スタミナ購入
	const REASON_BUY_GACHA = 5; // ガチャ
	const REASON_BUY_CONTINUE = 6; // コンティニュ
	const REASON_BUY_DUNGEON = 7; // ダンジョン購入
	const REASON_SELL_PIECE = 8; // 欠片売却
	const REASON_PIECE_EVOLUTION = 9; // 進化
	const REASON_PIECE_COMPOSITE = 10; // 強化
	const REASON_IDIP = 11; // IDIPより付与
	const REASON_REQUEST_HELP = 12; // ヘルパー要求
	const REASON_HELP_USERS = 13; // ヘルパーになる
	const REASON_QQ_VIP_BONUS = 14;//QQ会員ボーナス
	const REASON_PIECE_ULTIMATE_EVOLUTION = 15;  //究极进化
	const REASON_AWAKE_SKILL = 16;  //技能觉醒
	const REASON_RESET_DUNGEON_TIMES = 17; // 重置关卡次数
	const REASON_EXCHANGE_REFRESH = 18; // 兑换所刷新


	//幸运转盘、魔法转盘、限时魔法商店、新手嘉年华
	const REASON_LUNCKY_DIAL_PRIZE     = 25;    //幸运转盘的奖励
	const REASON_LUNCKY_DIAL_RESET     = 26;    //幸运转盘的重置
	const REASON_LUNCKY_DIAL_TURN      = 27;    //每次幸运转盘的付费
	const REASON_GOLD_DIAL_TURN       = 28;    //每次魔法转盘的付费
	const REASON_MAGICSTONE_SHOP_EXCHANGE = 29; //限时魔法石商店的交易
	const REASON_MAGICSTONE_SHOP_REFRESH  = 30; //限时魔法石商店的刷新
	const REASON_CARNIVAL_MISSION_FINISHED= 31; //完成嘉年华的任务

	// #PADC_DY# ----------begin----------
	const REASON_ACTIVITY_BONUS = 26; // 月卡奖励

	// #PADC_DY# -----------end-----------
	const SUBREASON_LOGIN_BONUS = 301; // ログインボーナス
	const SUBREASON_MAIL_BONUS = 302; // メールボーナス
	const SUBREASON_VIP_LV_UP_BONUS = 303; // VIPレベルアップボーナス
	const SUBREASON_VIP_WEEKLY_BONUS = 304; // VIPウィークリーボーナス
	const SUBREASON_MISSION_BONUS = 305; // ミッションボーナス
	const SUBREASON_SUBSCRIPTION_BONUS = 306; // 月額課金デーリーボーナス
	const SUBREASON_BUY_FRIEND_GACHA = 501;
	const SUBREASON_BUY_RARE_GACHA = 502;
	const SUBREASON_BUY_PREMIUM_GACHA = 503;
	const SUBREASON_BUY_EXTRA_RARE_GACHA = 504;
	const SUBREASON_BUY_EXTRA_PREMIUM_GACHA = 505;
	const SUBREASON_QQ_VIP_PURCHASE_BONUS = 1401;
	const SUBREASON_QQ_VIP_NOVICE_BONUS = 1402;
	const SUBREASON_QQ_SVIP_PURCHASE_BONUS = 1403;
	const SUBREASON_QQ_SVIP_NOVICE_BONUS = 1404;
	// #PADC_DY# ----------begin----------
	const SUBREASON_BUY_SNEAK_DUNGEON_COUNT = 102; // #PADC_DY# 购买关卡挑战次数限制
	const SUBREASON_BUY_EXCHANGE_REFRESH = 103; // #PADC_DY# 刷新交易所扣钻石.

	// 活动相关
	const SUBREASON_FIRST_BUY_GIFT = 2601; // 首充礼包
    const SUBREASON_FIRST_BUY_DOUBLE = 2602; // 首充双倍
    const SUBREASON_SHARE_GIFT = 2603; // 分享奖励
    const SUBREASON_1YG = 2604; // 1元购
    const SUBREASON_TOTAL_CHARGE_STEP1 = 2605; // 累计充值1阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP2 = 2606; // 累计充值2阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP3 = 2607; // 累计充值3阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP4 = 2608; // 累计充值4阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP5 = 2609; // 累计充值5阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP6 = 2610; // 累计充值6阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP7 = 2611; // 累计充值7阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP8 = 2612; // 累计充值8阶段奖励
    const SUBREASON_TOTAL_CHARGE_STEP9 = 2613; // 累计充值9阶段奖励

    const SUBREASON_DAILY_CHARGE_DAY1 = 2614; // 每日充值连续1天
    const SUBREASON_DAILY_CHARGE_DAY2 = 2615; // 每日充值连续2天
    const SUBREASON_DAILY_CHARGE_DAY3 = 2616; // 每日充值连续3天
    const SUBREASON_DAILY_CHARGE_DAY4 = 2617; // 每日充值连续4天
    const SUBREASON_DAILY_CHARGE_DAY5 = 2618; // 每日充值连续5天

    const SUBREASON_DAILY_CHARGE_EXTENDED = 2619; // 连续每日充值

    const SUBREASON_TOTAL_CONSUM_STEP1 = 2620; // 累计消费1阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP2 = 2621; // 累计消费2阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP3 = 2622; // 累计消费3阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP4 = 2623; // 累计消费4阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP5 = 2624; // 累计消费5阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP6 = 2625; // 累计消费6阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP7 = 2626; // 累计消费7阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP8 = 2627; // 累计消费8阶段奖励
    const SUBREASON_TOTAL_CONSUM_STEP9 = 2628; // 累计消费9阶段奖励

    const SUBREASON_POWER_STEP1 = 2629; // 战斗力达成1阶段奖励
    const SUBREASON_POWER_STEP2 = 2630; // 战斗力达成2阶段奖励
    const SUBREASON_POWER_STEP3 = 2631; // 战斗力达成3阶段奖励
    const SUBREASON_POWER_STEP4 = 2632; // 战斗力达成4阶段奖励
    const SUBREASON_POWER_STEP5 = 2633; // 战斗力达成5阶段奖励
    const SUBREASON_DAILY_LOGIN = 2634; // 每日登陆
    const SUBREASON_MONTHCARD = 2635; // 月卡奖励

	const SUBREASON_EXCHANGE_ITEM = 2636; // 兑换物品


	// SNS動作種類
	const SNSTYPE_SHOWOFF = 0; // 自慢
	const SNSTYPE_INVITE = 1; // フレンド招待
	const SNSTYPE_ACCEPT_INVITE = 2; // フレンド招待を受け入れる
	const SNSTYPE_REFUSE_INVITE = 3; // フレンド招待を断る
	const SNSTYPE_SENDHEART = 4; // ハート送る
	const SNSTYPE_RECEIVEHEART = 5; // ハート受取る
	const SNSTYPE_SENDEMAIL = 6; // メール送信
	const SNSTYPE_RECEIVEEMAIL = 7; // メール受信
	const SNSTYPE_SHARE = 8; // シェル
	const SNSTYPE_HELP = 9; // 助ける
	const SNSTYPE_OTHER = 10; // その他

	// アイテムタイプ
	const GOOD_TYPE_PIECE = 1; // 欠片
	const GOOD_TYPE_CARD = 2; // モンスターカード

	// アイテム変更原因
	const ITEM_REASON_BONUS = 1; // メール、ログインのボーナス
	const ITEM_REASON_DUNGEON = 2; // ダンジョン
	const ITEM_REASON_SELL = 3; // 売却
	const ITEM_REASON_EVOLUTION = 4; // 進化
	const ITEM_REASON_COMPOSITE = 5; // 強化
	const ITEM_REASON_GACHA = 6; // ガチャ
	const ITEM_REASON_QQ_VIP_BONUS = 7;//QQ会員ボーナス
	const ITEM_REASON_ACTIVITY_BONUS = 26; //活动奖励
	const ITEM_REASON_ULTIMATE_EVOLUTION = 27;  //究极进化
	const ITEM_REASON_AWAKE_SKILL = 28;  //技能觉醒
	const ITEM_REASON_EXCHANGE_ITEM = 29; // 兑换所

	const ITEM_SUBREASON_MAIL_BONUS = 101;
	const ITEM_SUBREASON_VIP_LV_UP_BONUS = 102;
	const ITEM_SUBREASON_VIP_WEEKLY_BONUS = 103;
	const ITEM_SUBREASON_MISSION_BONUS = 104;
	const ITEM_SUBREASON_BUY_FRIEND_GACHA = 601;
	const ITEM_SUBREASON_BUY_RARE_GACHA = 602;
	const ITEM_SUBREASON_BUY_PREMIUM_GACHA = 603;
	const ITEM_SUBREASON_BUY_EXTRA_RARE_GACHA = 604;
	const ITEM_SUBREASON_BUY_EXTRA_PREMIUM_GACHA = 605;
	const ITEM_SUBREASON_QQ_VIP_PURCHASE_BONUS = 701;
	const ITEM_SUBREASON_QQ_VIP_NOVICE_BONUS = 702;
	const ITEM_SUBREASON_QQ_SVIP_PURCHASE_BONUS = 703;
	const ITEM_SUBREASON_QQ_SVIP_NOVICE_BONUS = 704;

	// 活动相关
	const ITEM_SUBREASON_FIRST_BUY_GIFT = 2601; // 首充礼包
	const ITEM_SUBREASON_FIRST_BUY_DOUBLE = 2602; // 首充双倍
	const ITEM_SUBREASON_SHARE_GIFT = 2603; // 分享奖励
	const ITEM_SUBREASON_1YG = 2604; // 1元购
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP1 = 2605; // 累计充值1阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP2 = 2606; // 累计充值2阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP3 = 2607; // 累计充值3阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP4 = 2608; // 累计充值4阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP5 = 2609; // 累计充值5阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP6 = 2610; // 累计充值6阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP7 = 2611; // 累计充值7阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP8 = 2612; // 累计充值8阶段奖励
	const ITEM_SUBREASON_TOTAL_CHARGE_STEP9 = 2613; // 累计充值9阶段奖励

	const ITEM_SUBREASON_DAILY_CHARGE_DAY1 = 2614; // 每日充值连续1天
	const ITEM_SUBREASON_DAILY_CHARGE_DAY2 = 2615; // 每日充值连续2天
	const ITEM_SUBREASON_DAILY_CHARGE_DAY3 = 2616; // 每日充值连续3天
	const ITEM_SUBREASON_DAILY_CHARGE_DAY4 = 2617; // 每日充值连续4天
	const ITEM_SUBREASON_DAILY_CHARGE_DAY5 = 2618; // 每日充值连续5天

	const ITEM_SUBREASON_DAILY_CHARGE_EXTENDED = 2619; // 连续每日充值

	const ITEM_SUBREASON_TOTAL_CONSUM_STEP1 = 2620; // 累计消费1阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP2 = 2621; // 累计消费2阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP3 = 2622; // 累计消费3阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP4 = 2623; // 累计消费4阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP5 = 2624; // 累计消费5阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP6 = 2625; // 累计消费6阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP7 = 2626; // 累计消费7阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP8 = 2627; // 累计消费8阶段奖励
	const ITEM_SUBREASON_TOTAL_CONSUM_STEP9 = 2628; // 累计消费9阶段奖励

	const ITEM_SUBREASON_POWER_STEP1 = 2629; // 战斗力达成1阶段奖励
	const ITEM_SUBREASON_POWER_STEP2 = 2630; // 战斗力达成2阶段奖励
	const ITEM_SUBREASON_POWER_STEP3 = 2631; // 战斗力达成3阶段奖励
	const ITEM_SUBREASON_POWER_STEP4 = 2632; // 战斗力达成4阶段奖励
	const ITEM_SUBREASON_POWER_STEP5 = 2633; // 战斗力达成5阶段奖励
	const ITEM_SUBREASON_DAILY_LOGIN = 2634; // 每日登陆
	const ITEM_SUBREASON_MONTHCARD = 2635; // 月卡奖励

	const ITEM_SUBREASON_EXCHANGE_ITEM = 2636; // 兑换

	// battle type
	const BATTLE_TYPE_NORMAL = 0; // ノーマルダンジョン
	const BATTLE_TYPE_SPC = 1; // スペシャルダンジョン
	const BATTLE_TYPE_UNLOCK = 2; // テクニカルダンジョン
	const BATTLE_TYPE_RANKING = 3; // テクニカルダンジョン
	const BATTLE_TYPE_IP = 4; // #PADC_DY# IP关卡
	                               
	// battle result
	const BATTLE_RESULT_SUCCESS = 1;
	const BATTLE_RESULT_FAILURE = 0;
	
	// guide
	const GUIDE_START = 1;
	const GUIDE_BATTLE2_START = 2;
	const GUIDE_BATTLE3_START = 3;
	const GUIDE_RESULT = 4;
	const GUIDE_INPUT_NAME = 5;
	const GUIDE_SNEAK_DUNGEON_2 = 6;
	const GUIDE_CLEAR_DUNGEON_2 = 7;
	const GUIDE_COMPOSITE = 8;
	const GUIDE_EVOLUTION = 9;
	const GUIDE_GACHA = 10;
	
	// share type
	const SHARE_TYPE_CREATE_CARD = 0;
	const SHARE_TYPE_CARD_EVOLUTION = 1;
	const SHARE_TYPE_DUNGEON_FIRST_CLEAR = 2;
	
	// level up reason
	const LEVEL_REASON_DUNGEON = 1;
	const LEVEL_REASON_IDIP = 2;
	
	// 出力先
	private static $udp_writer = null;
	private static $file_writer = null;
	
	// シーケンス
	private static $sequence = null;
	
	/**
	 * 初期化
	 *
	 * @param int $server_id
	 *        	使用サーバ番号
	 * @param int $zone_id
	 *        	エリアID
	 */
	public static function init($server_id = 0, $zone_id = 0) {
		TlogBase::setGameServerId ( $server_id );
		TlogBase::setZoneId ( $zone_id );
	}
	
	/**
	 *
	 * @param unknown $host        	
	 * @param unknown $port        	
	 */
	public static function setServer($host, $port) {
		static::$udp_writer = new TlogUdpWriter ( $host, $port );
	}
	
	/**
	 * ログファイルを設定します
	 *
	 * @param string $logfile        	
	 */
	public static function setLogFile($logfile) {
		static::$file_writer = new TlogFileWriter ( $logfile );
	}
	
	/**
	 *
	 * @param number $tlog_type        	
	 * @param array $params        	
	 */
	public static function generateMessage($tlog_type, $params) {
		$msg = '';
		switch ($tlog_type) {
			case self::LOG_TYPE_SERVERSTATE :
				$msg = TlogGameSvrState::generateMessage ();
				break;
			case self::LOG_TYPE_PLAYERREGISTER :
				$msg = TlogPlayerRegister::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['cid'],$params['did']);
				break;
			case self::LOG_TYPE_PLAYERLOGIN :
				$msg = TlogPlayerLogin::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['lv'], $params ['fcnt'], $params ['cv'], $params ['cid'], $params ['vip_lv'], $params ['subs'], $params ['gold'], $params ['coin'], $params['ten_gc'], $params['did']);
				break;
			case self::LOG_TYPE_PLAYERLOGOUT :
				$msg = TlogPlayerLogout::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['onlineTime'], $params ['level'], $params ['playerFriendsNum'], $params ['clientVersion'], $params ['loginChannel'], $params ['DeviceId'], $params ['VipLevel'], $params ['MonthlyFee'], $params ['PlayerDiamonds'], $params ['PlayerMoney'] );
				break;
			case self::LOG_TYPE_MONEYFLOW :
				$msg = TlogMoneyFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['lv'], $params ['m'], $params ['r'], $params ['aor'], $params ['mt'], $params ['am'], $params['gold_free'], $params['gold_buy'], isset ( $params ['seq'] ) ? $params ['seq'] : 0, isset ( $params ['sr'] ) ? $params ['sr'] : 0, isset($params['round']) ? $params['round'] : 0, isset($params['gid']) ? $params['gid'] : null, isset($params['mission_id']) ? $params['mission_id'] : null );
				break;
			case self::LOG_TYPE_ITEMFLOW :
				$msg = TlogItemFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['lv'], $params ['gt'], $params ['gid'], $params ['c'], $params ['ac'], $params ['r'], $params ['sr'], $params ['m'], $params ['mt'], $params ['aor'], $params ['ra'], isset ( $params ['seq'] ) ? $params ['seq'] : 0, isset($params['round']) ? $params['round'] : 0, isset($params['mid']) ? $params['mid'] : 0 );
				break;
			case self::LOG_TYPE_PLAYEREXPFLOW :
				$msg = TlogPlayerExpFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['ec'], $params ['blv'], $params ['alv'], $params ['time'], $params ['r'], $params ['sr'] );
				break;
			case self::LOG_TYPE_SNSFLOW :
				$msg = TlogSnsFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['aoid'], $params ['c'], $params ['st'], $params ['toid'] );
				break;
			case self::LOG_TYPE_ROUNDFLOW :
				$msg = TlogRoundFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['bid'], $params ['bt'], $params ['rs'], $params ['rt'], $params ['res'], $params ['r'], $params ['g'], $params ['ct'], $params ['ssdk'], $params ['st'], $params ['mcn'], $params ['acn'], isset($params['round']) ? $params['round'] : 0, isset($params['diamond']) ? $params['diamond'] : 0, isset($params['star']) ? $params['star'] : 0 );
				break;
			case self::LOG_TYPE_IDIPFLOW :
				$msg = TlogIDIPFlow::generateMessage ( $params['area'], $params ['oid'], $params ['item_id'], $params ['item_num'], $params ['serial'], $params ['source'], $params ['cmd'], $params ['uuid'] );
				break;
			case self::LOG_TYPE_DECKFLOW :
				$msg = TlogDeckFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['decks'], $params ['totalPower'] );
				break;
			case self::LOG_TYPE_SNEAKDUNGEON :
				$msg = TlogSneakDungeon::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['bid'], $params ['btype'], $params ['deck'], $params ['tp'], $params ['rt'], $params ['rtn'], $params ['foid'], $params ['ssdk'], $params ['lv'], $params ['vip_lv'], $params ['st'], $params ['us'] );
				break;
			case self::LOG_TYPE_GUIDEFLOW :
				$msg = TlogGuideFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['gid'], 0, 0, $params['fullv'] );
				break;
			case self::LOG_TYPE_MISSIONFLOW :
				$msg = TlogMissionFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['mission_id'], $params ['user_lv'], $params ['vip_lv'] );
				break;
			case self::LOG_TYPE_SHARE :
				$msg = TlogShareFlow::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['share_type'], $params ['dungeon_id'], $params ['card_id'], $params ['user_lv'], $params ['vip_lv'] );
				break;
			case self::LOG_TYPE_MONTHLYREWARD :
				$msg = TlogMonthlyReward::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['user_lv'], $params ['vip_lv'], $params ['gold'] );
				break;
			case self::LOG_TYPE_COMPOSITE :
				$msg = TlogComposite::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['card_id'], $params ['piece_id'], $params ['card_lv'], $params ['after_card_lv'], $params ['lv'], $params ['vip_lv'], $params ['piece_num'], $params ['generic_use'], $params ['generic_num'], $params ['money'], $params ['hp'], $params ['attack'], $params ['recover'] );
				break;
			case self::LOG_TYPE_EVOLUTION :
				$msg = TlogEvolution::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['card_id'], $params ['after_card_id'], $params ['card_attribute'], $params ['lv'], $params ['vip_lv'], $params ['piece_num'], $params ['generic_use'], $params ['generic_num'], $params ['generic_piece_attribute'], $params ['money'] );
				break;
			case self::LOG_TYPE_VIPLEVELUP :
				$msg = TlogVipLevelUp::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['user_lv'], $params ['vip_lv'],$params['GameName'],$params['AddExp'],$params['IsLvUp'],$params['VipExp'], $params['LvUpExp']);
				break;
			case self::LOG_TYPE_RANKING :
				$msg = TlogRanking::generateMessage ( $params ['appid'], $params ['t'], $params ['dungeonId'], $params ['rankingId'], $params ['timeStamp'], $params ['userData1'], $params ['userData2'], $params ['userData3'], $params ['userData4'], $params ['userData5'] );
				break;
			case self::LOG_TYPE_FAILEDSNEAK :
				$msg = TlogFailedSneak::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['dungeonId'], $params ['sneakTime'] );
				break;
			case self::LOG_TYPE_SEC_ROUND_START_FLOW:
				$msg = TlogSecRoundStartFlow::generateMessage ($params);
				break;
			case self::LOG_TYPE_SEC_ROUND_END_FLOW:
				$msg = TlogSecRoundEndFlow::generateMessage ($params);
				break;
			case self::LOG_TYPE_SEC_TALK_FLOW:
				$msg = TlogSecTalkFlow::generateMessage ($params);
				break;
			case self::LOG_TYPE_CHANGE_NAME:
				$msg = TlogChangeName::generateMessage( $params['appid'], $params['t'], $params['oid'], $params['bn'], $params['an']);
				break;
			case self::LOG_TYPE_MONTHLY_CARD:
				$msg = TlogMonthlyCard::generateMessage($params['appid'], $params['t'], $params['oid'], $params['et']);
				break;
			case self::LOG_TYPE_LOG_ANTI_DATA:
				$msg = TlogLogAntiData::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['ssdk'], $params ['seq'] );
				break;
			case self::LOG_TYPE_EXCHANGE_ITEM:
				$msg = TlogExchangeItem::generateMessage ( $params ['appid'], $params ['t'], $params ['oid'], $params ['m'], $params ['et'] );
				break;
			case self::LOG_TYPE_AWAKESKILL:
				$msg = 	TlogAwakeSkill::generateMessage($params['appid'],$params['t'],$params['oid'],$params['Level'],$params['VipLevel'],$params['awake_piece_id'],$params['card_id'],$params['ps_id'],$params['awake_skill_piece_num'],$params['coin'],$params['user_card_lv']);
				break;
			case self::LOG_TYPE_CARNIVAL_PRIZE_TYPE:
				$msg = TlogCarnivalPrize::generateMessage($params ['appid'],$params ['t'],$params ['oid'],$params['prizeid'],$params['mtype'],$params['carnivalDesc']);
				break;
		}
		return $msg;
	}
	
	/**
	 * ログ出力
	 *
	 * @param string $msg        	
	 */
	public static function send($msg) {
		if (isset ( static::$udp_writer )) {
			static::$udp_writer->write ( $msg );
		}
		if (isset ( static::$file_writer )) {
			static::$file_writer->write ( $msg );
		}
	}
	
	/**
	 * シーケンス取得
	 *
	 * @param number $user_id        	
	 * @return number
	 */
	public static function getSequence($user_id) {
		if (! isset ( self::$sequence )) {
			self::$sequence = (($user_id & 0x7fff) << 16) + (time () & 0xffff);
		}
		return self::$sequence;
	}
	
	/**
	 */
	public static function getTlogClassNames() {
		$classNames = array (
				TlogGameSvrState::class ,
				TlogPlayerRegister::class,
				TlogPlayerLogin::class,
				TlogPlayerLogout::class,
				TlogMoneyFlow::class,
				TlogItemFlow::class,
				TlogPlayerExpFlow::class,
				TlogSnsFlow::class,
				TlogRoundFlow::class,
				TlogIDIPFlow::class,
				TlogDeckFlow::class,
				TlogSneakDungeon::class,
				TlogGuideFlow::class,
				TlogMissionFlow::class,
				TlogShareFlow::class,
				TlogMonthlyReward::class,
				TlogComposite::class,
				TlogEvolution::class,
				TlogVipLevelUp::class,
				TlogRanking::class,
				TlogFailedSneak::class,
				TlogSecRoundStartFlow::class,
				TlogSecRoundEndFlow::class,
				TlogSecTalkFlow::class,
				TlogChangeName::class,
				TlogMonthlyCard::class,
				TlogLogAntiData::class,
				TlogExchangeItem::class,
				TlogAwakeSkill::class
		);
		return $classNames;
	}
}
