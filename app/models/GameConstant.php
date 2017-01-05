<?php
/**
 * ゲーム定数.
 */

class GameConstant extends BaseMasterModel {
	const TABLE_NAME = "game_constants";
	const VER_KEY_GROUP = "const";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// 一部のゲーム定数を固定値で持つ.
	const SELL_PRICE_LEVEL = 0.10;
	const BEAT_COIN_LEVEL = 0.50;
	const BEAT_EXP_LEVEL = 0.50;
	const COMPOSITE_EXP_LEVEL = 0.25;
	const COMPOSITE_COST_LEVEL = 1.00;
	const COMPOSITE_SKILL_UP_PROB = 1000;
	const COMPOSITE_SAME_ATTR_BONUS = 1.50;
	const COMPOSITE_GOOD_BONUS = 1.50;
	const COMPOSITE_GOOD_BONUS_PROB = 1000;
	const COMPOSITE_EXCELLENT_BONUS = 2.00;
	const COMPOSITE_EXCELLENT_BONUS_PROB = 100;
	const FRIEND_POINT_FOR_FRIEND = 10;
	const FRIEND_POINT_FOR_NON_FRIEND = 5;
	// #PADC# ----------begin----------
	const FRIEND_POINT_FOR_SNS_FRIEND = 20;
	// #PADC# ----------end----------
	const FRIEND_GACHA_PRICE = 200;
	const COMPOSITE_PLUS_COIN_LEVEL = 1000;
	const EVOLUTION_PLUS_COIN_LEVEL = 0;
	const SELL_PLUS_COIN_LEVEL = 10000;
	const PLUS_HP = 4000;
	const PLUS_ATK = 4000;
	// #PADC# ----------begin----------
	const PLUS_REC = 4000;
	// #PADC# ----------end----------
	const DUNG_PLUS_DROP = 10000;
	const FRI_PLUS_DROP = 500;
	const RARE_PLUS_DROP = 3000;
	const PRES_PLUS_DROP = 10000;
	const MEDAL_FOR_FRIEND = 200;
	const MEDAL_FOR_NON_FRIEND = 100;
	const W_LOGIN_STREAK_MEDAL = 1000;

	// #PADC# ----------begin----------
	const PIECE_COMPOSITE_EXP = 1.00;
	const PIECE_COMPOSITE_COST_EXP = 0.001;
	const PIECE_COMPOSITE_SAME_ATTR_BONUS = 1.50;
	const PIECE_COMPOSITE_GOOD_BONUS = 1.50;
	const PIECE_COMPOSITE_GOOD_BONUS_PROB = 1000;
	const PIECE_COMPOSITE_EXCELLENT_BONUS = 2.00;
	const PIECE_COMPOSITE_EXCELLENT_BONUS_PROB = 100;
	const PIECE_COMPOSITE_PLUS_COIN_LEVEL = 1000;
	const PIECE_EVOLUTION_PLUS_COIN_LEVEL = 0;

	const PRESENT_MAX_SEND_PER_DAY = 0;//0:制限なし
	const PRESENT_SEND_MIN_LEVEL = 0;//今は制限なし
	const PRESENT_DAY_SWITCH_TIME = '04:00:00';
	const PRESENT_RECEIVE_EXPIRE = 86400; // 1day

	const TUTORIAL_DUNGEON_COUNT = 2; // チュートリアルダンジョン数

	const TENCENT_BONUS_EXPIRE = 259200; //3day

	const PIECE_COMPOSITE_RARE_BASE_COST = 200;
	const PIECE_COMPOSITE_RARE_COST = 3500;
	const PIECE_COMPOSITE_RARE_COST_POW_INDEX = 3;
	const PIECE_COMPOSITE_MLV_BASE_COST = 200;
	const PIECE_COMPOSITE_MLV_COST = 3500;
	const PIECE_COMPOSITE_MLV_COST_POW_INDEX = 3;

	const PIECE_EVOLUTION_RARE_BASE_COST = 200;
	const PIECE_EVOLUTION_RARE_COST = 5000;
	const PIECE_EVOLUTION_RARE_COST_POW_INDEX = 4;
	const PIECE_EVOLUTION_MLV_BASE_COST = 200;
	const PIECE_EVOLUTION_MLV_COST = 5000;
	const PIECE_EVOLUTION_MLV_COST_POW_INDEX = 3;
	const PIECE_EVOLUTION_COST_EXP = 0.25;

