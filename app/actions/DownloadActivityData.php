<?php

/**
 * #PADC_DY#
 * 活动数据
 */
class DownloadActivityData extends BaseAction {

    // http://pad.localhost/api.php?action=download_activity_data&pid=1&sid=1
    const MEMCACHED_EXPIRE = 86400; // 24時間.
    const MAIL_RESPONSE = FALSE;
    const ENCRYPT_RESPONSE = FALSE;
    const CONDITION_NONE = 0;   //没有条件
    const CONDITION_TOTAL_CHARGE = 1; //累计充值
    const CONDITION_TOTAL_CONSUM = 2; //累计消费
    const CONDITION_CHARGE_DAY = 3;   //连续充值天数
    const CONDITION_TOTALI_POWER = 4; //战斗力

    public function action($params) {
        $key = MasterCacheKey::getDownloadActivityData();
        $value = apc_fetch($key);
        if (FALSE === $value) {
            $value = DownloadMasterData::find(DownloadMasterData::ID_ACTIVITY)->gzip_data;
            apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
        }
        return $value;
    }

    //获取活动的条件的类型跟数值
    public static function getConditionTypeAndValue($activity){
        $condition = $activity->getCondition();
        $type = '';
        $val = 0;
        if (count($condition) > 0) {
            $condition_keys = array_keys($condition);
            $type = $condition_keys[0];                       //活动获取条件类型
            $val = $condition[$type];                       //活动获取条件数值
        }

		// 限时消费积分活动暂时不需要返回给前端奖励发放条件
		if ($activity->activity_type == Activity::ACTIVITY_TYPE_CONSUM_POINT_RANKING) {
			$type = '';
			$val = 0;
		}

        if (isset($condition['tp_gold_period'])) {
            $val = (int)$condition['tp_gold_period'] / GameConstant::GOLD_TO_MONEY_RATE;
            $type = 'tp_gold_period';
        }
        /*
           elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE && isset($condition['pgold'])) {
            $val = (int)$condition['pgold'];
            $type = 'pgold';
        } elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED && isset($condition['count_p6'])) {
            $val = (int)$condition['count_p6'];
            $type = 'count_p6';
        } elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CONSUM && isset($condition['tc_gold_period'])) {
            $val = (int)$condition['tc_gold_period'];
            $type = 'tc_gold_period';
        } elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_POWER && isset($condition['power'])) {
            $val = (int)$condition['power'];
            $type = 'power';
        }
        */
        return array($type,$val); // 返回一个数组
    }

    //普通模板的活动构造
    public static function templateActivity($activity){
        $act = array();
        $act['id'] = (int)$activity->id;                    //每个活动唯一的id
        $act['des'] = $activity->description;                    //每个活动描述

        //活动完成的条件
        $condition =  self::getConditionTypeAndValue($activity);
        $act['conditionType'] = $condition[0];
        $act['conditionValue'] = $condition[1];

        $act['vipLv'] = (int)$activity->vip_lv;
        $act['bottom'] = (int)$activity->bottom;                //这个模板的底部是否显示

        //奖励物品
        $act['bid1'] = (int)$activity->bonus_id1;
        $act['a1'] = (int)$activity->amount1;
        $act['pid1'] = (int)$activity->piece_id1;

        $act['bid2'] = (int)$activity->bonus_id2;
        $act['a2'] = (int)$activity->amount2;
        $act['pid2'] = (int)$activity->piece_id2;

        $act['bid3'] = (int)$activity->bonus_id3;
        $act['a3'] = (int)$activity->amount3;
        $act['pid3'] = (int)$activity->piece_id3;

        $act['bid4'] = (int)$activity->bonus_id4;
        $act['a4'] = (int)$activity->amount4;
        $act['pid4'] = (int)$activity->piece_id4;

        $act['bat'] = $activity->begin_at;
        $act['fat'] = $activity->finish_at;
        $act['exchange_times'] = (int)$activity->exchange_times;

        return $act;
    }

