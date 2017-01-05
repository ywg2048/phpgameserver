<?php
/**
 * 3. カードリスト取得
 */
class GetUserCards extends BaseAction {
  // http://pad.localhost/api.php?action=get_user_cards&pid=1&sid=1
  public function action($params){
    $rev = isset($params["r"]) ? $params["r"] : 0;
    if(isset($params["pid"])) {
      return json_encode(array('res' => RespCode::SUCCESS, 'cards' => GetUserCards::getAllUserCards($params["pid"], $rev)));
    } else {
      return json_encode(array('res' => RespCode::UNKNOWN_ERROR, 'cards' => array()));
    }
  }

  /**
   * ユーザが所有するカードのリストを返す.
   * API 33. データ取得からも呼ばれる.
   */
  public static function getAllUserCards($user_id, $rev = 0) {
    $pdo = Env::getDbConnectionForUserWrite($user_id);
    $user_cards = UserCard::findAllBy(array("user_id" => $user_id), "cuid ASC", null, $pdo);
    return static::arrangeColumns($user_cards, $rev);
  }

  public static function getOneUserCard($user_id, $cuid, $rev = 0) {
    $pdo = Env::getDbConnectionForUserWrite($user_id);
    $user_cards = UserCard::findAllBy(array("user_id" => $user_id, "cuid" => $cuid), null, null, $pdo);
    $cards = static::arrangeColumns($user_cards, $rev);
    return array_shift($cards);
  }

  /**
   * ユーザが保有しているカードをクライアントが要求するフォーマットに変換する.
   */
  public static function arrangeColumn($user_card, $rev = 0) {
    $arr = array();
    if ($rev >= 720) {
      $arr[] = (int)$user_card->cuid;
      $arr[] = (int)$user_card->exp;
      $arr[] = (int)$user_card->lv;
      $arr[] = (int)$user_card->slv;
      $arr[] = (int)$user_card->mcnt;
      $arr[] = (int)$user_card->card_id;
      $arr[] = (int)$user_card->equip1;
      $arr[] = (int)$user_card->equip2;
      $arr[] = (int)$user_card->equip3;
      $arr[] = (int)$user_card->equip4;
      // #PADC#
      $arr[] = (int)$user_card->eexp;
    } else {
      $arr['cuid'] = (int)$user_card->cuid;
      $arr['exp'] = (int)$user_card->exp;
      $arr['lv'] = (int)$user_card->lv;
      $arr['slv'] = (int)$user_card->slv;
      $arr['mcnt'] = (int)$user_card->mcnt;
      $arr['no'] = (int)$user_card->card_id;
      $arr['plus'] = array(
        (int)$user_card->equip1,
        (int)$user_card->equip2,
        (int)$user_card->equip3,
        (int)$user_card->equip4,
      );
      // #PADC#
      $arr['eexp'] = (int)$user_card->eexp;
      $arr['ps'] = isset($user_card->ps) ? json_decode($user_card->ps, TRUE) : null; // 觉醒技能
    }
    return $arr;
  }

  /**
   * ユーザが保有しているカードをクライアントが要求するフォーマットに変換する.
   */
  public static function arrangeColumns($user_cards, $rev = 0) {
    $mapper = array();
    foreach($user_cards as $user_card) {
      $mapper[] = static::arrangeColumn($user_card, $rev);
    }
    return $mapper;
  }

}
