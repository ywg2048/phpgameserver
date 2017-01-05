<?php
/**
 * Admin用:versions
 */
class AdminCheckDbVersion extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_version";
	const DB_MODEL_NAME = "Version";
	
	public function action($params)
	{
		$result = array(
			'format'	=> 'array',
				
			'アプリバージョン'	=> array(
					'format' => 'html',
					self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'AppliVersion'),
				),
				
			'マスターデータバージョン'	=> array(
					'format' => 'html',
					self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'Version'),
				),
		);
		return json_encode($result);
	}
}
