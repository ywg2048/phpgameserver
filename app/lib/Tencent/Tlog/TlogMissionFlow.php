<?php
class TlogMissionFlow extends TlogBase {
	const EVENT = "MissionFlow";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'MissionID',
			'Level',
			'VipLevel' 
	);
	
	/**
	 * Mission Flow
	 * 
	 * @param int $appId        	
	 * @param int $platId        	
	 * @param string $openId        	
	 * @param int $mission_id        	
	 * @param int $user_lv        	
	 * @param int $vip_lv        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $mission_id, $user_lv, $vip_lv) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$mission_id,
				$user_lv,
				$vip_lv 
		);
		return static::generateMessageFromArray ( $params );
	}
}