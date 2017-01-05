<?php
/**
 * ダンジョンクリアのログモデル.
 */
class UserLogClearDungeon extends BaseModel {
  const TABLE_NAME = "user_log_clear_dungeon";

  protected static $columns = array(
    'user_id',
    'dungeon_floor_id',
    'sneaked_at',
    'cleared_time',
    'deck',
    'data',
    'continue_cnt',
    'nxc',
  );

//負荷対策のためクリアログについてDBへのINSERTをログ出力へ変更(2012/5/21)
//  public static function log($user_id, $dungeon_floor_id, $sneaked_at, $cleared_time, $deck = array(), $data = array(), $pdo = null) {
  public static function log($user_id, $dungeon_floor_id, $sneaked_at, $cleared_time, $deck = array(), $data = array(), $continue_cnt, $nxc = null) {
    $l = new UserLogClearDungeon();
    $l->user_id = $user_id;
    $l->dungeon_floor_id = $dungeon_floor_id;
    $l->sneaked_at = $sneaked_at;
    $l->cleared_time = $cleared_time;
    $l->deck = json_encode($deck);
    $l->data = json_encode($data);
    $l->continue_cnt = $continue_cnt;
    $l->created_at = date("Y-m-d H:i:s");

if(!is_null($nxc)){
	$original_nxc=$nxc;//受け取った文字列(16進数)
	$nkey= hexdec($original_nxc);
//	echo "nkey:".dechex($nkey)."\n";

	$cf_random_key_mask = 0xFF;//mask用固定値
	$mask=(($nkey & $cf_random_key_mask) * 427053331 + 847549243) & 0xFFFFFFFF;
//	echo "mask:".dechex($mask)."\n";

	$decoded_nxc=($nkey ^ $mask) & ~$cf_random_key_mask;

//	echo "decode:".dechex($decode)."\n";
    $l->nxc = $decoded_nxc;
}else{
	$l->nxc = null;
}
    
//    $l->create($pdo);
    if ( Env::ENV == "production" ) {
        static::postLog((array) $l);

        $clear_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_user_log_clear_dungeon.txt");
        $clear_format = '%message%'.PHP_EOL;
        $clear_formatter = new Zend_Log_Formatter_Simple($clear_format);
        $clear_writer->setFormatter($clear_formatter);
        $clear_logger = new Zend_Log($clear_writer);
        $clear_logger->log(preg_replace('/"/', '""',$l->user_id).",".
                           preg_replace('/"/', '""',$l->dungeon_floor_id).",".
                           preg_replace('/"/', '""',$l->sneaked_at).",".
                           preg_replace('/"/', '""',$l->cleared_time).",\"".
                           preg_replace('/"/', '""',$l->deck)."\",\"".
                           preg_replace('/"/', '""',$l->data)."\",".
                           preg_replace('/"/', '""',date("Y-m-d H:i:s")).",".
                           preg_replace('/"/', '""',$l->continue_cnt).",".
                           preg_replace('/"/', '""',$l->nxc), Zend_Log::DEBUG);
    } else {
        // 本番環境以外はDBへのINSERTに
        $pdo = Env::getDbConnectionForDungeonLog();
        $l->create( $pdo );
    }
  }

}
