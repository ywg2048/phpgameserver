<?php
class UserGoldDialInfo extends BaseModel {
    const TABLE_NAME = "user_gold_dial_info";

    protected static $columns = array(
        'id',
        'events_id',
        'user_id',
        'prizelist',          //玩家转盘的奖品列表：奖品的ID、是否得到奖品的标志、奖品的得到概率
        'pgold',              //玩家当天的累计充值魔法石数量
        'chance',             //玩家的当前抽奖机会
        'flag',               //flag=1:免费一次的机会未用，flag=0：免费机会已用
        'created_at',
        'updated_at',
    );

    //更新玩家的转盘的状态
    public static function updatePrizeItemsById($id,$user_id,$pdo = null){

        if(null == $pdo){
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }
        $pdo->beginTransaction();
        $info = null;
        try{
            $prizeItems = DialPrize::getPrizeItems(DialPrize::DIAL_TYPE_GOLD);
            $probForGoldDialPosition = GameConstant::getParam('ProbForGoldDialPosition');

            $i = 0;
            $userPrizeItemList = null;
            foreach($prizeItems as $prizeItem){
                $userPrizeItemList[] = array(
                    $prizeItem->id,
                    0,                                   //是否已经抽取到
                    $probForGoldDialPosition[$i++],     //抽奖得到的概率
                );
            }
            $prizelist = json_encode($userPrizeItemList);

            $info = UserGoldDialInfo::find($id,$pdo,true);
            $info->prizelist = $prizelist;
            $info->pgold = 0;
            $info->updated_at = BaseModel::timeToStr(time());
            $info->update($pdo);

            $pdo->commit();
        }catch(Exception $e){
            if($pdo->inTransaction()){
                $pdo->rollBack();
            }
            throw new PadException(RespCode::UNKNOWN_ERROR,$e->getMessage());
        }


        return $info;
    }

    //玩家充值魔法石，可能获得魔法转盘的转盘机会
    public  static function updateChance($user_id,$addGold){

        $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        if(null == $pdo_user){
            throw new PadException(RespCode::UNKNOWN_ERROR,"Failed to get the pdo resource!");
        }


        //判断是否在活动期间
        $event_id = LimitEventsTime::getTypeById(LimitEventsTime::MAGIC_STONE_DIAL);
        if(!$event_id){
            return;
        }
        $limit_time = LimitEventsTime::find($event_id);             //取得魔法转盘的开放时间
        $beginTime = BaseModel::strToTime($limit_time->begin_at);
        $endTime   = BaseModel::strToTime($limit_time->finish_at);
        $now       = BaseModel::strToTime(date('Y-m-d H:i:s'));
        $isEnable  = false;
        if($beginTime<$now && $now<$endTime){
            $isEnable = true;
        }else{
            //活动不开放
            return;
        }

        try{
            $pdo_user->beginTransaction();
            $userGoldDialInfo = UserGoldDialInfo::findBy(array('user_id'=>$user_id),$pdo_user);

            //另一期活动或者首次参与活动，取出奖品
            if(null == $userGoldDialInfo || $event_id != $userGoldDialInfo->events_id){
                $probForGoldDialPosition = GameConstant::getParam('ProbForGoldDialPosition');
                //首次转盘的物品，所有玩家都是一样的
                $prizeItems = DialPrize::getFirstPrizeItemsForGoldDial();
                $i = 0;
                foreach($prizeItems as $prizeItem){
                    $prizes[] = array(
                        'id'        => $prizeItem->id,
                        'bonus_id'  => $prizeItem->bonus_id,
                        'amount'    => $prizeItem->amount,
                        'piece_id'  => $prizeItem->piece_id,
                        'isacquired'=> 0,
                    );

                    $userPrizeItemList[] = array(
                        $prizeItem->id,
                        0,                                   //是否已经抽取到
                        $probForGoldDialPosition[$i++],     //抽奖得到的概率
                    );
                }
                $prizelist = json_encode($userPrizeItemList);

                //如果用户不存在的情况
                if(null == $userGoldDialInfo ){
                    $userGoldDialInfo = new UserGoldDialInfo();
                    $userGoldDialInfo->user_id = $user_id;
                    $userGoldDialInfo->events_id = LimitEventsTime::getTypeById(LimitEventsTime::MAGIC_STONE_DIAL);
                    $userGoldDialInfo->prizelist = $prizelist;
                    $userGoldDialInfo->pgold = 0;
                    $userGoldDialInfo->chance = 1;
                    $userGoldDialInfo->flag = 1;
                    $userGoldDialInfo->create($pdo_user);
                }
                //另一期活动的情况
                if($event_id != $userGoldDialInfo->events_id){
                    $userGoldDialInfo->prizelist = $prizelist;
                    $userGoldDialInfo->pgold = 0;
                    $userGoldDialInfo->chance = 1;
                    $userGoldDialInfo->flag = 1;   //flag=1:玩家的首次免费还未使用；flag=0,首次免费已使用
                }
            }

            $before_pgold = $userGoldDialInfo->pgold;
            $after_gold = 0;
            if($isEnable && $addGold>0){
                $after_gold = $before_pgold+$addGold;
                $userGoldDialInfo->pgold += $addGold;
            }
            $getChance = self::getGoldLevel($after_gold)-self::getGoldLevel($before_pgold);

            $userGoldDialInfo->chance += $getChance;
            $userGoldDialInfo->created_at = BaseModel::timeToStr(time());
            $userGoldDialInfo->updated_at = BaseModel::timeToStr(time());
            $userGoldDialInfo->update($pdo_user);
            $pdo_user->commit();
        }catch(Exception $e){
            if($pdo_user->inTransaction()){
                $pdo_user->rollBack();
            }
            throw new PadException(RespCode::UNKNOWN_ERROR,$e->getMessage());
        }
        $pdo_user = null;

        return $getChance;
    }

    //根据$gold值，计算有几次抽奖机会
    public static function getGoldLevel($gold){
        $goldPointForChance = GameConstant::getParam('GoldPointForChance');

        $i = 0;
        krsort($goldPointForChance);
        foreach($goldPointForChance as $goldPoint){
            if($gold >= $goldPoint){
                return count($goldPointForChance)-$i;
            }
            $i++;
        }
    }
}
