<?php
/**
 * 12. コンティニューACK.
 */
class DoContinueAck extends BaseAction {
	// http://pad.localhost/api.php?action=do_continue_ack&pid=1&sid=1&rid=foobar
	public function action($params){
		// #PADC#
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		$use_cont = (isset($params["use_cont"]) ? $params["use_cont"] : 0);

		UserContinue::ack($params["pid"], $params["rid"], $token, $use_cont);
		// #PADC# ----------end----------

		return json_encode(array('res' => RespCode::SUCCESS));
	}
}
