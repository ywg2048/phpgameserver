<?php
/**
 * 7. ガチャ.
 * ※ ver7.2で廃止予定
 */
class PlayGacha extends BaseAction {
  // http://pad.localhost/api.php?action=play_gacha&pid=1&sid=1&gtype=1
  public function action($params){
    // このAPIでコールできるのは友情ガチャと課金ガチャのみ.
    $resp_code = RespCode::FAILED_GACHA;
    $user_card_str = 0;
    $bm = 0;
    $grow = null;
    $user_id = $params["pid"];
    $gtype = $params["gtype"];
    if($gtype == Gacha::TYPE_EXTRA){
      $grow = $params["grow"];
    }
    if(isset($params["r"]) && $params["r"] >= 2){
      $bm = (int)$this->decode_params["bm"];
      $rk = (int)$this->decode_params["rk"];
      $bm = $bm - ($rk & 0xFF);
    }
    $single = 1;
    
    // #PADC#
    $token = Tencent_MsdkApi::checkToken($params);
    if(!$token){
    	return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
    }
    
    list($user_cards, $gold, $fripnt) = Gacha::play($user_id, $bm, $gtype, $single, $grow, $token);
    if(isset($user_cards[0])) {
      $resp_code = RespCode::SUCCESS;
      $user_card_str = GetUserCards::arrangeColumn($user_cards[0]);
    }

    //新手嘉年华：钻石扭蛋
    if($gtype != Gacha::TYPE_TUTORIAL){
      //不是友情扭蛋，或者是额外扭蛋但不是友情扭蛋
      if($gtype != Gacha::TYPE_FRIEND || ($gtype == Gacha::TYPE_EXTRA && $gtype != ExtraGacha::TYPE_FRIEND)){
          UserCarnivalInfo::carnivalMissionCheck($user_id,CarnivalPrize::CONDITION_TYPE_DAILY_GACHA_GOLD);
      }
    }

    return json_encode(array('res' => $resp_code, 'gold' => $gold, 'fripnt' => $fripnt, 'card' => $user_card_str));
  }

  /**
   * このAPIをストレステストする際のダミーデータを作成する.
   */
  public function createDummyDataForUser($user, $pdo) {
    // 合成用友情ポイント&ゴールド大量付与.
    $user->addFripnt(999999);
    $user->addGold(999999, $pdo);
    $user->update($pdo);
  }

}
