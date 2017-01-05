<?php
/**
 * ガチャ
 */

class Gacha {
	const TYPE_FRIEND	= 1;  // 友情ガチャ
	const TYPE_CHARGE	= 2;  // レアガチャ
	const TYPE_THANKS	= 3;  // プレゼントガチャ
	const TYPE_SPARED	= 4;
	const TYPE_EXTRA	= 5;  // 追加ガチャ
	// #PADC# ----------begin----------
	const TYPE_PREMIUM	= 101;  // プレミアムガチャ
	const TYPE_TUTORIAL	= 102;  // チュートリアルガチャ
	// #PADC# ----------end----------

	const MAX_GACHA_CNT = 10;  // 連続ガチャ最大回数

	// 課金ガチャを回すときの消費魔石数
	const COST_MAGIC_STONE				= 105;// レアガチャ
	const COST_MAGIC_STONE_PREMIUM		= 260;// プレミアムガチャ
	const COST_MAGIC_STONE_PREMIUM_10	= 2340;// プレミアムガチャ10連
	
	// #PADC# ----------begin----------
	// 基本ガチャID
	const ID_FRIEND		= 1;  // 友情ガチャ
	const ID_CHARGE		= 2;  // レアガチャ
	const ID_PREMIUM	= 3;  // プレミアムガチャ
	const ID_TUTORIAL	= 4;  // チュートリアルガチャ
	// #PADC# ----------end----------
	// #PADC_DY# ----------begin----------
	const ID_CARNIVAL_STRENGTHEN = 6; //强化嘉年华
	const ID_IP_HULUWA = 7; // 葫芦娃扭蛋
	const ID_CARNIVAL_FIRE = 8; // 火嘉年华
	const ID_CARNIVAL_WATER = 9; // 水嘉年华
	const ID_CARNIVAL_WATER_GOD = 10; // 神类型水嘉年华
	const ID_CARNIVAL_WOOD = 11; // 木嘉年华
	const ID_CARNIVAL_DARK = 12; // 暗嘉年华
	const ID_CARNIVAL_WOOD_GOD = 13; // 神节日木嘉年华
	const ID_CARNIVAL_FIRE_GOD = 15; // 神节日火嘉年华
	const ID_CARNIVAL_LIGHT = 16; // 光嘉年华
	const ID_CARNIVAL_RARE = 17; // 稀少嘉年华
	const ID_CARNIVAL_DARK_GOD = 18; // 神类型暗嘉年华
	const ID_CARNIVAL_LIGHT_GOD = 19; // 神类型光嘉年华
	const ID_IP_BLEACH = 25; // 死神扭蛋
	// #PADC_DY# -----------end-----------


