<?php
/**
 * 安全IDIP：機能禁止
*/
class TencentAqDoBanPlay extends TencentBaseAction {
	const TYPE_NORMAL = 1;
	const TYPE_RANKING = 2;
	const TYPE_SPECIAL = 3;
	const TYPE_BUYDUNG = 4;
	const TYPE_ALL = 99;
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		if (isset ( $params ['RoleId'] )) {
			$user_id = $params ['RoleId'];

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
		
		if (isset ( $params ['Time'] )) {
			$end_time = time () + $params ['Time'];
		} else {
			throw new PadException ( static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		if (isset ( $params ['Tip'] )) {
			$message = $params ['Tip'];
		} else {
			throw new PadException ( static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		if (isset ( $params ['Type'] )) {
			$type = $params ['Type'];
		} else {
			throw new PadException ( static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		try {
			$pdo->beginTransaction ();
			
			if ($type == self::TYPE_NORMAL) {
				UserBanMessage::punishUser ( $user_id, array (
						array (
								'type' => User::PUNISH_PLAY_BAN_NORMAL,
								'end_time' => $end_time,
								'message' => $message 
						) 
				), $pdo );
			} else if ($type == self::TYPE_SPECIAL) {
				UserBanMessage::punishUser ( $user_id, array (
						array (
								'type' => User::PUNISH_PLAY_BAN_SPECIAL,
								'end_time' => $end_time,
								'message' => $message 
						) 
				), $pdo );
			} else if ($type == self::TYPE_RANKING) {
				UserBanMessage::punishUser ( $user_id, array (
						array (
								'type' => User::PUNISH_PLAY_BAN_RANKING,
								'end_time' => $end_time,
								'message' => $message 
						) 
				), $pdo );
			} else if ($type == self::TYPE_BUYDUNG) {
				UserBanMessage::punishUser ( $user_id, array (
						array (
								'type' => User::PUNISH_PLAY_BAN_BUYDUNG,
								'end_time' => $end_time,
								'message' => $message 
						) 
				), $pdo );
			} else if ($type == self::TYPE_ALL) {
				UserBanMessage::punishUser ( $user_id, array (
						array (
								'type' => User::PUNISH_PLAY_BAN_RANKING,
								'end_time' => $end_time,
								'message' => $message 
						),
						array (
								'type' => User::PUNISH_PLAY_BAN_SPECIAL,
								'end_time' => $end_time,
								'message' => $message 
						),
						array (
								'type' => User::PUNISH_PLAY_BAN_RANKING,
								'end_time' => $end_time,
								'message' => $message 
						),
						array (
								'type' => User::PUNISH_PLAY_BAN_BUYDUNG,
								'end_time' => $end_time,
								'message' => $message 
						) 
				), $pdo );
			}
			
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
