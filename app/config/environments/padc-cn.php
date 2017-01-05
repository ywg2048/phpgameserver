<?php
/**
 * ローカル開発環境定数.
 */
class AllEnv extends GlobalEnv {

//※※※※ここより下は継承クラスで上書きすること※※※※
	// base.jsonの取得時間が LAST_BASE_JSON_UPDATE 以前ならば再DLする。
	// ログインAPIのレスポンスで使用。前もってbase.jsonをアップできる場合は更新の必要なし。
	const LAST_BASE_JSON_UPDATE = '';
	// HOSTNAME
	const HOSTNAME = '';
	// 対応OS(ios | android)
	const ENABLED_OS = '';
	// #PADC#
	// ログファイルの書込み先パス
	const LOG_PATH = "";
	// ログファイルの書込み先
	const LOG_FILE = "";
	// エラーログファイルの書込み先
	const ERR_LOG_FILE = "";
	// 課金ログファイルの書込み先
	const PURCHASE_LOG_FILE = "";
	const IGNORED_PURCHASE_LOG_FILE = "";
	// 必須バージョン
	const APP_VERSION = 0;
	// #PADC# デバッグユーザ管理ファイルパス
	const DEBUG_USER_FILE_PATH = "";
//※※※※ここより上は継承クラスで上書きすること※※※※

	// 各種レアガチャログファイルの書込み先
	const RARE_GACHA_LOG_FILE = "local-rare-gacha.log";
	const EXTRA_GACHA_LOG_FILE = "local-extra-gacha.log";

	// デバッグツールによるDB編集ログファイルの書込み先
	const DEBUG_TOOL_EDIT_DB_LOG_FILE = "debug_tool_edit_db.log";
	
	const MODE = "APP";

	const SERVICE_AREA = "JP";

	// AppStore の本番サーバへアクセスするか (FALSEの場合は AppStore の Sandbox へアクセスする)
	const APPLE_APP_STORE_PRODUCTION = FALSE;

	// GooglePlay公開鍵
	const GOOGLE_PLAY_PUBLIC_KEY = "";

	// リクエストのサムをチェックするかどうか.
	const CHECK_REQ_SUM = FALSE;
	// ログインチェックを行うかどうか.
	const CHECK_LOGIN = TRUE;

	// ログインチェックユーザ一覧を返す.
	public static function getLoginCheckUsers() {
		return array();
	}

	// #PADC# ----------begin----------

	const API_LOG_LEVEL		= Zend_log::DEBUG;// ログの出力レベル
	const API_ERR_LOG_LEVEL	= Zend_log::ERR;// エラーログの出力レベル

	// 日の切り替えの時間
	const DAY_SWITCHING_HOUR = 10;

	//------------------------------
	// メンテナンス管理
	//------------------------------

	const MAINTENANCE_FILE = 'public/maintenance.html';// ROOT_DIRからの相対パス
	const MAINTENANCE_TYPE = 0;// 0:DB/1:FILE
	const MAINTENANCE_DEBUG_USER_FILE = 'maintenance_debug_user.php';// MAINTENANCE_TYPEが1:FILEの時に参照するユーザ管理ファイル

	//------------------------------
	// ユーザ登録制限
	//------------------------------

	const CHECK_SIGNUP_LIMIT			= false;// ユーザ登録上限チェックを行うかどうか
	const SIGNUP_BASE_USER_ID			= 1;// ユーザ登録の基点ID（現在のMAXIDとこのIDの差分をもとに登録数をチェックする）
	const SIGNUP_DEBUG_USER_FILE		= 'signuplimit_debug_user.php';// 登録上限状態でもユーザ登録可能なユーザ
	const CHECK_SIGNUP_LIMIT_DEBUG_USER	= false;// ユーザ登録上限を突破できるユーザをチェックするかどうか

	//------------------------------
	// fluent関連
	//------------------------------

	const FLUENT_UNIX_DOMAIN_SOCK = "unix:///var/run/td-agent/td-agent.sock";
	const FLUENT_ERROR_FILE = "fluent_error";
	const FLUENT_FLAG = false;// #PADC# Fluentが使用可能かどうか
	
	//------------------------------
	// Tencent関連
	//------------------------------

	// Tencent MSDKとMidas（現状は統一です）のサーバーアドレス, 開発環境の場合、テストサーバーで上書きします。
	const TENCENT_MSDK_DOMAIN　= 'msdk.qq.com';

	// TencentAPIのログインチェックを行うかどうか
	const CHECK_TENCENT_LOGIN = true;

	// TencentとMidas用のTokenをチェックするかどうか
	const CHECK_TENCENT_TOKEN = true;

	// WeChatフレンドデータを取得するかどうか。　開発環境でWeChatフレンドデータのアクセス権限がない時falseを上書きします。
	const CHECK_TENCENT_WECHAT_FRIEND = true;
	
	// Tencentフレンドランキング用のスコラをアップするかどうか。
	const ENABLE_QQ_REPORT_SCORE = true;

