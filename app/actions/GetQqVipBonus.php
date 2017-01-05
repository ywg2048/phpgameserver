<?php
class GetQqVipBonus extends BaseAction {
	public function action($params){
		$user_id = $params['pid'];
		$token = Tencent_MsdkApi::checkToken($params);
		$bonus_type = $params['bt'];
		$qq_vip = $params['qv'];
		$rev = isset($params['r']) ? $params['r'] : 0;
		
		//check parameters
		if($bonus_type == 1){
			$bonus_type = QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS;
			// 新手礼包自动发送，这里逻辑取消
			/*
		}else if($bonus_type == 2){
			$bonus_type = QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS;
			*/
		}else{
			throw new PadException(RespCode::UNKNOWN_ERROR, 'parameter error!');
		}
		if($qq_vip != User::QQ_ACCOUNT_VIP && $qq_vip != User::QQ_ACCOUNT_SVIP){
			throw new PadException(RespCode::UNKNOWN_ERROR, 'parameter error!');
		}
		
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$user = User::find($user_id, $pdo, true);
		
		if($user->checkQqVipBonusAvalible($bonus_type, $qq_vip) != User::QQ_VIP_BONUS_AVALIBLE){
			throw new PadException(RespCode::UNKNOWN_ERROR, 'Can not get this bonus!');
		}
		
		//reason for tlog
		if($bonus_type == QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS){
			if($qq_vip == User::QQ_ACCOUNT_VIP){
				$subreason = Tencent_Tlog::SUBREASON_QQ_VIP_PURCHASE_BONUS;
				$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_VIP_PURCHASE_BONUS;
				$message = GameConstant::getParam("QQVipPurchaseBonusMessage");
			}else{
				$subreason = Tencent_Tlog::SUBREASON_QQ_SVIP_PURCHASE_BONUS;
				$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_SVIP_PURCHASE_BONUS;
				$message = GameConstant::getParam("QQSvipPurchaseBonusMessage");
			}
		}else{
			if($qq_vip == User::QQ_ACCOUNT_VIP){
				$subreason = Tencent_Tlog::SUBREASON_QQ_VIP_NOVICE_BONUS;
				$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_VIP_NOVICE_BONUS;
			}else{
				$subreason = Tencent_Tlog::SUBREASON_QQ_SVIP_NOVICE_BONUS;
				$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_SVIP_NOVICE_BONUS;
			}
		}
		
		try{
			$pdo->beginTransaction();

			//TLOG
			UserTlog::beginTlog($user, array(
				'money_reason' => Tencent_Tlog::REASON_QQ_VIP_BONUS,
				'money_subreason' => $subreason,
				'item_reason' => Tencent_Tlog::ITEM_REASON_QQ_VIP_BONUS,
				'item_subreason' => $itemSubReason,
			));
			
			$bonuses = QqVipBonus::getBonuses($bonus_type, $qq_vip);
			$items = [];
			if($bonus_type == QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS){
				foreach($bonuses as $bonus) {
					UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, $bonus->bonus_id, $bonus->amount, $pdo, $message, null, $bonus->piece_id);
				}
			}else{
				foreach($bonuses as $bonus){
					$item = $user->applyBonus($bonus->bonus_id, $bonus->amount, $pdo, null, $token, $bonus->piece_id);
					$items[] = User::arrangeBonusResponse($item, $rev);
				}
			}
			$user->setQqVipBonusReceived($bonus_type, $qq_vip);
			$user->update($pdo);
			
			$pdo->commit();
			
			//TLOG
			UserTlog::commitTlog($user);

		}catch (Exception $e){
			if($pdo->inTransaction()){
				$pdo->rollBack();
			}
			throw $e;
		}
		
		$result = array(
				'res' => RespCode::SUCCESS,
				'items' => $items,
				'qq_vip_purchase' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS, User::QQ_ACCOUNT_VIP ),
				'qq_vip_novice' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, User::QQ_ACCOUNT_VIP ),
				'qq_svip_purchase' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS, User::QQ_ACCOUNT_SVIP ),
				'qq_svip_novice' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, User::QQ_ACCOUNT_SVIP ), 
		);

		// #PADC# QQ report score
		if(isset($res['items']['card'])){
			User::reportUserCardNum($user_id, $token['access_token']);
		}
		
		return json_encode($result);
	}
}