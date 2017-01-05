<?php
/**
 * 61. データアップロード
 */
class UploadData extends BaseAction {
	
  // http://pad.localhost/api.php?action=upload_data&pid=2&sid=1&type=0
  public function action($params){
    $resp_code = RespCode::UNKNOWN_ERROR;
    $user_id = $params['pid'];
    $type = $params['type'];
    $data = isset($params['data']) ? $params['data'] : "";
    try{
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();
      list($dcnt, $data) = UserUploadData::upload($user_id, $type, $data, $pdo);
      $pdo->commit();
      $resp_code = RespCode::SUCCESS;
    }catch(Exception $e){
      $dcnt = 0;
      $data = null;
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
    if($type == UserUploadData::ZUKAN){
      $res = array('res' => $resp_code, 'dcnt' => $dcnt, 'data' => $data);
    }else{
      $res = array('res' => $resp_code, 'dcnt' => $dcnt);
    }
    return json_encode($res);
  }

}
