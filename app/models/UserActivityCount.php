<?php

/**
 * #PADC_DY#
 * 用户活动新增阶段统计表
 * 包括金币消费、体力购买、魔法石扭蛋、宠物进化、技能觉醒
 * @author: ZhangYu
 */
class UserActivityCount extends BaseModel {

    const TABLE_NAME = "user_activity_counts";

    protected static $columns = array(
        'id', // 主键
        'user_id', // 玩家id
        'coin_consum', // 累计消费金币
        'coin_accessed_at', // 金币消费数据操作时间
        'sta_buy_count', // 体力购买次数
        'sta_accessed_at', // 体力购买数据操作时间
        'gacha_count', // 扭蛋累计次数
        'gacha_accessed_at', // 扭蛋数据操作时间
        'card_evo_count', // 卡牌进化次数
        'card_evo_accessed_at', // 卡牌进化数据操作时间
        'skill_awake_count', // 技能觉醒次数
        'skill_awake_accessed_at', // 技能觉醒累计操作时间
    );

    public static function getUserActivityCount($user_id, $pdo = null) {
        if ($pdo == null) {
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }
        $user_activity_count = static::findBy(array(
            'user_id' => $user_id
        ), $pdo, FALSE);

        if (empty($user_activity_count)) {
            $user_activity_count = new static();
            $user_activity_count->user_id = $user_id;
            $user_activity_count->sta_buy_count = 0;
            $user_activity_count->sta_accessed_at = null;
            $user_activity_count->coin_consum = 0;
            $user_activity_count->coin_accessed_at = null;
            $user_activity_count->gacha_count = 0;
            $user_activity_count->gacha_accessed_at = null;
            $user_activity_count->card_evo_count = 0;
            $user_activity_count->card_evo_accessed_at = null;
            $user_activity_count->skill_awake_count = 0;
            $user_activity_count->skill_awake_accessed_at = null;
            $user_activity_count->create($pdo);
        }
        return $user_activity_count;
    }

    public function addCounts($activity_type, $used_coin = 0, $gacha_cnt = 0) {
        $now = time();
        $activity = Activity::getByType($activity_type);
        if ($activity) {
            if ($activity_type == Activity::ACTIVITY_TYPE_STA_BUY_COUNT) {
                if ($this->sta_accessed_at >= $activity->begin_at && $this->sta_accessed_at <= $activity->finish_at) {
                    $this->sta_buy_count += 1;
                } else {
                    $this->sta_buy_count = 1;
                }
                $this->sta_accessed_at = static::timeToStr($now);
            } elseif ($activity_type == Activity::ACTIVITY_TYPE_COIN_CONSUM) {
                if ($this->coin_accessed_at >= $activity->begin_at && $this->coin_accessed_at <= $activity->finish_at) {
                    $this->coin_consum += $used_coin;
                } else {
                    $this->coin_consum = $used_coin;
                }
                $this->coin_accessed_at = static::timeToStr($now);
            } elseif ($activity_type == Activity::ACTIVITY_TYPE_SKILL_AWAKE_COUNT) {
                if ($this->skill_awake_accessed_at >= $activity->begin_at && $this->skill_awake_accessed_at <= $activity->finish_at) {
                    $this->skill_awake_count += 1;
                } else {
                    $this->skill_awake_count = 1;
                }
                $this->skill_awake_accessed_at = static::timeToStr($now);
            } elseif ($activity_type == Activity::ACTIVITY_TYPE_CARD_EVO_COUNT) {
                if ($this->card_evo_accessed_at >= $activity->begin_at && $this->card_evo_accessed_at <= $activity->finish_at) {
                    $this->card_evo_count += 1;
                } else {
                    $this->card_evo_count = 1;
                }
                $this->card_evo_accessed_at = static::timeToStr($now);
            } elseif ($activity_type == Activity::ACTIVITY_TYPE_GACHA_COUNT) {
                if ($this->gacha_accessed_at >= $activity->begin_at && $this->gacha_accessed_at <= $activity->finish_at) {
                    $this->gacha_count += $gacha_cnt;
                } else {
                    $this->gacha_count = $gacha_cnt;
                }
                $this->gacha_accessed_at = static::timeToStr($now);
            }
        }
    }
}
