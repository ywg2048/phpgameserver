<?php
/**
 * 29. お礼返し
 */
class SendThankYouGift extends BaseAction {
	
  // http://pad.localhost/api.php?action=send_thank_you_gift&pid=2&sid=1&gid=3
  public function action($params){
  	try{
  		$pdo_user1 = Env::getDbConnectionForUserWrite($params["pid"]);
  		$pdo_user2 = Env::getDbConnectionForUserWrite($params["gid"]);
      $pdo_user1->beginTransaction();
      $pdo_user2->beginTransaction();
  		$user = User::find($params['pid'], $pdo_user1, TRUE);
  		$user->sendThankYouGift($params['gid'], $pdo_user2);
  		$user->accessed_at = User::timeToStr(time());
  		$user->accessed_on = $user->accessed_at;
  		$user->update($pdo_user1);

      // 卵＋値取得
      $plus_egg = PlusEgg::getPlusParam(PlusEgg::GACHA_THANKS);
      // 送り主に景品付与.
      $gacha_prize = Gacha::takeGachaPrize(Gacha::TYPE_THANKS);
      $user_card = UserCard::addCardToUser(
        $user->id,
        $gacha_prize->card_id,
        $gacha_prize->getLevel(),
        UserCard::DEFAULT_SKILL_LEVEL,
        $pdo_user1,
        $plus_egg->hp * 3,
        $plus_egg->atk * 3,
        $plus_egg->rec * 3
      );
  		$user_card_str = GetUserCards::arrangeColumn($user_card);
  		$pdo_user1->commit();
  		$pdo_user2->commit();
  	}catch(Exception $e){
      if ($pdo_user1->inTransaction()) {
        $pdo_user1->rollback();
      }
      if ($pdo_user2->inTransaction()) {
        $pdo_user2->rollback();
      }
		throw $e;
  	}
	return json_encode(array('res'=>RespCode::SUCCESS, 'card'=>$user_card_str));
  }

}
