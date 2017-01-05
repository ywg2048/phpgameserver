<?php
/**
 * IDIP安全：クリアカードデータ
*/
class TencentAqDoClearCard extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		if(isset($params ['RoleId'])){
			$user_id = $params ['RoleId'];
			$user = User::find($user_id);
			if(empty($user)){
				throw new PadException(RespCode::USER_NOT_FOUND,'user not find');
			}
		}else if(isset($params ['OpenId']) && isset($params ['PlatId'])){
			$openid = $params ['OpenId'];
			$type = $params ['PlatId'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}else{
			throw new PadException(static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		if(isset($params ['IsClearLevel']) && $params ['IsClearLevel']){
			UserCard::clearCardLv($user_id);
		}

		return json_encode ( array (
				'res' => 0,
				'msg' => 'success',
				'Result' => 0,
				'RetMsg' => 'success'
		) );
	}
}
