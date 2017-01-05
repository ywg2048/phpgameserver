<?php
/**
 * 47.　メール削除
 */
class DeleteUserMail extends BaseAction {
	
  // http://pad.localhost/api.php?action=delete_user_mail&pid=2&sid=1&msgid=1
  public function action($params){
    $all_flg = FALSE;
    $msgid = FALSE;
    if(isset($params['msgid'])){
      $msgid = $params['msgid'];
    }
    $wmode = isset($params['m']) ? $params['m'] : User::MODE_NORMAL;
    if(isset($params['all']) && $params['all'] == "1"){
      // メール全件削除.
      $all_flg = TRUE;
    }
    $mails = UserMail::deleteMail($params['pid'], $msgid, $all_flg, $wmode);
    if($all_flg){
      return json_encode(array('res'=>RespCode::SUCCESS, 'mails'=>$mails));
    }else{
      return json_encode(array('res'=>RespCode::SUCCESS));
    }
  }

}
