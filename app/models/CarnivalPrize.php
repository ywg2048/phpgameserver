<?php

/**
 * Class CarnivalPrize
 *
 * 嘉年华的奖品列表
 */
class CarnivalPrize extends BaseMasterModel{
    const TABLE_NAME = "padc_carnival_prize";
    const VER_KEY_GROUP = "gcarnival";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    const MISSION_GROUP_ID_FOR_NONE       = 0;    //根据每日任务完成的进度，进行解锁的任务
    const MISSION_GROUP_ID_FOR_FIRST_DAY  = 1;    //第一天的任务
    const MISSION_GROUP_ID_FOR_SECOND_DAY = 2;    //第二天的任务
    const MISSION_GROUP_ID_FOR_THIRD_DAY  = 3;    //第三天的任务
    const MISSION_GROUP_ID_FOR_FOURTH_DAY = 4;    //第四天的任务
    const MISSION_GROUP_ID_FOR_FIFTH_DAY  = 5;    //第五天的任务


    const CONDITION_TYPE_WHOLE_TARGET_PRIZE    = 0;        //全目标奖励
    const CONDITION_TYPE_DAILY_LOGIN           = 1;        //登录奖励
    const CONDITION_TYPE_USER_RANK             = 2;        //玩家的等级
    const CONDITION_TYPE_DECK_POWER            = 3;        //战斗力
    const CONDITION_TYPE_CARD_COMPOSITE        = 4;        //宠物强化
    const CONDITION_TYPE_CARD_EVOLVE           = 5;        //宠物进化
    const CONDITION_TYPE_DAILY_SHARE           = 6;        //每日分享
    const CONDITION_TYPE_FRIEND_NUMBER         = 7;        //朋友的数量
    const CONDITION_TYPE_DUNGEON_CLEAR         = 8;        //关卡通关
    const CONDITION_TYPE_STAMINA_BUY           = 9;        //体力累积购买
    const CONDITION_TYPE_DAILY_GACHA_GOLD      =10;        //钻石扭蛋

    protected static $columns = array(
        'id',
        'condition_type',
        'open_condition',
        'group_id',
        'mission_id',
        'bonus_id1',
        'amount1',
        'piece_id1',
        'bonus_id2',
        'amount2',
        'piece_id2',
        'bonus_id3',
        'amount3',
        'piece_id3',
        'reward_text',
        //'created_at',
        //'updated_at',
    );

    /**
     * 获取嘉年华任务中，组ID为$group_id的记录
     * @param $group_id
     * @return array
     */
    public static function getCarnivalIdByGroupId($group_id)
    {
        $group_ids = array();

        $carnival_prizes = CarnivalPrize::getAllBy(array('group_id'=>$group_id));
        foreach($carnival_prizes as $carnival_prize){
            $group_ids[] = $carnival_prize->id;
        }
        return $group_ids;
    }

    /**
     * 获取嘉年华任务中，condition_type为$condition_type的记录
     * @param $condition_type
     * @return array
     */
    public static function getIdsByConditionType($condition_type)
    {
        $carnival_ids = array();
        $carnival_missions = CarnivalPrize::getAll();
        foreach($carnival_missions as $carnival_mission){
            if($carnival_mission->condition_type == $condition_type){
                $carnival_ids[] = $carnival_mission->id;
            }
        }
        return $carnival_ids;
    }

    /**
     * 得到该奖励，需要完成的任务的类型描述
     * @param $carnival_id
     * @return string
     */
    public static function getMissionDescription($carnival_id)
    {
        $carnival_prize = CarnivalPrize::get($carnival_id);
        $open_condition = json_decode($carnival_prize->open_condition,true);
        $condition_type = $carnival_prize->condition_type;

        $mtype = '';
        $desc = '';
        switch($condition_type){
            case self::CONDITION_TYPE_WHOLE_TARGET_PRIZE:
                $mtype = '进度完成的奖励';
                $desc  = '进度完成:'.$open_condition['progress'].'%';
                break;
            case self::CONDITION_TYPE_DAILY_LOGIN:
                $mtype = '登录奖励';
                $desc  = '登录'.$open_condition['login_day'].'天';
                break;
            case self::CONDITION_TYPE_USER_RANK:
                $mtype = '等级奖励';
                $desc  = '等级达到'.$open_condition['lv'].'级';
                break;
            case self::CONDITION_TYPE_DECK_POWER:
                $mtype = '战斗力奖励';
                $desc  = null;
                break;
            case self::CONDITION_TYPE_CARD_COMPOSITE:
                $mtype = '宠物强化';
                $desc  = '宠物强化'.$open_condition['composite'].'次';
                break;
            case self::CONDITION_TYPE_CARD_EVOLVE:
                $mtype = '宠物进化';
                $desc  = '宠物进化'.$open_condition['evolve'].'次';
                break;
            case self::CONDITION_TYPE_DAILY_SHARE:
                $mtype = '每日分享';
                $desc  = null;
                break;
            case self::CONDITION_TYPE_FRIEND_NUMBER:
                $mtype = '朋友数量';
                $desc  = '朋友数量'.$open_condition['friend'].'个';
                break;
            case self::CONDITION_TYPE_DUNGEON_CLEAR:
                $mtype = '通关奖励';
                $dungeon_floor = DungeonFloor::get($open_condition['dungeon']);
                $dungeon = Dungeon::get($dungeon_floor->dungeon_id);
                $desc = '通关'.$dungeon->name;
                break;
            case self::CONDITION_TYPE_STAMINA_BUY:
                $mtype = '体力购买';
                $desc  = '体力购买'.$open_condition['stamina'].'次';
                break;
            case self::CONDITION_TYPE_DAILY_GACHA_GOLD:
                $mtype = '钻石扭蛋';
                $desc  = '钻石扭蛋'.$open_condition['gacha'].'次';
                break;
        }
        return array('mtype'=>$mtype,'desc'=>$desc);
    }
}

