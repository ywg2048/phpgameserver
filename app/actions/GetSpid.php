<?php
/**
 * 64. サポートID取得
 */
class GetSpid extends BaseAction {

  // http://pad.localhost/api.php?action=get_spid&pid=11111&sid=2222
  public function action($params){
    $user_id = $params['pid'];

    // DBから検索
    $pdo_share = Env::getDbConnectionForShare();
    $support_data = SupportData::findBy(array('user_id'=>$user_id), $pdo_share);
    if(!$support_data) {
      $support_data = new SupportData();
      // 秘密のコード重複チェック.
      do{
        $secret_code = SupportData::generateSecretCode();
      } while(SupportData::findBy(array('secret_code'=>$secret_code), $pdo_share) != NULL);
      $support_data->user_id = $user_id;
      $support_data->secret_code = $secret_code;
      $support_data->create($pdo_share);
    }elseif(empty($support_data->secret_code)){
      // 秘密のコード重複チェック.
      do{
        $secret_code = SupportData::generateSecretCode();
      } while(SupportData::findBy(array('secret_code'=>$secret_code), $pdo_share) != NULL);
      $support_data->secret_code = $secret_code;
      $support_data->update($pdo_share);
    }
    return json_encode(array('res'=>RespCode::SUCCESS, 'spid'=>$support_data->secret_code));
  }


}
