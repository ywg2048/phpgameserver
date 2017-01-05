<?php
class TlogPlayerLogin extends TlogBase {
	const EVENT = 'PlayerLogin';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'Level',
			'PlayerFriendsNum',
			'ClientVersion',
			'SystemSoftware',
			'SystemHardware',
			'TelecomOper',
			'Network',
			'ScreenWidth',
			'ScreenHight',
			'Density',
			'LoginChannel',
			'CpuHardware',
			'Memory',
			'GLRender',
			'GLVersion',
			'DeviceId',
			'VipLevel',
			'MonthlyFee',
			'PlayerDiamonds',
			'PlayerMoney',
			'GameCenter'
	);
	/**
	 *
	 * @param string $appId        	
	 * @param number $platId
	 *        	(必須)ios：0 /android：1
	 * @param string $openId
	 *        	(必須)ユーザOpenID
	 * @param number $level
	 *        	(必須)ユーザLv
	 * @param number $playerFriendsNum
	 *        	(必須)友達数
	 * @param string $clientVersion
	 *        	(必須)クライアントVer
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
	 * @param number $loginChannel
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
	public static function generateMessage($appId, $platId, $openId, $level, $playerFriendsNum, $clientVersion, $loginChannel, $vipLevel, $monthlyFee, $playerDiamonds, $playerMoney, $gameCenter = 0, $deviceId = null, $systemSoftware = null, $systemHardware = null, $telecomOper = null, $network = null, $screenWidth = 0, $screenHight = 0, $density = 0, $cpuHardware = null, $memory = 0, $glRender = null, $glVersion = null) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$level,
				$playerFriendsNum,
				$clientVersion,
				$systemSoftware,
				$systemHardware,
				$telecomOper,
				$network,
				$screenWidth,
				$screenHight,
				$density,
				$loginChannel,
				$cpuHardware,
				$memory,
				$glRender,
				$glVersion,
				$deviceId,
				$vipLevel,
				$monthlyFee,
				$playerDiamonds,
				$playerMoney,
				$gameCenter
		);
		return static::generateMessageFromArray ( $params );
	}
}
