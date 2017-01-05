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

$_GET = array_merge($_GET,$_POST);
$actionName = $_GET['action'];
echo $actionName."返回值是：";
$actionName = underscore2Camel($actionName);
$action = new $actionName();
$response = $action->process($_GET, $_SERVER['REQUEST_URI'], $_POST);

$response = json_decode($response,true);

echo "<pre>";
print_r($response);
echo "<pre>";
