<?php
/**
 * 期限付きガチャメッセージ
 */
class GachaMessage extends BaseMasterModel {
  const TABLE_NAME = "gacha_messages";
  const VER_KEY_GROUP = "gacha_mes";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'begin_at',
    'finish_at',
    'message',
  );

  public static $messageTags = array(
    0 => array('view' => array('jp'), 'remove' => array('ht')), // 日本
    4 => array('view' => array('ht'), 'remove' => array('jp')), // 香港・台湾
  );

  /**
   * ガチャメッセージを返す.
   */
  public static function getMessage($area_id = null){
    $time = time();
    $messages = self::getAll();
    $res = array();
    if($messages){
      foreach($messages as $mes){
        if(static::strToTime($mes->begin_at) <= $time && $time <= static::strToTime($mes->finish_at)){
          $res['mes'] = self::replaceTagMessage($mes->message, $area_id);
        }
        if($time < static::strToTime($mes->begin_at) && !isset($res['next_time'])){
          $res['next_time'] = $mes->begin_at;
          $res['next_mes'] = self::replaceTagMessage($mes->message, $area_id);
        }
      }
    }
    return $res;
  }

  /**
   * メッセージから特定のタグを置換
   */
  public static function replaceTagMessage($message, $area_id) {
    if (isset(self::$messageTags[$area_id])) {
      foreach (self::$messageTags[$area_id]['view'] as $tag) {
        $message = preg_replace('/(<\/?'.$tag.'>(\r|\n|\r\n)?)/i', '', $message);
      }
      foreach (self::$messageTags[$area_id]['remove'] as $tag) {
        $message = preg_replace('/(<'.$tag.'>[\s\S]*?<\/'.$tag.'>(\r|\n|\r\n)?)/i', '', $message);
      }
    }
    return $message;
  }

}
