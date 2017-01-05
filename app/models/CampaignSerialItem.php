<?php
/**
 * キャンペーンシリアルアイテム情報.
 */

class CampaignSerialItem extends BaseMasterModel {
  const TABLE_NAME = "campaign_serial_item";
  const VER_KEY_GROUP = "se_itm";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'campaign_id',
    'item_id',
    'avatar_id',
    'lv',
    'plus_hp',
    'plus_atk',
    'plus_rec',
  );

}
