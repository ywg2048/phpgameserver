<?php
/**
 * 11. スタミナ購入.
 */
class BuyStamina extends BaseAction {
	// http://pad.localhost/api.php?action=buy_stamina&pid=2&sid=1
	public function action($params){
		$user_id = $params["pid"];
		$wmode = isset($params['m']) ? $params['m'] : 0;

		// #PADC# パラメータ追加 ----------begin----------
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		// #PADC# ----------end----------

		if($wmode == User::MODE_W){
			// パズドラWのスタミナ購入.
			$user = Shop::buyWStamina($user_id);
			$sta = $user->getWStamina();
		}else{
			// 本編のスタミナ購入.
			// #PADC# パラメータ追加
			$user = Shop::buyStamina($user_id, $token);
			$sta = $user->getStamina();

			//新手嘉年华：体力购买
			UserCarnivalInfo::carnivalMissionCheck($user_id,CarnivalPrize::CONDITION_TYPE_STAMINA_BUY);
		}
		$gold = $user->gold + $user->pgold;
		return json_encode(array('res' => RespCode::SUCCESS, 'gold' => $gold, 'sta' => $sta));
	}
}
