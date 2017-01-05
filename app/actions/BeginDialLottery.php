<?php
/**
 * #PADC_DY#
 *
 * 获取交换商品列表
 */
class BeginDialLottery extends BaseAction
{
    public function action($params)
    {
        //首先检测活动是否开放
        $time = time();
        $events_type = LimitEventsTime::LUCKY_DIAL;
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

        //转盘的三个参数：转盘价格、重置价格、8个位置对应的概率
        $gameConstant = GameConstant::getDialParameter();
        $priceForTurnDial = json_decode($gameConstant['PriceForTurnDial'],true);
        $priceForResetDial = json_decode($gameConstant['PriceForResetDial'],true);
        $maxResetNumForDial = $gameConstant['MaxResetNumForDial'];

        $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        $prizeList = array();             //未抽中的奖品列表ID
        $prizeListProb = array();         //未抽中的奖品的抽中概率
        $getPrizeList = array();          //抽中奖品的ID列表
        $userDialInfo = null;


        $items = array();
        try {
            $pdo_user->beginTransaction();

            $userDialInfo = UserDialInfo::findBy(array('user_id'=>$user_id),$pdo_user);
            if (null == $userDialInfo) {
                return json_encode(array(
                    'res' => RespCode::UNKNOWN_ERROR
                ));
            }
            $sequence = $userDialInfo->sequence;
            $tmpArr = json_decode($userDialInfo->prizelist);

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
            //已经获得全部的奖品
            if ($getPrizeNum == count($tmpArr)) {
                return json_encode(array(
                    'res' => RespCode::UNKNOWN_ERROR
                ));
            } else {
                $user = User::find($user_id, $pdo_user);

                $before_gold = $user->gold;
                $before_pgold = $user->pgold;
                $fripnt_before = $user->fripnt;

                $dialGold = 0;                    //此次转盘需要的魔法石
                //首次转盘
                if(0 == $userDialInfo->flag1)
                {
                    $dialGold = 0;
                    $userDialInfo->flag1 = 1;
                }
                else
                {
                    $dialGold = $priceForTurnDial[$getPrizeNum];
                }


                if ($user->checkHavingGold($dialGold) === FALSE) {
                    global $logger;
                    $logger->log(("not enough gold"), Zend_Log::DEBUG);
                    return json_encode(RespCode::NOT_ENOUGH_MONEY);
                }


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

                $userDialInfo->prizelist = json_encode($userPrizeItemList);
                $userDialInfo->updated_at = BaseModel::timeToStr(time());
                $userDialInfo->update($pdo_user);

                //给玩家发放奖励
                $prizeInfo = DialPrize::findBy(array('id' => $prizeId));


                $user->payGold($dialGold, $token, $pdo_user);      //玩家付费

                $user->accessed_at = User::timeToStr(time());
                $user->accessed_on = $user->accessed_at;

                $item = $user->applyBonus($prizeInfo->bonus_id, $prizeInfo->amount, $pdo_user, null, $token, $prizeInfo->piece_id);
                $items[] = User::arrangeBonusResponse($item, $rev);

                $user->update($pdo_user);

                $pdo_user->commit();
                $pdo_user = null;

                //TODO
                //转盘得到的奖励
                //卡牌的奖励
                if($prizeInfo->bonus_id <= BaseBonus::MAX_CARD_ID){
                    UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_CARD,$prizeInfo->bonus_id,$prizeInfo->amount,$items[0]['amount'], Tencent_Tlog::REASON_LUNCKY_DIAL_PRIZE,0, 0, Tencent_Tlog::MONEY_TYPE_DIAMOND,0);
                }elseif($prizeInfo->bonus_id == BaseBonus::PIECE_ID){
                    UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE,$prizeInfo->piece_id,$prizeInfo->amount,$items[0]['amount'], Tencent_Tlog::REASON_LUNCKY_DIAL_PRIZE,0, 0, Tencent_Tlog::MONEY_TYPE_DIAMOND,0);
                }elseif($prizeInfo->bonus_id == BaseBonus::COIN_ID){
                    UserTlog::sendTlogMoneyFlow($user,$prizeInfo->amount, Tencent_Tlog::REASON_LUNCKY_DIAL_PRIZE, Tencent_Tlog::MONEY_TYPE_MONEY,0,0, 0,0);
                }elseif($prizeInfo->bonus_id == BaseBonus::MAGIC_STONE_ID){
                    UserTlog::sendTlogMoneyFlow($user,$prizeInfo->amount, Tencent_Tlog::REASON_LUNCKY_DIAL_PRIZE, Tencent_Tlog::MONEY_TYPE_DIAMOND,abs($user->gold - $before_gold),abs($user->pgold - $before_pgold), 0,0);
                }elseif($prizeInfo->bonus_id == BaseBonus::FRIEND_POINT_ID){
                    UserTlog::sendTlogMoneyFlow($user, $user->fripnt - $fripnt_before, Tencent_Tlog::REASON_LUNCKY_DIAL_PRIZE, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT);
                }

                //此次转盘的魔法石付费
                UserTlog::sendTlogMoneyFlow($user,-$dialGold, Tencent_Tlog::REASON_LUNCKY_DIAL_TURN, Tencent_Tlog::MONEY_TYPE_DIAMOND,abs($user->gold - $before_gold),abs($user->pgold - $before_pgold),0,0);
            }
        }catch (Exception $e) {
            if ($pdo_user->inTransaction()) {
                $pdo_user->rollBack();
            }

            throw new PadException(RespCode::UNKNOWN_ERROR, "Failed to begin the dial game!".$e->getMessage());
        }

        //将用户的获得奖品存到UserDialPrize
        UserDialPrize::insertPrizeRecord($user_id,$prizeId,$sequence);

        $gold = 0;
        if(count($getPrizeList) >= $maxResetNumForDial){
            $gold = 0;
        }else{
            $gold = $priceForTurnDial[count($getPrizeList)];
        }

        //首次刷新转盘
        if(0 == $userDialInfo->flag2)
            $rgold = 0;
        else
            $rgold = $userDialInfo->sequence < $maxResetNumForDial? $priceForResetDial[$userDialInfo->sequence]:0;

        $letfGold = ($before_pgold +$before_gold)-$dialGold;

        $t1 = $userDialInfo->flag1 == 0?1:0;       //首次转盘免费
        $t2 = $userDialInfo->flag2 == 0?1:0;       //首次重置转盘免费

        //用户的魔法石余额是否需要返回
        return json_encode(array(
            'res'     =>RespCode::SUCCESS,
            'items'   =>$items,
            'prizeid' =>$prizeId,                 //此次获得的奖品ID
            'lgold'   =>$letfGold,                //用户剩余的魔法石
            'gold'    =>$gold,                    //下一次转盘需要的金币
            'rgold'   =>$rgold,                   //下一次刷新转盘需要的金币
            'mreset'  =>$maxResetNumForDial,
            'dial_lucky_total' => $t1+$t2,
        ));
    }
}