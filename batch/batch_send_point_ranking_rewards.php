<?php
$host = FALSE;
if(count($argv) > 1)
{
	$host = $argv[1];
}
if(!$host) {
	exit("[Error] no host name\n");
}

require_once ("../app/config/autoload.php");
setEnvironment($host);

//Tlog初期化
Tencent_Tlog::init(gethostname(), Env::TLOG_ZONEID);
if (Env::TLOG_SERVER != null && Env::TLOG_PORT != null) {
	Tencent_Tlog::setServer(Env::TLOG_SERVER, Env::TLOG_PORT);
}

echo '[Started] ' . strftime("%y/%m/%d %H:%M:%S",time()) . "\n";
try {
	$activity_type = Activity::ACTIVITY_TYPE_CONSUM_POINT_RANKING;
	$activities = Activity::findAllBy(array('del_flg' => 0,'activity_type' => $activity_type));

	foreach($activities as $activity) {
		if ($activity->reward_send_status > Activity::REWARD_SEND_STATUS_INIT) {
			// 奖励已发放
			continue;
		}
		
		$activity_id = $activity->id;
		$time_now = time();
		$reward_send_at = BaseModel::strToTime($activity->reward_send_at);
		$activity_end_at = BaseModel::strToTime($activity->finish_at);

		if ($reward_send_at < $activity_end_at) {
			echo "[ERROR] reward_send_at value can not less than finish_at value for Activity. activity_id: $activity_id\n";
			exit(1);
		}

		if ($time_now <= $reward_send_at) {
			// it's not time for sending reward yet
			continue;
		}

		$reward_condition = $activity->getCondition();
		if (!isset($reward_condition['ranking_id']) || !isset($reward_condition['rank'])) {
			echo "[ERROR] reward_condition is wrong! activity_id: $activity_id\n";
			exit(1);
		}
		if (count($reward_condition['rank']) != 2) {
			echo "[ERROR] reward_condition#rank is misformatted! activity_id: $activity_id\n";
			exit(1);
		}

		$ranking_id = $reward_condition['ranking_id']; // TODO isset
		list($start, $end) = $reward_condition['rank'];
		echo "[Rank Range] from_rank: $start, to_rank: $end\n";

		$rank_list = UserPointRanking::getRankList($ranking_id, $start - 1, $end - 1);

		foreach($rank_list as $user_id => $score) {
			usleep(50000); // sleep 0.05s
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$activity->sendRewardsToUser($user_id, $pdo);
			UserActivity::updateStatus($user_id, $activity_id, UserActivity::STATE_RECEIVED, $pdo);
			echo "[Sent Reward] user_id: $user_id, activity_id: $activity_id\n";
		}

		$activity->reward_send_status = Activity::REWARD_SEND_STATUS_SENT;
		$activity->update();
	}
} catch(Exception $e) {
	echo '[ERROR] Message: ' . $e->getMessage() . "\n";
	echo "[ERROR] Dump: \n";
	var_dump($e);
	return;
}
echo '[Finished] ' . strftime("%y/%m/%d %H:%M:%S",time()) . "\n";







