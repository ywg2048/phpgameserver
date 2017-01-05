<?php
/**
 * 追加ガチャのログモデル.
 */
class UserLogExtraGacha extends BaseModel {
  const TABLE_NAME = "user_log_extra_gacha";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'user_id',
    'extra_gacha_id',
    'data',
  );

  public static function log($user_id, $extra_gacha_id, $data = array(), $pdo = null) {
    $l = new UserLogExtraGacha();
    $l->user_id = $user_id;
    $l->extra_gacha_id = $extra_gacha_id;
    $l->data = json_encode($data);
    $l->create($pdo);
    
    #PADC#
    $logFile = Padc_Log_Log::getExtraGachaLogFile();

    //20130813 耐障害性を上げるためテキスト形式でも出力
    $extragacha_log_writer = new Zend_Log_Writer_Stream($logFile);
    $extragacha_log_format = '%message%'.PHP_EOL;
    $extragacha_log_formatter = new Zend_Log_Formatter_Simple($extragacha_log_format);
    $extragacha_log_writer->setFormatter($extragacha_log_formatter);
    $extragacha_log_logger = new Zend_Log($extragacha_log_writer);
    $extragacha_log_logger->log(
        $l->user_id.",".
        $l->extra_gacha_id.",".
        "\"".preg_replace('/"/', '""',$l->data)."\",".
        "\"".preg_replace('/"/', '""',date("Y-m-d H:i:s"))."\"", Zend_Log::DEBUG
    );
  }

}