	const RANKING_PARTICIPATE_FLOOR_ID = 1001;// ランキングダンジョンが開放される条件のダンジョンフロアID
	const DUNGEON_SALE_OPEN_CLEAR_COUNT = 21;// 購入ダンジョンが開放されるダンジョンクリア数
	
	const QQ_COIN_BONUS = 500;	// QQ会員コイン増加ボーナス割合
	const QQ_EXP_BONUS  = 500;	// QQ会員経験値増加ボーナス割合
	const QQ_VIP_COIN_BONUS = 1000;	// 超級QQ会員コイン増加ボーナス割合
	const QQ_VIP_EXP_BONUS  = 1000;	// 超級QQ会員経験値増加ボーナス割合
	
	//ゲームセンターログインボーナス
	const GAME_CENTER_LOGIN_BONUS_ID = 9900;//コイン
	const GAME_CENTER_LOGIN_BONUS_AMOUNT = 1000;
	
	const GAME_CENTER_COIN_BONUS = 1200; //12%
	// #PADC# ----------end----------
    const GOLD_TO_MONEY_RATE = 10; // #PADC_DY# 金额与魔法石转换比例
	const EXCHANGE_PRODUCT_TYPE1 = 1;
	const EXCHANGE_PRODUCT_TYPE2 = 2;
	const EXCHANGE_PRODUCT_TYPE3 = 3;
	const EXCHANGE_PRODUCT_TYPE4 = 4;
	const EXCHANGE_PRODUCT_TYPE5 = 5;
	const EXCHANGE_PRODUCT_TYPE6 = 6;
	const EXCHANGE_PRODUCT_TYPE7 = 7;
	const EXCHANGE_PRODUCT_TYPE8 = 8;

	protected static $columns = array(
		'id',
		'name',
		'value'
	);

