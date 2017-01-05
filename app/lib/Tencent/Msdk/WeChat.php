<?php
/**
 * #PADC#
 * TencentバックグラウンドAPI、WeChatクラス
 *
 */
class Tencent_Msdk_WeChat {
	// WeChat APIs
	const API_WECHAT_CHECK_TOKEN				= '/auth/check_token';	//WeChatトーケンチェック
	const API_WECHAT_UPLOAD_WX					= '/share/upload_wx';	//WeChat画像アップロード
	const API_WECHAT_SHARE_WX					= '/share/wx';		//Wechatシェア
	const API_WECHAT_SHARE_WXGAME				= '/share/wxgame';		//Wechatゲームにシェア
	const API_WECAHT_RELATION_WXFRIENDS_PROFILE	= '/relation/wxfriends_profile';	//Wechatフレンド情報
	const API_WECHAT_RELATION_WXFRIENDS			= '/relation/wxfriends';		//Wechatフレンドリスト
	const API_WECHAT_RELATION_WXUSERINFO		= '/relation/wxuserinfo';		//Wechatユーザー情報
	const API_WECHAT_PROFILE_WXSCORE			= '/profile/wxscore';//Deprecated
	const API_WECHAT_PROFILE_WXBATTLE_REPORT	= '/profile/wxbattle_report';		//Wechatスコアアップロード
	
	/**
	 * Check WeChat token
	 *
	 * @param string $open_id        	
	 * @param string $access_token        	
	 * @return array
	 */
	public static function checkWeChatToken($open_id, $access_token) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_CHECK_TOKEN, $open_id );
		$param = array (
				'accessToken' => $access_token,
				'openid' => $open_id 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * upload a picture for share and to get media_id
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $picture_path        	
	 * @return array:
	 */
	public static function upload_wx($openid, $access_token, $picture_path, $content_type = 'image/jpeg') {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_UPLOAD_WX, $openid );
		$handle = fopen ( $picture_path, 'r' );
		$filename = pathinfo ( $picture_path, PATHINFO_BASENAME );
		$filelength = filesize ( $picture_path );
		// $content_type = pathinfo ( $picture_path, PATHINFO_EXTENSION );
		$content = fread ( $handle, $filelength );
		$binary = rawurlencode ( $content );
		$param = array (
				'flag' => 1,
				'appid' => Env::WECHAT_APPID,
				'secret' => Env::WECHAT_APPKEY,
				'access_token' => "",
				'type' => "thumb",
				'filename' => $filename,
				'filelength' => $filelength,
				'content_type' => $content_type,
				'binary' => $binary 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	/**
	 * share the game message to the wechat friend
	 *
	 * @param string $openid        	
	 * @param string $fopenid        	
	 * @param string $access_token        	
	 * @param string $picture_url        	
	 * @return array
	 */
	public static function shareWeChat($openid, $fopenid, $access_token, $picture_url) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_SHARE_WX, $openid );
		$extinfo = "this is a extinfo information";
		$title = "this is a title";
		$description = "this is a description";
		$media_tag_name = "friend share";
		$tencent_server_return = json_decode ( static::upload_wx ( $openid, $access_token, $picture_url ) );
		$thumb_media_id = $tencent_sever_retrun->media_id;
		
		$param = array (
				'openid' => $openid,
				'fopenid' => $fopenid,
				'access_token' => $access_token,
				'extinfo' => $extinfo,
				'title' => $title,
				'description' => $description,
				'media_tag_name' => $media_tag_name,
				'thumb_media_id' => $thumb_media_id 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	/**
	 * for developer to share only one account
	 *
	 * @param string $openid        	
	 * @param string $access_token        	
	 * @param string $touser        	
	 * @param string $msgtype        	
	 * @param string $title        	
	 * @param string $content        	
	 * @param struct $button        	
	 * @param string $url        	
	 * @return array
	 */
	public static function sharewxganme($openid, $access_token, $touser, $msgtype, $title, $content, $button, $pictrue_url = null) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_SHARE_WXGAME, $openid );
		$appid = Env::WECHAT_APPID;
		$type_info = "";
		
		$param = array (
				'openid' => $openid,
				'appid' => $appid,
				'access_token' => $access_token,
				'touser' => $touser,
				'msgtype' => $msgtype,
				'title' => $title,
				'content' => $content,
				'type_info' => $type_info,
				'button' => $button 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	/**
	 * get wechat user or friend profile
	 *
	 * @param string $accessToken        	
	 * @param string $openid        	
	 * @return array
	 */
	public static function getwxprofile($accessToken, $openid) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECAHT_RELATION_WXFRIENDS_PROFILE, $openid );
		
		$param = array (
				'accessToken' => $accessToken,
				'openid' => $openid 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	/**
	 * get the base profiles of the wechat friends(精品游戏使用)
	 *
	 * @param string $openid        	
	 * @param string $accessToken        	
	 * @return array
	 */
	public static function getwxfriends($openid, $accessToken) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_RELATION_WXFRIENDS, $openid );
		
		$param = array (
				'openid' => $openid,
				'accessToken' => $accessToken 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	/**
	 * get the base profiles of the wechat friends(非精品游戏使用)
	 *
	 * @param string $openid        	
	 * @param string $accessToken        	
	 * @return array
	 */
	public static function getwxuserinfo($openid, $accessToken) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_RELATION_WXUSERINFO, $openid );
		$appid = Env::WECHAT_APPID;
		
		$param = array (
				'appid' => $appid,
				'openid' => $openid,
				'accessToken' => $accessToken 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 * upload score and compare with friends
	 *
	 * @deprecated
	 *
	 * @param string $openid        	
	 * @param string $score        	
	 * @return array
	 */
	public static function uploadwxscore($openid, $score) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_PROFILE_WXSCORE, $openid );
		$appid = Env::WECHAT_APPID;
		$grantType = 'client_credential';
		$expires = '3600';
		
		$param = array (
				'appid' => $appid,
				'grantType' => $grantType,
				'openid' => $openid,
				'score' => ''.$score,
				'expires' => $expires 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $score        	
	 * @return array
	 */
	public static function wxBattleReport($openid, $score) {
		$url = Tencent_Msdk::makeWeChatApiUrl ( self::API_WECHAT_PROFILE_WXBATTLE_REPORT, $openid );
		$appid = Env::WECHAT_APPID;
		
		$param = array (
				'appid' => Env::WECHAT_APPID,
				'openid' => $openid,
				'grantType' => 'client_credential',
				'score' => ''.$score,
				'expires' => '0' 
		);
		
		return Tencent_Msdk::sendRequest ( $url, $param );
	}
}
