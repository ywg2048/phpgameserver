<?php
/**
 * Admin用:dungeon_plus_drop
 */
class AdminCheckDbDungeonPlusDrop extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_dungeon_plus_drop";
	const DB_MODEL_NAME = "DungeonPlusDrop";
	
	public function action($params)
	{
		$result = array(
			'format'	=> 'array',
			'データ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], self::DB_MODEL_NAME),
			),
		);
		return json_encode($result);
	}
}
