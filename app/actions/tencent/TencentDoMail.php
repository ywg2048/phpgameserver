<?php

/**
 * Tencent用：ユーザデータを検索
 */
class TencentDoMail extends TencentBaseAction {
    /**
     *
     * @see TencentBaseAction::action()
     */
    public function action($params) {
        $openid = $params ['OpenId'];
        $type = $params ['PlatId'];
        $title = $params ['MailTitle'];
        $content = $params ['MailContent'];


        $user_id = UserDevice::getUserIdFromUserOpenId($type, $openid);
        $user = User::find($user_id);
        if (empty ($user)) {
            throw new PadException (RespCode::USER_NOT_FOUND, 'User not found!');
        }

        UserMail::sendAdminMail($user_id, $content, UserMail::TYPE_ADMIN_MESSAGE, null, $title);

        return json_encode(array(
            'res' => 0,
            'msg' => 'success',
            'Result' => 0,
            'RetMsg' => 'success'
        ));
    }

}
