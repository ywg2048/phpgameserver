<?php

/**
 * #PADC_DY# user roadmap unlock config
 */
class Roadmap extends BaseMasterModel
{
    const TABLE_NAME = "padc_roadmap";
    const VER_KEY_GROUP = "roadmap";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    const UNLOCK_TYPE_DUNGEONFLOOR = 2;  // unlock type dungeon floor
    const UNLOCK_TYPE_GAME_FUNCTION = 1; // 游戏内功能
    const UNLOCK_ID_BUY_DUNGEON = 7; // 购买dungeon的解锁ID为7（DB中数字）

    protected static $columns = array(
        'id',
        'lv',
        'unlock_type',
        'unlock_id',
        'unlock_icon',
        'description'
    );

}