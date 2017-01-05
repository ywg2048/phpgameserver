<?php
class TlogLogAntiData extends TlogBase {
	const EVENT = 'LogAntiData';
	protected static $columns = array (
			'event',
			'vGameSvrId',
			'dtEventTime',
			'iSequence',
			'vGameAppID',
			'vOpenID',
			'PlatID',
			'strData' 
	);
	
	/**
	 *
	 * @param string $appId        	
	 * @param number $platId        	
	 * @param string $openId        	
	 * @param number $guide_id        	
	 * @param number $level        	
	 * @param number $role_id        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $strData, $sequence) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$sequence,
				$appId,
				$openId,
				$platId,
				$strData 
		);
		return static::generateMessageFromArray ( $params );
	}
}
