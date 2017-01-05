<?php
/**
 * Tencent用：
 */
class TencentDoUnbanUsr extends TencentBaseAction {
	public function action($params) {
		$openId = $params ['OpenId'];
		$platId = $params ['PlatId'];
		$source = isset ( $params ['Source'] ) ? $params ['Source'] : null;
		$serial = isset ( $params ['Serial'] ) ? $params ['Serial'] : null;
		
		$user_id = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		try {
			$pdo->beginTransaction ();
			
			UserBanMessage::relievePunish ( $user_id, array (
					User::PUNISH_BAN 
			), $pdo );
			
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
		$result = array (
				'res' => 0,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success" 
		);
		return json_encode ( $result );
	}
}