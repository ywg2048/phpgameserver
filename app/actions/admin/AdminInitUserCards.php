<?php
/**
 * Tencent用：ユーザモンスター初期化
*/
class AdminInitUserCards extends AdminBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}

		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		$user = User::find ( $user_id, $pdo, true );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}

		// cuidのリセット
		$dbparams = array(
			'user_id' => $user_id,
		);
		$userCardSeq = UserCardSeq::findBy($dbparams,$pdo,true);
		$sql = 'UPDATE ' . UserCardSeq::TABLE_NAME . ' set max_cuid=0 WHERE user_id=?';
		$stmt = $pdo->prepare($sql);
		$binds = array(
			$user_id,
		);
		$result	= $stmt->execute($binds);

		$this->initUser ( $user, $pdo );

		$this->initUserCard ( $user, $pdo );

		$this->initUserPiece ( $user, $pdo );

		$this->initUserDeck ( $user, $pdo );

		$result = array (
				'res' => RespCode::SUCCESS
		);
		return json_encode ( $result );
	}
	private function initUser($user, $pdo) {
		// リーダーカード、サブリーダーカードに同じものを設定
		$init_cards = $user->getInitCards ();
		$lc = array (
				1,
				$init_cards [$user->camp] [0], // lc_id
				1, // lc_lv
				1, // lc_slv
				0, // lc_hp
				0, // lc_atk
				0, // lc_rec
				0
		); // lc_psk
		$lc = array_merge ( $lc, $lc );
		$user->lc = join ( ",", $lc );

		// PADC版はリーダーだけでなくデッキ内のカード全てを情報として持つ
		$ldeck = array ();
		$card_num = 1;
		foreach ( $init_cards [$user->camp] as $card_id ) {
			$ldeck [] = array (
					$card_num,
					$card_id, // lc_id
					1, // lc_lv
					1, // lc_slv
					0, // lc_hp
					0, // lc_atk
					0, // lc_rec
					0
			);
			$card_num ++;
			// デッキにセットできる数を超えたらループを抜ける
			if($card_num > User::DECK_CARD_MAX)
			{
				break;
			}
		}
		$user->ldeck = json_encode ( $ldeck );
		$user->book_cnt = count($init_cards[$user->camp]) + 1;

		$user->update ( $pdo );
	}
	private function initUserCard($user, $pdo) {
		// 所持カード削除
		$sql = 'DELETE FROM ' . UserCard::TABLE_NAME;
		$sql .= ' WHERE user_id = ?';
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				$user->id
		);
		global $logger;
		$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		$result = $stmt->execute ( $values );

		// 図鑑登録データ削除
		$sql = 'DELETE FROM ' . UserBook::TABLE_NAME;
		$sql .= ' WHERE user_id = ?';
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				$user->id
		);
		global $logger;
		$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		$result = $stmt->execute ( $values );

		// 図鑑にのみ登録しておくモンスターを追加
		$user_book = UserBook::getByUserId($user->id, $pdo);
		$user_book->addCardId(User::INIT_USER_BOOK_CARD_ID);
		$user_book->update($pdo);
		
		// 初期カード追加.
		$init_cards = $user->getInitCards ();
		$add_cards = array ();
		foreach ( $init_cards [$user->camp] as $k => $card_id ) {
			// #PADC# リーダーの初期レベル変更
			$card_level = UserCard::DEFAULT_LEVEL;
			if($k == 0)// 1体目（リーダー）のLVを変更
			{
				$card_level = User::INIT_LEADER_CARD_LEVEL;
			}
			$add_cards [] = UserCard::addCardsToUserReserve ( $user->id, $card_id, $card_level, UserCard::DEFAULT_SKILL_LEVEL, $pdo, 0, 0, 0, 0 );
		}
		UserCard::addCardsToUserFix ( $add_cards, $pdo );
	}
	private function initUserPiece($user, $pdo) {
		$sql = 'DELETE FROM ' . UserPiece::TABLE_NAME;
		$sql .= ' WHERE user_id = ?';
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				$user->id
		);
		global $logger;
		$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		$result = $stmt->execute ( $values );

		$init_piece_ids = $user->getInitPieceIds ();
		$add_pieces = array ();
		foreach ( $init_piece_ids [$user->camp] as $piece_id ) {
			$add_result = UserPiece::addUserPieceToUserReserve( $user->id, $piece_id, 0, $pdo );
			$user_piece = $add_result['piece'];
			$user_piece->create_card = 1;
			$add_pieces [] = $user_piece;
		}
		UserPiece::addUserPiecesWithCardsToUserFix ( $add_pieces, array (), $pdo );
	}
	private function initUserDeck($user, $pdo) {
		$user_deck = UserDeck::findby ( array (
				'user_id' => $user->id
		), $pdo, true );

		// デッキセット保存.
		$decks = array ();
		for($i = 0; $i < User::INIT_DECKS_MAX; $i ++) {
			// チーム2のみ5体モンスターをセット
			if ($i == 1) {
				$decks[] = array(sprintf("set_%02s", $i) => array(1, 2, 3, 4, 5));
			}
			else {
				$decks[] = array(sprintf("set_%02s", $i) => array(1, 0, 0, 0, 0));	// 初期デッキにリーダーをセット
			}
		}
		$user_deck->decks = json_encode ( $decks );
		$user_deck->deck_num = 0;
		$user_deck->update ( $pdo );
	}
}
