<?php
/**
 * 購入したダンジョン履歴.
 */

class UserBuyDungeonHistory extends BaseModel {
  const TABLE_NAME = "user_buy_dungeon_history";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'user_id',
    'dungeon_id',
    'expire_at',
    'buy_at',
    'before_coin',
    'after_coin',
  );

}
