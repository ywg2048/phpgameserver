<?php


/**
 * #PADC#
 * セッションID再取得
 */
class GetSessionId extends BaseAction {

	// このアクションへのコールはログイン必要なし
	const LOGIN_REQUIRED = FALSE;

	// http://pad.localhost/api.php?action=get_session_id&pid=1
	public function action($params){

		$user_id = $params ['pid'];
		$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		$ptype = $userDeviceData['pt'];
		
		// テンセント系パラメータ
		$openid = isset($params['ten_oid']) ? $params['ten_oid'] : 0;
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		
		// openIdが一致するかチェック
		if ($userDeviceData['oid'] !== $openid) {
			throw new PadException(RespCode::USER_NOT_FOUND, "user_id and open_id is mismatch.");
		}
		
		if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$user_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}else {
			$user_ip = $_SERVER["REMOTE_ADDR"];
		}
		
		if(ENV::CHECK_TENCENT_LOGIN || $token ['check_tencent'] != null){
			if($openid && ($ptype == UserDevice::PTYPE_QQ || $ptype == UserDevice::PTYPE_WECHAT)){
				try{
					$verifyResult = Tencent_MsdkApi::verifyLogin($openid, $token['access_token'], $user_ip, $ptype);
				}catch(MsdkConnectionException $e){
					throw new PadException(RespCode::TENCENT_NETWORK_ERROR, $e->getMessage());
				}catch(MsdkApiException $e){
					throw new PadException(RespCode::TENCENT_API_ERROR, $e->getMessage());
				}
			}
		}
		
		// セッションID再取得
		// #PADC# memcache→redis
		$redis = Env::getRedisForUser();
		$sessionKey = CacheKey::getUserSessionKey($user_id);
		$sessionValue = Login::generateSessionKey();
		$redis->set($sessionKey, $sessionValue, Login::SESSION_DURATION_SEC);

		$res = array(
			'res'	=> RespCode::SUCCESS,
			'sid'	=> $sessionValue,
		);

		return json_encode($res);
	}

}
