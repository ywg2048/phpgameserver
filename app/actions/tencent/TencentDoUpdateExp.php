<?php
/**
 * Tencent用：
 */
class TencentDoUpdateExp extends TencentBaseAction {
	public function action($params) {
		$type = $params ['PlatId'];
		$openid = $params ['OpenId'];
		$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		
		$result = array (
				'res' => 0,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success",
				'BeginValue' => 0,
				'EndValue' => 0 
		);
		
		return json_encode ( $result );
	}
}