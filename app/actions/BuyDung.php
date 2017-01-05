<?php
/**
 * ダンジョン購入.
 */
class BuyDung extends BaseAction {
  // http://pad.localhost/api.php?action=buy_dung&pid=2&did=1&sid=1
  public function action($params){
    $user_id = $params["pid"];
    $dungeon_id = $params["did"];
    
    // #PADC# パラメータ追加 ----------begin----------
    $token = Tencent_MsdkApi::checkToken($params);
    if(!$token){
    	return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
    }
    list($gold, $expire_at) = UserBuyDungeon::buy($user_id, $dungeon_id,$token);
	return json_encode(array('res' => RespCode::SUCCESS, 'tlm' => $expire_at, 'gold' => $gold));
	// #PADC# ----------end----------
  }

}


