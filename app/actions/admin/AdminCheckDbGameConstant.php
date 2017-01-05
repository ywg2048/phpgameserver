<?php
/**
 * Admin用:game_constants
 */
class AdminCheckDbGameConstant extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'GameConstant';
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
