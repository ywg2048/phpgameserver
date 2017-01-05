<?php
/**
 * Admin用:NgWord
 */
class AdminCheckDbNgWord extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'NgWord';
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
