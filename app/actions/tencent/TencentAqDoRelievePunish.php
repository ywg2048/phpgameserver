<?php
/**
 * 安全IDIP：処罰解除
*/
class TencentAqDoRelievePunish extends TencentBaseAction {
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
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		try {
			$pdo->beginTransaction ();
			
			if (isset ( $params ['RelieveZeroProfit'] ) && $params ['RelieveZeroProfit']) {
				UserBanMessage::relievePunish ( $user_id, array (
						User::PUNISH_ZEROPROFIT 
				), $pdo );
			}
			if (isset ( $params ['RelievePlayAll'] ) && $params ['RelievePlayAll']) {
				UserBanMessage::relievePunish ( $user_id, array (
						User::PUNISH_PLAY_BAN_NORMAL,
						User::PUNISH_PLAY_BAN_SPECIAL,
						User::PUNISH_PLAY_BAN_RANKING, 
						User::PUNISH_PLAY_BAN_BUYDUNG 
				), $pdo );
			}
			if (isset ( $params ['RelieveBan'] ) && $params ['RelieveBan']) {
				UserBanMessage::relievePunish ( $user_id, array (
						User::PUNISH_BAN 
				), $pdo );
			}
			if (isset ( $params ['RelieveBanJoinRank'] ) && $params ['RelieveBanJoinRank']) {
				UserBanMessage::relievePunish ( $user_id, array (
						User::PUNISH_PLAY_BAN_RANKING 
				), $pdo );
			}
			
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
		
		$result = array_merge ( array (
				'res' => 0,
				'msg' => 'OK',
				'Result' => 0,
				'RetMsg' => 'success' 
		) );
		
		return json_encode ( $result );
	}
}
