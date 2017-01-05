<?php

/**
 * #PADC_DY#
 * 用户参与活动
 */
class UserActivity extends BaseModel {

    const TABLE_NAME = "user_activities";
    // 状态
    const STATE_NONE = 0; // 未开放
    const STATE_CHALLENGE = 1; // 参与中
    const STATE_CLEAR = 2; // 参与完成
    const STATE_RECEIVED = 3; // 已领取奖励

    protected static $columns = array(
        'id',
        'user_id',
        'activity_id',
        'status',
        'ordered_at',
        'counts'
    );

    /**
     * 已经参与且当前有效的活动信息
     */
    public static function getActiveList($user_id, $user) {
        $active_list = array();
        $now = time();

        $user_activities = self::findAllBy(array(
            'user_id' => $user_id
        ));
        foreach ($user_activities as $user_activity) {
            $activity = Activity::get($user_activity->activity_id);
            if ($activity) {
                if ($activity->isEnabled($now)) {
                    if ($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CHARGE) {
                        if ($activity->checkCondition($user->tp_gold_period, $user->vip_lv) && $user_activity->status != UserActivity::STATE_RECEIVED) {
                            self::updateStatus($user_id, $activity->id, UserActivity::STATE_CLEAR);
                        }
                    }
                    if ($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CONSUM) {
                        if ($activity->checkCondition($user->tc_gold_period, $user->vip_lv) && $user_activity->status != UserActivity::STATE_RECEIVED) {
                            self::updateStatus($user_id, $activity->id, UserActivity::STATE_CLEAR);
                        }
                    }
                    $active_list[] = $user_activity;
                }
            }
        }

        return $active_list;
    }

    /**
     * 更新状态
     */
    public static function updateStatus($user_id, $activity_id, $update_status, $pdo = null) {
        if (empty($pdo)) {
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }

        $user_activity = self::findBy(array(
            'user_id' => $user_id,
            'activity_id' => $activity_id
        ), $pdo, true);

        $ordered_at = 0;
        if ($update_status == self::STATE_RECEIVED) {
            $ordered_at = BaseModel::timeToStr(time());
        }

        if ($user_activity) {
            if ($user_activity->status != $update_status) {
                $user_activity->status = $update_status;
                $user_activity->ordered_at = $ordered_at;
                $user_activity->updated_at = BaseModel::timeToStr(time());
                return $user_activity->update($pdo);
            }
        } else {
            $user_activity = new UserActivity();
            $user_activity->user_id = $user_id;
            $user_activity->activity_id = $activity_id;
            $user_activity->status = $update_status;
            $user_activity->ordered_at = $ordered_at;
            return $user_activity->create($pdo);
        }

        return false;
    }

    /**
     * 重置每日活动状态
     */
    public static function dailyReset($user_id, $activity_ids = array(), $pdo) {
        $sql = "UPDATE " . static::TABLE_NAME . " SET status = ? WHERE user_id = ?";
        $bind_param = array(self::STATE_CHALLENGE, $user_id);
        if (!empty($activity_ids)) {
            $sql .= " AND activity_id in (" . implode(',', $activity_ids) . ")";
        }

        return self::prepare_execute($sql, $bind_param, $pdo);
    }

}
