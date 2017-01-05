<?php

// #PADC_DY#
class RecommendDeck extends BaseMasterModel {
    const TABLE_NAME = "padc_recommend_decks";
    const VER_KEY_GROUP = "padcrd";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'description',
        'leader',
        'card_id1',
        'card_id2',
        'card_id3',
        'card_id4',
    );
}