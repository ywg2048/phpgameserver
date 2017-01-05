<?php

/**
 * 排名关卡的分关设置
 */
class RankingFloor extends BaseMasterModel {

    const TABLE_NAME = "padc_ranking_floor";
    const VER_KEY_GROUP = "dung";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'name',
        'ranking_id',
        'ranking_floor_id',
        'prev_ranking_floor_id',
        'hard'
    );
}