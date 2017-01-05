<?php
/**
 * 宝箱.
 */

class Treasure extends BaseMasterModel {
  const TABLE_NAME = "treasures";
  const VER_KEY_GROUP = "dung";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'dungeon_floor_id',
    'award_id',
    'prob',
    'amount',
  );

}
