<?php
/**
 * #PADC# Get Vip weekly Bonus
 * 
 * apply vip weekly bonus if vip_lv is greater than 1
 * @author zhudesheng
 */
class GetVipWeeklyBonus extends BaseAction {
	// http://pad.localhost/api.php?action=getweeklybonu&pid=1&sid=1&vip_lv=1&ten_at=1&ten_pt=1&ten_pf=1&ten_pfk=1
	public function action($params){
		$user_id = $params['pid'];
		$token = Tencent_MsdkApi::checkToken($params);
		$rev = isset($params['r']) ? $params['r'] : 0;
		
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		
		//find user object
		$pdo = ENV::getDbConnectionForUserWrite($user_id);
		$user = User::find($user_id,$pdo);
		if(!$user){
			throw new PadException(RespCode::USER_NOT_FOUND,'not find user');
		}		
		
		//check whether or not have had got weekly bonus
		if(VipBonus::isSameWeek_AM4(BaseModel::strToTime($user->last_vip_weekly))){
			//maybe need add a security log
			throw new PadException(RespCode::UNKNOWN_ERROR,"user have already get weekly bonus");
		}else {
			try{
				$pdo->beginTransaction();
				
				//Tlog
				UserTlog::beginTlog($user, array(
					'money_reason' => Tencent_Tlog::REASON_BONUS,
					'money_subreason' => Tencent_Tlog::SUBREASON_VIP_WEEKLY_BONUS,
					'item_reason' => Tencent_Tlog::ITEM_REASON_BONUS,
					'item_subreason' => Tencent_Tlog::ITEM_SUBREASON_VIP_WEEKLY_BONUS,
				));
								
				/**get weekly bonuses ,apply weekly bonuses to player*/
				$weeklyBonuses = VipBonus::getWeeklyVipBonuses($user->vip_lv,$rev);
				$result = VipBonus::applyVipBonuses($weeklyBonuses,$pdo,$token,$user, $rev);
				/***if applay bonus is ok ,update user object last_vip_weekly to now ,then commit*/
				$user->last_vip_weekly = VipBonus::timeToStr(time());
				$user->update($pdo);
				$pdo->commit();
					
				//TLOG
				UserTlog::commitTlog($user, $token);
			
			}catch (Exception $e){
				if($pdo->inTransaction()){
					$pdo->rollBack();
				}
				throw $e;
			}
		}
		
		// #PADC# QQ report score
		if(isset($result['card'])){
			User::reportUserCardNum($user_id, $token['access_token']);
		}
		
		return json_encode(array('res'=>RespCode::SUCCESS,'items'=>$result));
	}
}