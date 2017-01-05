<?php

/**
 * #PADC_DY#
 * 活动时间限制配置
 */
class LimitEventsTime extends BaseMasterModel {

	const TABLE_NAME = "limit_events_time";
	const LUCKY_DIAL = 1;//幸运转盘
	const MAGIC_STONE_SHOP = 2;//限时魔法石商店
	const MAGIC_STONE_DIAL = 3;//魔法石转盘

	protected static $columns = array(
		'id',
		'events_type',
		'name',
		'description',
		'begin_at',
		'finish_at',
		'del_flg'
	);

	/**
	 * 是否在有效期内
	 */
	public static function isEnabled($time,$events_id) {
		// 活动无效
		$activity =  self::findBy(array('id'=>$events_id));
		if ($activity->del_flg) {
			return false;
		}

		// 是否在有效期内
		$begin_at = static::strToTime($activity->begin_at);
		$finish_at = static::strToTime($activity->finish_at);
		if ($begin_at > 0 && $finish_at > 0) {
			if ($begin_at <= $time && $time <= $finish_at) {
				return true;
			}
			return false;
		}

		return true;
	}

	/**
	 * 根据类型获取单个活动
	 */
	public static function getByType($type) {
		$activities = self::getAllBy(array(
			'events_type' => $type,
			'del_flg' => 0
		));

		$now = time();
		foreach ($activities as $activity) {
			if ($activity->isEnabled($now)) {
				return $activity;
			}
		}

		return null;
	}
	/**
	*根据type获取活动id
	*/
	public static function getTypeById($type){
		$events = self::getAllBy(array(
			'events_type' => $type,
			'del_flg' => 0
		));
		$now = time();
		foreach ($events as $event) {
			if($event->events_type == $type){
				if($event->isEnabled($now,$event->id)){
					return $event->id;
				}	
			}
		}
		return null;
	}
	
}
