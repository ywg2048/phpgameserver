<?php
/**
 * #PADC#
 * 宝箱.ランキング用
 */

class RankingTreasure extends BaseMasterModel {
  const TABLE_NAME = "padc_ranking_treasures";
  const VER_KEY_GROUP = "padcrankingdung";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'dungeon_floor_id',
    'award_id',
    'prob',
    'amount',
  );

}
