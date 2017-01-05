<?php

/**
 * #PADC_DY#
 * 获取用户参与切当前有效的活动记录
 */
class GetUserActivities extends BaseAction {

    // http://pad.localhost/api.php?action=get_user_activities&pid=2
    public function action($params) {
        $user_id = $params["pid"];

        $res = array(
            'res' => RespCode::SUCCESS
        );
        $user = User::find($user_id);
        $res['use_money'] = (int) $user->tp_gold_period / GameConstant::GOLD_TO_MONEY_RATE;
        $res['use_stone'] = (int) $user->tc_gold_period;
        $res['recharge_day'] = (int) $user->count_p6;

        // 统计类活动用
        $uac = UserActivityCount::getUserActivityCount($user->id);
        $res['coin_total'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_COIN_CONSUM)) ? 0 : $uac->coin_consum;
        $res['stamina_buy'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_STA_BUY_COUNT)) ? 0 : $uac->sta_buy_count;
        $res['gacha_cnt'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_GACHA_COUNT)) ? 0 : $uac->gacha_count;
        $res['card_evolve'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_CARD_EVO_COUNT)) ? 0 : $uac->card_evo_count;
        $res['skill_awake'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_SKILL_AWAKE_COUNT)) ? 0 : $uac->skill_awake_count;

        $pdo = Env::getDbConnectionForUserRead($params['pid']);
        
        $user_activities = UserActivity::getActiveList($user_id, $user);
        foreach ($user_activities as $user_activity) {
            if($user_activity->status != 3){
                $activity = Activity::get((int)$user_activity->activity_id);
                list($cost_type, $cost_piece_id, $cost_amount) = $activity->getExchangeItem();
                //判断兑换物品数量够不够
                if ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_PIECE) {
                    // 获得用户用于兑换bonus物品的碎片（货币碎片）
                    $cost_piece = UserPiece::getUserPiece($user->id, $cost_piece_id, $pdo);
                    // 用户的货币碎片数量不足
                    if (!$cost_piece) {
                        $user_activity->status = 1;
                    }
                    if ($cost_piece->num < $cost_amount) {
                        $user_activity->status = 1;
                    }else{
                        $user_activity->status = 2;
                    }
                    
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_COIN) {
                    if ($user->coin < $cost_amount) {
                        $user_activity->status = 1;
                    }else{
                        $user_activity->status = 2;
                    }
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_GOLD) {
                    if(!$user->checkHavingGold($cost_amount)){
                        $user_activity->status = 1;
                    }else{
                        $user_activity->status = 2;
                    }
                }    
            }
            

            $res['activity'][] = array(
                'activity_id' => (int) $user_activity->activity_id,
                'status' => $user_activity->status,
                'ordered_at' => $user_activity->ordered_at,
                'counts' => $user_activity->counts
            );
        }

        return json_encode($res);
    }

}
