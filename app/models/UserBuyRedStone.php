<?php
/**
 * RedStone購入履歴
 */
class UserBuyRedStone extends BaseModel {
  const TABLE_NAME = "user_buy_red_stone";
  const HAS_UPDATED_AT = FALSE;
  const HAS_CREATED_AT = FALSE;
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'see_at',
    'buy_at',
  );

}
