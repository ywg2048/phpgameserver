<?php
/**
 * Tencent用：
 */
class TencentDoUpdateMoney extends TencentBaseAction {
	public function action($params) {
		$area_id = isset($params ['AreaId'])?$params ['AreaId']:0;
		$ptype = static::convertArea($params ['AreaId']);
		$openId = $params ['OpenId'];
		$value = $params ['Value'];
		$platId = $params ['PlatId'];
		$source = $params ['Source'];
		$serial = $params ['Serial'];
		$cmd = $params ['Cmdid'];
		
		$userId = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		$user = User::find ( $userId, $pdo, true );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}

		if($ptype == 0){
			throw new PadException(static::ERR_INVALID_REQ, 'Unknown area!' );
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
		
		$result = array (
				'res' => RespCode::SUCCESS,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success",
				'BeginValue' => $beginValue,
				'EndValue' => $endValue 
		);
		
		return json_encode ( $result );
	}
}