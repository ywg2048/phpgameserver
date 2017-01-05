<?php
/**
 * Admin用:login_streak_bonus
 */
class AdminCheckDbLoginStreakBonus extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'LoginStreakBonus';
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
