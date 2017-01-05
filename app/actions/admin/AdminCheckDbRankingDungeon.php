<?php
/**
 * Admin用:ランキングダンジョンデータ
 */
class AdminCheckDbRankingDungeon extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_ranking_dungeon";
	const DB_MODEL_NAME = "RankingDungeon";
	
	public function action($params)
	{
		$result = array(
			'format'	=> 'array',

			'ダンジョンデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'RankingDungeon'),
			),

			'ダンジョンフロアデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'RankingDungeonFloor'),
			),
		);
		return json_encode($result);
	}
}