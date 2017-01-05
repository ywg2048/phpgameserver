<?php
$LOG_FILE_LOGOUT = 'logout.log';
$LOGOUT_TIME = 4200;//1h10m
$DEFAULT_INTERVAL = 1800;

$host = FALSE;
if (count ( $argv ) > 1) {
	$host = $argv [1];
}
if ($host) {
	require_once ("../app/config/autoload.php");
	setEnvironment ( $host );

	//Tlog初期化
	Tencent_Tlog::init(gethostname(), Env::TLOG_ZONEID);
	if(Env::TLOG_SERVER != null && Env::TLOG_PORT != null){
		Tencent_Tlog::setServer(Env::TLOG_SERVER, Env::TLOG_PORT);
	}
		
	try {
		$start_time = getStartTime ();
		$end_time = getEndTime ();
		if (! isset ( $start_time )) {
			$start_time = BaseModel::timeToStr ( BaseModel::strToTime ( $end_time ) - $DEFAULT_INTERVAL );
		}
		userLogout ( $start_time, $end_time );
		writeEndTime ( $end_time );
	} catch ( Exception $e ) {
		echo 'ERROR Message: ' . $e->getMessage () . "\n";
		echo "ERROR Dump: \n";
		var_dump ( $e );
		return;
	}
	echo strftime ( "%y/%m/%d %H:%M:%S", time () ) . ' : success' . "\n";
} else {
	echo "no_host_name\n";
}
return;

/**
 *
 * @return string
 */
function getStartTime() {
	// return '2015-01-01 00:00:00';
	global $LOG_FILE_LOGOUT;
	$logFile = Env::LOG_PATH . $LOG_FILE_LOGOUT;
	
	$str_time = null;
	if (file_exists ( $logFile )) {
		$str_time = file_get_contents ( $logFile );
	}
	
	if (! $str_time || $str_time != BaseModel::timeToStr ( BaseModel::strToTime ( $str_time ) )) {
		$str_time = null;
	}
	
	// echo 'start time from file:'.$str_time."\n";
	return $str_time;
}

/**
 *
 * @return string
 */
function getEndTime() {
	global $LOGOUT_TIME;
	return BaseModel::timeToStr ( time () - $LOGOUT_TIME );
}

/**
 *
 * @param string $endTime        	
 */
function writeEndTime($endTime) {
	global $LOG_FILE_LOGOUT;
	$logFile = Env::LOG_PATH . $LOG_FILE_LOGOUT;
	
	$file = fopen ( $logFile, "w+" ) or die ( "Unable to open file!" );
	try {
		chmod ( $logFile, 0777 );
	} catch ( Exception $e ) {
	}
	fwrite ( $file, $endTime );
	fclose ( $file );
}
/**
 *
 * @param string $start_time        	
 * @param string $end_time        	
 */
function userLogout($start_time, $end_time) {
	$dsns = Env::getAllReadDSN ();
	$users_cnt = 0;
	$logout_time = time ();
	foreach ( $dsns as $dsn => $dbid ) {
		$pdo = Env::getDbConnectionForUserRead ( 1, $dbid );
		// #PADC_DY# ----------begin----------
		// $sql = 'SELECT id,clear_dungeon_cnt,fricnt,li_last,login_channel,device_id,vip_lv,tss_end,pgold,gold,coin FROM ' . User::TABLE_NAME . ' WHERE accessed_at >= ? AND accessed_at < ?';
		$sql = 'SELECT id,lv,fricnt,li_last,login_channel,device_id,vip_lv,tss_end,pgold,gold,coin FROM ' . User::TABLE_NAME . ' WHERE accessed_at >= ? AND accessed_at < ?';
		// #PADC_DY# ----------end----------
		$stmt = $pdo->prepare ( $sql );
		$values = array (
				$start_time,
				$end_time 
		);
		// $stmt->setFetchMode(PDO::FETCH_COLUMN, 0);
		if (Env::ENV !== "production") {
			echo 'sql: ' . $sql . '; bind: ' . join ( ',', $values ) . "\n";
		}
		$stmt->execute ( $values );
		$users = $stmt->fetchAll ();
		foreach ( $users as $user ) {
			// echo 'user:' . print_r ( $user, true ) . "\n";
			$users_cnt ++;
			$login_channel = isset ( $user ['login_channel'] ) ? $user ['login_channel'] : 0;
			$subs = (isset($user['tss_end']) && time() < strToTime($user['tss_end']))? 1 : 0;
			// #PADC_DY# ----------begin----------
			// UserTlog::sendTlogPlayerLogout ( $user ['id'], $user ['clear_dungeon_cnt'], $user ['fricnt'], $user ['li_last'], $logout_time, $login_channel, $user ['device_id'], $user['vip_lv'], $subs, $user['pgold'] + $user['gold'], $user['coin'] );
			UserTlog::sendTlogPlayerLogout ( $user ['id'], $user ['lv'], $user ['fricnt'], $user ['li_last'], $logout_time, $login_channel, $user ['device_id'], $user['vip_lv'], $subs, $user['pgold'] + $user['gold'], $user['coin'] );
			// #PADC_DY# ----------end----------
		}
	}
	
	Padc_Log_Log::sendTlogHistory ();
	
	if (Env::ENV !== "production") {
		echo '' . $users_cnt . " users logged out.\n";
	}
}
