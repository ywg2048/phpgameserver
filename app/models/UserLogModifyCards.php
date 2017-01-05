<?php
/**
 * カード通常合成、進化合成、売却のログモデル.
 */
class UserLogModifyCards extends BaseModel {
  const TABLE_NAME = "user_log_modify_cards";

  protected static $columns = array(
    'user_id',
    'mode_flg',
    'data',
  );

//負荷対策のため通常合成、進化合成、売却ログについてDBへのINSERTをログ出力へ変更(2012/5/23)
//  public static function log($user_id, $mode_flg, $data = array(), $pdo = null) {
  public static function log($user_id, $mode_flg, $data = array()) {
    $l = new UserLogModifyCards();
    $l->user_id = $user_id;
    $l->mode_flg = $mode_flg;
    $l->data = json_encode($data);
    $l->created_at = date("Y-m-d H:i:s");
    
//    $l->create($pdo);
    if ( Env::ENV == "production" ) {
        static::postLog((array) $l);

        $sell_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_user_log_modify_cards.txt");
        $sell_format = '%message%'.PHP_EOL;
        $sell_formatter = new Zend_Log_Formatter_Simple($sell_format);
        $sell_writer->setFormatter($sell_formatter);
        $sell_logger = new Zend_Log($sell_writer);
        $sell_logger->log(preg_replace('/"/', '""',$l->user_id).",".
						  preg_replace('/"/', '""',$l->mode_flg).",\"".
						  preg_replace('/"/', '""',$l->data)."\",".
						  preg_replace('/"/', '""',date("Y-m-d H:i:s")), Zend_Log::DEBUG);
    } else {
        // 本番環境以外はDBへのINSERTに
        $pdo = Env::getDbConnectionForCardLog();
        $l->create( $pdo );
    }
  }

}
