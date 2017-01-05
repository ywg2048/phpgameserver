<?php
/**
 * 16. メール取得
 */
class GetUserMails extends BaseAction {
  
  // http://pad.localhost/api.php?action=get_user_mails&pid=2&sid=1&ofs=0&cnt=10
  public function action($params){
    $m = isset($params['m']) ? $params['m'] : 0;
    $mails = UserMail::getMails(
      $params['pid'],
      array(
        UserMail::TYPE_FRIEND_REQUEST, 
        UserMail::TYPE_MESSAGE, 
        UserMail::TYPE_ADMIN_MESSAGE, 
        UserMail::TYPE_ADMIN_BONUS, 
        UserMail::TYPE_ADMIN_BONUS_TO_ALL
      ),
      $params['ofs'],
      $params['cnt'],
      $m
    );
    return json_encode(array('res' => RespCode::SUCCESS, 'mails'=>GetUserMails::arrangeColumns($mails)));
  }
  
  /**
   * UserMailのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  public static function arrangeColumns($user_mails) {
    $mapper = array();
    foreach($user_mails as $user_mail) {
      $arr = array();
      $arr['id'] = (int)$user_mail->id;
      $arr['from'] = (int)$user_mail->sender_id;
      $arr['date'] = date('ymdHis',strtotime($user_mail->created_at));
      $arr['fav'] = (int)$user_mail->fav;
      if(empty($user_mail->message)){
        $arr['sub'] = "";
      }else{
        $arr['sub'] = self::mb_strcut_sjislike($user_mail->message, 32);
      }
      switch($user_mail->type){
        case UserMail::TYPE_FRIEND_REQUEST:
          $arr['type'] = 0;
          break;
        case UserMail::TYPE_ADMIN_MESSAGE:
        case UserMail::TYPE_ADMIN_MESSAGE_NORMAL:
        case UserMail::TYPE_ADMIN_MESSAGE_W:
          $arr['type'] = 1;
          break;
        case UserMail::TYPE_ADMIN_BONUS:
        case UserMail::TYPE_ADMIN_BONUS_NORMAL:
        case UserMail::TYPE_ADMIN_BONUS_W:
          $arr['type'] = 2; // メッセージがない場合
          if ($user_mail->message) {
            $arr['type'] = $user_mail->bonus_id == BaseBonus::MAIL_ID ? 1 : 3;
          }
          break;
        case UserMail::TYPE_ADMIN_BONUS_TO_ALL:
        case UserMail::TYPE_ADMIN_BONUS_TO_ALL_NORMAL:
        case UserMail::TYPE_ADMIN_BONUS_TO_ALL_W:
          $arr['type'] = $user_mail->bonus_id == BaseBonus::MAIL_ID ? 1 : 3;
          break;
        case UserMail::TYPE_MESSAGE:
          $arr['type'] = 4;
          break;
      }
      $arr['offered'] = (int)$user_mail->offered;
      $arr['bonus_id'] = (int)$user_mail->bonus_id;
      // クライアントでは魔法石に有償・無償の区別はないため、無償魔法石として返す.
      if($arr['bonus_id'] == BaseBonus::PREMIUM_MAGIC_STONE_ID){
        $arr['bonus_id'] = BaseBonus::MAGIC_STONE_ID;
      }
      //#PADC# ----------begin----------
      if($arr['bonus_id'] == BaseBonus::PIECE_ID){
      	$arr['piece_id'] = (int)$user_mail->piece_id;
      }
      //#PADC# ----------end----------
      // アバター付与時
      if($arr['bonus_id'] == BaseBonus::AVATAR_ID) {
        $jdata = json_decode($user_mail->data);
        $arr['ava_id'] = (int)$jdata->aid;
        $arr['type'] = 3;
      }
      $arr['amount'] = (int)$user_mail->amount;
      if(isset($user_mail->title)){
		$arr['title'] = $user_mail->title;
	  }
      $mapper[] = $arr;
    }
    return $mapper;
  }

  private static function mb_strcut_sjislike($str, $length){
    $cnt = 0;
    $ret = "";
    $s = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY); // UTF-8の文字列を配列に.
    for ($i = 0; $i < count($s); $i++) {
      $asc = ord($s[$i]);
      if (32 <= $asc && $asc <= 126) {
        $cnt += 1; // 1バイト幅文字 0x20(" ")から0x7E("~")まで.
      }else{
        $cnt += 2; // 1バイト幅文字以外.
      }
      if($cnt > $length){
        return $ret;
      }
      $ret .= $s[$i];
    }
    return $ret;
  }

}
