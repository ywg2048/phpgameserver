<?php
/**
 * 17. フレンド申請許可
 */
class AcceptFriendRequest extends BaseAction {

	// http://pad.localhost/api.php?action=accept_friend_request&pid=0&sid=0&msgid=1&ack=1
	public function action($params){
		UserMail::replyFriendRequest($params['pid'], $params['msgid'], $params['ack']);

		$user_id = $params['pid'];

		//新手嘉年华，检查玩家的朋友数量
		UserCarnivalInfo::carnivalMissionCheck($user_id,CarnivalPrize::CONDITION_TYPE_FRIEND_NUMBER);
		$mail = UserMail::findBy(array('id' =>$params['msgid'] , 'user_id' => $user_id));
		if(false != $mail){
			UserCarnivalInfo::carnivalMissionCheck($mail->sender_id,CarnivalPrize::CONDITION_TYPE_FRIEND_NUMBER);
		}

		return json_encode(array('res'=>RespCode::SUCCESS));
	}

  /**
   * このAPIをストレステストする際のダミーデータを作成する.
   */
  public function createDummyDataForUser($user, $pdo) {
  	try{
  	  UserMail::sendFriendRequest($user->id-1, $user->id);
	}catch(Exception $e){
		// ignore
	}
  }
}
