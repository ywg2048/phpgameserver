<?php
require_once 'Tencent/Tlog.php';

/**
 * #PADC#
 * ログ関連処理クラス
 */
class Padc_Log_Log {

	// tlog ヒストリ
	private static $tlog_history = array();
	
	/**
	 * 日付付きのログファイル名を取得
	 * 
	 * @param string $logname        	
	 * @return string
	 */
	public static function getLogName($logname) {
		$time = Padc_Time_Time::getDate ( "YmdH" );
		$logname = sprintf ( "%s%s", $time, $logname );
		return $logname;
	}
	
	/**
	 * ログ出力先ファイルパスを取得
	 * 
	 * @return string
	 */
	public static function getLogFile() {
		$logFile = Env::LOG_PATH . self::getLogName ( Env::LOG_FILE );
		return $logFile;
	}
	
	/**
	 * ログ出力先ファイルパスを取得（エラー用）
	 * 
	 * @return string
	 */
	public static function getErrorLogFile() {
		$logFile = Env::LOG_PATH . self::getLogName ( Env::ERR_LOG_FILE );
		return $logFile;
	}
	
	/**
	 * ログ出力先ファイルパスを取得（課金用）
	 * 
	 * @return string
	 */
	public static function getPurchaseLogFile() {
		$logFile = Env::LOG_PATH . self::getLogName ( Env::PURCHASE_LOG_FILE );
		return $logFile;
	}
	
	/**
	 * ログ出力先ファイルパスを取得（レアガチャ用）
	 * 
	 * @return string
	 */
	public static function getRareGachaLogFile() {
		$logFile = Env::LOG_PATH . self::getLogName ( Env::RARE_GACHA_LOG_FILE );
		return $logFile;
	}
	
	/**
	 * ログ出力先ファイルパスを取得（Extraガチャ用）
	 * 
	 * @return string
	 */
	public static function getExtraGachaLogFile() {
		$logFile = Env::LOG_PATH . self::getLogName ( Env::EXTRA_GACHA_LOG_FILE );
		return $logFile;
	}
	
	/**
	 * ログ出力先ファイルパスを取得（Fluent用）
	 * 
	 * @return string
	 */
	public static function getFluentErrorFile() {
		$logFile = Env::LOG_PATH . self::getLogName ( Env::FLUENT_ERROR_FILE );
		return $logFile;
	}
	
	/**
	 * ユーザ登録ログを出力
	 * 
	 * @param string $openid        	
	 * @param int $type        	
	 * @param int $ptype        	
	 * @throws PadException
	 */
	public static function writePlayerRegister($openid, $type, $ptype, $reg_channel_id,$device_id) {
		$logBody = array (
				'oid' => $openid,
				't' => $type,
				'pt' => $ptype,
				'cid' => $reg_channel_id,
				'did' => $device_id
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_PLAYERREGISTER );
		return;
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param number $openid        	
	 * @param number $level        	
	 * @param number $playerFriendsNum        	
	 * @param number $clientVersion        	
	 * @param number $ptype        	
	 */
	public static function writePlayerLogin($type, $openid, $level, $playerFriendsNum, $clientVersion, $ptype, $channel_id, $vip_lv, $subs, $gold, $coin, $device_id, $game_center = 0) {
		$logBody = array (
				'oid' => $openid,
				't' => $type,
				'lv' => $level,
				'fcnt' => $playerFriendsNum,
				'cv' => $clientVersion,
				'pt' => $ptype,
				'cid' => $channel_id,
				'vip_lv' => $vip_lv,
				'subs' => $subs,
				'gold' => $gold,
				'coin' => $coin,
				'did' => $device_id,
				'ten_gc' => $game_center
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_PLAYERLOGIN );
		return;
	}
	
	/**
	 *
	 * @param int $type        	
	 * @param string $openid        	
	 * @param unknown $level        	
	 * @param unknown $money        	
	 * @param unknown $reason        	
	 * @param unknown $addOrReduce        	
	 * @param unknown $moneyType        	
	 * @param unknown $ptype        	
	 */
	public static function sendMoneyFlow($type, $openid, $level, $money, $reason, $addOrReduce, $moneyType, $afterMoney, $goldFree, $goldBuy, $ptype, $sequence = 0, $subReason = 0, $roundTicket = 0, $gachaId = null, $missionId = null) {
		$logBody = array (
				'oid' => $openid,
				't' => $type,
				'lv' => $level,
				'm' => $money,
				'r' => $reason,
				'aor' => $addOrReduce,
				'mt' => $moneyType,
				'am' => $afterMoney,
				'gold_free' => $goldFree,
				'gold_buy' => $goldBuy,
				'seq' => $sequence,
				'sr' => $subReason,
				'pt' => $ptype,
				'round' => $roundTicket,  // #PADC_DY#
				'gid' => $gachaId,  // #PADC_DY#
				'mission_id' => $missionId, //#PADC_DY#
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_MONEYFLOW );
		return;
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param string $openid        	
	 * @param number $level        	
	 * @param number $goodsType        	
	 * @param number $goodsId        	
	 * @param number $count        	
	 * @param number $afterCount        	
	 * @param number $reason        	
	 * @param number $subReason        	
	 * @param number $money        	
	 * @param number $moneyType        	
	 * @param number $addOrReduce        	
	 */
	public static function sendItemFlow($type, $openid, $level, $goodsType, $goodsId, $count, $afterCount, $reason, $subReason, $money, $moneyType, $addOrReduce, $rare, $ptype, $sequence = 0, $roundTicket = 0, $missionId = null) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'lv' => $level,
				'gt' => $goodsType,
				'gid' => $goodsId,
				'c' => $count,
				'ac' => $afterCount,
				'r' => $reason,
				'sr' => $subReason,
				'm' => $money,
				'mt' => $moneyType,
				'aor' => $addOrReduce,
				'ra' => $rare,
				'seq' => $sequence,
				'pt' => $ptype,
                // #PADC_DY# ----------begin----------
                'round' => $roundTicket,
                'mid' => $missionId
                // #PADC_DY# -----------end-----------
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_ITEMFLOW );
	}
	
