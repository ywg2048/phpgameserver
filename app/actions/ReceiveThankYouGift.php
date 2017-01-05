<?php
/**
 * 37. お礼受け取り
 */
class ReceiveThankYouGift extends BaseAction {
	
  // http://pad.localhost/api.php?action=receive_thank_you_gift&pid=2&sid=1
  public function action($params){
    $sender_id = 0;
    try{
      $pdo = Env::getDbConnectionForUserWrite($params["pid"]);
      $pdo->beginTransaction();
      $user = User::find($params['pid'], $pdo, TRUE);
      $sender_id = $user->receiveThankYouGift($pdo);
  	  $user->accessed_at = User::timeToStr(time());
  	  $user->accessed_on = $user->accessed_at;
      $user->update($pdo);

      // 卵＋値取得
      $plus_egg = PlusEgg::getPlusParam(PlusEgg::GACHA_THANKS);
      // 受け取り側に景品付与.
      $gacha_prize = Gacha::takeGachaPrize(Gacha::TYPE_THANKS);
      $user_card = UserCard::addCardToUser(
        $user->id,
        $gacha_prize->card_id,
        $gacha_prize->getLevel(),
        UserCard::DEFAULT_SKILL_LEVEL,
        $pdo,
        $plus_egg->hp * 3,
        $plus_egg->atk * 3,
        $plus_egg->rec * 3
      );

//      $log_data = array('from'=>$sender_id, 'card_id'=>$user_card->card_id, 'cuid'=>$user_card->cuid);
//      UserLog::log("ReceiveThankYouGift", $user->id, $log_data, $pdo);

      $user_card_str = GetUserCards::arrangeColumn($user_card);

      $pdo->commit();
    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
    return json_encode(array('res'=>RespCode::SUCCESS, 'from'=>$sender_id, 'card'=>$user_card_str));
  }

}
