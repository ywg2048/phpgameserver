<?php
class TlogPlayerRegister extends TlogBase {
	const EVENT = 'PlayerRegister';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'ClientVersion',
			'SystemSoftware',
			'SystemHardware',
			'TelecomOper',
			'Network',
			'ScreenWidth',
			'ScreenHight',
			'Density',
			'RegChannel',
			'CpuHardware',
			'Memory',
			'GLRender',
			'GLVersion',
			'DeviceId' 
	);
	
	/**
	 *
	 * @param string $appId        	
	 * @param number $platId
	 *        	(必須)ios：0 /android：1
	 * @param string $openId
	 *        	(必須)ユーザOpenID
	 * @param string $clientVersion
	 *        	(任意)クライアントVer
	 * @param string $systemSoftware
	 *        	(任意)端末OS
	 * @param string $systemHardware
	 *        	(任意)端末機種
	 * @param string $telecomOper
	 *        	(任意)端末通信の運用会社
	 * @param string $network
	 *        	(任意)3G/WIFI/2G
	 * @param number $screenWidth
	 *        	(任意)画面の横サイズ
	 * @param number $screenHight
	 *        	(任意)画面の縦サイズ
	 * @param number $density
	 *        	(任意)解像度
	 * @param number $regChannel
	 *        	(任意)ログイン用プラットフォーム
	 * @param string $cpuHardware
	 *        	(任意)cpu対応|周波数|コア数
	 * @param number $memory
	 *        	(任意)メモリ　単位M
	 * @param string $glRender
	 *        	(任意)opengl render情報
	 * @param string $glVersion
	 *        	(任意)openglバージョン情報
	 * @param string $deviceId
	 *        	(任意)端末ID
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $regChannel,$deviceId = null, $clientVersion = null, $systemSoftware = null, $systemHardware = null, $telecomOper = null, $network = null, $screenWidth = 0, $screenHight = 0, $density = 0, $cpuHardware = null, $memory = 0, $glRender = null, $glVersion = null) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$clientVersion,
				$systemSoftware,
				$systemHardware,
				$telecomOper,
				$network,
				$screenWidth,
				$screenHight,
				$density,
				$regChannel,
				$cpuHardware,
				$memory,
				$glRender,
				$glVersion,
				$deviceId 
		);
		return static::generateMessageFromArray ( $params );
	}
}
