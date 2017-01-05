<?php

/**
 * #PADC_DY#
 * 用户兑换记录
 */
class UserExchangeMagicStoneHistory extends BaseModel
{
	const TABLE_NAME = "user_exchange_magic_stone_history";

	protected static $columns = array(
		'id',
		'user_id',
		'product_id',
		'activity_type'
	);
}
