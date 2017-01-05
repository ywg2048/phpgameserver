<?php

/**
 * #PADC_DY#
 *
 * 获取交换商品列表
 */
class GetUserDialInfo extends BaseAction
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
        
        $user_id = $params['pid'];

        //转盘的三个参数：转盘价格、重置价格、8个位置对应的概率
        $gameConstant = GameConstant::getDialParameter();
        $probForDialPosition = json_decode($gameConstant['ProbForDialPosition'],true);
        $priceForTurnDial = json_decode($gameConstant['PriceForTurnDial'],true);
        $priceForResetDial = json_decode($gameConstant['PriceForResetDial'],true);
        $maxResetNumForDial = $gameConstant['MaxResetNumForDial'];

        $res = array();
        $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        $userDialInfo = UserDialInfo::findBy(array('user_id'=>$user_id),$pdo_user);
        $userDialInfo_by_activity = UserDialInfo::findBy(array('user_id'=>$user_id,'events_id'=>$events_id),$pdo_user); 

        $getPrizeNum = 0;        //玩家已获得的奖品数量
        $prizes = array();
        //玩家第一次玩转盘
        if(null == $userDialInfo){
            try{
                $userPrizeItemList = array();
                $pdo_user->beginTransaction();
                //根据设置的好的概率从每个档次的奖品中，取出两个
                $prizeItems = DialPrize::getPrizeItems(DialPrize::DIAL_TYPE_LUCKY);
                $i = 0;
                foreach($prizeItems as $prizeItem){
                    $prizes[] = array(
                        'id'        => $prizeItem->id,
                        'bonus_id'  => $prizeItem->bonus_id,
                        'amount'    => $prizeItem->amount,
                        'piece_id'  => $prizeItem->piece_id,
                        'level'     => $prizeItem->level,
                        'isacquired'=> 0,
                    );

                    $userPrizeItemList[] = array(
                        $prizeItem->id,
                        0,                                   //是否已经抽取到
                        $probForDialPosition[$i++],          //抽奖得到的概率
                    );
                }
                $prizelist = json_encode($userPrizeItemList);

                $userDialInfo = new UserDialInfo();
                $userDialInfo->user_id = $user_id;
                $userDialInfo->prizelist = $prizelist;
                $userDialInfo->sequence = 0;
                $userDialInfo->flag1 = 0;
                $userDialInfo->flag2 = 0;
                $userDialInfo->events_id = $events_id;
                $userDialInfo->create($pdo_user);

                $pdo_user->commit();
            }catch(Exception $e)
            {
                if($pdo_user->inTransaction()){
                    $pdo_user->rollBack();
                }
                throw new PadException(RespCode::UNKNOWN_ERROR,"Failed to insert into UserDialInfo!");
            }
            $pdo_user = null;
            $res['seq']    = 0;
            $res['gold']   = 0;      //下一次转盘的付费
            $res['rgold']  = 0;      //下一次刷新转盘的付费
            $res['prizes'] = $prizes;
        }else{
            if(!$userDialInfo_by_activity){
                //参加过往期活动，但是没有参加本期
                $userDialInfo->user_id = $user_id;
                $userDialInfo->events_id = $events_id;              
                $userDialInfo->prizelist = "";
                $userDialInfo->sequence = 0;
                $userDialInfo->flag1 = 0;
                $userDialInfo->flag2 = 0;
                $userDialInfo->update($pdo_user);
                $userDialInfo = UserDialInfo::updatePrizeItemsById($userDialInfo->id,$user_id,$probForDialPosition);

            }else{
                //活动期间四点刷新
                $update_at =BaseModel::strToTime($userDialInfo->updated_at);
                $refresh_time =BaseModel::strToTime(date('Y-m-d').' 04:00:00');
                $now = BaseModel::strToTime(date('Y-m-d H:i:s'));

                if(($now >= $refresh_time)&&($update_at <= $refresh_time)){
                      $userDialInfo = UserDialInfo::updatePrizeItemsById($userDialInfo->id,$user_id,$probForDialPosition);
                }
            }
            

            $tmpArr = json_decode($userDialInfo->prizelist,true);
            foreach($tmpArr as $value){
                $prizeItem = DialPrize::get($value[0]);
                $prizes[] = array(
                    'id'        => $prizeItem->id,
                    'bonus_id'  => $prizeItem->bonus_id,
                    'amount'    => $prizeItem->amount,
                    'piece_id'  => $prizeItem->piece_id,
                    'level'     => $prizeItem->level,
                    'isacquired'=> $value[1],
                );

                if(1 == $value[1])
                    $getPrizeNum += 1;
            }

            $res['seq'] = $userDialInfo->sequence;
            //首次转盘、重置均免费
            if(0 == $userDialInfo->flag1)
            {
                $res['gold']  = 0;
            }else{
                $res['gold']  = $getPrizeNum<$maxResetNumForDial?$priceForTurnDial[$getPrizeNum]:0;
            }
            if(0 == $userDialInfo->flag2)
            {
                $res['rgold'] = 0;
            }else{
                $j = $res['seq'];
                $res['rgold'] = $j < $maxResetNumForDial?$priceForResetDial[$j]:0;
            }
            $res['prizes'] = $prizes;
        }

        $t1 = $userDialInfo->flag1 == 0?1:0;       //首次转盘免费
        $t2 = $userDialInfo->flag2 == 0?1:0;       //首次重置转盘免费
        $res['dial_lucky_total'] = $t1 + $t2;

        $pdo_user = null;
        $res['res'] = RespCode::SUCCESS;
        $res['mreset'] = $maxResetNumForDial;
        return json_encode($res);
    }

}