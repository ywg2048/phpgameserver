<?php
/**
 * Admin用：月額付与
 */
class AdminAddUserSubscription extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id ); // 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$tss = $params ['tss'];
		$token = Tencent_MsdkApi::checkToken ( $params );
		
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		$user = User::find ( $user_id, $pdo, TRUE );
		
		if (($user->device_type == UserDevice::TYPE_ADR || Env::ENABLE_IOS_MIDAS) && Env::CHECK_TENCENT_TOKEN) {
			$present_subscribe_result = Tencent_MsdkApi::presentSubscribe ( $tss, $openid, $token, $ptype );
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
}
