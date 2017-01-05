<?php
/**
 * キャンペーンシリアルコード情報.
 */

class CampaignSerialCode extends BaseMasterModel {
  const TABLE_NAME = "campaign_serial_code";
  const VER_KEY_GROUP = "se_cd";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  const SERIAL_TYPE_ONCE = 0;
  const SERIAL_TYPE_MULTI = 1;
  const SERIAL_TYPE_FREE = 2;

  protected static $columns = array(
    'id',
    'begin_at',
    'finish_at',
    'serial_type',
    'memo',
  );

  /**
   * このボーナスが適用時間内である場合に限りTRUEを返す.
   */
  public function checkEnabled() {
    $now = time();
    if(static::strToTime($this->begin_at) <= $now && static::strToTime($this->finish_at) >= $now) {
      return TRUE;
    }
    return FALSE;
  }

}
