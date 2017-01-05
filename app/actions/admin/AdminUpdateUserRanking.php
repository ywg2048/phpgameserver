<?php
class AdminUpdateUserRanking extends AdminBaseAction{
	public function action($params)
	{
		$ret = 0;
		$user_id = $params['pid'];
		$score = $params['score'];
		$ranking_id = $params['ranking_id'];
		$user = User::find($user_id);
		if($user)
		{
			$lc = $user->getLeaderCardsData();
			$lc_array = join(',',array($lc->id[0],$lc->lv[0],$lc->slv[0],$lc->hp[0],$lc->atk[0],$lc->rec[0],$lc->psk[0]));
			$update_values = UserRanking::createUserRankingValues($user_id,$ranking_id,$user->name,$lc_array,$score,1,array(),0);
			$b = UserRanking::entryRanking($update_values);	
		}
		else
		{
			$ret = 0;
			$update_values = 'not user';
		}
		return json_encode(array('res' => $ret, 'update_param' => $update_values));
	}
}