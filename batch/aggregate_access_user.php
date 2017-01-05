<?php
$host = FALSE;
if(count($argv) > 1)
{
	$host = $argv[1];	
}
if($host)
{

	require_once ("../app/config/autoload.php");
	setEnvironment($host);
	try
	{
		$INTERVAL = 30;
		$exec_limit = 5000;
		$count = 0;
		$share = Env::getDbConnectionForShareRead();
		$sql = 'SELECT DISTINCT dbid FROM '.UserDevice::TABLE_NAME;
		$stmt = $share->query($sql);
		$dbids = $stmt->fetchAll(PDO::FETCH_COLUMN);
		$device_types = array(UserDevice::TYPE_IOS,UserDevice::TYPE_ADR);
		$user_count_list = array();
		$log_user = array();
		$aggregate_time_int = time();
		$aggregate_time = BaseModel::timeToStr($aggregate_time_int);
		foreach($device_types as $device_type)
		{
			$user_count_list[$device_type] = 0;
		}
		foreach($dbids as $dbid)
		{
			$count = UserDevice::countAllBy(array('dbid'=>$dbid),$share);
			for($i = 0; $i <= floor($count / $exec_limit); $i++)
			{
				$sql = 'SELECT id,type FROM '.UserDevice::TABLE_NAME.' where dbid = '.$dbid.' order by id asc limit '.($i * $exec_limit).','.$exec_limit;
				$stmt = $share->query($sql);
				$user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
				
				$user_pdo = Env::getDbConnectionForUserRead(1,$dbid);
				$sql = 'select count(id) FROM '.User::TABLE_NAME.' where accessed_at > ("'.$aggregate_time.'" - INTERVAL '.$INTERVAL.' MINUTE)';
				$stmt = $user_pdo->query($sql);
				$count_rec = $stmt->fetchAll(PDO::FETCH_COLUMN);
				$user_count = $count_rec[0];
				for($j = 0; $j <= floor($user_count / $exec_limit); $j++)
				{
					$sql = 'select id FROM '.User::TABLE_NAME.' where accessed_at > ("'.$aggregate_time.'" - INTERVAL '.$INTERVAL.' MINUTE) order by accessed_at asc limit '.($j * $exec_limit).','.$exec_limit;

					$stmt = $user_pdo->query($sql);

					$access_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

					foreach($access_users as $access_user)
					{
						if(array_key_exists($access_user,$user_ids))
						{
							$user_count_list[$user_ids[$access_user][0]]++;
							// $log_user[] = $access_user." _".$dbid;
						}
					}
				}
			}
		}
		TbOnlineCnt::log(Env::TLOG_VGAMEAPP_ID,$aggregate_time_int,Env::TLOG_GAMESVR_ID,Env::TLOG_IZONEAREA_ID,$user_count_list[UserDevice::TYPE_IOS],$user_count_list[UserDevice::TYPE_ADR]);
	}
	catch(Exception $e)
	{
		echo 'ERROR Message: ' . $e->getMessage() . "\n";
		echo "ERROR Dump: \n";
		var_dump($e);
		return;
	}
	echo strftime("%y/%m/%d %H:%M:%S",time()).' : success'."\n";
}
else
{
	echo "no_host_name\n";
}
return;