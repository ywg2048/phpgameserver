<?php
/**
 * Admin用:Tlog実行テスト
 */
class AdminTestTlog extends AdminBaseAction {
	public function action($params) {
		$dummy = isset ( $params ['dummy'] ) ? $params ['dummy'] : 0;
		$start = isset ( $params ['start'] ) ? $params ['start'] : null;
		$end = isset ( $params ['end'] ) ? $params ['end'] : null;
		$type = (isset ( $params ['type'] ) && $params ['type'] != '') ? $params ['type'] : null;
		
		if ($dummy) {
			self::dummy_output ();
			Padc_Log_Log::sendTlogHistory();
		}
		
		$logdata = self::getTlogRange ( Env::LOG_PATH, 'tlog.log', $start, $end, $type );
		
		$logdata = self::filterType ( $type, $logdata );
		//global $logger;
		//$logger->log ( 'logdata:' . print_r ( $logdata, true ), 7 );
		
		$result = array (
				'format'	=> 'array',
				'TLOGデータ'		=> $logdata,
				//'res' => RespCode::SUCCESS,
				//'log' => $logdata,
		);
		return json_encode ( $result );
	}
	
	/**
	 *
	 * @param string $prefix        	
	 * @param string $suffix        	
	 * @param string $start        	
	 * @param string $end        	
	 * @return array
	 */
	private static function getTlogRange($prefix = null, $suffix = null, $start = null, $end = null, $type = null) {
		$str_date_start = (isset ( $start ) && $start != '') ? $start : date ( 'Ymd' );
		$str_date_end = (isset ( $end ) && $end != '') ? $end : date ( 'Ymd' );
		$dt = new DateTime ( $str_date_start );
		$dtend = new DateTime ( $str_date_end );
		$columns = self::getColumnNames($type);
		//global $logger;
		//$logger->log ( '$columns:' . print_r($columns, true), 7 );
		$logdata = array ('format' => 'array', 0 => $columns);
		//$logdata = array ($columns);
		//$logger->log ( '$logdata:' . print_r($logdata, true), 7 );
		//$logger->log ( '' . $str_date_start . ' : ' . $str_date_end, 7 );
		//$logger->log ( '' . $dt->getTimestamp () . ' : ' . $dtend->getTimestamp (), 7 );
		while ( $dt->getTimestamp () <= $dtend->getTimestamp () ) {
			for($h = 0; $h < 24; $h++){
				$fn = '' . $prefix . $dt->format ( 'Ymd' ) . sprintf("%02d", $h) . $suffix;
				global $logger;
				$logger->log ( 'fn:' . $fn, 7 );
				$daylogdata = self::getTlogfile ( $fn );
				// $logger->log ( '$logdata:' . json_encode ( $logdata ), 7 );
				if ($daylogdata) {
					//$logger->log ( 'merge $logdata:' . print_r($logdata, true), 7 );
					//$logger->log ( 'merge $daylogdata:' . print_r($daylogdata, true), 7 );
					$logdata = array_merge ( $logdata, $daylogdata );
				}
			}
			$dt->modify ( '+1 hour' );
		}
		//$logdata['format'] = 'array';
		//$logger->log ( 'range return $logdata:' . print_r($logdata, true), 7 );
		return $logdata;
	}
	
	/**
	 *
	 * @param string $fn        	
	 * @return array
	 */
	private static function getTlogfile($fn) {
		if (! file_exists ( $fn )) {
			//global $logger;
			//$logger->log ( 'file not exist: ' . $fn, 7 );
			return null;
		}
		$contents = file_get_contents ( $fn );
		$lines = explode ( "\n", $contents );
		// global $logger;
		// $logger->log('lines:'.print_r($lines, true), 7);
		$datas = array ();
		foreach ( $lines as $line ) {
			if (strlen ( $line ) == 0) {
				continue;
			}
			$data = explode ( '|', $line );
			if (! (empty ( $data ))) {
				$datas [] = $data;
			}
		}
		return $datas;
	}
	
	/**
	 *
	 * @param string $type        	
	 * @param array $logdata        	
	 * @return array
	 */
	private static function filterType($type, $logdata) {
		if (! isset ( $type )) {
			return $logdata;
		}
		$num = count ( $logdata );
		for($i = 1; $i < $num; $i ++) {
			if (isset($logdata [$i] [0]) && $logdata [$i] [0] != $type) {
				unset ( $logdata [$i] );
			}
		}
		//$logdata = array_values ( $logdata );
		return $logdata;
	}
	
	private static function getColumnNames($type){
		$class_names = Tencent_Tlog::getTlogClassNames();
		//global $logger;
		//$logger->log('$class_names:'.print_r($class_names, true), 7);
		foreach($class_names as $class_name){
			//$logger->log('$class_name:'.$class_name, 7);
			//$logger->log('$class_name:'.$class_name::EVENT, 7);
			if($class_name::EVENT == $type){
				//$logger->log('Columns:'.print_r(TlogPlayerLogin::getColumns(), true), 7);
				//$logger->log('Columns:'.print_r($class_name::getColumns(), true), 7);
				//break;
				return $class_name::getColumns();
			}
		}
		return array();
	}
	
