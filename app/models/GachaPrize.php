<?php
/**
 * ガチャ景品.
 */

class GachaPrize extends BaseMasterModel {
	const TABLE_NAME = "gacha_prizes";
	const VER_KEY_GROUP = "gacha";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array(
		'id',
		'gacha_id',
		'gacha_type',
		// #PADC# ----------begin----------
		'piece_id',
		'rare',
		'piece_num',
		'piece_num2',
		// #PADC# ----------end----------
		'min_level',
		'max_level',
		'prob',
		// #PADC_DY# ----------begin----------
		'event',
		// #PADC_DY# ----------end----------
	);

	/**
	 * 取得時レベルを決定して返す.
	 */
	public function getLevel() {
		$offset = mt_rand(0, $this->max_level - $this->min_level);
		return $this->min_level + $offset;
	}

	/**
	 * 指定されたガチャIdの景品が持つ確率値総和をキャッシュして返す.
	 */
	public static function getSumProbByGachaId($gacha_id) {
		$key = MasterCacheKey::getGachaPrizeSumProbKey($gacha_id);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			$pdo = Env::getDbConnectionForShare();
			$sql = "SELECT SUM(prob) FROM gacha_prizes WHERE gacha_id = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(1, $gacha_id);
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
