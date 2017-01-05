<?php
/**
 * 62. データダウンロード
 */
class DownloadData extends BaseAction {
	
  // http://pad.localhost/api.php?action=download_data&pid=2&sid=1&type=0
  public function action($params){
    $pdo = Env::getDbConnectionForUserRead($params["pid"]);
    $user_id = $params['pid'];
    $type = $params['type'];
    $user_upload_data = UserUploadData::findBy(array('user_id' => $user_id, 'type' => $type));
    if(!$user_upload_data){
      $dcnt = 0;
      $data = null;
    }else{
      $dcnt = (int)$user_upload_data->dcnt;
      $data = $user_upload_data->data;
    }
    $resp_code = RespCode::SUCCESS;
    $res = array('res'=>RespCode::SUCCESS, 'data'=>$data, 'dcnt' => $dcnt);
    return json_encode($res);
  }

}
