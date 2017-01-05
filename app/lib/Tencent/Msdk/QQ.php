<?php
/**
 * #PADC#
 * TencentバックグラウンドAPI、QQクラス
 * 
 */
class Tencent_Msdk_QQ {
	const QQ_SCORE_LEVEL = 1;
	const QQ_SCORE_MONEY = 2;
	const QQ_SCORE_RANKING = 3;
	const QQ_SCORE_LOGIN_TIME = 8;
	const QQ_SCORE_DEVICE_TYPE = 12;
	const QQ_SCORE_TEAM_POWER = 17;
	const QQ_SCORE_SIGNUP_TIME = 25;
	const QQ_SCORE_AREA_TYPE = 26;
	const QQ_SCORE_PARTITION_TYPE = 27;
	const QQ_SCORE_ROLE_TYPE = 28;
	const QQ_SCORE_PURCHASE = 32;
	const QQ_SCORE_VIP_LEVEL = 45;
	const QQ_SCORE_CARDS = 1001;
	const QQ_SCORE_PRIVILEDGE_STARTUP = 1002;
	/* #PADC_DY#
	 * 葫芦娃IP相关
	const QQ_SCORE_IP_HARD = 1004;
	const QQ_SCORE_IP_NORMAL = 1005;
	const QQ_SCORE_IP_EASY = 1006;
	const QQ_SCORE_IP_GACHA_SINGLE = 1007;
	const QQ_SCORE_IP_GACHA_TEN = 1008;
	*/

	/* #PADC_DY#
	 * 死神IP相关

	const QQ_SCORE_IP_SUPERHELL = 1014;
	const QQ_SCORE_IP_HELL = 1015;
	const QQ_SCORE_IP_HARD = 1016;
	const QQ_SCORE_IP_NORMAL = 1017;
	const QQ_SCORE_IP_EASY = 1018;
	const QQ_SCORE_IP_GACHA_SINGLE = 1012;
	const QQ_SCORE_IP_GACHA_TEN = 1013;
	*/

	/**#PADC_DY#
	 * 尸兄ip相关
	 */
	const QQ_SCORE_IP_HELL = 1022;
	const QQ_SCORE_IP_HARD = 1023;
	const QQ_SCORE_IP_NORMAL = 1024;
	const QQ_SCORE_IP_EASY = 1025;
	const QQ_SCORE_IP_GACHA_SINGLE = 1020;
	const QQ_SCORE_IP_GACHA_TEN = 1021;




	const VIP_NORMAL = 1;
	const VIP_BLUE = 4;
	const VIP_RED = 8;
	const VIP_SUPER = 16;
	const VIP_GAME = 32;
	const VIP_XINYUE = 64;
	const VIP_YELOW = 128;
	
	const QQ_FRIENDS_VIP_LIMIT = 50;//一回通信の最大数
	
	// QQ APIs
	const API_QQ_VERIFY_LOGIN	= '/auth/verify_login';		//ユーザーログインチェック
	const API_QQ_SHARE			= '/share/qq';		//シェア
	const API_QQ_PROFILE		= '/relation/qqprofile';	//QQユーザー情報
	const API_QQ_FRIENDS_DETAIL	= '/relation/qqfriends_detail';		//QQフレンド情報
	const API_QQ_LOAD_VIP		= '/profile/load_vip';		//QQVIP情報
	const API_QQ_FRIENDS_VIP	= '/relation/qqfriends_vip';		//QQフレンドVIP情報
	const API_QQ_SCORE_UPLOAD	= '/profile/qqscore_batch';		//QQフレンドランキングスコアアップロード
	const API_QQ_VIP_RICH_INFO	= '/relation/get_vip_rich_info';	//QQVIP情報
	
