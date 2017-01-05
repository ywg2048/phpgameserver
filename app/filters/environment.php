<?php
/**
 * フィルタ関数.
 * 実行環境を判定し、Envオブジェクトを初期化する.
 * @return エラーページを表示して処理を終了する場合は、FALSE. そうでなければ null.
 */
function setEnvironment($serverName,$serverPort=null) {
	
	// ホスト名で環境を振り分け、設定を読み込む.
	switch($serverName)	{
		//----------------------------------------------------------------------
		// Tencent各種確認環境
		//----------------------------------------------------------------------
		case "aqq.zlmc.qq.com":// dns
			//-----------------------------------
			// TencentPOBT環境AndroidQQ
			//-----------------------------------
			if($serverPort == 52802)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-adr-qq.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-adr-qq.php');
			}
			//-----------------------------------
			// Tencent負荷テスト環境
			//-----------------------------------
			elseif($serverPort == 56100)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-stresstest.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-stresstest.php');
			}
			//-----------------------------------
			// TencentCBT4環境AndroidQQ
			//-----------------------------------
			elseif($serverPort == 56102)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-cbt4-adr-qq.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-cbt4-adr-qq.php');
			}
			//-----------------------------------
			// TencentCBT1環境
			//-----------------------------------
			else
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-cbt1.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-cbt1.php');
			}
			break;
		//----------------------------------------------------------------------
		// TencentCBT4/CBT3環境/POBT環境AndroidWechat
		//----------------------------------------------------------------------
		case "awx.zlmc.qq.com":// dns
			//-----------------------------------
			// TencentPOBT環境AndroidWechat
			//-----------------------------------
			if($serverPort == 52801)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-adr-wx.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-adr-wx.php');
			}
			//-----------------------------------
			// TencentCBT3環境Wechat
			//-----------------------------------
			elseif($serverPort == 57101)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-cbt3-wechat.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-cbt3-wechat.php');
			}
			//-----------------------------------
			// TencentCBT3環境QQ
			//-----------------------------------
			elseif($serverPort == 57102)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-cbt3-qq.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-cbt3-qq.php');
			}
			//-----------------------------------
			// TencentCBT4環境AndroidWechat
			//-----------------------------------
			elseif($serverPort == 56101)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-cbt4-adr-wx.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-cbt4-adr-wx.php');
			}
			break;
		//----------------------------------------------------------------------
		// TencentPOBT環境IOSQQ
		//----------------------------------------------------------------------
		case "iqq.zlmc.qq.com":
			//-----------------------------------
			// TencentPOBT環境IOSQQ
			//-----------------------------------
			if($serverPort == 52804)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-ios-qq.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-ios-qq.php');
			}
			break;
		//----------------------------------------------------------------------
		// TencentPOBT環境IOSWechat
		//----------------------------------------------------------------------
		case "iwx.zlmc.qq.com":
			//-----------------------------------
			// TencentPOBT環境IOSWechat
			//-----------------------------------
			if($serverPort == 52803)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-ios-wx.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-ios-wx.php');
			}
			break;
		//----------------------------------------------------------------------
		// TencenPOBT環境AndroidQQ
		//----------------------------------------------------------------------
		case "10.239.181.242":// IDIP用アドレス
		case "TEN_POBT_ADR_QQ":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-adr-qq.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-adr-qq.php');
			break;
		//----------------------------------------------------------------------
		// TencenPOBT環境AndroidWechat
		//----------------------------------------------------------------------
		case "10.239.188.191":// IDIP用アドレス
		case "TEN_POBT_ADR_WX":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-adr-wx.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-adr-wx.php');
			break;
		//----------------------------------------------------------------------
		// TencenPOBT環境IOSQQ
		//----------------------------------------------------------------------
		case "10.239.188.209":// IDIP用アドレス
		case "TEN_POBT_IOS_QQ":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-ios-qq.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-ios-qq.php');
			break;
		//----------------------------------------------------------------------
		// TencenPOBT環境IOSWechat
		//----------------------------------------------------------------------
		case "10.239.188.208":// IDIP用アドレス
		case "TEN_POBT_IOS_WX":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-pobt-ios-wx.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-pobt-ios-wx.php');
			break;
		//----------------------------------------------------------------------
		// TencentIOS課金検証
		//----------------------------------------------------------------------
		case "apple.zlmc.qq.com":
			//-----------------------------------
			// Wechat
			//-----------------------------------
			if($serverPort == 52703)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-ios-applytest-wx.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-ios-applytest-wx.php');
			}
			//-----------------------------------
			// QQ
			//-----------------------------------
			elseif($serverPort == 52704)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-ios-applytest-qq.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-ios-applytest-qq.php');
			}
			break;
		//----------------------------------------------------------------------
		// TencentCBT1環境
		//----------------------------------------------------------------------
		case "10.217.159.65":// ip
		case "TEN_CBT1":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-cbt1.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-cbt1.php');
			break;
		//----------------------------------------------------------------------
		// Tencent開発3環境IDIP用
		//----------------------------------------------------------------------
		case "10.231.158.15":
			// QQ
			if($serverPort == 45579)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-dev3-qq.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-dev3-qq.php');
			}
			// Wechat
			elseif($serverPort == 45585)
			{
				require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-dev3-wechat.php');
				require_once(CONFIG_DIR. '/environments/padc-cn.php');
				require_once(CONFIG_DIR. '/environments/padc-cn-tencent-dev3-wechat.php');
			}
			break;
		//----------------------------------------------------------------------
		// Tencent開発3環境QQ
		//----------------------------------------------------------------------
		case "115.159.38.33":
		case "TEN_DEV3_QQ":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-dev3-qq.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-dev3-qq.php');
			break;
		//----------------------------------------------------------------------
		// Tencent開発3環境Wechat
		//----------------------------------------------------------------------
		case "115.159.122.155":
		case "TEN_DEV3_WECHAT":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-dev3-wechat.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-dev3-wechat.php');
			break;
		//----------------------------------------------------------------------
		// Tencent開発2環境
		//----------------------------------------------------------------------
		case "182.254.159.94":// android
		case "10.170.30.71"://IDIP用アドレス
		case "TEN_DEV2":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-dev2.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-dev2.php');
			break;
		//----------------------------------------------------------------------
		// Tencent開発1環境
		//----------------------------------------------------------------------
		case "182.254.214.84":// android
		case "10.240.65.22"://idip
		case "TEN_DEV1":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-tencent-dev1.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-tencent-dev1.php');
			break;
		//----------------------------------------------------------------------
		// STG4環境（AWS）
		//----------------------------------------------------------------------
		case "stg4-pad-cn-adr-api-1500138288.ap-northeast-1.elb.amazonaws.com":// android
		case "stg4-pad-cn-ios-api-495488077.ap-northeast-1.elb.amazonaws.com":// ios
		case "STG4":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-stg4.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-stg4.php');
			break;
		//----------------------------------------------------------------------
		// STG3環境（AWS）
		//----------------------------------------------------------------------
		case "stg3-pad-cn-adr-api-2047556555.ap-northeast-1.elb.amazonaws.com":// android
		case "stg3-pad-cn-ios-api-57267834.ap-northeast-1.elb.amazonaws.com":// ios
		case "STG3":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-stg3.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-stg3.php');
			break;
		//----------------------------------------------------------------------
		// STG2環境（AWS）
		//----------------------------------------------------------------------
		case "stg2-pad-cn-adr-api-859981376.ap-northeast-1.elb.amazonaws.com":// android
		case "stg2-pad-cn-ios-api-275658645.ap-northeast-1.elb.amazonaws.com":// ios
		case "STG2":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-stg2.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-stg2.php');
			break;
		//----------------------------------------------------------------------
		// STG1環境（AWS）
		//----------------------------------------------------------------------
		case "stg1-pad-cn-adr-api-710760711.ap-northeast-1.elb.amazonaws.com":// android
		case "stg1-pad-cn-ios-api-111431304.ap-northeast-1.elb.amazonaws.com":// ios
		case "STG1":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-stg1.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-stg1.php');
			break;
		//----------------------------------------------------------------------
		// 開発環境（AWS）
		//----------------------------------------------------------------------