	// パラメタキー名とID, 型との関連.
	// 型は settype で有効な文字列を指定すること. http://www.php.net/manual/ja/function.settype.php
	protected static $params = array(
		// 売却　価格レベル補正係数
		'SellPriceLevel' => array(1, "float"),
		// 撃破　コインレベル補正係数
		'BeatCoinLevel' => array(2, "float"),
		// 撃破　経験値レベル補正係数
		'BeatExpLevel' => array(3, "float"),
		// 合成　経験値レベル補正係数
		'CompositeExpLevel' => array(4, "float"),
		// 合成　費用レベル補正係数
		'CompositeCostLevel' => array(5, "float"),
		// 合成　スキルアップ確率
		'CompositeSkillUpProb' => array(6, "int"),
		// 合成　同属性合成ボーナス係数
		'CompositeSameAttrBonus' => array(7, "float"),
		// 合成　成功ボーナス係数
		'CompositeGoodBonus' => array(8, "float"),
		// 合成　成功ボーナス確率
		'CompositeGoodBonusProb' => array(9, "int"),
		// 合成　大成功ボーナス係数
		'CompositeExcellentBonus' => array(10, "float"),
		// 合成　大成功ボーナス確率
		'CompositeExcellentBonusProb' => array(11, "int"),
		// フレ　友情ポイント加算値
		'FriendPointForFriend' => array(12, "int"),
		// フレ　非フレンドの友情ポイント加算値
		'FriendPointForNonFriend' => array(13, "int"),
		// フレ　友情ガチャ一回の値段
		'FriendGachaPrice' => array(14, "int"),
		// お知らせメッセージ
		'NoticeMessage' => array(15, "string"),
		// ガチャ告知
		'GachaMessage' => array(16, "string"),
        // 2012/4/2 ＋卵機能追加対応
		// ＋合成時　コイン倍率
		'CompositePlusCoinLevel' => array(18,"int"),
		// ＋進化時　コイン倍率
		'EvolutionPlusCoinLevel' => array(19, "int"),
		// ＋売却時　コイン倍率
		'SellPlusCoinLevel' => array(20, "int"),
		// ＋卵HP割り振り確率
		'PlusHP' => array(21, "int"),
		// ＋卵ATK割り振り確率
		'PlusATK' => array(22, "int"),
		// ダンジョン内＋ドロップ率
		'DungPlusDrop' => array(24, "int"),
		// 友情ガチャ＋ドロップ率
		'FrigPlusDrop' => array(25, "int"),
		// レアガチャ＋ドロップ率
		'RarePlusDrop' => array(26, "int"),
		// プレゼントガチャ＋ドロップ率
		'PresPlusDrop' => array(27, "int"),
		// アカバンメッセージ
		'BanMessage' => array(28, "string"),
		// アップデートメッセージ
		'UpdateMessage' => array(29, "string"),
		// 通算ログインボーナスメッセージ
		'LoginTotalCountBonusMessage' => array(30, "string"),
		// アカウント削除済みメッセージ
		'DelMessage' => array(31, "string"),
		// アカウント凍結中メッセージ
		'FrzMessage' => array(32, "string"),
		// フレ　メダル加算値(パズドラW)
		'MedalForFriend' => array(33, "int"),
		// フレ　非フレンドのメダル加算値(パズドラW)
		'MedalForNonFriend' => array(34, "int"),
		// パズドラWログインストリークメダル
		'WLoginStreakMedal' => array(35, "int"),
		// GSアカバンメッセージ
		'GSBanMessage' => array(36, "string"),
		// CRアカバンメッセージ
		'CRBanMessage' => array(37, "string"),
		// 期限付きアカウント停止メッセージ
		'LtdBanMessage' => array(38, "string"),
		// ランキング集計基準時間(毎時n分等)
		'RankingAggregateBaseTime' =>  array(1001, "int"),
		// ランキング集計間隔(n時間毎等)
		'RankingAggregateIntervalTime' =>  array(1002, "int"),
		// QQ会員コイン増加ボーナス割合
		'QQCoinBonus' =>  array(1003, "int"),
		// QQ会員経験値増加ボーナス割合
		'QQExpBonus' =>  array(1004, "int"),
		// 超級QQ会員コイン増加ボーナス割合
		'QQVipCoinBonus' =>  array(1005, "int"),
		// 超級QQ会員経験値増加ボーナス割合
		'QQVipExpBonus' =>  array(1006, "int"),
		// QQ会員ログインボーナスメッセージ
		'QQVipLoginBonusMessage' => array(1007, "string"),
		'QQVipLoginBonus' => array(1008, "string"),
		'QQSvipLoginBonus' => array(1009, "string"),
		'TencentIdipBonusMessage' => array(1010, "string"),
		'LoginTotalCountBonusMessage' => array(1011, "string"),
		'GameCenterLoginBonusMessage' => array(1012, "string"),
		'QQGameCenter' => array(1013, "string"),
		'WechatGameCenter' => array(1014, "string"),
		// #PADC_DY# ----------begin----------
		'QQVipNoviceBonusMessage' => array(1015, "string"),
		'QQSvipNoviceBonusMessage' => array(1016, "string"),
		'DailyDungeonBonusMailTitle' => array(1017,"string"), // 每日关卡通关奖励邮件的标题
		'QQVipPurchaseBonusMessage' => array(1018,"string"), // QQ会员开通礼包邮件内容
		'QQSvipPurchaseBonusMessage' => array(1019,"string"), // 超级会员开通礼包邮件内容
		'ChargeT1' =>  array(1020, "int"),
		'ChargeT2' =>  array(1021, "int"),
		'ChargeT3' =>  array(1022, "int"),
		'ChargeT4' =>  array(1023, "int"),
		'ChargeT5' =>  array(1024, "int"),
		'ChargeT6' =>  array(1025, "int"),
		'ChargeT7' =>  array(1026, "int"),
		'ChargeG1' =>  array(1027, "int"),
		'ChargeG2' =>  array(1028, "int"),
		'ChargeG3' =>  array(1029, "int"),
		'ChargeG4' =>  array(1030, "int"),
		'ChargeG5' =>  array(1031, "int"),
		'ChargeG6' =>  array(1032, "int"),
		'ChargeG7' =>  array(1033, "int"),
		'ExchangeProductCount1' =>  array(1040, "int"),
		'ExchangeProductCount2' =>  array(1041, "int"),
		'ExchangeProductCount3' =>  array(1042, "int"),
		'ExchangeRefreshGold' => array(1043, "int"),
		'ExchangeMagicStoneProductCount1' => array(1050,'int'),
		'ExchangeMagicStoneProductCount2' => array(1051,'int'),
		'ExchangeMagicStoneProductCount3' => array(1052,'int'),
		'ExchangeMagicStoneProductCount4' => array(1053,'int'),
		'ExchangeMagicStoneProductCount5' => array(1054,'int'),
		'ExchangeMagicStoneProductCount6' => array(1055,'int'),
		'ExchangeMagicStoneProductCount7' => array(1056,'int'),
		'ExchangeMagicStoneProductCount8' => array(1057,'int'),
		'ExchangeMagicStoneRefreshGold1' => array(1060,'int'),
		'ExchangeMagicStoneRefreshGold2' => array(1061,'int'),
		'ExchangeMagicStoneRefreshGold3' => array(1062,'int'),
		'ExchangeMagicStoneRefreshGold4' => array(1063,'int'),
		'ExchangeMagicStoneRefreshGold5' => array(1064,'int'),
		'ExchangeMagicStoneRefreshGold6' => array(1065,'int'),
		'ExchangeMagicStoneRefreshGold7' => array(1066,'int'),
		'ExchangeMagicStoneRefreshGold8' => array(1067,'int'),
		'MaxRefresh' => array(1100,'int'),
		// #PADC_DY# ----------end----------

		'GuestLoginBonusMessage' => array(1101,'string'),
		'GuestGameCenter' => array(1102,'string'),
		//扭蛋次数限制
		'GachaDailyPlayCounts' => array(2100,'int'),
		//开启扭蛋限制与否
		'GachaLimitSwitch' => array(2101,'int'),
	);


