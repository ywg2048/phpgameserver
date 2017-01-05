<?php

// PHPファイル群をロード.
//require_once ("../app/config/load.php");
require_once ("../app/config/autoload.php");

setEnvironment($_SERVER["SERVER_NAME"],$_SERVER["SERVER_PORT"]);

define("LOCK_SEC", 90);

function underscore2Camel($str) {
	$words = explode('_', strtolower($str));
	$return = '';
	foreach ($words as $word) {
		$return .= ucfirst(trim($word));
	}
	return $return;
}

$response = "";
$error_msg = NULL;

// #PADC# memcache → redis
$redis = Env::getRedisForUser();

try
{
	if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else {
		$ip_addr = $_SERVER["REMOTE_ADDR"];
	}

/*
	$access_block = $redis->get(CacheKey::getAccessBlock($ip_addr));
	if($access_block) {
		// 不正アクセスの疑い
		throw new PadException(RespCode::UNKNOWN_ERROR,
							'同一IPによる不正アクセスの疑いを検知しブロックしました');
	}
*/

/*
	if(isset($_GET['m']) && $_GET['m'] == 1){
		// W緊急メンテ用.普段はコメント
		echo '{"res":99}';
		exit;
	}
*/
	// #PADC# リクエストパラメータをgetでもpostでも受けれるようにマージ
	$_GET = array_merge($_GET,$_POST);

	// #PADC# ----------begin----------
	// 暗号化されたパラメータをパース
	if(!isset($_GET['param']))
	{
		throw new PadException(RespCode::UNKNOWN_ERROR,'invalid param');
	}
	$_GET = getDecodeParam($_GET['param']);
	// #PADC# ----------end----------

	// メンテナンスチェック
	checkMaintenance($_GET);

	if(!isset($_GET['action'])){
		throw new PadException(RespCode::UNKNOWN_ERROR);
	}
	$actionName = $_GET['action'];
	$actionName = underscore2Camel($actionName);
	if(!class_exists($actionName)){
		throw new PadException(RespCode::UNKNOWN_ERROR);
	}
	$action = new $actionName();

	if(Env::ENV == "production"){
		if(isset($_GET['pid'])) {
			$pid =  $_GET['pid'];
			$lock_key = CacheKey::getUserLockApiKey($pid);
			if($redis->get($lock_key)){
				// 同じユーザで同時にapiが実行されたときはエラーを返す
				unset($lock_key);
				throw new PadException(RespCode::UNKNOWN_ERROR, "pid:".$pid." action:".$actionName." error lock 90sec. __NO_TRACE");
			}else{
				// api実行中はロックをセット
				$redis->set($lock_key, 1, LOCK_SEC);
			}
		}
	}

	$response = $action->process($_GET, $_SERVER['REQUEST_URI'], $_POST);
}catch(Exception $e){
	if ( $e instanceof PadException ){
		$code = $e->getCode();

		// #PADC# ----------begin----------
		$response = array('res'=>$code);
		if($code == RespCode::KICK_OFF)
		{
			// レスポンス文言調整
			$response = array(
				'res'=>$code,
				'msg'=> '您已被踢下线，请重新登录。',
			);
		}
		$response = json_encode($response);
		// #PADC# ----------end----------

//		if($code == RespCode::USER_NOT_FOUND || $code == RespCode::INVALID_REQUEST_CHECKSUM) {
		if($code == RespCode::INVALID_REQUEST_CHECKSUM) {
			// ブロックカウンターをインクリメントする
			AccessBlockLogData::increment($ip_addr, $_SERVER, $redis);
		}
	}else{
		$response = json_encode(array('res'=>RespCode::UNKNOWN_ERROR));
	}
	global $logger;
	$error = print_r($e, true);
	if ( strpos( $error, '*RECURSION*' ) === FALSE ) {
		$error = var_export($e, true);
	}
	$error_msg = $e->getMessage();

	if ( isset( $logger ) ) {
		if(mb_strpos($error_msg, " __NO_TRACE") !== false){
			$error_msg = str_replace(" __NO_TRACE", "", $error_msg);
			$logger->log("RespCode:" . $e->getCode() . " " . $e->getFile() . "(" . $e->getLine() . ") 'message' => '" . $error_msg . "'", Zend_Log::NOTICE);
		}elseif(mb_strpos($error_msg, " __NO_LOG") === false){
			$logger->log("Exception: " . $error, Zend_Log::ERR);
		}
	}
	// ダンジョンクリア失敗の時はチートの可能性があるので90秒待たせる.
	if($e->getCode() == RespCode::FAILED_CLEAR_DUNGEON){
		unset($lock_key);
	}
}

if(isset($lock_key)){
	// ロックを削除
	$redis->delete($lock_key);
}

// #PADC# ----------begin----------
// レスポンスをエンコード
if($actionName::ENCRYPT_RESPONSE)
{
	$responseHash = getHashStr($response);
	$response = 'hash=' . $responseHash . '&json=' . $response;
	$response = 'param=' . Padc_Encrypt::encrypt($response);
}
// #PADC# ----------end----------

