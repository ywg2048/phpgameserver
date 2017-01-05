<?php
/**
 * Tencent用：
 */
class AdminUpdateUserStamina extends AdminBaseAction {
	// https://pad.localhost/api_tencent.php?action=tencent_update_user_stamina&oid=1&t=2
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$amount = $params ['amount'];
		
		$stamina = static::updateStamina ( $user_id, $amount );

		$result = array (
				'res' => RespCode::SUCCESS,
				'sta' => $stamina 
		);
		return json_encode ( $result );
	}
	
	/**
	 * update stamina
	 *
	 * @param string $user_id        	
	 * @param int $amount        	
	 * @throws PadException
	 * @throws PDOException
	 */
	public static function updateStamina($user_id, $amount) {
		try {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
			$pdo->beginTransaction ();
			
			$user = User::find ( $user_id, $pdo, TRUE );
			if ($user == false) {
				throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found' );
			}
			
			$user->useStamina ( $user->getStamina () - $amount );
			
			$user->update ( $pdo );
			$pdo->commit ();
			
			return $user->stamina;
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
	}
}
