<?php
class TlogDeckFlow extends TlogBase {
	const EVENT = 'DeckFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'decks',
			'totalPower' 
	);
		
	/**
	 * チーム編成
	 *
	 * @param string $appId
	 *        	APP_ID
	 * @param number $platId
	 *        	ios：0 /android：1
	 * @param string $openId
	 *        	ユーザOpenID
	 * @param string $decks
	 *        	チームデータ
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $decks,$totalPower) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$decks,
				$totalPower,
		);
		return static::generateMessageFromArray ( $params );
	}
}
