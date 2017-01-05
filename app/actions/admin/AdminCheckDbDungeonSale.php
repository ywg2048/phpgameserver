<?php
/**
 * Admin用:ダンジョンSaleデータ
 */
class AdminCheckDbDungeonSale extends AdminBaseAction
{
	const ADMIN_FROM_ACTION_NAME = "admin_check_db_dungeon_sale";
	const DB_MODEL_NAME = "DungeonSale";
	
	public function action($params)
	{
		$result = array(
			'format'	=> 'array',

			'ダンジョンSaleデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'DungeonSale'),
			),

			'ダンジョンSaleCommodityデータ'	=> array(
				'format' => 'html',
				self::getDataListEditForm(self::ADMIN_FROM_ACTION_NAME, $params['request_type'], 'DungeonSaleCommodity'),
			),
		);
		return json_encode($result);
	}
}