<?php
/**
 * 63. データ引き継ぎ
 */
class ChangeDevice extends BaseAction {

  // このアクションへのコールはログイン必要なし
  const LOGIN_REQUIRED = FALSE;

  const AUTH_TWITTER = 0; // TwitterAccount
  const AUTH_FACEBOOK = 1; // FacebookAccount
  const AUTH_GOOGLE_ID = 2; // GoogleAccount
  const AUTH_CHANGE_CODE = 3; // 機種変コード(モデルはUserChangeDevice)

  // http://pad.localhost/api.php?action=change_device&t=0&u=UUID&id=11111
  public function action($params){
    $device_type = $params['t'];
    $uuid = $params['u'];
    $pid = $params['id'];
    $auth_type = $params['type'];
    $auth_data = $params['auth_data'];
    $mode = isset($params['mode']) ? $params['mode'] : null;

    global $logger;
    $logger->log(("ChangeDevice device:".$device_type." uuid:".$uuid." pid: ".$pid." auth_type:".$auth_type." data:".$auth_data." mode:".$mode), Zend_Log::DEBUG);

    // DBから検索
    $pdo_share = Env::getDbConnectionForShare();
    $pdo_share->beginTransaction();
    $pdo = Env::getDbConnectionForUserWrite($pid);
    if($auth_type == static::AUTH_CHANGE_CODE){
      $cdd = ChangeDeviceData::findBy(array('user_id'=>$pid, 'code'=>$auth_data), $pdo);
      if($cdd){
        $user = User::find($pid, $pdo);
        if($user) {
          $user_name = $user->name;
          $user_lv = $user->lv;
          $user_gold = $user->gold + $user->pgold;
          $org_device_type = $user->device_type;
          if($mode == 0){
            // 確認モード.
            return json_encode(array('res'=>RespCode::SUCCESS, 'name'=>$user_name, 'lv'=>$user_lv, 'gold'=>$user_gold));
          }elseif($mode == 1){
            $user_device = UserDevice::findBy(array('type' => $user->device_type, 'id' => $pid), $pdo_share);
          }
        }
      }
    }else{
      $support_data = SupportData::findBy(array('user_id' => $pid), $pdo_share);
      // 機種変コード以外(Googleアカウント).
      if($support_data && $support_data->auth_type == $auth_type && $support_data->auth_data == $auth_data){
        $user = User::find($pid, $pdo);
        if($user && $user->device_type == User::TYPE_ANDROID) {
          $user_name = $user->name;
          $user_lv = $user->lv;
          $user_gold = $user->gold + $user->pgold;
          $org_device_type = $user->device_type;
          if($mode == 0){
            // 確認モード.
            return json_encode(array('res'=>RespCode::SUCCESS, 'name'=>$user_name, 'lv'=>$user_lv, 'gold'=>$user_gold));
          }elseif($mode == 1){
            $user_device = UserDevice::findBy(array('type' => $device_type, 'id' => $pid), $pdo_share);
          }
        }
      }
    }

    if(isset($user_device) && $user_device){
      // uuidの付け替え.
      if($auth_type == static::AUTH_CHANGE_CODE){
        $write_log = TRUE;
      }else{
        $write_log = FALSE;
      }
      $result = UserDevice::replaceUuid($pid, $uuid, $device_type, $pdo_share, null, $write_log);
      if($result){
        $pdo_share->commit();
        // 機種変コード破棄.
        if($auth_type == static::AUTH_CHANGE_CODE){
          $cdd->delete();
        }
        return json_encode(array('res'=>RespCode::SUCCESS));
      }else{
        $pdo_share->rollback();
        throw new PadException(RespCode::UNKNOWN_ERROR);
      }
    }else{
      $pdo_share->rollback();
      if($auth_type == static::AUTH_CHANGE_CODE){
        // 引き継ぎのユーザーがいない.
        throw new PadException(RespCode::FAILED_USER_OR_CODE, "ChangeDevice USER_OR_CODE_NOT_FOUND. pid:".$pid." device_type:".$device_type." uuid:".$uuid." type:".$auth_type." data:".$auth_data. " __NO_TRACE");
      }else{
        throw new PadException(RespCode::USER_NOT_FOUND, "ChangeDevice USER_NOT_FOUND. pid:".$pid." device_type:".$device_type." uuid:".$uuid." type:".$auth_type." data:".$auth_data. " __NO_TRACE");
      }
    }
  }
}
