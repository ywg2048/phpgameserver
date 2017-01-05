<?php

// #PADC_DY#
class UserDailyCounts extends BaseModel{
    const TABLE_NAME = "user_gacha_count";
    const VER_KEY_GROUP = "gachacount";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'user_id',
        'ip_daily_count',
        'piece_daily_count',
        'daily_reset_at'
    );

}