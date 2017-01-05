<?php
/**
 * Admin用:support_data
 */
class AdminCheckDbSupportData extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'SupportData';
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
