<?php
/**
 * 18. フレンド解除
 */
class QuitFriend extends BaseAction {

	// http://pad.localhost/api.php?action=quit_friend&pid=2&sid=0&del=3
	public function action($params){
		Friend::quit($params['pid'], $params['del']);
		return json_encode(array('res'=>RespCode::SUCCESS));
	}

}
