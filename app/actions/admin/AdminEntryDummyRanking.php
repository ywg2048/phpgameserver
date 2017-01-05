<?php
class AdminEntryDummyRanking extends AdminBaseAction{
	public function action($params)
	{
		$num = $params['num'];
		$score_mergin = $params['score'];
		$ranking_id = $params['ranking_id'];
		$pdo_array = array();
		try
		{
			$delete_query = "DELETE FROM ".UserRanking::TABLE_NAME." WHERE id < 0";
			for($dbid = 0; $dbid < Env::REGISTERED_DB_NUMBER; $dbid++)
			{
				$pdo = Env::getDbConnectionForUserWrite(1,Env::assignUserDbId($dbid));
				$pdo->query($delete_query);
			}
			for($i = 0; $i < $num; $i++)
			{
				$user_id = -(5000000 + $i);
				$abs_user_id = abs($user_id);
				$dbid = Env::assignUserDbId($abs_user_id);
				if(array_key_exists($dbid,$pdo_array))
				{
					$pdo = $pdo_array[$dbid];
				}
				else
				{
					$pdo = Env::getDbConnectionForUserWrite($abs_user_id,$dbid);
					$pdo->beginTransaction();
					$pdo_array[$dbid] = $pdo;
				}
				$score = $num * $score_mergin - $i * $score_mergin;
				$update_values = UserRanking::createUserRankingValues($user_id,$ranking_id,'dummy_'.$user_id,'1,5,1,0,0,0,0',$score,0,array(),0);
				$b = UserRanking::updateRanking($pdo,$update_values);
			}
			foreach($pdo_array as $pdo)
			{
				if($pdo->inTransaction())
				{
					$pdo->commit();	
				}
			}
		}
		catch(Exception $e)
		{
			foreach($pdo_array as $pdo)
			{
				if($pdo->inTransaction())
				{
					$pdo->rollback();	
				}
			}
			throw $e;
		}
		return json_encode(array('ret' => 'success'));
	}
	
}