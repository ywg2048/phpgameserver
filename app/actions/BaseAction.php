<?php
/**
 * アクションクラスのベースクラス.
 */
abstract class BaseAction
{
	const LOGIN_REQUIRED = TRUE;// ログインが必要かどうか
	// #PADC# ----------begin----------
	const MAIL_RESPONSE		= TRUE;// レスポンスに新規メール受信数をセットするかどうか
	const ENCRYPT_RESPONSE	= TRUE;// レスポンスを暗号化するかどうか
	// #PADC# ----------end----------

	public $post_params;
	public $decode_params;

	public function process($get_params, $request_uri, $post_params = null)
	{
		if(Env::ENV !== "production"){
			global $timer;
			$timer->setMarker('handling request');
		}
		if($this->isValidRequest($get_params, $request_uri) === TRUE || Env::CHECK_REQ_SUM === FALSE){
			$this->post_params = $post_params;
			$this->decode_params = array();
			if(isset($get_params['e'])){
				$this->decode_params = Cipher::decode($get_params['e']);
			}
			// #PADC# ----------begin----------
			if(Env::CHECK_LOGIN && static::LOGIN_REQUIRED){
				$code_check_loggedin = $this->checkLoggedIn($get_params['pid'], $get_params['sid']);
				if($code_check_loggedin != RespCode::SUCCESS){
					if($code_check_loggedin == RespCode::KICK_OFF){
						throw new PadException($code_check_loggedin, "kick off. __NO_LOG");
					}else if(Env::ENV !== "production") {
						global $logger;
						$logger->log("SESSION ERROR! ".$code_check_loggedin." pid=".$get_params['pid']." action=".$get_params['action'], Zend_Log::INFO);
						throw new PadException($code_check_loggedin, "session error. __NO_LOG");
					}
				}
			}
			// #PADC# ----------end----------
			$api_revision = isset($get_params['r']) ? (int)$get_params['r'] : 0;
			Env::setRev($api_revision);

			// #PADC# ----------begin----------
			//Tencent_Tlog::init(Env::TLOG_GAMESVR_ID, Env::TLOG_ZONEID);
			Tencent_Tlog::init($_SERVER["SERVER_ADDR"], Env::TLOG_ZONEID);
			if(Env::TLOG_SERVER != null && Env::TLOG_PORT != null){
				Tencent_Tlog::setServer(Env::TLOG_SERVER, Env::TLOG_PORT);
			}
			// #PADC# ----------end----------

			if(Env::ENV !== "production") {
				global $logger;
				$debug_print = "\n** REQUEST PARAM **\n";
				$debug_print .= "get_params :\n";
				foreach($get_params as $key => $val){
					$debug_print .= "   [$key] => $val\n";
				}
				if(count($post_params) > 0){
					$debug_print .= "post_params :\n";
					foreach($post_params as $key => $val){
						$debug_print .= "   [$key] => $val\n";
					}
				}
				if(count($this->decode_params) > 0){
					$debug_print .= "decode_params :\n";
					foreach($this->decode_params as $key => $val){
						$debug_print .= "   [$key] => $val\n";
					}
				}
				$logger->log($debug_print, Zend_Log::INFO);
			}
			// #PADC# ----------begin----------
			// アプリのバージョンチェック処理を追加
			if ($this->isAppliVersionError($get_params)) {
				$res = array(
					'res' => RespCode::APP_VERSION_ERR,
					'msg' => GameConstant::getParam("UpdateMessage"),
				);
				$response = json_encode($res);
			}
			else {
				$response = $this->action($get_params);
			}
			// #PADC# ----------end----------
					
			// #PADC# ----------begin----------
			//Tlog送信
			Padc_Log_Log::sendTlogHistory();
			
			if(static::MAIL_RESPONSE)
			{
				// メールの受信数をレスポンスに追加（アプリ側で新規受信時にマーカーを表示するため）
				if(isset($get_params["pid"]) && $get_params["pid"] != 0)
				{
					$response_decode = (array)json_decode($response,TRUE);
					if(array_key_exists('mails',$response_decode) == FALSE)
					{
						$pdo = Env::getDbConnectionForUserRead($get_params["pid"]);
						$mails = User::getMailCount($get_params["pid"], User::MODE_NORMAL, $pdo, TRUE);
						$response_decode['mails'] = $mails;
						$response = json_encode($response_decode);
					}
				}
			}
			// #PADC# ----------end----------
			if(Env::ENV !== "production"){
				global $timer;
				$timer->setMarker('called action');
			}
		}else{
			throw new PadException(RespCode::INVALID_REQUEST_CHECKSUM);
		}
		if(Env::ENV !== "production"){
			global $timer;
			$timer->stop();
			$profiling = $timer->getProfiling();
			global $logger;
			$action_name = $_GET['action'];;
			foreach($profiling as $p){
//				$logger->log($action_name . ' ' . $p['name'] . ' ' . $p['time'] . ' ' . $p['diff'] . ' ' . $p['total'], Zend_Log::INFO);
				$logger->log($action_name . '  ' . substr($p['name'].str_repeat(' ',20),0,18) . '  ' . round($p['diff'], 4) . '  ' . round($p['total'], 4), Zend_Log::INFO);
			}
			// var_dump($profiling);
			if(substr($response, 0, 2) !== "\x1f\x8b"){
				$logger->log(mb_substr($response, 0, 4096), Zend_Log::INFO);
			}
		}
		return $response;
	}

