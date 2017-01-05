<?php
/**
 * 名前変更のログモデル.
 */
class UserLogChangeName extends BaseModel {
  const TABLE_NAME = "user_log_change_name";
  const MEMCACHED_EXPIRE = 3600; // 1時間.

  protected static $columns = array(
    'user_id',
    'data',
    'admin_id',
  );

  public static function log($user_id, $data = array(), $admin_id = null, $pdo = null) {
    $l = new UserLogChangeName();
    $l->user_id = $user_id;
    $l->data = json_encode($data);
    $l->admin_id = $admin_id;
    $l->create($pdo);
  }
}
