<?php
/**
 * #PADC#
 * TencentIOS課金検証QQ環境定数.
 */
class Env extends AllEnv
{
	const ENV = 'tencentiosapplytestqq';

	// base.jsonの取得時間が LAST_BASE_JSON_UPDATE 以前ならば再DLする。
	// ログインAPIのレスポンスで使用。前もってbase.jsonをアップできる場合は更新の必要なし。
	const LAST_BASE_JSON_UPDATE = '120228040000';

	// HOSTNAME
	const HOSTNAME = '127.0.0.1';

	// ログ書き込み先パス
	const LOG_PATH = '/data/padc/logs/';

	// ログファイルの書込み先
	const LOG_FILE = "padc.log";

	// エラーログファイルの書込み先
	const ERR_LOG_FILE = "padc-err.log";
	
	// 課金ログファイルの書込み先
	const PURCHASE_LOG_FILE = "purchase.log";
	const IGNORED_PURCHASE_LOG_FILE = "";

	// 各種レアガチャログファイルの書込み先
	const RARE_GACHA_LOG_FILE = "rare-gacha.log";
	const EXTRA_GACHA_LOG_FILE = "extra-gacha.log";

	// snapshotlogの書き込み先ディレクトリ
	const SNAPSHOT_LOG_PATH = "/data/padc/logs/";

	// 対応OS(ios | android)
	const ENABLED_OS = 'ios';

	// 必須バージョン
	const APP_VERSION = 7.0;

	// #PADC# デバッグユーザ管理ファイルパス
	const DEBUG_USER_FILE_PATH = "/var/www/padc/www/file";

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
	const SIGNUP_BASE_USER_ID			= 100000;// ユーザ登録の基点ID（現在のMAXIDとこのIDの差分をもとに登録数をチェックする）
	const SIGNUP_DEBUG_USER_FILE		= 'signuplimit_debug_user.php';// 登録上限状態でもユーザ登録可能なユーザ
	const CHECK_SIGNUP_LIMIT_DEBUG_USER	= true;// ユーザ登録上限を突破できるユーザをチェックするかどうか

	//------------------------------
	// Tencent関連
	//------------------------------

    // Tencent MSDK server domain
    const TENCENT_MSDK_DOMAIN = 'msdk.qq.com';

	// MidasのZoneID
    const MIDAS_ZONEID = 103;

	// TencentAPIのログインチェックを行うかどうか
	const CHECK_TENCENT_LOGIN = true;
	
	// TencentとMidas用のTokenをチェックするかどうか
	const CHECK_TENCENT_TOKEN = true;

	const CHECK_TENCENT_WECHAT_FRIEND = true;
	
	// ログ出力方法
	const OUT_LOG_TYPE = 1;// 0：ファイル出力、1：TencentTlog送信

	// Tlog server
	const TLOG_SERVER		= 'shanghai.nanhui.tglog.datacenter.db';
	const TLOG_PORT			= 35680;
	const TLOG_GAMESVR_ID	= 0;// 固定値を設定
	const TLOG_ZONEID		= 0;// 固定値を設定
	const TLOG_VGAME_IP		= 0;// 未使用
	const TLOG_VGAMEAPP_ID	= 0;// 未使用
	const TLOG_IZONEAREA_ID	= 0;// 未使用

	// Tlogログファイル
	const TLOG_LOG_FILE	= 'tlog.log';
	
	//idip area
	const IDIP_AREA_WECHAT = 1;
	const IDIP_AREA_QQ = 2;
	
	// iOS版Midas　APIを使うことが許可するかどうか
	const ENABLE_IOS_MIDAS = false;
}
