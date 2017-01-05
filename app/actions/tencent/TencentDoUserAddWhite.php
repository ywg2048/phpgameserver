<?php

/**
 * 将某帐号增加至停服维护白名单请求-
 */
class TencentDoUserAddWhite extends TencentBaseAction {

    public function action($params) {
        if (isset ($params ['OpenId']) && isset ($params ['PlatId'])) {
            $openId = $params ['OpenId'];
            $type = $params ['PlatId'];
            $user_id = UserDevice::getUserIdFromUserOpenId($type, $openId);

            $user = User::find($user_id);
            if (empty($user)) {
                throw new PadException(RespCode::USER_NOT_FOUND, 'user not find');
            }
        } else {
            throw new PadException (static::ERR_INVALID_REQ, 'Invalid request!');
        }

        $pdo = Env::getDbConnectionForShare();
        try {
            $pdo->beginTransaction();

            $debug_user = DebugUser::findBy(array('open_id' => $openId ,'user_id'=>$user_id), $pdo );
            if(false == $debug_user){
                $debug_user = new DebugUser();
                $debug_user->open_id = $openId;
                $debug_user->user_id = $user_id;
                $debug_user->maintenance_flag = 1;
                $debug_user->cheatcheck_flag = 0;
                $debug_user->dropchange_flag = 0;
                $debug_user->drop_round_prob = 0;
                $debug_user->drop_plus_prob = 0;
                $debug_user->skillup_change_flag = 0;
                $debug_user->skillup_change_prob = 0;
                $debug_user->create($pdo);

                Padc_Log_Log::debugToolEditDBLog( "TencentDoUserAddWhite " . BaseModel::timeToStr(time()) . " " . json_encode($debug_user) );
            }else{
                $b_data = clone $debug_user;
                $debug_user->user_id = $user_id;
                $debug_user->maintenance_flag = 1;
                $debug_user->update($pdo);

                Padc_Log_Log::debugToolEditDBLog( "TencentDoUserAddWhite " . BaseModel::timeToStr(time()) . " " . json_encode($b_data) . " => " . json_encode($debug_user) );
            }

            $pdo->commit ();
        } catch ( Exception $e ) {
            if ($pdo->inTransaction ()) {
                $pdo->rollback ();
            }
            throw $e;
        }
        $result = array_merge(array(
            'res' => 0,
            'msg' => 'OK',
            'Result' => 0,
            'RetMsg' => 'success'
        ));

        return json_encode($result);
    }
}
