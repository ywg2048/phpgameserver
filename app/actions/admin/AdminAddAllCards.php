<?php
/**
 * Admin用：すべてのカードの追加
 */
class AdminAddAllCards extends AdminBaseAction {
	/**
	 *
	 * @see AdminBaseAction::action()
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
		
		if ($user_id === FALSE) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		try {
			$pdo->beginTransaction ();
			
			$cards = Card::getAll ();
			foreach ( $cards as $card ) {
				if ($card->id == 0) {
					continue;
				}
				$user_card = UserCard::findBy ( array (
						'user_id' => $user_id,
						'card_id' => $card->id 
				) );
				if (! $user_card) {
					UserCard::addCardToUser ( $user_id, $card->id, UserCard::DEFAULT_LEVEL, UserCard::DEFAULT_SKILL_LEVEL, $pdo );
				}
			}
			
			$pieces = Piece::getAll ();
			foreach ( $pieces as $piece ) {
				if ($piece->cid > 0) {
					$user_piece = UserPiece::getUserPiece ( $user_id, $piece->id, $pdo );
					$user_piece->create_card = 1;
					$user_piece->update ( $pdo );
				}
			}
			
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
}
