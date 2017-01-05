<?php
/**
 * Admin用：重複所持条件を満たしたモンスターを生成
 */
class AdminAddAllAdditionalCard extends AdminBaseAction {
	/**
	 *
	 * @see AdminBaseAction::action()
	 */
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id ); // 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		
		if ($user_id === FALSE) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		
		//$pdoShare = Env::getDbConnectionForShareRead ();
		$pdoUser = Env::getDbConnectionForUserWrite ( $user_id );
		
		$userCards = UserCard::findAllBy ( array (
				'user_id' => $user_id 
		), null, null, $pdoUser );
		
		foreach ( $userCards as $userCard ) {
			$baseCard = $userCard->getMaster ();
			if ($userCard->lv == $baseCard->mlv && $baseCard->gup_final == 1) {
				try {
					//global $logger;
					$pdoUser->beginTransaction ();
					$piece_id = $baseCard->gup_piece_id;
					//$logger->log ( "card_id:" . $userCard->card_id, 7 );
					//$logger->log ( "piece_id:" . $piece_id, 7 );
					$user_piece = UserPiece::getUserPiece ( $user_id, $piece_id, $pdoUser, TRUE );
					$basePiece = $user_piece->getMaster ();
					$user_piece->addPiece ( $basePiece->gcnt, $pdoUser );
					//$logger->log ( "create card,piece:" . $user_piece->piece_id, 7 );
					$createdUserCard = $user_piece->createCard ( $pdoUser, null, $user_piece->checkAdditionalMonster ( $pdoUser ) );
					$user_piece->update ( $pdoUser );
					//$logger->log ( "create card:" . $createdUserCard->card_id, 7 );
					$pdoUser->commit ();
				} catch ( Exception $e ) {
					if ($pdoUser->inTransaction ()) {
						$pdoUser->rollback ();
					}
					throw $e;
				}
			}
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
}
