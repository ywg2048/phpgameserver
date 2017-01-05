<?php
// ディレクトリ定数.
define('ROOT_DIR',    realpath(__DIR__ . '/../..'));
define('APP_DIR',     realpath(ROOT_DIR . '/app'));
define('CONFIG_DIR',  realpath(ROOT_DIR . '/app/config'));
define('LIB_DIR',     realpath(ROOT_DIR . '/app/lib'));
define('MODEL_DIR',   realpath(ROOT_DIR . '/app/models'));
define('ACTION_DIR',   realpath(ROOT_DIR . '/app/actions'));
define('FILTER_DIR',  realpath(ROOT_DIR . '/app/filters'));
define('VIEW_DIR',  realpath(ROOT_DIR . '/app/views'));
define('ALL_CONFIG_DIR',  realpath(ROOT_DIR . '/global'));

require_once("application.php");

set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR);

// #PADC# ----------begin---------- 
// Tencent Msdk
require_once(LIB_DIR . "/Tencent/MsdkApi.php");
// tencent tlog autoload file
require_once(LIB_DIR . "/Tencent/Tlog.php");
// 時間関連
require_once(LIB_DIR . "/Padc/Time/Time.php");
// ログ関連
require_once(LIB_DIR . "/Padc/Log/Log.php");
// 暗号化関連
require_once(LIB_DIR . "/Padc/Encrypt.php");
// #PADC# ----------end----------

// Fluentd
set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR. '/fluent-logger-php');
require_once("Fluent/Autoloader.php");
Fluent\Autoloader::register();

// ベンチマーク設定
set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR . '/Benchmark-1.2.8');
require_once("Benchmark/Timer.php");
$timer = new Benchmark_Timer();
$timer->start();

require CONFIG_DIR . '/ClassLoader.php';

$class_loader = new ClassLoader();
$class_loader->registerDir(MODEL_DIR);
$class_loader->registerDir(ACTION_DIR);
require_once(FILTER_DIR . "/environment.php");
// エラーハンドラ. エラーが発生したら例外をスローする.
function errorHandler($errno, $errstr, $errfile, $errline) {
  throw new Exception($errfile . "[" . $errline . "]:" . $errstr, $errno);
}
set_error_handler('errorHandler');
