<?php
/**
 * Tencent Aq用：
 */
class TencentAqDoUpdateMoney extends TencentBaseAction {
	public function action($params) {
		$area_id = isset($params ['AreaId'])?$params ['AreaId']:0;
		$ptype = static::convertArea($params ['AreaId']);//qq:1,wx:2,guest:0
		$openId = isset($params ['OpenId']) ? $params ['OpenId'] : null;
		$userId = isset($params['RoleId']) ? $params['RoleId'] : null;
		$value = isset($params ['Value']) ? $params ['Value'] : null;
		$platId = isset($params ['PlatId']) ? $params ['PlatId'] : null;
		$source = isset($params ['Source']) ? $params ['Source'] : null;
		$serial = isset($params ['Serial']) ? $params ['Serial'] : null;
		$cmd = $params ['Cmdid'];
		
		
		if($ptype == 0){
			throw new PadException(static::ERR_INVALID_REQ, 'Unknown area!' );
		}
		if(empty($openId) && empty($userId)) throw new PadException(static::ERR_INVALID_REQ,'request param is incorrect. missed Openid or Roleid.');
		if(empty($userId)){
			if(is_null($platId)) {
				throw new PadException(static::ERR_INVALID_REQ,'request param is incorrect. missed PlatId.');
			}
			$userId = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		}
		
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		$user = User::find ( $userId, $pdo, true );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		$beginValue = $user->coin;
		$user->coin += $value;
		if ($user->coin < 0) {
			$user->coin = 0;
		}
		$endValue = $user->coin;
		
		if($endValue != $beginValue){
			$user->update ( $pdo );
		}
		
		$coin_added = $endValue - $beginValue;
		Padc_Log_Log::sendIDIPFlow($area_id, $openId, 0, $coin_added, $serial, $source, $cmd, 0, $ptype);

		$addOrReduce	= ($endValue >= $beginValue)? Tencent_Tlog::ADD : Tencent_Tlog::REDUCE;
		Padc_Log_Log::sendMoneyFlow($user->device_type, $openId, $user->lv, abs($coin_added), Tencent_Tlog::REASON_IDIP, $addOrReduce, Tencent_Tlog::MONEY_TYPE_MONEY, $endValue, 0, 0, $ptype);
		
		User::kickOff($userId);
		
		$result = array (
				'res' => RespCode::SUCCESS,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success",
		);
		
		return json_encode ( $result );
	}
}
