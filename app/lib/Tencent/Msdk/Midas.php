<?php
/**
 * #PADC#
 * TencentバックグラウンドAPI、Midasクラス
 */
class Tencent_Msdk_Midas {
	const ENABLE_DEBUG_RESPONSE_3000111 = false;
	const DEBUG_RESPONSE_3000111_TIMES = -1;

	// purchase APIs
	const API_GET_BALANCE	= '/mpay/get_balance_m';	//残高取得
	const API_PAY			= '/mpay/pay_m';		//消費
	const API_CANCEL_PAY	= '/mpay/cancel_pay_m';		//消費キャンセル
	const API_QUERY_QUALIFY = '/mpay/query_qualify_m';		//課金イベント調べる
	const API_PRESENT 		= '/mpay/present_m';		//付与
	const API_SUBSCRIBE		= '/mpay/subscribe_m';		//月額課金

	private static $debugResponseCnt = 0;
	
	/**
	 * Get balance
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function getBalance($openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type) {
		return static::purchaseCommon ( self::API_GET_BALANCE, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type );
	}
	
	/**
	 * Pay gold
	 *
	 * @param number $pay_num        	
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function payGold($pay_num, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, $billno = null) {
		$ext_params = array (
				'amt' => $pay_num 
		);
		if (isset ( $billno )) {
			$ext_params ['billno'] = $billno;
		}
		return static::purchaseCommon ( self::API_PAY, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, $ext_params );
	}
	
	/**
	 * Cencel pay
	 *
	 * @param number $cancel_num        	
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function cancelPay($cancel_num, $billno, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type) {
		return static::purchaseCommon ( self::API_CANCEL_PAY, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, array (
				'amt' => $cancel_num,
				'billno' => $billno 
		) );
	}
	
	/**
	 * Query qualify
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function queryQualify($openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type) {
		return static::purchaseCommon ( self::API_QUERY_QUALIFY, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, array (
				'req_from' => 'InGame',
				'accounttype' => 'save' 
		) );
	}
	
	/**
	 *
	 * @param int $discountid        	
	 * @param int $presentid        	
	 * @param int $num        	
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function present($openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, $num, $discountid = NULL, $presentid = NULL, $billno = NULL) {
		$ext_params = array (
				'presenttimes' => $num /* present num */
		);
		if (isset ( $discountid )) {
			$ext_params ['discountid'] = $discountid; /* discount event id　 */
		}
		if (isset ( $presentid )) {
			$ext_params ['giftid'] = $presentid; /* present id　 */
		}
		if (isset ( $billno )) {
			$ext_params ['billno'] = $billno;
		}
		
		return static::purchaseCommon ( self::API_PRESENT, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, $ext_params );
	}
	
	/**
	 * 月額情報を取得
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function querySubscribe($openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type) {
		return static::purchaseCommon ( self::API_SUBSCRIBE, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, array (
				'session_id' => self::getSessionId ( $platform_type ),
				'session_type' => self::getSessionType ( $platform_type ),
				'cmd' => 'QUERY' 
		) );
	}
	
	/**
	 * 月額付与
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @param int $buy_quantity        	
	 * @return array
	 */
	public static function presentSubscribe($openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, $buy_quantity) {
		return static::purchaseCommon ( self::API_SUBSCRIBE, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $type, array (
				'session_id' => self::getSessionId ( $platform_type ),
				'session_type' => self::getSessionType ( $platform_type ),
				'cmd' => 'PRESENT',
				'tss_inner_product_id' => Env::SUBSCRIBE_SERVICE_CODE,
				'buy_quantity' => $buy_quantity 
		) );
	}
	
	/**
	 * Generate sig
	 *
	 * @param string $appkey        	
	 * @param string $uri        	
	 * @param string $param        	
	 * @return string sig
	 */
	private static function makeSig($appkey, $uri, $param) {
		$source = 'GET&' . urlencode ( $uri ) . '&';
		
		$param_arr = array ();
		foreach ( $param as $key => $val ) {
			$param_arr [] = "$key=$val";
		}
		$param_str = implode ( "&", $param_arr );
		
		$source .= urlencode ( $param_str );
		
		$sig = sha1 ( $appkey . '&' . $source );
		return $sig;
	}
	
	/**
	 * common purchase request
	 *
	 * @param string $api        	
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pay_token        	
	 * @param string $pf        	
	 * @param string $pfkey        	
	 * @param int $platform_type        	
	 * @param array $ext_params        	
	 * @return array
	 */
	private static function purchaseCommon($api, $openid, $access_token, $pay_token, $pf, $pfkey, $platform_type, $device_type, $ext_params = null) {
		$url = Tencent_Msdk::makeUrl ( $api );

		// #PADC_DY# ----------begin----------
		// $appInfo = self::getAppInfo ( $device_type );
		$appInfo = self::getAppInfoByPtypeAndDtype($platform_type, $device_type);
		// #PADC_DY# ----------end----------

		$cookie = array (
				'org_loc' => urlencode ( $api ),
				'session_id' => self::getSessionId ( $platform_type ),
				'session_type' => self::getSessionType ( $platform_type ) 
		);
		
		$params = array (
				'openid' => $openid,
				'openkey' => ($platform_type == UserDevice::PTYPE_QQ) ? $pay_token : $access_token,
				'appid' => $appInfo ['appid'],
				'ts' => time (),
				'pf' => $pf,
				'pfkey' => $pfkey,
				'zoneid' => $appInfo ['zoneid'] 
		);
		
		if ($platform_type == UserDevice::PTYPE_GUEST) {
			$params ['openkey'] = 'openkey';
		}
		
		if ($platform_type == UserDevice::PTYPE_QQ) {
			$params ['pay_token'] = $pay_token;
		}
		
		if (isset ( $ext_params )) {
			$params = array_merge ( $params, $ext_params );
		}
		
		$sig = SnsSigCheck::makeSig ( 'GET', $api, $params, $appInfo ['appkey'] . '&' );
		$params ['sig'] = $sig;
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( 'Midas request url: ' . $url . ' param:' . json_encode ( $params ), Zend_log::INFO );
		}
		
		$result = SnsNetwork::makeRequest ( $url, $params, $cookie, 'get' );
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( 'Midas response url: ' . $url . ' param:' . json_encode ( $params ) . ' result:' . print_r ( $result, true ), Zend_log::INFO );
		}
		$tencent_result = isset ( $result ['msg'] ) ? json_decode ( $result ['msg'], true ) : null;
		
		$billno = (isset ( $tencent_result ) && isset ( $tencent_result ['billno'] )) ? $tencent_result ['billno'] : null;
		$debugResponse = self::debugResponse ( $api, $billno );
		if (isset ( $debugResponse )) {
			$result = $debugResponse;
			$tencent_result = isset ( $result ['msg'] ) ? json_decode ( $result ['msg'], true ) : null;
		}
		
		if (! $result ['result']) {
			throw new MsdkConnectionException ( $result ['msg'] );
		}
		
		if (! isset ( $tencent_result ['ret'] )) {
			throw new MsdkConnectionException ( $result ['msg'] );
		}
		if ($tencent_result ['ret'] != 0) {
			throw new MsdkApiException ( $tencent_result ['ret'], isset($tencent_result['msg']) ? $tencent_result['msg'] : '', $tencent_result );
		}
		
		return $tencent_result;
	}
	
	/**
	 *
	 * @param number $platform_type        	
	 * @return string
	 */
	private static function getSessionId($platform_type) {
		if ($platform_type == UserDevice::PTYPE_QQ) {
			return 'openid';
		} else if ($platform_type == UserDevice::PTYPE_WECHAT) {
			return 'hy_gameid';
		} else if ($platform_type == UserDevice::PTYPE_GUEST) {
			return 'hy_gameid';
		}
	}
	
	/**
	 *
	 * @param number $platform_type        	
	 * @return string
	 */
	private static function getSessionType($platform_type) {
		if ($platform_type == UserDevice::PTYPE_QQ) {
			return 'kp_actoken';
		} else if ($platform_type == UserDevice::PTYPE_WECHAT) {
			return 'wc_actoken';
		} else if ($platform_type == UserDevice::PTYPE_GUEST) {
			return 'st_dummy';
		}
	}
	
	/**
	 *
	 * @param number $device_type        	
	 * @return array
	 */
	private static function getAppInfo($device_type) {
		$info = array ();
		if ($device_type == UserDevice::TYPE_IOS) {
			$info ['appid'] = Env::MIDAS_APPID_IOS;
			$info ['appkey'] = Env::MIDAS_APPKEY_IOS;
			$info ['zoneid'] = Env::MIDAS_ZONEID_IOS;
		} else {
			// android
			$info ['appid'] = Env::MIDAS_APPID_ADR;
			$info ['appkey'] = Env::MIDAS_APPKEY_ADR;
			$info ['zoneid'] = Env::MIDAS_ZONEID_ADR;
		}
		return $info;
	}
	
	/**
	 *
	 * @param string $api        	
	 * @return NULL|array
	 */
	private static function debugResponse($api, $billno = null) {
		if (self::ENABLE_DEBUG_RESPONSE_3000111) {
			// test 3000111
			if ($api == self::API_PAY || 
					$api == self::API_PRESENT || 
					$api == self::API_CANCEL_PAY) {
				if (self::DEBUG_RESPONSE_3000111_TIMES < 0 || self::$debugResponseCnt < self::DEBUG_RESPONSE_3000111_TIMES) {
					self::$debugResponseCnt ++;
					if (isset ( $billno )) {
						$result = array (
								'result' => 1,
								'msg' => json_encode ( array (
										'ret' => 3000111,
										'msg' => '请求已处理，请稍后核实物品到账情况',
										'err_code' => '3--111-0',
										'billno' => $billno 
								) ) 
						);
						return $result;
					}
				}
				else {
					return null;
				}
			}
		}
		return null;
	}

	// #PADC_DY# get ZoneId by platform type and device type
	private static function getAppInfoByPtypeAndDtype($platform_type, $device_type) {
		$info = array ();
		if ($device_type == UserDevice::TYPE_IOS) {
			$info ['appid'] = Env::MIDAS_APPID_IOS;
			$info ['appkey'] = Env::MIDAS_APPKEY_IOS;
			if ($platform_type == UserDevice::PTYPE_QQ) {
				$info ['zoneid'] = Env::MIDAS_ZONEID_IOS_QQ;
			} else if ($platform_type == UserDevice::PTYPE_WECHAT) {
				$info ['zoneid'] = Env::MIDAS_ZONEID_IOS_WX;
			} else {
				$info ['zoneid'] = Env::MIDAS_ZONEID_IOS_GUEST;
			}
		} else {
			// android
			$info ['appid'] = Env::MIDAS_APPID_ADR;
			$info ['appkey'] = Env::MIDAS_APPKEY_ADR;
			if ($platform_type == UserDevice::PTYPE_QQ) {
				$info ['zoneid'] = Env::MIDAS_ZONEID_ADR_QQ;
			} else {
				$info ['zoneid'] = Env::MIDAS_ZONEID_ADR_WX;
			}
		}
		return $info;
	}
}
