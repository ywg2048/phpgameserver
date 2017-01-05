<?php
/**
 * #PADC_DY#
 * 玩家积分排名记录
 */
class UserPointRanking extends BaseModel {
	const TABLE_NAME = "user_point_ranking";
	const TIME_WEIGHT_MAX = 9999999;
	const TIME_WEIGHT_HOLD = 10000000; // Score的最后7位为时间权重，可以支撑的活动期间为115天
	const HAS_UPDATED_AT = FALSE; // 为了使用php里的time更新设置为false

	protected static $columns = array(
		'id',
		'user_id',
		'ranking_id',
		'point',
		'updated_at',
		'created_at',
	);

	public static function isPointRankingActivityOpen($activity_type)
	{
		$is_open = false;
		$activities = Activity::getAllBy(array('del_flg' => 0,'activity_type' => $activity_type));
		if (empty($activities)) {
			return array(false, null);
		}

		foreach ($activities as $activity) {
			if ($activity->isEnabled(time())) {
				return array(true, $activity);
				break;
			}
		}

		return array(false, null);
	}

	public static function getRankingIdFromActivity($activity)
	{
		$activity_condition = json_decode($activity->reward_condition, true);
		return isset($activity_condition["ranking_id"]) ? $activity_condition["ranking_id"] : null;
	}

	public static function savePointToCurrentRanking($user_id, $add_point, $activity_type, $pdo = null)
	{
		list($is_open, $activity) = self::isPointRankingActivityOpen($activity_type);
		if (! $is_open) {
			return false;
		}

		$ranking_id = self::getRankingIdFromActivity($activity);
		if (empty($ranking_id)) {
			return false;
		}

		$time_now = time();
		$user_point_ranking = self::findBy(array('user_id' => $user_id, 'ranking_id' => $ranking_id), $pdo);
		if (empty($user_point_ranking)) {
			$user_point_ranking = new UserPointRanking();
			$user_point_ranking->user_id = $user_id;
			$user_point_ranking->ranking_id = $ranking_id;
			$user_point_ranking->point = $add_point;
			$user_point_ranking->updated_at = date('Y-m-d H:i:s', $time_now);
			$user_point_ranking->create($pdo);
		} else {
			$user_point_ranking->point += $add_point;
			$user_point_ranking->updated_at = date('Y-m-d H:i:s', $time_now);
			$user_point_ranking->update($pdo);
		}

		$activity_end_time = BaseModel::strToTime($activity->finish_at);
		$time_weight = $activity_end_time - $time_now; // 同分的情况下，优先到达的排前
		$score = self::caculateScore($user_point_ranking->point, $time_weight);
		$user_point_ranking->updateRank($score);

		return $user_point_ranking;
	}

	public function updateRank($score)
	{
		$key = self::getRankingRedisKey($this->ranking_id);
		$redis = self::getRedis();

		$redis->zAdd($key, $score, $this->user_id);
	}

	public static function caculateScore($point, $time_weight)
	{
		if ($time_weight > self::TIME_WEIGHT_MAX) {
			global $logger;
			$logger->log("UserPointRanking#caculateScore: time weight exceeded max value!");
		}

		return $point * self::TIME_WEIGHT_HOLD + $time_weight;
	}

	public static function getPointFromScore($score)
	{
		return intval($score / self::TIME_WEIGHT_HOLD);
	}

	public static function getTimeweightFromScore($score)
	{
		return $score % self::TIME_WEIGHT_HOLD;
	}

	public static function getTopRankList($ranking_id, $limit = 100)
	{
		$key = self::getRankingRedisKey($ranking_id);
		$redis = self::getRedis();

		$rank_list = $redis->zRevRange($key, 0, $limit, true);

		return $rank_list;
	}

	/*
	 * 获取排行榜的第几名~第几名
	 *
	 * $start_rank 从0算起的排名（第一名0)
	 * $end_rank 从0算起的排名（第一名0)
	 */
	public static function getRankList($ranking_id, $start_rank, $end_rank)
	{
		$key = self::getRankingRedisKey($ranking_id);
		$redis = self::getRedis();

		$rank_list = $redis->zRevRange($key, $start_rank, $end_rank, true);

		return $rank_list;
	}

	public function getRank()
	{
		$key = self::getRankingRedisKey($this->ranking_id);
		$redis = self::getRedis();

		$rank = $redis->zRevRank($key, $this->user_id);
		return $rank;
	}

	public function getRankWithScore()
	{
		$key = self::getRankingRedisKey($this->ranking_id);
		$redis = self::getRedis();

		$rank = $redis->zRevRank($key, $this->user_id);
		$score = $redis->zScore($key, $this->user_id);

		return array($rank, $score);
	}

	public static function getRankingRedisKey($ranking_id)
	{
		$key = RedisCacheKey::getPointRankingKey($ranking_id);
		return $key;
	}

	public static function getRedis()
	{
		$redis = Env::getRedis(Env::REDIS_USER);
		return $redis;
	}
}
