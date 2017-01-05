<?php

// PHPファイル群をロード.
require_once ("../app/config/autoload.php");

define('TENCENT_ACTION_DIR',   ACTION_DIR . '/tencent');
$class_loader = new ClassLoader();
$class_loader->registerDir(TENCENT_ACTION_DIR);

setEnvironment($_SERVER["SERVER_NAME"],$_SERVER["SERVER_PORT"]);

function underscore2Camel($str) {
	$words = explode('_', strtolower($str));
	$return = '';
	foreach ($words as $word) {
		$return .= ucfirst(trim($word));
	}
	return $return;
}

function getActionName($cmd_id){
	$action_list = array(
			0x1001 => 'DoUpdateExp',
			0x1003 => 'DoUpdateLevel',
			0x1005 => 'DoUpdateMoney',
			0x1007 => 'DoUpdatePhysical',
			0x1009 => 'DoUpdateVipExp',
			0x100b => 'DoDelItem',
			0x100d => 'DoSendItem',
			0x100f => 'DoSendBatBonus',
			0x1011 => 'QueryUsrInfo',
			0x1013 => 'QueryUsrInfoByRoleName',
			0x1015 => 'QueryBanInfo',
			0x1017 => 'QueryItemInfo',
			0x1019 => 'DoBanUsr',
			0x103d => 'DoUnbanUsr',
			0x101d => 'AqQueryUsrInfo',
			0x101f => 'AqDoSendMsg',
			0x1021 => 'AqDoUpdateMoney',
			0x1023 => 'AqDoUpdateStone',
			0x1025 => 'AqDoBanPlay',
			0x1027 => 'AqDoZeroprofit',
			0x1029 => 'AqDoBanUsr',
			0x102b => 'AqDoRelievePunish',
			0x102d => 'AqDoBanJoinrankOffline',
			0x102f => 'AqDoInitAccount',
			0x1031 => 'AqDoBanPlayAll',
			0x1033 => 'AqDoSetGameScore',
			0x1035 => 'AqDoClearGameScore',
			0x1037 => 'AqDoClearCard',
            0x1039 => 'DoUpdateFriends',
            0x103b => 'DoNoticeMail',
            0x101b => 'DoSkipNewbeeGuidance',
            0x1041 => 'QueryUserOpenId',
			0x103f => 'DoMail',
			0x1043 => 'DoMaskchat',
			0x1045 => 'DoClearSpeak',
			0x1047 => 'DoUserAddWhite',
	);
	if(!isset($action_list[$cmd_id])){
		throw new PadException(RespCode::UNKNOWN_ERROR, "not found API for CmdId:$cmd_id");
	}

	return 'Tencent'.$action_list[$cmd_id];
}

function convertResCode($res_code){
	if($res_code == RespCode::USER_NOT_FOUND){
		return TencentBaseAction::ERR_NO_USER;
	}else if($res_code < 0){
		return $res_code;
	}else if($res_code != 0){
		return TencentBaseAction::CMD_ERROR_API_GAME;
	}
	return 0;
}

function getErrMsg($result){
	if(isset($result['res']) && !$result['res']){
		return '';
	}
	return isset($result['msg'])? $result['msg'] : '';
}

function convertResponse($response, $data_packet){
	//global $logger;
	//$logger->log('convertResponse response:'.print_r($response, true), 7);
	$result_array = json_decode($response, true);
	$response_array = array(
			'head' => array(
					'PacketLen' => 0,
					'Cmdid' => $data_packet['head']['Cmdid'] + 1,
					'Seqid' => $data_packet['head']['Seqid'],
					'ServiceName' => $data_packet['head']['ServiceName'],
					'SendTime' => strftime ( '%Y%m%d', time () ),
					'Version' => $data_packet['head']['Version'],
					'Authenticate' => $data_packet['head']['Authenticate'],
					'Result' => convertResCode($result_array['res']),
					'RetErrMsg' => getErrMsg($result_array)//isset($result_array['msg'])? $result_array['msg'] : ''
			),
			'body' => $result_array
	);
	unset($response_array['body']['res']);
	if(isset($response_array['body']['msg'])){
		unset($response_array['body']['msg']);
	}
	$response_json = json_encode($response_array);
	if($result_array['res'] != 0){
		global $logger;
		$logger->log('error!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!', 7);
	}
	//$logger->log('response:'.$response_json, 7);
	return $response_json;//'response='.$response_json;
}

$response = "";
$error_msg = NULL;

try
{
	if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else {
		$ip_addr = $_SERVER["REMOTE_ADDR"];
	}

	// #PADC# リクエストパラメータをgetでもpostでも受けれるようにマージ
	$_GET = array_merge($_GET,$_POST);
	$params = array();
	global $logger;
	$logger->log('params:'.print_r($_GET, true), 7);
	if(isset($_GET['data_packet']))
	{
		$data_packet = json_decode($_GET['data_packet'],true);
		if ($data_packet == null) {
			$logger->log('Failed to decode data_packet, json_decode errorcode:' . json_last_error(), 7);
		}
		$params = $data_packet['body'];
		$params['Cmdid'] = $data_packet['head']['Cmdid'];
	}
	
	$logger->log('Cmdid:'.print_r($data_packet['head']['Cmdid'], true), 7);
	$actionName = getActionName($data_packet['head']['Cmdid']);
	if(!class_exists($actionName)){
		throw new PadException(RespCode::UNKNOWN_ERROR, 'Not found 0x'.dechex($data_packet['head']['Cmdid']));
	}
	$action = new $actionName();

	$response = $action->process($params, $_SERVER['REQUEST_URI'], $_POST);
}catch(Exception $e){
	if ( $e instanceof PadException ){
		$code = $e->getCode();
		$response = json_encode(array('res'=>$code, 'msg'=>$e->getMessage()));
	}else{
		$response = json_encode(array('res'=>RespCode::UNKNOWN_ERROR, 'msg'=>$e->getMessage()));
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

if(!headers_sent()){
	// gzipで圧縮
	ob_start("ob_gzhandler");
}

global $logger;
$logger->log('response:'.$response, 7);
$response = convertResponse($response, $data_packet);
global $logger;
$logger->log('response'.$response, 7);
echo $response;

?>
