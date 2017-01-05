<?php
/**
 * this api is for share tlog
 * @author zhudesheng
 *
 */
class Share extends BaseAction {
	// http://pad.localhost/api.php?action=share&pid=1&sid=1
	public function action($params){
		$user_id = $params['pid'];
		$share_type = $params['st'];
		
		$dungeon_id = isset($params['dung'])? $params['dung'] : 0;
		$card_id =isset($params['cid'])? $params['cid'] : 0;
		UserTlog::sendTlogShareFlow($user_id, $share_type, $dungeon_id, $card_id);
		return json_encode(array('res'=>RespCode::SUCCESS));
	}
}
