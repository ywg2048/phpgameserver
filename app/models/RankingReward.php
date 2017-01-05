<?php
/**
 * #PADC#
 * ランキング報酬
 */

class RankingReward extends BaseMasterModel {
	const TABLE_NAME = "padc_ranking_reward";
	const VER_KEY_GROUP = "padcranking";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array(
		'id',
		'reward_id',
		'ranking_id',
		'reward_get_rank_top',
		'reward_get_rank_bottom',
		'bonus_id',
		'amount',
		'piece_id',
		'message',
	);

	public function applyReward($user, $pdo, $token)
	{
		$user->applyBonus($this->bonus_id,$this->amount,$pdo,null,$token,$this->piece_id);
	}

	public static function applyRewardsMail($user_id, $rewards, $pdo)
	{	
		$reward_record = UserRankingReward::findBy(array('user_id'=>$user_id));
		if($reward_record){
			return false;
		}
		$histories = UserRankingReward::findAllBy(array('user_id'=>$user_id), null, null, $pdo, null);
	    $history_ids = array();
	    foreach($histories as $history){
			$history_ids[$history->id] = $history->ranking_reward_id;
	    }
	    foreach($rewards as $reward){
			if(in_array($reward->reward_id, $history_ids)){
				// 既に該当報酬を付与済み
				continue;
			}
			$user_ranking_reward = new UserRankingReward();
			$user_ranking_reward->user_id = $user_id;
			$user_ranking_reward->ranking_reward_id = $reward->reward_id;
			$user_ranking_reward->create($pdo);
			UserMail::sendAdminMailMessage($user_id, UserMail::TYPE_ADMIN_BONUS, $reward->bonus_id, $reward->amount, $pdo, $reward->message, null, $reward->piece_id);
			$applied_bonuses[] = $reward;
	    }
	}

	// MY : ユーザが取得できる報酬を取得。
	public static function getReward($current_time, $user_id)
	{
		$pdo = Env::getDbConnectionForShareRead();
		$user_ranking = Ranking::findBy(array('user_id' => $user_id),$pdo);

		$ret = null;
		if($user_ranking)
		{
			$num = $user_ranking->ranking_number;
			$limited_ranking = LimitedRanking::findBy(array('ranking_id'=>$user_ranking->ranking_id));
			$aggregate_data = RankingAggregateData::find($user_ranking->ranking_id,$pdo);
			
			if($aggregate_data && $limited_ranking)
			{
				if($aggregate_data->checkAggregateEnd($limited_ranking->end_time) && User::strToTime($current_time) < User::strToTime($limited_ranking->reward_get_end_time))
				{
					$sql = 'SELECT * FROM '.static::TABLE_NAME.' WHERE ranking_id = ? AND (? >= reward_get_rank_top OR reward_get_rank_top = 0) AND (? <= reward_get_rank_bottom OR reward_get_rank_bottom = 0)';
					$values = array($user_ranking->ranking_id,$num,$num);
					$stmt = $pdo->prepare($sql);
					$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
					$stmt->execute($values);
					$ret = $stmt->fetchAll();
				}
			}
		}
		return $ret;
	}

	public static function getRewardList($ranking_id){
		$pdo = Env::getDbConnectionForShareRead();
		
		$sql = 'SELECT reward_get_rank_top,reward_get_rank_bottom,bonus_id,amount,piece_id FROM '.static::TABLE_NAME.' WHERE ranking_id=?';
		$values = array($ranking_id);
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);
		$ret = $stmt->fetchAll();
		return $ret;
	}
}
