<?php
/**
 * 52. クライアントからカード付与
 */
class Collaboration extends BaseAction {

  // http://pad.localhost/api.php?action=collaboration&pid=2&sid=1&type=0
  public function action($params){
    //TODO:コラボ種類に対応したカードデータがないのでエラーになるが、存在するカードデータIDならば問題なく付与された
    $resp_code = RespCode::FAILED_ADD_CARD;
    $user_cards = array();
    $item_offered = 0;	// 付与済みか？のフラグ
    $msg = "";
    try{
      $pdo = Env::getDbConnectionForUserWrite($params["pid"]);
      $pdo->beginTransaction();
      $user_id = $params['pid'];
      $type = $params['type'];
      $user_collaboration = UserCollaboration::findBy(array('user_id' => $user_id, 'type' => $type));
      // type=0:どんちゃん(太鼓の達人コラボ)
      // type=1:チョコボ(FFコラボ)
      // type=2:ケリ姫(ケリ姫コラボ)
      // type=3:どんちゃん(太鼓の達人コラボ)2回目
      if($params['type'] == 1 ){
//        $msg = "どんちゃん(太鼓の達人コラボ)を付与しました。";
        $msg = "チョコボ(クリスタルディフェンダーズコラボ)を付与しました。";
        if($user_collaboration === FALSE){
          $user_collaboration = new UserCollaboration();
          $user_collaboration->user_id = $user_id;
          $user_collaboration->type = $type;
          $user_collaboration->create($pdo);
//          $card_id = 280;	// どんちゃん
          $card_id = 446;	// チョコボ
          $level = 1;
          $skill_level = 1;
          UserCard::addCardToUser($user_id, $card_id, $level, $skill_level, $pdo);
        }else{
          $item_offered = 1;
        }
      }
      if($params['type'] == 2 ){
        if ( Env::REGION == "KR" ) {
          $msg = "발차기 공주를 부여했습니다.";
        } else {
          $msg = "ケリ姫(ケリ姫コラボ)を付与しました。";
        }
        if($user_collaboration === FALSE){
          $user_collaboration = new UserCollaboration();
          $user_collaboration->user_id = $user_id;
          $user_collaboration->type = $type;
          $user_collaboration->create($pdo);
          $card_id = 500;	// ケリ姫
          $level = 1;
          $skill_level = 1;
          UserCard::addCardToUser($user_id, $card_id, $level, $skill_level, $pdo);
        }else{
          $item_offered = 1;
        }
      }
      if($params['type'] == 3 ){
        $msg = "どんちゃん(太鼓の達人コラボ)を付与しました。";
        if($user_collaboration === FALSE){
          $user_collaboration = new UserCollaboration();
          $user_collaboration->user_id = $user_id;
          $user_collaboration->type = $type;
          $user_collaboration->create($pdo);
          $card_id = 280;	// どんちゃん
          $level = 1;
          $skill_level = 1;
          UserCard::addCardToUser($user_id, $card_id, $level, $skill_level, $pdo);
        }else{
          $item_offered = 1;
        }
      }
      $pdo->commit();
      $resp_code = RespCode::SUCCESS;
      $user_cards = GetUserCards::getAllUserCards($user_id);
    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
    $res = array(
      'res' => $resp_code,
      'item_offered' => $item_offered,
      'msg' => $msg,
      'card' => $user_cards,
    );
    return json_encode($res);
  }

}
