<?php
/**
 * #PADC#
 * ランキングDB
 */
class Ranking extends BaseModel {
	const TABLE_NAME = "padc_ranking";
	const TEMPORARY_TABLE_NAME = 'padc_ranking_temporary';
	const TEMPORARY2_TABLE_NAME = 'padc_ranking_temporary2';
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	const RANKING_ID_SLICE_NUM = 10000;
	const LIMIT_RETURN_RANKING = 100;
	const CALCULATE_VALUE_MAX = 12;
	protected static $columns = array(
		'id',
		'user_id',
		'ranking_id',
		'user_name',
		'ranking_number',
		'lc',
		'score',
		'score_group',
		'total_power',
		'updated_at',
	);

	public static function createValues($user_id,$ranking_id,$user_name,$lc,$score,$score_group,$total_power)
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
		return $ret;
	}

	public static function entryRanking($update_values)
	{
		$user_id = $update_values['user_id'];
		$ranking_id = $update_values['ranking_id'];
		$score = $update_values['score'];
		$pdo = static::getWritePdo($user_id);
		try
		{
			$pdo->beginTransaction();
			// ランキングエントリー受付中チェック。
			static::updateRanking($pdo,$update_values);
			//添加排名关卡数据记录
			// $user_ranking_record = new UserRankingRecord();
			// $user_ranking_record ->user_id = $user_id;
			// $user_ranking_record ->ranking_id = $ranking_id;
			// $user_ranking_record ->score = $score;
			// $user_ranking_record ->combos = 0;
			// $user_ranking_record ->waves = 0;
			// $user_ranking_record ->rare = 0;
			// $user_ranking_record ->turns = 0;
			// $user_ranking_record ->created_at = User::timeToStr(time());
			// $user_ranking_record ->updated_at = User::timeToStr(time());
			// $user_ranking_record ->create($pdo);
			$pdo->commit();
		}
		catch(Exception $e)
		{
			if($pdo)
			{
				if($pdo->inTransaction())
				{
					$pdo->rollBack();
				}
			}
			$pdo = null;
			throw $e;
		}
		$pdo = null;
		return static::TABLE_NAME;
	}

	public static function updateRanking($pdo,$update_values)
	{
		$values = array();
		$array_comma = false;
		$columns_array = '';
		$columns_bind_array = '';
		$columns_update_array = '';
		foreach(static::$columns as $column)
		{
			if(array_key_exists($column,$update_values))
			{
				$columns_array = $columns_array.',';
				$columns_bind_array = $columns_bind_array.',';
				// MY : プライマリーキーはアップデート時には不要。
				if($column != 'user_id' || $column != 'id')
				{
					$columns_update_array = $columns_update_array.',';	
					$columns_update_array = $columns_update_array.' '.$column.' = values('.$column.')';
				}
				$columns_array = $columns_array.$column;
				$columns_bind_array = $columns_bind_array.'?';
				$values[] = $update_values[$column];
			}
		}
		$sql = 'INSERT INTO '.static::TABLE_NAME.'(updated_at'.$columns_array.') values(now()'.$columns_bind_array.') ON DUPLICATE KEY UPDATE updated_at=now() '.$columns_update_array;
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);
	}

	private static function updateAllRanking($pdo, $user_ranking_datas, $temporary_table = null,$add_columns = null)
	{
		$table_columns = array('id','user_id','user_name','ranking_id','lc','score','score_group','updated_at','total_power');
		if(isset($add_columns))
		{
			$table_columns = array_merge($table_columns,$add_columns);
		}
		$column_num = count($table_columns);
		$value_query = '(?'.str_repeat(',?',$column_num-1).')';
		$update_columns = array();
		for($i = 1; $i < $column_num; $i++)
		{
			$column = $table_columns[$i];
			$update_columns[] = $column. ' = values('.$column.')';
		}

		$values = array();
		$values_query = 'values ';
		$comma = false;
		$add_update = '';
		foreach($user_ranking_datas as $user_ranking_data)
		{
			foreach($table_columns as $value)
			{
				$values[] = $user_ranking_data->$value;
			}
		}
		$values_query = $values_query.$value_query.str_repeat(','.$value_query,count($user_ranking_datas)-1);
		$table_name = self::TABLE_NAME;
		if($temporary_table)
		{
			$table_name = $temporary_table;
		}
		$sql = 'INSERT INTO '.$table_name.'('.join(',',$table_columns).') '.$values_query.' ON DUPLICATE KEY UPDATE '.join(',',$update_columns);
		$stmt = $pdo->prepare($sql);
		$stmt->execute($values);
	}

	private static function switchTemporaryTable($pdo)
	{
		$sql = 'RENAME TABLE '.static::TABLE_NAME.' TO '.static::TABLE_NAME.'_old, '.static::TEMPORARY_TABLE_NAME.' TO '.static::TABLE_NAME;
		$pdo->query($sql);
		$sql = 'DROP TABLE '.static::TABLE_NAME.'_old';
		$pdo->query($sql);
		$sql = 'DROP TABLE '.static::TEMPORARY2_TABLE_NAME;
		$pdo->query($sql);
	}

	public static function getRanking($limit = self::LIMIT_RETURN_RANKING, $offset = 0)
	{
		$pdo = static::getReadPdo();
		$sql = 'SELECT * FROM '.static::TABLE_NAME.' ORDER BY score DESC LIMIT '.$limit.' OFFSET '.$offset;
		$stmt = $pdo->query($sql,PDO::FETCH_CLASS, get_called_class());
		$ranking_datas = $stmt->fetchAll();		
		$pdo = null;
		return $ranking_datas;
	}

	public static function getScoreRanking($score, $pdo = null)
	{
		if($pdo == null)
		{
			$pdo = static::getReadPdo();	
		}
		$count = 0;
		if($score > 0)
		{
			$sql = 'SELECT count(*)+1 as Ranking from '.static::TABLE_NAME.' where score > ?';
			$values = array($score);
			$stmt = $pdo->prepare($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
			$stmt->execute($values);
			$record = $stmt->fetchAll();
			$count = $record[0]->Ranking;
		}
		return (int)$count;
	}

	public static function getScoreGroupRanking($score)
	{
		$pdo = static::getReadPdo();
		$sql = 'SELECT count(*)+1 as Ranking from '.static::TABLE_NAME.' where score > ? and score_group = ?';
		$values = array($score,0);
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);
		$record = $stmt->fetchAll();
		$count = $record[0]->Ranking;
		$pdo = null;
		return $count;
	}

	protected static function getWritePdo($user_id = null)
	{
		return Env::getDbConnectionForShare();
	}

	protected static function getReadPdo($user_id = null)
	{
		return Env::getDbConnectionForShareRead();
	}

	public static function reflectionAllRanking()
	{
		$ret = 'not_ranking';
		$aggregate_num = 0;
		$start = 0;
		$time = 0;
		$time2 = 0;
		$time3 = 0;
		$user_num = 0;
		try
		{
			$log_time = time();
			$aggregate_time = self::timeToStr($log_time);
			$start = time();
			$exec_limit = 5000;
			$share = self::getWritePdo();
			$ranking = LimitedRanking::getAggregateRanking($share);
			if($ranking)
			{
				$ranking = $ranking[0];
				$ranking_id = $ranking->ranking_id;
				$aggregate_end = false;
				$aggregate_data = RankingAggregateData::find($ranking_id,$share);
				if($aggregate_data)
				{
					$aggregate_end = $aggregate_data->checkAggregateEnd($ranking->end_time);
				}
				if($aggregate_end == false)
				{

					$share->beginTransaction();
					$sql = 'SELECT padc_cron_lock.lock FROM padc_cron_lock where id = 1';
					$stmt = $share->query($sql,PDO::FETCH_CLASS, get_called_class());
					$rec = $stmt->fetchAll();
					$lock = 0;
					if(isset($rec) && count($rec) > 0)
					{
						$lock = $rec[0]->lock;	
					}
					else
					{

						$sql = 'INSERT padc_cron_lock VALUES (1,0)';
						$stmt = $share->query($sql,PDO::FETCH_CLASS, get_called_class());
						$sql = 'SELECT padc_cron_lock.lock FROM padc_cron_lock where id = 1';
						$stmt = $share->query($sql,PDO::FETCH_CLASS, get_called_class());
						$rec = $stmt->fetchAll();
						$lock = $rec[0]->lock;
					}
					if($lock == 0)
					{
						$sql = 'UPDATE padc_cron_lock set padc_cron_lock.lock = 1 where id = 1';
					}
					$share->query($sql);
					$share->commit();

					if($lock == 0)
					{
						
						$share->beginTransaction();

						$temporary_tables = array(static::TEMPORARY_TABLE_NAME,static::TEMPORARY2_TABLE_NAME);
						foreach($temporary_tables as $table)
						{
							$sql = 'DROP TABLE IF EXISTS '.$table;
							$sql = 'CREATE TABLE IF NOT EXISTS '.$table.' LIKE '.static::TABLE_NAME;
							$share->query($sql);
							$sql = 'TRUNCATE '.$table;
							$share->query($sql);
						}
						$time2 = time();
						for($i = 1; $i < Env::REGISTERED_DB_NUMBER+1; $i++)
						{
							$pdo = Env::getDbConnectionForUserRead(''.$i,$i);
							$array_users = array();
							$user_dbs[$i] = array();
							$count = User::countAllBy(array(),$pdo);
							$user_num += $count;
							// MY : BAN対象検索。
							for($j = 0; $j <= floor($count / $exec_limit); $j++)
							{
								$stmt = $pdo->query('SELECT id,del_status FROM '.User::TABLE_NAME.' WHERE del_status != '.User::STATUS_NORMAL.' LIMIT '.$exec_limit.' OFFSET '.($j*$exec_limit),PDO::FETCH_CLASS, 'User');
								$users = $stmt->fetchAll();
								foreach($users as $user)
								{
									$array_users[$user->id] = $user;
								}
								$stmt = null;
								$users = null;
							}
							$count = UserRanking::countAllBy(array('ranking_id' => $ranking_id),$pdo);
							$aggregate_num += $count;
							for($j = 0; $j <= floor($count / $exec_limit); $j++)
							{
								$stmt = $pdo->query('SELECT * FROM '.UserRanking::TABLE_NAME.' WHERE ranking_id ='.$ranking_id.' AND score > 0 LIMIT '.$exec_limit.' OFFSET '.($j*$exec_limit),PDO::FETCH_CLASS, 'UserRanking');
								$user_ranking_datas = $stmt->fetchAll();
								if($user_ranking_datas)
								{
									foreach($user_ranking_datas as $key => $data)
									{
										$user = null;
										if(array_key_exists($data->user_id,$array_users))
										{
											$user = $array_users[$data->user_id];	
										}
										if($user)
										{
											if($user->del_status != User::STATUS_NORMAL)
											{
												unset($user_ranking_datas[$key]);
											}
										}
									}
									self::updateAllRanking($share,$user_ranking_datas,static::TEMPORARY2_TABLE_NAME);	
								}
								$stmt = null;
								$user_ranking_datas = null;
							}
							$pdo = null;
						}
						$time2 = time() - $time2;
						$time3 = time();

						$time3 = time() - $time3;
						$user_dbs = null;
						$sql = 'SELECT COUNT(*) AS count FROM '.static::TEMPORARY2_TABLE_NAME;
						$stmt = $share->query($sql,PDO::FETCH_CLASS, get_called_class());
						$rec = $stmt->fetchAll();
						$count = $rec[0]->count;
						$old_score = 0;
						$rank_int = 0;
						$score_count = 0;
						$exec_limit = 5000;
						$time = time();
						for($j = 0; $j <= floor($count / $exec_limit); $j++)
						{
							$sql = 'SELECT * FROM '.static::TEMPORARY2_TABLE_NAME.' ORDER BY score DESC LIMIT '.$exec_limit.' OFFSET '.($j*$exec_limit);
							$stmt = $share->query($sql,PDO::FETCH_CLASS, get_called_class());
							$ranking_datas = $stmt->fetchAll();
							foreach($ranking_datas as $key => $data)
							{
								$score_count++;
								if((int)$data->score != $old_score )
								{
									$rank_int += $score_count;
									$score_count = 0;
								}
								$data->ranking_number = max(1,$rank_int);
								$old_score = $data->score;
								$ranking_datas[$key] = $data;	
							}
							if($ranking_datas)
							{
								self::updateAllRanking($share,$ranking_datas,static::TEMPORARY_TABLE_NAME,array('ranking_number'));	
							}
						}
						$time = time() - $time;
						$share->commit();

						self::switchTemporaryTable($share);

						$share->beginTransaction();
						$aggregate_data = RankingAggregateData::find($ranking_id,$share,true);
						if($aggregate_data == false)
						{
							$aggregate_data = new RankingAggregateData();
							$aggregate_data->id = $ranking_id;
							$aggregate_data->create($share);
						}

						$aggregate_data->time = $aggregate_time;
						$aggregate_data->update($share);
						$share->commit();
						$ret = 'succeed';

						$share->beginTransaction();
						$sql = 'UPDATE padc_cron_lock set padc_cron_lock.lock = 0 where id = 1';
						$share->query($sql);			
						$share->commit();
						
						// MY : ログ保存
						$score_ranking = Ranking::getRanking();
						$user_data_list = array();
						foreach($score_ranking as $score_data)
						{
							$user = UserDevice::find($score_data->user_id,$share);
							if($user)
							{
								// [ユーザID,OpenID,ユーザー名、スコア]
								$user_data_list[] = array($user->id,$user->oid,$score_data->user_name,$score_data->score);
							}
						}
						$log_time = strftime('%Y%m%d%H%M%S', $log_time);
						UserTlog::sendTlogRanking($ranking->ranking_dungeon_id, $ranking_id, $log_time, $user_data_list);
					}
					else
					{
						$ret = 'failed';
					}
				}
				else
				{
					$ret = 'end';
				}
			}
			else
			{
				$rat = 'not ranking';
			}
		}
		catch(Exception $e)
		{
			if($share)
			{
				if($share->inTransaction())
				{
					$share->rollback();	
				}
				
			}
			$share->beginTransaction();
			$sql = 'UPDATE padc_cron_lock set padc_cron_lock.lock = 0 where id = 1';
			$share->query($sql);			
			$share->commit();
			$share = null;
			$pdo = null;
			throw $e;
		}
		Padc_Log_Log::sendTlogHistory();
		return $ret.' aggregate num '.$aggregate_num.' time '.(time()-$start).'_'.$time.'_'.$time2.'_'.$time3.'_user '.$user_num;
	}

	private static function clearRanking($pdo, $ranking_id)
	{
		$sql = 'DELETE FROM '.static::TABLE_NAME.' WHERE ranking_id = '.$ranking_id;
		$pdo->exec($sql);
	}

	public static function calculateScore($values,$rule,$dungeon_floor,$user_deck_cards,$ranking_id)
	{
		// MY : 計算用定数。
		$combo_score_rate = 5000;
		$combo_score_min = 4;
		$combo_score_gamma = 4;
		$turn_score_max = 40000;
		$turn_score_rate = 2500;
		$time_score_max = 29999;
		$time_score_rate = 100;
		$rare_score_max = 500000;
		$rare_score_rare_max = 10;
		$rare_score_gamma = 4;
		$scores = array();

		$rankingfloor = RankingFloor::findBy(array('ranking_id'=>$ranking_id,'ranking_floor_id'=>$dungeon_floor->id));
		if(!$rankingfloor){
			throw new PadException(RespCode::UNKNOWN_ERROR,"排名关卡难度未配置");
		}
		$hard = $rankingfloor->hard;


		for($i = 0; $i < self::CALCULATE_VALUE_MAX;$i++)
		{
			if($rule & 0x01 << $i)
			{
				switch($i)
				{
					case 0: 
						{
							$scores['average_combo_score'] = 0;
							if(isset($values['v0']) && isset($values['v2']) )
							{
								$combo = $values['v0'];
								$use_turn = $values['v2'];
								if($use_turn > 0)
								{
									$average = $combo / $use_turn;	
								}
								else
								{
									$average = 0;
								}
								
								$dk = pow($combo_score_min,$combo_score_gamma);
								$dk = pow($average,$combo_score_gamma) / $dk;
								$scores['average_combo_score'] = max(0,round($combo_score_rate * $dk / 100)*100*$hard);	
							}

						}
						break;
					case 1:
						{
							$scores['clear_time_score'] = 0;
							if(isset($values['v1']))
							{
								$time =  $values['v1'];
								$scores['clear_time_score'] = max(0,($time_score_max - $time)*$hard);	
							}
						}
						break;
					case 2:
						{
							$scores['clear_turn_score'] = 0;
							if(isset($values['v2']))
							{
								$use_turn = $values['v2'];
								$wave_num = $dungeon_floor->waves;
								$scores['clear_turn_score'] = max(0,($turn_score_max - ($use_turn - $wave_num) * $turn_score_rate)*$hard);
							}
						}
						break;
					case 3:
						{
							$rare = 0;
							foreach($user_deck_cards as $card)
							{
								$rare = $rare + $card->rare;
							}
							$rare = round($rare / count($user_deck_cards),2);
							$dk = pow( $rare_score_rare_max - 1, $rare_score_gamma);
							$dk = pow($rare_score_rare_max - $rare,$rare_score_gamma) / $dk;
							$dk = $dk * $rare_score_max;
							$scores['average_rarity_score'] = max(0,round($dk / 100) * 100*$hard);
							$ranking_rare = $rare;
						}
						break;
					default:
						break;
				}
			}
		}

		$total = 0;
		foreach($scores as $score)
		{
			$total = $total + $score;
		}

		$scores['total'] = $total;
		return array($scores,$ranking_rare);
	}

	public static function calculateRankingScore($values,$rule,$dungeon_floor,$user_deck_cards,$ranking_id){

		$wave = $values['wave'];
		$averageCombo = $values['averageCombo'];
		$totalTurn  = $values['totalTurn'];
		$rare  = $values['rare'];
		$scores = array();
		$rankingfloor = RankingFloor::findBy(array('ranking_id'=>$ranking_id,'ranking_floor_id'=>$dungeon_floor));
		if(!$rankingfloor){
			throw new PadException(RespCode::UNKNOWN_ERROR,"排名关卡难度未配置");
		}
		$hard = $rankingfloor->hard;
		// $scores['waves_score'] = 5*$wave*($wave+1)*(2*$wave+1)/3/2;
		$scores['combo_score'] = pow($averageCombo,4)/256*5000*$hard;
		$scores['turn_score']  = (40000-($totalTurn-$wave)*2500)*$hard;
		$scores['rare_score']  = pow((10-$rare),4)/pow(9,4)*500000*$hard;
		$total = 0;
		foreach($scores as $score)
		{
			$total = $total + $score;
		}
		$scores['total'] = $total;
		return $scores;
	}
	################REDIS#######################
	//获取Redis连接
	public static function getRedis()
	{
		$redis = Env::getRedis(Env::REDIS_USER);
		return $redis;
	}
	//获取redis里面前X名排行数据
	public static function getTopRankList($ranking_id, $limit = 100)
	{
		$key = self::getRankingRedisKey($ranking_id);
		$redis = self::getRedis();

		$rank_list = $redis->zRevRange($key, 0, $limit, true);

		return $rank_list;
	}
	//获取排名key
	public static function getRankingRedisKey($ranking_id)
	{
		$key = RedisCacheKey::getRankingDungeonKey($ranking_id);
		return $key;
	}

	#返回自己的名次
	public static function getSelfRank($ranking_id,$user_id,$score_in)
	{
		$key = self::getRankingRedisKey($ranking_id);
		$redis = self::getRedis();

		$rank = $redis->zRevRank($key, $user_id);
		$rank ++;
		$score = $redis->zScore($key, $user_id);
		if($score == 0||$score == ""|| !$score){
			$rank = self::getScoreRanking($score_in);
		}
		return $rank;
	}
	#返回自己的名次
	public function getRankWithScore()
	{
		$key = self::getRankingRedisKey($this->ranking_id);
		$redis = self::getRedis();

		$rank = $redis->zRevRank($key, $this->user_id);
		$score = $redis->zScore($key, $this->user_id);

		return array($rank, $score);
	}
}