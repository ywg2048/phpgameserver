<?php
/**
 * ウェーブ.
 */

class Wave extends BaseMasterModel {
	const TABLE_NAME = "waves";
	const VER_KEY_GROUP = "dung";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array(
		'dungeon_floor_id',
		'seq',
		'mons_max',
		'egg_prob',
		'tre_prob',
		'boss',
	);

	/**
	 * #PADC#
	 * 管理ツールのダンジョンシミュレータ速度向上のため、複数フロアIDを指定して取得する処理を追加
	 * @param array $dungeonFloorIds
	 * @param PDO $pdo
	 * @return multitype:
	 */
	static function debugGetWaves($dungeonFloorIds, $pdo = null) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForShare();
		}
		// SQLの組み立て.
		$str_floor_ids = implode(",", $dungeonFloorIds);
		$sql = "SELECT * FROM " . static::TABLE_NAME;
		$sql .= " WHERE dungeon_floor_id in (" . $str_floor_ids . ")";
		$sql .= " ORDER BY seq ASC";
		$sql .= " FOR UPDATE";
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute();
		$objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql.";"), Zend_Log::DEBUG);
		}

		// 整形して戻す
		$ret = array();
		foreach($objs as $_obj) {
			$ret[$_obj->dungeon_floor_id][] = $_obj;
		}

		return $ret;
	}


}
