<?php
/**
 * #PADC#
 * ランキングDB
 */
class UserRanking extends Ranking {
	const TABLE_NAME = "user_ranking";
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	const TYPE_IOS = 0;
	const TYPE_ADR = 1;
	protected static $columns = array(
		'id',
		'user_id',
		'ranking_id',
		'user_name',
		'lc',
		'score',
		'score_group',
		'total_power',
		'v0',
		'v1',
		'v2',
		'v3',
		'v4',
		'v5',
		'v6',
		'v7',
		'v8',
		'v9',
		'v10',
		'v11',
		'v12',
		'v13',
		'v14',
		'v15',
		'updated_at',
	);


	public static function createUserRankingValues($user_id,$ranking_id,$user_name,$lc,$score,$score_group,$v,$total_power)
	{
		$ret = array();
		$ret['id'] = $user_id;
		$ret['user_id'] = $user_id;
		$ret['ranking_id'] = $ranking_id;
		$ret['user_name'] = $user_name;
		$ret['lc'] = $lc;
		$ret['score'] = $score;
		$ret['score_group'] = $score_group;
		$ret['total_power'] = $total_power;
		for($i = 0; $i < 16; $i++)
		{
			$ret['v'.$i] = null;
		}
		foreach($v as $k => $p)
		{
			$ret[$k] = $p;
		}
		return $ret;
	}


	protected static function getWritePdo($user_id = null)
	{
		return Env::getDbConnectionForUserWrite($user_id,Env::assignUserDbId(abs($user_id)));
	}

	protected static function getReadPdo($user_id = null)
	{
		return Env::getDbConnectionForUserRead($user_id,Env::assignUserDbId(abs($user_id)));
	}
	
	/**
	 *  set score for user ranking
	 * @param string $userId
	 * @param int $value
	 * @param PDO $pdo
	 * @throws PadException
	 * @return boolean | mixed
	 */
	public static function setScore($userId,$value,$pdo = null) {
		
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($userId);
		}
		//found user ranking,if not find throw a exception
		$user_ranking = UserRanking::findBy(array('user_id'=>$userId),$pdo);
		if(!$user_ranking)throw new PadException(RespCode::UNKNOWN_ERROR,'not found user_ranking');
		
		// SQLを構築.
		list($columns, $values) = $user_ranking->getValuesForUpdate();
		$sql = 'UPDATE ' . static::TABLE_NAME . ' SET score='.$value;

		if(static::HAS_UPDATED_AT === TRUE) $sql .= ',updated_at=now()';
		$sql .= ' WHERE user_id = ?';
		$stmt = $pdo->prepare($sql);
		//echo $sql;
		$result = $stmt->execute(array($userId));
	
		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",",array_merge($values, array($userId)))), Zend_Log::DEBUG);
		}
	
		return $result;
	}

}