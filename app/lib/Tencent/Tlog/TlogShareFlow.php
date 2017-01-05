<?php
class TlogShareFlow extends TlogBase {
	const EVENT = "ShareFlow";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'ShareType',
			'DungeonID',
			'CardID',
			'Level',
			'VipLevel' 
	);
	
	/**
	 * share
	 *
	 * @param int $appId        	
	 * @param int $platId        	
	 * @param string $openId        	
	 * @param int $shareType        	
	 * @param int $dungeonId        	
	 * @param int $cardId        	
	 * @param int $user_lv        	
	 * @param int $vip_lv        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $shareType, $dungeonId, $cardId, $user_lv, $vip_lv) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$shareType,
				$dungeonId,
				$cardId,
				$user_lv,
				$vip_lv 
		);
		return static::generateMessageFromArray ( $params );
	}
}