	/**
	 * 指定されたタイプのガチャを回し、ユーザに景品カードを付与する.
	 * 付与したUserCardオブジェクトを返す.
	 */
	public static function play($user_id, $bm, $gacha_type, $single, $extra_gacha_id = null, $token, $discount_id) {
		
		// #PADC# ----------begin----------
		// 初回割引利用フラグが送られた場合、実際に初回かチェックする
		$gacha_discount = null;
		if ($discount_id) {
			$gacha_discount = GachaDiscount::getActiveGachaDiscount($gacha_type, ($single ? 1 : 10));
			if ($gacha_discount == null || $gacha_discount->id != $discount_id) {
				// 初回割引データが見つからない場合、エラーを返す
				throw new PadException(RespCode::FAILED_GACHA, "GachaDiscount data is none. gacha_type:".$gacha_type." single:".$single.". __NO_TRACE");
			}
			$user_gacha_discounts = UserGachaDiscount::findAllBy(array('user_id' => $user_id));
			foreach ($user_gacha_discounts as $user_gacha_discount) {
				// 初回割引利用済みの場合、エラーを返す
				if ($gacha_discount->id == $user_gacha_discount->discount_id) {
					throw new PadException(RespCode::FAILED_GACHA, "GachaDiscount already used. user_id:".$user_id." discount_id:".$gacha_discount->id.". __NO_TRACE");
				}
			}
		}
		// #PADC# ----------end----------

		$user_card = null;
		$is_friend_gacha = 0;
		if($gacha_type == self::TYPE_FRIEND) {
			// 友情ポイントガチャ.
			list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt) = self::playFriendGachaCnt($user_id, $bm, $single, null, $gacha_discount);
		}else if($gacha_type == self::TYPE_CHARGE) {
			// レアガチャ.
			// #PADC#
			if ($single == 1) {
				list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt) = self::playChargeGacha($user_id, $bm, null, $token, $gacha_discount);
			}
			else {
				list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt) = self::playChargeGachaCnt($user_id, $bm, null, $token, $gacha_discount);
			}
		}else if($gacha_type == self::TYPE_EXTRA) {
			$extra_gacha = ExtraGacha::getActiveExtraGacha();
			if($extra_gacha === FALSE || $extra_gacha->id !== $extra_gacha_id) {
				throw new PadException(RespCode::FAILED_GACHA, "playExtraGacha FAILED_GACHA. __NO_LOG"); // 有効期間外.
			}
			if($extra_gacha->gacha_type == ExtraGacha::TYPE_FRIEND){
				// 追加ガチャ(友情ポイントガチャ).
				list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt) = self::playFriendGachaCnt($user_id, $bm, $single, $extra_gacha, $gacha_discount);
			}elseif($extra_gacha->gacha_type == ExtraGacha::TYPE_CHARGE){
				// 追加ガチャ(有償ガチャ).
				// #PADC#
				$pdo = Env::getDbConnectionForUserWrite($user_id);
				$user_daily_counts =  UserDailyCounts::findBy(array("user_id"=>$user_id));
				$counts = 0; 
				if(!$user_daily_counts){
					//没有记录
					$user_daily_counts->piece_daily_count = 0;
					$user_daily_counts->ip_daily_count = 0;
					$user_daily_counts->create($pdo); 
					$counts =  0;
				}else{
					$counts = $user_daily_counts->ip_daily_count;
				}
				
				if ($single == 1) {
					// #PADC_DY# 加入gacha报酬结果的数据$gacha_bonus
					if(GameConstant::getParam("GachaLimitSwitch")){
						//开启扭蛋限制
						if($counts >=0 && $counts<=GameConstant::getParam("GachaDailyPlayCounts")){
							list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGacha($user_id, $bm, $extra_gacha, $token, $gacha_discount);
							if($gacha_bonus){
								$user_daily_counts->ip_daily_count++;
								$user_daily_counts->update($pdo);
							}
						}else{
							throw new PadException(RespCode::GACHA_DISABLE, "Gacha Counts reach max,No Play!");
						}
					}else{
						list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGacha($user_id, $bm, $extra_gacha, $token, $gacha_discount);
					}
					
					
				}
				else {
					// #PADC_DY# 加入gacha报酬结果的数据$gacha_bonus
					if(GameConstant::getParam("GachaLimitSwitch")){
						//开启扭蛋限制
						if($counts >=0 && $counts <=10){
							list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGachaCnt($user_id, $bm, $extra_gacha, $token, $gacha_discount);
							if($gacha_bonus){
								$user_daily_counts->ip_daily_count = $user_daily_counts->ip_daily_count + 10;
								$user_daily_counts->update($pdo);
							}
						}else{
							throw new PadException(RespCode::GACHA_DISABLE, "Gacha Counts reach max,No Play!");
						}
					}else{
						list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGachaCnt($user_id, $bm, $extra_gacha, $token, $gacha_discount);
					}
				}
			}
		}
		// #PADC# ----------begin----------
		elseif($gacha_type == self::TYPE_PREMIUM)
		{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$user_daily_counts =  UserDailyCounts::findBy(array("user_id"=>$user_id));
			$counts = 0; 
			if(!$user_daily_counts){
				//没有记录
				$user_daily_counts->piece_daily_count = 0;
				$user_daily_counts->ip_daily_count = 0;
				$user_daily_counts->create($pdo); 
				$counts =  0;
			}else{
				$counts = $user_daily_counts->piece_daily_count;
			}
			

			// プレミアムガチャ
			if ($single == 1) {
				// #PADC_DY# 加入gacha报酬结果的数据$gacha_bonus
				if(GameConstant::getParam("GachaLimitSwitch")){
					if($counts >=0 && $counts <=GameConstant::getParam("GachaDailyPlayCounts")){
						list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGacha($user_id, $bm, null, $token, $gacha_discount);
						if($gacha_bonus){
							$user_daily_counts->piece_daily_count++;
							$user_daily_counts->update($pdo);
						}
					}else{
						throw new PadException(RespCode::GACHA_DISABLE, "Gacha Counts reach max,No Play!");
					}
					
				}else{
					list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGacha($user_id, $bm, null, $token, $gacha_discount);
				}
				
			}
			else {
				// #PADC_DY# 加入gacha报酬结果的数据$gacha_bonus
				if(GameConstant::getParam("GachaLimitSwitch")){
					if($counts >=0 && $counts <=10){
						list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGachaCnt($user_id, $bm, null, $token, $gacha_discount);
						if($gacha_bonus){
							$user_daily_counts->piece_daily_count = $user_daily_counts->piece_daily_count + 10;
							$user_daily_counts->update($pdo);
						}
					}else{
						throw new PadException(RespCode::GACHA_DISABLE, "Gacha Counts reach max,No Play!");
					}
				}else{
					list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playPremiumGachaCnt($user_id, $bm, null, $token, $gacha_discount);
				}
				
			}
		}
		elseif($gacha_type == self::TYPE_TUTORIAL)
		{
			// チュートリアルガチャ
			// #PADC_DY# 加入gacha报酬结果的数据$gacha_bonus
			list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = self::playTutorialGacha($user_id, $bm, null, $token);
		}
		// #PADC# ----------end----------


		// 扭蛋累计活动如果开启
		$activity = Activity::getByType(Activity::ACTIVITY_TYPE_GACHA_COUNT);
		if ($activity) {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
			$uac->addCounts(Activity::ACTIVITY_TYPE_GACHA_COUNT, 0, $single == 1 ? 1 : self::MAX_GACHA_CNT);
			$uac->update($pdo);
		}

		// #PADC_DY# 加入gacha报酬结果的数据$gacha_bonus
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, isset($gacha_bonus) ? $gacha_bonus : null);
	}

	/**
	 * 課金ガチャを回し、景品をUserに付与する.
	 * 得たUserCardオブジェクトを返す.
	 * 追加ガチャの時はextra_gachaを指定する.
	 **/
	private static function playChargeGacha($user_id, $bm, $extra_gacha = null, $token, $gacha_discount = null) {
		// #PADC# ----------begin----------
		User::getUserBalance($user_id, $token);
		$billno = 0;
		// #PADC# ----------end----------

		$log_data = array();
		// 消費する魔石数を算定.
		$cost = ($extra_gacha === null) ? self::COST_MAGIC_STONE : (int)$extra_gacha->price;
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			$gold = $user->gold + $user->pgold;
			$fripnt = $user->fripnt;
			// #PADC# ----------begin----------
			// PADCではモンスター所持数上限がないのでチェックをコメントアウト
			//// 所持カード枚数チェック.
			//if($bm > $user->card_max){
			//	throw new PadException(RespCode::EXCEEDED_MAX_NUM_CARD, "playChargeGacha EXCEEDED_MAX_NUM_CARD user_id:".$user_id." bm:$bm. __NO_TRACE");
			//}
			// #PADC# ----------end----------
			// 消費魔石チェック.
			if($user->checkHavingGold($cost) === FALSE) {
				throw new PadException(RespCode::FAILED_GACHA, "playChargeGacha FAILED_GACHA user_id:".$user_id." not enough gold. __NO_TRACE");
			}
			$log_data["gold_before"] = (int)$user->gold;
			$log_data["pgold_before"] = (int)$user->pgold;
			if($extra_gacha === NULL){
				$gacha_prize = self::takeGachaPrize(self::TYPE_CHARGE);
			}else{
				// 追加ガチャ（魔法石）.
				$gacha_prize = self::takeGachaPrize(self::TYPE_EXTRA, $extra_gacha->gacha_id);
			}

			// #PADC# ----------begin----------

			$result_get_pieces = array();

			// 生成したことがあるかどうかチェック
			$user_piece = UserPiece::getUserPiece($user_id, $gacha_prize->piece_id, $pdo);
			if(UserPiece::isCreated($user_piece))
			{
				$add_piece_num = $gacha_prize->piece_num2;
			}
			else
			{
				$add_piece_num = $gacha_prize->piece_num;
			}

			$result_get_pieces[] = array(
				(int)$gacha_prize->piece_id,
				(int)$add_piece_num,
			);

			// 入手時のLVをセット
			$initInfo = array(
				'lv' => $gacha_prize->getLevel(),
				'slv' => UserCard::DEFAULT_SKILL_LEVEL,
			);

			// 入手対象欠片の追加準備
			$add_pieces = array();
			$add_cards = array();
			$add_result = UserPiece::addUserPieceToUserReserve(
				$user_id,
				$gacha_prize->piece_id,
				$add_piece_num,
				$pdo,
				$initInfo
			);
			$add_pieces[$gacha_prize->piece_id] = $add_result['piece'];
			if(array_key_exists('card', $add_result)){
				$add_cards[] = $add_result['card'];
			}

			// 欠片（及びモンスター）追加処理
			list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

			//#PADC# ---------------begin------------------
			// 図鑑登録数の更新
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			//#PADC# ---------------end------------------

			//#PADC# ---------------begin------------------
			// 割引対応
			if ($gacha_discount) {
				// 割引後のコストは四捨五入
				$cost = round($cost * (10000 - $gacha_discount->ratio) / 10000);
				// 割引を利用したデータを残す
				UserGachaDiscount::setUsed($user_id, $gacha_discount->id, $pdo);
			}
			//#PADC# ---------------end------------------

			// TODO: ログ内容調整
			$log_data["prize_card"] = array();
			$log_data["prize_card"]["piece_id"] = (int)$gacha_prize->piece_id;

			// #PADC# 魔法石消費.
			$billno = $user->payGold($cost, $token, $pdo);

			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$log_data["gold_after"] = (int)$user->gold;
			$log_data["pgold_after"] = (int)$user->pgold;
			$gold = $user->gold+$user->pgold;
			$fripnt = $user->fripnt;
			$log_data['dev'] = (int)$user->device_type;
			$log_data['area'] = (int)$user->area_id;

			$pdo->commit();
			//#PADC#
			$sequence = Tencent_Tlog::getSequence($user->id);

			if($extra_gacha === null){
				UserLogRareGacha::log($user->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_RARE_GACHA, 0, $gacha_prize->gacha_id);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_RARE_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence);
				// #PADC# ----------end----------
			}else{
				UserLogExtraGacha::log($user->id, $extra_gacha->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_EXTRA_RARE_GACHA, 0, $extra_gacha->gacha_id);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_EXTRA_RARE_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence);
				// #PADC# ----------end----------
			}
			
			// #PADC# report score to Tencent MSDK
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
				
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			// #PADC# 魔法石消費を取り消す ----------begin----------
			if($billno){
				$user->cancelPay($cost, $billno, $token);
			}
			// ----------end----------
			throw $e;
		}
		// #PADC# レスポンス調整
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt);
	}

	/**
	 * #PADC#
	 * プレミアムガチャを回し、景品をUserに付与する.
	 * 得たUserCardオブジェクトを返す.
	 * 追加ガチャの時はextra_gachaを指定する.
	 **/
	private static function playPremiumGacha($user_id, $bm, $extra_gacha = null, $token, $gacha_discount = null)
	{
		// #PADC# ----------begin----------
		User::getUserBalance($user_id, $token);
		$billno = 0;
		// #PADC# ----------end----------

		$log_data = array();
		// 消費する魔石数を算定.
		$cost = ($extra_gacha === null) ? self::COST_MAGIC_STONE_PREMIUM : (int)$extra_gacha->price;
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			$gold = $user->gold + $user->pgold;
			$fripnt = $user->fripnt;
			// #PADC# ----------begin----------
			// PADCではモンスター所持数上限がないのでチェックをコメントアウト
			// 所持カード枚数チェック.
			//if($bm > $user->card_max){
			//	throw new PadException(RespCode::EXCEEDED_MAX_NUM_CARD, "playPremiumGacha EXCEEDED_MAX_NUM_CARD user_id:".$user_id." bm:$bm. __NO_TRACE");
			//}
			// #PADC# ----------end----------
			// 消費魔石チェック.
			if($user->checkHavingGold($cost) === FALSE) {
				throw new PadException(RespCode::FAILED_GACHA, "playPremiumGacha FAILED_GACHA user_id:".$user_id." not enough gold. __NO_TRACE");
			}
			$log_data["gold_before"] = (int)$user->gold;
			$log_data["pgold_before"] = (int)$user->pgold;
			if($extra_gacha === NULL){
				$gacha_prize = self::takeGachaPrize(self::TYPE_PREMIUM);
			}else{
				// 追加ガチャ（魔法石）.
				$gacha_prize = self::takeGachaPrize(self::TYPE_EXTRA, $extra_gacha->gacha_id);
			}

			// #PADC# ----------begin----------

			$result_get_pieces = array();

			// 生成したことがあるかどうかチェック
			$user_piece = UserPiece::getUserPiece($user_id, $gacha_prize->piece_id, $pdo);
			if(UserPiece::isCreated($user_piece))
			{
				$add_piece_num = $gacha_prize->piece_num2;
			}
			else
			{
				$add_piece_num = $gacha_prize->piece_num;
			}

			$result_get_pieces[] = array(
				(int)$gacha_prize->piece_id,
				(int)$add_piece_num,
			);

			// 入手時のLVをセット
			$initInfo = array(
				'lv' => $gacha_prize->getLevel(),
				'slv' => UserCard::DEFAULT_SKILL_LEVEL,
			);

			// 入手対象欠片の追加準備
			$add_pieces = array();
			$add_cards = array();
			$add_result = UserPiece::addUserPieceToUserReserve(
				$user_id,
				$gacha_prize->piece_id,
				$add_piece_num,
				$pdo,
				$initInfo
			);
			$add_pieces[$gacha_prize->piece_id] = $add_result['piece'];
			if(array_key_exists('card', $add_result)){
				$add_cards[] = $add_result['card'];
			}

			// 欠片（及びモンスター）追加処理
			list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

			//#PADC# ---------------begin------------------
			// 図鑑登録数の更新
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			//#PADC# ---------------end------------------

			// #PADC# ----------begin----------
			// ガチャ回数をカウント
			$user_count = UserCount::getByUserId($user_id, null, $pdo);
			$user_count->addCount(UserCount::TYPE_GACHA_GOLD);
			$user_count->addCount(UserCount::TYPE_DAILY_GACHA_GOLD);
			$user_count->update($pdo);
			// #PADC# ----------end----------
					
			//#PADC# ---------------begin------------------
			// 割引対応
			if ($gacha_discount) {
				// 割引後のコストは四捨五入
				$cost = round($cost * (10000 - $gacha_discount->ratio) / 10000);
				// 割引を利用したデータを残す
				UserGachaDiscount::setUsed($user_id, $gacha_discount->id, $pdo);
			}
			//#PADC# ---------------end------------------

			// TODO: ログ内容調整
			$log_data["prize_card"] = array();
			$log_data["prize_card"]["piece_id"] = (int)$gacha_prize->piece_id;

			// #PADC# 魔法石消費.
			$billno = $user->payGold($cost, $token, $pdo);

			// #PADC_DY# ----------begin----------
			$gacha_bonus = GachaBonus::findBy(array("gacha_id" => Gacha::ID_PREMIUM));
			$user = GachaBonus::applyBonuses($user, $gacha_bonus);
			// #PADC_DY# ----------end----------


			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$log_data["gold_after"] = (int)$user->gold;
			$log_data["pgold_after"] = (int)$user->pgold;
			$gold = $user->gold+$user->pgold;
			$fripnt = $user->fripnt;
			$log_data['dev'] = (int)$user->device_type;
			$log_data['area'] = (int)$user->area_id;

			$pdo->commit();

			//#PADC#
			$sequence = Tencent_Tlog::getSequence($user->id);

			if($extra_gacha === null){
				// FIXME: ログ出力は要調整
				UserLogRareGacha::log($user->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_PREMIUM_GACHA, $gacha_bonus->amount, $gacha_prize->gacha_id);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_PREMIUM_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence, $gacha_bonus->amount);
				// #PADC# ----------end----------
			}else{
				UserLogExtraGacha::log($user->id, $extra_gacha->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_EXTRA_PREMIUM_GACHA, $gacha_bonus->amount, $extra_gacha->gacha_id);

				// 更新扭蛋次数计数器，并上报手Q，目前只记录IP扭蛋
				$cnt = UserCount::incrUserGachaDailyPlayCount($user->id, $extra_gacha->gacha_id);

				$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_IP_GACHA_SINGLE, $token, $cnt);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_EXTRA_PREMIUM_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence, $gacha_bonus->amount);
				// #PADC# ----------end----------
			}
		
			// #PADC# report score to Tencent MSDK
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
		
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			// #PADC# 魔法石消費を取り消す ----------begin----------
			if($billno){
				$user->cancelPay($cost, $billno, $token);
			}
			// ----------end----------
			throw $e;
		}
		// #PADC# レスポンス調整
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus);
	}

	/**
	 * #PADC#
	 * チュートリアルガチャを回し、景品をUserに付与する.
	 * 得たUserCardオブジェクトを返す.
	 * 追加ガチャの時はextra_gachaを指定する.
	 * ※処理はほとんどplayPremiumGachaのコピペ
	 **/
	private static function playTutorialGacha($user_id, $bm, $extra_gacha = null, $token)
	{
		// #PADC# ----------begin----------
		User::getUserBalance($user_id, $token);
		$billno = 0;
		// #PADC# ----------end----------

		$log_data = array();
		// 消費する魔石数を算定.
		$cost = ($extra_gacha === null) ? self::COST_MAGIC_STONE_PREMIUM : (int)$extra_gacha->price;
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			$gold = $user->gold + $user->pgold;
			$fripnt = $user->fripnt;
			// #PADC# ----------begin----------
			// PADCではモンスター所持数上限がないのでチェックをコメントアウト
			// 所持カード枚数チェック.
			//if($bm > $user->card_max){
			//	throw new PadException(RespCode::EXCEEDED_MAX_NUM_CARD, "playTutorialGacha EXCEEDED_MAX_NUM_CARD user_id:".$user_id." bm:$bm. __NO_TRACE");
			//}
			// #PADC# ----------end----------
			// 消費魔石チェック.
			if($user->checkHavingGold($cost) === FALSE) {
				throw new PadException(RespCode::FAILED_GACHA, "playTutorialGacha FAILED_GACHA user_id:".$user_id." not enough gold. __NO_TRACE");
			}
			$log_data["gold_before"] = (int)$user->gold;
			$log_data["pgold_before"] = (int)$user->pgold;
			if($extra_gacha === NULL){
				$gacha_prize = self::takeGachaPrize(self::TYPE_TUTORIAL);
			}else{
				// 追加ガチャ（魔法石）.
				$gacha_prize = self::takeGachaPrize(self::TYPE_EXTRA, $extra_gacha->gacha_id);
			}

			// #PADC# ----------begin----------

			$result_get_pieces = array();

			// 生成したことがあるかどうかチェック
			$user_piece = UserPiece::getUserPiece($user_id, $gacha_prize->piece_id, $pdo);
			if(UserPiece::isCreated($user_piece))
			{
				$add_piece_num = $gacha_prize->piece_num2;
			}
			else
			{
				$add_piece_num = $gacha_prize->piece_num;
			}

			$result_get_pieces[] = array(
				(int)$gacha_prize->piece_id,
				(int)$add_piece_num,
			);

			// 入手時のLVをセット
			$initInfo = array(
				'lv' => $gacha_prize->getLevel(),
				'slv' => UserCard::DEFAULT_SKILL_LEVEL,
			);

			// 入手対象欠片の追加準備
			$add_pieces = array();
			$add_cards = array();
			$add_result = UserPiece::addUserPieceToUserReserve(
				$user_id,
				$gacha_prize->piece_id,
				$add_piece_num,
				$pdo,
				$initInfo
			);
			$add_pieces[$gacha_prize->piece_id] = $add_result['piece'];
			if(array_key_exists('card', $add_result)){
				$add_cards[] = $add_result['card'];
			}

			// 欠片（及びモンスター）追加処理
			list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

			// #PADC_DY# ----------begin----------
			$gacha_bonus = GachaBonus::findBy(array("gacha_id" => Gacha::ID_TUTORIAL));
			$user = GachaBonus::applyBonuses($user, $gacha_bonus);
			// #PADC_DY# ----------end----------

			//#PADC# ---------------begin------------------
			// 図鑑登録数の更新
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			//#PADC# ---------------end------------------

			// TODO: ログ内容調整
			$log_data["prize_card"] = array();
			$log_data["prize_card"]["piece_id"] = (int)$gacha_prize->piece_id;

			// #PADC# 魔法石消費.
			$billno = $user->payGold($cost, $token, $pdo);

			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$log_data["gold_after"] = (int)$user->gold;
			$log_data["pgold_after"] = (int)$user->pgold;
			$gold = $user->gold+$user->pgold;
			$fripnt = $user->fripnt;
			$log_data['dev'] = (int)$user->device_type;
			$log_data['area'] = (int)$user->area_id;

			$pdo->commit();

			//#PADC#
			$sequence = Tencent_Tlog::getSequence($user->id);

			if($extra_gacha === null){
				// FIXME: ログ出力は要調整
				UserLogRareGacha::log($user->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_PREMIUM_GACHA, $gacha_bonus->amount, $gacha_prize->gacha_id);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_PREMIUM_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence, $gacha_bonus->amount);
				// #PADC# ----------end----------
			}else{
				UserLogExtraGacha::log($user->id, $extra_gacha->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_EXTRA_PREMIUM_GACHA, $gacha_bonus->amount, $extra_gacha->gacha_id);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_EXTRA_PREMIUM_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence, $gacha_bonus->amount);
				// #PADC# ----------end----------
			}
		
			// #PADC# report score to Tencent MSDK
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
		
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			// #PADC# 魔法石消費を取り消す ----------begin----------
			if($billno){
				$user->cancelPay($cost, $billno, $token);
			}
			// ----------end----------
			throw $e;
		}
		// #PADC# レスポンス調整
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus);
	}

	/**
	 * 友情ガチャを回し、景品をUserに付与する.
	 * 得たUserCardオブジェクトを返す.
	 *
	 * ※現在は使用していません
	 * モンスターの初期レベル設定に不備があったため、playFriendGachaCnt()に移行しました
	 */
	private static function playFriendGacha($user_id, $bm, $single, $extra_gacha = null) {
		// 消費する魔石数を算定.
		$cost = ($extra_gacha === null) ? GameConstant::getParam("FriendGachaPrice") : (int)$extra_gacha->price;
		// ガチャを回す最大回数.
		$max_cnt = ($single == 1) ? 1 : self::MAX_GACHA_CNT;
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			// #PADC# ----------begin----------
			// PADCではモンスター所持数上限がないのでチェックをコメントアウト
			// 所持カード枚数チェック.
			//if($bm > $user->card_max){
			//	throw new PadException(RespCode::EXCEEDED_MAX_NUM_CARD, "playFriendGacha EXCEEDED_MAX_NUM_CARD user_id:".$user_id." bm:$bm. __NO_TRACE");
			//}
			// #PADC# ----------end----------
			// 消費友情ポイントチェック.
			$gacha_cnt = min(floor($user->fripnt / $cost), $max_cnt);
			if($gacha_cnt == 0){
				throw new PadException(RespCode::FAILED_GACHA, "playFriendGacha NOT_ENOUGH_FRIENDPONIT user_id:".$user_id." fripnt:".$user->fripnt.". __NO_TRACE");
			}

			// #PADC# ----------begin----------

			// モンスターの生成状況をチェックするため欠片所持情報を取得
			$user_pieces = UserPiece::getUserPiecesWithSetKey($user_id);

			// 欠片のマスターデータ一式をIDをキーとして取得
			$pieces = Piece::getPiecesWithSetKey();

			// 入手欠片数を調整
			// 入手したことがあるかどうか一式取得→for分回しつつ中で判定初回だったら初回分、2回目以降は2回目以降分を参照して合算したものをまとめて加算する
			$check_user_piece_nums	= array();// ガチャ実行前の所持数と実行中の所持数の総数をセット（※生成チェック）
			$add_piece_nums		= array();// ガチャ実行の総取得数をセット
			$result_get_pieces	= array();// ガチャ実行の結果を個々にセット
			for($i = 1; $i <= $gacha_cnt; $i++)
			{
				if($extra_gacha === null){
					$gacha_prize = self::takeGachaPrize(self::TYPE_FRIEND);
				}else{
					// 追加ガチャ（友情ポイント）.
					$gacha_prize = self::takeGachaPrize(self::TYPE_EXTRA, $extra_gacha->gacha_id);
				}

				// 入手欠片数の整形
				$return_data			= self::getAddPieceList($gacha_prize,$user_pieces,$pieces,$check_user_piece_nums,$add_piece_nums,$result_get_pieces);
				$add_piece_nums			= $return_data['add_piece_nums'];
				$result_get_pieces		= $return_data['result_get_pieces'];
				$check_user_piece_nums	= $return_data['check_user_piece_nums'];
			}

			// 入手対象欠片の追加準備
			$add_pieces = array();
			$add_cards = array();
			foreach($add_piece_nums as $piece_id => $piece_num)
			{
				$add_result = UserPiece::addUserPieceToUserReserve(
					$user->id,
					$piece_id,
					$piece_num,
					$pdo
				);
				$add_pieces[$piece_id] = $add_result['piece'];
				if(array_key_exists('card', $add_result)){
					$add_cards[] = $add_result['card'];
				}
			}

			// 欠片（及びモンスター）追加処理
			list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

			// #PADC# ----------end----------

			//#PADC# ---------------begin------------------
			// 図鑑登録数の更新
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			//#PADC# ---------------end------------------

			// 友情ポイント消費.
			$user->addFripnt(($cost * $gacha_cnt) * -1);
			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$pdo->commit();
			$fripnt = $user->fripnt;
			$gold = $user->gold + $user->pgold;

			// #PADC# TLOG friend point ----------begin----------
			$sequence = Tencent_Tlog::getSequence($user->id);
			UserTlog::sendTlogMoneyFlow($user, ($cost * $gacha_cnt) * -1, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT, 0, 0, $sequence, Tencent_Tlog::SUBREASON_BUY_FRIEND_GACHA, 0, $extra_gacha ? $extra_gacha->gacha_id : $gacha_prize->gacha_id);
			self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_FRIEND_GACHA, $cost * $gacha_cnt, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT, $sequence);
			// #PADC# ----------end----------

		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}

		// #PADC# レスポンス調整
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt);
	}

	/**
	 * 友情ガチャを回し、景品をUserに付与する.
	 * 得たUserCardオブジェクトを返す.
	 */
	private static function playFriendGachaCnt($user_id, $bm, $single, $extra_gacha = null, $gacha_discount = null) {
		// 消費する魔石数を算定.
		$cost = ($extra_gacha === null) ? GameConstant::getParam("FriendGachaPrice") : (int)$extra_gacha->price;
		// ガチャを回す最大回数.
		$max_cnt = ($single == 1) ? 1 : self::MAX_GACHA_CNT;
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			// 消費友情ポイントチェック.
			$gacha_cnt = min(floor($user->fripnt / $cost), $max_cnt);
			if($gacha_cnt == 0){
				throw new PadException(RespCode::FAILED_GACHA, "playFriendGachaCnt NOT_ENOUGH_FRIENDPONIT user_id:".$user_id." fripnt:".$user->fripnt.". __NO_TRACE");
			}

			// ガチャ結果
			if($extra_gacha === NULL){
				$result_gacha_prizes = static::takeGachaPrizeCnt(self::TYPE_FRIEND, null, $gacha_cnt);
			}
			else {
				$result_gacha_prizes = static::takeGachaPrizeCnt(self::TYPE_EXTRA, $extra_gacha->gacha_id, $gacha_cnt);
			}
			if(count($result_gacha_prizes) != $gacha_cnt) {
				throw new PadException(RespCode::FAILED_GACHA, "playFriendGachaCnt FAILED_GACHA result_gacha_prizes not found. __NO_TRACE");
			}

			// 欠片（及びモンスター）追加処理
			list ($result_get_pieces, $result_pieces, $get_cards) = Gacha::addGachaPrizes($user_id, $result_gacha_prizes, $pdo);

			//#PADC# ---------------begin------------------
			// 図鑑登録数の更新
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			//#PADC# ---------------end------------------
			
			// #PADC# ----------begin----------
			// ガチャ回数をカウント
			$user_count = UserCount::getByUserId($user_id, null, $pdo);
			$user_count->addCount(UserCount::TYPE_GACHA_FRIEND, $gacha_cnt);
			$user_count->addCount(UserCount::TYPE_DAILY_GACHA_FRIEND, $gacha_cnt);
			$user_count->update($pdo);
			// #PADC# ----------end----------
					
			//#PADC# ---------------begin------------------
			$cost = $cost * $gacha_cnt;
			// 割引対応
			if ($gacha_discount) {
				// 割引後のコストは四捨五入
				$cost = round($cost * (10000 - $gacha_discount->ratio) / 10000);
				// 割引を利用したデータを残す
				UserGachaDiscount::setUsed($user_id, $gacha_discount->id, $pdo);
			}
			//#PADC# ---------------end------------------

			// 友情ポイント消費.
			$user->addFripnt($cost * -1);
			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$pdo->commit();
			$fripnt = $user->fripnt;
			$gold = $user->gold + $user->pgold;

			// #PADC# TLOG friend point ----------begin----------
			$sequence = Tencent_Tlog::getSequence($user->id);
			UserTlog::sendTlogMoneyFlow($user, ($cost * $gacha_cnt) * -1, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT, 0, 0, $sequence, Tencent_Tlog::SUBREASON_BUY_FRIEND_GACHA, 0, $result_gacha_prizes[0]->gacha_id);
			self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_FRIEND_GACHA, $cost * $gacha_cnt, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT, $sequence);
			// #PADC# ----------end----------

		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}

		// #PADC# レスポンス調整
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt);
	}

	/**
	 * #PADC#
	 * 10連ガチャ
	 * 課金ガチャを回し、景品をUserに付与する.
	 * 得たUserCardオブジェクトを返す.
	 * 追加ガチャの時はextra_gachaを指定する.
	 * 1～9回まで通常ラインナップ、10回目をレア度5以上のラインナップから選出する
	 **/
	private static function playChargeGachaCnt($user_id, $bm, $extra_gacha = null, $token, $gacha_discount = null) {

		User::getUserBalance($user_id, $token);
		$billno = 0;

		$log_data = array();
		// 消費する魔石数を算定.
		$cost = (($extra_gacha === null) ? self::COST_MAGIC_STONE : (int)$extra_gacha->price) * 10;
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			$gold = $user->gold + $user->pgold;
			// 消費魔石チェック.
			if($user->checkHavingGold($cost) === FALSE) {
				throw new PadException(RespCode::FAILED_GACHA, "playChargeGachaCnt FAILED_GACHA user_id:".$user_id." not enough gold. __NO_TRACE");
			}
			$log_data["gold_before"] = (int)$user->gold;
			$log_data["pgold_before"] = (int)$user->pgold;

			// 10連ガチャ結果（レア度5確定）
			if($extra_gacha === NULL){
				$result_gacha_prizes = static::takeGachaPrizeCnt(self::TYPE_CHARGE, null, self::MAX_GACHA_CNT, 5);
			}
			else {
				$result_gacha_prizes = static::takeGachaPrizeCnt(self::TYPE_EXTRA, $extra_gacha->gacha_id, self::MAX_GACHA_CNT, 5);
			}
			if(count($result_gacha_prizes) != self::MAX_GACHA_CNT) {
				throw new PadException(RespCode::FAILED_GACHA, "playChargeGachaCnt FAILED_GACHA result_gacha_prizes not found. __NO_TRACE");
			}

			// 欠片（及びモンスター）追加処理
			list ($result_get_pieces, $result_pieces, $get_cards) = Gacha::addGachaPrizes($user_id, $result_gacha_prizes, $pdo);

			//#PADC# ---------------begin------------------
			// 図鑑登録数の更新
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			//#PADC# ---------------end------------------

			//#PADC# ---------------begin------------------
			// 割引対応
			if ($gacha_discount) {
				// 割引後のコストは四捨五入
				$cost = round($cost * (10000 - $gacha_discount->ratio) / 10000);
				// 割引を利用したデータを残す
				UserGachaDiscount::setUsed($user_id, $gacha_discount->id, $pdo);
			}
			//#PADC# ---------------end------------------

			// TODO: ログ内容調整
			$log_data["prize_card"] = array();
			$log_data["prize_card"]["piece_id"] = $result_get_pieces;

			// #PADC# 魔法石消費.
			$billno = $user->payGold($cost, $token, $pdo);

			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$log_data["gold_after"] = (int)$user->gold;
			$log_data["pgold_after"] = (int)$user->pgold;
			$gold = $user->gold+$user->pgold;
			$fripnt = $user->fripnt;
			$log_data['dev'] = (int)$user->device_type;
			$log_data['area'] = (int)$user->area_id;

			$pdo->commit();
			//#PADC#
			$sequence = Tencent_Tlog::getSequence($user->id);

			if($extra_gacha === null){
				UserLogRareGacha::log($user->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_RARE_GACHA);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_RARE_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence);
				// #PADC# ----------end----------
			}else{
				UserLogExtraGacha::log($user->id, $extra_gacha->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_EXTRA_RARE_GACHA, 0, $extra_gacha->gacha_id);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_EXTRA_RARE_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence);
				// #PADC# ----------end----------
			}
			
			// #PADC# report score to Tencent MSDK
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
			
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			// #PADC# 魔法石消費を取り消す ----------begin----------
			if($billno){
				$user->cancelPay($cost, $billno, $token);
			}
			// ----------end----------
			throw $e;
		}
		// #PADC# レスポンス調整
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt);
	}

	/**
	 * #PADC#
	 * 10連ガチャ
	 * プレミアムガチャを回し、景品をUserに付与する.
	 * 得たUserCardオブジェクトを返す.
	 * 追加ガチャの時はextra_gachaを指定する.
	 * 1～9回まで通常ラインナップ、10回目をレア度5以上のラインナップから選出する
	 **/
	private static function playPremiumGachaCnt($user_id, $bm, $extra_gacha = null, $token, $gacha_discount = null)
	{
		User::getUserBalance($user_id, $token);
		$billno = 0;

		$log_data = array();
		// 消費する魔石数を算定.
		$cost = ($extra_gacha === null) ? self::COST_MAGIC_STONE_PREMIUM_10 : (int)$extra_gacha->price10;
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			$gold = $user->gold + $user->pgold;
			// 消費魔石チェック.
			if($user->checkHavingGold($cost) === FALSE) {
				throw new PadException(RespCode::FAILED_GACHA, "playPremiumGachaCnt FAILED_GACHA user_id:".$user_id." not enough gold. __NO_TRACE");
			}
			$log_data["gold_before"] = (int)$user->gold;
			$log_data["pgold_before"] = (int)$user->pgold;

			// 10連ガチャ結果（レア度5確定）
			if($extra_gacha === NULL){
				$result_gacha_prizes = static::takeGachaPrizeCnt(self::TYPE_PREMIUM, null, self::MAX_GACHA_CNT, 5);
			}
			else {
				// 追加ガチャの場合、確定レア度をデータから参照する
				$result_gacha_prizes = static::takeGachaPrizeCnt(self::TYPE_EXTRA, $extra_gacha->gacha_id, self::MAX_GACHA_CNT, $extra_gacha->rare);
			}
			if(count($result_gacha_prizes) != self::MAX_GACHA_CNT) {
				throw new PadException(RespCode::FAILED_GACHA, "playPremiumGachaCnt FAILED_GACHA result_gacha_prizes not found. __NO_TRACE");
			}

			// 欠片（及びモンスター）追加処理
			list ($result_get_pieces, $result_pieces, $get_cards) = Gacha::addGachaPrizes($user_id, $result_gacha_prizes, $pdo);

			//#PADC# ---------------begin------------------
			// 図鑑登録数の更新
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			//#PADC# ---------------end------------------

			// #PADC# ----------begin----------
			// ガチャ回数をカウント
			$user_count = UserCount::getByUserId($user_id, null, $pdo);
			$user_count->addCount(UserCount::TYPE_GACHA_GOLD, self::MAX_GACHA_CNT);
			$user_count->addCount(UserCount::TYPE_DAILY_GACHA_GOLD, self::MAX_GACHA_CNT);
			$user_count->update($pdo);
			// #PADC# ----------end----------
					
			//#PADC# ---------------begin------------------
			// 割引対応
			if ($gacha_discount) {
				// 割引後のコストは四捨五入
				$cost = round($cost * (10000 - $gacha_discount->ratio) / 10000);
				// 割引を利用したデータを残す
				UserGachaDiscount::setUsed($user_id, $gacha_discount->id, $pdo);
			}
			//#PADC# ---------------end------------------

			// TODO: ログ内容調整
			$log_data["prize_card"] = array();
			$log_data["prize_card"]["piece_id"] = $result_get_pieces;

			// #PADC# 魔法石消費.
			$billno = $user->payGold($cost, $token, $pdo);

			// #PADC_DY# ----------begin----------
			$gacha_bonus = GachaBonus::findBy(array("gacha_id" => Gacha::ID_PREMIUM));
			$gacha_bonus->amount *= self::MAX_GACHA_CNT;
			$user = GachaBonus::applyBonuses($user, $gacha_bonus);
			// #PADC_DY# ----------end----------

			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$log_data["gold_after"] = (int)$user->gold;
			$log_data["pgold_after"] = (int)$user->pgold;
			$gold = $user->gold+$user->pgold;
			$fripnt = $user->fripnt;
			$log_data['dev'] = (int)$user->device_type;
			$log_data['area'] = (int)$user->area_id;

			$pdo->commit();

			//#PADC#
			$sequence = Tencent_Tlog::getSequence($user->id);

			if($extra_gacha === null){
				// FIXME: ログ出力は要調整
				UserLogRareGacha::log($user->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_PREMIUM_GACHA, $gacha_bonus->amount, Gacha::ID_PREMIUM);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_PREMIUM_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence, $gacha_bonus->amount);
				// #PADC# ----------end----------
			}else{
				UserLogExtraGacha::log($user->id, $extra_gacha->id, $log_data, $pdo);

				// #PADC# TLOG ----------begin----------
				UserTlog::sendTlogMoneyFlow($user, -$cost, Tencent_Tlog::REASON_BUY_GACHA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]), $sequence, Tencent_Tlog::SUBREASON_BUY_EXTRA_PREMIUM_GACHA, $gacha_bonus->amount, $extra_gacha->gacha_id);

				// 更新扭蛋次数计数器，并上报手Q，目前只记录IP扭蛋
				$cnt = UserCount::incrUserGachaDailyPlayCount($user->id, $extra_gacha->gacha_id, false);

				$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_IP_GACHA_TEN, $token, $cnt);

				self::sendTlogItemFlow($user->id, $result_get_pieces, $result_pieces, $get_cards, Tencent_Tlog::ITEM_SUBREASON_BUY_EXTRA_PREMIUM_GACHA, $cost, Tencent_Tlog::MONEY_TYPE_DIAMOND, $sequence, $gacha_bonus->amount);
				// #PADC# ----------end----------
			}
			
			// #PADC# report score to Tencent MSDK
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
			
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			// #PADC# 魔法石消費を取り消す ----------begin----------
			if($billno){
				$user->cancelPay($cost, $billno, $token);
			}
			// ----------end----------
			throw $e;
		}
		// #PADC# レスポンス調整
		// #PADC_DY# 加入gacha报酬物品
		return array($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus);
	}

	/**
	 * 指定されたガチャタイプについて、現時点でのボーナスを勘案した上で、
	 * 景品のGachaPrizeのリストを返す. キャッシュする.
	 * TYPE_SPAREDが指定された場合は何も返さない.
	 */
	public static function getGachaPrizes($gacha_type, $ext_gacha_id = null) {
		$gacha_prizes = array();
		if($gacha_type == self::TYPE_FRIEND) {
			$bonus = LimitedBonus::getActiveFriendGacha();
			if($bonus) {
				$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => $bonus->target_id), "prob ASC");
			}else{
				// #PADC# ----------begin----------
				// gacha_type ではなく gacha_id 指定で取得
				$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => self::ID_FRIEND), "prob ASC");
				// #PADC# ----------end----------
			}
		}else if($gacha_type == self::TYPE_CHARGE) {
			$bonus = LimitedBonus::getActiveChargeGacha();
			if($bonus) {
				$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => $bonus->target_id), "prob ASC");
			}else {
				// #PADC# ----------begin----------
				// gacha_type ではなく gacha_id 指定で取得
				$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => self::ID_CHARGE), "prob ASC");
				// #PADC# ----------end----------
			}
		}else if($gacha_type == self::TYPE_THANKS) {
			$gacha_prizes = GachaPrize::getAllBy(array("gacha_type" => self::TYPE_THANKS), "prob ASC");
		}else if($gacha_type == self::TYPE_EXTRA) {
			$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => $ext_gacha_id), "prob ASC");
		// #PADC# プレミアムガチャ
		}else if($gacha_type == self::TYPE_PREMIUM) {
			$bonus = LimitedBonus::getActivePremiumGacha();
			if($bonus) {
				$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => $bonus->target_id), "prob ASC");
			}else {
				// #PADC# ----------begin----------
				// gacha_type ではなく gacha_id 指定で取得
				$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => self::ID_PREMIUM), "prob ASC");
				// #PADC# ----------end----------
			}
		}
		// #PADC# チュートリアルガチャ
		else if($gacha_type == self::TYPE_TUTORIAL) {
			// gacha_type ではなく gacha_id 指定で取得
			$gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => self::ID_TUTORIAL), "prob ASC");
		}
		return $gacha_prizes;
	}

	/**
	 * 指定されたタイプのガチャを回し、景品を得る.
	 * GachaPrizeオブジェクトを返す.
	 */
	public static function takeGachaPrize($gacha_type, $ext_gacha_id = null) {
		$gacha_prize = null;
		$gacha_prizes = static::getGachaPrizes($gacha_type, $ext_gacha_id);
		$gacha_id = $gacha_prizes[0]->gacha_id;
		$seed = mt_rand(1, GachaPrize::getSumProbByGachaId($gacha_id));
		for($i=0; count($gacha_prizes); $i++) {
			$seed -= $gacha_prizes[$i]->prob;
			if($seed <= 0) {
				$gacha_prize = $gacha_prizes[$i];
				break;
			}
		}
		return $gacha_prize;
	}

	/**
	 * 指定されたタイプのガチャを回し、景品を得る.
	 * GachaPrizeオブジェクト配列を返す.
	 * 回す回数と確定レア度を指定できる.
	 */
	public static function takeGachaPrizeCnt($gacha_type, $ext_gacha_id = null, $gacha_cnt = 1, $rare = 0) {
		$result_gacha_prizes = array();
		$gacha_prizes = static::getGachaPrizes($gacha_type, $ext_gacha_id);
		if(count($gacha_prizes) == 0) {
			throw new PadException(RespCode::FAILED_GACHA, "takeGachaPrizeCnt FAILED_GACHA gacha_prizes not found. __NO_TRACE");
		}

		// 景品の確率合計値とレア度を絞った配列を取得
		$sum_prob = 0;
		$sum_rare_prob = 0;
		$rare_gacha_prizes = array();
		foreach ($gacha_prizes as $gacha_prize) {
			$sum_prob += $gacha_prize->prob;
			if ($gacha_prize->rare >= $rare) {
				$sum_rare_prob += $gacha_prize->prob;
				$rare_gacha_prizes[] = $gacha_prize;
			}
		}
		if($rare > 0 && count($rare_gacha_prizes) == 0) {
			throw new PadException(RespCode::FAILED_GACHA, "takeGachaPrizeCnt FAILED_GACHA rare_gacha_prizes not found. __NO_TRACE");
		}

		// 連続ガチャ
		// 確定レア度指定があれば-1
		$gacha_cnt = ($rare > 0) ? $gacha_cnt - 1 : $gacha_cnt;
		for($i=0; $i<$gacha_cnt; $i++) {
			$seed = mt_rand(1, $sum_prob);
			foreach ($gacha_prizes as $gacha_prize) {
				$seed -= $gacha_prize->prob;
				if($seed <= 0) {
					$result_gacha_prizes[] = $gacha_prize;
					break;
				}
			}
		}

		// レア確定のガチャ
		if($rare > 0) {
			$seed = mt_rand(1, $sum_rare_prob);
			foreach ($rare_gacha_prizes as $gacha_prize) {
				$seed -= $gacha_prize->prob;
				if($seed <= 0) {
					$result_gacha_prizes[] = $gacha_prize;
					break;
				}
			}
		}

		// 結果をシャッフル
		//shuffle($result_gacha_prizes);
		return $result_gacha_prizes;
	}

	/**
	 * #PADC# 未使用
	 * 実際にカードを追加する処理.
	 */
	private static function addCardReserve($user_id, $gacha_prize, $plus_egg_type, $pdo){
		/*
		// 以下の種類の場合は、「＋」値を付けない https://61.215.220.70/redmine-pad/issues/764
		$arr_outside_plus_egg = array(
			Card::MONSTER_TYPE_EVOLUTION, // 00:進化用モンスター
			Card::MONSTER_TYPE_FEED, // 14:強化合成用モンスター
			Card::MONSTER_TYPE_MONEY, // 15:換金用モンスター
		);
		$card = Card::get($gacha_prize->card_id);
		if(!in_array($card->mt, $arr_outside_plus_egg)) {
			// 卵＋値を取得
			$plus_egg = PlusEgg::getPlusParam($plus_egg_type);
			$plus_hp = $plus_egg->hp;
			$plus_atk = $plus_egg->atk;
			$plus_rec = $plus_egg->rec;
		} else {
			$plus_hp = 0;
			$plus_atk = 0;
			$plus_rec = 0;
		}
		// 景品付与(保留).
		$user_card = UserCard::addCardsToUserReserve(
			$user_id,
			$gacha_prize->card_id,
			$gacha_prize->getLevel(),
			UserCard::DEFAULT_SKILL_LEVEL,
			$pdo,
			$plus_hp,
			$plus_atk,
			$plus_rec,
			0 // psk
		);

		return $user_card;
		*/
		return;
	}

	/**
	 * #PADC#
	 * 実際に欠片とカードを追加する処理.
	 */
	private static function addGachaPrizes($user_id, $gacha_prizes, $pdo){
		// 入手対象欠片の追加準備
		$result_get_pieces = array();
		$add_pieces = array();
		$add_cards = array();
		foreach ($gacha_prizes as $gacha_prize) {
			$piece_id = $gacha_prize->piece_id;

			if (array_key_exists($piece_id, $add_pieces)) {
				$user_piece = $add_pieces[$piece_id];
			}
			else {
				// ユーザーの欠片オブジェクトを取得（reserve）
				// 新入手の欠片であってもこの時点ではテーブルデータは作成されない
				$user_piece = UserPiece::getUserPieceReserve($user_id, $piece_id, $pdo);
			}

			// 生成したことがあるかどうかチェック
			if(UserPiece::isCreated($user_piece)) {
				$add_piece_num = $gacha_prize->piece_num2;
			}
			else {
				$add_piece_num = $gacha_prize->piece_num;
			}

			$result_get_pieces[] = array(
				(int)$piece_id,
				(int)$add_piece_num,
			);

			// 入手時のLVをセット
			$initInfo = array(
				'lv' => $gacha_prize->getLevel(),
				'slv' => UserCard::DEFAULT_SKILL_LEVEL,
			);

			// 欠片を付与した場合に生成されるカード情報を取得
			// user_piece が reserve 状態のため、この時点ではカードもまだ生成されない
			$add_card = $user_piece->addPieceWithInitInfo($add_piece_num, $pdo, $initInfo);
			if(isset($add_card))
			{
				$add_cards[] = $add_card;
			}

			$add_pieces[$piece_id] = $user_piece;
		}

		// 欠片（及びモンスター）追加処理
		list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

		return array($result_get_pieces, $result_pieces, $get_cards);
	}

	/**
	 * 追加対象となる欠片の個数整形
	 * @param GachaPrize $gacha_prize		抽選対象
	 * @param array $user_pieces			所持欠片情報
	 * @param array $pieces					欠片マスター（IDをキーとした配列）
	 * @param array $check_user_piece_nums	ガチャ実行前の所持数と実行中の所持数の総数をセット（※生成チェック）用
	 * @param array $add_piece_nums			ガチャ実行の総取得数をセット用
	 * @param array $result_get_pieces		ガチャ実行の結果を個々にセット用
	 * @return multitype:unknown Ambigous <multitype:, array, int>
	 */
	private static function getAddPieceList($gacha_prize,$user_pieces,$pieces,$check_user_piece_nums,$add_piece_nums,$result_get_pieces)
	{
		$piece_id	= $gacha_prize->piece_id;
		$piece_num	= 0;

		// モンスターの欠片の場合、生成されたことがあるかどうかチェック
		if($pieces[$piece_id]->isTypeMonster())
		{
			// 対象の欠片の所持状況をチェック
			if(isset($user_pieces[$piece_id]))
			{
				// 生成済みかチェック
				if(UserPiece::isCreated($user_pieces[$piece_id]))
				{
					// 生成済みの場合「piece_num2」をセット
					$piece_num = $gacha_prize->piece_num2;
				}
				// 生成したことがない場合、同タイミングでの入手の結果、生成必要数に達するかチェック
				else
				{
					// チェック用の配列にセットされているかチェック（されている場合、今回の抽選ですでにヒット済み）
					if(isset($check_user_piece_nums[$piece_id]))
					{
						// 今回の抽選中に生成必要数に達しているかチェック
						$piece_num = self::getAddPieceNum($pieces,$piece_id,$gacha_prize,$check_user_piece_nums);

						// チェック用の配列に入手数を加算
						$check_user_piece_nums[$piece_id] += $piece_num;
					}
					// チェック用の配列にセットされていない場合、生成前で生成必要数に達していない状態のため「piece_num」の数値をセットし、DB上の所持数+その時の入手数をセットする
					else
					{
						$piece_num = $gacha_prize->piece_num;
						$check_user_piece_nums[$piece_id] = $user_pieces[$piece_id]->num + $piece_num;
					}
				}
			}
			// 入手したことがない場合「piece_num」をセットし、チェック用の配列にその時の入手数をセットする
			else
			{
				// チェック用の配列にセットされているかチェック（されている場合、今回の抽選ですでにヒット済み）
				if(isset($check_user_piece_nums[$piece_id]))
				{
					// 今回の抽選中に生成必要数に達しているかチェック
					$piece_num = self::getAddPieceNum($pieces,$piece_id,$gacha_prize,$check_user_piece_nums);

					// チェック用の配列に入手数を加算
					$check_user_piece_nums[$piece_id] += $piece_num;
				}
				// チェック用の配列にセットされていない場合、生成前で生成必要数に達していない状態のため「piece_num」の数値をセットし、その時の入手数をセットする
				else
				{
					$piece_num = $gacha_prize->piece_num;
					$check_user_piece_nums[$piece_id] = $piece_num;
				}
			}
		}
		// プラスの欠片の場合は「piece_num」を固定参照
		else
		{
			$piece_num = $gacha_prize->piece_num;
		}

		// 追加する欠片の総数を加算
		$add_piece_nums = self::addPieceNumArray($add_piece_nums,$piece_id,$piece_num);

		// 個々に追加する個数
		$result_get_pieces[] = array(
			(int)$piece_id,
			(int)$piece_num,
		);

		$return_data = array(
			'add_piece_nums'		=> $add_piece_nums,
			'result_get_pieces'		=> $result_get_pieces,
			'check_user_piece_nums'	=> $check_user_piece_nums,
		);

		return $return_data;
	}

	/**
	 * 追加する欠片の個数をチェック
	 * @param array $pieces
	 * @param int $piece_id
	 * @param GachaPrize $gacha_prize
	 * @param array $check_user_piece_nums
	 */
	private static function getAddPieceNum($pieces,$piece_id,$gacha_prize,$check_user_piece_nums)
	{
		if($check_user_piece_nums[$piece_id] >= $pieces[$piece_id]->gcnt)
		{
			$piece_num = $gacha_prize->piece_num2;
		}
		else
		{
			$piece_num = $gacha_prize->piece_num;
		}
		return $piece_num;
	}

	/**
	 * 対象の配列に該当キーの項目が存在していれば加算、なければ代入
	 * @param array $add_piece_nums
	 * @param int $piece_id
	 * @param int $num
	 * @return array
	 */
	private static function addPieceNumArray($add_piece_nums,$piece_id,$num)
	{
		if(isset($add_piece_nums[$piece_id]))
		{
			$add_piece_nums[$piece_id] += $num;
		}
		else
		{
			$add_piece_nums[$piece_id] = $num;
		}
		return $add_piece_nums;
	}

	private static function sendTlogItemFlow($user_id, $result_get_pieces, $result_pieces, $get_cards, $subreason, $money, $money_type, $sequence = 0, $roundTicket = 0, $missionId = null){
		foreach($result_get_pieces as $get_piece){
			$piece_id = $get_piece[0];
			$piece_num = $get_piece[1];
			$piece_num_after = $result_pieces[$piece_id] -> num;
			UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $piece_num, $piece_num_after, Tencent_Tlog::ITEM_REASON_GACHA, $subreason, $money, $money_type, $sequence, $roundTicket, $missionId);
		}
		foreach($get_cards as $get_card){
			UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_CARD, $get_card->card_id, 1, 1, Tencent_Tlog::ITEM_REASON_GACHA, $subreason, $money, $money_type, $sequence, $roundTicket, $missionId);
		}
	}
}
