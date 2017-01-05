<?php
/**
 * レベルアップ
 */

class LevelUp extends BaseMasterModel {
  const TABLE_NAME = "levelup_experience";
  const VER_KEY_GROUP = "lvup";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  // #PADC_DY# ----------begin----------
  const MAX_USER_LEVEL = 999;
  // #PADC_DY# ----------end----------

  protected static $columns = array(
    'id',
    'level',
    'required_experience',
    'bonus_id',
    'amount',
  );
  
  
}
