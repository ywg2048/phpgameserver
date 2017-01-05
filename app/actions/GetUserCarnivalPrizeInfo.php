<?php

/**
 * #PADC_DY#
 *
 * 获取新手嘉年华的信息
 */
class GetUserCarnivalPrizeInfo extends BaseAction
{
    public function action($params)
    {
        $user_id = $params['pid'];

        $res = array();

        $pdo_user = Env::getDbConnectionForUserRead($user_id);
        $user_carnival_missions = UserCarnivalMission::findAllBy(array("user_id"=>$user_id),"carnival_id asc",null,$pdo_user);
        if(empty($user_carnival_missions)){
            return json_encode(array('res'=>RespCode::SUCCESS,'box'=>array()
                            ,'missions'=>array()
                            ,'progress'=>null));
        }

        $molecule = 0;
        $denominator = 0;

        $tmp = array();
        $finished = 0;
        foreach($user_carnival_missions as $user_carnival_mission){
            if(UserCarnivalMission::STATUS_FINISHED == $user_carnival_mission->status){
                $finished += 1;
            }

            $carnival_prize = CarnivalPrize::get($user_carnival_mission->carnival_id);
            if(CarnivalPrize::MISSION_GROUP_ID_FOR_NONE == $carnival_prize->group_id){
                $res['box'][] =array(
                    'carnival_id'  =>$user_carnival_mission->carnival_id,
                    'status'       =>$user_carnival_mission->status,
                );
            }else{
                //非全目标的奖励的group_id，只能大于等于1.
                $tmp[$carnival_prize->group_id-1][] = array(
                    'carnival_id'=>$user_carnival_mission->carnival_id,
                    'status'     =>$user_carnival_mission->status,
                );
                if(UserCarnivalMission::STATUS_GET_AWARD  == $user_carnival_mission->status){
                    $molecule += 1;
                }
                $denominator += 1;
            }
        }

        $progress =floor(($molecule/$denominator)*100);

        $res['m'] = $molecule;
        $res['d'] = $denominator;
        $res['missions'] = array_values($tmp);
        $res['progress'] = $progress;
        $res['carnival_chance_total'] = $finished;
        $res['res']   = RespCode::SUCCESS;

        return json_encode($res);
    }
}