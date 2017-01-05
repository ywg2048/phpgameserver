<?php
/**
 *  用户排名关卡通关数据记录.
 */
class UserRankingRecord extends BaseModel {
	const TABLE_NAME = "user_ranking_record";

	protected static $columns = array(
		'id',
		'user_id',
		'ranking_id',
		'waves',
		'combos',
		'turns',
		'rare',
		'score',
		'created_at',
		'updated_at'
	);
	public static function findbyUserId($params,$pdo)
	{
		
		$conditions = array();
		$values = array();
		foreach($params as $k => $v) {
			$conditions[] = $k . '=?';
			$values[] = $v;
		}
		$sql = "SELECT * FROM " . static::TABLE_NAME . " WHERE " . join(' AND ', $conditions);
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);

		$obj = $stmt->fetch(PDO::FETCH_CLASS);
		return $obj;
	}
}