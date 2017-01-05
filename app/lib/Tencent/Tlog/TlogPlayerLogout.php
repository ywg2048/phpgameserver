<?php
class TlogPlayerLogout extends TlogBase {
	const EVENT = "PlayerLogout";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'OnlineTime',
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
			'PlayerMoney' 
	);
	
	/**
	 * palyer logout
	 *
	 * @param string $appId        	
	 * @param int $platId        	
	 * @param string $openId        	
	 * @param int $onlineTime        	
	 * @param int $level        	
	 * @param int $playerFriendsNum        	
	 * @param string $clientVersion        	
	 * @param string $systemSoftware        	
	 * @param string $systemHardware        	
	 * @param string $telecomOper        	
	 * @param string $network        	
	 * @param int $screenWidth        	
	 * @param int $screenHight        	
	 * @param float $density        	
	 * @param int $loginChannel        	
	 * @param string $cpuHardware        	
	 * @param int $memory        	
	 * @param string $GLRender        	
	 * @param string $GLVersion        	
	 * @param string $DeviceId        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $onlineTime, $level, $playerFriendsNum, $clientVersion, $loginChannel, $DeviceId, $VipLevel, $MonthlyFee, $PlayerDiamonds, $PlayerMoney, $systemSoftware = null, $systemHardware = null, $telecomOper = null, $network = null, $screenWidth = 0, $screenHight = 0, $density = 0, $cpuHardware = null, $memory = 0, $GLRender = null, $GLVersion = null) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$onlineTime,
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
				$GLRender,
				$GLVersion,
				$DeviceId,
				$VipLevel,
				$MonthlyFee,
				$PlayerDiamonds,
				$PlayerMoney 
		);
		return static::generateMessageFromArray ( $params );
	}
}