	/**
	 * action base
	 * @param array $get_params
	 * @throws Exception
	 */
	public function action($get_params){
		throw new Exception('Unimplemented action');
	}

	/**
	 * login check
	 * @param int $pid
	 * @param int $sid
	 * @return boolean
	 */
	private function isLoggedIn($pid, $sid){
		// #PADC# memcache→redis
		$rRedis = Env::getRedisForUserRead();
		$sessionKey = CacheKey::getUserSessionKey($pid);
		$sessionValue = $rRedis->get($sessionKey);
		return ($sessionValue && ($sid === $sessionValue));
	}

	/**
	 * #PADC#
	 * login check
	 * セッションエラーの場合
	 * 2:セッションID見つからない or 2008:セッションIDが異なる のエラー番号を返します
	 *
	 * @param int $pid
	 * @param int $sid
	 * @return RespCode
	 */
	private function checkLoggedIn($pid, $sid){
		// #PADC# memcache→redis
		$rRedis = Env::getRedisForUserRead();
		$sessionKey = CacheKey::getUserSessionKey($pid);
		$sessionValue = $rRedis->get($sessionKey);
		if ($sessionValue) {
			if($sessionValue == 'KICK_OFF'){
				$rRedis->delete($sessionKey);
				return RespCode::KICK_OFF;
			}
			else if ($sid === $sessionValue) {
				return RespCode::SUCCESS;
			}
			else {
				return RespCode::SESSION_ERROR_DIFFERENT;
			}
		}
		else {
			return RespCode::SESSION_ERROR;
		}
	}

	/**
	 * request check
	 * @param array $get_params
	 * @param string $request_uri
	 * @return boolean
	 */
	private function isValidRequest($get_params, $request_uri){
		$result = FALSE;
		$key = $get_params['key'];
		$i = strpos($request_uri, '?') + 1;
		$e = strrpos($request_uri, '&');
		$args = (substr($request_uri, $i, $e - $i));
		$r = isset($get_params['r']) ? $get_params['r'] : 0;
		// APIリビジョン3から新暗号化ロジック.キーはリージョン別に.
		if(Env::REGION == "NA"){
			$pad_key_maker = new padKeyMakerVer2NA();
		}elseif(Env::REGION == "KR"){
			$pad_key_maker = new padKeyMakerVer2KR();
		}elseif(Env::REGION == "EU"){
			$pad_key_maker = new padKeyMakerVer2EU();
		}else{
			$pad_key_maker = new padKeyMakerVer2();
		}
		if(Env::ENABLED_OS == 'ios'){
			$result = $pad_key_maker->isKeyMatchedComplex($args, $key);
		}elseif(Env::ENABLED_OS == 'android'){
			$result = $pad_key_maker->isKeyMatchedAndroid($args, $key);
		}elseif(Env::ENABLED_OS == 'amazon'){//場合分けはしたが内部処理はAndroidと同一
			$result = $pad_key_maker->isKeyMatchedAndroid($args, $key);
		}else{
			$result = FALSE;
		}
		return $result;
	}

	public function createDummyDataForUser($user, $pdo) {
		// 何もしない. 子クラスごとに実装されていればそれを実行する.
	}
	
	/**
	 * #PADC#
	 * アプリバージョンチェック
	 */
	private function isAppliVersionError($params) {
		if(isset($params['appv'])) {
			// xx.xx.xx のフォーマットになっていない場合はエラーとする
			$appv_array = explode('.', $params['appv'], 3);
			if (count($appv_array) < 3) {
				Padc_Log_Log::writeLog('appli version error. [format is wrong] appv:'.$params['appv'], Zend_Log::DEBUG);
				return true;
			}
			list($main, $minor, $revision) = $appv_array;
			
			// メジャー、マイナー、リビジョンの各情報が数値であることを前提とする
			if (!ctype_digit($main) || !ctype_digit($minor) || !ctype_digit($revision)) {
				Padc_Log_Log::writeLog('appli version error. [format is wrong] appv:'.$params['appv'], Zend_Log::DEBUG);
				return true;
			}
	
			// アプリバージョン管理テーブルから現バージョン数値を取得
			$app_version = AppliVersion::getActiveAppliVersion();
			if (!$app_version) {
				Padc_Log_Log::writeLog('appli version error. [appli version databese is empty]', Zend_Log::DEBUG);
				return true;
			}
	
			$version_error = false;
			if ($main < $app_version->main) {
				$version_error = true;
			}
			else if ($main == $app_version->main) {
				if ($minor < $app_version->minor) {
					$version_error = true;
				}
				else if ($minor == $app_version->minor) {
					if ($revision < $app_version->revision) {
						$version_error = true;
					}
				}
			}
	
			if ($version_error) {
				Padc_Log_Log::writeLog('appli version error. [old version] appv:'.$params['appv'].' < '.$app_version->main.'.'.$app_version->minor.'.'.$app_version->revision, Zend_Log::DEBUG);
				return true;
			}
	
			return false;
		}
		else {
			Padc_Log_Log::writeLog('appli version error. [param appv is none]', Zend_Log::DEBUG);
			return true;
		}
	}
	
}

class padKeyMakerVer2 {
	public function isKeyMatchedComplex($args, $key) {
		return true;
	}
	public function isKeyMatchedAndroid($args, $key) {
		return true;
	}
}

