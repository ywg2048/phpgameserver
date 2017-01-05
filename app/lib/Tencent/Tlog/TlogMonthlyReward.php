<?php
class TlogMonthlyReward extends TlogBase {
	const EVENT = "MonthlyReward";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'Level',
			'VipLevel',
			'Gold' 
	);
	
	/**
	 *
	 * @param string $appId        	
	 * @param number $platId        	
	 * @param string $openId        	
	 * @param number $user_lv        	
	 * @param number $vip_lv        	
	 * @param number $gold        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $user_lv, $vip_lv, $gold) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$user_lv,
				$vip_lv,
				$gold 
		);
		return static::generateMessageFromArray ( $params );
	}
}