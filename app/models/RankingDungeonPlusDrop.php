<?php
/**
 * #PADC#
 * ダンジョンごとの＋卵ドロップ率補正.ランキング用
 */

class RankingDungeonPlusDrop extends BaseMasterModel {
  const TABLE_NAME = "padc_ranking_dungeon_plus_drop";
  const VER_KEY_GROUP = "padcrankingdung";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'drop_prob',
  );

}
