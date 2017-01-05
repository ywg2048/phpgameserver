<?php
class TlogGameSvrState extends TlogBase {
	const EVENT = 'GameSvtState';
	protected static $columns = array(
			'event',
			'dtEventTime',
			'vGameIP',
			'iZoneAreaID'
	);
	/**
	 *
	 * @return string
	 */
	public static function generateMessage() {
		$params = array (
				static::EVENT,
				static::makeTime (),
				gethostbyname ( $_SERVER ['SERVER_NAME'] ),
				static::getZoneId () 
		);
		return static::generateMessageFromArray ( $params );
	}
}
