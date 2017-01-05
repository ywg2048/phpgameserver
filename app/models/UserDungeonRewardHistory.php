<?php

/**
 * #PADC_DY#
 * 三星奖励领取记录
 */
class UserDungeonRewardHistory extends BaseModel {

    const TABLE_NAME = "user_dungeon_reward_history";

    private $reward_array = null;
    protected static $columns = array(
        'id',
        'user_id',
        'dungeon_id',
        'step_reward_gained',
        'step1_gained_at',
        'step2_gained_at',
        'step3_gained_at'
    );

    /**
     * #PADC_DY#
     * 获取领取记录
     * @params $user_id
     * @params $dungeon_id
     */
    public static function getByUserId($user_id, $dungeon_id, $pdo = null) {
        $obj = self::findBy(array(
            'user_id' => $user_id,
            'dungeon_id' => $dungeon_id
        ), $pdo);
        if ($obj) {
            $obj->reward_array = $obj->getReward();
        } else {
            $obj = new self();
            $obj->user_id = $user_id;
            $obj->dungeon_id = $dungeon_id;
            $obj->step_reward_gained = json_encode(array());
            $obj->step1_gained_at = '0000-00-00 00:00:00';
            $obj->step2_gained_at = '0000-00-00 00:00:00';
            $obj->step3_gained_at = '0000-00-00 00:00:00';
            $obj->reward_array = array();
            $obj->create($pdo);
        }

        return $obj;
    }

    /**
     * #PADC_DY#
     * 增加领取记录
     */
    public function addReward($step) {
        if (!in_array($step, $this->reward_array)) {
            $this->reward_array[] = $step;
            $this->reward_array = array_unique($this->reward_array);
            sort($this->reward_array);
            $this->step_reward_gained = json_encode($this->reward_array);
            switch ($step) {
                case 1:
                    $this->step1_gained_at = self::timeToStr(time());
                    break;
                case 2:
                    $this->step2_gained_at = self::timeToStr(time());
                    break;
                case 3:
                    $this->step3_gained_at = self::timeToStr(time());
                    break;
            }
        }
    }

    /**
     * #PADC_DY#
     * 判断是否已领取
     */
    public function checkReward($step) {
        return in_array($step, $this->reward_array);
    }

    /**
     * #PADC_DY#
     * 获取领取记录数
     */
    public function getRewardCount() {
        return count($this->reward_array);
    }

    /**
     * #PADC_DY#
     * 获取领取数据
     */
    public function getRewardArray() {
        return $this->reward_array;
    }
    
    /**
     * #PADC_DY#
     * 格式化step_reward_gained成整形数组
     */
    public function getReward() {
        return array_map('intval', json_decode($this->step_reward_gained, true));
    }

}
