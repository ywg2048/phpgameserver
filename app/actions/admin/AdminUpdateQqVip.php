<?php
class AdminUpdateQqVip extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$clear = $params ['cl'];
		$qq_vip = $params ['qv'];
		$clear_bonus = $params ['cb'];
		$qq_vip_purchase = $params ['qvp'];
		$qq_svip_purchase = $params ['qsvp'];
		
		if ($clear) {
			QqVipBonus::clearDebugValue ( $user_id );
		} else {
			QqVipBonus::setDebugValue ( $user_id, $qq_vip );
		}
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		try {
			$pdo->beginTransaction ();
			
			$user = User::find ( $user_id, $pdo, true );
			if ($clear_bonus) {
				$user->qq_vip_gift = 0;
				$user->lqvdb_days = 0;
			}
			if ($qq_vip_purchase) {
				$user->qq_vip_gift |= User::QQ_VIP_PURCHASE;
			}
			if ($qq_svip_purchase) {
				$user->qq_vip_gift |= User::QQ_SVIP_PURCHASE;
			}
			
			$user->update ( $pdo );
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
}