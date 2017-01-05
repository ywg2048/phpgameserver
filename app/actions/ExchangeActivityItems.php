<?php

/**
 * #PADC_DY#
 * 活动兑换碎片用API
 */
class ExchangeActivityItems extends BaseAction {

    // http://domainname/api.php?action=receive_activity_reward&pid=1&activity_id=1
    public function action($params) {
        global $logger;
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
        try {
            $pdo = Env::getDbConnectionForUserWrite($user_id);
            // $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);

            $user = User::find($user_id, $pdo);

            // 判定是否同一天
            if (!BaseModel::isSameDay_AM4($now, BaseModel::strToTime($user->li_last))) {
                throw new PadException(RespCode::LOGIN_DATE_DIFFERENT, "login date different");
            }

            $activity = Activity::get($activity_id);
            // 活动不存在
            if (!$activity) {
                throw new PadException(RespCode::UNKNOWN_ERROR, "activity not found (id=$activity_id)");
                // 活动已经结束
            } elseif (!$activity->isEnabled($now)) {
                throw new PadException(RespCode::UNKNOWN_ERROR, "activity invalid (id=$activity_id)");
                // 活动类型不是兑换物品类型
            } elseif ($activity->activity_type != Activity::ACTIVITY_TYPE_EXCHANGE_ITEM) {
                throw new PadException(RespCode::UNKNOWN_ERROR, "wrong activity type (id=$activity_id), type:$activity->activity_type");
            }

            //判断是否超过领取次数
            // $pdo = Env::getDbConnectionForUserRead($params['pid']);
            $user_activity = UserActivity::findBy(array('user_id'=>$params['pid'],'activity_id'=>$activity_id),$pdo);
            $logger->log("user_activity = ".json_encode($user_activity),Zend_log::DEBUG);
            $logger->log("activity = ".json_encode($activity->exchange_times),Zend_log::DEBUG);
            if($user_activity){
                if((int)$user_activity->counts >= (int)$activity->exchange_times && (int)$activity->exchange_times != 0){
                    throw new PadException(RespCode::ACTIVITY_ALREADY_FINISHED, "{$activity_id} counts > exchange_times");
                }    
            }
            

            list($cost_type, $cost_piece_id, $cost_amount) = $activity->getExchangeItem();


            if ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_PIECE) {
                // 获得用户用于兑换bonus物品的碎片（货币碎片）
                $cost_piece = UserPiece::getUserPiece($user->id, $cost_piece_id, $pdo);
                // 用户的货币碎片数量不足
                if (!$cost_piece) {
                    throw new PadException(RespCode::UNKNOWN_ERROR, "not have piece");
                }
                if ($cost_piece->num < $cost_amount) {
                    throw new PadException(RespCode::UNKNOWN_ERROR, "not enough piece (pieceid=$cost_piece_id), need:$cost_amount,own:$cost_piece->num");
                }
                $cost_piece->subtractPiece($cost_amount);
                $cost_piece->update($pdo);
            } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_COIN) {
                if ($user->coin < $cost_amount) {
                    throw new PadException(RespCode::UNKNOWN_ERROR, "not enough coin, need:$cost_amount,own:$user->coin");
                }
                $user->addCoin(-1 * $cost_amount);
            } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_GOLD) {
                if(!$user->checkHavingGold($cost_amount)){
                    throw new PadException(RespCode::NOT_ENOUGH_MONEY, "not enough gold, need:$cost_amount");
                }
                $billno = $user->payGold($cost_amount, $token, $pdo);
            }


            $bonus_id1 = (int)$activity->bonus_id1;
            $amount1 = (int)$activity->amount1;
            $piece_id1 = ($bonus_id1 == BaseBonus::PIECE_ID ? (int)$activity->piece_id1 : null);
            $bonus_id2 = (int)$activity->bonus_id2;
            $amount2 = (int)$activity->amount2;
            $piece_id2 = ($bonus_id2 == BaseBonus::PIECE_ID ? (int)$activity->piece_id2 : null);
            $bonus_id3 = (int)$activity->bonus_id3;
            $amount3 = (int)$activity->amount3;
            $piece_id3 = ($bonus_id3 == BaseBonus::PIECE_ID ? (int)$activity->piece_id3 : null);
            $bonus_id4 = (int)$activity->bonus_id4;
            $amount4 = (int)$activity->amount4;
            $piece_id4 = ($bonus_id4 == BaseBonus::PIECE_ID ? (int)$activity->piece_id4 : null);

            $items = array();


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

            $user->update($pdo);

            $pdo->commit();

            UserTlog::commitTlog($user, $token);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            if ($billno) {
                $user->cancelPay($cost_amount, $billno, $token);
            }
            throw $e;
        }
        $count = 0;
        $status = 0;
        $pdo = Env::getDbConnectionForUserRead($params['pid']);
        $user_activity = UserActivity::findBy(array('user_id'=>$params['pid'],'activity_id'=>$activity_id),$pdo);
        if($user_activity){
            
            if(($user_activity->counts+1) == $activity->exchange_times){
                $user_activity->status = 3;
            }else{
                //判断兑换物品数量够不够
                if ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_PIECE) {
                    // 获得用户用于兑换bonus物品的碎片（货币碎片）
                    $cost_piece = UserPiece::getUserPiece($user->id, $cost_piece_id, $pdo);
                    global $logger;
                    $logger->log("cost_piece".json_encode($cost_piece),Zend_log::DEBUG);
                    // 用户的货币碎片数量不足
                    if (!$cost_piece) {
                        $user_activity->status = 1;
                    }
                    if ($cost_piece->num < $cost_amount) {
                        $user_activity->status = 1;
                    }
                    $logger->log("cost_amount".$cost_amount,Zend_log::DEBUG);
                    
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_COIN) {
                    if ($user->coin < $cost_amount) {
                        $user_activity->status = 1;
                    }
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_GOLD) {
                    if(!$user->checkHavingGold($cost_amount)){
                        $user_activity->status = 1;
                    }
                }    
            }
            
            $user_activity->counts++;
            $user_activity->user_id = $params['pid'];
            $user_activity->activity_id = $activity_id;
            $user_activity->update($pdo);

            $user_activity_1 = UserActivity::findBy(array('user_id'=>$params['pid'],'activity_id'=>$activity_id),$pdo);
            $status = $user_activity_1->status;

            $count = $user_activity_1->counts;
        }else{
            $user_activity = new UserActivity();
            $user_activity->user_id = $params['pid'];
            $user_activity->activity_id = $activity_id;
            $user_activity->status = 2;
            if($activity->exchange_times == 1){
                $user_activity->status = 3;
            }else{
                 //判断兑换物品数量够不够
                if ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_PIECE) {
                    // 获得用户用于兑换bonus物品的碎片（货币碎片）
                    $cost_piece = UserPiece::getUserPiece($user->id, $cost_piece_id, $pdo);
                    global $logger;
                    $logger->log("cost_piece".json_encode($cost_piece),Zend_log::DEBUG);
                    // 用户的货币碎片数量不足
                    if (!$cost_piece) {
                        $user_activity->status = 1;
                    }
                    if ($cost_piece->num < $cost_amount) {
                        $user_activity->status = 1;
                    }
                    $logger->log("cost_amount".$cost_amount,Zend_log::DEBUG);
                    
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_COIN) {
                    if ($user->coin < $cost_amount) {
                        $user_activity->status = 1;
                    }
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_GOLD) {
                    if(!$user->checkHavingGold($cost_amount)){
                        $user_activity->status = 1;
                    }
                }    
            }
            $user_activity->ordered_at = BaseModel::timeToStr(time());
            $user_activity->counts = 1;
            $user_activity->create($pdo);
            $count = 1;
            $status = $user_activity->status;
        }

        $res = array(
            'res' => RespCode::SUCCESS,
            'items' => $items,
            'activity_info' =>array(
                'activity_id' => $activity_id,
                'count' => (int)$count,
                'ordered_at' => BaseModel::timeToStr(time()),
                'status' => $status
            ),
            'coin' => (int)$user->coin,
            'gold' => (int)($user->gold + $user->pgold),
            'piece' => empty($cost_piece) ? null : UserPiece::arrangeColumn($cost_piece),
        );
        
        $logger->log("items = ".json_encode($count),Zend_log::DEBUG);
        return json_encode($res);
    }


    public function getActivityTlogInfo($activity_type, $seq, $user) {
        $infos = array(
            'money_reason' => Tencent_Tlog::REASON_ACTIVITY_BONUS,
            'money_subreason' => Tencent_Tlog::SUBREASON_EXCHANGE_ITEM,
            'item_reason' => Tencent_Tlog::ITEM_REASON_ACTIVITY_BONUS,
            'item_subreason' => Tencent_Tlog::ITEM_SUBREASON_EXCHANGE_ITEM,
        );

        return $infos;
    }
}
