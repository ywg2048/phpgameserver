<?php
/**
 * #PADC#
 * ランキング情報取得
 */
class GetRanking extends BaseAction{

	public function action($params)
	{
		$user_ranking = null;
		$rev = (isset($params["r"])) ? (int)$params["r"] : 1;
		$num = 0;
		$update_time = User::strToTime(0);
		$calc_time = 0;
		$aggregate_end = false;

		$ranking = LimitedRanking::getAggregateRanking();
		$aggregate_data = false;
		$top_ranking = array();
		// 集計期間中のランキングが無い場合前回のランキング情報を取得。
		if($ranking == false)
		{
			$ranking = LimitedRanking::getLastRanking();
		}
		if($ranking)
		{
			$ranking_id = $ranking[0]->ranking_id;
			$aggregate_data = RankingAggregateData::find($ranking_id,Env::getDbConnectionForShare());
			$user_ranking = UserRanking::findBy(array('user_id' => $params['pid'],'ranking_id' => $ranking_id));
			if($user_ranking)
			{
				$num = Ranking::getScoreRanking($user_ranking->score);	
			}
			if($aggregate_data)
			{
				$top_ranking = Ranking::getRanking();
				$calc_time = User::strToTime($aggregate_data->time);
				$update_time = $calc_time;
				$aggregate_end = $aggregate_data->checkAggregateEnd($ranking[0]->end_time);
			}
		}
		elseif($ranking)
		{
			$update_time = User::strToTime($ranking[0]->start_time);
			$calc_time = time();
		}
		// MY : 現在時刻が次回更新時間を超えていた場合、暫定対処として現在時刻から計算する。
		$now_time = time();
		$calc_time = RankingAggregateData::getNextAggregateTime($calc_time);
		if($now_time > $calc_time)
		{
			$calc_time = RankingAggregateData::getNextAggregateTime($now_time);    
		}
		$next_update_time = strftime("%y%m%d%H%M%S",$calc_time);
		$update_time = strftime("%y%m%d%H%M%S",$update_time);

		$ret = array('res' => RespCode::SUCCESS,'update_time' => $update_time,'ranking' => self::arrangeTopRankingColumns($top_ranking,$rev), 'user_ranking' => self::arrangeUserRankingColumns($user_ranking,$num,$rev));
		if($aggregate_end == false)
		{
			$ret['next_update'] = $next_update_time;
		}
		return json_encode($ret);
	}
	private static function arrangeTopRankingColumns($top_ranking,$rev)
	{
		$ret = array();
		foreach($top_ranking as $ranking)
		{
			$data = array();
			$data['user_id'] = $ranking->user_id;
			$data['ranking'] = $ranking->ranking_number;
			$data['lc'] = explode(',',$ranking->lc);
			foreach($data['lc'] as $k => $lcv)
			{
				$data['lc'][$k] = intval($lcv);
			}
			$data['name'] = $ranking->user_name;
			$data['score'] = $ranking->score;
			// $data['data'] = User::getCacheFriendData($ranking->user_id,$rev);
			$user = User::find($ranking->user_id);
			$data["qq_vip"] = 0;
			$data['ten_gc'] = User::NOT_GAME_CENTER;
			if($user)
			{
				$data["qq_vip"] = $user->qq_vip;
				if(isset($user->game_center)){
					$data['ten_gc'] = $user->game_center;
				}
			}

			$ret[] = $data;
		}
		return $ret;
	}
	
	private static function arrangeUserRankingColumns($user_ranking,$number,$rev)
	{
		$ret = array();
		if($user_ranking)
		{
			if($number > 0)
			{
				$ret['user_id'] = $user_ranking->user_id;
				$ret['ranking'] = $number;
				$ret['lc'] = explode(',',$user_ranking->lc);
				foreach($ret['lc'] as $k => $lcv)
				{
					$ret['lc'][$k] = intval($lcv);
				}
				$ret['name'] = $user_ranking->user_name;
				$ret['score'] = $user_ranking->score;	
				// $data['data'] = User::getCacheFriendData($user_ranking->user_id,$rev);
				$user = User::find($user_ranking->user_id);
				$ret["qq_vip"] = 0;
				$ret['ten_gc'] = User::NOT_GAME_CENTER;
				if($user)
				{
					$ret["qq_vip"] = $user->qq_vip;
					if(isset($user->game_center)){
						$ret['ten_gc'] = $user->game_center;
					}
				}
			}
		}
		return $ret;
	}
}