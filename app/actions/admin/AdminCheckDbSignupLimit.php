<?php
/**
 * Admin用:padc_signup_limits
 */
class AdminCheckDbSignupLimit extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_signup_limit";
	const DB_MODEL_NAME = "SignupLimit";
	
	public function action($params)
	{
		$columnNames	= array(
			'id'			=> 'ID',
			'date'			=> '日付',
			'num'			=> '登録数',
			'last_user_id'	=> '最後に登録されたユーザID',
		);
		$result = array(
			'format'	=> 'array',
			'データ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], self::DB_MODEL_NAME, $columnNames),
			),
		);
		return json_encode($result);
	}
}
