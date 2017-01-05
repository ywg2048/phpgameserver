<?php
/**
 * Admin用:user_devices
 */
class AdminCheckDbUserDevice extends AdminBaseAction
{
	public function action($params)
	{
		$className		= 'UserDevice';
		$columnNames	= array(
			'id'			=> 'ID',
			'type'			=> 'デバイスタイプ',
			'uuid'			=> 'UUID',
			'dbid'			=> 'DBID',
			'oid'			=> 'オープンID',
			'ptype'			=> 'プラットフォームタイプ',
			'version'		=> 'バージョン',
			'created_at'	=> 'created_at',
			'updated_at'	=> 'updated_at',
		);
		$columns		= array(
			'id',
			'type',
			'uuid',
			'dbid',
			'oid',
			'ptype',
			'version',
			'created_at',
			'updated_at',
		);
		$datalist		= self::getDataList($className,$columnNames,$columns);
		
		$result = array(
			'format'	=> 'array',
			'データ'		=> $datalist,
		);
		return json_encode($result);
	}
}
