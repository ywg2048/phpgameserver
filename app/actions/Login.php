<?php


/**
 * ログインアクション
 */
class Login extends BaseAction {

	// このアクションへのコールはログイン必要なし
	const LOGIN_REQUIRED = FALSE;
	// #PADC_DY# session duration change to 24hour
	const SESSION_DURATION_SEC = 86400; // 24hr

	// http://pad.localhost/api.php?action=login&t=0&u=UUID&v=1.0.0
	public function action($params){
		global $logger;

		$type = $params['t'];
		// #PADC#
		$uuid = isset($params['u']) ? $params['u'] : 0;
		$area = isset($params['p']) ? $params['p'] : null;
		$version = $params['v'];
		$upd = isset($params['upd']) ? $params['upd'] : null;
		$dev = isset($params['dev']) ? $params['dev'] : null;
		$osv = isset($params['osv']) ? $params['osv'] : null;
		$wmode = isset($params['m']) ? $params['m'] : User::MODE_NORMAL;
		$camp = isset($params['c']) ? $params['c'] : null;

		// #PADC# ----------begin----------
		$openid = isset($params['ten_oid']) ? $params['ten_oid'] : 0;
		$channel_id = isset($params['ten_cid']) ? $params['ten_cid'] : 0;;
		$device_id = isset($params['ten_did']) ? $params['ten_did'] : null;
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		$game_center = isset($params['ten_gc']) ? $params['ten_gc'] : null;
		// #PADC# ----------end----------

		if($wmode == User::MODE_W && Env::SERVICE_AREA=="HT"){
			// 香港台湾版でWをログインしようとするとエラーを返す（暫定処理？）.
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}

		// #PADC# パラメータ追加
		list($user, $mails) = User::login($type, $uuid, $area, $version, $dev, $osv, $wmode, $camp, $openid, $channel_id, $token, $device_id, $game_center);

		// #PADC# SNSフレンド更新
		$this->updateSnsFriends($user->id, $token);

		$res = array('res' => RespCode::SUCCESS);
		// アカウント期限停止対応.
		if($user->del_status == User::STATUS_BAN_LTD){
			$res['res'] = RespCode::APP_VERSION_ERR;
			$tmp_msg=UserBanMessage::findby(array('user_id'=>$user->id));
			//#PADC# msgを配列に変更
			$msg = !empty($tmp_msg->message)?$tmp_msg->message:GameConstant::getParam("BanMessage");
			$res['ban_msg'][] = $msg;
			$logger->log("##Login Error## user_id: ".$user->id." ban_status: ".$user->del_status." message: ".$msg, Zend_Log::INFO);
			//#PADC# return when all message ready
			//return json_encode($res);
		}
		//#PADC# ----------begin----------
		else if($user->del_status == User::STATUS_BAN){
			$punish_ban_info = UserBanMessage::getPunishInfo($user->id, User::PUNISH_BAN);
			if($punish_ban_info){
				$res['res'] = RespCode::APP_VERSION_ERR;
				$res['ban_msg'][] = $punish_ban_info['msg'];
				$res['ban_end'][] = $punish_ban_info['end'];
				$logger->log("##Login Error## user_id: ".$user->id." ban_status: ".$user->del_status." message: ".$punish_ban_info['msg'], Zend_Log::INFO);
				//#PADC# return when all message ready
				//return json_encode($res);
			}
		}
		//#PADC# ----------end----------

		// アカウント停止/削除/凍結対応.
		elseif($user->del_status != User::STATUS_NORMAL){
			$res['res'] = RespCode::APP_VERSION_ERR;
			$tmp_msg=UserBanMessage::findby(array('user_id'=>$user->id));
			switch($user->del_status) {
				//#PADC# ----------begin----------
				// PADCコメントアウト、別の方法でBAN状態判断
				//case User::STATUS_BAN:
				//	$res['msg'] = GameConstant::getParam("BanMessage");
				//	break;
				//#PADC# ----------end----------
				case User::STATUS_DEL:
					//#PADC#
					$res['ban_msg'][] = GameConstant::getParam("DelMessage");
					break;
				case User::STATUS_FRZ:
					//#PADC#
					$res['ban_msg'][] = GameConstant::getParam("FrzMessage");
					break;
				case User::STATUS_BAN_GS:
					//#PADC#
					$res['ban_msg'][] = GameConstant::getParam("GSBanMessage");
					break;
				case User::STATUS_BAN_CR:
					//#PADC#
					$res['ban_msg'][] = GameConstant::getParam("CRBanMessage");
					break;
				default:
					//#PADC#
					$res['ban_msg'][] = GameConstant::getParam("BanMessage");
			}
			$logger->log("##Login Error## user_id: ".$user->id." ban_status: ".$user->del_status, Zend_Log::INFO);
			//#PADC# return when all message ready
			//return json_encode($res);
		}
		//#PADC# ----------begin----------
		$punish_list = array(
				User::PUNISH_PLAY_BAN_NORMAL,
				User::PUNISH_PLAY_BAN_SPECIAL,
				User::PUNISH_PLAY_BAN_RANKING,
				User::PUNISH_PLAY_BAN_BUYDUNG,
				User::PUNISH_ZEROPROFIT
		);
		$punish_infos = UserBanMessage::getMultiPunishInfo($user->id, $punish_list);
		foreach($punish_list as $punish_type){
			if(!empty($punish_infos[$punish_type])){
				$res['ban_msg'][] = $punish_infos[$punish_type]['msg'];
				$res['ban_end'][] = $punish_infos[$punish_type]['end'];
			}
		}
		if($res['res'] == RespCode::APP_VERSION_ERR){
			return json_encode($res);
		}
		//#PADC# ----------end----------

		// アプリバージョンチェック.
		if($version < Env::APP_VERSION){
			$res['res'] = RespCode::APP_VERSION_ERR;
			$res['msg'] = GameConstant::getParam("UpdateMessage");
			$logger->log("##Login Error## user_id: ".$user->id." APP_VERSION: ".Env::APP_VERSION. " version: ".$version, Zend_Log::INFO);
			return json_encode($res);
		}

		// #PADC# ミッションクリア確認（全ミッションチェック）
		list(,$clear_mission_ids) = UserMission::checkClearMissionTypes ( $user->id, array (
				Mission::CONDITION_TYPE_TOTAL_LOGIN,
				Mission::CONDITION_TYPE_USER_RANK,
				Mission::CONDITION_TYPE_DUNGEON_CLEAR,
				Mission::CONDITION_TYPE_BOOK_COUNT,
				Mission::CONDITION_TYPE_CARD_EVOLVE,
				Mission::CONDITION_TYPE_CARD_COMPOSITE,
				Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR,
				//Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR_RANKING,
				Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_NORMAL,
				Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_SPECIAL,
				Mission::CONDITION_TYPE_DAILY_GACHA_FRIEND,
				Mission::CONDITION_TYPE_DAILY_GACHA_GOLD,
				Mission::CONDITION_TYPE_DAILY_CARD_COMPOSITE,
				Mission::CONDITION_TYPE_DAILY_CARD_EVOLVE,
				Mission::CONDITION_TYPE_DAILY_LOGIN_STREAK
		) );
		
		// #PADC# memcache→redis
		$redis = Env::getRedisForUser();
		$sessionKey = CacheKey::getUserSessionKey($user->id);
		$sessionValue = $this->generateSessionKey();
		$redis->set($sessionKey, $sessionValue, Login::SESSION_DURATION_SEC);

		//#PADC#
		//$res['res'] = RespCode::SUCCESS;
		$res['id'] = ''.$user->id;
		$res['sid'] = $sessionValue;
		$res['time'] = date("ymdHis");
		$res['rlb'] = Env::LAST_BASE_JSON_UPDATE;
		$res['ranking_open_floor_id'] = GameConstant::getParam('RankingParticipateFloorId');
		$res['dsale_open_clear_cnt'] = GameConstant::getParam('DungeonSaleOpenClearCount');
		// セッションIDアップデート時は upd=y が付く
		// upd=y が付いている時のみ、cver 等のバージョン番号を返す
		if($upd == "y") {
			if($wmode == User::MODE_W){
				// たまドラモード.
				$res['tdver'] = Version::getVersion(WDungeon::VER_KEY_GROUP);
				$res['tsver'] = Version::getVersion(WAvatarItem::VER_KEY_GROUP);
			}else{
				$res['cver'] = Version::getVersion(Card::VER_KEY_GROUP);
				$res['sver'] = Version::getVersion(Skill::VER_KEY_GROUP);
				$res['dver'] = Version::getVersion(Dungeon::VER_KEY_GROUP);
				$res['pver'] = Version::getVersion(LimitedBonus::VER_KEY_GROUP);
				$res['msver'] = Version::getVersion(EnemySkill::VER_KEY_GROUP);
				$res['dsver'] = Version::getVersion(DungeonSale::VER_KEY_GROUP);
				// #PADC# ----------begin----------
				$res['padc_pver'] = Version::getVersion(Piece::VER_KEY_GROUP);
				$res['padc_sver'] = Version::getVersion(Scene::VER_KEY_GROUP);
				//vver is vip version and vip cost version
				$res['padc_vver'] = Version::getVersion(VipBonus::VER_KEY_GROUP);
				$res['padc_lver'] = Version::getVersion(LoginTotalCountBonus::VER_KEY_GROUP);

				$res['padc_miver'] = Version::getVersion(Mission::VER_KEY_GROUP);
				$res['padc_rver'] = Version::getVersion(LimitedRanking::VER_KEY_GROUP);
				$res['padc_rdver'] = Version::getVersion(RankingDungeon::VER_KEY_GROUP);
				// #PADC# ----------end----------
			}
		}
		$res['mails'] = $mails;
		// #PADC#チート対策として同時起動チェック対象のアプリ名をセット
		$res['padc_p'] = array(
			'com.forrep.pad.combo',
			'com.forrep.pad.m',
			'com.shxd48ooegxu',
			'com.xxAssistant',
			'com.xxAssistant:xg_service_v2',
		);
		// #PADC# 先頭ID
		$userDevice = UserDevice::getUserDeviceFromRedis($user->id);
		$res['pre_pid'] = UserDevice::getPreUserId($userDevice['t'],$userDevice['pt']);
		// #PADC#
		$res['clear_mission_list'] = $clear_mission_ids;

		$res['game_center'] = $user->game_center;

		//新手嘉年华：每日登录
		UserCarnivalInfo::carnivalMissionCheck($user->id,CarnivalPrize::CONDITION_TYPE_DAILY_LOGIN);

		//登录检查扭蛋限制是否可以重置
		$user_daily_counts =  UserDailyCounts::findBy(array("user_id"=>$user->id));
		$pdo = Env::getDbConnectionForUserWrite($user->id);
		if(!$user_daily_counts){
			$user_daily_counts = new UserDailyCounts();
			$user_daily_counts->user_id = $user->id;
			$user_daily_counts->ip_daily_count = 0;
			$user_daily_counts->piece_daily_count = 0;
			$user_daily_counts->daily_reset_at = BaseModel::timeToStr(time());
			$user_daily_counts->create($pdo);
		}else{
			$daily_reset_at = BaseModel::strToTime($user_daily_counts->daily_reset_at);
			if(!BaseModel::isSameDay_AM4(time(), $daily_reset_at)){
				$user_daily_counts->ip_daily_count = 0;
				$user_daily_counts->piece_daily_count = 0;
				$user_daily_counts->daily_reset_at = BaseModel::timeToStr(time());
				$user_daily_counts->update($pdo);
			}
		}
		
		return json_encode($res);
	}

