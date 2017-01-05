<?php

/**
 * #PADC_DY#
 * 获取用户转盘、魔法转盘、新手嘉年华是否可参与状态
 */
class GetUserNewActivityStatus extends BaseAction {

    public function action($params) {
        $user_id = $params["pid"];

        $res = array();

        $pdo = Env::getDbConnectionForUserRead($user_id);

        //转盘：幸运转盘、魔法转盘
        $user_dial_info = UserDialInfo::findBy(array('user_id'=>$user_id),$pdo);
        if(!$user_dial_info){
            $t1 = 0;
            $t2 = 0;
        }else{
            $t1 = $user_dial_info->flag1 == 0?1:0;       //首次转盘免费
            $t2 = $user_dial_info->flag2 == 0?1:0;       //首次重置转盘免费

        }
        $res['dial_lucky_total'] = $t1 + $t2;

        $user_gold_dial_info = UserGoldDialInfo::findBy(array('user_id'=>$user_id),$pdo);
        if(!$user_gold_dial_info){
            $gold_dial_chance = 0;
        }else{
            $gold_dial_chance = $user_gold_dial_info->chance;
        }
        $res['gold_dial_total']  = $gold_dial_chance;

        //新手嘉年华
        $carnival_mission_finished = UserCarnivalMission::findAllBy(array('user_id'=>$user_id,'status'=>UserCarnivalMission::STATUS_FINISHED),null,null,$pdo);
        if(!$carnival_mission_finished){
            $carnival_mission_count = 0;
        }else{
            $carnival_mission_count = count($carnival_mission_finished);
        }
        $res['carnival_chance_total'] = $carnival_mission_count;

        $res['res'] = RespCode::SUCCESS;

        return json_encode($res);
    }
}
