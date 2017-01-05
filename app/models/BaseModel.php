<?php
/**
 * モデルのベースクラス.
 * @author kusanagi@banana-systems.com
 */
abstract class BaseModel {

	// テーブル名. 実装クラスで上書きすること.
	const TABLE_NAME = "";
	// updated_at カラムが存在するかどうか. 必要であれば実装クラスで上書きすること.
	const HAS_UPDATED_AT = TRUE;
	// created_at カラムが存在するかどうか. 必要であれば実装クラスで上書きすること.
	const HAS_CREATED_AT = TRUE;
	// created_on カラムが存在するかどうか. 必要であれば実装クラスで上書きすること.
	const HAS_CREATED_ON = FALSE;

	/**
	 * ID を指定してデータベースからレコードを取得する.
	 * @param mixed $id ID
	 * @param PDO $pdo トランザクション内で実行するときは、PDOオブジェクトを指定すること.
	 * @param boolean $forUpdate 更新用クエリにするかどうか.
	 * @return  モデルオブジェクト.
	 */
	public static function find($id, $pdo = null, $forUpdate = FALSE) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($this->user_id);
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
	 * 指定した条件に基づいて、データベースからレコードを1件だけ取得して返す.
	 * @param array $params カラム名をキー、検索に使う値を値とする連想配列.
	 * @param PDO $pdo トランザクション内で実行するときは、PDOオブジェクトを指定すること.
	 * @param boolean $forUpdate 更新用クエリにするかどうか.
	 */
	public static function findBy($params, $pdo = null, $forUpdate = FALSE) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($params['user_id']);
		}
		// SQLの組み立て.
		$conditions = array();
		$values = array();
		foreach($params as $k => $v) {
			$conditions[] = $k . '=?';
			$values[] = $v;
		}
		$sql = "SELECT * FROM " . static::TABLE_NAME . " WHERE " . join(' AND ', $conditions);
		if($forUpdate) {
			$sql .= " FOR UPDATE";
		}
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);

		$obj = $stmt->fetch(PDO::FETCH_CLASS);

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ". join(",",$values)), Zend_Log::DEBUG);
		}

		return $obj;
	}

	/**
	 * 指定した条件に基づいて、データベースからレコードを全件取得して返す.
	 * @param array $params カラム名をキー、検索に使う値を値とする連想配列.
	 * @param string $order SQLのORDER BY句
	 * @param array $limitArgs SQLのLIMIT句のパラメータ
	 * @param PDO $pdo トランザクション内で実行するときは、PDOオブジェクトを指定すること.
	 * @param boolean $forUpdate 更新用クエリにするかどうか.
	 */
	public static function findAllBy($params, $order = null, $limitArgs = null, $pdo = null, $forUpdate = FALSE) {
		if($pdo == null) {
//throw new PadException(RespCode::UNKNOWN_ERROR, "user_id none.");
			$pdo = Env::getDbConnectionForUserRead($params['user_id']);
		}
		// SQLの組み立て.
		$conditions = array();
		$values = array();
		foreach($params as $k => $v) {
			$conditions[] = $k . '=?';
			$values[] = $v;
		}
		$sql = "SELECT * FROM " . static::TABLE_NAME;
		if(!empty($conditions)) {
			$sql .= " WHERE " . join(' AND ', $conditions);
		}
		if(isset($order)) {
			$sql .= " ORDER BY " . $order;
		}
		if(isset($limitArgs) && array_key_exists('limit', $limitArgs)) {
			if(array_key_exists('offset', $limitArgs)) {
				$sql .= " LIMIT " . $limitArgs['offset'] . ", " . $limitArgs['limit'];
			}
			else {
				$sql .= " LIMIT " . $limitArgs['limit'];
			}
		}
		if($forUpdate) {
			$sql .= " FOR UPDATE";
		}
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);
		$objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",",$values)), Zend_Log::DEBUG);
		}

		return $objs;
	}

	public static function countAllBy($params, $pdo = null){
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($this->user_id);
		}
		// SQLの組み立て.
		$conditions = array();
		$values = array();
		foreach($params as $k => $v) {
			$conditions[] = $k . '=?';
			$values[] = $v;
		}
		$sql = "SELECT count(*) as count FROM " . static::TABLE_NAME;
		if(!empty($conditions)) {
			$sql .= " WHERE " . join(' AND ', $conditions);
		}
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);
		$records = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
		$count = 0;
		if(!empty($records[0]->count)){
				$count = $records[0]->count;
		}

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind:".join(",",$values)), Zend_Log::DEBUG);
		}

		return $count;
	}

	/**
	 * オブジェクトをデータベースに新規保存する.
	 * @param PDO $pdo
	 * @return 挿入の結果割り振られたID.
	 */
	public function create($pdo = null, $replace = FALSE) {
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($this->user_id);
		}
		// SQLを構築.
		list($columns, $values) = $this->getValues();
		if(!$replace){
			$sql = 'INSERT INTO ' . static::TABLE_NAME . ' (' . join(',', $columns);
		}else{
			$sql = 'REPLACE INTO ' . static::TABLE_NAME . ' (' . join(',', $columns);
		}
		if(static::HAS_CREATED_ON === TRUE) $sql .= ',created_on';
		if(static::HAS_CREATED_AT === TRUE) $sql .= ',created_at';
		if(static::HAS_UPDATED_AT === TRUE) $sql .= ',updated_at';
		$sql .= ') VALUES (' . str_repeat('?,', count($columns) - 1) . '?';
		if(static::HAS_CREATED_ON === TRUE) $sql .= ',CURRENT_DATE()';
		if(static::HAS_CREATED_AT === TRUE) $sql .= ',now()';
		if(static::HAS_UPDATED_AT === TRUE) $sql .= ',now()';
		$sql .= ')';
		// INSERT実行.
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute($values);
		if(empty($this->id)) {
			$this->id = $pdo->lastInsertId();
		}

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",",$values))."; last_insert_id: ".$this->id, Zend_Log::DEBUG);
		}

		return $result;
	}

	/**
	 * オブジェクトを更新する.
	 * @param PDO $pdo
	 */
	public function update($pdo = null) {
		if(!isset($this->id)) {
			throw new Exception('The ' . get_called_class() . ' is not saved yet.');
		}
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($this->user_id);
		}
		// SQLを構築.
		list($columns, $values) = $this->getValuesForUpdate();
		$sql = 'UPDATE ' . static::TABLE_NAME . ' SET ';
		$setStmts = array();
		foreach($columns as $column) {
			$setStmts[] = $column . '=?';
		}
		$sql .= join(',', $setStmts);
		if(static::HAS_UPDATED_AT === TRUE) $sql .= ',updated_at=now()';
		$sql .= ' WHERE id = ?';
		$stmt = $pdo->prepare($sql);
		//echo $sql;
		$result = $stmt->execute(array_merge($values, array($this->id)));

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",",array_merge($values, array($this->id)))), Zend_Log::DEBUG);
		}

		return $result;
	}

	/**
	 * オブジェクトを削除する.
	 * @param PDO $pdo
	 */
	public function delete($pdo = null) {
		if(!isset($this->id)) {
			throw new Exception('The ' . get_called_class() . ' is not saved yet.');
		}
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($this->user_id);
		}
		$stmt = $pdo->prepare('DELETE FROM ' . static::TABLE_NAME . ' WHERE id = ?');
		$stmt->bindParam(1, $this->id);
		$result = $stmt->execute();

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".'DELETE FROM ' . static::TABLE_NAME . ' WHERE id = '.$this->id), Zend_Log::DEBUG);
		}

		return $result;
	}

	/**
	 * オブジェクトをまとめて削除する.
	 * @param array $ids
	 * @param PDO $pdo
	 */
	public static function delete_ids($ids, $pdo = null) {
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($this->user_id);
		}
		$sql = 'DELETE FROM ' . static::TABLE_NAME;
		$sql .= ' WHERE id IN (' . str_repeat('?,', count($ids) - 1) . '?)';
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute($ids);

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",",$ids)), Zend_Log::DEBUG);
		}

		return $result;
	}

	/**
	 * クエリーを直接実行する.
	 * @param string $sql
	 * @param array $bind_param
	 * @param PDO $pdo
	 * @return integer $result
	 * @return stmt $stmt
	 */
	public static function prepare_execute($sql, $bind_param, $pdo = null){
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($this->user_id);
		}
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute($bind_param);

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",", $bind_param)), Zend_Log::DEBUG);
		}

		return array($result, $stmt);
	}

	/**
	 * データベースから取得した時刻文字列を、タイムスタンプ（秒）に変換.
	 * @param string $str
	 * @param int Unix エポック (1970 年 1 月 1 日 00:00:00 GMT) からの通算秒.
	 */
	public static function strToTime($str) {
		return strtotime($str);
	}

	/**
	 * タイムスタンプ（秒）をデータベース登録用文字列に変換.
	 * @param int $time Unix エポック (1970 年 1 月 1 日 00:00:00 GMT) からの通算秒
	 */
	public static function timeToStr($time) {
		return strftime('%Y-%m-%d %H:%M:%S', $time);
	}

	/**
	 * タイムスタンプ（秒）をデータベース登録用文字列に変換.
	 * @param int $time Unix エポック (1970 年 1 月 1 日 00:00:00 GMT) からの通算秒
	 */
	public static function timeToDateStr($time) {
		return strftime('%Y-%m-%d', $time);
	}

	/**
	 * 同じ日付かどうかを返す
	 */
	public static function isSameDay($time1, $time2){
		return strftime('%y%m%d', $time1) == strftime('%y%m%d', $time2);
	}

	/**
	 * 同じ月かどうかを返す
	 */
	public static function isSameMonth($time1, $time2){
		return strftime('%y%m', $time1) == strftime('%y%m', $time2);
	}

	/**
	 * 同じ日付かどうかを返す
	 * 午前4時に日替り判断
	 */
	public static function isSameDay_AM4($time1, $time2){
		return strftime('%y%m%d', $time1 - 14400) == strftime('%y%m%d', $time2 - 14400);
	}

	public static function isSameMonth_AM4($time1, $time2){
		return strftime('%y%m', $time1 - 14400) == strftime('%y%m', $time2 - 14400);
	}

	public static function getNextDayAM4Timestamp($time = null) {
		if ($time == null) {
			$time = time();
		}

		$today_date = strftime('%Y%m%d', $time - 14400);
		$today_am4 = strtotime($today_date . " 03:59:59");
		$next_am4 = $today_am4 + (24 * 3600);

		return $next_am4;
	}

	/**
	 * whether or not is in the same week
	 * @param string $time1
	 * @param string $time2
	 * @return boolean
	 */
	public static function isSameWeek_AM4($time1,$time2=null){
		if(empty($time2)){
			$time2=time();
		}
		
		$hour = 14400;
		//get game week minus 4 hours
		$time1 -= $hour;
		$time2 -= $hour;
		//get this year and week
		$year = date("Y",$time2);
		$week = date("W",$time2);
		$user_year = date("Y",$time1);
		$user_week = date("W",$time1);
		if($year == $user_year && $week == $user_week){
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * $columns に対応する値の配列を返す.
	 * インスタンスにセットされていない属性は含めない(デフォルト値を使用する場合のため).
	 * @return array カラムの配列、値の配列からなる配列.
	 */
	protected function getValues() {
		$values = array();
		$columns = array();
		foreach (static::$columns as $column) {
			if(isset($this->$column)) {
				$columns[] = $column;
				$values[] = $this->$column;
			}
		}
		return array($columns, $values);
	}

	/**
	 * $columns に対応する値の配列を返す.
	 * @return array カラムの配列、値の配列からなる配列.
	 */
	protected function getValuesForUpdate() {
		$values = array();
		$columns = array();
		foreach (static::$columns as $column) {
			$columns[] = $column;
			$values[] = $this->$column;
		}
		return array($columns, $values);
	}

	/**
	 * fluentdにログをPushします
	 *
	 * @param  array $data
	 */
	protected static function postLog(array $data) {
		// #PADC# -----begin----- Fluentが使用可能かどうかで分岐
		if(Env::FLUENT_FLAG)
		{
			$fluent_logger = Fluent\Logger\FluentLogger::open(Fluent\Env::FLUENT_UNIX_DOMAIN_SOCK);
			$fluent_tag = Fluent\FluentConfig::createForwardTag(static::TABLE_NAME);
			$fluent_result = $fluent_logger->post($fluent_tag, $data);
	
			// レスポンスチェック追加
			if ($fluent_result === false) {
			$errorFile = Padc_Log_Log::getFluentErrorFile();
			$destination = "Fluent\\" . $errorFile . "/" . $fluent_tag;
			error_log(json_encode($data) . PHP_EOL, 3 , $destination);
			}
		}
		else
		{
			return;
		}
		// #PADC# -----end-----
	}

	/**
	 * $columns に対応する値をNULLにする.
	 */
	public function resetColumns() {
		foreach (static::$columns as $c) {
			if ($c != "id") {
				$this->$c = NULL;
			}
		}
	}

	/**
	 * #PADC#
	 * $columns 一式を取得
	 */
	public static function getColumns()
	{
		return static::$columns;
	}
}