	/**
	 * send Player Exp Flow
	 * @param int $type
	 * @param string $openid
	 * @param int $expChange
	 * @param int $beforeLevel
	 * @param int $afterLevel
	 * @param int $time
	 * @param int $reason
	 * @param int $subReason
	 * @param int $ptype
	 */
	public static function sendPlayerExpFlow($type, $openid, $expChange,$beforeLevel, $afterLevel, $time, $reason, $subReason, $ptype)
	{
		$logBody = array(
			'oid'	=> $openid,
			't'		=> $type,
			'ec' 	=> $expChange,
			'blv'	=> $beforeLevel,
			'alv' 	=> $afterLevel,
			'time' 	=> $time,
			'r' 	=> $reason,
			'sr' 	=> $subReason,
			'pt' 	=> $ptype,
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_PLAYEREXPFLOW );
		return;
	}
	
	/**
	 *
	 * @param unknown $type        	
	 * @param unknown $actorOpenID        	
	 * @param unknown $count        	
	 * @param unknown $snsType        	
	 * @param unknown $ptype        	
	 */
	public static function sendSnsFlow($type, $actorOpenID, $count, $snsType, $targetOpenID, $ptype) {
		$logBody = array (
				't' => $type,
				'aoid' => $actorOpenID,
				'c' => $count,
				'st' => $snsType,
				'toid' => $targetOpenID,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_SNSFLOW );
		return;
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param string $openid        	
	 * @param number $battleId        	
	 * @param number $battleType        	
	 * @param number $roundScore        	
	 * @param number $roundTime        	
	 * @param number $result        	
	 * @param number $rank        	
	 * @param number $coin        	
	 * @param number $cheat        	
	 * @param string $securitySDK        	
	 * @param string $sneakTime        	
	 * @param number $maxComboNum        	
	 * @param number $aveComboNum        	
	 * @param number $ptype        	
	 */
	public static function sendRoundFlow($type, $openid, $battleId, $battleType, $roundScore, $roundTime, $result, $rank, $coin, $cheat, $securitySDK, $sneakTime, $maxComboNum, $aveComboNum, $ptype, $roundTicket = 0, $diamond = 0, $starRating = 0) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'bid' => $battleId,
				'bt' => $battleType,
				'rs' => $roundScore,
				'rt' => $roundTime,
				'res' => $result,
				'r' => $rank,
				'g' => $coin,
				'ct' => $cheat,
				'ssdk' => $securitySDK,
				'st' => $sneakTime,
				'mcn' => $maxComboNum,
				'acn' => $aveComboNum,
				'pt' => $ptype,
                // #PADC_DY# ----------begin----------
                'round' => $roundTicket,
                'diamond' => $diamond,
                'star' => $starRating
                // #PADC_DY# -----------end-----------
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_ROUNDFLOW );
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $item_id        	
	 * @param number $item_num        	
	 * @param number $serial        	
	 * @param string $source        	
	 * @param number $cmd        	
	 * @param number $uuid        	
	 */
	public static function sendIDIPFlow($area_id, $openid, $item_id, $item_num, $serial, $source, $cmd, $uuid, $ptype) {
		$logBody = array (
				'area' => $area_id,
				'oid' => $openid,
				'item_id' => $item_id,
				'item_num' => $item_num,
				'serial' => $serial,
				'source' => $source,
				'cmd' => $cmd,
				'uuid' => $uuid,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_IDIPFLOW );
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param string $openid        	
	 * @param string $decks        	
	 */
	public static function sendDeckFlow($type, $openid, $decks, $totalPower, $ptype) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'decks' => $decks,
				'totalPower' => $totalPower,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_DECKFLOW );
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param string $openid        	
	 * @param number $bettle_id        	
	 * @param unknown $bettle_type        	
	 * @param unknown $deck        	
	 */
	public static function sendSneakDungeon($type, $openid, $bettle_id, $bettle_type, $deck, $totalPower, $roundTicket, $roundTicketNum, $friendOpenId, $securitySDK, $level, $vipLevel, $sneakTime, $ptype, $useStamina) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'bid' => $bettle_id,
				'btype' => $bettle_type,
				'deck' => $deck,
				'tp' => $totalPower,
				'rt' => $roundTicket,
				'rtn' => $roundTicketNum,
				'foid' => $friendOpenId,
				'ssdk' => $securitySDK,
				'lv' => $level,
				'vip_lv' => $vipLevel,
				'st' => $sneakTime,
				'pt' => $ptype,
				'us' => $useStamina
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_SNEAKDUNGEON );
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param string $openid        	
	 * @param number $guide_id        	
	 * @param number $ptype        	
	 */
	public static function sendGuideFlow($type, $openid, $guide_id, $full_ver, $ptype) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'gid' => $guide_id,
				'fullv' => $full_ver,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_GUIDEFLOW );
	}
	
	/**
	 * 
	 * @param int $type        	
	 * @param string $openid        	
	 * @param int $user_lv        	
	 * @param int $vip_lv        	
	 * @param int $ptype        	
	 */
	public static function sendVipLevel($type, $openid, $user_lv, $vip_lv, $ptype,$GameName,$AddExp,$IsLvUp,$VipExp, $LvUpExp) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'user_lv' => $user_lv,
				'vip_lv' => $vip_lv,
				'pt' => $ptype,
				'GameName' => $GameName,
				'AddExp' => $AddExp,
				'IsLvUp' => $IsLvUp,
				'VipExp' => $VipExp,
				'LvUpExp' => $LvUpExp
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_VIPLEVELUP );
	}
	
	/**
	 *
	 * @param int $type        	
	 * @param string $openid        	
	 * @param int $user_lv        	
	 * @param int $vip_lv        	
	 * @param int $gold        	
	 * @param int $ptype        	
	 */
	public static function sendMonthlyReward($type, $openid, $user_lv, $vip_lv, $gold, $ptype) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'user_lv' => $user_lv,
				'vip_lv' => $vip_lv,
				'gold' => $gold,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_MONTHLYREWARD );
	}
	
	/**
	 * send Composite
	 * 
	 * @param int $type        	
	 * @param string $openid        	
	 * @param int $card_id        	
	 * @param int $piece_id        	
	 * @param int $card_lv        	
	 * @param int $after_card_lv        	
	 * @param int $lv        	
	 * @param int $vip_lv        	
	 * @param int $piece_num        	
	 * @param int $generic_use        	
	 * @param int $generic_num        	
	 * @param int $money        	
	 * @param int $hp        	
	 * @param int $attack        	
	 * @param int $recover        	
	 * @param int $ptype        	
	 */
	public static function sendComposite($type, $openid, $card_id, $piece_id, $card_lv, $after_card_lv, $lv, $vip_lv, $piece_num, $generic_use, $generic_num, $money, $hp, $attack, $recover, $ptype) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'card_id' => $card_id,
				'piece_id' => $piece_id,
				'card_lv' => $card_lv,
				'after_card_lv' => $after_card_lv,
				'lv' => $lv,
				'vip_lv' => $vip_lv,
				'piece_num' => $piece_num,
				'generic_use' => $generic_use,
				'generic_num' => $generic_num,
				'money' => $money,
				'hp' => $hp,
				'attack' => $attack,
				'recover' => $recover,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_COMPOSITE );
	}
	
	/**
	 * send Evolution
	 * 
	 * @param int $type        	
	 * @param string $openid        	
	 * @param int $card_id        	
	 * @param int $after_card_id        	
	 * @param int $card_attribute        	
	 * @param int $lv        	
	 * @param int $vip_lv        	
	 * @param int $piece_num        	
	 * @param int $generic_use        	
	 * @param int $generic_num        	
	 * @param int $generic_piece_attr        	
	 * @param int $money        	
	 * @param int $ptype        	
	 */
	public static function sendEvolution($type, $openid, $card_id, $after_card_id, $card_attribute, $lv, $vip_lv, $piece_num, $generic_use, $generic_num, $generic_piece_attr, $money, $ptype) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'card_id' => $card_id,
				'after_card_id' => $after_card_id,
				'card_attribute' => $card_attribute,
				'lv' => $lv,
				'vip_lv' => $vip_lv,
				'piece_num' => $piece_num,
				'generic_use' => $generic_use,
				'generic_num' => $generic_num,
				'generic_piece_attribute' => $generic_piece_attr,
				'money' => $money,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_EVOLUTION );
	}
	/**
	 * send Mission Flow tlog
	 * 
	 * @param int $type        	
	 * @param string $openid        	
	 * @param int $awake_piece_id        	
	 * @param int $name        	       	
	 * @param int $ptype
	 * By: YuanWenGuang        	
	 */
	public static function sendAwakeSkill($type,$openid,$Level,$VipLevel,$awake_piece_id,$card_id,$ps_id,$awake_skill_piece_num,$coin,$user_card_lv,$ptype){
		$logBody = array(
				't' => $type,
				'oid' => $openid,
				'Level' => $Level,
				'VipLevel' => $VipLevel,
				'awake_piece_id' => $awake_piece_id,
				'card_id' => $card_id,
				'ps_id' => $ps_id,
				'awake_skill_piece_num' => $awake_skill_piece_num,
				'coin' => $coin,
				'user_card_lv' => $user_card_lv,
				'pt' => $ptype
		);
		self::writeLogBase($logBody,Tencent_Tlog::LOG_TYPE_AWAKESKILL);
	}
	/**
	 * send Mission Flow tlog
	 * 
	 * @param int $type        	
	 * @param string $openid        	
	 * @param int $mission_id        	
	 * @param int $user_lv        	
	 * @param int $vip_lv        	
	 * @param int $ptype        	
	 */
	public static function sendMissionFlow($type, $openid, $mission_id, $user_lv, $vip_lv, $ptype) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'mission_id' => $mission_id,
				'user_lv' => $user_lv,
				'vip_lv' => $vip_lv,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_MISSIONFLOW );
	}
	/**
	 * send share tlog
	 * 
	 * @param int $type        	
	 * @param string $openid        	
	 * @param int $share_type        	
	 * @param int $dungeon_id        	
	 * @param int $card_id        	
	 * @param int $user_lv        	
	 * @param int $vip_lv        	
	 * @param int $ptype        	
	 */
	public static function sendShareFlow($type, $openid, $share_type, $dungeon_id, $card_id, $user_lv, $vip_lv, $ptype) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'share_type' => $share_type,
				'dungeon_id' => $dungeon_id,
				'card_id' => $card_id,
				'user_lv' => $user_lv,
				'vip_lv' => $vip_lv,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_SHARE );
	}	

	/**
	 * 
	 * loggerにセット
	 * @param string $logtext
	 * @param int $logtype
	 */
	public static function writeLog($logtext,$logtype=Zend_Log::DEBUG)
	{
		global $logger;
		if(Env::ENV !== "production"){
			$logger->log($logtext, $logtype);
		}
		return;
	}

	/**
	 * ログ出力
	 * 
	 * @param array $logBody        	
	 * @param int $type        	
	 * @throws PadException
	 */
	private static function writeLogBase($logBody = array(), $logType) {
		$ptype = $logBody ['pt'];
		$appinfo = Tencent_Msdk::getAppInfo ($ptype);
		$logBody ['appid'] = $appinfo ['appid'];
		
		self::saveToHistory(Tencent_Tlog::generateMessage($logType, $logBody));
	}
	
	/**
	 * 
	 * @throws PadException
	 */
	public static function sendTlogHistory(){
		// Tlog送信
		$logFile = Env::LOG_PATH . self::getLogName ( Env::TLOG_LOG_FILE );
		if (Env::OUT_LOG_TYPE) {
			foreach(self::$tlog_history as $tlog_msg){
				try {
					Tencent_Tlog::send ( $tlog_msg );
				} catch ( Exception $e ) {
					//throw new PadException ( RespCode::TENCENT_NETWORK_ERROR, $e->getMessage () );
				
					//TLOG送信失敗の場合、ファイルに出力します。
					self::_writeFileLog ( $logFile, $tlog_msg );
				}
			}
		} 		// ファイル出力
		else {
			self::_writeFileLog ( $logFile, self::$tlog_history );
		}
		self::$tlog_history = array();
	}
	
	/**
	 * ログをファイルに出力
	 * 
	 * @param string $logFile        	
	 * @param string $logBody        	
	 */
	private static function _writeFileLog($logFile, $logBody) {
		$log_writer = new Zend_Log_Writer_Stream ( $logFile );
		try
		{
			chmod($logFile,0777);
		}
		catch(Exception $e)
		{

		}
		$log_format = '%message%' . PHP_EOL;
		$log_formatter = new Zend_Log_Formatter_Simple ( $log_format );
		$log_writer->setFormatter ( $log_formatter );
		$log_logger = new Zend_Log ( $log_writer );
		if(is_array($logBody)){
			foreach($logBody as $msg){
				$log_logger->log ( $msg, Zend_Log::DEBUG );
			}
		}else{
			$log_logger->log ( $logBody, Zend_Log::DEBUG );
		}
		return;
	}
	
	/**
	 * send ranking
	 * 
	 * @param int $type        	
	 * @param int $dungeonId        	
	 * @param int $rankingId        	
	 * @param string $timeStamp        	
	 * @param string $userData1        	
	 * @param string $userData2        	
	 * @param string $userData3        	
	 * @param string $userData4        	
	 * @param string $userData5        	
	 * @param int $ptype        	
	 */
	public static function sendRanking($type, $dungeonId, $rankingId, $timeStamp, $userData1, $userData2, $userData3, $userData4, $userData5, $ptype) {
		$logBody = array (
				't' => $type,
				'dungeonId' => $dungeonId,
				'rankingId' => $rankingId,
				'timeStamp' => $timeStamp,
				'userData1' => $userData1,
				'userData2' => $userData2,
				'userData3' => $userData3,
				'userData4' => $userData4,
				'userData5' => $userData5,
				'pt' => $ptype 
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_RANKING );
	}
	
	/**
	 * send Failed Sneak
	 * @param int $type
	 * @param string $openId
	 * @param int $dungeonId
	 * @param string $sneakTime
	 * @param int $ptype
	 */
	public static function sendFailedSneak($type,$openId,$dungeonId,$sneakTime,$ptype){
		$logBody = array(
				't'			=> $type,
				'oid'		=> $openId,
				'dungeonId'=> $dungeonId,
				'sneakTime'	=> $sneakTime,
				'pt'		=> $ptype
		);
		self::writeLogBase($logBody,Tencent_Tlog::LOG_TYPE_FAILEDSNEAK);
	}

	/**
	 * send Player Logout
	 * @param int $type
	 * @param string $openId
	 * @param int $onlineTime
	 * @param int $level
	 * @param int $playerFriendsNum
	 * @param string $clientVersion
	 * @param string $systemSoftware
	 * @param string $systemHardware
	 * @param string $telecomOper
	 * @param string $network
	 * @param int $screenWidth
	 * @param int $screenHight
	 * @param float $density
	 * @param int $loginChannel
	 * @param string $cpuHardware
	 * @param int $memory
	 * @param string $GLRender
	 * @param string $GLVersion
	 * @param string $DeviceId
	 * @param int $ptype
	 */
	public static function sendPlayerLogout($type,$openId,$onlineTime,$level,$playerFriendsNum,$clientVersion,$loginChannel,$DeviceId,$VipLevel,$MonthlyFee,$PlayerDiamonds,$PlayerMoney,$ptype){
		$logBody = array(
				't'					=> $type,
				'oid'				=> $openId,
				'onlineTime'		=> $onlineTime,
				'level'				=> $level,
				'playerFriendsNum'	=> $playerFriendsNum,
				'clientVersion'		=> $clientVersion,
				'loginChannel'		=> $loginChannel,
				'DeviceId'			=> $DeviceId,
				'VipLevel'			=> $VipLevel,
				'MonthlyFee'		=> $MonthlyFee,
				'PlayerDiamonds'	=> $PlayerDiamonds,
				'PlayerMoney'		=> $PlayerMoney,
				'pt'				=> $ptype
		);
		self::writeLogBase($logBody,Tencent_Tlog::LOG_TYPE_PLAYERLOGOUT);
	}
	
	public static function sendSecRoundStartFlow($logBody){
		self::writeLogBase($logBody,Tencent_Tlog::LOG_TYPE_SEC_ROUND_START_FLOW);
	}
	/**
	 * send Security Round End Flow
	 * @param int $type
	 * @param int $ptype
	 * @param string $openId
	 */
	public static function sendSecRoundEndFlow($logBody){
		self::writeLogBase($logBody,Tencent_Tlog::LOG_TYPE_SEC_ROUND_END_FLOW);
	}
	
	/**
	 * send Security Talk Flow
	 * @param int $type
	 * @param int $ptype
	 * @param string $openId
	 */
	public static function sendSecTalkFlow($logBody){
		self::writeLogBase($logBody,Tencent_Tlog::LOG_TYPE_SEC_TALK_FLOW);
	}
	
	
	/**
	 * send Change Name
	 * @param int $type
	 * @param string $openid
	 * @param int $ptype
	 * @param string $BeforeName
	 * @param string $AfterName
	 */
	public static function sendChangeName($type,$openid,$ptype,$BeforeName,$AfterName){
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'pt' => $ptype,
				'bn' => $BeforeName,
				'an' => $AfterName
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_CHANGE_NAME );
	}
	
	
	/**
	 * send monthly card
	 * @param int $type
	 * @param string $openid
	 * @param int $ptype
	 * @param string $endTime
	 */
	public static function sendMonthlyCard($type,$openid,$ptype,$endTime){
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'pt' => $ptype,
				'et' => $endTime,
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_MONTHLY_CARD );
	}

	/**
	 * send monthly card
	 * @param int $type
	 * @param string $openid
	 * @param int $ptype
	 * @param string $endTime
	 */
	public static function sendForeverMonthlyCard($type,$openid,$ptype,$endTime){
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'pt' => $ptype,
				'et' => $endTime,
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_FOREVER_MONTHLY_CARD );
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param string $openid        	
	 * @param number $guide_id        	
	 * @param number $ptype        	
	 */
	public static function sendLogAntiData($type, $openid, $securitySDK, $ptype, $sequence) {
		$logBody = array (
				't' => $type,
				'oid' => $openid,
				'ssdk' => $securitySDK,
				'pt' => $ptype,
				'seq' => $sequence,
		);
		self::writeLogBase ( $logBody, Tencent_Tlog::LOG_TYPE_LOG_ANTI_DATA );
	}
    
	/**
	 *
	 * @param int $type
	 * @param string $openid
	 * @param unknown $money
	 * @param unknown $exchangeType
	 */
	public static function sendExchangeItem($type, $openid, $ptype, $money, $exchangeType) {
		$logBody = array (
            't' => $type,
            'oid' => $openid,
            'pt' => $ptype,
            'm' => $money,
            'et' => $exchangeType
		);
		self::writeLogBase($logBody, Tencent_Tlog::LOG_TYPE_EXCHANGE_ITEM);
		return;
	}

	/**
	 * send carnival prize's id
	 * @param $type
	 * @param $openid
	 * @param $ptype
	 * @param $prizeid
	 */
	public static function sendCarnivalReceivePrizeId($type, $openid, $ptype,$prizeid,$mtype,$desc){
		$logBody = array (
			't'      => $type,
			'oid'    => $openid,
			'pt'     => $ptype,
			'prizeid'=> $prizeid,
			'mtype'  => $mtype,
			'carnivalDesc'   => $desc,
		);
		self::writeLogBase($logBody, Tencent_Tlog::LOG_TYPE_CARNIVAL_PRIZE_TYPE);
	}
	
	/**
	 *
	 * @param string $msg
	 */
	public static function saveToHistory($msg){
		self::$tlog_history []= $msg;
	}
	
	/**
	 * デバッグツールによるDB編集のログ出力
	 */
	public static function debugToolEditDBLog($logBody){
		// ログファイル出力
		$logFile = Env::LOG_PATH . self::getLogName ( Env::DEBUG_TOOL_EDIT_DB_LOG_FILE );
		self::_writeFileLog ( $logFile, $logBody );
	}
}
