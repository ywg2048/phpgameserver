<?php

	/**
	 * ====================================================================================================
	 * 管理ツールTOP
	 * ====================================================================================================
	 * ・機能追加手順
	 * 1、/app/actions/adminにツール用APIを実装（※「AdminBaseAction」を継承。レスポンス形式はJsonフォーマットとする）
	 * 2、このファイルの「Debug機能ID定義」にIDを追加
	 * 3、このファイルの「Debug機能詳細定義（$requestInfos）」に項目を追加
	 * ====================================================================================================
	 */

	// PHPファイル群をロード.
	require_once ("../app/config/autoload.php");

	// MY : コマンドの引数からパラメータ設定できるように調整。
	if(isset($argv))
	{
		if(count($argv))
		{
			$argvList = explode('&',$argv[1]);
			foreach($argvList as $arg)
			{
				$param = explode('=',$arg);
				$_GET[$param[0]] = $param[1];
			}
			if(array_key_exists('server',$_GET))
			{
				$_SERVER['SERVER_NAME'] = $_GET['server'];
				$_SERVER['SERVER_PORT'] = null;
				$_SERVER['REQUEST_URI'] = null;
			}
		}
	}
	define('ADMIN_ACTION_DIR',   ACTION_DIR . '/admin');
	$class_loader = new ClassLoader();
	$class_loader->registerDir(ADMIN_ACTION_DIR);

	setEnvironment($_SERVER["SERVER_NAME"],$_SERVER["SERVER_PORT"]);

	$response = '';
	$error_msg = NULL;

	// getでもpostでも受けれるようにマージ
	$_GET = array_merge($_GET,$_POST);

	//====================================================================================================
	// const定義
	//====================================================================================================
	const REQUEST_URL_ADMIN		= 'api_admin.php';
	const REQUEST_URL_TENCENT	= 'api_tencent.php';

	// 環境設定
	const TOOL_ENV_NAME			= 0;
	const TOOL_BODY_COLOR		= 1;
	const TOOL_TITLE_COLOR		= 2;
	const TOOL_TITLE_BGCOLOR	= 3;
	const TOOL_SERVER_NAME		= 4;
	const TOOL_SERVER_PORT		= 5;
	const TOOL_LINK_TYPE		= 6;

	// ツールタイプ（※追加する場合「$columnList」にも追加
	const API_TYPE_LINK			= -1;// リンク管理
	const API_TYPE_ADMIN		= 0;// 管理系
	const API_TYPE_USER			= 1;// ユーザデータ操作系
	const API_TYPE_DATA			= 2;// マスターデータ参照
	const API_TYPE_GAME_DATA	= 3;// ゲームマスターデータ参照
	const API_TYPE_DATA_DL		= 4;// マスターデータ・マスターDLデータ参照
	const API_TYPE_TENCENT		= 5;// テンセント用
	const API_TYPE_DEBUG		= 6;// デバッグ系

	$columnList = array(
		API_TYPE_LINK		=> 'column_line_link',
		API_TYPE_DEBUG		=> 'column_line_debug',
		API_TYPE_ADMIN		=> 'column_line_admin',
		API_TYPE_USER		=> 'column_line_user',
		API_TYPE_DATA		=> 'column_line_data',
		API_TYPE_GAME_DATA	=> 'column_line_game_data',
		API_TYPE_DATA_DL	=> 'column_line_data_dl',
		API_TYPE_TENCENT	=> 'column_line_admin',
	);

	// jsonフォーマットで出力するかどうか
	const JSON_OUT_TRUE		= 1;
	const JSON_OUT_FALSE	= 0;

	// パラメータ入力フォームを使うかどうか
	const FORM_INPUT_USE	= 1;
	const FORM_INPUT_UNUSE	= 0;

	// 該当Form内で編集して良い項目かどうか
	const FORM_PARAM_UNEDITABLE	= 0;
	const FORM_PARAM_EDITABLE	= 1;
	const FORM_PARAM_DEVICETYPE	= 2;
	const FORM_PARAM_INVISIBLE	= 3;
	const FORM_PARAM_TEXTBOX	= 4;

	// jsonデータの表示フォーマット
	const HTML_TYPE_DEFAULT		= 0;
	const HTML_TYPE_TABLEFORMAT	= 1;

	// 直接実行しているかどうか
	const EXEC_DIRECT_ACCESS	= 0;
	const EXEC_INDIRECT_ACCESS	= 1;

	// パラメータをPOSTで渡すかGETで渡すか
	const SEND_PARAMETER_GET	= 0;
	const SEND_PARAMETER_POST	= 1;

	//====================================================================================================
	// Debug機能ID定義
	//====================================================================================================
	const TYPE_REDIS_CHECK							= 1;
	const TYPE_REDIS_CLEAR							= 2;
	const TYPE_REDIS_SEARCH							= 3;
	const TYPE_APC_CHECK							= 4;
	const TYPE_APC_CLEAR							= 5;
	const TYPE_APC_SEARCH							= 6;
	const TYPE_ADD_USER_PIECE						= 7;
	const TYPE_ADD_USER_COIN						= 8;
	const TYPE_DELETE_USER							= 9;
	const TYPE_SEARCH_USER							= 10;
	const TYPE_ADD_USER_FRIPNT						= 11;
	const TYPE_ADD_USER_GOLD						= 12;
	const TYPE_UPDATE_DUNGEON_FLOOR_CLEAR			= 13;
	const TYPE_UPDATE_DUNGEON_FLOOR_RESET			= 14;
	const TYPE_ADD_USER_MAILS						= 15;
	const TYPE_CHECK_DB_CARD						= 21;
	const TYPE_CHECK_DB_DUNGEON						= 22;
	const TYPE_CHECK_DB_SKILL						= 23;
	const TYPE_CHECK_DB_ENEMY_SKILL					= 24;
	const TYPE_CHECK_DB_PIECE						= 25;
	const TYPE_SEARCH_USER_BY_OPENID				= 26;
	const TYPE_ADD_USER_STAMINA						= 27;
	const TYPE_ADD_USER_CARD						= 28;
	const TYPE_UPDATE_USER_CARD						= 29;
	const TYPE_LAST_ERROR							= 30;
	const TYPE_PLAY_GACHA							= 31;
	const TYPE_ADD_ALL_PIECES						= 32;
	const TYPE_ADD_ALL_CARDS						= 33;
	const TYPE_ALL_CARDS_LEVEL_MAX					= 34;
	const TYPE_CHANGE_NAME							= 35;
	const TYPE_SNEAK_DUNGEON						= 36;
	const TYPE_ADD_FRIEND							= 37;
	const TYPE_UPDATE_USER_VIP						= 38;
	const TYPE_INIT_USER_CARDS						= 39;
	const TYPE_CHECK_DB_SCENE						= 40;
	const TYPE_CHECK_RESOURCE_DIRECTORY				= 41;
	const TYPE_CHECK_DB_CHALLENGE_DUNGEON_BONUS		= 42;
	const TYPE_CHECK_DB_EXTRA_GACHA					= 43;
	const TYPE_CHECK_DB_GACHA_PRIZE					= 44;
	const TYPE_CHECK_DB_GAME_CONSTANT				= 45;
	const TYPE_CHECK_DB_LIMITED_BONUS				= 46;
	const TYPE_CHECK_DB_LIMITED_BONUS_DUNGEON_BONUS	= 47;
	const TYPE_CHECK_DB_LOGIN_STREAK_BONUS			= 48;
	const TYPE_CHECK_DB_LOGIN_MESSAGE				= 49;
	const TYPE_CHECK_DB_LOGIN_TOTAL_COUNT_BONUS		= 50;
	const TYPE_CHECK_DB_SUPPORT_DATA				= 51;
	const TYPE_CHECK_DB_TREASURE					= 52;
	const TYPE_CHECK_DB_WAVE						= 53;
	const TYPE_CHECK_DB_WAVE_MONSTER				= 54;
	const TYPE_CHECK_DB_VERSION						= 55;
	const TYPE_TENCENT_QUERY_USERINFO				= 56;
	const TYPE_SNEAK_DUNGEON2						= 57;
	const TYPE_REDIS_SEARCH_DELETE					= 58;
	const TYPE_APC_SEARCH_DELETE					= 59;
	const TYPE_CHECK_DB_LIMITED_BONUS_GROUP			= 60;
	const TYPE_CHECK_DB_USER_DEVICE					= 61;
	const TYPE_TENCENT_UPDATE_LEVEL					= 62;
	const TYPE_TENCENT_UPDATE_MONEY					= 63;
	const TYPE_TENCENT_UPDATE_PHYSICAL				= 64;
	const TYPE_TENCENT_DEL_ITEM						= 65;
	const TYPE_TENCENT_SEND_ITEM					= 66;
	const TYPE_TENCENT_SEND_BAT_MAIL				= 67;
	const TYPE_TENCENT_QUERY_BAN_INFO				= 68;
	const TYPE_TENCENT_QUERY_ITEM_INFO				= 69;
	const TYPE_TENCENT_BAN_USER						= 70;
	const TYPE_TENCENT_UNBAN_USER					= 71;
	const TYPE_CHECK_DB_TENCENT_BONUS				= 72;
	const TYPE_CHECK_DB_SIGNUP_LIMIT				= 73;
	const TYPE_TEST_TLOG							= 74;
	const TYPE_CHECK_DB_ALL_USER_BONUS				= 75;
	const TYPE_UPDATE_DUNGEON_FLOOR_CLEAR2			= 76;
	const TYPE_CHECK_DB_MISSION						= 77;
	const TYPE_CHECK_USER_MISSION					= 78;
	const TYPE_UPDATE_USER_MISSION					= 79;
	const TYPE_CHECK_DB_VIP_BONUS					= 80;
	const TYPE_CHECK_DB_VIP_COST					= 81;
	const TYPE_CHECK_DB_NG_WORD						= 82;
	const TYPE_CHECK_DB_LEVELUP_EXPERIENCE			= 83;
	const TYPE_CHECK_DB_SUBSCRIPTION_BONUS			= 84;// テーブル使用しなくなったため欠番
	const TYPE_ADD_USER_SUBSCRIPTION				= 85;
	const TYPE_CHECK_DB_RANKING_DUNGEON				= 86;
	const TYPE_CHECK_DB_RANKING_WAVE				= 87;
	const TYPE_CHECK_DB_RANKING_WAVE_MONSTER		= 88;
	const TYPE_CHECK_DB_RANKING_TREASURE			= 89;
	const TYPE_CHECK_DB_RANKING						= 90;
	const TYPE_CHECK_DB_DUNGEON_PLUS_DROP			= 91;
	const TYPE_CHECK_DB_RANKING_DUNGEON_PLUS_DROP	= 92;
	const TYPE_CHECK_DB_RANKING_DUNGEON_REWORD		= 93;
	const TYPE_CHECK_DB_LIMITED_RANKING				= 94;
	const TYPE_CHECK_DB_LOGIN_PERIOD				= 95;
	const TYPE_SNEAK_DUNGEON3						= 96;
	const TYPE_PLAY_GACHA2							= 97;
	const TYPE_CHECK_DUNGEDON_SALE					= 98;
	const TYPE_CHECK_USER_BOOK						= 99;
	const TYPE_APC_AND_REDIS_CLEAR					= 100;
	const TYPE_CHECK_DB_TUTORIAL_CARD				= 101;
	const TYPE_VIEW_DB_TUTORIAL_CARD				= 102;
	const TYPE_SET_MASTER_DOWNLOAD_DATA				= 103;
	const TYPE_CHECK_DB_DAILY_DUNGEON_BONUS			= 104;
	const TYPE_CHECK_DB_DEBUG_USER					= 105;
	const TYPE_CHECK_DB_CONVERT_TEXT				= 106;
	const TYPE_UPDATE_USER_RANKING					= 107;
	const TYPE_RANKING_REFLECTION					= 108;
	const TYPE_ENTRY_DUMMY_RANKING					= 109;
	const TYPE_CHECK_RECOMENDED_HELPER				= 110;
	const TYPE_SET_RECOMENDED_HELPER				= 111;
	const TYPE_VIEW_CALENDAR						= 112;
	const TYPE_TENCENT_UPDATE_VIP					= 113;
	const TYPE_TENCENT_QUERY_USR_INFO_BY_ROLENAME	= 114;
	const TYPE_TENCENT_AQ_QUERY_USR_INFO			= 115;
	const TYPE_TENCENT_AQ_DO_SEND_MSG				= 116;
	const TYPE_TENCENT_AQ_DO_UPDATE_MONEY			= 117;
	const TYPE_TENCENT_AQ_DO_CLEAR_GAMESCORE		= 118;
	const TYPE_TENCENT_AQ_DO_SET_GAMESCORE			= 119;
	const TYPE_TENCENT_AQ_DO_INIT_ACCOUNT			= 120;
	const TYPE_TENCENT_AQ_DO_ZEROPROFIT				= 121;
	const TYPE_TENCENT_AQ_DO_BAN_PLAY				= 122;
	const TYPE_TENCENT_AQ_DO_BAN_JOINRANK_OFFLINE	= 123;
	const TYPE_TENCENT_AQ_DO_BAN_USR				= 124;
	const TYPE_TENCENT_AQ_DO_RELIEVE_PUNISH			= 125;
	const TYPE_TENCENT_AQ_DO_UPDATE_STONE			= 126;
	const TYPE_TENCENT_AQ_DO_BAN_PLAY_ALL			= 127;
	const TYPE_TENCENT_AQ_DO_CLEAR_CARD				= 128;
	const TYPE_USER_MONSTER_EVOLVE					= 129;
	const TYPE_EXEC_SQL_QUERY						= 130;
	const TYPE_CHECK_DB_TB_ONLINE_CNT				= 131;
	const TYPE_SET_DEBUG_USER						= 132;
	const TYPE_SNEAK_DUNGEON4						= 133;
	const TYPE_CHECK_DB_GACHA_DISCOUNT				= 134;
	const TYPE_CHECK_USER_GACHA_DISCOUNT			= 135;
	const TYPE_DELETE_USER_GACHA_DISCOUNT			= 136;
	const TYPE_REDIS_SESSION_SEARCH_DELETE			= 137;
	const TYPE_EDIT_DB_SHARE_DATA					= 138;
	const TYPE_UPDATE_QQ_VIP						= 139;
	const TYPE_CHECK_USER_COUNT						= 140;
	const TYPE_CHECK_USER_CARD						= 141;
	const TYPE_SEARCH_USER_BY_DISPID				= 142;
	const TYPE_CONVSERT_USER_ID						= 143;
	const TYPE_INIT_USER_MISSION					= 144;
	const TYPE_CHECK_DB_EXCHANGE_LINEUP				= 145;
	const TYPE_CHECK_DB_EXCHANGE_ITEM				= 146;
	const TYPE_ALL_CARDS_FINAL						= 147;
	const TYPE_ADD_ALL_ADDITIONAL_CARDS				= 148;
	const TYPE_TENCENT_DO_UPDATE_FRIENDS        	= 149;
	const TYPE_TENCENT_DO_NOTICE_MAIL				= 150;
	const TYPE_TENCENT_QUERY_USER_OPEN_ID			= 151;
	const TYPE_TENCENT_DO_MAIL						= 152;
	const TYPE_TENCENT_DO_MASKCHAT					= 153;
	const TYPE_TENCENT_DO_CLEAR_SPEAK				= 154;
	const TYPE_TENCENT_DO_USER_ADD_WHITE            = 155;

	// 管理ツールの区分
	const TYPE_LINK_ADMIN				= 10000;
	const TYPE_LINK_CHECK				= 10001;
	const TYPE_LINK_USER				= 10002;
	const TYPE_LINK_DBDATA_AND_DLDATA	= 10003;
	const TYPE_LINK_DBDATA_MASTER		= 10004;
	const TYPE_LINK_DBDATA				= 10005;
	const TYPE_LINK_IDIP				= 10006;

	//====================================================================================================
	// Debug機能詳細定義
	//====================================================================================================
	// 	array(
	// 		Debug機能ID => array(
	// 			機能名称,
	// 			array(
	// 				リクエストパラメータ名 => array(
	// 					パラメータ名称（※説明も兼ねた和名）,
	// 					デフォルト値（※デフォルトで使用 or 表示する値）,
	// 					入力フォーマット（※入力フォーム表示時の表示形式）,
	// 				),
	// 					・
	// 					・
	// 			),
	// 			jsonデータのみで出力するかどうか（0：しない、1：別タブで出力）,
	// 			パラメータ入力用フォームを使用するかどうか（0：しない、1：する）,
	// 			実行結果の出力フォーマット（0：デフォルトのテキスト形式、1：Htmlのテーブルタグで整形）,
	// 			APIタイプ（0：管理者用、1：ユーザ用、2：データ確認用、3：データ&DLデータ確認用）,
	// 		),
	// 	);
	//====================================================================================================
	$requestInfos = array(

		//====================================================================================================
		// 管理系
		//====================================================================================================
		TYPE_LINK_ADMIN => array(
			'管理用',	array(),	JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_LINK,
		),

		//--------------------------------------------------
		TYPE_SET_MASTER_DOWNLOAD_DATA => array(
			'【管理用】DL Master数据更新（DLマスターデータ更新）',
			array(
				'action'		=> array('action名','admin_set_master_download_data',FORM_PARAM_UNEDITABLE),
				'target_table'	=> array('选择数据','',FORM_PARAM_EDITABLE),
				'pass'			=> array('密码','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		TYPE_APC_AND_REDIS_CLEAR => array(
			'【管理用】apc/redis数据全清除（apc/redis保存データ全削除）',
			array(
				'action'	=> array('action名','admin_manage_apc_and_redis',FORM_PARAM_UNEDITABLE),
				'pass'		=> array('密码','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		TYPE_REDIS_CHECK => array(
			'【管理用】redis数据確認（redis内容確認 ）',
			array(
				'action'	=> array('action名','admin_manage_redis',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',0,FORM_PARAM_INVISIBLE),
			),
			JSON_OUT_TRUE,
			FORM_INPUT_UNUSE,
			HTML_TYPE_DEFAULT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		// redis/apc同時に削除する機能追加したためいったんコメントアウト
		/*
		TYPE_REDIS_CLEAR => array(
			'【管理用】redis保存データ全削除',
			array(
				'action'	=> array('action名','admin_manage_redis',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',2,FORM_PARAM_INVISIBLE),
				'pass'		=> array('パスワード','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_ADMIN,
		),
		*/
		//--------------------------------------------------
		TYPE_REDIS_SEARCH => array(
			'【管理用】redis数据查询（redis保存データ検索）',
			array(
				'action'	=> array('action名','admin_manage_redis',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',3,FORM_PARAM_INVISIBLE),
				'rkey'		=> array('关键字','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		TYPE_REDIS_SEARCH_DELETE => array(
			'【管理用】Redis数据查询删除（redis保存データ検索削除）',
			array(
				'action'	=> array('action名','admin_manage_redis',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',1,FORM_PARAM_INVISIBLE),
				'rkey'		=> array('关键字','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		TYPE_APC_CHECK => array(
			'【管理用】Apc数据确认（apc内容確認）',
			array(
				'action'	=> array('action名','admin_manage_apc',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',0,FORM_PARAM_INVISIBLE),
			),
			JSON_OUT_TRUE,
			FORM_INPUT_UNUSE,
			HTML_TYPE_DEFAULT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		// redis/apc同時に削除する機能追加したためいったんコメントアウト
		/*
		TYPE_APC_CLEAR => array(
			'【管理用】apc保存データ全削除',
			array(
				'action'	=> array('action名','admin_manage_apc',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',2,FORM_PARAM_INVISIBLE),
				'pass'		=> array('パスワード','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_ADMIN,
		),
		*/
		//--------------------------------------------------
		TYPE_APC_SEARCH => array(
			'【管理用】Apc数据查询（apc保存データ検索）',
			array(
				'action'	=> array('action名','admin_manage_apc',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',3,FORM_PARAM_INVISIBLE),
				'rkey'		=> array('关键字','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		TYPE_APC_SEARCH_DELETE => array(
			'【管理用】Apc数据查询删除（apc保存データ検索削除）',
			array(
				'action'	=> array('action名','admin_manage_apc',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',1,FORM_PARAM_INVISIBLE),
				'rkey'		=> array('关键字','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_ADMIN,
		),
		//--------------------------------------------------
		TYPE_EXEC_SQL_QUERY => array(
			'【管理用】SQL执行（SQL実行）<span class="notes">（※使用要注意！）</span>',
			array(
				'action'		=> array('action名','admin_exec_sql_query',FORM_PARAM_UNEDITABLE),
				'sqlquery'		=> array('SQL','',FORM_PARAM_TEXTBOX),
				'pass'			=> array('パスワード','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_ADMIN,
			SEND_PARAMETER_POST,
		),
		//--------------------------------------------------
		TYPE_TEST_TLOG => array(
			'【管理用】Tlog导出测试（Tlog出力テスト）',
			array(
					'action'	=> array('action名','admin_test_tlog',FORM_PARAM_UNEDITABLE),
					'dummy'		=> array('是否导出Dummy Log (1:导出 0:不导出)', 0, FORM_PARAM_EDITABLE),
					'start'		=> array('開始時間 (Default:当日　例：20151001)', null, FORM_PARAM_EDITABLE),
					'end'		=> array('结束時間 (Default:当日　例：20151001)', null, FORM_PARAM_EDITABLE),
					'type'		=> array('Log种类 (Default:全部种类　例：PlayerLogin)', null, FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	
			FORM_INPUT_USE,	
			HTML_TYPE_TABLEFORMAT,	
			API_TYPE_ADMIN,
		),

		//====================================================================================================
		// デバッグ用
		//====================================================================================================
		TYPE_LINK_CHECK => array(
			'Debug用',	array(),	JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_LINK,
		),

		//--------------------------------------------------
		TYPE_PLAY_GACHA => array(
			'扭蛋测试（ガチャシミュレータ）',
			array(
				'action'		=> array('action名','admin_play_gacha',FORM_PARAM_UNEDITABLE),
				'gacha_type'	=> array('扭蛋类型','',FORM_PARAM_EDITABLE),
				'gacha_id'		=> array('追加扭蛋ID（※测试追加扭蛋才需设定、extra_gachaのid値）','',FORM_PARAM_EDITABLE),
				'cnt'			=> array('测试抽取次数',1,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		TYPE_PLAY_GACHA2 => array(
			'10连扭蛋测试（10連ガチャシミュレータ）',
			array(
				'action'		=> array('action名','admin_play_gacha2',FORM_PARAM_UNEDITABLE),
				'gacha_type'	=> array('扭蛋类型','',FORM_PARAM_EDITABLE),
				'gacha_id'		=> array('追加扭蛋ID（※测试追加扭蛋才需指定、extra_gachaのid値）','',FORM_PARAM_EDITABLE),
				'cnt'			=> array('测试抽取次数',1,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		TYPE_SNEAK_DUNGEON => array(
			'关卡潜入测试（ダンジョン潜入シミュレータ）',
			array(
				'action'			=> array('action名','admin_sneak_dungeon',FORM_PARAM_UNEDITABLE),
				'dfid'				=> array('关卡Floor ID','',FORM_PARAM_EDITABLE),
				'rank_dfid'			=> array('排名关卡Floor ID','',FORM_PARAM_EDITABLE),
				'cnt'				=> array('测试次数',1,FORM_PARAM_EDITABLE),
				'target_dungeon'	=> array('目标关卡','',FORM_PARAM_EDITABLE),
				'use_ticket'		=> array('使用扫荡卷 <span class="notes">（※周回可能なダンジョンかチェックはしません）</span>','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		// TYPE_SNEAK_DUNGEON4が全機能を含んでいるのでコメントアウト
		/*
		TYPE_SNEAK_DUNGEON2 => array(
			'ダンジョンクリア結果シミュレータ',
			array(
				'action'			=> array('action名','admin_sneak_dungeon2',FORM_PARAM_UNEDITABLE),
				'dfid'				=> array('ダンジョンフロアID','',FORM_PARAM_EDITABLE),
				'rank_dfid'			=> array('ランキングダンジョンフロアID','',FORM_PARAM_EDITABLE),
				'cnt'				=> array('試行回数',1,FORM_PARAM_EDITABLE),
				'target_dungeon'	=> array('対象ダンジョン','',FORM_PARAM_EDITABLE),
				'use_ticket'		=> array('周回チケット <span class="notes">（※周回可能なダンジョンかチェックはしません）</span>','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
		),
		*/
		//--------------------------------------------------
		TYPE_SNEAK_DUNGEON4 => array(
			'通关结果测试（ダンジョンクリア結果シミュレータ） <span class="notes">（※選択フロアや試行回数が多いとそれなりに時間がかかります）</span>',
			array(
				'action'			=> array('action名','admin_sneak_dungeon4',FORM_PARAM_UNEDITABLE),
				'dfid_multi'		=> array('关卡Floor ID','',FORM_PARAM_EDITABLE),
				'rank_dfid_multi'	=> array('排名关卡Floor ID','',FORM_PARAM_EDITABLE),
				'cnt'				=> array('测试次数',1,FORM_PARAM_EDITABLE),
				'target_dungeon'	=> array('目标关卡','',FORM_PARAM_EDITABLE),
				'use_ticket'		=> array('使用扫荡卷 <span class="notes">（※周回可能なダンジョンかチェックはしません）</span>','',FORM_PARAM_EDITABLE),
				'output_csv'		=> array('CSV Text','',FORM_PARAM_EDITABLE),
				'comment'			=> array('所需时间预测','Floor数×测试次数×(0.03～0.05)＋0.2秒',FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
			SEND_PARAMETER_POST,
		),
		//--------------------------------------------------
		TYPE_SNEAK_DUNGEON3 => array(
			'关卡潜入确认（ダンジョン潜入チェック) <span class="notes">（※選択フロアや試行回数が多いとそれなりに時間がかかります）</span>',
			array(
				'action'			=> array('action名','admin_sneak_dungeon3',FORM_PARAM_UNEDITABLE),
				'dfid_multi'		=> array('关卡Floor ID','',FORM_PARAM_EDITABLE),
				'rank_dfid_multi'	=> array('排名关卡Floor ID','',FORM_PARAM_EDITABLE),
				'cnt'				=> array('测试次数',1,FORM_PARAM_EDITABLE),
				'target_dungeon'	=> array('目标关卡','',FORM_PARAM_EDITABLE),
				'use_ticket'		=> array('使用扫荡卷 <span class="notes">（※周回可能なダンジョンかチェックはしません）</span>','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
			SEND_PARAMETER_POST,
		),
		//--------------------------------------------------
		TYPE_CHECK_RESOURCE_DIRECTORY => array(
			'Resource Directory确认（リソースディレクトリ確認）',
			array('action'	=> array('action名','admin_check_resource_directory',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		TYPE_ENTRY_DUMMY_RANKING => array(
			'添加Dummy排名数据（ダミーランキングデータ追加）：<span class="notes">ランキング全体に影響があるため注意。（集計は走らないため、集計処理が別途動作する必要があります）</span>',
			array(
				'action'		=> array('action名','admin_entry_dummy_ranking',FORM_PARAM_UNEDITABLE),
				'ranking_id'	=> array('排名ID',1,FORM_PARAM_EDITABLE),
				'num'			=> array('人数',100000,FORM_PARAM_EDITABLE),
				'score'			=> array('分数差',2,FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		TYPE_LAST_ERROR => array(
			'显示最新错误信息（最後のエラーを表示）',
			array('action'	=> array('action名','admin_last_error',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		TYPE_VIEW_CALENDAR => array(
			'活动日程表（イベントカレンダー）',
			array('action'	=> array('action名','admin_view_calendar',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		TYPE_SET_DEBUG_USER => array(
			'添加Debug用户（デバッグユーザー登録）',
			array(
				'action'		=> array('action名','admin_set_debug_user',FORM_PARAM_UNEDITABLE),
				'pid'			=> array('Player ID','',FORM_PARAM_EDITABLE),
				'ten_oid'		=> array('openId <span class="notes">（プレイヤーIDを入力すると無視されます）</span>','',FORM_PARAM_EDITABLE),
				't'				=> array('Device type<span class="notes">（プレイヤーIDを入力すると無視されます）</span>','',FORM_PARAM_DEVICETYPE),
				'maintenance'	=> array('维护状态可进入','',FORM_PARAM_EDITABLE),
				'cheat_check'	=> array('跳过作弊检测','',FORM_PARAM_EDITABLE),
				'drop_change'	=> array('掉落内容变更','',FORM_PARAM_EDITABLE),
				'round_prob'	=> array('扫荡卷掉率（万分率）',0,FORM_PARAM_EDITABLE),
				'plus_prob'		=> array('Plus碎片掉率（万分率）',0,FORM_PARAM_EDITABLE),
				'comment'		=> array('关于掉率','按 扫荡卷 →Plus碎片 的顺序判定、都不属于的情况按正常掉率计算',FORM_PARAM_UNEDITABLE),
				'skillup_change'	=> array('Skill up的掉率变更','',FORM_PARAM_EDITABLE),
				'skillup_prob'	=> array('Skill up几率（万分率）',0,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
		),
		//--------------------------------------------------
		TYPE_REDIS_SESSION_SEARCH_DELETE => array(
			'Session查询删除（セッション検索削除）',
			array(
				'action'	=> array('action名','admin_manage_redis',FORM_PARAM_UNEDITABLE),
				'rt'		=> array('执行type',4,FORM_PARAM_INVISIBLE),
				'rkey'		=> array('Player ID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_DEBUG,
		),
		//====================================================================================================
		// ユーザ操作系
		//====================================================================================================
		TYPE_LINK_USER => array(
			'用户数据管理',	array(),	JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_LINK,
		),

		//--------------------------------------------------
		TYPE_SEARCH_USER => array(
			'用户数据查询（Player ID）（ユーザデータ検索（プレイヤーID））',
			array(
				'action'	=> array('action名','admin_search_user',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_SEARCH_USER_BY_OPENID => array(
			'用户数据查询（ユーザデータ検索）（openID）',
			array(
				'action'	=> array('action名','admin_search_user',FORM_PARAM_UNEDITABLE),
				'ten_oid'	=> array('openId','',FORM_PARAM_EDITABLE),
				't'			=> array('DeviceType','',FORM_PARAM_DEVICETYPE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_SEARCH_USER_BY_DISPID => array(
			'用户数据查询（ユーザデータ検索）（表示用ID）',
			array(
				'action'	=> array('action名','admin_search_user',FORM_PARAM_UNEDITABLE),
				'disp_id'	=> array('dispId','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_CONVSERT_USER_ID => array(
			'用户ID转换（ユーザID変換）（表示ID←→内部ID）',
			array(
				'action'	=> array('action名','admin_convert_user_id',FORM_PARAM_UNEDITABLE),
				'disp_id'	=> array('表示ID か User ID','',FORM_PARAM_EDITABLE),
				't'			=> array('0：表示ID / 1：User ID',0,FORM_PARAM_EDITABLE),
				'dt'		=> array('IOS:'.UserDevice::TYPE_IOS.' / Android:'.UserDevice::TYPE_ADR,UserDevice::TYPE_ADR,FORM_PARAM_EDITABLE),
				'pt'		=> array('QQ:'.UserDevice::PTYPE_QQ.' / Wechat:'.UserDevice::PTYPE_WECHAT.' / 游客:'.UserDevice::PTYPE_GUEST,UserDevice::PTYPE_QQ,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_DELETE_USER => array(
			'用户数据删除（ユーザデータ削除）',
			array(
				'action'	=> array('action名','admin_delete_user',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		// TYPE_SEARCH_USERで編集できるようになったのでコメントアウト
		/*
		TYPE_ADD_USER_COIN => array(
			'コイン所持数変更',
			array(
				'action'	=> array('action名','admin_update_user_coin',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
				'c'			=> array('コイン','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		*/
		//--------------------------------------------------
		// TYPE_SEARCH_USERで編集できるようになったのでコメントアウト
		/*
		TYPE_ADD_USER_FRIPNT => array(
			'友情ポイント所持数変更',
			array(
				'action'	=> array('action名','admin_update_user_fripnt',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
				'fripnt'	=> array('友情ポイント','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		*/
		//--------------------------------------------------
		// TYPE_SEARCH_USERで編集できるようになったのでコメントアウト
		/*
		TYPE_ADD_USER_STAMINA => array(
			'スタミナ（ハート）数変更',
			array(
				'action'	=> array('action名','admin_update_user_stamina',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
				'amount'	=> array('スタミナ（ハート）','',FORM_PARAM_EDITABLE),
				'r'			=> array('アプリバージョン','722',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		*/
		//--------------------------------------------------
		TYPE_ADD_USER_PIECE => array(
			'碎片发放（带自动生成）（欠片直接付与（生成対応付き））',
			array(
				'action'	=> array('action名','admin_add_user_piece',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'piece_id'	=> array('碎片ID','',FORM_PARAM_EDITABLE),
				'amount'	=> array('个数','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_ADD_ALL_PIECES => array(
			'所有碎片发放（带自动生成）（欠片を全種類付与（生成対応付き））',
			array(
				'action'	=> array('action名','admin_add_all_pieces',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'amount'	=> array('个数',0,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_ADD_USER_CARD => array(
			'Monster直接发放（モンスター直接付与）',
			array(
				'action'	=> array('action名','admin_add_user_card',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'card_id'	=> array('CardID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		// 重複不可の仕様と競合するためコメントアウト
		/*
		TYPE_ADD_ALL_CARDS => array(
			'すべてのモンスター付与',
			array(
				'action'	=> array('action名','admin_add_all_cards',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		*/
		//--------------------------------------------------
		TYPE_CHECK_USER_CARD => array(
			'用户Monster信息变更（ユーザーモンスター情報変更）<span class="notes">（※レベルやプラス値の変更）</span>',
			array(
				'action'	=> array('action名','admin_check_user_card',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_ALL_CARDS_LEVEL_MAX => array(
			'升级所有持有的Monster到最高等级（すべての所持モンスターをレベルMAX）',
			array(
				'action'	=> array('action名','admin_all_cards_level_max',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_INIT_USER_CARDS => array(
			'初始化持有Monster（所持モンスター初期化）',
			array(
				'action'	=> array('action名','admin_init_user_cards',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//---------------------------------------------------
		TYPE_ALL_CARDS_FINAL => array(
			'所有Monster到最终形态（全モンスターを最終形態に変更）',
			array(
				'action'	=> array('action名','admin_all_cards_final',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//---------------------------------------------------
		TYPE_ADD_ALL_ADDITIONAL_CARDS => array(
			'生成满足重复可持有条件的Monster（重複所持条件を満たしたモンスターを生成）',
			array(
				'action'	=> array('action名','admin_add_all_additional_card',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		// access tokenなどの指定が難しいため、いったんコメントアウト
		/*
		TYPE_ADD_USER_GOLD => array(
			'魔法石付与と消費<span class="notes">（※アプリ側でMSDKから発行されるパラメータが必要）</span>',
			array(
				'action'	=> array('action名','admin_add_user_gold',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
				'g'			=> array('魔法石（負数消費）',100,FORM_PARAM_EDITABLE),
				'ten_at'	=> array('access token','',FORM_PARAM_EDITABLE),
				'ten_pt'	=> array('pay token','',FORM_PARAM_EDITABLE),
				'ten_pf'	=> array('pf','',FORM_PARAM_EDITABLE),
				'ten_pfk'	=> array('pfkey','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		*/
		//--------------------------------------------------
		TYPE_UPDATE_DUNGEON_FLOOR_CLEAR => array(
			'修改DungeonFloor的Clear状态1（ダンジョンフロアをクリア状態に変更1）',
			array(
				'action'	=> array('action名','admin_update_dungeon_floor',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'dfid'		=> array('Dungeon_floor_ID','',FORM_PARAM_EDITABLE),
				'star'		=> array('星级(1~3)',3,FORM_PARAM_EDITABLE),
				'clr'		=> array('クリア状態',1,FORM_PARAM_INVISIBLE),
				'reset'		=> array('初期化',0,FORM_PARAM_INVISIBLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_UPDATE_DUNGEON_FLOOR_CLEAR2 => array(
			'修改DungeonFloor的Clear状态2（ダンジョンフロアをクリア状態に変更2）<span class="notes">（※解放に必要なクリアダンジョン数は考慮しないので、条件があるダンジョンをクリア状態にするときは注意）</span>',
			array(
				'action'		=> array('action名','admin_update_dungeon_floor2',FORM_PARAM_UNEDITABLE),
				'pid'			=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
				'dfid_multi'	=> array('ダンジョンフロアID','',FORM_PARAM_EDITABLE),
				'star'			=> array('星级(1~3)',3,FORM_PARAM_EDITABLE),
				'clr'			=> array('クリア状態',1,FORM_PARAM_INVISIBLE),
				'reset'			=> array('初期化',0,FORM_PARAM_INVISIBLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
			SEND_PARAMETER_POST,
		),
		//--------------------------------------------------
		TYPE_UPDATE_DUNGEON_FLOOR_RESET => array(
			'初始化DungeonFloor的Clear状态（ダンジョンフロアのクリア状態を初期化）',
			array(
				'action'	=> array('action名','admin_update_dungeon_floor',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'dfid'		=> array('DungeonFloorID','',FORM_PARAM_INVISIBLE),
				'clr'		=> array('Clear状态',0,FORM_PARAM_INVISIBLE),
				'reset'		=> array('初期化',1,FORM_PARAM_INVISIBLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_ADD_USER_MAILS => array(
			'邮件, 体力礼物的发送・删除<span class="notes">（※魔法石给予）</span>（メール、スタミナプレゼントの送信・削除<span class="notes">（※魔法石の付与はこちらから）</span>）',
			array(
				'action'		=> array('action名','admin_add_user_mails',FORM_PARAM_UNEDITABLE),
				'pid'			=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'clear'			=> array('删除所有的Mail和体力礼物<br />（*设为1的话为删除操作）',0,FORM_PARAM_EDITABLE),
				'mail_msg' 		=> array('邮件 Message', 'DEBUG', FORM_PARAM_EDITABLE),
				'user_mails'	=> array('邮件发送份数（入力した数値分該当ユーザにメールを送信）<br />（※適当なフレンドから送信。<br />フレンドがいない場合適当なユーザとフレンドにする）',0,FORM_PARAM_EDITABLE),
				'presents'		=> array('体力礼物发送份数（入力した数値分該当ユーザスタミナプレゼントを送信）<br />（※適当なフレンドから送信。<br />フレンドがいない場合適当なユーザとフレンドにする）',0,FORM_PARAM_EDITABLE),
				'admin_mails'	=> array('从运营发送邮件（以下物品作为附件）', 0, FORM_PARAM_EDITABLE),
				'c'				=> array('　　├Coin数', 0, FORM_PARAM_EDITABLE),
				'g'				=> array('　　├魔法石数', 0, FORM_PARAM_EDITABLE),
				'fripnt'		=> array('　　├友情点数', 0, FORM_PARAM_EDITABLE),
				'sta'			=> array('　　├体力数', 0, FORM_PARAM_EDITABLE),
				'piece_id'		=> array('　　└碎片ID', 0, FORM_PARAM_EDITABLE),
				'piece_num'		=> array('　　　　└碎片数', 0, FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_ADD_USER_SUBSCRIPTION => array(
			'月額付与',
			array(
				'action'	=> array('action名','admin_add_user_subscription',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'tss'		=> array('付与日数',30,FORM_PARAM_EDITABLE),
				'ten_at'	=> array('access token','',FORM_PARAM_EDITABLE),
				'ten_pt'	=> array('pay token','',FORM_PARAM_EDITABLE),
				'ten_pf'	=> array('pf','',FORM_PARAM_EDITABLE),
				'ten_pfk'	=> array('pfkey','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		// TYPE_SEARCH_USERで編集できるようになったのでコメントアウト
		/*
		TYPE_CHANGE_NAME => array(
			'ユーザ名変更',
			array(
				'action'	=> array('action名','admin_change_name',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
				'name'		=> array('名前','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		*/
		//--------------------------------------------------
		TYPE_UPDATE_USER_VIP => array(
			'VIP变更',
			array(
				'action'	=> array('action名','admin_update_user_vip',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'vl'		=> array('VIP Level','0',FORM_PARAM_EDITABLE),
				'tpg'		=> array('总计魔法石消费数','0',FORM_PARAM_EDITABLE),
				'clb'		=> array('清楚Bonus的获取记录','0',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_ADD_FRIEND => array(
			'好友追加（※和指定的ID用户加为好友）（フレンド追加）',
			array(
				'action'	=> array('action名','admin_add_friend',FORM_PARAM_UNEDITABLE),
				'pid1'		=> array('PlayerID1','',FORM_PARAM_EDITABLE),
				'pid2'		=> array('PlayerID2 (範囲開始)','',FORM_PARAM_EDITABLE),
				'pidn'		=> array('PlayerID N (範囲終了)','',FORM_PARAM_EDITABLE),
				'del'		=> array('好友状态　(0:追加  1:解除)','0',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_CHECK_USER_MISSION => array(
			'用户任务情况确认/变更（ユーザーミッション状態確認/変更）',
			array(
				'action'	=> array('action名','admin_check_user_mission',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_INIT_USER_MISSION => array(
			'用户任务初始化（ユーザーミッション初期化）',
			array(
				'action'	=> array('action名','admin_init_user_mission',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_CHECK_USER_BOOK => array(
			'用户图鉴数据确认（ユーザー図鑑データ確認）',
			array(
				'action'	=> array('action名','admin_check_user_book',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_UPDATE_USER_RANKING => array(
			'用户排名信息更新（ユーザのランキング情報更新）',
			array(
				'action'	=> array('action名','admin_update_user_ranking',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('プレイヤーID','',FORM_PARAM_EDITABLE),
				'ranking_id'		=> array('ランキングID',1,FORM_PARAM_EDITABLE),
				'score'		=> array('スコア',0,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_DEFAULT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_CHECK_RECOMENDED_HELPER => array(
			'推荐战斗伙伴测试（助っ人冒険者ユーザー確認）',
			array(
				'action'	=> array('action名','admin_check_recommended_helper',FORM_PARAM_UNEDITABLE),
				'rank1'	=> array('等级范围','',FORM_PARAM_EDITABLE),
				'rank2'	=> array('等级范围','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_SET_RECOMENDED_HELPER => array(
			'推荐战斗伙伴登录（助っ人冒険者ユーザー登録）',
			array(
				'action'	=> array('action名','admin_set_recommended_helper',FORM_PARAM_UNEDITABLE),
				'pid'	=> array('登录PlayerID','',FORM_PARAM_EDITABLE),
				'rank'	=> array('登录的等级','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_USER_MONSTER_EVOLVE => array(
			'持有Monster进化（所持モンスター進化<span class="notes">（※分岐進化先選択不可）</span>）',
			array(
				'action'	=> array('action名','admin_user_monster_evolve',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
				'all'		=> array('全Monser Flag (指定0的话会参考cuid)',1,FORM_PARAM_EDITABLE),
				'cuid'		=> array('cuid单体进化(指定0的话进化所有的Monster)',0,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_CHECK_USER_GACHA_DISCOUNT => array(
			'用户扭蛋初次利用折扣记录确认/删除 （ユーザーガチャ初回割引利用データ確認/削除）',
			array(
				'action'	=> array('action名','admin_check_user_gacha_discount',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_UPDATE_QQ_VIP => array(
		'用户QQ会员设定（ユーザーQQ会員設定）',
		array(
			'action'	=> array('action名','admin_update_qq_vip',FORM_PARAM_UNEDITABLE),
			'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			'cl'		=> array('Debug信息清除 1:清除 0:不清除','0',FORM_PARAM_EDITABLE),
			'qv'		=> array('QQ会員状态設置(1小时有効) 0:普通用户 1:会員 2:超級会員','0',FORM_PARAM_EDITABLE),
			'cb'		=> array('QQ Bonus领取状态清除  1:清除  2:不清除','0',FORM_PARAM_EDITABLE),
			'qvp'		=> array('QQ会员Bonus可领取状态设置    1:可领取  0:不更改','0',FORM_PARAM_EDITABLE),
			'qsvp'		=> array('QQ超级会员Bonus可领取状态设置    1:可领取  0:不更改','0',FORM_PARAM_EDITABLE),
		),
		JSON_OUT_FALSE,
		FORM_INPUT_USE,
		HTML_TYPE_TABLEFORMAT,
		API_TYPE_USER,
		),
		//--------------------------------------------------
		TYPE_CHECK_USER_COUNT => array(
			'用户Count数据确认（ユーザーカウントデータ確認）',
			array(
				'action'	=> array('action名','admin_check_user_count',FORM_PARAM_UNEDITABLE),
				'pid'		=> array('PlayerID','',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,
			FORM_INPUT_USE,
			HTML_TYPE_TABLEFORMAT,
			API_TYPE_USER,
		),
		
		//====================================================================================================
		// データ確認系（※「admin_check_db_XXX / admin_dowonload_XXX」の形式で定義する）
		//====================================================================================================
		TYPE_LINK_DBDATA_AND_DLDATA => array(
			'DB/DL Master Data',	array(),	JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_LINK,
		),

		//--------------------------------------------------
		TYPE_CHECK_DB_CARD => array(
			'【数据】DB Card Data（DB カードデータ）',
			array('action'	=> array('action名','admin_check_db_card',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_DUNGEON => array(
			'【数据】DB Dungeon Data（DB ダンジョンデータ）',
			array('action'	=> array('action名','admin_check_db_dungeon',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_SKILL => array(
			'【数据】DB Skill Data（スキルデータ）',
			array('action'	=> array('action名','admin_check_db_skill',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_ENEMY_SKILL => array(
			'【数据】DB 敌人Skill Data（DB 敵スキルデータ）',
			array('action'	=> array('action名','admin_check_db_enemy_skill',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_PIECE => array(
			'【数据】DB 碎片Data（DB 欠片データ）',
			array('action'	=> array('action名','admin_check_db_piece',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_SCENE => array(
			'【数据】DB Scene Data（DB シーンデータ）',
			array('action'	=> array('action名','admin_check_db_scene',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		//--------------------------------------------------
		TYPE_CHECK_DUNGEDON_SALE => array(
			'【数据】Db Dungeon Sale Data（DB ダンジョンSaleデータ）',
			array('action'	=> array('action名','admin_check_db_dungeon_sale',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_MISSION => array(
			'【数据】DB 任务数据（DB ミッションデータ）',
			array('action'	=> array('action名','admin_check_db_mission',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA_DL,
		),
		
		//====================================================================================================
		// データ確認系（※DBテーブルのみ）
		//====================================================================================================
		TYPE_LINK_DBDATA_MASTER => array(
			'更新系 DB Master Data',	array(),	JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_LINK,
		),

		//--------------------------------------------------
		TYPE_CHECK_DB_VERSION => array(
			'【数据】DB Version管理（DB バージョン管理）',
			array('action'	=> array('action名','admin_check_db_version',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),

		//--------------------------------------------------
		TYPE_CHECK_DB_WAVE => array(
			'【数据】DB WAVE',
			array('action'	=> array('action名','admin_check_db_wave',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_WAVE_MONSTER => array(
			'【数据】DB WAVE_MONSTER',
			array('action'	=> array('action名','admin_check_db_wave_monster',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_TREASURE => array(
			'【数据】DB TREASURE',
			array('action'	=> array('action名','admin_check_db_treasure',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_DUNGEON_PLUS_DROP => array(
			'【数据】DB Dungeon PlusDrop',
			array('action'	=> array('action名','admin_check_db_dungeon_plus_drop',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_CHALLENGE_DUNGEON_BONUS => array(
			'【数据】DB Challange Bonus（DB チャレンジダンジョンボーナス）',
			array('action'	=> array('action名','admin_check_db_challenge_dungeon_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_DAILY_DUNGEON_BONUS => array(
			'【数据】DB 每日Dungeon Bonus（DB デイリーダンジョンボーナス）',
			array('action'	=> array('action名','admin_check_db_daily_dungeon_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),

		//--------------------------------------------------
		TYPE_CHECK_DB_RANKING_DUNGEON => array(
			'【数据】DB Ranking Dungeon（DB ランキングダンジョン）',
			array('action'	=> array('action名','admin_check_db_ranking_dungeon',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_RANKING_WAVE => array(
			'【数据】DB Ranking Wave（DB ランキングWave）',
			array('action'	=> array('action名','admin_check_db_ranking_wave',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_RANKING_WAVE_MONSTER => array(
			'【数据】DB Ranking WaveMonster（DB ランキングWaveMonster）',
			array('action'	=> array('action名','admin_check_db_ranking_wave_monster',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_RANKING_TREASURE => array(
			'【数据】DB Ranking Treasure（DB ランキングTreasure）',
			array('action'	=> array('action名','admin_check_db_ranking_treasure',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_RANKING_DUNGEON_PLUS_DROP => array(
			'【数据】DB Ranking Dungeon PlusDrop（DB ランキングダンジョンPlusDrop）',
			array('action'	=> array('action名','admin_check_db_ranking_dungeon_plus_drop',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_RANKING_DUNGEON_REWORD => array(
			'【数据】DB Ranking Dungeon Reward（DB ランキングダンジョンReward）',
			array('action'	=> array('action名','admin_check_db_ranking_reward',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_RANKING => array(
			'【数据】DB Ranking',
			array(
				'action'	=> array('action名','admin_check_db_ranking',FORM_PARAM_UNEDITABLE),
				'offset'	=> array('抽出開始位置',0,FORM_PARAM_EDITABLE),
				),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_LIMITED_RANKING => array(
			'【数据】DB Limited Ranking （DB リミテッドランキング）',
			array('action'	=> array('action名','admin_check_db_limited_ranking',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_EXCHANGE_LINEUP => array(
			'【数据】DB Ranking Point交换所Lineup （DB ランキングポイント交換所ラインナップ）',
			array('action'	=> array('action名','admin_check_db_exchange_lineup',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_EXCHANGE_ITEM => array(
			'【数据】DB Ranking Point交换所Item （DB ランキングポイント交換所アイテム）',
			array('action'	=> array('action名','admin_check_db_exchange_item',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),

		//--------------------------------------------------
		TYPE_CHECK_DB_EXTRA_GACHA => array(
			'【数据】DB 追加扭蛋（DB 追加ガチャ）',
			array('action'	=> array('action名','admin_check_db_extra_gacha',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_GACHA_PRIZE => array(
			'【数据】DB gacha_prizes',
			array('action'	=> array('action名','admin_check_db_gacha_prize',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_GACHA_DISCOUNT => array(
			'【数据】DB 初次扭蛋折扣Data（DB ガチャ初回割引データ）',
			array('action'	=> array('action名','admin_check_db_gacha_discount',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_LIMITED_BONUS => array(
			'【数据】DB Limited Bonus（DB リミテッドボーナス）',
			array('action'	=> array('action名','admin_check_db_limited_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_LIMITED_BONUS_GROUP => array(
			'【数据】DB Limited Bonus Group（DB リミテッドボーナスグループ）',
			array('action'	=> array('action名','admin_check_db_limited_bonus_group',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		// リミテッドボーナスへ機能を併合されて使用することがなくなったのでコメントアウト
		/*
		TYPE_CHECK_DB_LIMITED_BONUS_DUNGEON_BONUS => array(
			'【データ】DB リミテッドダンジョンボーナス',
			array('action'	=> array('action名','admin_check_db_limited_bonus_dungeon_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		*/
		//--------------------------------------------------
		TYPE_CHECK_DB_VIP_BONUS => array(
			'【数据】DB VIP报酬（DB VIP報酬）',
			array('action'	=> array('action名','admin_check_db_vip_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_VIP_COST => array(
			'【数据】DB VIP Cost（DB VIPコスト）',
			array('action'	=> array('action名','admin_check_db_vip_cost',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA,
		),
		//--------------------------------------------------
		TYPE_EDIT_DB_SHARE_DATA => array(
			'【数据】DB数据修改（DB データ編集）',
			array('action'	=> array('action名','admin_edit_share_data',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_GAME_DATA, SEND_PARAMETER_GET, true
		),
		
		//====================================================================================================
		// データ確認系（※DBテーブルのみ）
		//====================================================================================================
		TYPE_LINK_DBDATA => array(
			'其他DB数据',	array(),	JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_LINK,
		),

		//--------------------------------------------------
		TYPE_CHECK_DB_USER_DEVICE => array(
			'【数据】DB 用户一览（DB ユーザ一覧)',
			array('action'	=> array('action名','admin_check_db_user_device',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_DEBUG_USER => array(
			'【数据】DB Debug用户一览（DB デバッグユーザ一覧）',
			array('action'	=> array('action名','admin_check_db_debug_user',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_GAME_CONSTANT => array(
			'【数据】DB 常量定义（DB 定数定義）',
			array('action'	=> array('action名','admin_check_db_game_constant',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_TUTORIAL_CARD => array(
			'【数据】DB Card Data. 教程用（DB カードデータ（チュートリアル用））<span class="notes">（※確認専用のためAPIからは参照しません）</span>',
			array('action'	=> array('action名','admin_check_db_tutorial_card',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_VIEW_DB_TUTORIAL_CARD => array(
			'【数据】DB Card Data. 教程用（DB カードデータ（チュートリアル用））【Json格式导出】<span class="notes">（※確認専用のためAPIからは参照しません）</span>',
			array('action'	=> array('action名','admin_view_tutorial_card_data',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		// 使用することがなくなったのでコメントアウト
		/*
		TYPE_CHECK_DB_LOGIN_STREAK_BONUS => array(
			'【データ】DB ログインボーナス',
			array('action'	=> array('action名','admin_check_db_login_streak_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		*/
		//--------------------------------------------------
		TYPE_CHECK_DB_LOGIN_MESSAGE => array(
			'【数据】DB Login Message（DB ログインメッセージ）',
			array('action'	=> array('action名','admin_check_db_login_message',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_LOGIN_TOTAL_COUNT_BONUS => array(
			'【数据】DB 累计登陆奖励（DB 累計ログインボーナス）',
			array('action'	=> array('action名','admin_check_db_login_total_count_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_LOGIN_PERIOD => array(
			'【数据】DB 登陆期间管理（DB ログイン期間管理）',
			array('action'	=> array('action名','admin_check_db_login_period',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_ALL_USER_BONUS => array(
			'【数据】DB 全部用户Bonus管理（DB 全ユーザボーナス管理）',
			array('action'	=> array('action名','admin_check_db_all_user_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_TENCENT_BONUS => array(
			'【数据】DB IDIP用全用户Bonus管理 (DB IDIP用全ユーザボーナス管理）',
			array('action'	=> array('action名','admin_check_db_tencent_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_SIGNUP_LIMIT => array(
			'【数据】DB 用户注册上限管理（DB ユーザ登録上限管理）',
			array('action'	=> array('action名','admin_check_db_signup_limit',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_NG_WORD => array(
			'【数据】DB NG Word（DB NGワード）',
			array('action'	=> array('action名','admin_check_db_ng_word',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_LEVELUP_EXPERIENCE => array(
			'【数据】DB Level Up管理（DB ランクアップ管理）',
			array('action'	=> array('action名','admin_check_db_levelup',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		/*
		TYPE_CHECK_DB_SUBSCRIPTION_BONUS => array(
			'【データ】DB 月額課金ボーナス',
			array('action'	=> array('action名','admin_check_db_subscription_bonus',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		*/
		//--------------------------------------------------
		TYPE_CHECK_DB_SUPPORT_DATA => array(
			'【数据】DB Support Data（DB サポートデータ）',
			array('action'	=> array('action名','admin_check_db_support_data',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_CONVERT_TEXT => array(
			'【数据】DB 翻译（DB 翻訳）',
			array('action'	=> array('action名','admin_check_db_convert_text',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		//--------------------------------------------------
		TYPE_CHECK_DB_TB_ONLINE_CNT => array(
			'【数据】DB Online 用户统计（DB オンラインユーザ集計）',
			array('action'	=> array('action名','admin_check_db_tb_online_cnt',FORM_PARAM_UNEDITABLE),),
			JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_DATA,
		),
		
		//====================================================================================================
		// IDIP
		//====================================================================================================
		TYPE_LINK_IDIP => array(
			'IDIP相关',	array(),	JSON_OUT_FALSE,	FORM_INPUT_UNUSE,	HTML_TYPE_DEFAULT,	API_TYPE_LINK,
		),

		//--------------------------------------------------
		TYPE_TENCENT_UPDATE_LEVEL => array(
			'【IDIP测试】用户等级变更（ユーザレベル変更）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('Command ID','0x1003',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"Value"		=> array('等级（Clear Dungeon）増減数',"1",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_UPDATE_MONEY => array(
			'【IDIP测试】用户Coin变更（ユーザコイン変更）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('Command ID','0x1005',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"Value"		=> array('Coin増減数',"1",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_UPDATE_PHYSICAL => array(
			'【IDIP测试】用户体力变更（ユーザスタミナ（ハート）変更）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1007',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"Value"		=> array('体力増減数',"1",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',"123",FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_UPDATE_VIP => array(
			'【IDIP测试】用户VIP变更（ユーザVIP変更）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1009',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"Value"		=> array('VIP増減数',"1",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_DEL_ITEM => array(
			'【IDIP测试】用户Item删除（ユーザアイテム削除）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x100b',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"ItemId"	=> array('ItemID(碎片：9998)',"9998",FORM_PARAM_EDITABLE),
				"Uuid"		=> array('碎片ID',"10001",FORM_PARAM_EDITABLE),
				"ItemNum"	=> array('Item数目',"1",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_SEND_ITEM => array(
			'【IDIP测试】Item发放（アイテム付与）',
			array('action'	=> array('action名','admin_tencent_send_item',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x100d',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_EDITABLE),
				"Serial"	=> array('Serial',"Serial",FORM_PARAM_EDITABLE),
				"ItemList"	=> array('ItemList: |碎片BonusID　数量 碎片ID|...',"|9998 1 10001|9998 1 10008|",FORM_PARAM_EDITABLE),
				"MailTitle"	=> array('MailTitle',"title",FORM_PARAM_EDITABLE),
				"MailContent" => array('MailBody',"content",FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_SEND_BAT_MAIL => array(
			'【IDIP测试】所有用户Item发放（全員アイテム付与）',
			array('action'		=> array('action名','admin_tencent_send_bat_mail',FORM_PARAM_UNEDITABLE),
				'Cmdid'			=> array('CommandID','0x100f',FORM_PARAM_UNEDITABLE),
				"AreaId"		=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"		=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"BatItemList"	=> array('ItemList: |BonusID　数量　碎片ID　Level(0固定)|...','|9998 1 10001 0|9998 1 10008 0|',FORM_PARAM_EDITABLE),
                "MailTitle"	=> array('邮件标题', "title", FORM_PARAM_EDITABLE),
				"MailContent" => array('邮件内容', "content", FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_QUERY_USERINFO => array(
			'【IDIP测试】用户信息查询-OpenId查询（ユーザ情報チェック）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1011',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_QUERY_USR_INFO_BY_ROLENAME => array(
			'【IDIP测试】用户信息查询-用户名查询（ユーザ情報チェック(名前検索)）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1013',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"RoleName"	=> array('用户名',"NAME",FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_QUERY_USER_OPEN_ID => array(
			'【IDIP测试】用户OpenID查询-使用表示ID',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1041',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"PlayerId"	=> array('PlayerId',"IDIPUSER1",FORM_PARAM_EDITABLE)
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),

		//--------------------------------------------------
		TYPE_TENCENT_DO_MAIL => array(
			'【IDIP测试】邮件接口（单个）请求',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x103f',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"MailTitle"	=> array('MailTitle',"title",FORM_PARAM_EDITABLE),
				"MailContent"	=> array('MailContent',"content",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),

		//--------------------------------------------------
		TYPE_TENCENT_QUERY_BAN_INFO => array(
			'【IDIP测试】用户BAN信息（ユーザBAN情報）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1015',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_QUERY_ITEM_INFO => array(
			'【IDIP测试】用户Item信息（ユーザアイテム情報）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1017',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"PageNo"	=> array('Page数',0,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_BAN_USER => array(
			'【IDIP测试】BAN用户（BANユーザー）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1019',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition" => array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"Time"		=> array('時間(秒) -1:永久',-1,FORM_PARAM_UNEDITABLE),
				"Reason"	=> array('原因',"reason",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_UNBAN_USER => array(
			'【IDIP测试】UNBAN用户（UNBANユーザー）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x101b',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"IDIPUSER1",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_QUERY_USR_INFO => array(
			'【IDIP测试(AQ)】openid信息查询（openid情報チェック）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x101d',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_EDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"Roleid"	=> array('UserID',1,FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_SEND_MSG => array(
			'【IDIP测试(AQ)】邮件发送（メッセージ送信）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x101f',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"MsgContent" => array('邮件内容','TEST',FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_UPDATE_MONEY => array(
			'【IDIP测试(AQ)】Coin数变更（コイン数変更）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1021',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Value"		=> array('Coin増減数',1,FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_UPDATE_STONE => array(
			'【IDIP测试(AQ)】魔法石数变更（魔法石数変更）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1023',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Value"		=> array('魔法石増減数',1,FORM_PARAM_EDITABLE),
				"IsLogin"	=> array('要求登陆 1:yes 0:no',1,FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_BAN_PLAY => array(
			'【IDIP测试(AQ)】禁止功能（機能禁止）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1025',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Type"		=> array('禁止Dungeon种类　１：普通Dungeon　２：特殊Dungeon　３：Ranking Dungeon　９９：全部',1,FORM_PARAM_EDITABLE),
				"Time"		=> array('BAN秒数',60,FORM_PARAM_EDITABLE),
				"Tip"		=> array('原因',"reason",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_ZEROPROFIT => array(
			'【IDIP测试(AQ)】零収益',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1027',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Time"		=> array('秒数',60,FORM_PARAM_EDITABLE),
				"Reason"	=> array('原因',"reason",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_BAN_USR => array(
			'【IDIP测试(AQ)】BAN用户（BANユーザー）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1029',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"    => array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Time"		=> array('秒数',60,FORM_PARAM_EDITABLE),
				"Reason"	=> array('原因',"reason",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_RELIEVE_PUNISH => array(
			'【IDIP测试(AQ)】解除处罚（処罰解除）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x102b',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"RelieveZeroProfit"	=> array('零収益解除 1:yes 0:no',1,FORM_PARAM_EDITABLE),
				"RelievePlayAll"	=> array('機能禁止解除 1:yes 0:no',1,FORM_PARAM_EDITABLE),
				"RelieveBan"		=> array('BAN解除 1:yes 0:no',1,FORM_PARAM_EDITABLE),
				"RelieveBanJoinRank"=> array('Ranking禁止解除 1:yes 0:no',1,FORM_PARAM_EDITABLE),
				"Source"		=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_BAN_JOINRANK_OFFLINE => array(
			'【IDIP测试(AQ)】禁止参加Ranking（Offline）（ランキング（オフライン）参加禁止）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x102d',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"IsZeroRank"	=> array('Clear Ranking Data 1:yes 0:no',0,FORM_PARAM_EDITABLE),
				"Type"		=> array('禁止类型　 1:Ranking 99:全部',1,FORM_PARAM_EDITABLE),
				"Time"		=> array('秒数',60,FORM_PARAM_EDITABLE),
				"Tip"		=> array('原因',"reason",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_INIT_ACCOUNT => array(
			'【IDIP测试(AQ)】账号初始化（アカウント初期化)',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x102f',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_BAN_PLAY_ALL => array(
			'【IDIP测试(AQ)】禁止所有功能（全機能禁止）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1031',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Time"		=> array('秒数',60,FORM_PARAM_EDITABLE),
				"Tip"		=> array('原因',"reason",FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_SET_GAMESCORE => array(
			'【IDIP测试(AQ)】Score设置（スコア設置）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1033',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Type"		=> array('Score类型　 1:Ranking 99:全部',1,FORM_PARAM_EDITABLE),
				"Value"		=> array('設定値',0,FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_CLEAR_GAMESCORE => array(
			'【IDIP测试(AQ)】Score清除（クリアスコア）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1035',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"Type"		=> array('Score类型 1:Ranking 99:全部',1,FORM_PARAM_EDITABLE),
				"IsZero"	=> array('是否清除 1:yes 0:no',1,FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_AQ_DO_CLEAR_CARD => array(
			'【IDIP测试】ClearCard-Card清除（クリアカード）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1037',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"IsClearLevel"	=> array('CardLevel清除　 1:yes 0:no',1,FORM_PARAM_EDITABLE),
				"IsClearGrade"	=> array('未使用选项',0,FORM_PARAM_EDITABLE),
				"IsClearAwake"	=> array('未使用选项',0,FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
			TYPE_TENCENT_DO_UPDATE_FRIENDS => array(
					'【IDIP测试(AQ)】修改友情点',
					array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
							'Cmdid'		=> array('CommandID','0x1039',FORM_PARAM_UNEDITABLE),
							"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
							"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
							"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
							"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
							"Value"	    => array('修改值：+加-减',0,FORM_PARAM_EDITABLE),
							"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
							"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
					),
					JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
			),
		//--------------------------------------------------
		TYPE_TENCENT_DO_NOTICE_MAIL => array(
			'【IDIP测试(AQ)】全服邮件',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x103b',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"MailTitle"	=> array('邮件标题','',FORM_PARAM_EDITABLE),
				"MailContent"	=> array('邮件内容','',FORM_PARAM_EDITABLE),
				"Source"	=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_DO_MASKCHAT => array(
			'【IDIP测试】禁言（禁止用户发送消息邮件）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1043',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
				"RoleId"	=> array('UserID',1,FORM_PARAM_EDITABLE),
				"BanTime"		=> array('秒数',600,FORM_PARAM_EDITABLE),
				"BanReason"		=> array('原因',"reason",FORM_PARAM_EDITABLE),
				"Source"		=> array('Source',123,FORM_PARAM_UNEDITABLE),
				"Serial"	=> array('Serial',"serial",FORM_PARAM_UNEDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_DO_CLEAR_SPEAK => array(
			'【IDIP测试】清除发言（删除用户发送过的消息邮件）',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1045',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
		//--------------------------------------------------
		TYPE_TENCENT_DO_USER_ADD_WHITE => array(
			'【IDIP测试】玩家加入白名单',
			array('action'	=> array('action名','admin_tencent_idip',FORM_PARAM_UNEDITABLE),
				'Cmdid'		=> array('CommandID','0x1046',FORM_PARAM_UNEDITABLE),
				"AreaId"	=> array(getAreaIdComment(),Env::IDIP_AREA_QQ,FORM_PARAM_EDITABLE),
				"PlatId"	=> array('0:iOS 1:Android',1,FORM_PARAM_EDITABLE),
				"Partition"	=> array('Partition',1,FORM_PARAM_UNEDITABLE),
				"OpenId"	=> array('OpenId',"openid",FORM_PARAM_EDITABLE),
			),
			JSON_OUT_FALSE,	FORM_INPUT_USE,	HTML_TYPE_TABLEFORMAT,	API_TYPE_TENCENT,
		),
	);

	//====================================================================================================
	// 環境別設定
	//====================================================================================================

	// 環境別設定 array(名称、タイトル文字色ー、背景色、タイトル背景色、IP、ポート、リンクタイプ（※指定の数字以下の環境をリンク設定））
	$envNames = array(
		'padclocal'			=> array('LOCAL開発',		'#ecfcff',	'#ffffff',	'#0090f0',	'127.0.0.1',		8081,	2),
		'padcdev'			=> array('DEV開発',			'#eafaea',	'#ffffff',	'#008000',	'dev-pad-cn-ios-api-871501207.ap-northeast-1.elb.amazonaws.com',	null,	2),
		'padcstg1'			=> array('STG1開発',			'#ffefd6',	'#ffffff',	'#fa8c00',	'stg1-pad-cn-adr-api-710760711.ap-northeast-1.elb.amazonaws.com',	null,	2),
		'padcstg2'			=> array('STG2開発',			'#ffeeee',	'#ffffff',	'#cc0000',	'stg2-pad-cn-adr-api-859981376.ap-northeast-1.elb.amazonaws.com',	null,	2),
		'padcstg3'			=> array('STG3開発',			'#efeaff',	'#ffffff',	'#663399',	'stg3-pad-cn-adr-api-2047556555.ap-northeast-1.elb.amazonaws.com',	null,	2),
		'padcstg4'			=> array('STG4開発',			'#ddffff',	'#ffffff',	'#33bb99',	'stg4-pad-cn-adr-api-1500138288.ap-northeast-1.elb.amazonaws.com',	null,	2),
		'padctencentdev1'	=> array('Tencent DEV1開発',	'#ccdfff',	'#ffffff',	'#6090c0',	'182.254.214.84',	null,	1),
		'padctencentdev2'	=> array('Tencent DEV2開発',	'#cff9df',	'#ffffff',	'#609980',	'182.254.159.94',	null,	1),
		'padctencentdev3qq'		=> array('Tencent DEV3 QQ開発',		'#ffddbb',	'#ffffff',	'#dd8866',	'115.159.38.33',	null,	1),
		'padctencentdev3wechat'	=> array('Tencent DEV3 Wechat開発',	'#ffddee',	'#ffffff',	'#dd6688',	'115.159.122.155',	null,	1),
		'padcdyspace'		=> array('DYspace DEV1开发', '#FFFAD1', '#ffffff', '#3BD8FF', '115.159.28.48', null, 2),
		'padcdyspace_zy'		=> array('DYspace DEV3开发', '#7A9A95', '#ffffff', '#3F2860', '192.168.0.188', null, 2),
	);

	$envName		= 'unknown';
	$bodyColor		= '#ffffff';
	$titleColor		= '#ffffff';
	$titleBgColor	= '#666666';
	$linkType		= 0;

	$env = Env::ENV;
	if(isset($envNames[$env]))
	{
		$envName		= $envNames[$env][TOOL_ENV_NAME];
		$bodyColor		= $envNames[$env][TOOL_BODY_COLOR];
		$titleColor		= $envNames[$env][TOOL_TITLE_COLOR];
		$titleBgColor	= $envNames[$env][TOOL_TITLE_BGCOLOR];
		$linkType		= $envNames[$env][TOOL_LINK_TYPE];
	}

	//====================================================================================================
	// DebugTOP
	//====================================================================================================

	// 各環境DebugTOPリンク
	$urllink = '';
	$urllinks = array();
	$urlViewFlag = false;
	foreach($envNames as $envname => $envinfo)
	{
		$_envName		= $envinfo[TOOL_ENV_NAME];
		$_envServerName	= $envinfo[TOOL_SERVER_NAME];
		$_envServerPort	= $envinfo[TOOL_SERVER_PORT];
		$_envLinkType	= $envinfo[TOOL_LINK_TYPE];

		// url整形
		$_url = 'http://' . $_envServerName;
		if($_envServerPort)
		{
			$_url .= ':' . $_envServerPort;
		}
		$_url .= '/' . REQUEST_URL_ADMIN;

		// アクセス中の環境
		if($_SERVER['SERVER_NAME'] == $_envServerName && (!$_envServerPort || ($_envServerPort == $_SERVER['SERVER_PORT'])))
		{
			$_envName = '<span class="selected_column">' . $_envName . '</span>';
			$urlViewFlag = true;
		}

		// 指定した環境のみ相互リンクを設置する
		if($linkType >= $_envLinkType)
		{
			$urllinks[] = '<a href="' . $_url . '" class="a_header">' . $_envName . '</a>';
		}
	}

	// 表示用整形（※想定している環境のみリンクを表示）
	if($urlViewFlag)
	{
		$urllink = getDataArray2TableFormat($urllinks,'環境別TOP');
	}

	//====================================================================================================
	// Debug機能一覧表示
	//====================================================================================================
	$backlink = '';
	$linkList = '';
	$headerLink = '';
	if(!isset($_GET['action']))
	{
		$headerLinks = array();
		$linkList .= '<ul>';
		foreach($requestInfos as $_requestType => $_requestInfo)
		{
			$linkName		= $_requestInfo[0];
			$requestParams	= $_requestInfo[1];
			$jsonOutFlag	= $_requestInfo[2];
			$inputFormFlag	= $_requestInfo[3];
			$viewType		= $_requestInfo[4];
			$apiType		= $_requestInfo[5];
			$sendParameter	= isset($_requestInfo[6]) ? $_requestInfo[6] : SEND_PARAMETER_GET;
			$hidden			= isset($_requestInfo[7]) ? $_requestInfo[7] : false;
			
			if ($hidden) {
				continue;
			}
			
			// リクエストパラメータ整形
			$requestStr = '';
			$requestParamArrays = array(
				'request_type=' . $_requestType,
			);
			foreach($requestParams as $_key => $_value)
			{
				$requestParamArrays[] = $_key . '=' . $_value[1];
			}
			$requestStr = join('&',$requestParamArrays);

			$columnKey = $columnList[$apiType];

			// リクエストURLを調整
			$requestUrl4Html = 'api_admin.php?' . $requestStr . '&inputform=' . $inputFormFlag;
			$requestUrl4Json = 'api_admin.php?' . $requestStr . '&output_type=1';

			if($apiType == API_TYPE_LINK)
			{
				$_urllinkname	= sprintf("link%d",$_requestType);
				$linkList 		.= '<a name="' . $_urllinkname . '"></a>';
				$linkList		.= '<a href="#top"><li class="' . $columnKey . '">■ ' . $linkName . '</li></a>';

				$headerLinks[]	= '<a href="#' . $_urllinkname . '" class="a_header">' . $linkName . '</a>';
			}
			else
			{
				$linkList	.= '<li class="' . $columnKey . '"><a href="' . $requestUrl4Html . '">' . $linkName . '</a>';

				// 結果をJSONフォーマットで確認したい場合、別タブで出力するURLをセット
				if($jsonOutFlag)
				{
					$linkList .= ' / <a href="' . $requestUrl4Json . '" target="_blank">' . $linkName . '（json出力）</a>';
				}

				// データ確認用（※DB参照とDLデータ参照）
				if($apiType == API_TYPE_DATA_DL)
				{
					$_tmpURL		= preg_replace('/_check_db_/', '_download_', $requestUrl4Html);
					$_tmpLinkName	= preg_replace('/DB/', 'DL', $linkName);
					$linkList		.= ' / <a href="' . $_tmpURL . '">' . $_tmpLinkName . '</a>';
				}
				$linkList .= '</li>';
			}

		}
		$linkList .= '</ul>';

		// 表示用整形
		$headerLink = getDataArray2TableFormat($headerLinks,'Links:');
	}
	//====================================================================================================
	// 対象のDebug機能実行
	//====================================================================================================
	else
	{
		$accessType = EXEC_DIRECT_ACCESS;

		if(isset($_GET['request_type']))
		{
			$requestType	= $_GET['request_type'];
			$requestInfo	= $requestInfos[$requestType];
			$linkName		= $requestInfo[0];
			$requestParams	= $requestInfo[1];
			$jsonOutFlag	= $requestInfo[2];
			$inputFormFlag	= $requestInfo[3];
			$viewType		= $requestInfo[4];
			$apiType		= $requestInfo[5];
			$sendParameter	= isset($requestInfo[6]) ? $requestInfo[6] : SEND_PARAMETER_GET;

			$accessType		= EXEC_INDIRECT_ACCESS;

			$columnKey = $columnList[$apiType];
		}

		// form形式で整形
		if(isset($_GET['inputform']) && $_GET['inputform'] == FORM_INPUT_USE)
		{
			// どの機能を実行しているか引き回すためrequest_typeをセット
			$requestParams['request_type'] = array('request_type',$requestType,FORM_PARAM_INVISIBLE);

			$formList	= getFormList(REQUEST_URL_ADMIN, $requestParams, $sendParameter);
			$linkList	.= '<div class="' . $columnKey . '">' . $linkName . '</div><hr />'
						. '<table border="1">'
						. $formList
						. '</table>';
		}
		else
		{
			try
			{
				// 選択したデバッグ機能を実行
				if(isset($_GET['action']))
				{
					$actionName = $_GET['action'];
					$actionName = underscore2Camel($actionName);
					if(!class_exists($actionName))
					{
						throw new PadException(RespCode::UNKNOWN_ERROR);
					}
					$action = new $actionName();

					$response = $action->process($_GET, $_SERVER['REQUEST_URI'], $_POST);
				}
			}
			catch(Exception $e)
			{
				if($e instanceof PadException)
				{
					$code = $e->getCode();
					$response = json_encode(array('res'=>$code));
				}
				else
				{
					$response = json_encode(array('res'=>RespCode::UNKNOWN_ERROR));
				}

				// 動作確認用ログ
				global $logger;
				$error = print_r($e, true);
				if ( strpos( $error, '*RECURSION*' ) === FALSE )
				{
					$error = var_export($e, true);
				}
				$error_msg = $e->getMessage();

				if ( isset( $logger ) )
				{
					if(mb_strpos($error_msg, " __NO_TRACE") !== false)
					{
						$error_msg = str_replace(" __NO_TRACE", "", $error_msg);
						$logger->log("RespCode:" . $e->getCode() . " " . $e->getFile() . "(" . $e->getLine() . ") 'message' => '" . $error_msg . "'", Zend_Log::NOTICE);
					}
					elseif(mb_strpos($error_msg, " __NO_LOG") === false)
					{
						$logger->log("Exception: " . $error, Zend_Log::ERR);
					}
				}
			}

			// 表示フォーマット調整（※Jsonデータをそのまま表示するか、Htmlのテーブル形式で表示するか）
			if($accessType == EXEC_INDIRECT_ACCESS && $viewType == HTML_TYPE_TABLEFORMAT)
			{
				$response = getHtmlTableFromJson($response);
			}

			if(isset($linkName) && isset($columnKey))
			{
				$linkList	.= '<div class="' . $columnKey . '">' . $linkName . '</div><hr />';
			}

			$linkList	.= '<div style="background-color:#ffffff; color:#000000;">'
						. $response
						. '</div>';
		}
	}

	// HTML整形
	$htmlList = '';
	$htmlList	.= '<h2 style="'
				. ' background-color:' . $titleBgColor . ';'
				. ' padding:10px;'
				. ' border-radius:5px;'
				. ' color:' . $titleColor . ';'
				. ' font-style:oblique;'
				. ' ">PADC Debug Tool （' . $envName . '環境）</h2>';
	$htmlList	.=  '<hr />';

	$nowtime	= Padc_Time_Time::getDate();
	$timezone	= date_default_timezone_get();

	$htmlList	.=  '<div class="title_line">' . $nowtime . ' （ ' . $timezone . ' ）' . '</div>';
	$htmlList	.=  '<hr />';

	if($urllink)
	{
		$htmlList	.=  '<div class="title_line">' . $urllink . '</div>';
	}
	if($headerLink)
	{
		$htmlList	.=  '<div class="title_line">' . $headerLink . '</div>';
	}
	$htmlList	.=  '<hr />';
	$htmlList	.= $linkList;

	//====================================================================================================
	// 出力タイプに合わせて出力
	//====================================================================================================
	if((isset($_GET['output_type']) && $_GET['output_type'] == 1) || (isset($accessType) && $accessType == EXEC_DIRECT_ACCESS))
	{
		echo($response);
	}
	else
	{
		// 戻るリンク
		if(isset($_GET['backlink']) && $_GET['backlink'])
		{
			$backurl	= REQUEST_URL_ADMIN
						. '?action=' . $_GET['action']
						. '&inputform=' . FORM_INPUT_USE
						. '&request_type=' . $_GET['request_type'];
			$backlink	= '<a href="'.$backurl.'">≫ 返回</a><br />';
		}

		$htmlList	.= '<hr />'
					. $backlink
					. '<a href="./api_admin.php">≫ 回到功能一览</a>';

		printHtml($htmlList,$bodyColor);
	}
	exit;

	//====================================================================================================
	// 各種function定義
	//====================================================================================================

	/**
	 * ==================================================
	 * action名のパース
	 * @param string $str
	 * @return string
	 * ==================================================
	 */
	function underscore2Camel($str)
	{
		$words = explode('_', strtolower($str));
		$return = '';
		foreach ($words as $word)
		{
			$return .= ucfirst(trim($word));
		}
		return $return;
	}

	/**
	 * ==================================================
	 * html出力
	 * @param string $htmlList
	 * @param string $bodyColor
	 * ==================================================
	 */
	function printHtml($htmlList,$bodyColor)
	{
		$printList	= '<html>'
					. '<head><style type="text/css">'
					. 'body{font-size:10pt; background:'.$bodyColor .';}'
					. 'table{border-collapse: collapse; font-size:10pt;}'
					. 'td{background-color:#ffffff; color:#000000; padding:2px 0 2px 0;	}'
//					. 'li{list-style-type:none; border-left:solid 3px #6699ff; border-bottom:solid 1px #6699ff; background:#ddeeff; margin:5px; padding:3px; font-weight:bold;}'
					. 'li{list-style-type:none;}'
					. 'a{text-decoration:none; color:#333333;}'
					. 'hr{border-top:0;	border-bottom:1px solid #666666; padding:0px;}'

					. '.column_line_link{		border-left:solid 3px #999966; border-bottom:solid 1px #999966; background:#ffffcc; padding:5px; font-weight:bold;}'
					. '.column_line_admin{		border-left:solid 3px #ff9999; border-bottom:solid 1px #ff9999; background:#ffdddd; margin:5px; padding:3px; font-weight:bold;}'
					. '.column_line_debug{		border-left:solid 3px #6666ff; border-bottom:solid 1px #6666ff; background:#e5e5ff; margin:5px; padding:3px; font-weight:bold;}'
					. '.column_line_user{		border-left:solid 3px #6699ff; border-bottom:solid 1px #6699ff; background:#ddeeff; margin:5px; padding:3px; font-weight:bold;}'
					. '.column_line_data{		border-left:solid 3px #448844; border-bottom:solid 1px #448844; background:#88dd88; margin:5px; padding:3px; font-weight:bold;}'
					. '.column_line_game_data{	border-left:solid 3px #448844; border-bottom:solid 1px #448844; background:#5fcc5f; margin:5px; padding:3px; font-weight:bold;}'
					. '.column_line_data_dl{	border-left:solid 3px #669900; border-bottom:solid 1px #669900; background:#cff99f; margin:5px; padding:3px; font-weight:bold;}'
					. '.column_line{			border-left:solid 3px #aacc88; border-bottom:solid 1px #aacc88; background:#eeffcc; margin:5px; padding:3px; font-weight:bold;}'

					. '.caution{	color:#ff0000; font-weight:bold;}'
					. '.notes{		color:#cc0000; font-weight:bold;}'
					. '.text_jp{	color:#008833; font-weight:bold;}'
					. '.text_view{	color:#333333;}'
					. '.common{		color:#333333;	margin:2px;	}'

					. '.title_line{		font-weight:bold;	font-size:10pt;	margin:0px;	}'
					. '.explain_list{	color:#333333;	background-color:#ffffff;	border:1px solid #999999;	padding:5px;	}'
					. '.explain_ul{		margin:0px;	}'

					. '.base_td{	border:2px solid #333333;	}'
					. '.default_tr{	height:50px;	background-color:#ffffff;}'
					. '.wday_tr{	text-align:center;	background-color:#ffffcc;}'
					. '.common_td{	text-align:center;	background-color:#ffffcc;}'
					. '.date_line{	text-align:left;	padding-left:3px;	background-color:#aaccff;	border-bottom:1px solid #999999;}'
					. '.sun_line{	text-align:left;	padding-left:3px;	background-color:#ffcccc;	border-bottom:1px solid #999999;}'
					. '.sat_line{	text-align:left;	padding-left:3px;	background-color:#88aaff;	border-bottom:1px solid #999999;}'
					. '.today_line{	text-align:left;	padding-left:3px;	background-color:#ffff66;	border-bottom:1px solid #999999;}'
					. '.fill_line{	background-color:#cccccc;	padding-left:3px;	border-bottom:1px solid #999999;}'

					. '.month_link{	color:#666666;	text-align:center;	margin:5px;}'

					. '.default_mark{	color:#555555; font-weight:bold;}'
					. '.start_mark{		color:#4488ff; font-weight:bold;}'
					. '.end_mark{		color:#ff4400; font-weight:bold;}'

					. '.bonus_info{	padding:2px;	margin:3px;}'
					. '.bonus_type_line{		background-color:#ccff88;	padding:3px;	color:#448800;	margin:3px;	font-weight:bold; width:700px;	}'
					. '.ranking_dungeon_line{	background-color:#ffcc88;	padding:3px;	color:#ff6633;	margin:3px;	font-weight:bold; width:700px;	}'
					. '.login_message_line{		background-color:#99ccff;	padding:3px;	color:#0066cc;	margin:3px;	font-weight:bold; width:700px;	}'
					. '.alluserbonus_line{		background-color:#ffcccc;	padding:3px;	color:#ff3333;	margin:3px;	font-weight:bold; width:700px;	}'
					. '.extragacha_line{		background-color:#bbaaff;	padding:3px;	color:#9933ff;	margin:3px;	font-weight:bold; width:700px;	}'

					. '.selected_column{	background-color:#ccff99; color:#339966;	}'

					. '.td_link{	padding:5px;	font-weight:bold;	color:#666666;	}'
					. '.td_column{	padding:5px;	font-weight:bold;	color:#333333;	background-color:#ffffcc;	}'
					. '.a_header{	color:#666666;}'
					. '.table_span{	margin:3px;	}'

					. '</style></head>'
					. '<body>'
					. '<a name="top"></a>'
					. $htmlList
					. '<div><a href="#top">▲回到页面最上面</a></div>'
					. '</body></html>';
		// html出力
		header("Content-Type: text/html; charset=UTF-8");
		echo($printList);
	}

	/**
	 * ==================================================
	 * Form整形
	 * @param string $requestUrl
	 * @param array $requestParams
	 * @return string
	 * ==================================================
	 */
	function getFormList($requestUrl,$requestParams, $sendParameter=SEND_PARAMETER_GET)
	{
		if($sendParameter)
		{
			$formList	= '<form action="'.$requestUrl.'" method="post">';
		}
		else
		{
			$formList	= '<form action="'.$requestUrl.'" method="get">';
		}

		foreach($requestParams as $_key => $_value)
		{
			$columnName		= $_value[0];
			$defaultValue	= $_value[1];
			$inputType		= $_value[2];

			$_viewName	= '';
			$_inputType	= 'text';

			// 編集させない項目
			if($inputType == FORM_PARAM_UNEDITABLE)
			{
				$_viewName	= $defaultValue;
				$_inputType = 'hidden';
			}

			// デバイスタイプ用にラジオボタン形式で表示
			if($inputType == FORM_PARAM_DEVICETYPE)
			{
				$_formdata = getDeviceTypeForm($_key);
			}
			// 表示しない
			elseif($inputType == FORM_PARAM_INVISIBLE)
			{
				$columnName	= '';
				$_formdata	= '<input type="hidden" name="' . $_key . '" value="' . $defaultValue . '" />';
			}
			// テキストBOX
			elseif($inputType == FORM_PARAM_TEXTBOX)
			{
				$columnName	= '';
				$_formdata	= '<textarea name="' . $_key . '" cols="100" rows="20">' . $defaultValue . '</textarea>';
			}
			// 表示（入力 or 参照のみ）
			else
			{
				// 特定のキーの場合プルダウン表示

				// 欠片
				if($_key == 'piece_id')
				{
					$_formdata	= getPieceSelectForm();
				}
				// モンスター
				else if($_key == 'card_id')
				{
					$_formdata	= getCardSelectForm();
				}
				// ダンジョンフロア
				elseif($_key == 'dfid')
				{
					$_formdata	= getDungeonFloorSelectForm();
				}
				elseif($_key == 'dfid_multi')
				{
					$_formdata	= getDungeonFloorSelectMultiForm();
				}
				// ランキングダンジョンフロア
				elseif($_key == 'rank_dfid')
				{
					$_formdata	= getRankingDungeonFloorSelectForm();
				}
				elseif($_key == 'rank_dfid_multi')
				{
					$_formdata	= getRankingDungeonFloorSelectMultiForm();
				}
				// ガチャタイプ
				elseif($_key == 'gacha_type')
				{
					$_formdata	= getGachaTypeSelectForm();
				}
				// マスターデータ
				elseif($_key == 'target_table')
				{
					$_formdata = getMasterDataCheckBoxForm();
				}
				// 対象ダンジョン
				elseif($_key == 'target_dungeon')
				{
					$_formdata	= getTargetDungeonForm($_key);
				}
				// チケット利用
				elseif($_key == 'use_ticket')
				{
					$_formdata	= getUseSelectForm($_key);
				}
				// メンテナンス突破、チート判定スルー、DROP内容変更、スキルアップ確率変更
				elseif($_key == 'maintenance' || $_key == 'cheat_check' || $_key == 'drop_change' || $_key == 'skillup_change')
				{
					$_formdata	= getOffOnSelectForm($_key);
				}
				// CSV出力
				elseif($_key == 'output_csv')
				{
					$_formdata	= getRadioButtonTypeForm($_key, array('無し','有り','CSVのみ表示'));
				}
				// 表示のみコメント
				elseif($_key == 'comment')
				{
					$_formdata	= $_viewName;
				}
				else
				{
					$_formdata = $_viewName . '<input type="' . $_inputType . '" name="' . $_key . '" value="' . $defaultValue . '" />';
				}
			}

			$formList	.= '<tr>'
						. '<td>' . $columnName . '</td>'
						. '<td>' . $_formdata . '</td>'
						. '<tr>';
		}
		$formList	.= '<tr><td><input type="hidden" name="backlink" value="1" /><input type="submit" value="実行" /></td></tr>'
					. '</form>';
		return $formList;
	}

	/**
	 * ==================================================
	 * デバイスタイプ選択用ラジオボタン（※デフォルトでIOSにチェックをいれておく）
	 * @param string $inputkey
	 * @return string
	 * ==================================================
	 */
	function getDeviceTypeForm($inputkey)
	{
		$params = array(
			User::TYPE_IOS		=> array('IOS',' checked'),
			User::TYPE_ANDROID	=> array('Android',''),
		);

		$list = '';
		foreach($params as $_deviceType => $_value)
		{
			$_name		= $_value[0];
			$_checked	= $_value[1];
			$list		.= $_name . '：<input type="radio" name="' . $inputkey . '" value="' . $_deviceType . '"' . $_checked . ' />';
		}
		return $list;
	}

	/**
	 * ==================================================
	 * jsonフォーマットのデータをHtmlのTable形式に整形
	 * @param json $jsondata
	 * @return string
	 * ==================================================
	 */
	function getHtmlTableFromJson($jsondata)
	{
		$list = '';
		$tmpArray = json_decode($jsondata,true);
		if(isset($tmpArray['format']) && $tmpArray['format'] == 'array')
		{
			unset($tmpArray['format']);
			foreach($tmpArray as $key => $value)
			{
				$list .= '<div class="column_line">■ ' . $key . '</div>';
				if(isset($value['format']) && $value['format'] == 'array')
				{
					unset($value['format']);
// 					$list .= getHtmlTableFromArray2($value);

					//INFO:「table**」キーがあったら別々にテーブル表示させる
					$keys = array_keys($value);
					$table_keys = preg_grep("/^table[0-9]+$/", $keys);
					if ($table_keys) {
						foreach($table_keys as $table_key) {
							$list .= getHtmlTableFromArray2($value[$table_key]);
						}
					}
					else {
						$list .= getHtmlTableFromArray2($value);
					}
				}
				else if(isset($value['format']) && $value['format'] == 'html')
				{
					unset($value['format']);
					$list .= $value[0];
				}
				else
				{
					$list .= getHtmlTableFromArray($value);
				}
			}
		}
		else
		{
			$list .= getHtmlTableFromArray($tmpArray);
		}
		return $list;
	}

	/**
	 * ==================================================
	 * key => value形式の配列データをTableタグに整形
	 * @param array $tmpArray
	 * @return string
	 * ==================================================
	 */
	function getHtmlTableFromArray($tmpArray=array())
	{
		$list	= '';
		$list	.= '<table border="1" style="margin:10px 5px 10px 10px;">'
				. '<tr style="background:#ffffcc;">'
				. '<th>項目</th>'
				. '<th>詳細</th>'
				. '</tr>';
		foreach($tmpArray as $key => $value)
		{
			if(is_array($value))
			{
				$value = print_r($value,true);
			}

			$list	.= '<tr>'
					. '<td>' . $key . '</td>'
					. '<td>' . $value . '</td>'
					. '</tr>';
		}
		$list .= '</table>';

		return $list;
	}

	/**
	 * ==================================================
	 * 先頭にカラム名、それ以降にデータをセットした形式の配列データをTableタグに整形
	 * @param array $tmpArray
	 * @return string
	 * ==================================================
	 */
	function getHtmlTableFromArray2($tmpArray=array())
	{
		$list	= '';
		$list	.= '<table border="1" style="margin:10px 5px 10px 10px;">';
		$tmp = 0;
		foreach($tmpArray as $key => $value)
		{
			if($tmp)
			{
				$tr		= '<tr>';
				$td		= '<td>';
				$tdend	= '</td>';
			}
			else
			{
				$tr		=  '<tr style="background:#ffffcc;">';
				$td		= '<th>';
				$tdend	= '</th>';
				$tmp	= 1;
			}

			$list .= $tr;
			foreach($value as $v)
			{
				if(is_array($v))
				{
//					$v = print_r($v,true);
					$v = arrayToTableData($v);
				}
				$list .= $td . $v . $tdend;
			}
			$list .= '</tr>';
		}
		$list .= '</table>';

		return $list;
	}

	/**
	 * ==================================================
	 * マスターデータの種類
	 * ==================================================
	 */
	function getMasterDataCheckBoxForm()
	{
		$datas = array(
			DownloadMasterData::ID_SKILLS			=> '技能',
			DownloadMasterData::ID_ENEMY_SKILLS		=> '敌人技能',
			DownloadMasterData::ID_DUNGEONS_VER2	=> 'Dungeons',
			DownloadMasterData::ID_CARDS_VER4		=> 'Cards',
			DownloadMasterData::ID_DUNGEON_SALES	=> '购买关卡',
			DownloadMasterData::ID_PIECES			=> '碎片',
			DownloadMasterData::ID_SCENES			=> 'Scene',
			DownloadMasterData::ID_MISSIONS			=> 'Mission',
			// #PADC_DY# ----------begin----------
			DownloadMasterData::ID_ROADMAP			=> '升级解锁信息',
			DownloadMasterData::ID_ACTIVITY			=> '运营活动',
			DownloadMasterData::ID_GACHA_LINEUP		=> '扭蛋限定一览',
			DownloadMasterData::ID_PASSIVE_SKILL	=> '觉醒技能',
			DownloadMasterData::ID_CARNIVAL         => '新手嘉年华',  //##新手嘉年华
			// #PADC_DY# ----------end----------
		);

		$list = '';
		foreach($datas as $k => $v)
		{
			$list .= '<input type="checkbox" name="target_tables[]" value="' . $k . '" />' . $k . ':' . $v . '<br />';
		}
		return $list;
	}

	/**
	 * ==================================================
	 * 欠片用プルダウン
	 * ==================================================
	 */
	function getPieceSelectForm()
	{
		return getTargetClassSelectForm('Piece','piece_id');
	}

	/**
	 * ==================================================
	 * カード用プルダウン
	 * ==================================================
	 */
	function getCardSelectForm()
	{
		return getTargetClassSelectForm('Card','card_id');
	}

	/**
	 * ==================================================
	 * ダンジョンフロア
	 * ==================================================
	 */
	function getDungeonFloorSelectForm()
	{
		return getTargetClassSelectForm('DungeonFloor','dfid');
	}
	function getDungeonFloorSelectMultiForm()
	{
		return getTargetClassSelectForm('DungeonFloor','dfid[]', true);
	}

	/**
	 * ==================================================
	 * ランキングダンジョンフロア
	 * ==================================================
	 */
	function getRankingDungeonFloorSelectForm()
	{
		return getTargetClassSelectForm('RankingDungeonFloor','rank_dfid');
	}
	function getRankingDungeonFloorSelectMultiForm()
	{
		return getTargetClassSelectForm('RankingDungeonFloor','rank_dfid[]', true);
	}

	/**
	 * ==================================================
	 * ガチャタイプ
	 * ==================================================
	 */
	function getGachaTypeSelectForm()
	{
		$types = array(
			Gacha::TYPE_FRIEND	=> '友情扭蛋',
			//Gacha::TYPE_CHARGE	=> 'レアガチャ',
			Gacha::TYPE_EXTRA	=> '追加扭蛋（IP联动）',
			Gacha::TYPE_PREMIUM	=> '魔法石扭蛋',
			Gacha::TYPE_TUTORIAL	=> '教程扭蛋',
		);
		$list = getTargetArraySelectForm($types,'gacha_type');
		return $list;
	}

	/**
	 * ==================================================
	 * ラジオボタン選択用フォーム（※最初の項目がチェックされた状態になります）
	 * @param string $inputkey
	 * @return array $items
	 * ==================================================
	 */
	function getRadioButtonTypeForm($inputkey, $items)
	{
		$items = array_merge($items);

		$list = '';
		foreach($items as $_num => $_value)
		{
			$list		.= $_value . '：<input type="radio" name="' . $inputkey . '" value="' . $_num . '"' . ($_num == 0 ? ' checked' : '') . ' /> ';
		}
		return $list;
	}

	/**
	 * ==================================================
	 * 対象ダンジョン
	 * ==================================================
	 */
	function getTargetDungeonForm($inputkey)
	{
		$items = array(
			'通常',
			'ランキング',
		);
		return getRadioButtonTypeForm($inputkey, $items);
	}
	/**
	 * ==================================================
	 * 利用OFF/ON
	 * ==================================================
	 */
	function getUseSelectForm($inputkey)
	{
		$items = array(
			'利用しない',
			'利用する',
		);
		return getRadioButtonTypeForm($inputkey, $items);
	}
	/**
	 * ==================================================
	 * OFF/ON切り替え
	 * ==================================================
	 */
	function getOffOnSelectForm($inputkey)
	{
		$items = array(
			'OFF',
			'ON',
		);
		return getRadioButtonTypeForm($inputkey, $items);
	}

	/**
	 * ==================================================
	 * クラスとリクエストパラメータ名からプルダウンを取得
	 * @param string $className
	 * @param string $paramName
	 * ==================================================
	 */
	function getTargetClassSelectForm($className,$paramName,$multi=false)
	{
		// 翻訳データ
		$textData = ConvertText::getConvertTextArrayByTextKey();

		$list = '<select name="' . $paramName . '" ' . ($multi ? 'multiple size="10"' : '') . '>';
		$params = array();
		$datas = $className::findAllBy($params);
		foreach($datas as $_data)
		{
			if($_data->id > 0)
			{
				$_tmpName = getTextDataWithJpText($textData, $_data->name);
				$list .= '<option value="' . $_data->id . '">' . $_data->id . ':' . $_tmpName . '</option>';
			}
		}
		$list .= '</select>';
		return $list;
	}
	
	/**
	 * ==================================================
	 * 指定のテキストの翻訳データがあればセットして返す
	 * @param array $textData
	 * @param string $name
	 * ==================================================
	 */
	function getTextDataWithJpText($textData,$name)
	{
		$_tmpName = $name;
		$textJP = getJpTextByText($textData, $name);
		if($textJP)
		{
			$_tmpName = $name . '（' . $textJP . '）';
		}
		return $_tmpName;
	}

	/**
	 * ==================================================
	 * 指定の配列とリクエストパラメータ名からプルダウンを取得
	 * @param array $datas array('id' => id,'name' => name);
	 * @param string $paramName
	 * ==================================================
	 */
	function getTargetArraySelectForm($datas,$paramName)
	{
		$list = '<select name="' . $paramName . '">';
		foreach($datas as $k => $v)
		{
			$list .= '<option value="' . $k . '">' . $k . ':' . $v . '</option>';
		}
		$list .= '</select>';
		return $list;
	}

	/**
	 * ==================================================
	 * 配列をHtmlTableの形式に整形
	 * @param array $array
	 * @return string
	 * ==================================================
	 */
	function arrayToTableData($array)
	{
		$list = '<table border="1">';
		foreach($array as $key => $_tmparray)
		{
			$list .= '<tr>';

			if(is_array($_tmparray))
			{
				foreach($_tmparray as $key2 => $value2)
				{
					if(is_array($value2))
					{
						$value2 = arrayToTableData($value2);
					}
					$list	.= '<td>' . $value2 . '</td>';
				}
			}
			else
			{
				$list	.= '<td>' . $_tmparray . '</td>';
			}
			$list .= '</tr>';
		}
		$list .= '</table>';
		return $list;
	}

	/**
	 * テキストデータから和訳を取得
	 * @param array $textData
	 * @param string $text
	 * @return Ambigous <string, unknown>
	 */
	function getJpTextByText($textData,$text)
	{
		$textJP = '';
		if($text !== '*****')
		{
		$text = preg_replace('/的碎片/','',$text);
		if(isset($textData[$text]))
		{
			$textJP = $textData[$text];
			}
		}
		return $textJP;
	}

	/**
	 * 配列をTableにセットして取得
	 * @param array $array
	 * @param string $column
	 * @return string
	 */
	function getDataArray2TableFormat($array,$column='')
	{
		$list = '<table border="1" class="table_span"><tr>';
		if($column)
		{
			$list .= '<td class="td_column">' . $column . '</td>';
		}
		foreach($array as $_value)
		{
			$list .= '<td class="td_link">' . $_value . '</td>';
		}
		$list .= '</tr></table>';
		return $list;
	}
	
	/**
	 * 
	 */
	function getAreaIdComment(){
		return '' . Env::IDIP_AREA_QQ . ':QQ ' . Env::IDIP_AREA_WECHAT. ':Wechat';
	}
?>
