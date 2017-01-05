<?php
/**
 * Tencent Aq用：
 * 
 * @author zhudesheng
 *
 */
class TencentAqDoUpdateStone extends TencentBaseAction {
	public function action($params) {
		$area_id = isset($params ['AreaId'])?$params ['AreaId']:0;
		$platId = isset($params['PlatId']) ? $params['PlatId'] : null;
		$openId = isset($params ['OpenId']) ? $params ['OpenId'] : null;
		$userId = isset($params['RoleId']) ? $params['RoleId'] : null;
		$value = isset($params ['Value']) ? $params ['Value'] : null;
		$IsLogin = $params['IsLogin'];
		$source = isset($params ['Source']) ? $params ['Source'] : null;
		$serial = isset($params ['Serial']) ? $params ['Serial'] : null;
		$cmd = $params ['Cmdid'];
		
		//get user_id,if no user_id,throw exception
		if(!$userId){
			$userId = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		}
		if(!$userId){
			throw new PadException(static::ERR_INVALID_REQ,'requst param is incorrect');
		}
		
		//find user instance
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		$user = User::find ( $userId, $pdo, true );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		
		// if isLogin is true,then user will be log out immediately,otherwise just update user 
		// reserve gold for next login use
		if($IsLogin){
			User::kickOff($userId);
		}else{
			User::offline($userId);
		}
		
		//reserve_gold will be used in login
		$user->reserve_gold += $value;
		$user->update($pdo);
		
		$user_device_data = UserDevice::getUserDeviceFromRedis($userId);
		Padc_Log_Log::sendIDIPFlow($area_id, $user_device_data['oid'], 0, $value, $serial, $source, $cmd, 0, $user_device_data['pt']);
		
		$result = array (
				'res' => RespCode::SUCCESS,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success",
		);
		
		return json_encode ( $result );
	}
}