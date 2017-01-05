<?php
/**
 * #PADC# Get Vip Level Up Bonus
 *
 * get someone vip level up bonus,whether or not token is expired and user cheat vip bonuses,if all ok,get bonuses from database
 * apply bonuses to user ,then record history of got vip level up bonus,return res,vip_lv and card
 *
 * @author zhudesheng
 */
class GetVipLvUpBonus extends BaseAction {
	// http://pad.localhost/api.php?action=getviplvupbonu&pid=1&sid=1&vip_lv=1&ten_at=1&ten_pt=1&ten_pf=1&ten_pfk=1
	public function action($params){
		$user_id = $params['pid'];
		$rev = isset($params['r']) ? $params['r'] : 0;
		$blv = (int)$params['blv'];//blv is bonus level
		$token = Tencent_MsdkApi::checkToken($params);
		
		$uservipbonus = new UserVipBonus();
		list($user,$pdo) = $uservipbonus->checkBonuses($user_id, $blv);
		try{
			$pdo->beginTransaction();

			//TLOG
			UserTlog::beginTlog($user, array(
				'money_reason' => Tencent_Tlog::REASON_BONUS,
				'money_subreason' => Tencent_Tlog::SUBREASON_VIP_LV_UP_BONUS,
				'item_reason' => Tencent_Tlog::ITEM_REASON_BONUS,
				'item_subreason' => Tencent_Tlog::ITEM_SUBREASON_VIP_LV_UP_BONUS,
			));
			
			/**get lv up bonuses ,apply lv up bonuses to player*/
			$LvUpBonuses = VipBonus::getLvUpBonuses($blv);
			
			$result = VipBonus::applyVipBonuses($LvUpBonuses,$pdo,$token,$user,$rev);
			/***if applay bonus is ok ,new a userVipBonus object to insert a record in user vip bonus ,then commit*/
			$uservipbonus->createUserBonusRecord($user_id, $blv,$pdo);
			$user->update($pdo);// updateしないとコインや友情ポイント、図鑑登録数が反映されません
			$pdo->commit();
			
			//TLOG
			UserTlog::commitTlog($user, $token);

		}catch (Exception $e){
			if($pdo->inTransaction()){
				$pdo->rollBack();
			}
			throw $e;
		}

		$res = array(
				'res' => RespCode::SUCCESS,
				'vip_lv' => $blv,
				'items' => $result
		);

		// #PADC#
		// ミッションクリア確認（図鑑登録数）
		list($res['ncm'], $res['clear_mission_list']) = UserMission::checkClearMissionTypes ( $user_id, array (
				Mission::CONDITION_TYPE_BOOK_COUNT,
		) );

		// #PADC# QQ report score
		if(isset($res['items']['card'])){
			User::reportUserCardNum($user_id, $token['access_token']);
		}
		
		return json_encode($res);

	}
}