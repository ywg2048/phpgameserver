<?php
/**
 * ユーザが所有しているカード.
 */

class UserCard extends BaseUserCardModel {
	const TABLE_NAME = "user_cards";
	const DEFAULT_LEVEL = 1;
	const DEFAULT_SKILL_LEVEL = 1;
	//ログ保存時フラグ
	//「通常合成」:1、「進化合成」:2、「売却」:3
	const COMPOSITE_CARDS_FLG = 1;
	const EVOLVE_CARDS_FLG = 2;
	const SELL_CARDS_FLG = 3;

	// #PADC#
	const MAX_EVOLVE_EXP = 100000;
	protected static $columns = array(
		'user_id',
		'card_id',
		'cuid',
		'exp',
		'eexp',
		'lv',
		'slv',
		'equip1',
		'equip2',
		'equip3',
		'equip4',
		'mcnt',
		'ps',
	);

	private $master_card;

	const COMPOSITE_BONUS_NONE = 0;
	const COMPOSITE_BONUS_GOOD = 1;
	const COMPOSITE_BONUS_EXCELLENT = 2;

	// ＋値最大値
	const MAX_PLUS_VALUE = 99;

	// パッシブスキル値最大値
	const MAX_PSKILL_VALUE = 10;

	// 覚醒モンスターたまドラ(モンスターID:797).
	const KAKUSEI_CARD_ID = 797;

	// 覚醒モンスター1000日たまドラ(モンスターID:1702).
	const KAKUSEI_CARD_ID1000 = 1702;

	// たまドラベビー(モンスターID:1002).
	const TAMADORA_BABY_CARD_ID = 1002;

	// サーティワン・たまドラ(モンスターID:1475 ).
	const TAMADORA_31_CARD_ID = 1475 ;

	// たまドラベビー覚醒確率（1万分立）.
	const TAMADORA_PSK_PROB = 4000;

	// サーティワン・たまドラ覚醒確率（1万分立）.
	const TAMADORA_31_PSK_PROB = 1000;

	// 1547 確定スキレベアップモンスター火
	// 1548 確定スキレベアップモンスター水
	// 1549 確定スキレベアップモンスター木
	// 1550 確定スキレベアップモンスター光
	// 1551 確定スキレベアップモンスター闇
	const ALWAYS_SKILL_UP_CARD = "1547,1548,1549,1550,1551";

	/**
	 * 指定のカードを指定のレベル, スキルレベルでユーザに付与.
	 * 作成したUserCardオブジェクトを返す.
	 * 枚数上限チェックは違うレイヤでおこなうため、ここではチェックしない.
	 */
	public static function addCardToUser($user_id, $card_id, $level, $skill_level, $pdo, $plus_hp = 0, $plus_atk = 0, $plus_rec = 0, $psk = 0, $cuid = null, $share_pdo = null) {
		$card = Card::get($card_id, $share_pdo);

		if(is_null($cuid)){
			$cuid = UserCardSeq::getNextCuid($user_id,$pdo);
		}
		$uc = new UserCard();
		$uc->user_id = $user_id;
		$uc->card_id = $card_id;
		$uc->lv = $level;
		$uc->slv = $skill_level;
		$uc->cuid = $cuid;
		$uc->equip1 = $plus_hp;
		$uc->equip2 = $plus_atk;
		$uc->equip3 = $plus_rec;
		$uc->equip4 = $psk;
		$uc->mcnt = 0;
		$uc->exp = $card->getExpOnLevel($level);
		// #PADC#
		$uc->eexp = 0;
		$uc->create($pdo);

		// #PADC# ----------begin----------
		// 図鑑データにカードIDを登録
		$user_book = UserBook::getByUserId($user_id, $pdo);
		$user_book->addCardId($card_id);
		$user_book->update($pdo);
		// #PADC# ----------end----------

		// #PADC# ----------begin----------
		// 生成回数をカウント
		$user_count = UserCount::getByUserId($user_id, null, $pdo);
		$user_count->addCount(UserCount::TYPE_CARD_CREATE);
		$user_count->addCount(UserCount::TYPE_DAILY_CARD_CREATE);
		$user_count->update($pdo);
		// #PADC# ----------end----------

		return $uc;
	}

	public static function addCardsToUserReserve($user_id, $card_id, $level, $skill_level, $pdo, $plus_hp = 0, $plus_atk = 0, $plus_rec = 0, $psk = 0, $share_pdo = null) {
		$card = Card::get($card_id, $share_pdo);

		$uc = new UserCard();
		$uc->user_id = $user_id;
		$uc->card_id = $card_id;
		$uc->lv = $level;
		$uc->slv = $skill_level;
		$uc->cuid = null;
		$uc->equip1 = $plus_hp;
		$uc->equip2 = $plus_atk;
		$uc->equip3 = $plus_rec;
		$uc->equip4 = $psk;
		$uc->mcnt = 0;
		$uc->exp = $card->getExpOnLevel($level);
		// #PADC#
		$uc->eexp = 0;

		return $uc;
	}

	public static function addCardsToUserFix(Array $add_cards, $pdo) {

		$max_cuid = UserCardSeq::getNextCuid($add_cards[0]->user_id, $pdo, count($add_cards));

		// これから追加するカードのcuidの配列を作成.
		$next_cuid_array = range($max_cuid - count($add_cards) + 1, $max_cuid);
		foreach($add_cards as &$uc){
			$uc->cuid = array_shift($next_cuid_array);
		}

		$res = UserCard::bulk_insert($add_cards, $pdo);

		// #PADC# ----------begin----------
		// 図鑑データにカードIDを登録
		$user_id = $add_cards[0]->user_id;
		$user_book = UserBook::getByUserId($user_id, $pdo);

		foreach($add_cards as $ac){
			$user_book->addCardId($ac->card_id);
		}
		$user_book->update($pdo);
		// #PADC# ----------end----------

		// #PADC# ----------begin----------
		// 生成回数をカウント
		$user_count = UserCount::getByUserId($user_id, null, $pdo);
		$user_count->addCount(UserCount::TYPE_CARD_CREATE, count($add_cards));
		$user_count->addCount(UserCount::TYPE_DAILY_CARD_CREATE, count($add_cards));
		$user_count->update($pdo);
		// #PADC# ----------end----------

		return $add_cards;
	}

	/**
	 * 売却価格を計算して返す.
	 */
	public function getSalePrice() {
		// 売却するモンスターのレベル*サーバ側定数 売却価格レベル補正係数*係数（モンスターデータN列)
		$card = $this->getMaster();
		$price = $this->lv * GameConstant::getParam("SellPriceLevel") * $card->scost;
		// ＋値ボーナス
		$price += ($this->equip1 + $this->equip2 + $this->equip3) * GameConstant::getParam("SellPlusCoinLevel");
		return round($price);
	}

	/**
	 * カードをまとめて売却する.
	 * 合計売却価格を返す.
	 */
	public static function sells($user_id, Array $cuids) {
		global $logger;
		$price = null;
		$user_cards = array();
		// ログ用.
		$log_data = array();
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			// 売却カードを取得.
			$sql = 'SELECT * FROM user_cards WHERE user_id = ? AND cuid IN (' . str_repeat('?,', count($cuids) - 1) . '?) FOR UPDATE';
			$values = array($user_id);
			$values = array_merge($values, $cuids);
			$stmt = $pdo->prepare($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
			$stmt->execute($values);
			$user_cards = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
			if(count($user_cards) != count($cuids)) {
				// 指定のカードが無い.
				$logger->log(("specified card to sell is not exist. user_id:".$user_id), Zend_Log::DEBUG);
				$pdo->rollback();
				return $price;
			}

			$user_deck = UserDeck::findBy(array("user_id" => $user_id), $pdo, TRUE);
			foreach($user_cards as $user_card) {
				if($user_deck->hasUserCardCuid($user_card->cuid)) {
					// デッキ上のカードを売却しようとした.
					$logger->log(("cannot sell the card on deck. user_id:".$user_id." cuid:".$user_card->cuid), Zend_Log::DEBUG);
					$pdo->rollback();
					return $price;
				}
			}

			$price = 0;
			$exchange_point = 0; // #PADC_DY# 宠物点数
			$user = User::find($user_id, $pdo, TRUE);
			$log_data["before_coin"] = (int)$user->coin;
			$log_data["sell_cards"] = array();
			foreach($user_cards as $user_card) {
				//価格を合計する
				// $sale_price = $user_card->getSalePrice();
				$sale_price = 0; // 出售宠物只获得点数，不得金币
				$plus = array((int)$user_card->equip1, (int)$user_card->equip2, (int)$user_card->equip3);
				$log_data["sell_cards"][] = array("card_id"=>(int)$user_card->card_id, "cuid"=>(int)$user_card->cuid, "lv"=>(int)$user_card->lv, "slv"=>(int)$user_card->slv, "plus"=>$plus, "price"=>$sale_price);
				$price += $sale_price;

				// #PADC_DY# 宠物点数
				$card = $user_card->getMaster();
				$exchange_point += (int) $card->exchange_point;
			}

			// 売却カード消費.
			UserCard::delete_cards($user_id, $cuids, $pdo);

			// 取消获得金币
			// $user->addCoin($price);
			$user->exchange_point += $exchange_point; // #PADC_DY# 宠物点数
			$log_data["after_coin"] = (int)$user->coin;
			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			// ログ.
//負荷対策のためログについてDBへのINSERTをログ出力へ変更。実行位置もcommit直後へ移動(2012/5/23)
//      UserLogSellCards::log($user->id, $log_data, $pdo);
			$pdo->commit();
			UserLogModifyCards::log($user->id, UserCard::SELL_CARDS_FLG, $log_data);

			// #PADC#
			//UserTlog::sendTlogMoneyFlow($user, $price, Tencent_Tlog::REASON_SELL_CARD, Tencent_Tlog::MONEY_TYPE_MONEY);
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		return $price;
	}

	/**
	 * 指定のユーザが保有しているカード枚数を返す.
	 */
	public static function countUserCards($user_id, $pdo = null) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		$sql = "SELECT COUNT(id) FROM user_cards where user_id = ? FOR UPDATE";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $user_id);
		$stmt->execute();
		$obj = $stmt->fetch(PDO::FETCH_NUM);

