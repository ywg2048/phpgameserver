<?php

/**
 * #PADC_DY#
 * 获取活动奖励
 */
class ReceiveActivityReward extends BaseAction {

	// http://domainname/api.php?action=receive_activity_reward&pid=1&activity_id=1
	public function action($params) {
		$rev = isset($params['r']) ? $params['r'] : 0;
		$user_id = $params ["pid"];
		$activity_id = $params ["activity_id"];
		$now = time();

		$token = Tencent_MsdkApi::checkToken($params);
		if (!$token) {
			return json_encode(array(
				'res' => RespCode::TENCENT_TOKEN_ERROR
			));
		}

		try {
			User::getUserBalance($user_id, $token);
		} catch (PadException $e) {
			if ($e->getCode() != RespCode::TENCENT_NETWORK_ERROR && $e->getCode() != RespCode::TENCENT_API_ERROR) {
				throw $e;
			}
		}

		$pdo = Env::getDbConnectionForUserWrite($user_id);
		// $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);

		$user = User::find($user_id, $pdo);

		// 判定是否同一天
		if (!BaseModel::isSameDay_AM4($now, BaseModel::strToTime($user->li_last))) {
			throw new PadException(RespCode::LOGIN_DATE_DIFFERENT, "login date different");
		}

		$activity = Activity::get($activity_id);
		if (!$activity) {
			throw new PadException(RespCode::ACTIVITY_ALREADY_FINISHED, "activity not found (id=$activity_id)");//2017
		} elseif (!$activity->isEnabled($now)) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "activity invalid (id=$activity_id)");
		} elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_1YG && $user->device_type != UserDevice::TYPE_IOS) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "activity only for ios (id=$activity_id)");
		} elseif($activity->activity_type == Activity::ACTIVITY_TYPE_POWER) {
			// 获取战斗力
			$team_id = $params['activity_team'];
			$param = new DeckParamData();
			$total_power = $param->getTotalPower($user, $team_id, $pdo);

			if (!$activity->checkCondition($total_power, $user->vip_lv)) {
				throw new PadException(RespCode::CONDITION_NOT_REACH, "total power not enough (user_id={$user_id},activity_id={$activity_id},total_power={$total_power})");//2018
			}
		}

		$user_activity = UserActivity::findBy(array(
			'user_id' => $user_id,
			'activity_id' => $activity_id
		), $pdo);

		//
		if($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CONSUM && !$activity->checkCondition($user->tc_gold_period, $user->vip_lv)){
			throw new PadException(RespCode::CONDITION_NOT_REACH, "not reach total consum condition (user_id={$user_id},activity_id={$activity_id})");//2018
		}

		if($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CHARGE && !$activity->checkCondition($user->tp_gold_period, $user->vip_lv)){
			throw new PadException(RespCode::CONDITION_NOT_REACH, "not reach total charge condition (user_id={$user_id},activity_id={$activity_id})");//2018
		}


		$total_count_activity_types = array(
				Activity::ACTIVITY_TYPE_COIN_CONSUM,
				Activity::ACTIVITY_TYPE_STA_BUY_COUNT,
				Activity::ACTIVITY_TYPE_GACHA_COUNT,
				Activity::ACTIVITY_TYPE_CARD_EVO_COUNT,
				Activity::ACTIVITY_TYPE_SKILL_AWAKE_COUNT
		);

		if (in_array($activity->activity_type, $total_count_activity_types)) {
			$uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
			$not_finish = true;
			if ($activity->activity_type == Activity::ACTIVITY_TYPE_STA_BUY_COUNT) {
				if ($activity->checkCondition($uac->sta_buy_count, $user->vip_lv)) {
					$not_finish = false;
				}
			} elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_COIN_CONSUM) {
				if ($activity->checkCondition($uac->coin_consum, $user->vip_lv)) {
					$not_finish = false;
				}
			} elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_SKILL_AWAKE_COUNT) {
				if ($activity->checkCondition($uac->skill_awake_count, $user->vip_lv)) {
					$not_finish = false;
				}
			} elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_CARD_EVO_COUNT) {
				if ($activity->checkCondition($uac->card_evo_count, $user->vip_lv)) {
					$not_finish = false;
				}
			} elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_GACHA_COUNT) {
				if ($activity->checkCondition($uac->gacha_count, $user->vip_lv)) {
					$not_finish = false;
				}
			}
			if ($not_finish) {
				throw new PadException(RespCode::CONDITION_NOT_REACH, "not reach total count condition (user_id={$user_id},activity_id={$activity_id})");
			}
		}



		if($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE
				|| $activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED){
			if(!$user_activity || $user_activity->status != UserActivity::STATE_CLEAR){
				throw new PadException(RespCode::CONDITION_NOT_REACH, "not reach condition (user_id={$user_id},activity_id={$activity_id})");//2018
			}
		}

		if (($activity->activity_type == Activity::ACTIVITY_TYPE_FIRST_CHARGE && $user->first_charge_gift_received == 1)
			|| ($activity->activity_type == Activity::ACTIVITY_TYPE_SHARE && BaseModel::isSameDay_AM4($now, BaseModel::strToTime($user->share_reward_at)))
			|| ($user_activity && $user_activity->status == UserActivity::STATE_RECEIVED)) {
			throw new PadException(RespCode::ALREADY_RECEIVED, "already received (user_id={$user_id},activity_id={$activity_id})");//2014
		} elseif($user_activity && $user_activity->status != UserActivity::STATE_CLEAR && ($activity->activity_type != Activity::ACTIVITY_TYPE_SHARE)){
			throw new PadException(RespCode::CONDITION_NOT_REACH, "user activity status error (user_id={$user_id},activity_id={$activity_id})");//2018
		}

		$bonus_id1 = (int) $activity->bonus_id1;
		$amount1 = (int) $activity->amount1;
		$piece_id1 = ($bonus_id1 == BaseBonus::PIECE_ID ? (int) $activity->piece_id1 : null);
		$bonus_id2 = (int) $activity->bonus_id2;
		$amount2 = (int) $activity->amount2;
		$piece_id2 = ($bonus_id2 == BaseBonus::PIECE_ID ? (int) $activity->piece_id2 : null);
		$bonus_id3 = (int) $activity->bonus_id3;
		$amount3 = (int) $activity->amount3;
		$piece_id3 = ($bonus_id3 == BaseBonus::PIECE_ID ? (int) $activity->piece_id3 : null);
		$bonus_id4 = (int) $activity->bonus_id4;
		$amount4 = (int) $activity->amount4;
		$piece_id4 = ($bonus_id4 == BaseBonus::PIECE_ID ? (int) $activity->piece_id4 : null);

		$items = array();

		try {
			$pdo->beginTransaction();

			$tlog_infos = self::getActivityTlogInfo($activity->activity_type, $activity->seq, $user);
			UserTlog::beginTlog($user, $tlog_infos);

			if ($bonus_id1 && $amount1) {
				$item = $user->applyBonus($bonus_id1, $amount1, $pdo, null, $token, $piece_id1);
				$items[] = User::arrangeBonusResponse($item, $rev);
			}
			if ($bonus_id2 && $amount2) {
				$item = $user->applyBonus($bonus_id2, $amount2, $pdo, null, $token, $piece_id2);
				$items[] = User::arrangeBonusResponse($item, $rev);
			}
			if ($bonus_id3 && $amount3) {
				$item = $user->applyBonus($bonus_id3, $amount3, $pdo, null, $token, $piece_id3);
				$items[] = User::arrangeBonusResponse($item, $rev);
			}
			if ($bonus_id4 && $amount4) {
				$item = $user->applyBonus($bonus_id4, $amount4, $pdo, null, $token, $piece_id4);
				$items[] = User::arrangeBonusResponse($item, $rev);
			}

			$datetime = BaseModel::timeToStr($now);
			if ($activity->activity_type == Activity::ACTIVITY_TYPE_SHARE) {
				$user->share_reward_at = $datetime;
			} elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_FIRST_CHARGE) {
				$user->first_charge_gift_received = 1;
			}
			$user->update($pdo);

			// 更新用户活动状态
			UserActivity::updateStatus($user_id, $activity_id, UserActivity::STATE_RECEIVED, $pdo);

			$pdo->commit();

			UserTlog::commitTlog($user, $token);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			throw $e;
		}

		$res = array(
			'res' => RespCode::SUCCESS,
			'items' => $items
		);

		return json_encode($res);
	}


	public function getActivityTlogInfo($activity_type, $seq, $user) {
		$infos = array(
			'money_reason' => Tencent_Tlog::REASON_ACTIVITY_BONUS,
			'money_subreason' => 0,
			'item_reason' => Tencent_Tlog::ITEM_REASON_ACTIVITY_BONUS,
			'item_subreason' => 0,
		);

		if ($activity_type == Activity::ACTIVITY_TYPE_FIRST_CHARGE) {
			$infos['money_subreason'] = Tencent_Tlog::SUBREASON_FIRST_BUY_GIFT;
			$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_FIRST_BUY_GIFT;
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_FIRST_CHARGE_DOUBLE) {
			$infos['money_subreason'] = Tencent_Tlog::SUBREASON_FIRST_BUY_DOUBLE;
			$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_FIRST_BUY_DOUBLE;
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_SHARE) {
			$infos['money_subreason'] = Tencent_Tlog::SUBREASON_SHARE_GIFT;
			$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_SHARE_GIFT;
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_1YG) {
			$infos['money_subreason'] = Tencent_Tlog::SUBREASON_1YG;
			$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_1YG;
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_TOTAL_CHARGE) {
			if ($seq == 1) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP1;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP1;
			} elseif ($seq == 2) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP2;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP2;
			} elseif ($seq == 3) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP3;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP3;
			} elseif ($seq == 4) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP4;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP4;
			} elseif ($seq == 5) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP5;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP5;
			} elseif ($seq == 6) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP6;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP6;
			} elseif ($seq == 7) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP7;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP7;
			} elseif ($seq == 8) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP8;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP8;
			} elseif ($seq == 9) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CHARGE_STEP9;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CHARGE_STEP9;
			}
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE) {
			if ($user->count_p6 == 1) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_DAILY_CHARGE_DAY1;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_DAILY_CHARGE_DAY1;
			} elseif ($user->count_p6 == 2) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_DAILY_CHARGE_DAY2;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_DAILY_CHARGE_DAY2;
			} elseif ($user->count_p6 == 3) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_DAILY_CHARGE_DAY3;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_DAILY_CHARGE_DAY3;
			} elseif ($user->count_p6 == 4) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_DAILY_CHARGE_DAY4;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_DAILY_CHARGE_DAY4;
			} elseif ($user->count_p6 == 5) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_DAILY_CHARGE_DAY5;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_DAILY_CHARGE_DAY5;
			}
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED) {
			$infos['money_subreason'] = Tencent_Tlog::SUBREASON_DAILY_CHARGE_EXTENDED;
			$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_DAILY_CHARGE_EXTENDED;
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_TOTAL_CONSUM) {
			if ($seq == 1) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP1;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP1;
			} elseif ($seq == 2) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP2;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP2;
			} elseif ($seq == 3) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP3;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP3;
			} elseif ($seq == 4) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP4;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP4;
			} elseif ($seq == 5) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP5;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP5;
			} elseif ($seq == 6) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP6;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP6;
			} elseif ($seq == 7) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP7;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP7;
			} elseif ($seq == 8) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP8;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP8;
			} elseif ($seq == 9) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_TOTAL_CONSUM_STEP9;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_TOTAL_CONSUM_STEP9;
			}
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_POWER) {
			if ($seq == 1) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_POWER_STEP1;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_POWER_STEP1;
			} elseif ($seq == 2) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_POWER_STEP2;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_POWER_STEP2;
			} elseif ($seq == 3) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_POWER_STEP3;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_POWER_STEP3;
			} elseif ($seq == 4) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_POWER_STEP4;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_POWER_STEP4;
			} elseif ($seq == 5) {
				$infos['money_subreason'] = Tencent_Tlog::SUBREASON_POWER_STEP5;
				$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_POWER_STEP5;
			}
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_DAILY_LOGIN) {
			$infos['money_subreason'] = Tencent_Tlog::SUBREASON_DAILY_LOGIN;
			$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_DAILY_LOGIN;
		} elseif ($activity_type == Activity::ACTIVITY_TYPE_MONTHCARD) {
			$infos['money_subreason'] = Tencent_Tlog::SUBREASON_MONTHCARD;
			$infos['item_subreason'] = Tencent_Tlog::ITEM_SUBREASON_MONTHCARD;
		}
		return $infos;
	}
}
