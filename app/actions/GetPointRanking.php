<?php
/**
 * #PADC_DY#
 * 获取限时积分活动的排行榜信息
 */

class GetPointRanking extends BaseAction {
	const DEFAULT_RANKING_LIMIT = 3;
	const MAX_RANKING_LIMIT = 100;
	const DEFAULT_TOP_RANKING_CACHE_MINUTES_OF_HOUR = 15; // 每个小时第xx分

	public function action($params)
	{
		$user_id = $params["pid"];
		$activity_id = $params["activity_id"];
		$limit = isset($params["limit"]) ? $params["limit"] : self::DEFAULT_RANKING_LIMIT;
		$limit = $limit > self::MAX_RANKING_LIMIT ? self::MAX_RANKING_LIMIT : $limit;

		$token = Tencent_MsdkApi::checkToken($params);
		if (!$token) {
			return json_encode(array(
				'res' => RespCode::TENCENT_TOKEN_ERROR
			));
		}

		$time_now = time();
		global $logger;
		$activity = Activity::get($activity_id);
		if (empty($activity)) {
			$logger->log("GetPointRanking: cannot found activity. activity_id: $activity_id");
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}

		$ranking_id = UserPointRanking::getRankingIdFromActivity($activity);
		if (empty($ranking_id)) {
			$logger->log("GetPointRanking: missed ranking_id. activity_id: $activity_id");
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}

		$overall_rank_info_list = $this->getTopRankInfoList($ranking_id, $limit);
		$user_rank_info = $this->getUserRankInfo($user_id, $ranking_id);

		$ret = array(
			'res' => RespCode::SUCCESS,
			'ranking' => array(
				'overall' => $overall_rank_info_list,
				'user' => $user_rank_info,
			)
		);

		return json_encode($ret);
	}

	private function getUserRankInfo($user_id, $ranking_id)
	{
		$user_point_ranking = UserPointRanking::findBy(array('user_id' => $user_id, 'ranking_id' => $ranking_id));
		if (! $user_point_ranking) {
			return array();
		}

		list($user_rank, $user_score) = $user_point_ranking->getRankWithScore();
		if ($user_rank === false) {
			return array();
		}

		$user = User::find($user_id);
		$user_ranking = $this->getFormalizedRankInfo($user, $user_score, $user_rank);

		return $user_ranking;
	}

	private function getTopRankInfoList($ranking_id, $limit)
	{
		$limit = $limit > self::MAX_RANKING_LIMIT ? self::MAX_RANKING_LIMIT : $limit;

		// First, search from cache
		$redis = UserPointRanking::getRedis();
		$key = RedisCacheKey::getPointTopRankingCacheKey($ranking_id, self::MAX_RANKING_LIMIT);
		$rank_info_list = $redis->get($key);
		if ($rank_info_list) {
			return array_slice($rank_info_list, 0, $limit);
		}

		// If not found read latest ranking list  and cache it
		$rank_list = UserPointRanking::getTopRankList($ranking_id, self::MAX_RANKING_LIMIT);
		$rank_info_list = array();
		$r = 0;
		foreach($rank_list as $user_id => $score) {
			$pdo = Env::getDbConnectionForUserRead($user_id);
			$user = User::find($user_id, $pdo); // N+1 query not good but we have cache and max limit
			$rank_info_list[] = $this->getFormalizedRankInfo($user, $score, $r++);
		}

		if ($rank_info_list) {
			// Update cache
			$redis->set($key, $rank_info_list);
			$expire_at = $this->getCacheExpireAt();
			$redis->expireAt($key, $expire_at);
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
		$point = UserPointRanking::getPointFromScore($score);

		return array(
			'pid' => $user->id,
			'name' => $user->name,
			'qq_vip' => $user->qq_vip,
			'ten_gc' => isset($user->game_center) ? $user->game_center : User::NOT_GAME_CENTER,
			'lc' => $this->getLeaderCardInfo($user),
			'point' => $point,
			'rank' => $rank,
		);
	}

	private function getLeaderCardInfo($user)
	{
		$lc = $user->getLeaderCardsData();
		/*
		$info = array(
			intval($lc->id[0]),
			intval($lc->lv[0]),
			intval($lc->slv[0]),
			intval($lc->hp[0]),
			intval($lc->atk[0]),
			intval($lc->rec[0]),
			intval($lc->psk[0]),
		);
		*/

		//目前版本只需要返回卡片ID
		$id = intval($lc->id[0]);

		return $id;
	}
}
