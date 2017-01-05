<?php
/**
 * 24. カードをまとめて売却.
 */
class SellUserCards extends BaseAction {
  // http://pad.localhost/api.php?action=sell_user_cards&pid=1&sid=1&cuids=1,2,3
  public function action($params){
    $pid = $params["pid"];
//    $cuids = explode(",", $this->post_params['cuids']);
    $cuids = explode(",", $params['cuids']);
    $have_card_cnt = (isset($params["c"]) ? $params["c"] : 0); // 所持しているカード枚数（売却前）.
    $revision = (isset($params["r"])) ? (int)$params["r"] : 0;
    $res_cards_flg = false;
    if($revision < 2){
      $res_cards_flg = true;
    }else{
      $pdo = Env::getDbConnectionForUserWrite($params["pid"]);
      $before_card_count = UserCard::countAllBy(array('user_id' => $pid), $pdo);
      if($have_card_cnt != $before_card_count){
        $res_cards_flg = true;
      }
    }
    $price = UserCard::sells($pid, $cuids);
    if($price == 0) {
      $res = array(
        'res' => RespCode::SUCCESS,
        'sold' => $price,
      );
      if($res_cards_flg){
        // 売却前の枚数がサーバと合わない場合はカードを全部返す(r=2以降).
        $res['cards'] = GetUserCards::getAllUserCards($pid);

        // #PADC_DY# 用户当前宠物点数
        $user = User::find($pid);
        $res['exchange_point'] = (int)$user->exchange_point;
      }
      return json_encode($res);
    } else {
      return json_encode(array(
        'res' => RespCode::FAILED_SELLING_USER_CARD,
        'sold' => 0
      ));
    }
  }

}