	/**
	 * output dummy tlog data
	 */
	private static function dummy_output() {
		$oid = 'A0071C0DDDC0F74BE1413598297F3A41';
		$t = 1;
		$pt = 1;
		Padc_Log_Log::writePlayerRegister ( $oid, $t, $pt, 1001 , 'dummy_device_id');
		Padc_Log_Log::writePlayerLogin ( $t, $oid, 0, 0, 7.4, $pt, 1001, 0, 0, 100, 1000 , 'dummy_device_id');
		Padc_Log_Log::sendPlayerLogout ( $t, $oid, 1, 1, 0, 7.4, null, null, null, null, 0, 0, 0, 1001, null, 0, null, null, null, $pt );
		Padc_Log_Log::sendMoneyFlow ( $t, $oid, 0, 10, Tencent_Tlog::REASON_BONUS, Tencent_Tlog::ADD, Tencent_Tlog::MONEY_TYPE_MONEY, 10, 0, 0, $pt, 0, Tencent_Tlog::SUBREASON_LOGIN_BONUS );
		Padc_Log_Log::sendItemFlow ( $t, $oid, 0, Tencent_Tlog::GOOD_TYPE_PIECE, 10008, 1, 1, Tencent_Tlog::ITEM_REASON_BONUS, 0, 0, 0, Tencent_Tlog::ADD, 2, $pt );
		Padc_Log_Log::sendPlayerExpFlow ( $t, $oid, 1, 1, 2, 1, 1, 0, $pt );
		Padc_Log_Log::sendSnsFlow ( $t, $oid, 1, Tencent_Tlog::SNSTYPE_RECEIVEEMAIL, 'toid', $pt );
		Padc_Log_Log::sendRoundFlow ( $t, $oid, 10001, Tencent_Tlog::BATTLE_TYPE_NORMAL, 100, 0, 0, 0, 100, 0, '1100ff', '2015-01-11 09:11:11', 2, 1.5, $pt );
		Padc_Log_Log::sendIDIPFlow ( 992, $oid, 9998, 1, 'serial', 123, 100, 10008, $pt );
		Padc_Log_Log::sendDeckFlow ( $t, $oid, '[[[1,1,1,0,0,0]],[[1,1,1,0,0,0]],[[1,1,1,0,0,0]],[[1,1,1,0,0,0]],[[1,1,1,0,0,0]],[[1,1,1,0,0,0]],[[1,1,1,0,0,0]]]', 512, $pt );
		Padc_Log_Log::sendSneakDungeon ( $t, $oid, 10001, Tencent_Tlog::BATTLE_TYPE_NORMAL, '[[1,2,1],[0,0,0],[0,0,0],[0,0,0],[0,0,0]]', 512, 0, 1, 'foid', '1100ff', 1, 0, '2015-01-11 09:11:11', $pt );
		Padc_Log_Log::sendGuideFlow ( $t, $oid, 2, 1, $pt );
		Padc_Log_Log::sendMissionFlow ( $t, $oid, 1, 1, 1, $pt );
		Padc_Log_Log::sendShareFlow ( $t, $oid, 2, 2, 0, 1, 1, $pt );
		Padc_Log_Log::sendMonthlyReward ( $t, $oid, 1, 1, 100, $pt );
		Padc_Log_Log::sendComposite ( $t, $oid, 1, 1, 1, 2, 1, 0, 1, 0, 0, 100, 0, 0, 0, $pt );
		Padc_Log_Log::sendEvolution ( $t, $oid, 1, 2, 1, 1, 0, 1, 0, 0, 0, 100, $pt );
		Padc_Log_Log::sendVipLevel ( $t, $oid, 1, 1, $pt, 'name', 60, 0, 120 , 0);
		Padc_Log_Log::sendRanking ( $t, 1, 0, '2015-01-11 09:11:11', json_encode ( array () ), json_encode ( array () ), json_encode ( array () ), json_encode ( array () ), json_encode ( array () ), $pt );
		Padc_Log_Log::sendFailedSneak ( $t, $oid, 2, '2015-01-11 09:11:11', $pt );
		//Padc_Log_Log::sendSecRoundStartFlow($t, $pt, $oid);
		//UserTlog::sendTlogSecRoundStartFlow();
		//Padc_Log_Log::sendSecRoundEndFlow($t,$pt,$oid);
		Padc_Log_Log::sendSecTalkFlow( array('pt' => $pt, 'OpenID' => $oid, 'PlatID' => $t, 'AreaID' => 1, 'RoleLevel' => 1, 'UserIP' => '127.0.0.1', 'ReceiverOpenID' => 'ropenid', 'ReceiverRoleLevel' => 1, 'ReceiverIP' => null, 'ChatType' => 1, 'TitleContents' => null, 'ChatContents' => 'test'));
		Padc_Log_Log::sendChangeName($t, $oid, $pt, "before_name", "after_name");
		Padc_Log_Log::sendMonthlyCard($t, $oid, $pt, "2015-10-11 12:12:12");
	}
}