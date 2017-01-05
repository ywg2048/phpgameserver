<?php
/**
 * カードスロット拡張のログモデル.
 */
class UserLogExpandNumCards extends BaseModel {
  const TABLE_NAME = "user_log_expand_num_cards";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'user_id',
    'data',
  );

  public static function log($user_id, $data = array(), $pdo = null) {
    $l = new UserLogExpandNumCards();
    $l->user_id = $user_id;
    $l->data = json_encode($data);
    $l->create($pdo);
  }

}
