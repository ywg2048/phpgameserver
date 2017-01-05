<?php
/**
 * #PADC_DY#
 *
 * 获取交换商品列表
 */
class BeginGoldDialPrize extends BaseAction
{
    public function action($params)
    {
        //首先检测活动是否开放
        $time = time();
       $events_type = LimitEventsTime::MAGIC_STONE_DIAL;
        $events_id = LimitEventsTime::getTypeById($events_type);
        if($events_id == null){
            throw new PadException(RespCode::ACTIVITY_ALREADY_FINISHED, "It's not in events time");
        }
        if(!LimitEventsTime::isEnabled($time,$events_id)){
            throw new PadException(RespCode::ACTIVITY_ALREADY_FINISHED, "It's not in activity time");
        }

        $rev = isset($params['r']) ? $params['r'] : 0;
        $user_id = $params['pid'];

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

        $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        $prizeList = array();             //未抽中的奖品列表ID
        $prizeListProb = array();         //未抽中的奖品的抽中概率
        $getPrizeList = array();          //抽中奖品的ID列表
        $userGoldDialInfo = null;

        $items = array();
        try {
            $pdo_user->beginTransaction();

            $userGoldDialInfo = UserGoldDialInfo::findBy(array('user_id'=>$user_id),$pdo_user);
            if (null == $userGoldDialInfo) {
                return json_encode(array(
                    'res' => RespCode::UNKNOWN_ERROR
                ));
            }
            $chance = $userGoldDialInfo->chance;
            if($chance <= 0){
                return json_encode(array(
                    'res' => RespCode::UNKNOWN_ERROR
                ));
            }
            $tmpArr = json_decode($userGoldDialInfo->prizelist);

            foreach ($tmpArr as $value) {
                //奖品已经抽中
                if (1 == $value[1]) {
                    $getPrizeList[] = $value[0];
                } else {
                    $prizeList[] = $value[0];
                    $prizeListProb[] = $value[2];
                }
            }

            $prizeId = null;
            $getPrizeNum = count($getPrizeList);
            //已获得奖盘上的所有奖品
            if ($getPrizeNum == count($tmpArr)) {
                return json_encode(array(
                    'res' => RespCode::UNKNOWN_ERROR
                ));
            } else {

                $user = User::find($user_id, $pdo_user);
                $before_gold = $user->gold;
                $before_pgold = $user->pgold;

                //抽奖的计算方式
                $sum_prob = array_sum($prizeListProb);
                $seed = mt_rand(0, $sum_prob);
                $grad_sum = 0;
                foreach ($prizeListProb as $key => $value) {
                    $grad_sum += $value;
                    $seed -= $grad_sum;
                    //抽中商品，进行处理
                    if ($seed <= 0) {
                        $prizeId = $prizeList[$key];                  //抽中奖品ID为$prizeId
                        $getPrizeList[] = $prizeId;
                        unset($prizeList[$key]);
                        unset($prizeListProb[$key]);
                        break;
                    }
                }
                $chance = $chance-1;
                ksort($getPrizeList);

                //更新玩家UserDialInfo中的信息
                $userPrizeItemList = array();
                //未抽中奖品的信息
                foreach ($prizeList as $key => $value) {
                    $userPrizeItemList[] = array(
                        $value,                                       //奖品ID
                        0,                                            //玩家未抽取过
                        $prizeListProb[$key],                         //概率
                    );
                }
                //抽中奖品的信息
                foreach ($getPrizeList as $value) {
                    $userPrizeItemList[] = array(
                        $value,
                        1,
                        0,
                    );
                }
                $userGoldDialInfo->flag = 0;
                $userGoldDialInfo->chance = $chance;
                $userGoldDialInfo->prizelist = json_encode($userPrizeItemList);
                $userGoldDialInfo->updated_at = BaseModel::timeToStr(time());
                $userGoldDialInfo->update($pdo_user);

                //给玩家发放奖励
                $prizeInfo = DialPrize::findBy(array('id' => $prizeId));

                $item = $user->applyBonus($prizeInfo->bonus_id, $prizeInfo->amount, $pdo_user, null, $token, $prizeInfo->piece_id);
                $items[] = User::arrangeBonusResponse($item, $rev);

                $user->update($pdo_user);
                $pdo_user->commit();
                $pdo_user = null;

                $after_gold  = $user->gold + $user->pgold;
                //TODO
                UserTlog::sendTlogMoneyFlow($user, $after_gold - ($before_gold + $before_pgold), Tencent_Tlog::REASON_GOLD_DIAL_TURN, Tencent_Tlog::MONEY_TYPE_DIAMOND,abs($user->gold - $before_gold),abs($user->pgold - $before_pgold),0,0);
            }
        }catch (Exception $e) {
            if ($pdo_user->inTransaction()) {
                $pdo_user->rollBack();
            }
            throw new PadException(RespCode::UNKNOWN_ERROR, $e->getMessage());
        }

        //将用户的获得奖品存到UserDialPrize
        UserGoldDialPrize::insertPrizeRecord($user_id,$prizeId);

        //用户的魔法石余额是否需要返回
        return json_encode(array(
            'res'     =>RespCode::SUCCESS,
            'agold'   =>$items[0]['gold'],
            'prizeid' =>$prizeId,                 //此次获得的奖品ID
            'chance'  =>$chance,
            'pgold'   =>$userGoldDialInfo->pgold, //玩家每日累积充值的魔法石
            'free'    =>$userGoldDialInfo->flag,
            'gold_dial_total' => $userGoldDialInfo->chance,   //客户端红点提示
        ));
    }
}