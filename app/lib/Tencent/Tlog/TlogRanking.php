<?php
class TlogRanking extends TlogBase {
	const EVENT = "Ranking";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'DungeonID',
			'RankingID',
			'TimeStamp',
			'UserData1',
			'UserData2',
			'UserData3',
			'UserData4',
			'UserData5' 
	);
	
	/**
	 * Ranking
	 * 
	 * @param int $appId        	
	 * @param int $platId        	
	 * @param int $dungeonId        	
	 * @param int $rankingId        	
	 * @param string $timeStamp        	
	 * @param string $userData1        	
	 * @param string $userData2        	
	 * @param string $userData3        	
	 * @param string $userData4        	
	 * @param string $userData5        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $dungeonId, $rankingId, $timeStamp, $userData1, $userData2, $userData3, $userData4, $userData5) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$dungeonId,
				$rankingId,
				$timeStamp,
				$userData1,
				$userData2,
				$userData3,
				$userData4,
				$userData5 
		);
		return static::generateMessageFromArray ( $params );
	}
}