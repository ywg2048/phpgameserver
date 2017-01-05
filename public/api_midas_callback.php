<?php
// 腾讯购买道具回调
require_once("../app/config/autoload.php");

setEnvironment($_SERVER["SERVER_NAME"], $_SERVER["SERVER_PORT"]);

$res = array(
	'ret' => 0,
	'msg' => 'OK'
);

global $logger;

try {
	$method = $_SERVER['REQUEST_METHOD'];
	$uri = $_SERVER['REQUEST_URI'];

	$params = array_merge($_GET, $_POST);
	$logger->log('Midas_callback_request:' . json_encode($params), 7);

	if (!isset($params['openid']) || !isset($params['appid']) || !isset($params['sig'])) {
		echo json_encode(array(
			'ret' => 4,
			'msg' => '请求参数错误：（openid, appid, sig）'
		));
		exit;
	}

	$sig = $params['sig'];
	unset($params['sig']);

	ksort($params);

	$data = array();
	foreach ($params as $key => $val) {
		$data[] = $key . '=' . $val;
	}
	$param_str = implode('&', $data);

	$source = $method . '&' . rawurlencode($uri) . '&' . rawurlencode($param_str);

	$appkey = '';
	$type = '';
	if ($params['appid'] == ENV::MIDAS_APPID_IOS) {
		$appkey = ENV::MIDAS_APPKEY_IOS;
		$type = User::TYPE_ANDROID;
	} elseif ($params['appid'] == ENV::MIDAS_APPID_ADR) {
		$appkey = ENV::MIDAS_APPKEY_ADR;
		$type = User::TYPE_IOS;
	}

	$sign = base64_encode(hash_hmac('sha1', $source, $appkey . '&'));

	if ($sign != $sig) {
		$res = array(
			'ret' => 4,
			'msg' => '请求参数错误：（sig）'
		);
	}

	$pdo_share = Env::getDbConnectionForShare();
	$user_device = UserDevice::findBy(array('type' => $type, 'oid' => $params['openid']), $pdo_share);
	
	$activity = Activity::getByType(Activity::ACTIVITY_TYPE_1YG);

	if($user_device && $activity && UserActivity::updateStatus($user_device->id, $activity->id, UserActivity::STATE_CLEAR)) {
		$logger->log('Midas Callback success', 7);
		echo json_encode(array(
			'ret' => 0,
			'msg' => 'OK'
		));
	} else {
		$logger->log('warning:user not found or activity not exist or not first purchase', 7);
		echo json_encode(array(
			'ret' => 0,
			'msg' => 'warning:user not found or activity not exist or not first purchase'
		));
	}
} catch (Exception $e) {
	$logger->log('response:' . $e->getMessage(), 7);

	$logger->log('Midas Callback failed! error occured!', 7);
	echo json_encode(array(
		'ret' => 4,
		'msg' => $e->getMessage()
	));
}
?>