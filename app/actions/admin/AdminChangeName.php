<?php
/**
 * 25. 名前変更
 */
class AdminChangeName extends AdminBaseAction {
  
  // http://pad.localhost/api.php?action=change_name&pid=2&sid=1&name=XXXX
  public function action($params){
    $name = trim($params['name']);
    if(strlen($name) == 0){
      throw new PadException(RespCode::INVALID_NAME);
    }
    try{
      $pdo = Env::getDbConnectionForUserWrite($params["pid"]);
      $pdo->beginTransaction();
      $user = User::find($params['pid'], $pdo, TRUE);
      $before_name = $user->name;
      $user->name = $name;
      $user->accessed_at = User::timeToStr(time());
      $user->accessed_on = $user->accessed_at;
      $user->update($pdo);
      // debug機能としては不要
//      UserLogChangeName::log($user->id, array('before_name'=>$before_name, 'after_name'=>$name), null, $pdo);
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
