<?php
/**
 * Tencent用：
 */
class TencentDoUpdatePhysical extends TencentBaseAction {
	public function action($params) {
		$platId = $params ['PlatId'];
		$openId = $params ['OpenId'];
		$value = $params ['Value'];
		
		$userId = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		$user = User::find ( $userId, $pdo, TRUE );
		if ($user === false) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found' );
		}
		$beginValue = $user->getStamina();
		$user->addStamina ( $value );
		$endValue = $user->stamina;
		
		if($endValue != $beginValue){
			$user->update ( $pdo );
		}
		
		$result = array (
				'res' => 0,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success",
				'BeginValue' => $beginValue,
				'EndValue' => $endValue 
		);
		return json_encode ( $result );
	}
}
