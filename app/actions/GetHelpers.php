<?php
/**
 * 19. ＡＰＩ：ヘルパーリスト取得
 */
class GetHelpers extends BaseAction {
	
  // http://pad.localhost/api.php?action=get_helpers&pid=2&sid=1
  public function action($params){
    $rev = isset($params['r']) ? (int)$params['r'] : 1;
    return json_encode(array('res' => RespCode::SUCCESS, 'helpers'=>Friend::getFriends($params['pid'], $rev)));
  }

}
