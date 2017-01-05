<?php
/**
 * 67. ユーザー設定
 */
class UserSetting extends BaseAction {
	
  // http://pad.localhost/api.php?action=user_setting&pid=2&sid=1&us=0
  public function action($params){
    $user_id = $params['pid'];
    $us = $params['us'];
    $pdo = Env::getDbConnectionForUserWrite($user_id);
    $pdo->beginTransaction();
    try{
      $user = User::find($user_id, $pdo, TRUE);
      $user->us = (int)$us;
      $user->update($pdo);
      $pdo->commit();
    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
    return json_encode(array('res'=>RespCode::SUCCESS));
  }

}
