<?php

/**
 * #PADC_DY#
 *
 * 兑换魔法石商品
 */
class ExchangeMagicStoneProduct extends BaseMasterModel
{
	const TABLE_NAME = 'padc_exchange_magic_products';
	const VER_KEY_GROUP = "padcexchstone";
	const MEMCACHED_EXPIRE = 604800; // 1時間.

	protected static $columns = array(
			'id',
			'type',
			'rate',
			'cost',
			'bonus_id',
			'amount',
			'piece_id',
			'limit_num',
			'mark'
			
	);
	// 获取表信息
	public static function find($id, $pdo = null, $forUpdate = FALSE) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForShare();
		}
		$sql = "SELECT * FROM " . static::TABLE_NAME . " WHERE id = ?";
		if($forUpdate) {
			$sql .= " FOR UPDATE";
		}
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->bindParam(1, $id);
		$stmt->execute();
		$obj = $stmt->fetch(PDO::FETCH_CLASS);

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".$id), Zend_Log::DEBUG);
		}

		return $obj;
	}
	
	/**
	 * 获取交换商品列表
	 */
	public static function getActiveExchangeItems($user_id = null, $refresh = false, $pdoShare = null, $token = null)
	{
		$user_record = UserMagicStoneRecord::find($user_id);
		$auto_refresh = false;
		if ($user_record) {
			$exchange_refresh_time = BaseModel::strToTime($user_record->exchange_refresh_time);
			$now = time();
			$date_start = BaseModel::strToTime(date('Y-m-d'));
			foreach (GameConstant::getParam('ExchangeMagicStoneShopRefreshTime') as $refresh_time) {
				$refresh_time = $date_start + ($refresh_time - 1) * 3600;
				if ($refresh_time < $now && $exchange_refresh_time < $refresh_time && !$auto_refresh) {
					$auto_refresh = true;
					break;
				}
			}
			if (!$refresh && !$auto_refresh) {
				$activeItems = self::getUserExchangeItems($user_record, $pdoShare);
				if (!empty($activeItems)) {
					return $activeItems;
				}
			}
		}

		// 将所有产品按type分组
		$groupItems = array();
		$groupCount = array();
		$exchange_items = self::getAll();
		foreach ($exchange_items as $item) {
			$group = (int)$item->type;
			$groupItems[$group][] = $item;

			if (isset($groupCount[$group])) {
				$groupCount[$group] += 1;
			} else {
				$groupCount[$group] = 1;
			}
		}

		$activeItems = array();
		$user_exchange_list = array();
		$config = array(
				// GameConstant::EXCHANGE_PRODUCT_TYPE1 => GameConstant::getParam('ExchangeProductCount1'),
				// GameConstant::EXCHANGE_PRODUCT_TYPE2 => GameConstant::getParam('ExchangeProductCount2'),
				// GameConstant::EXCHANGE_PRODUCT_TYPE3 => GameConstant::getParam('ExchangeProductCount3'),

				GameConstant::EXCHANGE_PRODUCT_TYPE1 => GameConstant::getParam('ExchangeMagicStoneProductCount1'),
				GameConstant::EXCHANGE_PRODUCT_TYPE2 => GameConstant::getParam('ExchangeMagicStoneProductCount2'),
				GameConstant::EXCHANGE_PRODUCT_TYPE3 => GameConstant::getParam('ExchangeMagicStoneProductCount3'),
				GameConstant::EXCHANGE_PRODUCT_TYPE4 => GameConstant::getParam('ExchangeMagicStoneProductCount4'),
				GameConstant::EXCHANGE_PRODUCT_TYPE5 => GameConstant::getParam('ExchangeMagicStoneProductCount5'),
				GameConstant::EXCHANGE_PRODUCT_TYPE6 => GameConstant::getParam('ExchangeMagicStoneProductCount6'),
				GameConstant::EXCHANGE_PRODUCT_TYPE7 => GameConstant::getParam('ExchangeMagicStoneProductCount7'),
				GameConstant::EXCHANGE_PRODUCT_TYPE8 => GameConstant::getParam('ExchangeMagicStoneProductCount8')
		);
		foreach ($config as $type => $count) {
			for ($i = 0; $i < $count; $i++) {
				$sum_prob = self::getSumProbByProductType($type);
				$seed = mt_rand(1, $sum_prob);
				foreach($groupItems[$type] as $key => $val){
					$seed -= $val->rate;
					if($seed <= 0) {
						$activeItems[] = $val;
						$user_exchange_list[] = (int)$val->id;
						unset($groupItems[$type][$key]);
						break;
					}
				}
			}
		}
		if ($user_record) {
			if ($refresh) {
				$user_record->emptyExchangeRecord();
				//refresh_times 为0是免费首次刷新，故不用扣魔法石
				// if($_SERVER['SERVER_NAME'] != "192.168.0.212"){
					// 非本地环境需要扣魔法石
					if($user_record->refresh_times>8){
						throw new Exception(RespCode::UNKNOWN_ERROR, '次数已用完');
						
					}
					if($user_record->refresh_times >= 1){

						Shop::buyExchangeMagicStoneRefresh($user_id, $user_record->refresh_times,$token);
					}	
				// }
				//TODO 手动刷新需要增加刷新次数
				$user_record->refresh_times ++;
			}

			$user_record->exchange_list = json_encode($user_exchange_list);
			if ($auto_refresh) {
				$user_record->emptyExchangeRecord();
				$user_record->exchange_refresh_time = BaseModel::timeToStr(time());
				if($user_record->refresh_times>=1){
					//大于等于1说明已经手动刷新过，需要重置为1,否则还是为0不用修改
					$user_record->refresh_times = 1;
				}
			}
			$user_record->update(ENV::getDbConnectionForUserWrite($user_id));
		}
		return $activeItems;
	}

	/**
	 * 获取用户交换商品列表
	 */
	public static function getUserExchangeItems($user, $pdoShare = null)
	{
		if ($user && !empty($user->exchange_list)) {
			if (!$pdoShare) {
				$pdoShare = Env::getDbConnectionForShareRead();
			}

			$product_ids = array_map('intval', json_decode($user->exchange_list, true));
			$sql = "select * from " . static::TABLE_NAME . " where id in (" . implode(',', $product_ids) . ");";
			$stmt = $pdoShare->prepare($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
			if ($stmt->execute()) {
				$results = $stmt->fetchAll();
				if ($results) {
					return $results;
				}
			} else {
				throw new PadException(RespCode::UNKNOWN_ERROR, "find items failed");
			}
		}

		return array();
	}

	public static function getSumProbByProductType($type) {
		$key = MasterCacheKey::getSumProbByMagicStoneProductType($type);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			$pdo = Env::getDbConnectionForShare();
			$sql = "SELECT SUM(rate) FROM padc_exchange_magic_products WHERE type = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(1, $type);
			$stmt->execute();
			$obj = $stmt->fetch(PDO::FETCH_NUM);
			$value = $obj[0];
			if($value) {
				apc_store($key, $value, static::MEMCACHED_EXPIRE + static::add_apc_expire());
			}
		}
		return $value;
	}
}