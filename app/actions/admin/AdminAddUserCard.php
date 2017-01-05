<?php
/**
 * Tencent用：ユーザ所持モンスター追加
 */
class AdminAddUserCard extends AdminBaseAction {
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
		$card_id = $params ['card_id'];

		$pdo_share = Env::getDbConnectionForShareRead ();
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );

		// check card exist
		$user_card = UserCard::findBy ( array (
				'user_id' => $user_id,
				'card_id' => $card_id
		), $pdo );
		if ($user_card) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'Card already exist!' );
		}

		// find base card
		$base_user_card = $this->findBaseUserCard ( $user_id, $card_id, $pdo, $pdo_share );

		try {
			$pdo->beginTransaction ();
			if ($base_user_card) {
				// update base card
				$this->updateCard ( $user_id, $base_user_card, $card_id, $pdo );
			} else {
				// add new card
				$this->addCard ( $user_id, $card_id, $pdo, $pdo_share );
			}

			// 図鑑登録数の更新
			$user = User::find ( $user_id, $pdo );
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			$user->update ( $pdo );

			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollBack ();
			}
			throw $e;
		}

		$result = array (
				'res' => RespCode::SUCCESS
		);
		return json_encode ( $result );
	}

	/**
	 *
	 * @param number $user_id
	 * @param number $card_id
	 * @param PDO $pdo
	 * @return mixed
	 */
	private function findBaseUserCard($user_id, $card_id, $pdo, $pdo_share) {
		$card = Card::find ( $card_id, $pdo_share );
		$base_card_id = $card->gupc;
		if ($base_card_id == 0)
			return false;
		$base_user_card = UserCard::findBy ( array (
				'user_id' => $user_id,
				'card_id' => $base_card_id
		), $pdo );
		if ($base_user_card) {
			return $base_user_card;
		}

		return $this->findBaseUserCard ( $user_id, $base_card_id, $pdo, $pdo_share );
	}

	/**
	 *
	 * @param number $user_id
	 * @param UserCard $user_card
	 * @param number $card_id
	 * @param PDO $pdo
	 */
	private function updateCard($user_id, $user_card, $card_id, $pdo) {
		$user = User::find ( $user_id, $pdo );

		$user_card->card_id = $card_id;
		$user_card->lv = UserCard::DEFAULT_LEVEL;
		$user_card->exp = 0;
		$user_card->update ( $pdo );

		// ベースカードがリーダー（サブリーダー）であれば、進化合成についても更新.
		$lc_data = $user_card->setLeaderCard ( $user );
		$user->lc = join ( ",", $lc_data );
		// PADC版追加
		$ldeck = $user_card->setLeaderDeckCard ( $user );
		$user->ldeck = json_encode ( $ldeck );
		$user->update ( $pdo );
		
		// 図鑑データにカードIDを登録
		$user_book = UserBook::getByUserId($user_id, $pdo);
		$user_book->addCardId($card_id);
		$user_book->update($pdo);
	}

	/**
	 *
	 * @param number $user_id
	 * @param number $card_id
	 * @param PDO $pdo
	 * @param PDO $pdo_share
	 */
	private function addCard($user_id, $card_id, $pdo, $pdo_share) {
		UserCard::addCardToUser ( $user_id, $card_id, UserCard::DEFAULT_LEVEL, UserCard::DEFAULT_SKILL_LEVEL, $pdo );

		$base_card_id = $this->findBaseCardId ( $card_id, $pdo_share );

		$base_piece = Piece::findBy ( array (
				'cid' => $base_card_id
		), $pdo_share );

		$user_piece = UserPiece::getUserPiece ( $user_id, $base_piece->id, $pdo );
		if (! $user_piece->create_card) {
			$user_piece->create_card = 1;
			$user_piece->last_get_time = User::timeToStr(time());
			$user_piece->update ( $pdo );
		}
	}

	/**
	 *
	 * @param number $card_id
	 * @param PDO $pdo_share
	 * @return number
	 */
	private function findBaseCardId($card_id, $pdo_share) {
		$card = Card::find ( $card_id, $pdo_share );
		$base_card_id = $card->gupc;
		if ($base_card_id == 0)
			return $card_id;

		return $this->findBaseCardId ( $base_card_id, $pdo_share );
	}
}
