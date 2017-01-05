<?php
/**
 * Admin用：
 */
class AdminAddUserMails extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$clear = isset ( $params ['clear'] ) ? $params ['clear'] : 0;
		$user_mails = isset ( $params ['user_mails'] ) ? $params ['user_mails'] : 0;
		$mail_msg = isset ( $params ['mail_msg'] ) ? $params ['mail_msg'] : '';
		$presents = isset ( $params ['presents'] ) ? $params ['presents'] : 0;
		$admin_mails = isset ( $params ['admin_mails'] ) ? $params ['admin_mails'] : 0;
		$coins = isset ( $params ['c'] ) ? $params ['c'] : 0;
		$golds = isset ( $params ['g'] ) ? $params ['g'] : 0;
		$friend_points = isset ( $params ['fripnt'] ) ? $params ['fripnt'] : 0;
		$stamina = isset ( $params ['sta'] ) ? $params ['sta'] : 0;
		$piece_id = isset ( $params ['piece_id'] ) ? $params ['piece_id'] : 0;
		$piece_num = isset ( $params ['piece_num'] ) ? $params ['piece_num'] : 0;
		
		global $logger;
		$logger->log('$piece_id:'.$piece_id.' $piece_num:'.$piece_num. ' $mail_msg:'.$mail_msg, 7);
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		if(!User::find($user_id, $pdo)){
			throw new PadException(RespCode::USER_NOT_FOUND);
		}
		
		if ($clear) {
			$this->clearMailsAndPresents ( $user_id, $pdo );
		}
		
		if ($user_mails > 0 || $presents > 0) {
			$friend_id = $this->getFriendId ( $user_id, $pdo );
		}
		
		if ($user_mails > 0) {
			$this->sendUserMails ( $user_id, $friend_id, $user_mails, $mail_msg );
		}
		
		if ($presents > 0) {
			$this->sendPresents ( $user_id, $friend_id, $presents, $pdo );
		}
		
		if ($admin_mails > 0) {
			$this->sendAdminMails ( $user_id, $admin_mails, $mail_msg, $coins, $golds, $friend_points, $stamina, $piece_id, $piece_num, $pdo );
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param PDO $pdo        	
	 * @return number
	 */
	private function getFriendId($user_id, $pdo) {
		$fids = Friend::getFriendids ( $user_id );
		if (! empty ( $fids )) {
			// フレンドあり、一番目を使います。
			return $fids [0];
		}
		
		// フレンドがない、作ります。
		$fid = $this->createFriend ( $user_id, $pdo );
		
		return $fid;
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param PDO $pdo        	
	 * @throws PadException
	 * @throws Exception
	 * @return number
	 */
	private function createFriend($user_id, $pdo) {
		$pdo_share	= Env::getDbConnectionForShareRead();
		$user_devices = UserDevice::findAllBy(array(), null, null, $pdo_share);
		shuffle($user_devices);
		
		$user1_device = null;
		$user2_device = null;
		foreach ( $user_devices as $user_device ) {
			if ($user_device->id == $user_id) {
				$user1_device = $user_device;
			}
			else {
				$user2_device = $user_device;
			}
			if ($user1_device && $user2_device) {
				break;
			}
		}
		if (! $user2_device) {
			throw new PadException ( RespCode::UNKNOWN_ERROR );
		}
		
		$fid = $user2_device->id;
		$pdo2 = null;
		if ($user1_device->dbid != $user2_device->dbid) {
			$pdo2 = Env::getDbConnectionForUserWrite ( $fid );
		}
		
		try {
			$pdo->beginTransaction ();
			if ($pdo2) {
				$pdo2->beginTransaction ();
			}
			Friend::accept ( $user_id, $fid, $pdo, ($pdo2 ? $pdo2 : $pdo) );
			if ($pdo2) {
				$pdo2->commit ();
			}
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollBack ();
			}
			if ($pdo2 && $pdo2->inTransaction ()) {
				$pdo2->rollBack ();
			}
			throw $e;
		}
		return $fid;
	}
	
	/**
	 *
	 * @param number $user_id        	
	 */
	private function clearMailsAndPresents($user_id, $pdo) {
		// clear mails
		$stmt = $pdo->prepare ( 'DELETE FROM ' . UserMail::TABLE_NAME . ' WHERE user_id = ?' );
		$result = $stmt->execute ( array (
				$user_id 
		) );
		
		// clear presents
		$stmt = $pdo->prepare ( 'DELETE FROM ' . UserPresentsReceive::TABLE_NAME . ' WHERE receiver_id = ?' );
		$result = $stmt->execute ( array (
				$user_id 
		) );
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param number $sender_id        	
	 * @param number $num        	
	 * @param string $message        	
	 */
	private function sendUserMails($user_id, $sender_id, $num, $message) {
		for($i = 0; $i < $num; $i ++) {
			UserMail::sendMail ( $sender_id, $user_id, $message );
		}
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param number $sender_id        	
	 * @param number $num        	
	 * @param PDO $pdo        	
	 * @throws Exception
	 */
	private function sendPresents($user_id, $sender_id, $num, $pdo) {
		$pdo_share	= Env::getDbConnectionForShareRead();
		$user1_device = UserDevice::find($user_id, $pdo_share);
		$user2_device = UserDevice::find($sender_id, $pdo_share);
		
		$pdo2 = null;
		if ($user1_device->dbid != $user2_device->dbid) {
			$pdo2 = Env::getDbConnectionForUserWrite ( $sender_id );
		}
		try {
			$pdo->beginTransaction ();
			if ($pdo2) {
				$pdo2->beginTransaction ();
			}
			for($i = 0; $i < $num; $i ++) {
				UserPresentsSend::sendPresent ( $sender_id, $user_id, ($pdo2 ? $pdo2 : $pdo) );
				UserPresentsReceive::sendPresent ( $sender_id, $user_id, $pdo );
			}
			if ($pdo2) {
				$pdo2->commit ();
			}
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollBack ();
			}
			if ($pdo2 && $pdo2->inTransaction ()) {
				$pdo2->rollBack ();
			}
			throw $e;
		}
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param number $num        	
	 * @param string $message        	
	 * @param number $coins        	
	 * @param number $golds        	
	 * @param number $friend_points        	
	 * @param number $stamina        	
	 * @param number $piece_id        	
	 * @param number $piece_num        	
	 * @param PDO $pdo        	
	 */
	private function sendAdminMails($user_id, $num, $message, $coins, $golds, $friend_points, $stamina, $piece_id, $piece_num, $pdo) {
		for($i = 0; $i < $num; $i ++) {
			$send_bonus = false;
			if ($coins > 0) {
				$send_bonus = true;
				UserMail::sendAdminMailMessage ( $user_id, UserMail::TYPE_ADMIN_BONUS, BaseBonus::COIN_ID, $coins, $pdo, $message );
			}
			if ($golds > 0) {
				$send_bonus = true;
				UserMail::sendAdminMailMessage ( $user_id, UserMail::TYPE_ADMIN_BONUS, BaseBonus::MAGIC_STONE_ID, $golds, $pdo, $message );
			}
			if ($friend_points > 0) {
				$send_bonus = true;
				UserMail::sendAdminMailMessage ( $user_id, UserMail::TYPE_ADMIN_BONUS, BaseBonus::FRIEND_POINT_ID, $friend_points, $pdo, $message );
			}
			if ($stamina > 0) {
				$send_bonus = true;
				UserMail::sendAdminMailMessage ( $user_id, UserMail::TYPE_ADMIN_BONUS, BaseBonus::STAMINA_RECOVER_ID, $stamina, $pdo, $message );
			}
			if ($piece_id > 0 && $piece_num > 0) {
				$send_bonus = true;
				$piece = Piece::find ( $piece_id );
				if ($piece) {
					UserMail::sendAdminMailMessage ( $user_id, UserMail::TYPE_ADMIN_BONUS, BaseBonus::PIECE_ID, $piece_num, $pdo, $message, null, $piece_id );
				}
			}
			if(!$send_bonus){
				UserMail::sendAdminMail($user_id, $message, UserMail::TYPE_ADMIN_MESSAGE);
			}
		}
	}
}
