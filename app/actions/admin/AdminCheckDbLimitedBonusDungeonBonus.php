<?php
/**
 * Admin用:limited_bonus_dungeon_bonus
 */
class AdminCheckDbLimitedBonusDungeonBonus extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'LimitedBonusDungeonBonus';
		$columnNames	= array();
		$columns		= null;
		$datalist		= self::getDataList($className,$columnNames,$columns);

		$result = array(
			'format'	=> 'array',
			'データ'		=> $datalist,
		);
		return json_encode($result);
	}
}
