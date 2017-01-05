<?php
class UserTencentBonusHistory extends BaseModel {
	const TABLE_NAME = "user_tencent_bonus_histories";
	protected static $columns = array (
			'user_id',
			'tencent_bonus_id' 
	);
}
