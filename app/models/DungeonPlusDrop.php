<?php
/**
 * ダンジョンごとの＋卵ドロップ率補正.
 */

class DungeonPlusDrop extends BaseMasterModel {
  const TABLE_NAME = "dungeon_plus_drop";
  const VER_KEY_GROUP = "dung_pd";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'drop_prob',
  );

}
