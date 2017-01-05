<?php

/**
 * #PADC_DY#
 *
 *接受新手嘉年华的礼物
 */
class ReceiveCarnivalReward extends BaseAction
{
    public function action($params)
    {
        $rev     = isset($params['rev'])?$params['rev']:null;
        $user_id = $params['pid'];
        $carnival_id = $params['carnival_id'];

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

        $res = array();
        $res['carnival_id'] = $carnival_id;

        $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        try {
            $pdo_user->beginTransaction();

            $user_carnival_info = UserCarnivalInfo::findBy(array('user_id' => $user_id), $pdo_user);
            $reward_get = $user_carnival_info->reward_get;
            $mission_num = $user_carnival_info->mission_num;

            $user_carnival_mission = UserCarnivalMission::findBy(array('user_id' => $user_id, 'carnival_id' => $carnival_id), $pdo_user);
            if (UserCarnivalMission::STATUS_FINISHED != $user_carnival_mission->status) {
                throw new PadException(RespCode::UNKNOWN_ERROR, "No rewards can get!");
            }

            //最终目标奖励不在进度计算的范畴
            $tmp_carnival_prize = CarnivalPrize::get($user_carnival_mission->carnival_id);
            if(CarnivalPrize::MISSION_GROUP_ID_FOR_NONE != $tmp_carnival_prize->group_id){
                $reward_get += 1;
            }
            $user_carnival_info->reward_get = $reward_get;
            $res['progress'] = floor(($reward_get / $mission_num) * 100);

            if (CarnivalPrize::MISSION_GROUP_ID_FOR_NONE != $tmp_carnival_prize->group_id){
                //根据当前的任务完成进度，更新玩家全目标奖励任务的状态
                $prize_ids = CarnivalPrize::getCarnivalIdByGroupId(CarnivalPrize::MISSION_GROUP_ID_FOR_NONE);
                $extra_prizes = UserCarnivalMission::getMission($user_id,$prize_ids,UserCarnivalMission::STATUS_UNFINISHED);

                if(!empty($extra_prizes)){
                    foreach ($extra_prizes as $extra_prize) {
                        $carnival_prize = CarnivalPrize::get($extra_prize->carnival_id);
                        $condition = json_decode($carnival_prize->open_condition, true);
                        if ($res['progress'] >= $condition['progress']) {
                            $extra_prize->status = UserCarnivalMission::STATUS_FINISHED;
                            $extra_prize->update($pdo_user);

                            $res['box'] = array();
                            $res['box']['carnival_id'] = (int)$extra_prize->carnival_id;
                            $res['box']['status']      = (int)$extra_prize->status;
                            break;
                        }
                    }
                }
            }
            $user_carnival_mission->status = UserCarnivalMission::STATUS_GET_AWARD;
            $user_carnival_mission->update($pdo_user);
            $user_carnival_info->update($pdo_user);

            $carnival = CarnivalPrize::get($carnival_id);
            $user = User::find($user_id,$pdo_user);
            $before_gold = $user->gold;
            $before_pgold = $user->pgold;
            $fripnt_before = $user->fripnt;


            $items = array();
            $prizes = array();
            for($i=1;$i<=3;$i++){
                $bonus_id = 'bonus_id'.$i;
                if(!empty($carnival->$bonus_id)){
                    $amount   = 'amount'.$i;
                    $piece_id = 'piece_id'.$i;
                    $item = $user->applyBonus($carnival->$bonus_id,$carnival->$amount,$pdo_user, null, $token,$carnival->$piece_id);
                    $items[] = User::arrangeBonusResponse($item, $rev);

                    $after_mount = 0;
                    if($carnival->$bonus_id == BaseBonus::PIECE_ID){
                        $after_mount = $item['user_piece']->num;
                    }elseif($carnival->$bonus_id == BaseBonus::FRIEND_POINT_ID){
                        $after_mount = $item['fripnt'];
                    }elseif($carnival->$bonus_id == BaseBonus::MAGIC_STONE_ID){
                        $after_mount = $item['gold'];
                    }
                    $prizes[]= array('bonus_id'=>$carnival->$bonus_id,'amount'=>$carnival->$amount,'piece_id'=>$carnival->$piece_id,'after_amount'=>$after_mount);
                }
            }
            $res['items'] = $items;
            $user->update($pdo_user);

            $res['gold'] = $user->gold + $user->pgold;
            $pdo_user->commit();

            //TODO
            foreach($prizes as $prize){
                if($prize['bonus_id'] == BaseBonus::COIN_ID){
                    UserTlog::sendTlogMoneyFlow($user,$prize['amount'], Tencent_Tlog::REASON_CARNIVAL_MISSION_FINISHED, Tencent_Tlog::MONEY_TYPE_MONEY,0,0, 0,0);
                }elseif($prize['bonus_id'] == BaseBonus::MAGIC_STONE_ID){
                    UserTlog::sendTlogMoneyFlow($user,$prize['amount'], Tencent_Tlog::REASON_CARNIVAL_MISSION_FINISHED, Tencent_Tlog::MONEY_TYPE_DIAMOND,abs($user->gold - $before_gold),abs($user->pgold - $before_pgold), 0,0);
                }elseif($prize['bonus_id'] == BaseBonus::PIECE_ID){
                    UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE,$prize['piece_id'],$prize['amount'],$prize['after_amount'], Tencent_Tlog::REASON_CARNIVAL_MISSION_FINISHED,0, 0, Tencent_Tlog::MONEY_TYPE_NONE,0);
                }elseif($prize['bonus_id'] <= BaseBonus::MAX_CARD_ID){
                    UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_CARD,$prize['bonus_id'],$prize['amount'],$prize['after_amount'], Tencent_Tlog::REASON_CARNIVAL_MISSION_FINISHED,0, 0, Tencent_Tlog::MONEY_TYPE_NONE,0);
                }elseif($prize['bonus_id'] == BaseBonus::FRIEND_POINT_ID){
                    UserTlog::sendTlogMoneyFlow($user, $user->fripnt - $fripnt_before, Tencent_Tlog::REASON_CARNIVAL_MISSION_FINISHED, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT);
                }
            }

            $result = CarnivalPrize::getMissionDescription($carnival_id);
            UserTlog::sendTlogCarnivalReceivePrizeId($user,$carnival_id,$result['mtype'],$result['desc']);
        }catch(Exception $e){

            if($pdo_user->inTransaction()){
                $pdo_user->rollBack();
            }
            throw new PadException(RespCode::UNKNOWN_ERROR,$e->getMessage());
        }
        $count = UserCarnivalMission::missionFinishedCount($user_id);
        $res['carnival_chance_total'] = (int)$count;
        $res['res'] = RespCode::SUCCESS;

        return json_encode($res);
    }
}