	// アプリ情報、MsdkAPI必要なパラメータです。
	const QQ_APPID		= '1104067326';		//QQのアプリID
	const QQ_APPKEY		= 'dwwjmkZv6PxENtxu';		//QQのアプリキー
	const WECHAT_APPID	= 'wx214189d265281f9f';		//WeChatのアプリID
	const WECHAT_APPKEY	= 'd50ff4082ae935fca34ca4df5c0f8c53';	//WeChatのアプリキー
	const GUEST_APPID	= 'G_1104067326';		//ゲストのアプリID
	const GUEST_APPKEY	= 'dwwjmkZv6PxENtxu';		//ゲストのアプリキー

	// Midas情報 　MidasAPI必要なパラメータです。
	const MIDAS_APPID_IOS	= '1450005455';		//iOS版MidasのアプリID
	const MIDAS_APPKEY_IOS	= 'WMb75sLZgNVrqL5gX1X12lumhSGSMgyT';	//iOS版Midasのアプリキー開発環境は：f9QIYttSwpwP4hqIewib23xTINuvSwZC を上書きします。★環境別に上書きします。
	const MIDAS_ZONEID_IOS	= 1;	//iOS版MidasのゾーンID。★環境別に上書きします。
	const MIDAS_APPID_ADR	= '1450001892';		//Android版MidasのアプリID。
	const MIDAS_APPKEY_ADR	= 'mSddie1TXyCYhmy5x9rT3be9T0SFmQ1N';	//Android版Midasのアプリキー。
	const MIDAS_ZONEID_ADR	= 2;	//Android版MidasのゾーンID。　★環境別に上書きします。
	// #PADC_DY# ----------begin----------
	const MIDAS_ZONEID_IOS_QQ = 1; // Midas IOS QQ ZoneId
	const MIDAS_ZONEID_IOS_WX = 1; // Midas IOS Wechat ZoneId
	const MIDAS_ZONEID_IOS_GUEST = 1; // Midas IOS Guest ZoneId
	const MIDAS_ZONEID_ADR_QQ = 1; // Midas Android QQ ZoneId
	const MIDAS_ZONEID_ADR_WX = 1; // Midas Android Wechat ZoneId
	// #PADC_DY# ----------end----------

	// 月額課金アイテムコードです。仕様変更で使わないです。
	const SUBSCRIBE_SERVICE_CODE = 11251;

	// ログ出力方法
	const OUT_LOG_TYPE = 0;// 0：ファイル出力、1：TencentTlog送信

	// Tlog server
	const TLOG_SERVER		= 'shanghai.nanhui.tglog.datacenter.db';//tlog先サーバー
	const TLOG_PORT			= 35680;//tlogサーバーポート
	const TLOG_VGAME_IP		= 0;//IP、固定０
	const TLOG_GAMESVR_ID	= 0;//サーバーID、固定０
	const TLOG_VGAMEAPP_ID	= 0;//アプリID,固定０
	const TLOG_IZONEAREA_ID	= 0;//ZoneAreaID, 固定０
	const TLOG_ZONEID		= 0;//ZoneID,固定０

	// Tlogログファイル
	const TLOG_LOG_FILE	= 'tlog.log';

	//idip area
	const IDIP_AREA_WECHAT = 1;// ★環境別に上書きします。
	const IDIP_AREA_QQ = 2;// ★環境別に上書きします。
	const IDIP_AREA_GUEST = 3;
	
	//security tlog area id
	const SECURITY_AREA_WECHAT = 0;
	const SECURITY_AREA_QQ = 1;
	const SECURITY_AREA_GUEST = 3;
	
	// iOS版Midas　APIを使うことが許可するかどうか
	const ENABLE_IOS_MIDAS = true;

	// 期限切れまたは受け取ったスタミナプレゼントを削除操作はActionで実行するかどうか。　TODO：falseの場合は別の方法で削除しよう。
	const ENABLE_DELETE_EXPIRED_STAMINA_PRESENTS = true;

	// 月額ボーナス関連設定
	const SUBSCRIPTION_DAILY_GOLD = 100;//毎日魔法石
	const SUBSCRIPTION_MONTH_COST = 300;//月額アイテムの魔法石コスト
	const SUBSCRIPTION_PERIOD = 2592000;// 月額（ボーナス）期間30日(60sec * 60min * 24hour * 30day)

	//QQ会員デバッグ機能
	const ENABLE_QQ_VIP_DEBUG = false;
	
	//QQ会員機能利用可能かどうか
	const ENABLE_QQ_VIP = true;

	// 課金商品
	public static function getProductItems() {
		return array (
			[
				'name' => 'test_name',
				'code' => 'jp.gungho.padtest.PadStone_001',
				'price' => 1,
				'bmsg' => 'test_bmsg',
				'spc' => 0,
				'buyable' => 1
			]
		);
	}

	// #PADC# ----------end----------
}
