<?php
/**
 * 15. フレンド申請
 */
class RequestFriend extends BaseAction {

	// http://pad.localhost/api.php?action=request_friend&pid=0&sid=0&req=1
	public function action($params){
		global $logger;
    $mode = isset($params['m']) ? $params['m'] : 0;
		UserMail::sendFriendRequest($params['pid'], $params['req'], FALSE, $mode);
		return json_encode(array('res'=>RespCode::SUCCESS));
	}

}
