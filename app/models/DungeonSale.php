<?php
/**
 * ダンジョン販売.
 */

class DungeonSale extends BaseMasterModel {
  const TABLE_NAME = "dungeon_sales";
  const VER_KEY_GROUP = "d_sale";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'begin_at',
    'finish_at',
    'font_color',
    'panel_color',
    'message',
  );

}
