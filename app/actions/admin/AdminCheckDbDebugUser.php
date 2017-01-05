<?php
/**
 * Admin用:デバッグユーザ
 */
class AdminCheckDbDebugUser extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'DebugUser';
		$columnNames	= array();
		$columns		= array_merge(array('id'),$className::getColumns());
		$datalist		= self::getDataList($className,$columnNames,$columns);

		$result = array(
			'format'	=> 'array',
			'データ'		=> $datalist,
		);
		return json_encode($result);
	}
}