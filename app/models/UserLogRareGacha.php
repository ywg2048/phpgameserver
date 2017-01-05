<?php
/**
 * レアガチャのログモデル.
 */
class UserLogRareGacha extends BaseModel {
  const TABLE_NAME = "user_log_rare_gacha";
  const MEMCACHED_EXPIRE = 86400; // 24時間.
  
  protected static $columns = array(
    'user_id',
    'data',
  );
  
  public static function log($user_id, $data = array(), $pdo = null) {
    $l = new UserLogRareGacha();
    $l->user_id = $user_id;
    $l->data = json_encode($data);
    $l->create($pdo);
    
    #PADC#
    $logFile = Padc_Log_Log::getRareGachaLogFile();
    
    //20130813 耐障害性を上げるためテキスト形式でも出力
    $raregacha_log_writer = new Zend_Log_Writer_Stream($logFile);
    $raregacha_log_format = '%message%'.PHP_EOL;
    $raregacha_log_formatter = new Zend_Log_Formatter_Simple($raregacha_log_format);
    $raregacha_log_writer->setFormatter($raregacha_log_formatter);
    $raregacha_log_logger = new Zend_Log($raregacha_log_writer);
    $raregacha_log_logger->log(
      $l->user_id.",".
      "\"".preg_replace('/"/', '""',$l->data)."\",".
      "\"".preg_replace('/"/', '""',date("Y-m-d H:i:s"))."\"", Zend_Log::DEBUG
    );
  }
}
