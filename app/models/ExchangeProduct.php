<?php

/**
 * #PADC_DY#
 *
 * 兑换的商品
 */
class ExchangeProduct extends BaseMasterModel
{
	const TABLE_NAME = 'padc_exchange_products';
	const VER_KEY_GROUP = "padcexch";
	const MEMCACHED_EXPIRE = 604800; // 1時間.

	protected static $columns = array(
			'id',
			'type',
			'rate',
			'exchange_point',
			'bonus_id',
			'amount',
			'piece_id',
			'limit_num',
			'favor'
	);

	/**
	 * 获取交换商品列表
	 */
	public static function getActiveExchangeItems($user_id = null, $refresh = false, $pdoShare = null, $token = null)
	{
		$user = User::find($user_id);
		$auto_refresh = false;
		if ($user) {
			$exchange_refresh_time = BaseModel::strToTime($user->exchange_refresh_time);
			$now = time();
			$date_start = BaseModel::strToTime(date('Y-m-d'));
			foreach (GameConstant::getParam('ExchangeRefreshTime') as $refresh_time) {
				$refresh_time = $date_start + ($refresh_time - 1) * 3600;
				if ($refresh_time < $now && $exchange_refresh_time < $refresh_time && !$auto_refresh) {
					$auto_refresh = true;
					break;
				}
			}
			if (!$refresh && !$auto_refresh) {
				$activeItems = self::getUserExchangeItems($user, $pdoShare);
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
				GameConstant::EXCHANGE_PRODUCT_TYPE1 => GameConstant::getParam('ExchangeProductCount1'),
				GameConstant::EXCHANGE_PRODUCT_TYPE2 => GameConstant::getParam('ExchangeProductCount2'),
				GameConstant::EXCHANGE_PRODUCT_TYPE3 => GameConstant::getParam('ExchangeProductCount3')
		);
		foreach ($config as $type => $count) {
			$tmp = 0;
			for ($i = 0; $i < $count; $i++) {
				$sum_prob = self::getSumProbByProductType($type)-$tmp;
				$seed = mt_rand(1, $sum_prob);
				foreach($groupItems[$type] as $key => $val){
					$seed -= $val->rate;
					if($seed <= 0) {
						$activeItems[] = $val;
						$user_exchange_list[] = (int)$val->id;
						$tmp+=(int)$val->rate;
						unset($groupItems[$type][$key]);
						break;
					}
				}
			}
		}

		if ($user) {
			if ($refresh) {
				$user->emptyExchangeRecord();
				Shop::buyExchangeRefresh($user_id, $token);
			}

			$user->exchange_list = json_encode($user_exchange_list);
			if ($auto_refresh) {
				$user->emptyExchangeRecord();
				$user->exchange_refresh_time = BaseModel::timeToStr(time());
			}
			$user->update(ENV::getDbConnectionForUserWrite($user_id));
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
			if(count($product_ids) == 0){
				return array();
			}
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
		$key = MasterCacheKey::getSumProbByProductType($type);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			$pdo = Env::getDbConnectionForShare();
			$sql = "SELECT SUM(rate) FROM padc_exchange_products WHERE type = ?";
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