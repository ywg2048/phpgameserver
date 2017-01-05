<?php
/**
 * 46. メール受信
 */
class GetUserMail extends BaseAction {

	// http://pad.localhost/api.php?action=get_user_mail&pid=2&sid=1&msgid=1
	public function action($params){
		$rev = isset($params['r']) ? $params['r'] : 0;

		// #PADC# Tencentサーバーに無料魔法石を追加する為に、パラメータ追加 ----------begin----------
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		
		list($user, $mail, $item_offered, $result) = UserMail::getMail(
			$params['msgid'],
			$params['pid'],
			$token
		);
		// #PADC# ----------end----------

		$res = array();
		$res['res'] = RespCode::SUCCESS;

		$res['body'] = $mail->message;

		$admin_mails = array(
			UserMail::TYPE_ADMIN_BONUS,
			UserMail::TYPE_ADMIN_BONUS_TO_ALL,
			UserMail::TYPE_ADMIN_BONUS_NORMAL,
			UserMail::TYPE_ADMIN_BONUS_TO_ALL_NORMAL,
			UserMail::TYPE_ADMIN_BONUS_W,
			UserMail::TYPE_ADMIN_BONUS_TO_ALL_W,
		);
		if (in_array($mail->type, $admin_mails)) {
			$res['item_offered'] = $item_offered;
			$res['item'] = User::arrangeBonusResponse($result,$rev);
		}

		// #PADC# ----------begin----------
		if(isset($mail->title)){
			$res['title'] = $mail->title;
		}
		// #PADC# ----------end----------

		// #PADC#
		// ミッションクリア確認（図鑑登録数）
		list($res['ncm'], $res['clear_mission_list']) = UserMission::checkClearMissionTypes ( $params['pid'], array (
				Mission::CONDITION_TYPE_BOOK_COUNT,
		) );
		
		if(isset($res['item']['card'])){
			User::reportUserCardNum($params['pid'], $token['access_token']);
		}

		return json_encode($res);
	}

}
