<?php
/**
 * #PADC#
 * Msdk server API base
 * 
 */
class Tencent_Msdk
{
	/**
	 * get APP info
	 * 
	 * @param int $platform_type
	 * @return array $appInfo array('appid' => APPID,'appkey' => APPKEY);
	 */
	public static function getAppInfo($platform_type)
	{
		// qq用
		$appkey = Env::QQ_APPKEY;
		$appid	= Env::QQ_APPID;

		// wechat用
		if($platform_type == UserDevice::PTYPE_WECHAT)
		{
			$appkey = Env::WECHAT_APPKEY;
			$appid	= Env::WECHAT_APPID;
		}
		// ゲストログイン用
		elseif($platform_type == UserDevice::PTYPE_GUEST)
		{
			$appkey = Env::GUEST_APPKEY;
			$appid	= Env::GUEST_APPID;
		}

		$appInfo = array(
			'appid' => $appid,
			'appkey' => $appkey,
		);

		return $appInfo;
	}

	/**
	 * Make request URL
	 *
	 * @param string $api
	 * @return string URL
	 */
	public static function makeUrl($api) {
		$api_url = 'http://' . Env::TENCENT_MSDK_DOMAIN . $api;
		return $api_url;
	}

	/**
	 * Make QQ request URL
	 * 
	 * @param string $api
	 * @param string $openid
	 */
	public static function makeQqApiUrl($api, $openid)
	{
		return static::makeApiUrl($api, $openid, UserDevice::PTYPE_QQ); 
	}

	/**
	 * Make WeChat request URL
	 * 
	 * @param string $api
	 * @param string $openid
	 */
	public static function makeWeChatApiUrl($api, $openid)
	{
		return static::makeApiUrl($api, $openid, UserDevice::PTYPE_WECHAT);
	}
	
	public static function makeGuestApiUrl($api, $openid){
		return static::makeApiUrl($api, $openid, UserDevice::PTYPE_GUEST);
	}

	/**
	 * Make API common URL
	 *
	 * @param string $api
	 * @param string $openid
	 * @param int $platform_type
	 * @return string Combined URL
	 */
	private static function makeApiUrl($api, $openid, $platform_type = UserDevice::PTYPE_QQ) {
		$url = static::makeUrl ( $api ) . '/?';
		
		$appInfo = static::getAppInfo($platform_type);
		$appkey = $appInfo['appkey'];
		$appid	= $appInfo['appid'];

		$timestamp = time ();
		$sig = md5 ( $appkey . $timestamp );
		
		$params = array (
			'timestamp' => $timestamp,
			'appid' => $appid,
			'sig' => $sig,
			'openid' => $openid,
			'encode' => 1
		);
		
		$valueArr = array ();
		foreach ( $params as $key => $val ) {
			$valueArr [] = "$key=$val";
		}
		
		$keyStr = implode ( "&", $valueArr );
		$url .= ($keyStr);
		
		return $url;
	}
	
	/**
	 *
	 * @param string $source        	
	 * @return string
	 */
	private function sourceEncode($source) {
		return preg_replace ( "/[^0-9a-zA-Z!\*()]/e", "'%'.dechex(ord('$0'))", $source );
	}
	
	/**
	 * Send request
	 *
	 * @param string $url
	 * @param array $param
	 * @return string
	 * @throws Exception
	 */
	public static function sendRequest($url, $param) {
		if (Env::ENV !== "production") {
			// sendRequest start
			global $logger;
			$logger->log ( 'MSDK request url:' . $url . ' param:'.json_encode ( $param ), Zend_log::INFO );
		}
		$result = SnsNetwork::makeRequest ( $url, json_encode ( $param ), '' );
		if (Env::ENV !== "production") {
			// sendRequest end
			global $logger;
			$logger->log ( 'MSDK response url:' .  $url . ' param:' . json_encode ( $param ) . ' result:'.print_r ( $result, true ), Zend_log::INFO );
		}
		
		if (! $result ['result']) {
			throw new MsdkConnectionException($result ['msg']);
		}
		$tencent_result = json_decode ( $result ['msg'], true );
		if(!isset($tencent_result ['ret'])){
			throw new MsdkConnectionException ( $result ['msg'] );
		}
		if ($tencent_result ['ret'] != 0) {
			throw new MsdkApiException($tencent_result ['ret'], $tencent_result ['msg']);
		}
		
		return $tencent_result;
	}
}
