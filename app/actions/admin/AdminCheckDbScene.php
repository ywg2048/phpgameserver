<?php
/**
 * Admin用:カードデータ
 */
class AdminCheckDbScene extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_scene";
	const DB_MODEL_NAME = "Scene";
	
	public function action($params)
	{
		$result = array(
			'format'	=> 'array',

			'シーンデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'Scene'),
			),

			'シーンパーツデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'ScenePart'),
			),
			
			'SPシーンデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'SpScene'),
			),
			
			'SPシーンパーツデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'SpScenePart'),
			),
		);
		return json_encode($result);
	}
}