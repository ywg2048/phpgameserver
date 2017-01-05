<?php
/**
 * 66. メールお気に入り登録
 */
class FavUserMail extends BaseAction {
	
  // http://pad.localhost/api.php?action=fav_user_mail&pid=1&sid=1&mid=1&fav=1
  public function action($params){
    $user_id = $params['pid'];
    $mail_id = $params['mid'];
    $fav = $params['fav'];
    UserMail::saveFav($user_id, $mail_id, $fav);
    return json_encode(array('res'=>RespCode::SUCCESS));
  }

}
