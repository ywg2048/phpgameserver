<?php

/**
 * Class UserCarnivalMission
 *
 * 新手嘉年华期间用户的需要完成的任务
 */
class UserCarnivalMission extends BaseModel{
    const TABLE_NAME = "user_carnival_mission";

    const STATUS_FORBIDDEN     = 0;  //任务不开放--登录的天数为判断标准
    const STATUS_UNFINISHED    = 1;  //任务开放，但未完成
    const STATUS_FINISHED      = 2;  //任务完成，奖励未领取
    const STATUS_GET_AWARD     = 3;  //已领取奖励

    protected static $columns = array(
        'id',
        'user_id',
        'carnival_id',
        'status',
        'created_at',
        'updated_at',
    );

    /**
     * 获取用户嘉年华任务中，任务id在数组$carnival_ids中，且状态为$status的记录
     * @param $user_id
     * @param $carnival_ids
     * @param int $status
     * @return array|null
     */
    public static function getMission($user_id,$carnival_ids,$status = UserCarnivalMission::STATUS_UNFINISHED)
    {
        if(!is_array($carnival_ids)){
            $carnival_ids = array($carnival_ids);
        }

        $pdo = Env::getDbConnectionForUserRead($user_id);

        $sql = 'SELECT * FROM ' . static::TABLE_NAME . ' WHERE user_id = '.$user_id.' AND status = '.$status.' AND carnival_id in ('. str_repeat('?,', count($carnival_ids) - 1) . '?) ORDER BY carnival_id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS,get_called_class());
        $stmt->execute($carnival_ids);
        $objs = $stmt->fetchAll();

        if(!empty($objs)){
            return $objs;
        }else{
            return null;
        }
    }

    /**
     * 获取用户嘉年华任务中，carnival_id在数组$carnival_ids中的记录
     * @param $user_id
     * @param $carnival_ids
     * @param int $status
     * @return array
     */
    public static function getUserCarnivalByIds($user_id,$carnival_ids,$status = self::STATUS_UNFINISHED)
    {
        $pdo = Env::getDbConnectionForUserRead($user_id);
        if(!is_array($carnival_ids)){
            $carnival_ids = array($carnival_ids);
        }

        $sql = 'SELECT * FROM '.static::TABLE_NAME.' WHERE status = ' .$status. ' AND user_id = '.$user_id .' AND carnival_id in ('.str_repeat('?,',count($carnival_ids)-1).'?)';
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS,get_called_class());
        $stmt->execute($carnival_ids);
        $user_missions = $stmt->fetchAll();
        if(empty($user_missions)){
            return array();
        }
        return $user_missions;
    }

    /**
     * 返回用户任务为完成状态的数量
     * @param $user_id
     * @return string
     */
    public static function missionFinishedCount($user_id)
    {
        $pdo_user = Env::getDbConnectionForUserRead($user_id);

        $sql  = 'SELECT count(*) FROM ' .UserCarnivalMission::TABLE_NAME .' WHERE user_id = '.$user_id.' AND status = '.UserCarnivalMission::STATUS_FINISHED;
        $stmt = $pdo_user->prepare($sql);
        $stmt->execute();
        $count = $stmt->fetchColumn(0);

        return $count;
    }

}