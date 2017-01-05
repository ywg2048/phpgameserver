<?php
/**
 * #PADC_DY#
 * 获取排名关卡的排行榜信息
 */

class GetRankingDungeon extends BaseAction {
	const DEFAULT_RANKING_LIMIT = 100;
	const MAX_RANKING_LIMIT = 100;
	const DEFAULT_TOP_RANKING_CACHE_MINUTES_OF_HOUR = 15; // 每个小时第xx分

	public function action($params)
	{
		$user_id = $params["pid"];
		$limit = isset($params["limit"]) ? $params["limit"] : self::DEFAULT_RANKING_LIMIT;
		$limit = $limit > self::MAX_RANKING_LIMIT ? self::MAX_RANKING_LIMIT : $limit;
		//查找当前开放的活动
		$ranking_id = LimitedRanking::getOpeningRankingDungeon();
		if(!$ranking_id){
			throw new PadException(RespCode::UNKNOWN_ERROR, "ranking_dungeon is not open");
		}
		$token = Tencent_MsdkApi::checkToken($params);
		if (!$token) {
			return json_encode(array(
				'res' => RespCode::TENCENT_TOKEN_ERROR
			));
		}

		$time_now = time();
		global $logger;
		
		// $key = Ranking::getRankingRedisKey($ranking_id);
		// $redis = Ranking::getRedis();
		// $redis->zAdd($key, 500, $user_id);
		$overall_rank_info_list = $this->getTopRankInfoList($ranking_id, $user_id, $limit);
		$user_rank_info = $this->getUserRankInfo($user_id, $ranking_id);
		$finished_dungeon_floors = array();
		$user_ranking_dungeon_floors =  UserRankingDungeonFloor::findAllBy(array('user_id'=>$user_id,'dungeon_id'=>$ranking_id));
		foreach ($user_ranking_dungeon_floors as $user_ranking_dungeon_floor) {
			$finished_dungeon_floors[] = (int)$user_ranking_dungeon_floor->dungeon_floor_id%1000;
		}
		$ret = array(
			'res' => RespCode::SUCCESS,
			'finished_dungeon_floors' => $finished_dungeon_floors,
			'all_rank' => $overall_rank_info_list,
			'self_rank' => $user_rank_info,
			
		);

		return json_encode($ret);
	}

	private function getUserRankInfo($user_id, $ranking_id)
	{	
		$redis = Ranking::getRedis();
		$key = RedisCacheKey::getRankingDungeonKey($ranking_id);
		$is_contains = $redis->zScore($key,$user_id);
		if($is_contains){
			//当前用户redis里面有数据，则返回redis排名
			$user_rank = $redis->zRevRank($key, $user_id);
			$user_score = $redis->zScore($key, $user_id);
			$user = User::find($user_id);
			$user_ranking = $this->getFormalizedRankInfo($user, $user_score, $user_rank+1);

			return $user_ranking;
		}
		
		//redis里面没有用户数据,查数据库
		$pdo = Env::getDbConnectionForShareRead($user_id);
		$user_point_ranking = Ranking::findBy(array('user_id' => $user_id, 'ranking_id' => $ranking_id),$pdo);
		
		if (! $user_point_ranking) {
			return array();
		}

		
		$user_score = $user_point_ranking->score;
		$user_rank = Ranking::getScoreRanking($user_score);
			
		$user = User::find($user_id);
		$user_ranking = $this->getFormalizedRankInfo($user, $user_score, $user_rank);

		return $user_ranking;
	}

	private function getTopRankInfoList($ranking_id,$user_id, $limit)
	{	
		$limit = $limit > self::MAX_RANKING_LIMIT ? self::MAX_RANKING_LIMIT : $limit;
		
		// If not found read latest ranking list  and cache it
		// $rank_list = Ranking::getTopRankList($ranking_id, self::MAX_RANKING_LIMIT);
		
		$redis = Ranking::getRedis();
		$key = RedisCacheKey::getRankingDungeonKey($ranking_id);
		$is_contains = $redis->zScore($key,$user_id);

		$rank_redis_list = $redis->zRevRange($key, 0, $limit, true);

		$rank_info_list = array();
		$r = 1;
		if(!$is_contains){
			//如果redis里面没有当前用户数据,如果redis清空之后没有数据，所以去pdac_ranking表读取排名信息
			$rank_list = Ranking::getRanking();
			if(empty($rank_list)){
				//pdac_ranking也没有数据,则显示缓存的排名
				foreach($rank_redis_list as $user_id => $score) {
					$pdo = Env::getDbConnectionForUserRead($user_id);
					$user = User::find($user_id, $pdo); // N+1 query not good but we have cache and max limit
					$rank_info_list[] = $this->getFormalizedRankInfo($user, $score, $r++);
				}
			}
			foreach ($rank_list as $key => $value) {

				$pdo = Env::getDbConnectionForUserRead($value->user_id);
				$user = User::find($value->user_id, $pdo); // N+1 query not good but we have cache and max limit
				$rank_info_list[] = $this->getFormalizedRankInfo($user, $value->score, $r++);
			}


		}else{
			foreach($rank_redis_list as $user_id => $score) {
				$pdo = Env::getDbConnectionForUserRead($user_id);
				$user = User::find($user_id, $pdo); // N+1 query not good but we have cache and max limit
				$rank_info_list[] = $this->getFormalizedRankInfo($user, $score, $r++);
			}
		}

		return array_slice($rank_info_list, 0, $limit);
	}

	/*
	 * Top排行榜缓存过期时间
	 * e.g. 每小时xx分钟刷新
	 */
	public function getCacheExpireAt()
	{
		$time_now = time();
		$current_minutes = intval(date('i'));
		$current_hour_time = strftime('%Y%m%d %H:', $time_now);
		$refresh_time = strtotime($current_hour_time . self::DEFAULT_TOP_RANKING_CACHE_MINUTES_OF_HOUR . ":00");
		if ($current_minutes > self::DEFAULT_TOP_RANKING_CACHE_MINUTES_OF_HOUR) {
			$expire_at = $refresh_time + 3600;
		} else {
			$expire_at = $refresh_time;
		}

		return $expire_at;
	}

	private function getFormalizedRankInfo($user, $score, $rank)
	{

		return array(
			'pid' => $user->id,
			'name' => $user->name,
			'qq_vip' => $user->qq_vip,
			'ten_gc' => isset($user->game_center) ? $user->game_center : User::NOT_GAME_CENTER,
			'lc' => $this->getLeaderCardInfo($user),
			'score' => $score,
			'rank' => $rank,
		);
	}

	private function getLeaderCardInfo($user)
	{	
		$pdo = Env::getDbConnectionForUserWrite($user->id);
		$lc = $user->getLeaderCardsData();
		

		$info = array(
			intval($lc->id[0]),
			intval($lc->lv[0]),
			intval($lc->slv[0]),
			intval($lc->hp[0]),
			intval($lc->atk[0]),
			intval($lc->rec[0]),
			intval($lc->psk[0]),
		);
		

		//目前版本只需要返回卡片ID
		// $id = intval($lc->id[0]);

		return $info;
	}

}
