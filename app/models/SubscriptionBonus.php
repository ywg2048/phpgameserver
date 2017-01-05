<?php
/**
 * #PADC# 月額課金ボーナス
 */
class SubscriptionBonus extends BaseMasterModel {
	const SUBSCRIPTION_FOREVER_PERIOD = 259200000;// 永久月卡（ボーナス）期間3000日(60sec * 60min * 24hour * 30day)
	const SUBSCRIPTION_FOREVER_MONTH_COST = 980;//永久月卡魔法石
	/**
	 * 月額課金チェック
	 *
	 * @param User $user        	
	 * @param array $tss_list        	
	 * @return boolean
	 */
	public static function checkSubscriptionAdr($user, $tss_list, $pdo) {
		if (! isset ( $tss_list [0] ['begintime'] ) || ! isset ( $tss_list [0] ['endtime'] )) {
			return;
		}
		$begin_time = BaseModel::strToTime ( $tss_list [0] ['begintime'] );
		$end_time = BaseModel::strToTime ( $tss_list [0] ['endtime'] );
		$user_end_time = BaseModel::strToTime ( $user->tss_end );
		
		if ($end_time > $user_end_time) {
			try {
				$pdo->beginTransaction ();
				self::applyCostToUserVip ( $user, UserDevice::TYPE_ADR, $pdo );
				self::updateUserEndTime ( $user, $end_time, $user_end_time, $pdo );
				$pdo->commit ();
			} catch ( Exception $e ) {
				if ($pdo->inTransaction ()) {
					$pdo->rollback ();
				}
				throw $e;
			}
			// send tlog monthly card
			UserTlog::sendTlogMonthlyCard ( $user, $tss_list [0] ['endtime'] );
		}
	}
	
	/**
	 *
	 * @param User $user        	
	 * @param number $pgold_add        	
	 * @param PDO $pdo        	
	 * @throws Exception
	 */
	public static function checkSubscription($user, $pgold_add, $pdo) {
		$user_end_time = BaseModel::strToTime ( $user->tss_end );
		if ($user->duringSubscription ()) {
			return;
		}
		
		if ($pgold_add >= Env::SUBSCRIPTION_MONTH_COST) {
			//30 days 4am
			$time4am = strftime ( '%Y-%m-%d 04:00:00', time () - 14400 );
			$time4am = strtotime ( $time4am );
			$end_time = $time4am + Env::SUBSCRIPTION_PERIOD;
			try {
				$pdo->beginTransaction ();
				//月額課金のVIP加算を削除
				//self::applyCostToUserVip ( $user, UserDevice::TYPE_IOS, $pdo );
				self::updateUserEndTime ( $user, $end_time, $user_end_time, $pdo );
				$pdo->commit ();
			} catch ( Exception $e ) {
				if ($pdo->inTransaction ()) {
					$pdo->rollback ();
				}
				throw $e;
			}
			// 月額課金仕様調整のため、Android版月額アイテムの購入が無くなった。
			// ここで魔法石の購入で月額バーナス期間が更新したら、Tlogを送信します。
			UserTlog::sendTlogMonthlyCard ( $user, $end_time );
		}
	}
	
	/**
	 * 月額アイテムの価格をVIPに反映する
	 *
	 * @param User $user        	
	 * @param number $month_bought        	
	 * @param unknown $pdo        	
	 */
	private static function applyCostToUserVip($user, $type, $pdo) {
		$cost = self::getSubscriptionCost ( $type );
		$user->tp_gold += $cost;
		list ( $isLvUp, $levelCost ) = $user->refreshVipLv ();
		UserTlog::sendTlogVipLevel ( $user, $cost, $isLvUp, $levelCost );
	}
	
	/**
	 * 月額終了時間をuserに保存
	 *
	 * @param User $user        	
	 * @param number $end_time        	
	 * @param number $user_end_time        	
	 * @param PDO $pdo        	
	 */
	private static function updateUserEndTime($user, $end_time, $user_end_time, $pdo) {
		if ($end_time > $user_end_time) {
			$user->tss_end = BaseModel::timeToStr ( $end_time );
			$user->last_subs_daily = 0;//当日ボーナス最大2回取得できます。
			$user->update ( $pdo );
		}
	}
	
