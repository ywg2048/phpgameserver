<?php
/**
 * ログインのログモデル.
 */
class UserLogLogin extends BaseModel {
  const TABLE_NAME = "user_log_login";

  protected static $columns = array(
    'user_id',
    'ip',
    'data',
  );

//負荷対策のためログインのログについてDBへのINSERTをログ出力へ変更(2012/6/14)
  public static function log($user_id, $ip, $data = array(), $pdo = null) {
    $l = new UserLogLogin();
    $l->user_id = $user_id;
    $l->ip = $ip;
    $l->data = json_encode($data);
    $l->created_at = date("Y-m-d H:i:s");

//    $l->create($pdo);
    if ( Env::ENV == "production" ) {
        static::postLog((array) $l);

        $login_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_user_log_login.txt");
        $login_format = '%message%'.PHP_EOL;
        $login_formatter = new Zend_Log_Formatter_Simple($login_format);
        $login_writer->setFormatter($login_formatter);
        $login_logger = new Zend_Log($login_writer);
        $login_logger->log(preg_replace('/"/', '""',$l->user_id).",\"".
						   preg_replace('/"/', '""',$l->ip)."\",\"".
						   preg_replace('/"/', '""',$l->data)."\",".
						   preg_replace('/"/', '""',date("Y-m-d H:i:s")), Zend_Log::DEBUG);
    } else {
        // 本番環境以外はDBへのINSERTに
        if ( $pdo == null ) {
            $pdo = Env::getDbConnectionForLog();
        }
        $l->create( $pdo );
    }
  }

  // IP検索
  public static function searchUserIp( $ip ) {
      $pdo = Env::getDbConnectionForLog();
      $sql = " select distinct user_id from " . self::TABLE_NAME . " where ip = ? ";
      $stmt = $pdo->prepare( $sql );
      $stmt->execute( array( $ip ) );
      $users = $stmt->fetchAll( PDO::FETCH_ASSOC );
      $user_ids = array();
      foreach ( $users as $user ) {
          $user_ids[] = $user['user_id'];
      }
      return $user_ids;
  }

}
