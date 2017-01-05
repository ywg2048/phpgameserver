<?php
/**
 * compare user vip lv and received record,return unreceived lv up bonuses
 * 
 * @author zhudesheng
 * 
 * @package PADC
 *
 */
class GetVipLvUpBonuses extends BaseAction {

	// http://pad.localhost/api.php?action=getviplvupbonues&pid=1&sid=1
	public function action($params){
		$user_id = $params['pid'];
		$user = User::find($user_id);
		if(!$user){
			throw new PadException(RespCode::USER_NOT_FOUND,"user not find");
		}
		$levels = VipBonus::getAvailableBonusLevel($user_id);
		//user vip lv greater than 1 and haven't get weekly bonus
		if($user->isVipWeeklyBonusAvailable()){
			$available_weekly = 1;
		}else{
			$available_weekly = 0;
		}
		$result = array(
				'res'=> RespCode::SUCCESS,
				'levels'=>$levels,
				'weekly'=>$available_weekly,
		);
		return json_encode($result);
	}
}