		return $obj[0];
	}

	/**
	 * 指定のユーザが保有しているカードが枚数上限を超えているかどうかをチェックし、
	 * 超えていなければTRUEを返す.
	 */
	public static function checkCarriableUserCards(User $user, $pdo = null) {
		if($user->card_max >= UserCard::countUserCards($user->id, $pdo)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 指定のユーザが保有しているカードが枚数上限に達しているかどうかをチェックし、
	 * 達していればTRUEを返す.
	 */
	public static function checkAddableUserCards(User $user, $pdo = null) {
		if($user->card_max > UserCard::countUserCards($user->id, $pdo)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * base_cuidのカードをtarget_card_idのカードに進化させる.
	 * 進化に成功したときに限りTRUEを返す.
	 */
	public static function evolve($user_id, $base_cuid, $target_card_id, Array $add_cuids) {
		global $logger;
		$succeed = FALSE;
		$coin = 0;
		// ログ用.
		$log_add_cards = array();
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			$user_card = UserCard::findBy(array("user_id" => $user_id, "cuid" => $base_cuid), $pdo, TRUE);
			if(empty($user_card)) {
				// 対象のベースカードが無い.
				$logger->log(("specified base card is not exist."), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin);
			}
			$user_deck = UserDeck::findBy(array("user_id" => $user_id), $pdo, TRUE);

			// 進化費用コインチェック. (合成費用と同等)
			$target_card = Card::get($target_card_id);
			$required_card_ids = $target_card->getRequiredCardIdsToEvolve();
			$user = User::find($user_id, $pdo, TRUE);

			$before_user = clone $user;
			$before_uc = clone $user_card;

			$evolve_price = $user_card->getEvolvePrice(count($required_card_ids));
			if($user->checkHavingCoin($evolve_price) === FALSE) {
				// コイン不足.
				$logger->log(("not enough coin"), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin);
			}

			// 進化可否チェック.
			$base_card = Card::get($user_card->card_id);
			if($target_card->canBeEvolvedFrom($base_card) === FALSE || $user_card->lv != $base_card->mlv) {
				// 進化不可能.
				$logger->log(("cannot evolve from that".$target_card->gupc), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin);
			}

			// 進化専用カード保有チェック.
			$sql = 'SELECT * FROM user_cards WHERE user_id = ? AND cuid IN (' . str_repeat('?,', count($add_cuids) - 1) . '?) FOR UPDATE';
			$values = array($user_id);
			$values = array_merge($values, $add_cuids);
			$stmt = $pdo->prepare($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
			$stmt->execute($values);
			$add_user_cards = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
			if((count($add_user_cards) != count($add_cuids)) || (count($add_user_cards) != count($required_card_ids))) {
				// 指定の枚数と保有枚数が異なる.
				$logger->log(("does not have add cards"), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin);
			}

			// 枚数チェックOK. 餌カードがデッキ上に無いかチェック.
			foreach($add_user_cards as $add_user_card) {
				if($user_deck->hasUserCardCuid($add_user_card->cuid)) {
					// デッキ上のカードを餌にしようとしたため、終了.
					$logger->log(("cannot add on deck"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, $coin);
				}
				$plus = array(
					(int)$add_user_card->equip1,
					(int)$add_user_card->equip2,
					(int)$add_user_card->equip3,
					(int)$add_user_card->equip4,
				);
				$log_add_cards[] = array("card_id" => (int)$add_user_card->card_id, "cuid" => (int)$add_user_card->cuid, "lv" => (int)$add_user_card->lv, "slv" => (int)$add_user_card->slv, "plus" => $plus);
			}

			// 餌カードごとに合成結果を算定する.
			$add_user_card_ids = array();
			foreach($add_user_cards as $add_user_card) {
				$add_user_card_ids[] = $add_user_card->card_id;
			}
			sort($required_card_ids);
			sort($add_user_card_ids);
			for($i = 0; $i < count($required_card_ids); $i++) {
				if($add_user_card_ids[$i] != $required_card_ids[$i]){
					// 必要なカードが見つからなかった.
					$logger->log(("does not have required cards. some cards might be in user deck."), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, $coin);
				}
			}

			// 進化.
			$user_card->card_id = $target_card->id;
			$user_card->lv = UserCard::DEFAULT_LEVEL;
			$user_card->exp = 0;
			if($base_card->hasSameSkill($target_card) === FALSE || $user_card->slv == 0) {
				$user_card->slv = UserCard::DEFAULT_SKILL_LEVEL;
			}
			$user_card->update($pdo);

			// コイン消費.
			$user->addCoin(-1 * $evolve_price);
			$coin = $user->coin;

			// ベースカードがリーダー（サブリーダー）であれば、進化合成についても更新.
			$lc_data = $user_card->setLeaderCard($user);
			$user->lc = join(",", $lc_data);

			// PADC版追加
			$ldeck = $user_card->setLeaderDeckCard($user);
			$user->ldeck = json_encode($ldeck);

			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);

			// 進化カード消費.
			UserCard::delete_cards($user_id, $add_cuids, $pdo);

			$pdo->commit();

			// ログ.
			$log_data = UserCard::setLogData($before_user, $user, $before_uc, $user_card, $log_add_cards);
			UserLogModifyCards::log($user->id, UserCard::EVOLVE_CARDS_FLG, $log_data);

			// #PADC#
			//UserTlog::sendTlogMoneyFlow($user, -1 * $evolve_price, Tencent_Tlog::REASON_CARD_EVOLUTION, Tencent_Tlog::MONEY_TYPE_MONEY);

			$succeed = TRUE;
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}

		return array($succeed, $coin);
	}

	/**
	 * base_cuidのカードにadd_cuidsリストのカードを合成させる.
	 * 成否(TRUE or FALSE), スキルレベル増分, 増加経験値, ボーナス発生状況のリストを返す.
	 */
	public static function composite($user_id, $base_cuid, $sevo, $devo, Array $add_cuids) {
		global $logger;
		$succeed = FALSE;
		$composite_bonus = UserCard::COMPOSITE_BONUS_NONE;

		// ログ用.
		$log_add_cards = array();

		// cuidリストのユニークチェック.
		$cuids = array_merge(array($base_cuid), $add_cuids);
		if(count($cuids) == count(array_unique($cuids))) {
			try {
				$pdo = Env::getDbConnectionForUserWrite($user_id);
				$pdo->beginTransaction();

				// 最大レベル到達チェック.
				$user_card = UserCard::findBy(array("user_id" => $user_id, "cuid" => $base_cuid), $pdo, TRUE);
				if(empty($user_card)) {
					// ベースカードがない.
					$logger->log(("specified base card is not exist"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, 0, 0, 0, $composite_bonus);
				}
				$user_deck = UserDeck::findBy(array("user_id" => $user_id), $pdo, TRUE);
				$base_card = $user_card->getMaster();
				$skill_lcap = max(Skill::get($base_card->skill)->lcap, UserCard::DEFAULT_SKILL_LEVEL);
				$level_max = FALSE;
				if($user_card->lv >= $base_card->mlv) {
					$level_max = TRUE;
				}

				$user = User::find($user_id, $pdo, TRUE);

				$before_user = clone $user;
				$before_uc = clone $user_card;

				// 餌カードを取得.
				$sql = 'SELECT * FROM user_cards WHERE user_id = ? AND cuid IN (' . str_repeat('?,', count($add_cuids) - 1) . '?) FOR UPDATE';
				$values = array($user_id);
				$values = array_merge($values, $add_cuids);
				$stmt = $pdo->prepare($sql);
				$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
				$stmt->execute($values);
				$add_user_cards = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
				if(count($add_user_cards) != count($add_cuids)) {
					// 指定の枚数と保有枚数が異なる.
					$logger->log(("does not have add cards"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, 0, 0, 0, $composite_bonus);
				}

				// 枚数チェックOK. 餌カードがデッキ上に無いかチェック.
				foreach($add_user_cards as $add_user_card) {
					if($user_deck->hasUserCardCuid($add_user_card->cuid)) {
						// デッキ上のカードを餌にしようとしたため、終了.
						$logger->log(("cannot add on deck"), Zend_Log::DEBUG);
						$pdo->rollback();
						return array($succeed, 0, 0, 0, $composite_bonus);
					}
					$plus = array(
						(int)$add_user_card->equip1,
						(int)$add_user_card->equip2,
						(int)$add_user_card->equip3,
						(int)$add_user_card->equip4,
					);
					$log_add_cards[] = array(
						"card_id" => (int)$add_user_card->card_id,
						"cuid" => (int)$add_user_card->cuid,
						"lv" => (int)$add_user_card->lv,
						"slv" => (int)$add_user_card->slv,
						"plus" => $plus,
					);
				}

				// 合成ボーナス発生判定.
				if($level_max === FALSE) {
					$composite_bonus = UserCard::getCompositeBonus();
				}

				$before_plus = $user_card->equip1 + $user_card->equip2 + $user_card->equip3;

				$up_exp = 0;
				$up_skill_level = 0;
				$up_plus_hp = 0;
				$up_plus_atk = 0;
				$up_plus_rec = 0;
				$up_psk = 0;
				// 餌カードごとに合成結果を算定する.
				foreach($add_user_cards as $add_user_card) {
					// 経験値.
					if($level_max === FALSE) {
						$up_exp += $add_user_card->getCompositeExp($base_card, $composite_bonus);
					}
					// スキルレベル.
					$up_skill_level += $add_user_card->getCompositeSkillUp($base_card, $user_card);
					// HP＋値
					$up_plus_hp += $add_user_card->equip1;
					// ATK＋値
					$up_plus_atk += $add_user_card->equip2;
					// REC＋値
					$up_plus_rec += $add_user_card->equip3;
					// PSK値
					$up_psk += $add_user_card->getCompositePassiveSkillUp($base_card, $user_card);
				}
				// 期間限定ボーナスの適用.
				if($level_max === FALSE) {
					$up_exp = UserCard::applyLimitedCompositeBonus($up_exp);
					$user_card->exp = min($user_card->exp + $up_exp, $base_card->pexpa);
				}
				$user_card->slv = min($user_card->slv + $up_skill_level, $skill_lcap);
				$user_card->equip1 = min($user_card->equip1 + $up_plus_hp, UserCard::MAX_PLUS_VALUE);
				$user_card->equip2 = min($user_card->equip2 + $up_plus_atk, UserCard::MAX_PLUS_VALUE);
				$user_card->equip3 = min($user_card->equip3 + $up_plus_rec, UserCard::MAX_PLUS_VALUE);
				$user_card->equip4 = min($user_card->equip4 + $up_psk, UserCard::MAX_PSKILL_VALUE);

				$after_plus = $user_card->equip1 + $user_card->equip2 + $user_card->equip3;

				// コイン保有チェック.
				$composite_price = $user_card->getCompositePrice(count($add_cuids));
				// 餌カードに＋値が（かつ合計後の＋値に変化）あった場合に合成に掛る値段が変化する
				if($after_plus > $before_plus) {
					$composite_price += ($after_plus * GameConstant::getParam("CompositePlusCoinLevel"));
				}
				if($user->checkHavingCoin($composite_price) === FALSE) {
					// コイン不足.
					$logger->log(("not enough coin"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, 0, 0, 0, $composite_bonus);
				}

				// 副属性の究極進化チェック.
				$evolve_flg = false;
				if(isset($sevo)){
					// 究極進化.
					$evolve_card_id = $sevo;
					$evolve_card = Card::get($evolve_card_id);
					if($evolve_card->spup != 0) { // 究極進化可能かチェック.
						$required_card_ids = array($evolve_card->gup1);
						if($evolve_card->gup2) $required_card_ids[] = $evolve_card->gup2;
						if($evolve_card->gup3) $required_card_ids[] = $evolve_card->gup3;
						if($evolve_card->gup4) $required_card_ids[] = $evolve_card->gup4;
						if($evolve_card->gup5) $required_card_ids[] = $evolve_card->gup5;
						if($evolve_card->gupc == $user_card->card_id) {
							$evolve_flg = true;
						}else{
							$logger->log(("required gupc:$evolve_card->gupc card_id:$user_card->card_id"), Zend_Log::DEBUG);
						}
					}else{
						$logger->log(("#Cheat!# api:composite user_id:$user_id base:$user_card->card_id sevo:$evolve_card_id"), Zend_Log::DEBUG);
						$pdo->rollback();
						return array($succeed, 0, 0, 0, $composite_bonus);
					}
				}
				if(isset($devo)){
					// カード退化.
					$evolve_card_id = $devo;
					$evolve_card = Card::get($evolve_card_id);
					$required_card_ids = array($base_card->dev1);
					if($base_card->dev2) $required_card_ids[] = $base_card->dev2;
					if($base_card->dev3) $required_card_ids[] = $base_card->dev3;
					if($base_card->dev4) $required_card_ids[] = $base_card->dev4;
					if($base_card->dev5) $required_card_ids[] = $base_card->dev5;
					if($base_card->gupc == $evolve_card_id) {
						$evolve_flg = true;
					}else{
						$logger->log(("required gupc:$base_card->gupc card_id:$evolve_card_id"), Zend_Log::DEBUG);
					}
				}
				if($evolve_flg){
					$add_user_card_ids = array();
					foreach($add_user_cards as $add_user_card) {
						$add_user_card_ids[] = $add_user_card->card_id;
					}
					foreach($required_card_ids as $required_card_id){
						if(!in_array($required_card_id, $add_user_card_ids)){
						$logger->log(("in_array required_card_id: $required_card_id"), Zend_Log::DEBUG);
							$evolve_flg = false;
						}
					}
					if($evolve_flg){
						// 究極進化に必要な素材が揃っているのでカードIDを変更.
						$user_card->card_id = $evolve_card_id;
						// 進化によりスキルが変わったときはスキルLVをリセット.
						if($base_card->hasSameSkill($evolve_card) === FALSE) {
							$user_card->slv = UserCard::DEFAULT_SKILL_LEVEL;
						}
					}else{
						// 必要なカードが見つからなかった.
						$logger->log(("does not have required cards."), Zend_Log::DEBUG);
						$pdo->rollback();
						return array($succeed, 0, 0, 0, $composite_bonus);
					}
				}

				// ベースカード更新.
				$user_card->setLevelOnExp();
				$user_card->mcnt += 1;
				$user_card->update($pdo);

				// 合成費用消費.
				$user->addCoin(-1 * $composite_price);

				// cuid,id,lv,slv,hp,atk,rec,pskのカンマ区切りの文字列*2個(リーダー、サブリーダー分).
				$lc_data = $user_card->setLeaderCard($user);
				$user->lc = join(",", $lc_data);

				// PADC版追加
				$ldeck = $user_card->setLeaderDeckCard($user);
				$user->ldeck = json_encode($ldeck);

				$user->accessed_at = User::timeToStr(time());
				$user->accessed_on = $user->accessed_at;
				$user->update($pdo);

				// 餌カード削除.
				UserCard::delete_cards($user_id, $add_cuids, $pdo);

				$pdo->commit();
				// ログ.
				$log_data = UserCard::setLogData($before_user, $user, $before_uc, $user_card, $log_add_cards);
				UserLogModifyCards::log($user->id, UserCard::COMPOSITE_CARDS_FLG, $log_data);

				// #PADC# Tlog
				//UserTlog::sendTlogMoneyFlow($user, -1 * $composite_price, Tencent_Tlog::REASON_CARD_COMPOSITE, Tencent_Tlog::MONEY_TYPE_MONEY);

				$succeed = TRUE;
			// #PADC# PDOException → Exception
			} catch (Exception $e) {
				if ($pdo->inTransaction()) {
					$pdo->rollback();
				}
				throw $e;
			}
		}

		$slup = $user_card->slv - $before_uc->slv;
		$aexp = $user_card->exp - $before_uc->exp;
		$coin = $user->coin;

		return array($succeed, $slup, $aexp, $coin, $composite_bonus);
	}


// #PADC# ----------begin----------
	/**
	 * 欠片進化。
	 */
	public static function pieceEvolve($user_id, $base_cuid, $target_card_id, Array $add_piece_datas)
	{
		global $logger;
		$succeed = FALSE;
		$coin = 0;
		// log用.
		$log_add_cards = array();
		$piece_ids = array_keys($add_piece_datas);
		$ret_piece = array();
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			$user_card = UserCard::findBy(array("user_id" => $user_id, "cuid" => $base_cuid), $pdo, TRUE);
			if(empty($user_card)) {
				// base card不存在的时候
				$logger->log(("specified base card is not exist."), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}
			$user_deck = UserDeck::findBy(array("user_id" => $user_id), $pdo, TRUE);

			// 进化费用check（合成费用相同）

			$target_card = Card::get($target_card_id);
			$ultimate_evolve = $target_card->spup != 0;
			// 究极进化的时候，客户端不传piece的参数，从数据库中取
			if($ultimate_evolve){
				$add_piece_datas = self::getUltimatePieces($target_card);
				$piece_ids = array_keys($add_piece_datas);
			}
			// 取得进化所必要的碎片数量
			$gup_index = $target_card->getGupcIndex($user_card->card_id);
			$required_evolve_exp = $target_card->getRequiredEvolveExp($gup_index);
			$user = User::find($user_id, $pdo, TRUE);

			$before_user = clone $user;
			$before_uc = clone $user_card;

			$evolve_price = $user_card->getPieceEvolvePrice($required_evolve_exp);
			if($user->checkHavingCoin($evolve_price) === FALSE) {
				// coin不足
				$logger->log(("not enough coin"), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}

			// 是否可以进化check
			$base_card = Card::get($user_card->card_id);
			if($target_card->canBeEvolvedFrom($base_card,$gup_index) === FALSE || $user_card->lv != $base_card->mlv) {
				// 无法进化
				$logger->log(("cannot evolve from that".$target_card->gupc), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}

			// 究极进化后的宠物不限制拥有个数
			/*
			// 究极进化禁止持有复数个
			if ($ultimate_evolve) {
				//既存逻辑只能让用户拥有全部觉醒卡牌中的1个。
				$ultimate_cards = Card::getAllBy(array('spup' => $target_card->spup));
				$ultimate_card_ids = array();
				foreach ($ultimate_cards as $ultimate_card) {
					$ultimate_card_ids[] = $ultimate_card->id;
				}
				$user_ultimate_cards = self::findByCardIds($user_id, $ultimate_card_ids, $pdo);
				$ultimate_card_num = 0;
				foreach ($user_ultimate_cards as $user_ultimate_card) {
					if ($user_ultimate_card->cuid != $user_card->cuid) {
						$ultimate_card_num++;
					}
				}

				if ($ultimate_card_num > 0) {
					$logger->log(("cannot evolve have much" . $target_card->spup), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, $coin, $ret_piece);
				}
				// 究极进化无法拥有同类卡牌多个
				$target_user_card = UserCard::findBy(array('user_id' => $user_id, 'card_id' => $target_card_id), $pdo);
				if ($target_user_card) {
					$logger->log(("cannot evolve have much" . $target_card->spup), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, $coin, $ret_piece);
				}

			}
			*/


			// 碎片取得
			$add_user_pieces = UserPiece::findByPieceIds($user_id, $piece_ids, $pdo);
			// MY : 強化時に加算されているものをベースとして計算。
			$piece_exp = $user_card->eexp;
			foreach($add_user_pieces as $add_user_piece)
			{
				$piece_exp += $add_piece_datas[$add_user_piece->piece_id]['num'] * $add_user_piece->getMaster()->eexp;
			}
			// MY PADC : 経験値が足りているかチェック
			// 究极进化不要求eexp
			if($piece_exp < $required_evolve_exp && !$ultimate_evolve)
			{
				$logger->log(("enough exp"), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}
			if(count($add_user_pieces) != count($piece_ids)) {
				// 指定の種類と保有種類数が異なる.
				$logger->log(("does not have add pieces"), Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}

			//Tlog
			$tlog_pieces = array();

			foreach($add_user_pieces as $add_user_piece)
			{
				if($add_user_piece->num < $add_piece_datas[$add_user_piece->piece_id]['num'])
				{
					$logger->log(("does not enough add piece"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, $coin, $ret_piece);
				}
				if(!$ultimate_evolve) {
					if ($target_card->canUseEvolvePiece($add_user_piece->getMaster(), $gup_index) == FALSE) {
						$logger->log(("cannot use piece"), Zend_Log::DEBUG);
						$pdo->rollback();
						return array($succeed, $coin, $ret_piece);
					}
				}
				$piece_reduce = $add_piece_datas[$add_user_piece->piece_id]['num'];
				$add_user_piece->subtractPiece($piece_reduce);
				$ret_piece[] = UserPiece::arrangeColumn($add_user_piece);

				//Tlog
				$tlog_pieces[$add_user_piece->piece_id] = array(
						'piece_add' => -$piece_reduce,
						'after_piece_num' => $add_user_piece->num,
						'master_piece' => $add_user_piece->getMaster(),
				);

			}

			// MY PADC DELETE : 欠片ベースになるのでカード保有チェックは削除 :進化専用カード保有チェック.
			// $sql = 'SELECT * FROM user_cards WHERE user_id = ? AND cuid IN (' . str_repeat('?,', count($add_cuids) - 1) . '?) FOR UPDATE';
			// $values = array($user_id);
			// $values = array_merge($values, $add_cuids);
			// $stmt = $pdo->prepare($sql);
			// $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
			// $stmt->execute($values);
			// $add_user_cards = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
			// if((count($add_user_cards) != count($add_cuids)) || (count($add_user_cards) != count($required_card_ids))) {
			//   // 指定の枚数と保有枚数が異なる.
			//   $logger->log(("does not have add cards"), Zend_Log::DEBUG);
			//   $pdo->rollback();
			//   return array($succeed, $coin);
			// }
			// 枚数チェックOK. 餌カードがデッキ上に無いかチェック.
			// foreach($add_user_cards as $add_user_card) {
			//  if($user_deck->hasUserCardCuid($add_user_card->cuid)) {
			//    // デッキ上のカードを餌にしようとしたため、終了.
			//    $logger->log(("cannot add on deck"), Zend_Log::DEBUG);
			//    $pdo->rollback();
			//    return array($succeed, $coin);
			//  }
			//  $plus = array(
			//    (int)$add_user_card->equip1,
			//    (int)$add_user_card->equip2,
			//    (int)$add_user_card->equip3,
			//    (int)$add_user_card->equip4,
			//  );
			//  $log_add_cards[] = array("card_id" => (int)$add_user_card->card_id, "cuid" => (int)$add_user_card->cuid, "lv" => (int)$add_user_card->lv, "slv" => (int)$add_user_card->slv, "plus" => $plus);
			// }

			// MY PADC DELETE : この処理は欠片の仕様になるため不要 : 餌カードごとに合成結果を算定する.
			// $add_user_card_ids = array();
			// foreach($add_user_cards as $add_user_card) {
			// $add_user_card_ids[] = $add_user_card->card_id;
			// }
			// sort($required_card_ids);
			// sort($add_user_card_ids);
			// for($i = 0; $i < count($required_card_ids); $i++) {
			//  if($add_user_card_ids[$i] != $required_card_ids[$i]){
			//    // 必要なカードが見つからなかった.
			//    $logger->log(("does not have required cards. some cards might be in user deck."), Zend_Log::DEBUG);
			//    $pdo->rollback();
			//    return array($succeed, $coin);
			//  }
			// }

			// 進化.
			$user_card->card_id = $target_card->id;
			$user_card->lv = UserCard::DEFAULT_LEVEL;
			$user_card->exp = 0;
			$user_card->eexp = 0;
			if($base_card->hasSameSkill($target_card) === FALSE || $user_card->slv == 0) {
				$user_card->slv = UserCard::DEFAULT_SKILL_LEVEL;
			}
			$user_card->update($pdo);

			// #PADC# ----------begin----------
			// 図鑑データにカードIDを登録
			//更新玩家拥有的图鉴数目
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user_book->addCardId($target_card_id);
			$user_book->update($pdo);
			$user->book_cnt = $user_book->getCountIds();
			// #PADC# ----------end----------

			// #PADC# ----------begin----------
			// 進化合成回数をカウント
			//进化、强化次数统计
			$user_count = UserCount::getByUserId($user_id, null, $pdo);
			$user_count->addCount(UserCount::TYPE_CARD_EVOLVE);
			$user_count->addCount(UserCount::TYPE_DAILY_CARD_EVOLVE);
			$user_count->update($pdo);
			// #PADC# ----------end----------

			// コイン消費.
			//扣除进化需要的金币
			$user->addCoin(-1 * $evolve_price);
			$coin = $user->coin;

			// 累计消费金币活动如果开启
			$activity = Activity::getByType(Activity::ACTIVITY_TYPE_COIN_CONSUM);
			if($activity){
				$uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
				$uac->addCounts(Activity::ACTIVITY_TYPE_COIN_CONSUM, $evolve_price);
				$uac->update($pdo);
			}

			// 进化累计活动如果开启
			$activity = Activity::getByType(Activity::ACTIVITY_TYPE_CARD_EVO_COUNT);
			if($activity){
				$uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
				$uac->addCounts(Activity::ACTIVITY_TYPE_CARD_EVO_COUNT);
				$uac->update($pdo);
			}

			// ベースカードがリーダー（サブリーダー）であれば、進化合成についても更新.
			$lc_data = $user_card->setLeaderCard($user);
			$user->lc = join(",", $lc_data);
			// PADC版追加
			$ldeck = $user_card->setLeaderDeckCard($user);
			$user->ldeck = json_encode($ldeck);
			$user->update($pdo);
			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;

			// MY PADC DELETE : 欠片に変更されるため削除 :進化カード消費.
			// UserCard::delete_cards($user_id, $add_cuids, $pdo);

			// MY PADC ADD : 欠片消費
			foreach($add_user_pieces as $add_user_piece) {
				$add_user_piece->update($pdo);
			}
			$pdo->commit();

			// ログ.
			$log_data = UserCard::setLogData($before_user, $user, $before_uc, $user_card, $log_add_cards);
			UserLogModifyCards::log($user->id, UserCard::EVOLVE_CARDS_FLG, $log_data);


			// Tlog
			$sequence = Tencent_Tlog::getSequence($user_id);


			if($ultimate_evolve){
				UserTlog::sendTlogMoneyFlow($user, -1 * $evolve_price, Tencent_Tlog::REASON_PIECE_ULTIMATE_EVOLUTION, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, $sequence);
				foreach($tlog_pieces as $piece_id => $tlog_piece){
					UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $tlog_piece['piece_add'], $tlog_piece['after_piece_num'], Tencent_Tlog::ITEM_REASON_ULTIMATE_EVOLUTION, 0, $evolve_price, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
				}
				if($user_card->card_id == $target_card->id){
					//tlog old card
					UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_CARD, $before_uc->card_id, 1, 0, Tencent_Tlog::ITEM_REASON_ULTIMATE_EVOLUTION, 0, $evolve_price, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
					//tlog new card
					UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_CARD, $user_card->card_id, 1, 1, Tencent_Tlog::ITEM_REASON_ULTIMATE_EVOLUTION, 0, $evolve_price, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
				}
			}
			else{
				UserTlog::sendTlogMoneyFlow($user, -1 * $evolve_price, Tencent_Tlog::REASON_PIECE_EVOLUTION, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, $sequence);
				foreach($tlog_pieces as $piece_id => $tlog_piece){
					UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $tlog_piece['piece_add'], $tlog_piece['after_piece_num'], Tencent_Tlog::ITEM_REASON_EVOLUTION, 0, $evolve_price, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
				}
				if($user_card->card_id == $target_card->id){
					//tlog old card
					UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_CARD, $before_uc->card_id, 1, 0, Tencent_Tlog::ITEM_REASON_EVOLUTION, 0, $evolve_price, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
					//tlog new card
					UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_CARD, $user_card->card_id, 1, 1, Tencent_Tlog::ITEM_REASON_EVOLUTION, 0, $evolve_price, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
				}

			}
			UserTlog::sendTlogEvolution($user, $before_uc, $user_card, $base_card,$target_card, $tlog_pieces, $evolve_price);


			$succeed = TRUE;
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
			$pdo->rollback();
			}
			throw $e;
		}

		//新手嘉年华：宠物进化
		UserCarnivalInfo::carnivalMissionCheck($user->id,CarnivalPrize::CONDITION_TYPE_CARD_EVOLVE,$user_count->card_evolve);
		
		return array($succeed, $coin, $ret_piece);
	}
	/**
	 *
	 * 欠片合成。
	 */
	public static function pieceComposite($user_id, $base_cuid, $add_piece_datas)
	{

		global $logger;
		$succeed = FALSE;
		$composite_bonus = UserCard::COMPOSITE_BONUS_NONE;
		$has_skill_piece = 0;           //如果玩家有点击技能碎片，$slup返回-1
		// ログ用.
		$log_add_cards = array();

		// MY PADC DELETE : 欠片となるため、base_cuidのユニークはチェックする必要がない : cuidリストのユニークチェック.
		// $cuids = array_merge(array($base_cuid), $add_piece_datas);
		$piece_ids = array_keys($add_piece_datas);

		if(count($add_piece_datas) == count(array_unique($piece_ids))) {
			try {

				$pdo = Env::getDbConnectionForUserWrite($user_id);
				$pdo->beginTransaction();

				// 最大レベル到達チェック.
				$user_card = UserCard::findBy(array("user_id" => $user_id, "cuid" => $base_cuid), $pdo, TRUE);

				if(empty($user_card)) {
					// ベースカードがない.
					$logger->log(("specified base card is not exist"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, 0, 0, 0, $composite_bonus,null);
				}


				// MY PADC DELETE : 欠片はデッキに入らないのでデッキ情報を取得する必要がない。
				// $user_deck = UserDeck::findBy(array("user_id" => $user_id), $pdo, TRUE);
				$base_card = $user_card->getMaster();
				$skill_lcap = max(Skill::get($base_card->skill)->lcap, UserCard::DEFAULT_SKILL_LEVEL);
				$level_max = FALSE;
				if($user_card->lv >= $base_card->mlv) {
					$level_max = TRUE;
				}
				$user = User::find($user_id, $pdo, TRUE);


				$before_user = clone $user;
				$before_uc = clone $user_card;


				// MY PADC : 欠片取得
				$add_user_pieces = UserPiece::findByPieceIds($user_id, $piece_ids, $pdo);

				// $add_user_pieces = array($dummy_piece);
				if(count($add_user_pieces) != count($piece_ids)) {
					// 指定の枚数と保有枚数が異なる.
					$logger->log(("does not have add piece"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, 0, 0, 0, $composite_bonus,null);
				}

				// MY PADC : 欠片個別の数チェック

				foreach($add_user_pieces as $add_user_piece) {
					if($add_user_piece->num < $add_piece_datas[$add_user_piece->piece_id]["num"]) {
						// 欠片の数が足りない
						$logger->log(("not enough piece"), Zend_Log::DEBUG);
						$pdo->rollback();
						return array($succeed, 0, 0, 0, $composite_bonus,null);
					}
				}

				// MY PADC DELETE : 欠片になるため削除。
				// 枚数チェックOK. 餌カードがデッキ上に無いかチェック.
				// foreach($add_user_pieces as $add_user_card) {
				//   if($user_deck->hasUserCardCuid($add_user_card->cuid)) {
				//     // デッキ上のカードを餌にしようとしたため、終了.
				//     $logger->log(("cannot add on deck"), Zend_Log::DEBUG);
				//     $pdo->rollback();
				//     return array($succeed, 0, 0, 0, $composite_bonus);
				//   }
				//   $plus = array(
				//     (int)$add_user_card->equip1,
				//     (int)$add_user_card->equip2,
				//     (int)$add_user_card->equip3,
				//     (int)$add_user_card->equip4,
				//   );
				//   $log_add_cards[] = array(
				//     "card_id" => (int)$add_user_card->card_id,
				//     "cuid" => (int)$add_user_card->cuid,
				//     "lv" => (int)$add_user_card->lv,
				//     "slv" => (int)$add_user_card->slv,
				//    "plus" => $plus,
				//  );
				// }

				// MY PADC DELETE : 欠片強化から大成功ボーナスがなくなりました。
				// if($level_max === FALSE) {
				// 	$composite_bonus = UserPiece::getCompositeBonus();
				// }
				// MY PADC : スキルとプラス値は合算して費用とする。
				$before_plus = $user_card->equip1 + $user_card->equip2 + $user_card->equip3 + ($user_card->slv - 1);

				$up_exp = 0;
				$up_skill_level = 0;
				$up_plus_hp = 0;
				$up_plus_atk = 0;
				$up_plus_rec = 0;
				$up_psk = 0;
				$up_eexp = 0;
				$up_skill = 0;
				$result_pieces = array();
				$skill_cost = ($user_card->slv - 1);
				//Tlog
				$tlog_pieces = array();
				//record PIECE_TYPE_MONSTER and PIECE_TYPE_STRENGTH type pieces
				$tlog_composite__pieces = array();

				// #PADC# ----------begin----------
				// スキルアップ確率変更するかデバッグユーザー情報を取得
				$debugSkillupChange = DebugUser::isSkillUpChangeDebugUser($user_id);
				if ($debugSkillupChange) {
					// 変更スキルアップ確率を取得
					$prob_skillup_change = DebugUser::getSkillUpChangeProb($user_id);
				}
				// #PADC# ----------end----------

				// MY PADC CHANGED : カード情報から欠片の情報に変更餌
				$skill_pieces = 0;   //技能碎片的总数
				$other_pieces = 0;    //HP\ATK\REC 碎片的总数
				foreach($add_user_pieces as $add_user_piece) {
					$use_num = $add_piece_datas[$add_user_piece->piece_id]["num"];
					$master_piece = $add_user_piece->getMaster();

					if($master_piece->isTypeCompositeExp())
					{
						if($master_piece->isTypeMonster() && $base_card->gup_piece_id != $master_piece->id)
						{
							$logger->log(("not use piece"), Zend_Log::DEBUG);
							$pdo->rollback();
							return array($succeed, 0, 0, 0, $composite_bonus,null);
						}
						// 経験値.
						if($level_max === FALSE) {
							$up_exp += $add_user_piece->getCompositeExp($base_card,$use_num);
						}
						if($base_card->canChargeEvolveExp($add_user_piece->piece_id))
						{
							$up_eexp += $master_piece->eexp * $use_num;
						}
						/**
						 * there are only 3 situations will happen ,one is all monster pieces,two is all generic pieces,three is a half monster pieces and a half generic pieces
						 * so there is no problem in here,if situations not changed
						 */
						$tlog_composite__pieces[$add_user_piece->piece_id] = $use_num;
					}
					else if($master_piece->isTypeCompositePlus())
					{
						$other_pieces += $use_num;
						$plus_cost = $master_piece->mexp * $use_num;
						// MY PADC : プラスの欠片計算処理変更
						if($master_piece->isTypeHPPlus())
						{
							// HP＋値
							$up_plus_hp += $plus_cost;
						}
						else if($master_piece->isTypeATKPlus())
						{
							// ATK＋値
							$up_plus_atk += $plus_cost;
						}
						else if($master_piece->isTypeRECPlus())
						{
							// REC＋値
							$up_plus_rec += $plus_cost;
						}
					}
					else if($master_piece->isTypeSkillPlus())
					{

						if($master_piece->attr >= 0 && $master_piece->attr != $base_card->attr)
						{
							$logger->log(("not use piece"), Zend_Log::DEBUG);
							$pdo->rollback();
							return array($succeed, 0, 0, 0, $composite_bonus,null);
						}
						$skill_pieces += $use_num;

						$has_skill_piece = 1;
						for($skill_up_count = 0; $skill_up_count < $use_num; $skill_up_count++)
						{
							// MY : スキルのかけらを使用する場合、失敗してもコストとして評価する。

							$skill_cost += $master_piece->mexp;
							$seed = mt_rand(1, 10000);

							if ($debugSkillupChange) {
								// スキルアップ確率変更ありの場合
								if($seed <= $prob_skillup_change) {
									$up_skill_level += $master_piece->mexp;
								}
							}
							else {
								// MY : スキルのかけらだった場合、eexpに成功確率が設定されているため、成功判定をする。
								if($seed <= $master_piece->eexp) {
									$up_skill_level += $master_piece->mexp;
								}

							}
						}
					}

					// MY PADC DELETE : 覚醒は初期配信に実装されないため削除 : PSK値
					// $up_psk += $add_user_piece->getCompositePassiveSkillUp($base_card, $user_card);

					// MY : 消費
					$piece_reduce = $add_piece_datas[$add_user_piece->piece_id]["num"];
					$add_user_piece->subtractPiece($piece_reduce);
					$result_pieces[] = UserPiece::arrangeColumn($add_user_piece);

					//Tlog
					$tlog_pieces[$add_user_piece->piece_id] = array('piece_add' => -$piece_reduce, 'after_piece_num' => $add_user_piece->num);
				}


				// MY PADC : 最大レベル時の超過分のコストを発生させない為、取得経験値を判定。

				$up_exp = min($up_exp, $base_card->getExpOnLevel($base_card->mlv) - $user_card->exp);
				if($level_max === FALSE) {
					// MY PADC DELETE : 経験値ボーナスはなくなりました : 期間限定ボーナスの適用.
					// $up_exp = UserPiece::applyLimitedCompositeBonus($up_exp);
					$user_card->exp = $user_card->exp + $up_exp;
				}
				// MY : 進化用の経験値も蓄積させる。
				$user_card->eexp = min(self::MAX_EVOLVE_EXP,$user_card->eexp + $up_eexp);
				$user_card->slv = min($user_card->slv + $up_skill_level, $skill_lcap);

				$equip_sum = $user_card->equip1 + $user_card->equip2 + $user_card->equip3;

				$user_card->equip1 = min($user_card->equip1 + $up_plus_hp, UserCard::MAX_PLUS_VALUE);
				$user_card->equip2 = min($user_card->equip2 + $up_plus_atk, UserCard::MAX_PLUS_VALUE);
				$user_card->equip3 = min($user_card->equip3 + $up_plus_rec, UserCard::MAX_PLUS_VALUE);
				$user_card->equip4 = min($user_card->equip4 + $up_psk, UserCard::MAX_PSKILL_VALUE);
				// MY PADC : スキルとプラス値は合算して費用とする。
				$after_plus = $user_card->equip1 + $user_card->equip2 + $user_card->equip3 + $skill_cost;


				// MY : コイン保有チェック.
				// MY PADC : 仕様変更により、プラス値の合成はプラス値の加算費用のみが適用される。
				if($after_plus > $before_plus) {
					//$composite_price = ($after_plus * GameConstant::getParam("CompositePlusCoinLevel"));

					$composite_price = $skill_pieces*100000 + 250*$other_pieces*(2*$equip_sum+$other_pieces+3);
				}
				else
				{
					// MY PADC : 値段は取得経験値量に応じて変化.
					$composite_price = $user_card->getPieceCompositePrice2($add_piece_datas);
				}

				if($user->checkHavingCoin($composite_price) === FALSE) {
					// コイン不足.

					$logger->log(("not enough coin"), Zend_Log::DEBUG);
					$pdo->rollback();
					return array($succeed, 0, 0, 0, $composite_bonus, null);
				}
				// MY PADC DELETE : 仕様から究極進化がなくなった為。
				// // 副属性の究極進化チェック.
				// $evolve_flg = false;
				// if(isset($sevo)){
				//   // 究極進化.
				//   $evolve_card_id = $sevo;
				//   $evolve_card = Card::get($evolve_card_id);
				//   if($evolve_card->spup != 0) { // 究極進化可能かチェック.
				//     $required_card_ids = array($evolve_card->gup1);
				//     if($evolve_card->gup2) $required_card_ids[] = $evolve_card->gup2;
				//     if($evolve_card->gup3) $required_card_ids[] = $evolve_card->gup3;
				//     if($evolve_card->gup4) $required_card_ids[] = $evolve_card->gup4;
				//     if($evolve_card->gup5) $required_card_ids[] = $evolve_card->gup5;
				//     if($evolve_card->gupc == $user_card->card_id) {
				//       $evolve_flg = true;
				//     }else{
				//       $logger->log(("required gupc:$evolve_card->gupc card_id:$user_card->card_id"), Zend_Log::DEBUG);
				//     }
				//   }else{
				//     $logger->log(("#Cheat!# api:composite user_id:$user_id base:$user_card->card_id sevo:$evolve_card_id"), Zend_Log::DEBUG);
				//     $pdo->rollback();
				//     return array($succeed, 0, 0, 0, $composite_bonus);
				//   }
				// }
				// if(isset($devo)){
				//   // カード退化.
				//   $evolve_card_id = $devo;
				//   $evolve_card = Card::get($evolve_card_id);
				//   $required_card_ids = array($base_card->dev1);
				//   if($base_card->dev2) $required_card_ids[] = $base_card->dev2;
				//   if($base_card->dev3) $required_card_ids[] = $base_card->dev3;
				//   if($base_card->dev4) $required_card_ids[] = $base_card->dev4;
				//   if($base_card->dev5) $required_card_ids[] = $base_card->dev5;
				//   if($base_card->gupc == $evolve_card_id) {
				//     $evolve_flg = true;
				//   }else{
				//     $logger->log(("required gupc:$base_card->gupc card_id:$evolve_card_id"), Zend_Log::DEBUG);
				//   }
				// }
				// if($evolve_flg){
				//   $add_user_piece_ids = array();
				//   foreach($add_user_pieces as $add_user_piece) {
				//     $add_user_piece_ids[] = $add_user_piece->card_id;
				//   }
				//   foreach($required_card_ids as $required_card_id){
				//     if(!in_array($required_card_id, $add_user_piece_ids)){
				//      $logger->log(("in_array required_card_id: $required_card_id"), Zend_Log::DEBUG);
				//       $evolve_flg = false;
				//     }
				//   }
				//   if($evolve_flg){
				//     // 究極進化に必要な素材が揃っているのでカードIDを変更.
				//     $user_card->card_id = $evolve_card_id;
				//     // 進化によりスキルが変わったときはスキルLVをリセット.
				//     if($base_card->hasSameSkill($evolve_card) === FALSE) {
				//       $user_card->slv = UserCard::DEFAULT_SKILL_LEVEL;
				//     }
				//   }else{
				//     // 必要なカードが見つからなかった.
				//     $logger->log(("does not have required cards."), Zend_Log::DEBUG);
				//     $pdo->rollback();
				//     return array($succeed, 0, 0, 0, $composite_bonus);
				//   }
				// }

				// ベースカード更新.
				$user_card->setLevelOnExp();

				$user_card->mcnt += 1;
				$user_card->update($pdo);

				// #PADC# ----------begin----------
				// 強化合成回数をカウント（今のところ全て強化合成としてカウント）
				$user_count = UserCount::getByUserId($user_id, null, $pdo);
				$user_count->addCount(UserCount::TYPE_CARD_COMPOSITE);
				$user_count->addCount(UserCount::TYPE_DAILY_CARD_COMPOSITE);
				$user_count->update($pdo);
				// #PADC# ----------end----------

				// 合成費用消費.
				$user->addCoin(-1 * $composite_price);

				// 累计消费金币活动如果开启
				$activity = Activity::getByType(Activity::ACTIVITY_TYPE_COIN_CONSUM);
				if($activity){
					$uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
					$uac->addCounts(Activity::ACTIVITY_TYPE_COIN_CONSUM, $composite_price);
					$uac->update($pdo);
				}

				// cuid,id,lv,slv,hp,atk,rec,pskのカンマ区切りの文字列*2個(リーダー、サブリーダー分).
				$lc_data = $user_card->setLeaderCard($user);
				$user->lc = join(",", $lc_data);

				// PADC版追加
				$ldeck = $user_card->setLeaderDeckCard($user);
				$user->ldeck = json_encode($ldeck);

				$user->accessed_at = User::timeToStr(time());
				$user->accessed_on = $user->accessed_at;
				$user->update($pdo);

				foreach($add_user_pieces as $add_user_piece) {
					$add_user_piece->update($pdo);
				}
				$pdo->commit();




				// MY PADC TDOO : 一旦コメントアウト : ログ.
				// $log_data = UserCard::setLogData($before_user, $user, $before_uc, $user_card, $log_add_cards);
				// UserLogModifyCards::log($user->id, UserCard::COMPOSITE_CARDS_FLG, $log_data);

				// #PADC# Tlog
				$sequence = Tencent_Tlog::getSequence($user_id);
				UserTlog::sendTlogMoneyFlow($user, -1 * $composite_price, Tencent_Tlog::REASON_PIECE_COMPOSITE, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, $sequence);
				foreach($tlog_pieces as $piece_id => $tlog_piece){
					UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $tlog_piece['piece_add'], $tlog_piece['after_piece_num'], Tencent_Tlog::ITEM_REASON_COMPOSITE, 0, $composite_price, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
				}
				UserTlog::sendTlogComposite($user,$before_uc,$user_card,$base_card,$tlog_composite__pieces,$composite_price);

				$succeed = TRUE;
				// #PADC# PDOException → Exception
			} catch (Exception $e) {
				if ($pdo->inTransaction()) {
					$pdo->rollback();
				}
				throw $e;
			}
		}

		$slup = $user_card->slv - $before_uc->slv;
		$aexp = $user_card->exp - $before_uc->exp;
		$coin = $user->coin;



		if($has_skill_piece == 0){
			$slup = -1;
		}

		//新手嘉年华：宠物强化
		UserCarnivalInfo::carnivalMissionCheck($user->id,CarnivalPrize::CONDITION_TYPE_CARD_COMPOSITE,$user_count->card_composite);

		return array($succeed, $slup, $aexp, $coin, $composite_price, $result_pieces);
	}


// #PADC# ----------end----------

	private function getCompositePassiveSkillUp(Card $base_card, UserCard $user_card){
		$up_psk = 0;
		if($base_card->ps0 != 0){
			// 同じカードID、または覚醒モンスター(カードID:797と1702)の場合パッシブスキル+1.
			$kakusei_card_ids = array(self::KAKUSEI_CARD_ID, self::KAKUSEI_CARD_ID1000);
			if($user_card->card_id == $this->card_id || in_array($this->card_id, $kakusei_card_ids)){
				$up_psk++;
			}
			// たまドラベビー(カードID:1002)の場合40%の確率でパッシブスキル+1.
			// https://61.215.220.70/redmine-pad/issues/3252
			if($this->card_id == self::TAMADORA_BABY_CARD_ID){
				$seed = mt_rand(1, 10000);
				if($seed <= self::TAMADORA_PSK_PROB) {
					$up_psk++;
				}
			}
			// サーティワン・たまドラ(カードID:1475)の場合10%の確率でパッシブスキル+1.
			// https://61.215.220.70/redmine-pad/issues/4106
			if($this->card_id == self::TAMADORA_31_CARD_ID){
				$seed = mt_rand(1, 10000);
				if($seed <= self::TAMADORA_31_PSK_PROB) {
					$up_psk++;
				}
			}
			// さらに同じカードIDの場合パッシブスキルを合計.
			if($user_card->card_id == $this->card_id){
				$up_psk += $this->equip4;
			}
		}
		return $up_psk;
	}

	// ベースカードがリーダー（サブリーダー）であれば、レベルアップについても更新.
	public function setLeaderCard(User $user){
		// cuid,id,lv,slv,hp,atk,rec,pskのカンマ区切りの文字列*2個(リーダー、サブリーダー分).
		$lc = $user->getLeaderCardsData();
		$lc_data = array();
		for ($i = 0; $i <= 1; $i++) {
			if($lc->cuid[$i] == $this->cuid) {
				$lc_data[] = $this->cuid;
				$lc_data[] = $this->card_id;
				$lc_data[] = $this->lv;
				$lc_data[] = $this->slv;
				$lc_data[] = $this->equip1;
				$lc_data[] = $this->equip2;
				$lc_data[] = $this->equip3;
				$lc_data[] = $this->equip4;
			}else{
				$lc_data[] = $lc->cuid[$i];
				$lc_data[] = $lc->id[$i];
				$lc_data[] = $lc->lv[$i];
				$lc_data[] = $lc->slv[$i];
				$lc_data[] = $lc->hp[$i];
				$lc_data[] = $lc->atk[$i];
				$lc_data[] = $lc->rec[$i];
				$lc_data[] = $lc->psk[$i];
			}
		}
		return $lc_data;
	}
	/**
	 * #PADC#
	 * ベースカードがリーダーデッキ内にあれば更新する
	 */
	public function setLeaderDeckCard(User $user){
		$ld = $user->getLeaderDecksData();
		foreach ($ld as $card_num => $card){
			if($card[0] == $this->cuid) {
				$ld[$card_num][0] = (int)$this->cuid;
				$ld[$card_num][1] = (int)$this->card_id;
				$ld[$card_num][2] = (int)$this->lv;
				$ld[$card_num][3] = (int)$this->slv;
				$ld[$card_num][4] = (int)$this->equip1;
				$ld[$card_num][5] = (int)$this->equip2;
				$ld[$card_num][6] = (int)$this->equip3;
				$ld[$card_num][7] = (int)$this->equip4;
				break;
			}
		}
		return $ld;
	}

	// ログデータセット.
	private static function setLogData(User $before_user, User $user, UserCard $before_uc, UserCard $user_card, $add_cards){
		$log_data = array();
		$log_data["base_card"] = array();
		$log_data["base_card"]["card_id"] = (int)$before_uc->card_id;
		$log_data["base_card"]["cuid"] = (int)$before_uc->cuid;
		$log_data["base_card"]["lv"] = (int)$before_uc->lv;
		$log_data["base_card"]["slv"] = (int)$before_uc->slv;
		$log_data["base_card"]["plus"] = array(
			(int)$before_uc->equip1,
			(int)$before_uc->equip2,
			(int)$before_uc->equip3,
			(int)$before_uc->equip4
		);
		$log_data["before_coin"] = (int)$before_user->coin;
		$log_data["add_cards"] = $add_cards;
		$log_data["after_coin"] = (int)$user->coin;
		$log_data["after_card"] = array();
		if((int)$before_uc->card_id !== (int)$user_card->card_id){
			// 進化.
			$log_data["after_card"]["card_id"] = (int)$user_card->card_id;
		}
		$log_data["after_card"]["lv"] = (int)$user_card->lv;
		$log_data["after_card"]["slv"] = (int)$user_card->slv;
		$log_data["after_card"]["plus"] = array(
			(int)$user_card->equip1,
			(int)$user_card->equip2,
			(int)$user_card->equip3,
			(int)$user_card->equip4
		);
		return $log_data;
	}

	/**
	 * ユーザーカードのマスターカードデータを返す(キャッシュの負担を減らすため).
	 */
	public function getMaster() {
		if(!isset($this->master_card)){
			$this->master_card = Card::get($this->card_id);
		}
		return $this->master_card;
	}

	/**
	 * 現在の経験値に見合うレベルをセットする.
	 */
	public function setLevelOnExp() {
		$card = $this->getMaster();
		$lv = $this->lv;
		while($lv < $card->mlv && $card->getExpOnLevel($lv+1) <= $this->exp) {
			$lv++;
		}
		$this->lv = $lv;
	}

	/**
	 * このカードをベースモンスターとして合成するときの費用を算出して返す.
	 * 引数として、餌として与えるカードの枚数を渡す.
	 */
	public function getCompositePrice($added_count) {
		// 【ベースモンスターのレベル】*【固定系数（係数のE5）】*【合成費用単価（モンスターのM列）】*【餌モンスターの数】
		$card = $this->getMaster();
		return round($this->lv * GameConstant::getParam("CompositeCostLevel") * $card->ccost * $added_count);
	}

	/**
	 * このカードを進化させるときの費用を算出して返す.
	 * 進化に必要なモンスターの枚数を引数として渡す.
	 * (本来渡さなくても計算可能だが、前後処理での重複計算を避けるため渡す.)
	 */
	public function getEvolvePrice($required_count) {
		// 【ベースモンスターのレベル】*【固定系数（係数のE5）】*【合成費用単価（モンスターのM列）】*【進化に必要なモンスターの枚数】
		$card = $this->getMaster();
		return round($this->lv * GameConstant::getParam("CompositeCostLevel") * $card->ccost * $required_count);
	}


// #PADC# ----------begin----------
	/**
	 * このカードをベースモンスターとして合成するときの費用を算出して返す.
	 */
	public function getPieceCompositePrice($added_exp) {

		$card = $this->getMaster();
		$rare_cost = pow($card->rare/Card::MAX_RARE,GameConstant::getParam('PieceCompositeRareCostPowIndex'))*GameConstant::getParam('PieceCompositeRareCost')+GameConstant::getParam('PieceCompositeRareBaseCost');
		$mlv_cost = pow($card->mlv/Card::MAX_MLV,GameConstant::getParam('PieceCompositeMlvCostPowIndex'))*GameConstant::getParam('PieceCompositeMlvCost')+GameConstant::getParam('PieceCompositeMlvBaseCost');
		return round(GameConstant::getParam("PieceCompositeCostExp") * $card->ccost * $added_exp + $rare_cost + $mlv_cost,-2);
	}

	/**
	 * このカードを進化させるときの費用を算出して返す.
	 */
	public function getPieceEvolvePrice($required_count) {
		// カード合成コスト * 進化経験値
		$card = $this->getMaster();
		$rare_cost = pow($card->rare/Card::MAX_RARE,GameConstant::getParam('PieceEvolutionRareCostPowIndex'))*GameConstant::getParam('PieceEvolutionRareCost')+GameConstant::getParam('PieceEvolutionRareBaseCost');
		$mlv_cost = pow($card->mlv/Card::MAX_MLV,GameConstant::getParam('PieceEvolutionMlvCostPowIndex'))*GameConstant::getParam('PieceEvolutionMlvCost')+GameConstant::getParam('PieceEvolutionMlvBaseCost');
		return round(GameConstant::getParam('PieceEvolutionCostExp') * $card->ccost * $required_count + $mlv_cost + $rare_cost,-2);
	}
// #PADC# ----------end----------

	/**
	 * 合成ボーナス発生判定.
	 */
	public static function getCompositeBonus() {
		$excellent_prob = GameConstant::getParam("CompositeExcellentBonusProb");
		$good_prob = GameConstant::getParam("CompositeGoodBonusProb");
		// 期間限定ボーナスがあれば適用.
		$bonus = LimitedBonus::getCompositeGoodExcellentUp();
		if($bonus) {
			$excellent_prob = ($excellent_prob * $bonus->args / 10000.0);
			$good_prob = ($good_prob * $bonus->args / 10000.0);
		}
		$seed = mt_rand(1, 10000);
		if($seed <= $excellent_prob) {
			return UserCard::COMPOSITE_BONUS_EXCELLENT;
		} else if($seed <= $excellent_prob + $good_prob) {
			return UserCard::COMPOSITE_BONUS_GOOD;
		}
		return UserCard::COMPOSITE_BONUS_NONE;
	}

	/**
	 * 入力した経験値に期間限定ボーナスがあれば適用し、適用後の値を返す.
	 */
	public static function applyLimitedCompositeBonus($exp) {
		$bonus = LimitedBonus::getActiveComposition();
		if($bonus) {
			return round($exp * $bonus->args / 10000.0);
		}
		return $exp;
	}

	/**
	 * このカードを餌モンスターとして合成するときの獲得経験値を返す.
	 * 引数として、ベースモンスターのCardオブジェクトと、成功ボーナス発生有無を渡す.
	 */
	public function getCompositeExp(Card $base_card, $composite_bonus) {
		// 【餌モンスターのレベル】*【固定系数(係数のC5)】*【合成単価(モンスターのL列)】*【同属性ボーナス（係数のM列）】*【成功ボーナス(係数のO列)】
		$card = $this->getMaster();
		$exp = $this->lv * GameConstant::getParam("CompositeExpLevel") * $card->mcost;
		if($base_card->attr == $card->attr) {
			// 同属性ボーナス.
			$exp = $exp * GameConstant::getParam("CompositeSameAttrBonus");
		}
		if($composite_bonus == UserCard::COMPOSITE_BONUS_GOOD) {
			// 成功ボーナス.
			$exp = $exp * GameConstant::getParam("CompositeGoodBonus");
		} else if($composite_bonus == UserCard::COMPOSITE_BONUS_EXCELLENT) {
			// 大成功ボーナス.
			$exp = $exp * GameConstant::getParam("CompositeExcellentBonus");
		}
		return round($exp);
	}

	/**
	 * このカードを指定のベースカードに合成した時のスキルレベルアップを判定し、増加したスキルレベル量を返す.
	 * (一枚に対する増分は1のみなので、0 or 1 が返る.)
	 */
	private function getCompositeSkillUp(Card $base_card, UserCard $user_card) {
		if($base_card->id == $this->card_id && $base_card->spup > 0 && $user_card->lv == 99 && $this->lv == 99){
			// 究極進化したカード(spupが0以外)のLV99同士のカードを合成した場合は必ずスキルレベルアップ.
			return 1;
		}
		$card = $this->getMaster();
		// ALWAYS_SKILL_UP_CARDは確定スキルレベルアップ素材.
		if(in_array($card->id, explode(',', self::ALWAYS_SKILL_UP_CARD))){
			return 1;
		}elseif($base_card->skill != 0 && $base_card->skill == $card->skill) {
			$prob = GameConstant::getParam("CompositeSkillUpProb");
			// 期間限定ボーナスがあれば適用.
			$bonus = LimitedBonus::getActiveSkillLvUp();
			if($bonus) {
				$prob = ($prob * $bonus->args / 10000.0);
			}
			$seed = mt_rand(1, 10000);
			if($seed <= $prob) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * 指定ユーザの保有カードをCUID指定で取得する.
	 */
	public static function findByCuids($user_id, Array $cuids, $pdo = null) {
		$results = array();
		if(is_array($cuids) && count($cuids) > 0) {
			if($pdo == null) {
				$pdo = Env::getDbConnectionForUserRead($user_id);
			}
			$qs = array();
			for($i = 1; $i <= count($cuids); $i++) {
				$qs[] = "?";
			}
			$sql = "SELECT * FROM user_cards WHERE user_id = ? AND cuid IN (" . implode(',', $qs) . ")";
			$bind_param = array($user_id);
			$bind_param = array_merge($bind_param, $cuids);
			list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
			$results = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
		}
		return $results;
	}
	// #PADC# ----------begin----------
	public static function findByCardIds($user_id, Array $card_ids, $pdo = null)
	{
		$results = array();
		if(is_array($card_ids) && count($card_ids) > 0) {
			if($pdo == null) {
				$pdo = Env::getDbConnectionForUserRead($user_id);
			}
			$qs = array();
			for($i = 1; $i <= count($card_ids); $i++) {
				$qs[] = "?";
			}
			$sql = "SELECT * FROM user_cards WHERE user_id = ? AND card_id IN (" . implode(',', $qs) . ")";
			$bind_param = array($user_id);
			$bind_param = array_merge($bind_param, $card_ids);
			list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
			$results = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
		}
		return $results;
	}
	// #PADC# ----------end----------
	// データスナップショット作成
	public static function getsnapshots($user_id, $log_date) {
		$pdo = Env::getDbConnectionForUserRead($user_id);
		$sql = "SELECT * FROM ". self::TABLE_NAME ." WHERE user_id = ?";
		$bind_param = array($user_id);
		list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
		$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$snapshot_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_user_cards_snapshot.log");
		$snapshot_format = '%message%'.PHP_EOL;
		$snapshot_formatter = new Zend_Log_Formatter_Simple($snapshot_format);
		$snapshot_writer->setFormatter($snapshot_formatter);
		$snapshot_logger = new Zend_Log($snapshot_writer);
		foreach($values as $value) {
				$value=preg_replace('/"/', '""',$value);
				$snapshot_logger->log($log_date.",".implode(",",$value), Zend_Log::DEBUG);
		}
	}

	/**
	 * #PADC# カードレベルをクリアする。　IDIP安全用
	 *
	 * @param number $user_id
	 * @param PDO $pdo
	 * @return boolean
	 */
	public static function clearCardLv($user_id, $pdo = null){
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
		}

		$columns = array('exp', 'lv', 'slv');
		$values = array(0, 1, 1);

		$sql = 'UPDATE ' . static::TABLE_NAME . ' SET ';
		$setStmts = array();
		foreach($columns as $column) {
			$setStmts[] = $column . '=?';
		}
		$sql .= join(',', $setStmts);
		if(static::HAS_UPDATED_AT === TRUE) $sql .= ',updated_at=now()';
		$sql .= ' WHERE user_id = ?';
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute(array_merge($values, array($user_id)));

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",",array_merge($values, array($user_id)))), Zend_Log::DEBUG);
		}

		return $result;

	}

	// 碎片强化卡牌价格计算公式新
	public function getPieceCompositePrice2($add_piece_datas) {

		$card = $this->getMaster();
		$price = 0;
		foreach($add_piece_datas as $piece_id => $num){
			$master_piece = Piece::get($piece_id);
			$price = $price + $card->rare * $master_piece->tcost * $num['num'];
		}
		return $price;
	}

    public static function getUltimatePieces($card) {
        $i = 1;
        $ult_piece_id_key = 'ult_piece_id' . $i;
        $ult_piece_num_key = 'ult_piece_num' . $i;
        $add_piece_datas = array();
        for ($j = 0; $j < 5; $j++) {
            if ((int)$card->$ult_piece_num_key > 0) {
                $add_piece_datas[$card->$ult_piece_id_key] = array('id' => $card->$ult_piece_id_key, 'num' => $card->$ult_piece_num_key);
            }
            $i = $i + 1;
            $ult_piece_id_key = 'ult_piece_id' . $i;
            $ult_piece_num_key = 'ult_piece_num' . $i;
        }
        return $add_piece_datas;
    }

	public static function awakeCardSkill($user_id, $cuid, $pskill_index) {
		global $logger;
		$succeed = FALSE;
		$coin = 0;
		$ret_piece = array();
		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			$user = User::find($user_id, $pdo, TRUE);
			if (!$user) {
				$logger->log('User Card not exist!', Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}
			$user_card = UserCard::findBy(array("user_id" => $user_id, "cuid" => $cuid), $pdo, TRUE);

			if (!$user_card) {
				$logger->log('User Card not exist!', Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}

			$card = Card::get($user_card->card_id);
			// 根据传入的index获得需要觉醒的技能id
			$ps_column = 'ps' . $pskill_index;
			// 获得觉醒技能的id
			$ps_id = $card->$ps_column;

			if (!$ps_id) {
				$logger->log('passive skill not exist!', Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}

			$awake_cost = PassiveSkill::getBy(array('pskill_id' => $ps_id), $pdo);

			if ($user->coin < $awake_cost->cost) {
				$logger->log('not enough money!', Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}

			$awake_skill_piece = UserPiece::findBy(array('piece_id' => $awake_cost->awake_piece_id, 'user_id' => $user_id), $pdo, TRUE);

			if (!$awake_skill_piece || $awake_skill_piece->num < $awake_cost->num) {
				$logger->log('not enough piece!', Zend_Log::DEBUG);
				$pdo->rollback();
				return array($succeed, $coin, $ret_piece);
			}

			if (empty($user_card->ps)) {
				$ps_status = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			} else {
				$ps_status = json_decode($user_card->ps, TRUE);
			}
			$ps_status[(int)$pskill_index] = 1;
			$user_card->ps = json_encode($ps_status);
			$user_card->equip4 += 1;
			$user_card->update($pdo);

			// 扣除对应金币和碎片
			$user->addCoin(-1 * (int)$awake_cost->cost);
			$coin = $user->coin;

			// 累计消费金币活动如果开启
			$activity = Activity::getByType(Activity::ACTIVITY_TYPE_COIN_CONSUM);
			if($activity){
				$uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
				$uac->addCounts(Activity::ACTIVITY_TYPE_COIN_CONSUM, (int)$awake_cost->cost);
				$uac->update($pdo);
			}

			// 技能觉醒活动如果开启
			$activity = Activity::getByType(Activity::ACTIVITY_TYPE_SKILL_AWAKE_COUNT);
			if($activity){
				$uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
				$uac->addCounts(Activity::ACTIVITY_TYPE_SKILL_AWAKE_COUNT);
				$uac->update($pdo);
			}

			$user->update($pdo);

			$awake_skill_piece->subtractPiece((int)$awake_cost->num);
			$ret_piece[] = UserPiece::arrangeColumn($awake_skill_piece);
			$awake_skill_piece->update($pdo);

			// 更新lc信息
			$lc_data = $user_card->setLeaderCard($user);
			$ps_status = array_merge($ps_status, $ps_status);
			if((int)$lc_data[0] == $user_card->cuid){
				$user->lc_ps = json_encode($ps_status);
				$user->update($pdo);
			}

			$pdo->commit();

			// TODO : tlog need add
			$sequence = Tencent_Tlog::getSequence($user->id);
			UserTlog::sendTlogMoneyFlow($user, -1 * (int)$awake_cost->cost, Tencent_Tlog::REASON_AWAKE_SKILL, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, $sequence);
			UserTlog::sendTlogItemFlow($user->id, Tencent_Tlog::GOOD_TYPE_PIECE, $awake_cost->awake_piece_id, -1 * (int)$awake_cost->num, $awake_skill_piece->num, Tencent_Tlog::ITEM_REASON_AWAKE_SKILL, 0, (int)$awake_cost->cost, Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
			UserTlog::sendTlogAwakeSkill($user,$awake_cost->awake_piece_id,$user_card->card_id,$ps_id,$awake_cost->num,$coin,$user_card->lv);

			$succeed = TRUE;

		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}

		return array($succeed, $coin, $ret_piece);
	}

	public static function getAwakeSkillAdditions($user_card_ps_info, $card) {
		$hp_add = 0;
		$atk_add = 0;
		$rec_add = 0;

		for ($i = 0; $i < 10; $i++) {
			$column_name = 'ps' . $i;
			if($card->$column_name == 0){
				continue;
			}
			if ($card->$column_name == PassiveSkill::PASSIVE_SKILL_HP_ID && $user_card_ps_info[$i] == 1) {
				$hp_add = PassiveSkill::PASSIVE_SKILL_HP_AMOUNT;
			} elseif ($card->$column_name == PassiveSkill::PASSIVE_SKILL_ATK_ID && $user_card_ps_info[$i] == 1) {
				$atk_add = PassiveSkill::PASSIVE_SKILL_ATK_AMOUNT;
			} elseif ($card->$column_name == PassiveSkill::PASSIVE_SKILL_REC_ID && $user_card_ps_info[$i] == 1) {
				$rec_add = PassiveSkill::PASSIVE_SKILL_REC_AMOUNT;
			}
		}
		return array($hp_add, $atk_add, $rec_add);
	}

	/**
	 * 找到帮助伙伴的卡牌的cuid
	 * @param $help_id
	 * @param $help_card_id
	 * @return null
	 */
	public static function getHelperUserCard($help_id,$help_card_id){
		$user = User::find($help_id);
		if(empty($user)){
			return null;
		}

		$ldeck = json_decode($user->ldeck,true);
		foreach($ldeck as $dk){
			if($help_card_id == $dk[1]){
				return $dk[0];
			}
		}
		return null;
	}

}
