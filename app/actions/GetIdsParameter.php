<?php
/**
 * XX. IDSパラメータ取得
 * (複数ユーザ情報取得)
 */
class GetIdsParameter extends BaseAction {
	
  // http://pad.localhost/api.php?action=get_id_parameter&pid=1&sid=1&ids=2,4,5,6
  public function action($params){
    $rev = (isset($params["r"])) ? (int)$params["r"] : 1;
    $ids = explode(",", $params['ids']);
    $targetUsers = array();
    foreach($ids as $id){
      $targetUsers[] = User::getCacheFriendData($id, $rev);
    }
    if( $targetUsers === FALSE || count($targetUsers) == 0){
      throw new PadException(RespCode::USER_NOT_FOUND);
    }
    $res = array();
    $res['res'] = RespCode::SUCCESS;
    $res['users'] = $targetUsers;
    return json_encode($res);
  }

}
