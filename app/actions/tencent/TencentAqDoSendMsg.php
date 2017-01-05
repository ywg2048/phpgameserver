<?php
/**
 * TencentAq用
 */
class TencentAqDoSendMsg extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$ptype = static::convertArea($params ['AreaId']);
		$platId = isset($params ['PlatId']) ? $params ['PlatId'] : null;
		$openId = isset($params ['OpenId']) ? $params ['OpenId'] : null;
		$userId = isset($params['RoleId']) ? $params['RoleId'] : null;
		$msgContent = isset($params['MsgContent']) ? $params['MsgContent'] : null;
		$source = isset($params ['Source']) ? $params ['Source'] : null;
		$serial = isset($params ['Serial']) ? $params ['Serial'] : null;
		$cmd = $params ['Cmdid'];

		if($ptype == 0){
			throw new PadException(static::ERR_INVALID_REQ, 'Unknown area!' );
		}
		if(empty($openId) && empty($userId)) throw new PadException(static::ERR_INVALID_REQ,'request param is incorrect. Missed OpenId or RoleId!');
		if(empty($userId)){
			if(is_null($platId)) {
				throw new PadException(static::ERR_INVALID_REQ,'request param is incorrect. missed PlatId!');
			}
			$userId = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		}
		//find user
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		$user = User::find ( $userId, $pdo, false );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		UserMail::sendAdminMail($userId, $msgContent, UserMail::TYPE_ADMIN_MESSAGE);
		
		return json_encode ( array (
				'res' => 0,
				'msg' => 'success',
				'Result' => 0,
				'RetMsg' => 'success' 
		) );
	}
}
