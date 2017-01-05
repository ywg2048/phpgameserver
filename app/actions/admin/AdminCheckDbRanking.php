<?php
/**
 * Admin用:ranking
 */
class AdminCheckDbRanking extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'Ranking';
		$offset = $params['offset'];
		$columnNames	= array();
		$columns		= $className::getColumns();
		$datalist		= self::getDataList($className,$columnNames,$columns,'ranking_number',array('limit' => 1000, 'offset' => $offset));

		$result = array(
			'format'	=> 'array',
			'データ'		=> $datalist,
		);
		return json_encode($result);
	}
}
