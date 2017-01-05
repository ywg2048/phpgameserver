<?php
/**
 * ユーザーカードモデルのベースクラス.
 * author akamiya@gungho.jp
 */
abstract class BaseUserCardModel extends BaseModel {

  // デフォルトの Memcached 有効期間.
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  /**
   * オブジェクトを更新する.
   * @param PDO $pdo
   */
  public function update($pdo = null) {
    if(is_null($pdo)) {
      $pdo = Env::getDbConnectionForUserWrite($this->user_id);
    }
    // SQLを構築.
    list($columns, $values) = $this->getValuesForUpdate();
    $sql = 'UPDATE ' . static::TABLE_NAME . ' SET ';
    $setStmts = array();
    foreach($columns as $column) {
      $setStmts[] = $column . '=?';
    }
    $sql .= join(',', $setStmts);
    if(static::HAS_UPDATED_AT === TRUE) $sql .= ',updated_at=now()';
    $sql .= ' WHERE user_id = ? and cuid = ?';
    $stmt = $pdo->prepare($sql);
    $values = array_merge($values, array($this->user_id, $this->cuid));
    $result = $stmt->execute($values);

    if(Env::ENV !== "production"){
      global $logger;
      $logger->log("sql_query: ".$sql."; bind: ".join(",",$values), Zend_Log::DEBUG);
    }

    return $result;
  }

  /**
   * cuid指定でカードを削除する.
   * @param PDO $pdo
   */
  public static function delete_cards($user_id, $cuids, $pdo = null) {
    if(is_null($pdo)) {
      $pdo = Env::getDbConnectionForUserWrite($user_id);
    }
    // SQLを構築.
    $sql = 'DELETE FROM user_cards WHERE user_id = ? AND cuid IN (' . str_repeat('?,', count($cuids) - 1) . '?)';
    $values = array($user_id);
    $values = array_merge($values, $cuids);
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($values);

    if(Env::ENV !== "production"){
      global $logger;
      $logger->log("sql_query: ".$sql."; bind: ".join(",",$values), Zend_Log::DEBUG);
    }

    return $result;
  }

  /**
   * ユーザーカードオブジェクトの配列をデータベースにバルクインサートする.
   * @param PDO $pdo
   * @return 挿入の結果割り振られたID.
   */
  protected static function bulk_insert($add_cards, $pdo = null) {
    $add_card = array_shift($add_cards);
    if(is_null($pdo)) {
      $pdo = Env::getDbConnectionForUserWrite($add_card->user_id);
    }
    // SQLを構築.
    list($columns, $values) = $add_card->getValues();
  global $logger;
    $sql = 'INSERT INTO ' . static::TABLE_NAME . ' (' . join(',', $columns);
    if(static::HAS_CREATED_ON === TRUE) $sql .= ',created_on';
    if(static::HAS_CREATED_AT === TRUE) $sql .= ',created_at';
    if(static::HAS_UPDATED_AT === TRUE) $sql .= ',updated_at';
    $sql .= ') VALUES ';
    $sql2 = '(' . str_repeat('?,', count($columns) - 1) . '?';
    if(static::HAS_CREATED_ON === TRUE) $sql2 .= ',CURRENT_DATE()';
    if(static::HAS_CREATED_AT === TRUE) $sql2 .= ',now()';
    if(static::HAS_UPDATED_AT === TRUE) $sql2 .= ',now()';
    $sql2 .= ')';
    $sql .= str_repeat($sql2 . ',', count($add_cards)) . $sql2;
    foreach($add_cards as $obj){
      list($columns, $val) = $obj->getValues();
      $values = array_merge($values, $val);
    }

    // INSERT実行.
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($values);

    if(Env::ENV !== "production"){
      global $logger;
      $logger->log(("sql_query: ".$sql."; bind: ".join(",",$values))."; last_insert_id: ".$pdo->lastInsertId(), Zend_Log::DEBUG);
    }

    return $result;
  }

  /**
   * $columns に対応する値の配列を返す.
   * @return array カラムの配列、値の配列からなる配列.
   */
  protected function getValuesForUpdate() {
    $values = array();
    $columns = array();
    foreach (static::$columns as $column) {
      if($column == "id" || $column == "user_id" || $column == "cuid") continue;
      $columns[] = $column;
      $values[] = $this->$column;
    }
    return array($columns, $values);
  }

}
