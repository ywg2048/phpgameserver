<?php
/**
 * 65. データ引き継ぎ認証
 */
class SetAuthData extends BaseAction {

  // http://pad.localhost/api.php?action=set_auth_data&pid=11111&sid=2222
  public function action($params){
    $user_id = $params['pid'];
    $type = $params['type']; // 0:twitter 1:facebook 2:google
    $auth_data = $params['auth_data'];

    // 引き継ぎ機能はとりあえずGoogleアカウントのみ対応.
    if($type != 2 || $auth_data == ""){
      throw new PadException(RespCode::UNKNOWN_ERROR);
    }

    // DBから検索
    $pdo_share = Env::getDbConnectionForShare();

    $support_data = SupportData::findBy(array('user_id' => $user_id), $pdo_share);
    if($support_data) {
      $support_data->auth_type = $type;
      $support_data->auth_data = $auth_data;
      $support_data->update($pdo_share);
    }else{
      $support_data = new SupportData();
      $support_data->user_id = $user_id;
      $support_data->auth_type = $type;
      $support_data->auth_data = $auth_data;
      $support_data->create($pdo_share);
    }
    return json_encode(array('res'=>RespCode::SUCCESS));
  }


}
