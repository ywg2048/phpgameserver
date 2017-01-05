<?php

/**
 * 45. メール送信
 */
class SendUserMail extends BaseAction {

    // http://pad.localhost/api.php?action=send_user_mail&pid=1&sid=1&to=2
    public function action($params) {
        $sender = $params['pid'];
        $recipient = $params['to'];
        $body = $params['body'];

        // 检查Ban信息
        $punish_info = UserBanMessage::getPunishInfo($params["pid"], User::PUNISH_SLIENCE);
        if($punish_info){
            return json_encode(array(
                'res' => RespCode::PLAY_BAN,
                'ban_msg' => $punish_info['msg'],
                'ban_end' => $punish_info['end']
            ));
        }

        // #PADC# ----------begin----------
        if ($word = NgWord::checkNGWords($body, NgWord::NGMAIL)) {
            return json_encode(array('res' => RespCode::NGWORD_ERROR, 'ngword' => $word));
        }
        // #PADC# ----------end----------
        UserMail::sendMail($sender, $recipient, $body);

        return json_encode(array('res' => RespCode::SUCCESS));
    }

}
