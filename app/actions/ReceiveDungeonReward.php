<?php

/**
 * #PADC_DY#
 * 领取三星奖励
 */
class ReceiveDungeonReward extends BaseAction {

    // http://pad.localhost/api.php?action=receive_dungeon_reward&pid=1&sid=1&dung=1&floor=1&step=1
    public function action($params) {
        $user_id = (isset($params["pid"]) ? (int) $params["pid"] : NULL);
        $dungeon_id = (isset($params["dung"]) ? (int) $params["dung"] : NULL);
        $step = (isset($params["step"]) ? (int) $params["step"] : NULL);

        if (empty($user_id) || empty($dungeon_id) || empty($step)) {
            throw new PadException(RespCode::INVALID_PARAMS, 'Invalid params!');
        } else {
            $token = Tencent_MsdkApi::checkToken($params);
            if (!$token) {
                return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
            }

            $data = UserDungeon::reward($user_id, $dungeon_id, $step, $token);
            
            if($data) {
                $res = array(
                    'res' => RespCode::SUCCESS,
                    'data' => $data
                );
                
                return json_encode($res);
            }

            throw new PadException(RespCode::UNKNOWN_ERROR, 'Data Error!');
        }
    }

}
