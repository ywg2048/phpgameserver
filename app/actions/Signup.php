<?php
/**
 * ユーザ登録アクション
 */
class Signup extends BaseAction
{
	// このアクションへのコールはログイン必要なし
	const LOGIN_REQUIRED = FALSE;

	// http://pad.localhost/api.php?action=signup&t=0&u=UUID&v=1.0.0&n=tony&a=1
	public function action($params)
	{
		$type		= $params['t'];// クライアントタイプ
		$uuid		= $params['u'];// uuid
		$version	= $params['v'];
		$name		= $params['n'];
		$camp		= $params['a'];// 初期モンスター種類
		$w_mode		= isset($params['m']) ? $params['m'] : User::MODE_NORMAL;

		// #PADC# ----------begin----------
		$openid			= isset($params['ten_oid']) ? $params['ten_oid'] : 0;// テンセントopenID
		$ptype			= $params['pt'];// プラットフォームタイプ
		$access_token	= isset($params['ten_at']) ? $params['ten_at'] : 0;// accessToken
		$ip_addr		= $_SERVER["REMOTE_ADDR"];
		$reg_channel_id	= isset($params['ten_cid']) ? $params['ten_cid'] : 0;
		$device_id		= isset($params['ten_did']) ? $params['ten_did'] : null;
		$tutorial_ver	= isset($params['tuto_v']) ? $params['tuto_v'] : 0;// チュートリアルのバージョンを返すかどうか

		// 登録上限チェック
		if(Env::CHECK_SIGNUP_LIMIT)
		{
			$redis = Env::getRedisForShare();
			SignupLimit::checkSignupLimit(Padc_Time_Time::getDate("Y-m-d"),$redis,$openid);
		}
		// #PADC# ----------end----------

		$name = trim($name);
		if(strlen($name) == 0){
			throw new PadException(RespCode::INVALID_NAME);
		}
		// #PADC# ----------begin----------
		if($word = NgWord::checkNGWords($name)){
			return json_encode(array('res'=>RespCode::NGWORD_ERROR,'ngword'=>$word));
		}
		// #PADC# ----------end----------

		$pdo_share = Env::getDbConnectionForShare();

		// #PADC# ----------begin----------


		// openIDがある場合
		if($openid)
		{
			// verify user
			if(ENV::CHECK_TENCENT_LOGIN || isset($params['check_tencent'])){
				try{
					$verifyResult = Tencent_MsdkApi::verifyLogin($openid, $access_token, $ip_addr, $ptype);
				}catch(MsdkConnectionException $e){
					throw new PadException(RespCode::TENCENT_NETWORK_ERROR, $e->getMessage());
				}catch(MsdkApiException $e){
					throw new PadException(RespCode::TENCENT_API_ERROR, $e->getMessage());
				}
			}

			// DBからopenIDとクライアントタイプで検索
			$userDevice = UserDevice::findBy(array('type' => $type, 'oid' => $openid), $pdo_share);
		}
		// openIDがない場合
		else
		{
			// androidだったらエラー
			if($type == User::TYPE_ANDROID)
			{
				throw new PadException(RespCode::UNKNOWN_ERROR,'no open_id');
			}

			// uuidとクライアントタイプで検索
			$userDevice = UserDevice::findBy(array('type' => $type, 'uuid' => $uuid), $pdo_share);
		}
		// #PADC# ----------end----------

		// データがなければ新規登録
		if($userDevice === FALSE)
		{
			$userDevice = new UserDevice();
			$userDevice->type = $type;
			$userDevice->uuid = $uuid;
			$userDevice->version = $version;
			// #PADC# ----------begin----------
			$userDevice->oid = $openid;
			$userDevice->ptype = $ptype;
			// #PADC# ----------end----------

			$user = new User();
			$user->signup($userDevice, $name, $camp, $w_mode);
			//if true,apply inheritance to it
			// #PADC# ----------begin----------
			if($inheritance = UserDevicesInherit::getInheritance($type,$openid)){
				$pdo = Env::getDbConnectionForUserWrite($user->id);
				//use find method to get user more properties
				$user = User::find($user->id,$pdo,true);
				$user->vip_lv = $inheritance->vip_lv;
				$user->tp_gold = $inheritance->tp_gold;
				$user->tss_end = $inheritance->tss_end;
				$user->update($pdo);
				$inheritance->unSetInheritance();
			}
			// #PADC# ----------end----------
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];
			} else {
				$ip_addr = $_SERVER["REMOTE_ADDR"];
			}

			// IP毎のカウンタをデクリメントする（イベント等で同一のアクセスポイントからアクセスが来た場合等の考慮）
			AccessBlockLogData::decrement($ip_addr);

			// #PADC# save to cache ----------begin----------
			$redis = Env::getRedisForUser();
			$idkey = CacheKey::getUserIdFromUserDeviceKey($type, $uuid, $openid);
			$redis->set($idkey, $user->id, UserDevice::MEMCACHED_EXPIRE);

			// ユーザIDをキーとしてユーザ情報をredisに保存
			$userDeviceDataKey = CacheKey::getUserDeviceByUserId($user->id);
			UserDevice::setUserDeviceToRedis($redis, $userDeviceDataKey, $type, $openid, $ptype, $version);

			// Tlog
			Padc_Log_Log::writePlayerRegister($openid, $type, $ptype, $reg_channel_id,$device_id);
			
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_SIGNUP, array('access_token' => $access_token));

			// #PADC# レスポンスデータ調整
			$response = array(
				'res' => RespCode::SUCCESS,
			);
			// #PADC# Tutorialのバージョンを返す
			if($tutorial_ver == 1)
			{
				$response['padc_tver'] = Tutorial::VERSION;
			}
			
			// #PADC# ----------end----------

			return json_encode($response);
		}else{
			throw new PadException(RespCode::USER_ALREADY_EXISTS);
		}
	}
}
