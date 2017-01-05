<?php

/*
 * #PADC_DY#
 * 修改友情点
 */

class TencentDoUpdateFriends extends TencentBaseAction {

    public function action($get_params) {
        $platId = $get_params['PlatId'];
        $openId = $get_params['OpenId'];
        $value = $get_params['Value'];

        $userId = UserDevice::getUserIdFromUserOpenId($platId, $openId);
        $pdo = Env::getDbConnectionForUserWrite($userId);
        // get user object
        $user = User::find($userId);
        if ($user === false) {
            throw new PadException(RespCode::USER_NOT_FOUND, 'user not find');
        }
        $beginValue = $user->fripnt;
        $user->fripnt = $user->fripnt + $value;
        if ($user->fripnt < 0) {
            $user->fripnt = 0;
        }
        $endValue = $user->fripnt;
        if ($beginValue != $endValue) {
            $user->update($pdo);
        }

        //TLOG friend point
        UserTlog::sendTlogMoneyFlow($user, $beginValue - $endValue, Tencent_Tlog::REASON_IDIP, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT);

        $result = array(
            'res' => 0,
            'msg' => 'success',
            'Result' => 0,
            'RetMsg' => 'success'
        );
        // return a json formatted data
        return json_encode($result);
    }

}
