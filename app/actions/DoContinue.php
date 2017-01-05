<?php
/**
 * 12. コンティニュー.
 */
class DoContinue extends BaseAction {
	// http://pad.localhost/api.php?action=do_continue&pid=1&sid=1
	public function action($params){
		$use_cont = (isset($params["use_cont"]) ? $params["use_cont"] : 0);
		$hash = UserContinue::saveHash($params["pid"], $use_cont);
		return json_encode(array('res' => RespCode::SUCCESS, 'rid'=>$hash));
	}
}
