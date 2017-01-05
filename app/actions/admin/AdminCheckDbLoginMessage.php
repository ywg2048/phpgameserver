<?php
/**
 * Admin用:login_messages
 */
class AdminCheckDbLoginMessage extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_login_message";
	const DB_MODEL_NAME = "LoginMessage";
	
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
