<?php
/**
 * Admin用:スキルデータ
 */
class AdminCheckDbSkill extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_skill";
	const DB_MODEL_NAME = "Skill";
	
	public function action($params)
	{
		$result = array(
			'format'	=> 'array',
			'データ'	=> array(
					'format' => 'html',
					self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], self::DB_MODEL_NAME)
				),
		);
		return json_encode($result);
	}
}