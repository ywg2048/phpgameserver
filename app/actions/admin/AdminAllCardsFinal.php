<?php
/**
 * Admin用：すべてのカードを最終形態に
 */
class AdminAllCardsFinal extends AdminBaseAction {
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
		
		$pdoShare = Env::getDbConnectionForShareRead ();
		$pdoUser = Env::getDbConnectionForUserWrite ( $user_id );
		
		$cards = Card::getAll ();
		
		$userCards = UserCard::findAllBy ( array (
				'user_id' => $user_id 
		), null, null, $pdoUser, true );
		if ($userCards == false) {
			return json_encode ( array (
					'res' => RespCode::SUCCESS 
			) );
		}
		foreach ( $userCards as $userCard ) {
			$finalCardId = self::findFinalCardId ( $userCard->card_id, $cards );
			if ($finalCardId != null) {
				global $logger;
				$logger->log ( 'find final ' . $finalCardId . ' for ' . $userCard->card_id, 7 );
				$userCard->card_id = $finalCardId;
				$userCard->update($pdoUser);
			}
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
	
	/**
	 * 
	 * @param number $card_id
	 * @param array<Card> $cards
	 * @return number|null
	 */
	private static function findFinalCardId($card_id, &$cards) {
		foreach ( $cards as $card ) {
			for($i = 1; $i <= 5; $i ++) {
				//already final
				if($card->id == $card_id && $card->gup_final == 1){
					return null;
				}
				
				//find card can up to	
				$gupc_property = $card->getGupcPropertyName ( $i );
				if ($card->$gupc_property == $card_id) {
					// global $logger;
					// $logger->log ( 'find ' . $card->id . ' for ' . $card_id, 7 );
					$finalCardId = self::findFinalCardId ( $card->id, $cards );
					if ($finalCardId == null) {
						$finalCardId = $card->id;
					}
					// global $logger;
					// $logger->log ( 'find final ' . $finalCardId . ' for ' . $card_id, 7 );
					return $finalCardId;
				}
			}
		}
		return null;
	}
}
