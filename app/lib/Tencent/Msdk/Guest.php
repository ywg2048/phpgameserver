<?php
/**
 * #PADC#
* TencentバックグラウンドAPI、ゲスト
*
*/
class Tencent_Msdk_Guest
{
	// API
	const API_GUEST_CHECK_TOKEN	= '/auth/guest_check_token';	//チェックトーケン

	public static function verifyGuestLogin($openid, $access_token) {
		$url = Tencent_Msdk::makeGuestApiUrl ( self::API_GUEST_CHECK_TOKEN, $openid );
		$param = array (
				'guestid' => $openid,
				'accessToken' => $access_token,
		);

		return Tencent_Msdk::sendRequest ( $url, $param );
	}
}
