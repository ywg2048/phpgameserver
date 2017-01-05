<?php
/**
 * Admin用:チュートリアル用カードデータ
 */
class AdminCheckDbTutorialCard extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'TutorialCard';
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