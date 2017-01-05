<?php

/**
 * 发言清除接口
 */
class TencentDoClearSpeak extends TencentBaseAction {

    public function action($params) {
        if (isset ($params ['OpenId']) && isset ($params ['PlatId'])) {
            $openid = $params ['OpenId'];
            $type = $params ['PlatId'];
            $user_id = UserDevice::getUserIdFromUserOpenId($type, $openid);
            $user = User::find($user_id);
            if (empty($user)) {
                throw new PadException(RespCode::USER_NOT_FOUND, 'user not find');
            }
        } else {
            throw new PadException (static::ERR_INVALID_REQ, 'Invalid request!');
        }

        $friend_ids = Friend::getFriendids($user_id);

        foreach ($friend_ids as $receiver_id) {
            $this->deleteUserMail($receiver_id, $user_id);
        }

        $result = array_merge(array(
            'res' => 0,
            'msg' => 'OK',
            'Result' => 0,
            'RetMsg' => 'success'
        ));

        return json_encode($result);
    }

    private function deleteUserMail($receiver_id, $sender_id) {
        UserMail::clearSpeak($receiver_id, $sender_id);
    }
}
