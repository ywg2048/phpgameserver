<?php
/**
 * Admin用:SubscriptionBonus
 */
class AdminCheckDbSubscriptionBonus extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'SubscriptionBonus';
		$columnNames	= array();
		$columns		= $className::getColumns();
		$datalist		= self::getDataList($className,$columnNames,$columns);

		$result = array(
			'format'	=> 'array',
			'データ'		=> $datalist,
		);
		return json_encode($result);
	}
}
