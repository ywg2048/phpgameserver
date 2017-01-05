<?php

/**
 * Class UserCarnivalInfo
 *
 * 玩家新手嘉年华的信息
 */
class UserCarnivalInfo extends BaseModel{
    const  TABLE_NAME = 'user_carnival_info';

    const CARNIVAL_LAST_DAYS = 7;   //嘉年华的活动时间

    protected static  $columns = array(
        'id',
        'user_id',
        'buy_stamina_num',
        'mission_num',          //总的任务数目
        'reward_get',           //已领取的奖励数目
        'ultimate_reward',      //全目标奖励的标志：0--未领取 1--已领取
        'is_first_login',       //第一次登录游戏
        'last_login',           //最后一次登录的时间
        'start_at',             //注册时间
        'end_at',               //嘉年华结束的时间
        'created_at',
        'updated_at',
    );

    /**
     * 检查用户是否可以参与嘉年华，如果用户参与嘉年华的时间已过期，删除相关的表信息
     * @param $user_id
     * @return bool
     * @throws PadException
     */
    public static function isCarnivalOpenForUser($user_id,$pdo_user){

        $user_carnival_info = UserCarnivalInfo::findBy(array('user_id'=>$user_id),$pdo_user);
        if(empty($user_carnival_info)){
            return false;
        }

        $start_at = BaseModel::strToTime($user_carnival_info->start_at);
        $end_at   = BaseModel::strToTime($user_carnival_info->end_at);
        $now      = time();
        if($start_at<=$now && $now<$end_at){
            return true;
        }else{
            //嘉年华已过期，用户的信息可以删除
            $sql = "DELETE FROM ".Static::TABLE_NAME." WHERE user_id = ".$user_id;
            $stmt = $pdo_user->prepare($sql);
            $stmt->execute();

            $sql = "DELETE FROM ".UserCarnivalMission::TABLE_NAME." WHERE user_id =".$user_id;
            $stmt= $pdo_user->prepare($sql);
            $stmt->execute();

            return false;
        }
    }

    /**
     * 用户注册的时候，初始化UserCarnivalInfo表，将七日的所有任务插入UserCarnivalMission表
     * @param $user_id
     * @param null $pdo_user
     * @return bool
     * @throws PadException
     */
    public static function initialUserCarnivalInfo($user_id,$pdo_user=null){
        if(null == $pdo_user){
            $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        }

        $result = UserCarnivalInfo::findBy(array('user_id'=>$user_id));
        if(!empty($result)){
            throw new PadException(RespCode::UNKNOWN_ERROR,"UserCarnvalInfo[$user_id] has founded!");
        }

        $carnival_prizes = CarnivalPrize::getAll();
        try{
            $pdo_user->beginTransaction();

            $user_carnival_info = new UserCarnivalInfo();
            $user_carnival_info->user_id         = $user_id;
            $user_carnival_info->buy_stamina_num = 0;
            $user_carnival_info->reward_get      = 0;
            $user_carnival_info->ultimate_reward = 0;
            //嘉年华以每天凌晨4：00为基准
            $start_at =BaseModel::strToTime(date('Y-m-d').' 04:00:00');
            if($start_at >= time()){
                $start_at -= 3600*24;
                $user_carnival_info->start_at    = BaseModel::timeToStr($start_at);
            }else{
                $user_carnival_info->start_at    = BaseModel::timeToStr($start_at);
            }
            $user_carnival_info->end_at          = BaseModel::timeToStr($start_at+24*3600*self::CARNIVAL_LAST_DAYS);
            $user_carnival_info->is_first_login  = 0;
            $user_carnival_info->last_login      = BaseModel::timeToStr(time());
            $mission_num = 0;

            foreach($carnival_prizes as $carnival_prize){
                if(CarnivalPrize::CONDITION_TYPE_WHOLE_TARGET_PRIZE != $carnival_prize->condition_type){
                    $mission_num += 1;
                }
            }
            $user_carnival_info->mission_num     = $mission_num;
            $user_carnival_info->create($pdo_user);

            foreach($carnival_prizes as $carnival_prize){
                    $user_carnival_mission = new UserCarnivalMission();
                    $user_carnival_mission->user_id       = $user_id;
                    $user_carnival_mission->carnival_id   = $carnival_prize->id;
                    $user_carnival_mission->status        = UserCarnivalMission::STATUS_UNFINISHED;
                    $user_carnival_mission->create($pdo_user);
            }

            $pdo_user->commit();
        }catch(Exception $e){

            global $logger;
            $logger->log($e->getMessage(),Zend_Log::DEBUG);

            if($pdo_user->inTransaction()){
                $pdo_user->rollBack();
            }
            throw  new PadException(RespCode::UNKNOWN_ERROR,$e->getMessage());
        }

        return true;
    }

