<?php
/*
 * tencent use
 */
class TencentDoUpdateVipExp extends TencentBaseAction
{
	public function action($get_params){
		$platId = $get_params['PlatId'];
		$openId = $get_params['OpenId'];
		$value = $get_params['Value'];
		
		$userId = UserDevice::getUserIdFromUserOpenId($platId, $openId);
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		//get user object
		$user = User::find($userId);
		if($user === false){
			throw new PadException(RespCode::USER_NOT_FOUND,'user not find');
		}
		$beginValue = $user->vip_lv;
		$user->vip_lv = $user->vip_lv + $value;
		$max_vip_lv = VipCost::getMaxVipLv();
		if($user->vip_lv < 0){
			$user->vip_lv = 0;
		}elseif ($user->vip_lv > $max_vip_lv){
			$user->vip_lv = $max_vip_lv;
		}
		$endValue = $user->vip_lv;
		if($beginValue != $endValue){
			$user->update($pdo);
		}
		
		$result = array(
				'res' => 0,
				'msg' => 'success',
				'Result' => 0,
				'RetMsg' => 'success',
				'BeginValue' => $beginValue,
				'EndValue' => $endValue
		);
		//return a json formatted data
		return json_encode($result);
		
	}
}