<?php
/**
 * 14. 所持カード数拡張
 */
class ExpandNumCards extends BaseAction {
	
  // http://pad.localhost/api.php?action=expand_num_cards&pid=2&sid=1
  public function action($params){
    $user_id = $params["pid"];
    $cur = (isset($params["cur"])) ? $params["cur"] : null;
 
    // #PADC#
    $token = Tencent_MsdkApi::checkToken($params);
    if(!$token){
    	return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
    }
    
    $user = Shop::buyCardSlot($user_id, $cur,$token);
    // #PADC# ----------end----------
    
    return json_encode(array('res' => RespCode::SUCCESS, 'cmax'=>$user->card_max, 'gold' => ($user->gold+$user->pgold)));
  }
  
}