	// セッションキーを生成
	static public function generateSessionKey(){
		return self::generateRandomString(40);
	}

	// ランダム文字列を生成
	static function generateRandomString($nLengthRequired = 8){
		$sCharList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";
		mt_srand();
		$sRes = "";
		for($i = 0; $i < $nLengthRequired; $i++)
		{
			$sRes .= $sCharList{mt_rand(0, strlen($sCharList) - 1)};
		}
		return $sRes;
	}

	/**
	 * #PADC# ユーザーのSNSフレンド関係を更新します。　フレンド追加またはフラッグセットを実行
	 *
	 * @param int $user_id
	 */
	private function updateSnsFriends($user_id, $token){

		$sns_firends_openids = User::getTencentFriendsOpenIds($user_id, $token);
		if(empty($sns_firends_openids)){
			return;
		}

		$sns_friends_ids = UserDevice::findAllUserIdByOpenids($sns_firends_openids);
		$friends_ids = Friend::getFriendids($user_id);

		//新しいSNSFriends追加
		$new_sns_friends_ids = array_diff($sns_friends_ids, $friends_ids);
		if(!empty($new_sns_friends_ids)){
			Friend::addSnsFriends($user_id, $new_sns_friends_ids);
		}

		//既存Firend　SNSFlag追加
		$game_and_sns_friends_ids = array_intersect($sns_friends_ids, $friends_ids);
		if(!empty($game_and_sns_friends_ids)){
			Friend::setFriendsFlag($user_id, $game_and_sns_friends_ids, 1);
		}

		//既存Firend　SNSFlag削除
		$game_not_sns_friends_ids = array_diff($friends_ids, $sns_friends_ids);
		if(!empty($game_not_sns_friends_ids)){
			Friend::setFriendsFlag($user_id, $game_not_sns_friends_ids, 0);
		}
	}
}
