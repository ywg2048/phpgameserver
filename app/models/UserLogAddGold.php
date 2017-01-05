<?php
/**
 * 魔宝石増加のログモデル.
 * type_flg 1:課金 2:ダンジョン制覇 3:ログインボーナス 4:管理者付与orALLボーナス
 */
class UserLogAddGold extends BaseModel {
  const TABLE_NAME = "user_log_add_gold";

  protected static $columns = array(
    'user_id',
    'type_flg',
    'data',
  );
  
  const TYPE_PURCHASE = 1;
  const TYPE_DUNGEON = 2;
  const TYPE_LOGIN = 3;
  const TYPE_MAIL = 4;
  const TYPE_MISSION = 5;
  const TYPE_ACTIVITY = 6;
  
  public static function log($user_id, $type_flg, $gold_before, $gold_after, $pgold_before, $pgold_after, $device_type) {

    //管理画面での表示のため魔宝石増加をCSV形式で残す(2012/6/14)
    $l = new UserLogAddGold();
    $l->user_id = $user_id;
    $l->type_flg = $type_flg;
    $l->data = json_encode( array( 'gold_before' => $gold_before, 'gold_after' => $gold_after, 'pgold_before' => $pgold_before, 'pgold_after' => $pgold_after, 'device_type' => $device_type));
    $l->created_at = date("Y-m-d H:i:s");

    if ( Env::ENV == "production" ) {
        static::postLog((array) $l);

        $purchase_data = "{\"gold_before\":\"".$gold_before."\",\"gold_after\":\"".($gold_after)."\",\"pgold_before\":\"".$pgold_before."\",\"pgold_after\":\"".($pgold_after)."\",\"device_type\":\"".$device_type."\"}";
        $purchase_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_user_log_add_gold.txt");
        $purchase_format = '%message%'.PHP_EOL;
        $purchase_formatter = new Zend_Log_Formatter_Simple($purchase_format);
        $purchase_writer->setFormatter($purchase_formatter);
        $purchase_logger = new Zend_Log($purchase_writer);
        $purchase_logger->log(preg_replace('/"/', '""',$user_id).",".
						      $type_flg.",\"".
						      preg_replace('/"/', '""',$purchase_data)."\",".
						      preg_replace('/"/', '""',date("Y-m-d H:i:s")), Zend_Log::DEBUG);
    } else {
        // 本番環境以外はDBへのINSERTに
        $pdo = Env::getDbConnectionForLog();
        $l->create( $pdo );
    }
  }

}
