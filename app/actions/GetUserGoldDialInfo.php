<?php
/**
 * #PADC#
 */
class GetUserGoldDialInfo extends BaseAction {
    public function action($params) {

        //首先检测活动是否开放
        $time = time();
        $events_type = LimitEventsTime::MAGIC_STONE_DIAL;
        $events_id = LimitEventsTime::getTypeById($events_type);

        if($events_id == null){
            throw new PadException(RespCode::ACTIVITY_ALREADY_FINISHED, "It's not in events time");
        }
        if(!LimitEventsTime::isEnabled($time,$events_id)){
            throw new PadException(RespCode::ACTIVITY_ALREADY_FINISHED, "It's not in events time");
        }
        
        $user_id = $params['pid'];

        //魔法转盘的参数:转盘位置的概率、转盘的价格、刷新转盘需要消耗的魔法石、每日最大的重置次数
        $probForGoldDialPosition = GameConstant::getParam('ProbForGoldDialPosition');
        $goldPointForChance = GameConstant::getParam('GoldPointForChance');

        $res = array();
        $pdo_user = Env::getDbConnectionForUserWrite($user_id);

        $userGoldDialInfo = UserGoldDialInfo::findBy(array('user_id'=>$user_id),$pdo_user);
        $userGoldDialInfo_by_activity = UserGoldDialInfo::findBy(array('user_id'=>$user_id,'events_id'=>$events_id),$pdo_user);

        $getPrizeNum = 0;        //玩家已获得的奖品数量
        $prizes = array();
        //玩家第一次玩转盘
        if(null == $userGoldDialInfo){
            try{
                $userPrizeItemList = array();
                $pdo_user->beginTransaction();
                //首次转盘的物品，所有玩家都是一样的
                $prizeItems = DialPrize::getFirstPrizeItemsForGoldDial();
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
                        $probForGoldDialPosition[$i++],     //抽奖得到的概率
                    );
                }
                $prizelist = json_encode($userPrizeItemList);

                $userGoldDialInfo = new UserGoldDialInfo();
                $userGoldDialInfo->user_id = $user_id;
                $userGoldDialInfo->prizelist = $prizelist;
                $userGoldDialInfo->pgold = 0;
                $userGoldDialInfo->chance = 1;
                $userGoldDialInfo->flag = 1;
                $userGoldDialInfo->events_id = $events_id;
                $userGoldDialInfo->create($pdo_user);

                $pdo_user->commit();
            }catch(Exception $e)
            {
                if($pdo_user->inTransaction()){
                    $pdo_user->rollBack();
                }
                throw new PadException(RespCode::UNKNOWN_ERROR,"Failed to insert into UserGoldDialInfo!");
            }
            $pdo_user = null;
            $res['pgold']  = 0;      //玩家当天的累计充值
            $res['chance'] = 1;      //玩家转盘的机会次数
            $res['prizes'] = $prizes;
            $res['free'] = 1;
        }else{
            if(!$userGoldDialInfo_by_activity){
                //参加过往期活动，但是没有参加本期
                $userGoldDialInfo->user_id = $user_id;
                $userGoldDialInfo->events_id = $events_id;
                $userGoldDialInfo->prizelist = "";
                $userGoldDialInfo->pgold = 0;
                $userGoldDialInfo->chance = 1;
                $userGoldDialInfo->flag = 1;
                $userGoldDialInfo->update($pdo_user);
                $userGoldDialInfo = UserGoldDialInfo::updatePrizeItemsById($userGoldDialInfo->id,$user_id,$pdo_user);
            }else{
                //机会使用完，才能更新
                if(0 == $userGoldDialInfo->chance){
                    //活动期间四点刷新
                    $update_at =BaseModel::strToTime($userGoldDialInfo->updated_at);
                    $refresh_time =BaseModel::strToTime(date('Y-m-d').' 04:00:00');
                    $now = BaseModel::strToTime(date('Y-m-d H:i:s'));

                    if(($now >= $refresh_time)&&($update_at <= $refresh_time)){
                        $userGoldDialInfo = UserGoldDialInfo::updatePrizeItemsById($userGoldDialInfo->id,$user_id,$pdo_user);
                    }
                }
            }
            $tmpArr = json_decode($userGoldDialInfo->prizelist,true);
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

            $res['pgold'] = $userGoldDialInfo->pgold;
            $res['chance'] = $userGoldDialInfo->chance;
            $res['prizes'] = $prizes;
            $res['free'] = $userGoldDialInfo->flag;
        }

        $res['cutpoint'] = $goldPointForChance;
        $res['res'] = RespCode::SUCCESS;
        $res['gold_dial_total']  = $userGoldDialInfo->chance;  //魔法转盘的免费次数

        $pdo_user = null;
        return json_encode($res);
    }
}
