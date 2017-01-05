<?php
/**
*从防沉迷服务器获取数据
***/
class CheckOnlineNotice{
	const LOGIN_REQUIRED = FALSE;
	const SEQ_ID = 1;
	const VERSION = "1.0";
	const APPID = "wx214189d265281f9f";//智龙迷城appid
	const PLAT_ID = 1;
	const AREA = 1;
	const URL = "http://maasapi.game.qq.com:12280/aas.fcg";

	public static function OnlineNoticeApi($params,$msg_type) {
		global $logger;
		$request_params['body_info'] = $params;
		if(!$msg_type){
				$logger->log("not set msg_type",Zend_Log::DEBUG);
				return null;
		}
		$res = self::CURL($request_params,$msg_type);

		$logger->log("res = ".json_encode($res),Zend_Log::DEBUG);
		
		return $res;
	}
	private static function CURL($request_params,$msg_type) {
		$request_params['common_msg'] = array(
					"seq_id" => self::SEQ_ID,
					"msg_type" => $msg_type,
					"version" => self::VERSION,
					"appid" => self::APPID,
					"plat_id" => self::PLAT_ID,
					"area" => self::AREA
			);

		$request_params = json_encode($request_params);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt ( $ch, CURLOPT_URL, self::URL );
		curl_setopt ( $ch, CURLOPT_POST, TRUE );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $request_params );
	
		$response = curl_exec ( $ch );
		curl_close ( $ch );
		return $response;
	}
}