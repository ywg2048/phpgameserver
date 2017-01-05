<?php
/**
 * Admin用：すべての欠片の追加
 */
class AdminAddAllPieces extends AdminBaseAction {
	/**
	 *
	 * @see AdminBaseAction::action()
	 */
	public function action($params)
	{
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$amount	= (int)$params['amount'];

		if ($user_id === FALSE) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}

		$pdo = Env::getDbConnectionForUserWrite ( $user_id );

		$pieces = Piece::getAll ();

		//--------------------------------------------------
		// Cardに登録されている欠片のみを付与対象とする
		//--------------------------------------------------

		$card = new Card();
		$cardNames = self::getNamesByDao($card);

		$tmpPieces = array();
		foreach($pieces as $piece)
		{
			if(isset($cardNames[$piece->cid]))
			{
				$tmpPieces[] = $piece;
			}
		}
		$pieces = $tmpPieces;

		//--------------------------------------------------

		try {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
			$pdo->beginTransaction ();

			// 入手対象欠片の追加準備
			$add_pieces = array();
			$add_cards = array();
			foreach ( $pieces as $piece ) {
				if ($piece->id == 0) {
					continue;
				}
				$add_result = UserPiece::addUserPieceToUserReserve(
						$user_id,
						$piece->id,
						$amount,
						$pdo
				);
				$add_pieces[] = $add_result['piece'];
				if(array_key_exists('card', $add_result)){
					$add_cards[] = $add_result['card'];
				}
			}

			// 欠片（及びモンスター）追加処理
			list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

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
			'res' => RespCode::SUCCESS,
		);
		return json_encode ( $result );
	}
}
