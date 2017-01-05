<?php
/**
 * Admin用：フレンド追加
 */
class AdminAddFriend extends AdminBaseAction {
	public function action($params) {
		global $logger;
		$logger->log('AdminAddFriend', 7);
		if (isset ( $params ['pid1'] )) {
			$user_id1 = $params ['pid1'];
			$userDeviceData1 = UserDevice::getUserDeviceFromRedis($user_id1);// 指定のユーザが存在しているかチェック
		} else {
			$openid1 = $params ['ten_oid1'];
			$type1 = $params ['t1'];
			$user_id1 = UserDevice::getUserIdFromUserOpenId ( $type1, $openid1 );
		}
		if (isset ( $params ['pid2'] )) {
			$user_id2 = $params ['pid2'];
			$userDeviceData2 = UserDevice::getUserDeviceFromRedis($user_id2);// 指定のユーザが存在しているかチェック
		} else {
			$openid2 = $params ['ten_oid2'];
			$type2 = $params ['t2'];
			$user_id2 = UserDevice::getUserIdFromUserOpenId ( $type2, $openid2 );
		}
		$user_id_end = isset($params ['pidn'])? (int)$params ['pidn'] : 0;
		$del_friends = isset($params ['del'])? $params ['del'] : 0;
		
		if(!$user_id_end){
			$user_id_end = $user_id2;
		}
		
		for($tid = $user_id2; $tid <= $user_id_end; $tid++){
			if(!$del_friends){
				if($this->addFriend($user_id1, $tid)){
					break;
				}
			}else{
				if($this->removeFriend($user_id1, $tid)){
					break;
				}
			}
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
	
	/**
	 * 
	 * @param number $user_id1
	 * @param number $user_id2
	 */
	private function addFriend($user_id1, $user_id2){
		global $logger;
		$logger->log('addFriend '.$user_id1.' '.$user_id2, 7);
		if($user_id1 == $user_id2){
			return false;
		}
		
		$pdo_user1 = Env::getDbConnectionForUserWrite ( $user_id1 );
		$pdo_user2 = Env::getDbConnectionForUserWrite ( $user_id2 );
		$user1 = User::find ( $user_id1, $pdo_user1, true );
		$user2 = User::find ( $user_id2, $pdo_user2, true );
		if (! $user1 || ! $user2) {
			//throw new PadException ( RespCode::USER_NOT_FOUND );
			$logger->log('!user', 7);
			return false;
		}
		
		if (Friend::isFriend ( $user_id1, $user_id2 )) {
			//throw new PadException ( RespCode::ALREADY_FRIEND, "id:$user_id1 and  id:$user_id2 are already friends." );
			$logger->log('already', 7);
			return false;
		}
		if ($user1->fricnt >= $user1->friend_max) {
			$logger->log('p1 max', 7);
			return true;
		}
		if ($user2->fricnt >= $user2->friend_max) {
			//throw new PadException ( RespCode::RECEIVER_TOO_MANY_FRIENDS, "The number of user2(id=$user_id2)'s friends has exceeded a limit." );
			$logger->log('p2 max', 7);
			return false;
		}
		
		Friend::accept ( $user_id1, $user_id2, $pdo_user1, $pdo_user2 );
		
		$this->removeRequestMails ( $user1, $user2, $pdo_user1, $pdo_user2 );

		if ($user1->fricnt + 1 >= $user1->friend_max) {
			$logger->log('p1 max', 7);
			return true;
		}
	}
	
	/**
	 *
	 * @param User $user1        	
	 * @param User $user2        	
	 * @param PDO $pdo_user1        	
	 * @param PDO $pdo_user2        	
	 */
	private function removeRequestMails($user1, $user2, $pdo_user1, $pdo_user2) {
		$this->removeRequestMail ( $user2, $user1, $pdo_user1 );
		$this->removeRequestMail ( $user1, $user2, $pdo_user2 );
	}
	
	/**
	 *
	 * @param User $sender        	
	 * @param User $receiver        	
	 * @param PDO $pdo_receiver        	
	 */
	private function removeRequestMail($sender, $receiver, $pdo_receiver) {
		$request_mail = UserMail::findBy ( array (
				'type' => UserMail::TYPE_FRIEND_REQUEST,
				'user_id' => $receiver->id,
				'sender_id' => $sender->id 
		), $pdo_receiver );
		
		if ($request_mail) {
			$request_mail->delete ( $pdo_receiver );
			$receiver->fr_cnt --;
			$receiver->update ( $pdo_receiver );
			User::resetMailCount ( $receiver->id );
		}
	}
	
	/**
	 * 
	 * @param number $user_id1
	 * @param number $user_id2
	 */
	private function removeFriend($user_id1, $user_id2){
		if($user_id1 == $user_id2){
			return false;
		}
		
		$pdo_user1 = Env::getDbConnectionForUserWrite ( $user_id1 );
		$pdo_user2 = Env::getDbConnectionForUserWrite ( $user_id2 );
		$user1 = User::find ( $user_id1, $pdo_user1, true );
		$user2 = User::find ( $user_id2, $pdo_user2, true );
		
		if (! $user1 || ! $user2) {
			return false;
		}
		
		if (!Friend::isFriend ( $user_id1, $user_id2 )) {
			return false;
		}
		
		if ($user1->fricnt <= 0) {
			return true;
		}
		
		if ($user2->fricnt <= 0) {
			return false;
		}
		
		Friend::quit($user_id1, $user_id2);
		
		if ($user1->fricnt - 1 <= 0) {
			return true;
		}
	}
}
