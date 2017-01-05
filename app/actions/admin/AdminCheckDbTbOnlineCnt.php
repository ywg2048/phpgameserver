<?php
/**
 * Admin用:オンラインユーザ
 */
class AdminCheckDbTbOnlineCnt extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'TbOnlineCnt';
		$columnNames	= array();
		$columns		= null;
		$pdo			= Env::getDbConnectionForTlog();
		$datalist		= self::getDataList($className,$columnNames,$columns,null,null,$pdo);

		$result = array(
			'format'	=> 'array',
			'データ'		=> $datalist,
		);
		return json_encode($result);
	}
}