//		case "54.64.30.135":
		case "dev-pad-cn-adr-api-678610142.ap-northeast-1.elb.amazonaws.com":// android
		case "dev-pad-cn-ios-api-871501207.ap-northeast-1.elb.amazonaws.com":// ios
		case "DEV":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-dev.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-dev.php');
			break;
		//----------------------------------------------------------------------
		// ローカル開発環境
		//----------------------------------------------------------------------
		case "127.0.0.1":
		case "LOCAL":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-local.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-local.php');
			break;

		case "192.168.0.34":
		case "dyspace":
			require_once(ALL_CONFIG_DIR . '/environments/padc-db-cn-dyspace.php');
			require_once(CONFIG_DIR. '/environments/padc-cn.php');
			require_once(CONFIG_DIR. '/environments/padc-cn-dyspace.php');
			break;
		default:
			die();
	}

	// ロガー初期化.
	global $logger;
	require_once("Zend/Log/Writer/Stream.php");
	require_once("Zend/Log.php");
	if($serverName === "test") {
		$logger = new PadTestLogger();
	} else {
		$logger = new Zend_Log();

		$logFile = Padc_Log_Log::getLogFile();
		$errorLogFile = Padc_Log_Log::getErrorLogFile();

		$writer = new Zend_Log_Writer_Stream($logFile);
		$err_writer = new Zend_Log_Writer_Stream($errorLogFile);
		try
		{
			chmod($logFile,0777);
			chmod($errorLogFile,0777);
		}
		catch(Exception $e)
		{

		}

		$writer->addFilter(Env::API_LOG_LEVEL);
		$err_writer->addFilter(Env::API_ERR_LOG_LEVEL);

		$logger->addWriter($writer);
		$logger->addWriter($err_writer);
	}
}

