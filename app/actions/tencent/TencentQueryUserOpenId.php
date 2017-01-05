<?php
/**
 * Tencent用: 使用用户表示ID查询OpenID
 */
class TencentQueryUserOpenID extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$display_id = $params ['PlayerId'];
		//$type = $params ['PlatId'];

		$user_id = UserDevice::convertDispIdToPlayerId($display_id);
		$user = User::find($user_id);
		if (empty($user)) {
			throw new PadException(RespCode::USER_NOT_FOUND, 'User not found!');
		}

		$open_id = UserDevice::getUserOpenId($user_id);
		$result = array (
			'res' => 0,
			'msg' => 'OK',
			'Result' => 0,
			'RetMsg' => 'success',
			'OpenId' => $open_id,
		);
		return json_encode($result);
	}
}
