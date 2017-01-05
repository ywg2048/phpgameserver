<?php

/**
 * Created by PhpStorm.
 * User: dy_space_01
 * Date: 2016/7/28
 * Time: 10:42
 */
class PassiveSkill extends BaseMasterModel{
    const TABLE_NAME = "padc_passive_skills";
    const VER_KEY_GROUP = "pskill";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    const PASSIVE_SKILL_HP_ID = 1; // HP强化的觉醒技能id
    const PASSIVE_SKILL_ATK_ID = 2;// 攻击力觉醒技能id
    const PASSIVE_SKILL_REC_ID = 3;// 恢复力强化觉醒技能id

    const PASSIVE_SKILL_HP_AMOUNT = 200;// HP强化觉醒技能强化量
    const PASSIVE_SKILL_ATK_AMOUNT = 100;// ATK强化觉醒技能强化量
    const PASSIVE_SKILL_REC_AMOUNT = 50;// REC强化觉醒技能强化量

    protected static $columns = array(
        'id',
        'pskill_id',
        'name',
        'desc',
        'awake_piece_id',
        'num',
        'cost',
    );
}