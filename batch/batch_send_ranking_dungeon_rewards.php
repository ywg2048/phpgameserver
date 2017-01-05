<?php
//发送排名关卡奖励脚本
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
	$reward_ranking_id = LimitedRanking::getOpeningRankingDungeon();
	
	$current_time = time();
	$ranking_info = LimitedRanking::findBy(array('ranking_id'=>$reward_ranking_id));	
	if(!$ranking_info){
		echo '[ERROR] Message'."not ranking in current time";
	}
	
	$aggregate_end_time = BaseModel::strToTime($ranking_info->aggregate_end_time);//结算结束时间
	$reward_end_at = BaseModel::strToTime($ranking_info->reward_get_end_time);//奖励领取结束时间
	if($current_time>$aggregate_end_time && $current_time < $reward_end_at){
		//可以发奖
		$pdo_share = Env::getDbConnectionForShare();
		$ranking_lists = Ranking::findAllBy(array(),null,null,$pdo_share);
		foreach ($ranking_lists as $ranking_list) {
			usleep(50000);//sleep 0.05s
			$pdo = Env::getDbConnectionForUserWrite($ranking_list->user_id);
			$rewards =  RankingReward::getReward($current_time,$ranking_list->user_id);
			RankingReward::applyRewardsMail($ranking_list->user_id,$rewards,$pdo);
			
		}
		$truncate_tables = array('user_ranking','user_ranking_dungeon_floors','user_ranking_dungeons','user_ranking_record','user_ranking_reward');
		foreach ($truncate_tables as $truncate_table){
			$pdo_users = Env::getDbConnectionForUserAllWrite();
			foreach ($pdo_users as $pdo_user) {
				$sql = "delete from {$truncate_table}";
				$stmt = $pdo_user->prepare($sql);
				$stmt->setFetchMode(PDO::FETCH_NUM);
				$stmt->execute();
			}
		}
		$pdo_share = Env::getDbConnectionForShare();
		$sql = "delete from padc_ranking";
		$stmt = $pdo_share->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$stmt->execute();

	}else{
		//not reward time yet
		echo '[ERROR] Message not in reward time';
	}

} catch(Exception $e) {
	echo '[ERROR] Message: ' . $e->getMessage() . "\n";
	echo "[ERROR] Dump: \n";
	var_dump($e);
	return;
}
echo '[Finished] ' . strftime("%y/%m/%d %H:%M:%S",time()) . "\n";
