<?php
class TlogChangeName extends TlogBase {
	const EVENT = "ChangeName";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'BeforeName',
			'AfterName'
	);
	
	/**
	 * change name tlog
	 * @param string $appId
	 * @param int $platId
	 * @param string $openId
	 * @param string $BeforeName
	 * @param string $AfterName
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId,$BeforeName,$AfterName ) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$BeforeName,
				$AfterName
		);
		return static::generateMessageFromArray ( $params );
	}
}