<?php
class AdminUploadWx extends AdminBaseAction {
	public function action($params) {
		$openid = $params ['ten_oid'];
		$access_token = $params ['ten_at'];
		
		$picture_path = ROOT_DIR . '/images/icon.png';
		$result = Tencent_Msdk_WeChat::upload_wx($openid, $access_token, $picture_path);
		
		return json_encode ( array('res' => 0, 'media_id' => $result['media_id']));
	}
}