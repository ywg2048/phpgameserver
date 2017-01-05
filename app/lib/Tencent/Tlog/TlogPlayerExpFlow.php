<?php
class TlogPlayerExpFlow extends TlogBase {
	const EVENT = 'PlayerExpFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'ExpChange',
			'BeforeLevel',
			'AfterLevel',
			'Time',
			'Reason',
			'SubReason' 
	);
	
	/**
	 *
	 * @param string $appId        	
	 * @param number $platId
	 *        	(必須)ios：0 /android：1
	 * @param string $openId
	 *        	(必須)ユーザOpenID
	 * @param number $expChange
	 *        	(必須)経験の変更値
	 * @param number $beforeLevel
	 *        	(任意)変更前Lｖ
	 * @param number $afterLevel
	 *        	(必須)変更後Lv
	 * @param number $time
	 *        	(必須)Lvアップ用の時間(秒)
	 * @param number $reason
	 *        	(必須)経験値変動経由１
	 * @param number $subReason
	 *        	(必須)経験値変動経由２
	 *        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $expChange, $beforeLevel, $afterLevel, $time, $reason, $subReason) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$expChange,
				$beforeLevel,
				$afterLevel,
				$time,
				$reason,
				$subReason 
		);
		return static::generateMessageFromArray ( $params );
	}
}
