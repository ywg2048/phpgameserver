<?php
/**
 * メール削除のログモデル.
 */
class UserLogDeleteMail extends BaseModel {
  const TABLE_NAME = "user_log_delete_mail";

  protected static $columns = array(
    'user_id',
    'data',
  );

  public static function log($user_id, $data = array(), $pdo = null) {
    $l = new UserLogDeleteMail();
    $l->user_id = $user_id;
    $l->data = json_encode($data);
    $l->create($pdo);
  }

}
