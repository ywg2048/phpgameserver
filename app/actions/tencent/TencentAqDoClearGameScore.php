<?php
/**
 * Tencent Aq用：
*/
class TencentAqDoClearGameScore extends TencentBaseAction {
	public function action($params) {
		$openId = isset($params['OpenId']) ? $params['OpenId'] : null;
		$userId = $params['RoleId'];
		$Type = isset($params ['Type']) ? $params ['Type'] : null;
		$IsZero = isset($params ['IsZero']) ? $params ['IsZero'] : null;
		$platId = isset($params ['PlatId']) ? $params['PlatId'] : null;
		$cmd = $params ['Cmdid'];

		if(!$userId){
			$userId = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		}
		if(!$userId){
			throw new PadException(static::ERR_INVALID_REQ,'requst param is incorrect');
		}
		//find user instance,else throw a PADCexception
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		$user = User::find ( $userId, $pdo, true );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		
		//only iszero is 1,score will be cleared
		if($IsZero){
			switch ( $Type ){
				case 1:
					UserRanking::setScore($userId, 0,$pdo);
					break;
				case 99:
					UserRanking::setScore($userId, 0,$pdo);
					break;
				default:
					throw new PadException(static::ERR_INVALID_REQ,'invalid type');
					break;
			}
		}

		$result = array (
				'res' => RespCode::SUCCESS,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success",
		);

		return json_encode ( $result );
	}
}