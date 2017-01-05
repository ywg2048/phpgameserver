<?php
/**
 * #PADC#
 */
class SendPresents extends BaseAction {
	/**
	 *
	 * @see BaseAction::action()
	 */
	public function action($params) {
		if (empty ( $params ['to'] )) {
			return json_encode ( array (
					'res' => RespCode::UNKNOWN_ERROR
			) );
		}
		$sender_id = $params ['pid'];
		$receiver_ids = array_unique(explode ( ',', $params ['to'] ));
		$rev = isset($params['r']) ? (int)$params['r'] : 1;
		
		// 最低レベル
		$user = User::find ( $sender_id );
		// #PADC_DY# clear_dungeon_cnt change to level
		if ($user->lv < GameConstant::PRESENT_SEND_MIN_LEVEL) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, "Not enough level." );
		}

		$pdo_sender = Env::getDbConnectionForUserWrite ( $sender_id );
		
		UserPresentsSend::removeExpired ( $sender_id, $pdo_sender );
		
		$today_sent_ids = UserPresentsSend::getFriendsSendToday ( $sender_id );
		if ($today_sent_ids == false) {
			$today_sent_ids = array ();
		}
		
		$sent_count = count ( $today_sent_ids ); // 1日プレゼントフレンド数
		$cur_sent_cnt = 0; // 今回プレゼント数
		$max_reached = 0;
		$secendsToTomorrow = UserPresentsSend::getSecendsToTomorrow ();

		$unpresented_ids = UserPresentsSend::getUnpresentedFriendIds($sender_id, $rev);
		
		//$receiver_ids = array_diff ( $receiver_ids, $today_sent_ids );
		if(!empty(array_diff ( $receiver_ids, $unpresented_ids ))){
			throw new PadException ( RespCode::UNKNOWN_ERROR, "target error!" );
		}
		
		$redis = Env::getRedisForUser();
		$key = CacheKey::getFriendsSendPresent ( $sender_id );

		$cur_sent_ids = array ();
		$share = Env::getDbConnectionForShare();
		$u1_device = UserDevice::find($sender_id,$share);
		foreach ( $receiver_ids as $receiver_id ) {
			// 一日最大回数
			if (GameConstant::PRESENT_MAX_SEND_PER_DAY > 0 && $sent_count >= GameConstant::PRESENT_MAX_SEND_PER_DAY) {
				$max_reached = 1;
				break;
			}
			$u2_device = UserDevice::find($receiver_id,$share);
			$unmatch_dbid = true;
			if($u2_device->dbid == $u1_device->dbid)
			{
				$pdo_receiver = $pdo_sender;
				$unmatch_dbid = false;
			}
			else
			{
				$pdo_receiver = Env::getDbConnectionForUserWrite ( $receiver_id );
			}

			try {
				$pdo_sender->beginTransaction ();
				if($unmatch_dbid)
				{
					$pdo_receiver->beginTransaction ();
				}
				
				UserPresentsReceive::removeExpired ( $receiver_id, $pdo_receiver );
				
				UserPresentsSend::sendPresent ( $sender_id, $receiver_id, $pdo_sender );
				UserPresentsReceive::sendPresent ( $sender_id, $receiver_id, $pdo_receiver );

				$pdo_sender->commit ();
				if($unmatch_dbid)
				{
					$pdo_receiver->commit ();
				}
			} catch ( Exception $e ) {
				if ($pdo_sender->inTransaction ()) {
					$pdo_sender->rollback ();
				}
				if ($pdo_receiver->inTransaction ()) {
					$pdo_receiver->rollback ();
				}
				throw $e;
			}

			// $receiver_idをCacheに追加
			$today_sent_ids [] = (int)$receiver_id;
			$cur_sent_ids [] = (int)$receiver_id;
			$sent_count ++;
			$cur_sent_cnt ++;
			if ($secendsToTomorrow > 1) {
				$redis->set ( $key, $today_sent_ids, $secendsToTomorrow );
			}
		}

		// #PADC# Tlog
		UserTlog::sendTlogSnsFlow ( $sender_id, $cur_sent_cnt, Tencent_Tlog::SNSTYPE_SENDHEART );

		$result = array (
				'res' => RespCode::SUCCESS,
				//'cnt' => $cur_sent_cnt,
				'pids' => $cur_sent_ids
		);
		if (GameConstant::PRESENT_MAX_SEND_PER_DAY > 0) {
			$result ['max'] = $max_reached;
		}
		return json_encode ( $result );
	}
}
