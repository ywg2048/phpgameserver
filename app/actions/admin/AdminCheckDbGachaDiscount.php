<?php
/**
 * Admin用:ガチャ初回割引データ
 */
class AdminCheckDbGachaDiscount extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_gacha_discount";
	const DB_MODEL_NAME = "GachaDiscount";
	
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