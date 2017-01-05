<?php

/**
 * #PADC_DY#
 * 三星配置数据
 */
class DungeonReward extends BaseMasterModel {

    const TABLE_NAME = "padc_dungeon_rewards";
    const VER_KEY_GROUP = "dung";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'dungeon_id',
        'step',
        'required_star',
        'bonus_id1',
        'amount1',
        'piece_id1',
        'bonus_id2',
        'amount2',
        'piece_id2',
        'bonus_id3',
        'amount3',
        'piece_id3'
    );

}
