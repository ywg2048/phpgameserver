<?php

// #PADC_DY#
class RecommendDeckLeader extends BaseMasterModel {

    const TABLE_NAME = "padc_recommend_deck_leaders";
    const VER_KEY_GROUP = "padcrdl";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'piece_id',
        'deck_id',
        'priority',
        'pos',
    );

}