	/**
	 * 指定した(型変換済み)ゲーム定数を取得する.
	 * キャッシュから取得できなければ、新たにキャッシュに書き込みする.
	 */
	public static function getParam($key) {

		// ゲーム定数で今後変更されないものはソースに直書きする #1854
		if($key === 'SellPriceLevel') {
			return GameConstant::SELL_PRICE_LEVEL;
		}elseif($key === 'BeatCoinLevel') {
			return GameConstant::BEAT_COIN_LEVEL;
		}elseif($key === 'BeatExpLevel') {
			return GameConstant::BEAT_EXP_LEVEL;
		}elseif($key === 'CompositeExpLevel') {
			return GameConstant::COMPOSITE_EXP_LEVEL;
		}elseif($key === 'CompositeCostLevel') {
			return GameConstant::COMPOSITE_COST_LEVEL;
		}elseif($key === 'CompositeSkillUpProb') {
			return GameConstant::COMPOSITE_SKILL_UP_PROB;
		}elseif($key === 'CompositeSameAttrBonus') {
			return GameConstant::COMPOSITE_SAME_ATTR_BONUS;
		}elseif($key === 'CompositeGoodBonus') {
			return GameConstant::COMPOSITE_GOOD_BONUS;
		}elseif($key === 'CompositeGoodBonusProb') {
			return GameConstant::COMPOSITE_GOOD_BONUS_PROB;
		}elseif($key === 'CompositeExcellentBonus') {
			return GameConstant::COMPOSITE_EXCELLENT_BONUS;
		}elseif($key === 'CompositeExcellentBonusProb') {
			return GameConstant::COMPOSITE_EXCELLENT_BONUS_PROB;
		}elseif($key === 'FriendPointForFriend') {
			return GameConstant::FRIEND_POINT_FOR_FRIEND;
		}elseif($key === 'FriendPointForNonFriend') {
			return GameConstant::FRIEND_POINT_FOR_NON_FRIEND;
		// #PADC# ----------begin----------
		}elseif($key === 'FriendPointForSNSFriend') {
			return GameConstant::FRIEND_POINT_FOR_SNS_FRIEND;
		// #PADC# ----------end----------
		}elseif($key === 'FriendGachaPrice') {
			return GameConstant::FRIEND_GACHA_PRICE;
		}elseif($key === 'CompositePlusCoinLevel') {
			return GameConstant::COMPOSITE_PLUS_COIN_LEVEL;
		}elseif($key === 'EvolutionPlusCoinLevel') {
			return GameConstant::EVOLUTION_PLUS_COIN_LEVEL;
		}elseif($key === 'SellPlusCoinLevel') {
			return GameConstant::SELL_PLUS_COIN_LEVEL;
		}elseif($key === 'PlusHP') {
			return GameConstant::PLUS_HP;
		}elseif($key === 'PlusATK') {
			return GameConstant::PLUS_ATK;
		// #PADC# ----------begin----------
		}elseif($key === 'PlusREC') {
			return GameConstant::PLUS_REC;
		// #PADC# ----------end----------
		}elseif($key === 'DungPlusDrop') {
			return GameConstant::DUNG_PLUS_DROP;
		}elseif($key === 'FrigPlusDrop') {
			return GameConstant::FRI_PLUS_DROP;
		}elseif($key === 'RarePlusDrop') {
			return GameConstant::RARE_PLUS_DROP;
		}elseif($key === 'PresPlusDrop') {
			return GameConstant::PRES_PLUS_DROP;
		}elseif($key === 'MedalForFriend') {
			return GameConstant::MEDAL_FOR_FRIEND;
		}elseif($key === 'MedalForNonFriend') {
			return GameConstant::MEDAL_FOR_NON_FRIEND;
		}elseif($key === 'WLoginStreakMedal') {
			return GameConstant::W_LOGIN_STREAK_MEDAL;
		}
		// #PADC# ----------begin----------　
		// MY : 欠片に関する定数追加
		elseif($key === 'PieceCompositeExp')
		{
			return GameConstant::PIECE_COMPOSITE_EXP;
		}
		elseif($key === 'PieceCompositeCostExp')
		{
			return GameConstant::PIECE_COMPOSITE_COST_EXP;
		}
		elseif($key === 'PieceCompositeSameAttrBonus')
		{
			return GameConstant::PIECE_COMPOSITE_SAME_ATTR_BONUS;
		}
		elseif($key === 'PieceCompositeGoodBonus')
		{
			return GameConstant::PIECE_COMPOSITE_GOOD_BONUS;
		}
		elseif($key === 'PieceCompositeGoodBonusProb')
		{
			return GameConstant::PIECE_COMPOSITE_GOOD_BONUS_PROB;
		}
		elseif($key === 'PieceCompositeExcellentBonus')
		{
			return GameConstant::PIECE_COMPOSITE_EXCELLENT_BONUS;
		}
		elseif($key === 'PieceCompositeExcellentBonusProb')
		{
			return GameConstant::PIECE_COMPOSITE_EXCELLENT_BONUS_PROB;
		}
		elseif($key === 'PieceCompositePlusCoinLevel')
		{
			return GameConstant::PIECE_COMPOSITE_PLUS_COIN_LEVEL;
		}
		elseif($key === 'PieceEvolutionPlusCoinLevel')
		{
			return GameConstant::PIECE_EVOLUTION_PLUS_COIN_LEVEL;
		}
		elseif($key === 'PieceCompositeRareBaseCost')
		{
			return GameConstant::PIECE_COMPOSITE_RARE_BASE_COST;
		}
		elseif($key === 'PieceCompositeRareCost')
		{
			return GameConstant::PIECE_COMPOSITE_RARE_COST;
		}
		elseif($key === 'PieceCompositeRareCostPowIndex')
		{
			return GameConstant::PIECE_COMPOSITE_RARE_COST_POW_INDEX;
		}
		elseif($key === 'PieceCompositeMlvBaseCost')
		{
			return GameConstant::PIECE_COMPOSITE_MLV_BASE_COST;
		}
		elseif($key === 'PieceCompositeMlvCost')
		{
			return GameConstant::PIECE_COMPOSITE_MLV_COST;
		}
		elseif($key === 'PieceCompositeMlvCostPowIndex')
		{
			return GameConstant::PIECE_COMPOSITE_MLV_COST_POW_INDEX;
		}
		elseif($key === 'PieceEvolutionRareBaseCost')
		{
			return GameConstant::PIECE_EVOLUTION_RARE_BASE_COST;
		}
		elseif($key === 'PieceEvolutionRareCost')
		{
			return GameConstant::PIECE_EVOLUTION_RARE_COST;
		}
		elseif($key === 'PieceEvolutionRareCostPowIndex')
		{
			return GameConstant::PIECE_EVOLUTION_RARE_COST_POW_INDEX;
		}
		elseif($key === 'PieceEvolutionMlvBaseCost')
		{
			return GameConstant::PIECE_EVOLUTION_MLV_BASE_COST;
		}
		elseif($key === 'PieceEvolutionMlvCost')
		{
			return GameConstant::PIECE_EVOLUTION_MLV_COST;
		}
		elseif($key === 'PieceEvolutionMlvCostPowIndex')
		{
			return GameConstant::PIECE_EVOLUTION_MLV_COST_POW_INDEX;
		}
		elseif($key === 'PieceEvolutionCostExp')
		{
			return GameConstant::PIECE_EVOLUTION_COST_EXP;
		}
		elseif($key === 'RankingParticipateFloorId')
		{
			return GameConstant::RANKING_PARTICIPATE_FLOOR_ID;
		}
		elseif($key === 'DungeonSaleOpenClearCount')
		{
			return GameConstant::DUNGEON_SALE_OPEN_CLEAR_COUNT;
		}
		elseif($key === 'QQCoinBonus')
		{
			return GameConstant::QQ_COIN_BONUS;
		}
		elseif($key === 'QQExpBonus')
		{
			return GameConstant::QQ_EXP_BONUS;
		}
		elseif($key === 'QQVipCoinBonus')
		{
			return GameConstant::QQ_VIP_COIN_BONUS;
		}
		elseif($key === 'QQVipExpBonus')
		{
			return GameConstant::QQ_VIP_EXP_BONUS;
		}
		elseif($key === 'GameCenterLoginBonusId')
		{
			return GameConstant::GAME_CENTER_LOGIN_BONUS_ID;
		}
		elseif($key === 'GameCenterLoginBonusAmount')
		{
			return GameConstant::GAME_CENTER_LOGIN_BONUS_AMOUNT;
		}
		elseif($key === 'GameCenterCoinBonus'){
			return GameConstant::GAME_CENTER_COIN_BONUS;
		}
		//魔法转盘的配置参数:转盘位置的选中概率
		elseif($key === 'ProbForGoldDialPosition'){
			return array(44945, 50, 5000, 5, 44945, 50, 5000, 5);
		}
		//魔法转盘的配置参数：累计充值多少可获得魔法转盘机会
		elseif($key === 'GoldPointForChance'){
			return array(60, 100, 300, 500, 980, 1980);
		}

		// #PADC# ----------end----------
        // #PADC_DY# 关卡恢复体力消耗金币
        elseif($key === 'FloorRecoveryGold'){
			return array(10, 20, 40, 60);
		}
		// #PADC_DY# 交换所定时刷新时间
		elseif($key === 'ExchangeRefreshTime'){
			return array(9, 12, 18, 21);
		}elseif($key === 'ExchangeMagicStoneShopRefreshTime'){
			//魔法石商店早上凌晨4点刷新一次
			return array(4);
		}

		$constant = null;
		$param = GameConstant::$params[$key];
		if(is_array($param)) {
			$key = MasterCacheKey::getCastedGameConstantKey($param[0]);
			$constant = apc_fetch($key);
			if(FALSE === $constant) {
				$game_constant = GameConstant::get($param[0]);
				$constant = $game_constant->value;
				if(in_array($param[1], array("float", "int"))) {
					// カンマ除去
					$constant = str_replace(",","",$constant);
				}
				settype($constant, $param[1]);
				apc_store($key, $constant, GameConstant::MEMCACHED_EXPIRE + static::add_apc_expire());
			}
		}
		return $constant;
	}


