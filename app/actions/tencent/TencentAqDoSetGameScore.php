<?php
/**
 * Tencent Aq用：
*/
class TencentAqDoSetGameScore extends TencentBaseAction {
	public function action($params) {
		$openId = isset($params['OpenId']) ? $params['OpenId'] : null;
		$userId = isset($params['RoleId']) ? $params['RoleId'] : null;
		$Type = isset($params ['Type']) ? $params ['Type'] : null;
		$Value = isset($params ['Value']) ? $params ['Value'] : null;
		$platId = isset($params ['PlatId']) ? $params['PlatId'] : null;
		$source = isset($params ['Source']) ? $params['Source'] : null;
		$serial = isset($params ['Serial']) ? $params['Serial'] : null;
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
		
		//$type decide next to do
		switch ( $Type ){
			case 1:
				UserRanking::setScore($userId, $Value,$pdo);
				break;
			case 99:
				UserRanking::setScore($userId, $Value,$pdo);
				break;
			default:
				throw new PadException(static::ERR_INVALID_REQ,'invalid type');
				break;
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