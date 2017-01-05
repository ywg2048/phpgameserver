<?php
/**
 * 禁言接口
*/
class TencentDoMaskchat extends TencentBaseAction {

	public function action($params) {
		if (isset ( $params ['RoleId'] )) {
			$disp_id = $params ['RoleId'];
			$user_id =  UserDevice::convertDispIdToPlayerId($disp_id);

			$user = User::find($user_id);
			if(empty($user)){
				throw new PadException(RespCode::USER_NOT_FOUND,'user not find');
			}

		} else if (isset ( $params ['OpenId'] ) && isset ( $params ['PlatId'] )) {
			$openid = $params ['OpenId'];
			$type = $params ['PlatId'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		} else {
			throw new PadException ( static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		if (isset ( $params ['BanTime'] )) {
			$end_time = time () + $params ['BanTime'];
		} else {
			throw new PadException ( static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		if (isset ( $params ['BanReason'] )) {
			$message = $params ['BanReason'];
		} else {
			throw new PadException ( static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		try {
			$pdo->beginTransaction ();
			
			UserBanMessage::punishUser ( $user_id, array (
					array (
							'type' => User::PUNISH_SLIENCE,
							'end_time' => $end_time,
							'message' => $message 
					) 
			), $pdo );
			
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
		
		User::kickOff ( $user_id );
		
		$result = array_merge ( array (
				'res' => 0,
				'msg' => 'OK',
				'Result' => 0,
				'RetMsg' => 'success' 
		) );
		
		return json_encode ( $result );
	}
}
