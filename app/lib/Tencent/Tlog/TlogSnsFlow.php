<?php
class TlogSnsFlow extends TlogBase {
	const EVENT = 'SnsFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'ActorOpenID',
			'RecNum',
			'Count',
			'SNSType',
			'SNSSubType',
			'TargetOpenID' 
	);
	
	/**
	 * ソーシャル情報
	 *
	 * @param string $appId        	
	 * @param number $platId
	 *        	(必須)ios：0 /android：1
	 * @param string $actorOpenID
	 *        	(必須)起動ユーザOpenID
	 * @param number $count
	 *        	(必須)送信数
	 * @param number $snsType
	 *        	(必須)SNSタイプ
	 * @param number $recNum
	 *        	(任意)受取ユーザ数
	 * @param number $snsSubType
	 *        	(任意)関係タイプ
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $actorOpenID, $count, $snsType, $targetOpenID, $recNum = 0, $snsSubType = 0) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$actorOpenID,
				$recNum,
				$count,
				$snsType,
				$snsSubType,
				$targetOpenID 
		);
		return static::generateMessageFromArray ( $params );
	}
}
