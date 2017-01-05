<?php
/**
 * ユーザが保持しているカードのシーケンスのログモデル.
 */
class UserCardSeq extends BaseModel {
  const TABLE_NAME = "user_card_seq";

  protected static $columns = array(
    'user_id',
    'max_cuid',
  );

  // カードのcuidを新たに採番して返す
  public static function getNextCuid($user_id, $pdo, $add_cuid_cnt = 1) {
    //$pdo = Env::getDbConnectionForUserWrite($user_id);
    $sql = 'UPDATE ' . static::TABLE_NAME . ' SET max_cuid=LAST_INSERT_ID(max_cuid+?) where user_id=?';
    $bind_param = array($add_cuid_cnt, $user_id);
    list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
    $row = $stmt->rowCount();
    if($row > 0) {
      $sql = 'SELECT LAST_INSERT_ID() AS max_cuid';
      $bind_param = array();
      list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
      $obj = $stmt->fetch(PDO::FETCH_NUM);
      $max_cuid = $obj[0];
    } else {
      $sql = 'INSERT INTO ' . static::TABLE_NAME . ' (user_id, max_cuid) VALUES (?, ?)';
      $bind_param = array($user_id, $add_cuid_cnt);
      self::prepare_execute($sql, $bind_param, $pdo);
      $max_cuid = $add_cuid_cnt;
    }
    return $max_cuid;
  }

}