    /**
     * 判断用户的嘉年华任务是否完成
     * @param $user_id
     * @param $condition_type
     * @param $param
     * @throws
     */
    public static function carnivalMissionCheck($user_id,$condition_type,$param=null){


        $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        try{
            $pdo_user->beginTransaction();

            $isOpen = self::isCarnivalOpenForUser($user_id,$pdo_user);
            if(true == $isOpen){
                $carnival_ids = CarnivalPrize::getIdsByConditionType($condition_type);

                //取出类型为$condition_type,且状态为未完成的任务
                $user_carnival_missions = array();
                $missions = UserCarnivalMission::findAllBy(array('user_id'=>$user_id,'status'=>UserCarnivalMission::STATUS_UNFINISHED),null,null,$pdo_user);
                foreach($missions as $mission){
                    if(in_array($mission->carnival_id,$carnival_ids)){
                        $user_carnival_missions[] = $mission;
                    }
                }

                if(empty($user_carnival_missions)){
                    return;
                }

                switch($condition_type){
                    case CarnivalPrize::CONDITION_TYPE_DAILY_LOGIN:
                        $user_carnival_info    = UserCarnivalInfo::findBy(array('user_id'=>$user_id),$pdo_user);
                        $login_day =floor((time() - BaseModel::strToTime($user_carnival_info->start_at))/3600/24);    //新手嘉年华的累积登录天数

                        //更新新手嘉年华的每日登录任务
                        //如果不是同一天登录或者首次登录游戏，更新玩家两张新手嘉年华的表：UserCarnivalMission,UserCarnivalInfo
                        if(date('Y-m-d',time())!= substr($user_carnival_info->last_login,0,10)

                            || 0 == $user_carnival_info->is_first_login){

                            if(0 == $user_carnival_info->is_first_login)
                            {
                                $user_carnival_info->is_first_login = 1;
                            }
                            $login_day += 1;
                            foreach($user_carnival_missions as $ucm) {
                                $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                                $condition = json_decode($carnival_prize->open_condition,true);
                                if($login_day>=$condition['login_day']){
                                    $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                    $ucm->update($pdo_user);
                                }
                            }
                        }
                        $user_carnival_info->last_login = date("Y-m-d H:i:s");
                        $user_carnival_info->update($pdo_user);

                        break;
                    case CarnivalPrize::CONDITION_TYPE_USER_RANK:
                        foreach($user_carnival_missions as $ucm) {
                            $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                            $condition = json_decode($carnival_prize->open_condition,true);
                            if($param >=$condition['lv']){
                                $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                $ucm->update($pdo_user);
                            }
                        }
                        break;
                    case CarnivalPrize::CONDITION_TYPE_CARD_COMPOSITE:
                        foreach($user_carnival_missions as $ucm) {
                            $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                            $condition = json_decode($carnival_prize->open_condition,true);
                            if($param >=$condition['composite']){
                                $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                $ucm->update($pdo_user);
                            }
                        }
                        break;
                    case CarnivalPrize::CONDITION_TYPE_CARD_EVOLVE:
                        foreach($user_carnival_missions as $ucm) {
                            $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                            $condition = json_decode($carnival_prize->open_condition,true);
                            if($param >=$condition['evolve']){
                                $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                $ucm->update($pdo_user);
                            }
                        }
                        break;
                    case CarnivalPrize::CONDITION_TYPE_FRIEND_NUMBER:
                        $user = User::find($user_id);
                        foreach($user_carnival_missions as $ucm) {
                            $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                            $condition = json_decode($carnival_prize->open_condition,true);

                            if($user->fricnt >= $condition['friend']){
                                $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                $ucm->update($pdo_user);
                            }
                        }
                        break;
                    case CarnivalPrize::CONDITION_TYPE_DUNGEON_CLEAR:
                        foreach($user_carnival_missions as $ucm) {
                            $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                            $condition = json_decode($carnival_prize->open_condition,true);
                            if($param == $condition['dungeon']){
                                $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                $ucm->update($pdo_user);
                                break;
                            }
                        }
                        break;
                    case CarnivalPrize::CONDITION_TYPE_STAMINA_BUY:
                        $user_carnival_info = UserCarnivalInfo::findBy(array('user_id'=>$user_id),$pdo_user);
                        $user_carnival_info->buy_stamina_num += 1;
                        foreach($user_carnival_missions as $ucm) {
                            $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                            $condition = json_decode($carnival_prize->open_condition,true);
                            if($user_carnival_info->buy_stamina_num >=$condition['stamina']){
                                $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                $ucm->update($pdo_user);
                            }
                        }
                        $user_carnival_info->update($pdo_user);
                        break;
                    case CarnivalPrize::CONDITION_TYPE_DAILY_GACHA_GOLD:
                        $user_count = UserCount::findBy(array("user_id"=>$user_id));
                        $gacha_gold = $user_count->gacha_gold;
                        foreach($user_carnival_missions as $ucm) {
                            $carnival_prize = CarnivalPrize::get($ucm->carnival_id);
                            $condition = json_decode($carnival_prize->open_condition,true);
                            if($gacha_gold>=$condition['gacha']){
                                $ucm->status = UserCarnivalMission::STATUS_FINISHED;
                                $ucm->update($pdo_user);
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
            $pdo_user->commit();
        }catch(Exception $e){

            if($pdo_user->inTransaction()){
                $pdo_user->rollBack();
            }
            throw new PadException(RespCode::CARNIVAL_MISSION_CHECK,$e->getMessage());
        }
    }
    
}