	/**
	 * デーリーボーナス情報取得
	 *
	 * @param number $user_id        	
	 * @throws PadException
	 * @return array
	 */
	public static function getDailyBonusInfo($user_id) {
		$user = User::find ( $user_id );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}
		$res = array ();
		if ($user->duringSubscription ()) {
			if (! $user->isGetSubscriptionBonusToday ()) {
				$res ['daily'] = 1;
			}
			$res ['remain_days'] = self::getRemainDays ( $user );
		}
		return $res;
	}

	/**
	 * 永久月卡デーリーボーナス情報取得
	 *
	 * @param number $user_id
	 * @throws PadException
	 * @return array
	 */
	public static function getForeverDailyBonusInfo($user_id) {
		$user = User::find ( $user_id );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}
		$res = array ();
		$now = time();
		if (BaseModel::strToTime($user->tss_forever_end) > $now) {
			if (! $user->isGetForeverSubscriptionBonusToday ()) {
				$res ['daily'] = 1;
			}
			$res['get_cnt'] = $user->tss_forever_cnt;
		} else {
			$res ['daily'] = 0;
			$res ['get_cnt'] = null;
		}
		return $res;
	}
	
	/**
	 * デーリーボーナス受取る日数
	 *
	 * @param User $user        	
	 * @return number
	 */
	private static function getRemainDays($user) {
		$cur_time = time ();
		$next_get_time = null;
		// global $logger;
		if (isset ( $user->last_subs_daily )) {
			$next_get_time = self::getNext4Am ( BaseModel::strToTime ( $user->last_subs_daily ) );
			// $logger->log ( '$next_get_time ' . BaseModel::timeToStr ( $next_get_time ), 7 );
			$next_get_time = max ( $next_get_time, $cur_time );
			// $logger->log ( '$next_get_time ' . BaseModel::timeToStr ( $next_get_time ), 7 );
		} else {
			$next_get_time = $cur_time;
			// $logger->log ( '$next_get_time ' . BaseModel::timeToStr ( $next_get_time ), 7 );
		}
		$end_time = BaseModel::strToTime ( $user->tss_end );
		$end_time = self::getNext4Am ( $end_time ) - 1;
		// $logger->log ( '$end_time ' . BaseModel::timeToStr ( $end_time ), 7 );
		
		$remain_days = max ( floor ( ($end_time - $next_get_time) / 86400 ), 0 );
		// $logger->log ( '$remain_days ' . $remain_days, 7 );
		return $remain_days;
	}
	
	/**
	 * get time of next 4 am
	 *
	 * @param number $time        	
	 * @return number
	 */
	private static function getNext4Am($time) {
		$time4am = strftime ( '%Y-%m-%d 04:00:00', $time );
		$time4am = strtotime ( $time4am );
		if ($time4am < $time) {
			$time4am += 86400;
		}
		return $time4am;
	}
	
	/**
	 *
	 * @param number $type        	
	 * @return string
	 */
	private static function getSubscriptionCost($type) {
		//if ($type == UserDevice::TYPE_ADR) {
		//	return Env::SUBSCRIPTION_MONTH_COST_ADR;
		//} else if ($type == UserDevice::TYPE_IOS) {
		return Env::SUBSCRIPTION_MONTH_COST;
		//}
	}

	/**
	 * 检查永久月卡是否设置
	 * @param User $user
	 * @param number $pgold_add
	 * @param PDO $pdo
	 * @throws Exception
	 */
	public static function checkForeverSubscription($user, $pgold_add, $pdo) {
		$user_end_time = BaseModel::strToTime ( $user->tss_forever_end );
		$now = time();
		if ($user_end_time > $now) {
			return;
		}

		if ($pgold_add >= self::SUBSCRIPTION_FOREVER_MONTH_COST) {
			//3000 days 4am
			$time4am = strftime ( '%Y-%m-%d 04:00:00', time () - 14400 );
			$time4am = strtotime ( $time4am );
			$end_time = $time4am + self::SUBSCRIPTION_FOREVER_PERIOD;
			try {
				$pdo->beginTransaction ();
				//月額課金のVIP加算を削除
				//self::applyCostToUserVip ( $user, UserDevice::TYPE_IOS, $pdo );
				self::updateUserForeverEndTime ( $user, $end_time, $user_end_time, $pdo );
				$pdo->commit ();
			} catch ( Exception $e ) {
				if ($pdo->inTransaction ()) {
					$pdo->rollback ();
				}
				throw $e;
			}

			// TODO 永久月卡的tlog
			UserTlog::sendTlogForeverMonthlyCard ( $user, $end_time );
		}
	}

	/**
	 * 永久月額終了時間をuserに保存
	 *
	 * @param User $user
	 * @param number $end_time
	 * @param number $user_end_time
	 * @param PDO $pdo
	 */
	private static function updateUserForeverEndTime($user, $end_time, $user_end_time, $pdo) {
		if ($end_time > $user_end_time) {
			$user->tss_forever_end = BaseModel::timeToStr ( $end_time );
			$user->last_forever_subs_daily = 0;//当日ボーナス最大2回取得できます。
			$user->tss_forever_cnt = 0; // 第一次购买领取次数设置为0
			$user->update ( $pdo );
		}
	}
}