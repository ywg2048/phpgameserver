<?php
/**
 * Admin用:login_total_count_bonuses
 */
class AdminCheckDbLoginTotalCountBonus extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_login_total_count_bonus";
	const DB_MODEL_NAME = "LoginTotalCountBonus";
	
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