	//获得转盘的三个参数：转盘价格、重置价格、8个位置对应的概率
	public static function getDialParameter(){
		$tmpArr = array();


		//8个位置对应的概率
		$positionProb = GameConstant::get(2043);
		$tmpArr[$positionProb->name] = $positionProb->value;

		//转盘价格
		$priceForTurnDial = GameConstant::get(2044);
		$tmpArr[$priceForTurnDial->name] = $priceForTurnDial->value;

		//重置价格
		$priceForResetDial = GameConstant::get(2045);
		$tmpArr[$priceForResetDial->name] = $priceForResetDial->value;

		$maxResetNumForDial = GameConstant::get(2046);
		$tmpArr[$maxResetNumForDial->name] = $maxResetNumForDial->value;

		return $tmpArr;
	}

	public static function getCarnivalTabDes(){
		$pdo_share = Env::getDbConnectionForShareRead();

		$sql =  "SELECT id,value FROM " . static::TABLE_NAME . " WHERE name LIKE 'CarnivalTabDescription%' ORDER BY id asc";
		$stmt = $pdo_share->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS,GameConstant::class);
		$stmt->execute();
		$objs = $stmt->fetchAll();

		$tab_desccriptions = array();
		foreach($objs as $obj){
			$tab_desccriptions[] = $obj->value;
		}

		return $tab_desccriptions;
    }
}
