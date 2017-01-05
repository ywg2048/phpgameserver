<?php
class TlogFailedSneak extends TlogBase {
	const EVENT = 'FailedSneak';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'DungeonID',
			'SneakTime' 
	);
	
	/**
	 * Failed Sneak
	 *
	 * @param string $appId        	
	 * @param int $platId        	
	 * @param string $openId        	
	 * @param int $dungeonId        	
	 * @param string $sneakTime        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $dungeonId, $sneakTime) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$dungeonId,
				$sneakTime 
		);
		return static::generateMessageFromArray ( $params );
	}
}
