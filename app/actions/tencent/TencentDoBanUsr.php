<?php
/**
 * Tencent用：
 */
class TencentDoBanUsr extends TencentBaseAction {
	public function action($params) {
		$openId = $params ['OpenId'];
		$platId = $params ['PlatId'];
		$time = $params ['Time'];
		$reason = $params ['Reason'];
		$source = isset ( $params ['Source'] ) ? $params ['Source'] : null;
		$serial = isset ( $params ['Serial'] ) ? $params ['Serial'] : null;
		
		if ($time > 0) {
			$end_time = time () + $time;
		} else {
			$end_time = time () + 315360000; // 100year
		}
		$user_id = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		try {
			$pdo->beginTransaction ();
			
			UserBanMessage::punishUser ( $user_id, array (
					array (
							'type' => User::PUNISH_BAN,
							'end_time' => $end_time,
							'message' => $reason 
					) 
			), $pdo );
			
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
		// temporarily,we used ban or unban status
		$result = array (
				'res' => 0,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success" 
		);
		return json_encode ( $result );
	}
}