    public static function arrangeColumnsTest($activities){
        $mapper = array();
        $typeFlagArr = array();   //记录type类型的活动是否构造了
        foreach ($activities as $activity) {
            if(!isset($typeFlagArr[$activity->activity_type])){   //如果个type的活动未构造数组
                $typeFlagArr[$activity->activity_type] = true;
                $curActList = array();
                foreach ($activities as $subAct) {   //去把所有type类型的活动数据构造成一个子数组（每个活动独立的属性）
                    if( $subAct->activity_type == $activity->activity_type){
                        $curActList[] = self::templateActivity($subAct);
                    }
                }
                $mapper[] = array(    //构造这个活动的数组，这里变量表示这个type活动的通用数据
                    'type' => (int)$activity->activity_type,                          //活动type
                    'template' => (int)$activity->template,                                //活动的模板
                    'tipPos' => (int)$activity->tip_pos,                                    //活动说明位置(0,1,2,3) => (无，左，中，右)
                    'tip' => $activity->tip,                                           //活动说明
                    'rt' => $activity->reward_text,                                    //活动注意事项 Tips
                    'timePos'=> (int)$activity->time_pos,                                   //活动倒计时位置(0,1,2,3) => (无，左，中，右)
                    'bgImg' => $activity->bg_img,                                       //活动背景图片
                    'iconImg' => $activity->icon_img,                                  //活动icon图片
                    'btnImg1' => $activity->btn_img1,                                  //活动按钮图片
                    'btnImg2' => $activity->btn_img2,
                    'btnImg3' => $activity->btn_img3,
                    'btnSkip' => (int)$activity->btn_skip,                              //按钮点击跳转位置（0,1,2）=> (无，商店，分享)
                    'show_condition' => (int)$activity->show_condition,                 // 是否显示进度（例如连续充值6天，3/6）
                    'actList' => $curActList,
                );
            }
        }
        return $mapper;
    }

    public static function arrangeColumns($activities) {
        $mapper = array();
        $now = time();

        foreach ($activities as $activity) {
            //活动时间现在由客户端判断，服务器把数据都发送过去
            // if ($activity->isEnabled($now)) {
            $condition = $activity->getCondition();
            $val = 0;
            if ($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CHARGE && isset($condition['tp_gold_period'])) {
                $val = (int)$condition['tp_gold_period'] / GameConstant::GOLD_TO_MONEY_RATE;
            } elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE && isset($condition['pgold'])) {
                $val = (int)$condition['pgold'];
            } elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED && isset($condition['count_p6'])) {
                $val = (int)$condition['count_p6'];
            } elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_TOTAL_CONSUM && isset($condition['tc_gold_period'])) {
                $val = (int)$condition['tc_gold_period'];
            } elseif ($activity->activity_type == Activity::ACTIVITY_TYPE_POWER && isset($condition['power'])) {
                $val = (int)$condition['power'];
            }
            $mapper[] = array(
                'id' => $activity->id,
                'type' => (int)$activity->activity_type,
                'name' => $activity->name,
                'desc' => $activity->description,
                'rt' => $activity->reward_text,
                'ri' => $activity->reward_img,
                'rc' => $condition,
                'val' => $val,
                'lv' => (int)$activity->vip_lv,
                'bid1' => (int)$activity->bonus_id1,
                'a1' => (int)$activity->amount1,
                'pid1' => (int)$activity->piece_id1,
                'bid2' => (int)$activity->bonus_id2,
                'a2' => (int)$activity->amount2,
                'pid2' => (int)$activity->piece_id2,
                'bid3' => (int)$activity->bonus_id3,
                'a3' => (int)$activity->amount3,
                'pid3' => (int)$activity->piece_id3,
                'bid4' => (int)$activity->bonus_id4,
                'a4' => (int)$activity->amount4,
                'pid4' => (int)$activity->piece_id4,
                'bat' => $activity->begin_at,
                'fat' => $activity->finish_at,
                'exchange_times' => $activity->exchange_times
            );
        }
        return $mapper;
    }

}
