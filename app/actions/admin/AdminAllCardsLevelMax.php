<?php
/**
 * Admin用：すべてのカードレベルMAX
 */
class AdminAllCardsLevelMax extends AdminBaseAction {
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
		
		$user_cards = UserCard::findAllBy ( array (
				'user_id' => $user_id 
		), null, null, $pdo, true );
		foreach ( $user_cards as $user_card ) {
			$base_card = Card::get ( $user_card->card_id );
			if ($base_card) {
				$user_card->lv = $base_card->mlv;
				$user_card->update ( $pdo );
			}
		}
		
		$result = array (
				'res' => RespCode::SUCCESS 
		);
		return json_encode ( $result );
	}
}
