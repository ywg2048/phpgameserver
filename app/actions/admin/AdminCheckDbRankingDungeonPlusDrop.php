<?php
/**
 * Admin用:ranking_dungeon_plus_drop
 */
class AdminCheckDbRankingDungeonPlusDrop extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_ranking_dungeon_plus_drop";
	const DB_MODEL_NAME = "RankingDungeonPlusDrop";
	
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
