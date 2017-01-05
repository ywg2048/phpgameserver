<?php
/**
 * ダンジョン販売商品.
 */

class DungeonSaleCommodity extends BaseMasterModel {
  const TABLE_NAME = "dungeon_sale_commodities";
  const VER_KEY_GROUP = "d_sale";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'dungeon_sale_id',
    'dungeon_id',
    'price',
    'open_hour',
    'message',
  );

}
