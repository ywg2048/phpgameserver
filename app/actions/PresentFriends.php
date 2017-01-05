<?php
/**
 * #PADC# 
 */
class PresentFriends extends BaseAction {
	/**
	 *
	 * @see BaseAction::action()
	 */
	public function action($params) {
		$user_id = $params ['pid'];
		$rev = isset($params['r']) ? (int)$params['r'] : 1;
		
		$friends = UserPresentsSend::getUnpresentedFriend($user_id, $rev);
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS,
				'friends' => $friends 
		) );
	}
}