if(substr($response, 0, 2) === "\x1f\x8b"){
	if(isset($_SERVER["HTTP_ACCEPT_ENCODING"])){
		$http_accept_encoding = $_SERVER["HTTP_ACCEPT_ENCODING"];
	}else{
		$http_accept_encoding = "";
	}
	if(strpos($http_accept_encoding, "gzip") !== FALSE) {
		// Accept-Encodingヘッダがある場合はgzipデータを返す.
		header("Content-Encoding: gzip");
		header("Vary: Accept-Encoding");
		echo($response);
	}else{
		// Accept-Encodingヘッダがない場合はgzipデータを展開して返す.
		echo gzinflate(substr($response, 10, -4));
	}
}else{
	if(!headers_sent()){
		// gzipで圧縮
		ob_start("ob_gzhandler");
	}
	echo $response;
}

/**
 * #PADC#
 * メンテナンスチェック
 */
function checkMaintenance($params)
{
	// メンテ突破ユーザとして登録されている場合、メンテをスルー
	if(isset($params['ten_oid']) || isset($params['pid']))
	{
		// ファイル管理の場合
		if(Env::MAINTENANCE_TYPE)
		{
			$debugUsers = array();
			$filePath = Env::DEBUG_USER_FILE_PATH . '/' . Env::MAINTENANCE_DEBUG_USER_FILE;
			if(file_exists($filePath))
			{
				$tmpDebugUsers = array();
				$debugUserData = file_get_contents($filePath);
				$debugUsers = explode("\n", $debugUserData);
				foreach($debugUsers as $key => $value)
				{
					$value = trim($value);
					if($value)
					{
						$tmpDebugUsers[] = $value;
					}
				}
				$debugUsers = $tmpDebugUsers;
			}
			if(isset($params['ten_oid']))
			{
				if(in_array($params['ten_oid'],$debugUsers))
				{
					return;
				}
			}
			if(isset($params['pid']))
			{
				if(in_array($params['pid'],$debugUsers))
				{
					return;
				}
			}
		}
		// DB管理の場合
		else
		{
			/*
			$rRedis = Env::getRedisForShareRead();
			$key = RedisCacheKey::getDebugUserKey();
			$debugUsers = $rRedis->get($key);
			if($debugUsers === FALSE)
			{
				// OpenID、UserIDをキーとしてデバッグユーザ情報をキャッシュに保存しておく
				$debugUsers = DebugUser::findAllBy(array());
				$setDebugUsers = array();
				foreach($debugUsers as $debugUser)
				{
					if($debugUser->open_id)
					{
						$setDebugUsers[$debugUser->open_id] = $debugUser;
					}
					if($debugUser->user_id)
					{
						$setDebugUsers[$debugUser->user_id] = $debugUser;
					}
				}
				$debugUsers = $setDebugUsers;

				$redis = Env::getRedisForShare();
				$redis->set($key,$setDebugUsers,DebugUser::MEMCACHED_EXPIRE);
			}
			*/
			$debugUsers = DebugUser::getDebugUsers();

			// メンテ突破ユーザとして登録されている場合、メンテをスルー
			if(isset($params['ten_oid']))
			{
				$openId = $params['ten_oid'];
				if(isset($debugUsers[$openId]) && $debugUsers[$openId]->maintenance_flag == DebugUser::MAINTENANCE_FREE)
				{
					return;
				}
			}
			if(isset($params['pid']))
			{
				$userId = $params['pid'];
				if(isset($debugUsers[$userId]) && $debugUsers[$userId]->maintenance_flag == DebugUser::MAINTENANCE_FREE)
				{
					return;
				}
			}
		}
	}

	// 対象のファイルが存在していたらメンテナンスエラーを返す
	$maintenanceFile = ROOT_DIR . '/' . Env::MAINTENANCE_FILE;
	if(file_exists($maintenanceFile))
	{
		// レスポンスとしてファイルの内容をそのまま返す
		$response = file_get_contents($maintenanceFile);
		echo $response;
		exit;
	}
	return;
}

/**
 * #PADC#
 * hash値取得
 */
function getHashStr($str)
{
	return hash('sha256', $str);
}

/**
 * #PADC#
 * リクエストパラメータをデコード
 * @param string $param
 * @throws PadException
 */
function getDecodeParam($param)
{
	$decodeGetParam = Padc_Encrypt::decrypt($param);// 文字列をデコード

	// hashキーの値を取得＆key=valueの形式で配列にセット
	$spldata = explode('&',$decodeGetParam);
	$checkhash = '';
	$checkparams = array();
	$checkparamlist = '';
	$tmpGetParam = array();
	foreach($spldata as $_splKey => $_spldata)
	{
		$sp_array = explode('=',$_spldata, 2);
		// key=valueの形式になっていないものがあれば無視する
		if (count($sp_array) < 2) {
			continue;
		}
		list($_key,$_value) = $sp_array;
		if($_key == 'hash' && $_splKey == 0)
		{
			$checkhash = $_value;
		}
		else
		{
			$checkparams[] = $_spldata;
			$tmpGetParam[$_key] = urldecode($_value);
		}
	}

	// hash値の合致チェック
	if(is_array($checkparams))
	{
		$checkparamlist = implode('&', $checkparams);
	}
	if(!($checkhash && $checkparamlist && $checkhash == getHashStr($checkparamlist)))
	{
		throw new PadException(RespCode::UNKNOWN_ERROR,'invalid request');
	}

	return $tmpGetParam;
}

?>
