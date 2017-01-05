<?php
/**
 * Admin用:ダンジョンデータ
 */
class AdminCheckDbDungeon extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_dungeon";
	const DB_MODEL_NAME = "Dungeon";
	
	public function action($params)
	{
		$result = array(
			'format'	=> 'array',

			'ダンジョンデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'Dungeon'),
			),

			'ダンジョンフロアデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'DungeonFloor'),
			),
		);
		return json_encode($result);
	}
}