	/**
	 * Verify QQ user login status
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $userip        	
	 * @return array
	 */
	public static function verifyQqLogin($openid, $access_token, $userip) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_VERIFY_LOGIN, $openid );
		$param = array (
				'appid' => ENV::QQ_APPID,
				'openid' => $openid,
				'openkey' => $access_token,
				'userip' => $userip 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * Share to QQ friend
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param array $share_data
	 *        	(
	 *        	* image_url string URL of share image, size 128*128, < 2M.
	 *        	* summary string Less than 45 Byte.
	 *        	* title string Less than 45 Byte.
	 *        	* target_url string "http://gamecenter.qq.com/gcjump?appid={YOUR_APPID}&pf=invite&from=iphoneqq&plat=qq&originuin=111&ADTAG=gameobj.msg_invite".
	 *        	* fopenids json array [{"openid":"","type":0}] openid: friend's openid, type: fixed 0, supports only share to 1 friend.
	 *        	* previewText string Share text content.Not necessary. Less than 45 Byte.
	 *        	)
	 * @return array
	 */
	public static function shareQQ($openid, $access_token, $share_data) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_SHARE, $openid );
		$param = array (
				'act' => 0, // 0:url jump, 1: APP jump
				'oauth_consumer_key' => ENV::QQ_APPID, // appid
				'dst' => 1001, // currently only fill 1001
				'flag' => 1, // Roaming, 0:y 1:n, currently only fill 1
				'openid' => $openid,
				'access_token' => $access_token,
				'src' => 0, // default 0
				'appid' => ENV::QQ_APPID,/*appid, same as oauth_consumer_key*/
		);
		$param = array_merge ( $share_data, $param );
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * Get QQ user profile
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 */
	public static function getQQProfile($openid, $access_token) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_PROFILE, $openid );
		$param = array (
				'appid' => ENV::QQ_APPID,
				'accessToken' => $access_token,
				'openid' => $openid 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * Get QQ friends infomation
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pf        	
	 * @return array
	 */
	public static function getQQFriendsDetail($openid, $access_token, $pf) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_FRIENDS_DETAIL, $openid );
		$param = array (
				'appid' => ENV::QQ_APPID,
				'accessToken' => $access_token,
				'openid' => $openid,
				'flag' => 1, /* 1: not include myself. 2: include */
		        'nonick' => 0, // 1: empty nickname
				'nogender' => 0, // 1: empty gender
				'nofigure' => 0, // 1: empty figure
				'pf' => $pf /* platform */
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * Get QQ user VIP infomation
	 *
	 * @deprecated
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $vip
	 *        	1(bit1):会员 4(bit3):蓝钻 8(bit4):红钻 16(bit5):超级会员 32(bit6):游戏会员
	 * @return array
	 */
	public static function getQQVip($openid, $access_token, $vip) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_LOAD_VIP, $openid );
		$param = array (
				'appid' => ENV::QQ_APPID,
				'login' => 2, // login type, default 2
				'uin' => 0, // user id type, 0 if use openid
				'openid' => $openid,
				'vip' => $vip 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * Get QQ friends VIP infomation
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $pf        	
	 * @param array $fopenids
	 *        	Array of friends openid. e.g. ['openid1', 'openid2']
	 * @param string $userip        	
	 * @return array
	 */
	
	public static function getQQFriendsVip($openid, $access_token, $fopenids, $userip) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_FRIENDS_VIP, $openid );
		$param = array (
				'appid' => ENV::QQ_APPID,
				'openid' => $openid,
				'accessToken' => $access_token,
				'fopenids' => $fopenids,
				'flags' => 'qq_vip,qq_svip',
				'userip' => $userip,
				'pf' => 'openmobile' 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * upload score to qq
	 *
	 * @param string $openid        	
	 * @param string $accessToken        	
	 * @param array $params        	
	 * @return array(vector) *type:1:LEVEL（等级），
	 *         *2:MONEY（金钱）,
	 *         *3:SCORE（得分）,
	 *         *4:EXP（经验）,
	 *         *5:HST_SCORE(历史最高分)
	 */
	public static function qqScoreBatch($openid, $accessToken, $params) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_SCORE_UPLOAD, $openid );
		$params = array_merge(array(
			array(
				'type' => self::QQ_SCORE_AREA_TYPE,
				'bcover' => 1,
				'data' => '2',
				'expires' => '0'
			),
			array(
				'type' => self::QQ_SCORE_PARTITION_TYPE,
				'bcover' => 1,
				'data' => '1',
				'expires' => '0'
			),
			array(
				'type' => self::QQ_SCORE_ROLE_TYPE,
				'bcover' => 1,
				'data' => '1',
				'expires' => '0'
			)
		), $params);
		$param = array (
				'appid' => ENV::QQ_APPID,
				'accessToken' => $accessToken,
				'openid' => $openid,
				'param' => $params 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param string $accessToken        	
	 * @return array
	 */
	public static function getVipRichInfo($openid, $accessToken) {
		$url = Tencent_Msdk::makeQqApiUrl ( self::API_QQ_VIP_RICH_INFO, $openid );
		
		$param = array (
				'appid' => ENV::QQ_APPID,
				'openid' => $openid,
				'accessToken' => $accessToken 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
}
