<?php
class ReflectQqVip extends BaseAction {
	public function action($params) {
		$user_id = $params ["pid"];
		$token = Tencent_MsdkApi::checkToken ( $params );
		if (! $token) {
			return json_encode ( array (
					'res' => RespCode::TENCENT_TOKEN_ERROR
			) );
		}
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		try {
			$pdo->beginTransaction ();
			
			$user = User::find ( $user_id, $pdo, true );
			$user->getQqVip ( $token, $pdo , false);
			
			//qq vip login bonus
			if($user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_LOGIN_BONUS) == User::QQ_VIP_BONUS_AVALIBLE){
				$qqVipLoginBonuses = QqVipBonus::getBonuses(QqVipBonus::TYPE_QQ_VIP_LOGIN_BONUS, $user->qq_vip);
				foreach($qqVipLoginBonuses as $qvlBonus){
					$base_message = GameConstant::getParam("QQVipLoginBonusMessage");
					$bonus_message = ($user->qq_vip == User::QQ_ACCOUNT_SVIP)? GameConstant::getParam("QQSvipLoginBonus"):GameConstant::getParam("QQVipLoginBonus");
					$message = sprintf($base_message,$bonus_message, $bonus_message);
					UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, $qvlBonus->bonus_id, $qvlBonus->amount, $pdo,$message,null,$qvlBonus->piece_id);
				}
				$user->lqvdb_days = $user->li_days;
			}
			// #PADC_DY# ----------begin----------
			// QQ VIP新手礼包以邮件的形式发放给玩家
			if ($user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, $user->qq_vip) == User::QQ_VIP_BONUS_AVALIBLE){
				if ($user->qq_vip == User::QQ_ACCOUNT_VIP){
					$subreason = Tencent_Tlog::SUBREASON_QQ_VIP_NOVICE_BONUS;
					$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_VIP_NOVICE_BONUS;
					$message = GameConstant::getParam("QQVipNoviceBonusMessage");
				}else {
					$subreason = Tencent_Tlog::SUBREASON_QQ_SVIP_NOVICE_BONUS;
					$itemSubReason = Tencent_Tlog::ITEM_SUBREASON_QQ_SVIP_NOVICE_BONUS;
					$message = GameConstant::getParam("QQSvipNoviceBonusMessage");
				}

				$bonuses = QqVipBonus::getBonuses(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, $user->qq_vip);
				foreach	($bonuses as $bonus){
					UserMail::sendAdminMailMessage($user->id, UserMail::TYPE_ADMIN_BONUS, $bonus->bonus_id, $bonus->amount, $pdo, $message, null, $bonus->piece_id);
				}

				$user->setQqVipBonusReceived(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, $user->qq_vip);

				UserTlog::beginTlog($user, array(
						'money_reason' => Tencent_Tlog::REASON_QQ_VIP_BONUS,
						'money_subreason' => $subreason,
						'item_reason' => Tencent_Tlog::ITEM_REASON_QQ_VIP_BONUS,
						'item_subreason' => $itemSubReason,
				));

				UserTlog::commitTlog($user);
			}
			// #PADC_DY# ----------end----------
			$user->update($pdo);
			$pdo->commit();
			$mails = User::getMailCount ( $user->id, User::MODE_NORMAL, $pdo, TRUE );
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
		
		$res = array (
				'res' => RespCode::SUCCESS,
				'qq_vip' => $user->qq_vip,
				'qq_vip_purchase' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS, User::QQ_ACCOUNT_VIP ),
				'qq_vip_novice' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, User::QQ_ACCOUNT_VIP ),
				'qq_svip_purchase' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS, User::QQ_ACCOUNT_SVIP ),
				'qq_svip_novice' => $user->checkQqVipBonusAvalible ( QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, User::QQ_ACCOUNT_SVIP ), 
				'qq_vip_expire' => $user->qq_vip_expire,
				'qq_svip_expire' => $user->qq_svip_expire
		);
		
		return json_encode ( $res );
	}
}