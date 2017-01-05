<?php
class TlogMonthlyCard extends TlogBase {
	const EVENT = "MonthlyCard";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'dtEndTime',
			
	);
	
	/**
	 * Monthly Card
	 * @param string $appId
	 * @param int $platId
	 * @param string $openId
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId,$dtEndTime) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$dtEndTime,
		);
		return static::generateMessageFromArray ( $params );
	}
}