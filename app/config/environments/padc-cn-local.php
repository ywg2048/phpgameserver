<?php
/**
 * #PADC#
 * ローカル開発環境定数.
 */
class Env extends AllEnv
{
	const ENV = 'padclocal';

	// base.jsonの取得時間が LAST_BASE_JSON_UPDATE 以前ならば再DLする。
	// ログインAPIのレスポンスで使用。前もってbase.jsonをアップできる場合は更新の必要なし。
	const LAST_BASE_JSON_UPDATE = '120228040000';

	// HOSTNAME
	const HOSTNAME = '127.0.0.1';

	// ログ書き込み先パス
	const LOG_PATH = '/var/log/pad/';

	// ログファイルの書込み先ファイル名
	const LOG_FILE = "local-ios-pad.log";

	// エラーログファイルの書込み先
	const ERR_LOG_FILE = "local-ios-pad-err.log";

	// 課金ログファイルの書込み先
	const PURCHASE_LOG_FILE = "local-ios-purchase.log";
	const IGNORED_PURCHASE_LOG_FILE = "";

	// 各種レアガチャログファイルの書込み先
	const RARE_GACHA_LOG_FILE = "local-rare-gacha.log";
	const EXTRA_GACHA_LOG_FILE = "local-extra-gacha.log";

	// snapshotlogの書き込み先ディレクトリ
	const SNAPSHOT_LOG_PATH = "/var/log/pad/";

	// 対応OS(ios | android)
	const ENABLED_OS = 'ios';

	// 必須バージョン
	const APP_VERSION = 7.0;

	// ログインチェックを行うかどうか.
	const CHECK_LOGIN = FALSE;

	// #PADC# デバッグユーザ管理ファイルパス
	const DEBUG_USER_FILE_PATH = "/usr/local/apache/htdocs/file";

	//------------------------------
	// メンテナンス管理
	//------------------------------

	const MAINTENANCE_FILE = 'public/maintenance.htm';// ROOT_DIRからの相対パス
	const MAINTENANCE_TYPE = 0;// 0:DB/1:FILE
	const MAINTENANCE_DEBUG_USER_FILE = 'maintenance_debug_user.php';// MAINTENANCE_TYPEが1:FILEの時に参照するユーザ管理ファイル

	//------------------------------
	// ユーザ登録制限
	//------------------------------

	const CHECK_SIGNUP_LIMIT			= true;// ユーザ登録上限チェックを行うかどうか
	const SIGNUP_BASE_USER_ID			= 123795840;// ユーザ登録の基点ID（現在のMAXIDとこのIDの差分をもとに登録数をチェックする）
	const SIGNUP_DEBUG_USER_FILE		= 'signuplimit_debug_user.php';// 登録上限状態でもユーザ登録可能なユーザ
	const CHECK_SIGNUP_LIMIT_DEBUG_USER	= true;// ユーザ登録上限を突破できるユーザをチェックするかどうか

	//------------------------------
	// Tencent関連
	//------------------------------

	// API server domain
	const TENCENT_MSDK_DOMAIN = 'msdktest.qq.com';

	// Midas
	const MIDAS_APPKEY_IOS	= 'f9QIYttSwpwP4hqIewib23xTINuvSwZC';
	const MIDAS_ZONEID_ADR = 1;
	const MIDAS_ZONEID_IOS = 1;

	// TencentAPIのログインチェックを行うかどうか
	const CHECK_TENCENT_LOGIN = false;

	// TencentとMidas用のTokenをチェックするかどうか
	const CHECK_TENCENT_TOKEN = false;

	const CHECK_TENCENT_WECHAT_FRIEND = false;

	const ENABLE_QQ_REPORT_SCORE = false;

	// ログ出力方法
	const OUT_LOG_TYPE = 0;// 0：ファイル出力、1：TencentTlog送信

	// Tlog server
	const TLOG_SERVER		= NULL;
	const TLOG_PORT			= NULL;
	const TLOG_VGAME_IP		= 0;
	const TLOG_GAMESVR_ID	= 0;
	const TLOG_VGAMEAPP_ID	= 0;
	const TLOG_IZONEAREA_ID	= 0;
	const TLOG_ZONEID		= 0;

	// Tlogログファイル
	const TLOG_LOG_FILE	= 'tlog.log';

	//idip area
	const IDIP_AREA_WECHAT = 1;
	const IDIP_AREA_QQ = 2;

	// iOS版Midas　APIを使うことが許可するかどうか
	const ENABLE_IOS_MIDAS = true;

	//QQ会員デバッグ機能
	const ENABLE_QQ_VIP_DEBUG = true;
}
