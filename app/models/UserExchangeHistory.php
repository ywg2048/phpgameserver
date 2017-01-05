<?php

/**
 * #PADC_DY#
 * 用户兑换记录
 */
class UserExchangeHistory extends BaseModel
{
	const TABLE_NAME = "user_exchange_history";

	protected static $columns = array(
		'id',
		'user_id',
		'product_id'
	);
}
