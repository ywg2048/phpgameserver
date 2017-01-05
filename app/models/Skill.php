<?php
/**
 * スキル.
 */

class Skill extends BaseMasterModel {
  const TABLE_NAME = "skills";
  const VER_KEY_GROUP = "skl";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  const TYPE_EGGUP = 53;   // 53:LS:卵のドロップ率が@1倍になる
  const TYPE_COINUP = 54;  // 54:LS:入手コインが@1倍になる

  protected static $columns = array(
    'id',
    'name',
    'help',
    'sktp',
    'skp1',
    'skp2',
    'skp3',
    'skp4',
    'skp5',
    'skp6',
    'skp7',
    'skp8',
    'lcap',
    'ctbs',
    'ctel',
  );

}
