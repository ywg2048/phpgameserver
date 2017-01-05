<?php
/**
 * マスターモデルのベースクラス.
 * author kusanagi@banana-systems.com
 */
abstract class BaseMasterModel extends BaseModel {

	// デフォルトの Memcached 有効期間.
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	// updated_atカラムが存在するかどうか.
	const HAS_UPDATED_AT = FALSE;
	// キャッシュキー用マスターグループ名.継承クラスでoverrideする.
	const VER_KEY_GROUP = "";

	/**
	 * ID を指定してデータベースからレコードを取得する.
	 * @param mixed $id ID
	 * @param PDO $pdo トランザクション内で実行するときは、PDOオブジェクトを指定すること.
	 * @param boolean $forUpdate 更新用クエリにするかどうか.
	 * @return  モデルオブジェクト.
	 */
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
	 * 指定した条件に基づいて、データベースからレコードを1件だけ取得して返す.
	 * @param array $params カラム名をキー、検索に使う値を値とする連想配列.
	 * @param PDO $pdo トランザクション内で実行するときは、PDOオブジェクトを指定すること.
	 * @param boolean $forUpdate 更新用クエリにするかどうか.
	 */
	public static function findBy($params, $pdo = null, $forUpdate = FALSE) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForShare();
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
			$pdo = Env::getDbConnectionForShare();
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
			$pdo = Env::getDbConnectionForShare();
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
			$pdo = Env::getDbConnectionForShare();
		}
		// SQLを構築.
		list($columns, $values) = $this->getValues();
	global $logger;

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
		$this->id = $pdo->lastInsertId();

		if(Env::ENV !== "production"){
			global $logger;
			$str = join(",",$values);
			if(strlen($str) < 5000){
				$logger->log(("sql_query: ".$sql."; bind: ".$str)."; last_insert_id: ".$this->id, Zend_Log::DEBUG);
			}
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
			$pdo = Env::getDbConnectionForShare();
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
			$str = join(",",array_merge($values, array($this->id)));
			if(strlen($str) < 5000){
				$logger->log(("sql_query: ".$sql."; bind: ".$str), Zend_Log::DEBUG);
			}
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
			$pdo = Env::getDbConnectionForShare();
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
	 * @param PDO $pdo
	 */
	public static function delete_ids($ids, $pdo = null) {
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForShare();
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
	 * 指定したIDに対するデータをAPCから取得する.
	 * @param mixed $id
	 * @return モデルオブジェクト.
	 */
	public static function get($id,$pdo = null) {
		$key = static::getKey($id,$pdo);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			// APCに値がセットされていなければ、Memcacheから取得してAPCにセットする(多段キャッシュ).
			//echo "Loading from DB.";
			$value = self::getForMemcache($id,$pdo);
			if($value) {
				apc_store($key, $value, static::MEMCACHED_EXPIRE + static::add_apc_expire());
			}
		}
		return $value;
	}

	/**
	 * すべてのデータをAPCから取得する.
	 * @return モデルオブジェクトの配列.
	 */
	public static function getAll() {
		$key = static::getAllKey();
		$value = apc_fetch($key);
		if(FALSE === $value) {
			// APCに値がセットされていなければ、Memcacheから取得してAPCにセットする(多段キャッシュ).
			$value = self::getAllForMemcache(array());
			if($value) {
				apc_store($key, $value, static::MEMCACHED_EXPIRE + static::add_apc_expire());
			}
		}
		return $value;
	}

	/**
	 * 条件にあうすべてのデータをAPCから取得する.
	 * @return モデルオブジェクトの配列.
	 */
	public static function getAllBy($params, $order = null, $limitArgs = null) {
		$key = static::getAllByKey($params, $order, $limitArgs);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			// APCに値がセットされていなければ、Memcacheから取得してAPCにセットする(多段キャッシュ).
			$value = self::getAllByForMemcache($params, $order, $limitArgs);
			if($value) {
				apc_store($key, $value, static::MEMCACHED_EXPIRE + static::add_apc_expire());
			}
		}
		return $value;
	}

	/**
	 * 条件にあうデータを1件APCから取得する.
	 * @return モデルオブジェクトの配列.
	 */
	public static function getBy($params) {
		$key = static::getByKey($params);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			// APCに値がセットされていなければ、Memcacheから取得してAPCにセットする(多段キャッシュ).
			$value = self::getByForMemcache($params);
			if($value) {
				apc_store($key, $value, static::MEMCACHED_EXPIRE + static::add_apc_expire());
			}
		}
		return $value;
	}


	/**
	 * 指定したIDに対するデータをMemcacheから取得する.
	 * もしMemcacheに登録されていなければ、データベースから取得してMemcacheにセットする.
	 *
	 * @param mixed $id
	 * @return モデルオブジェクト.
	 */
	public static function getForMemcache($id, $pdo = null) {
		$key = static::getKey($id);
		// #PADC# ----------begin----------memcache→redisに切り替え
		// Memcahced に接続して、値を取得する.
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if(FALSE === $value) {
			// Memcached に値がセットされていなければ、データベースから取得して Memcached にセットする.
			//echo "Loading from DB.";
			$value = self::find($id,$pdo);
			if($value) {
				$redis = Env::getRedisForShare();
				$redis->set($key, $value, static::MEMCACHED_EXPIRE);
			}
		}
		// #PADC# ----------end----------
		return $value;
	}

	/**
	 * すべてのデータをMemcacheから取得する.
	 * もしMemcacheに登録されていなければ、データベースから取得してMemcacheにセットする.
	 *
	 * @return モデルオブジェクトの配列.
	 */
	public static function getAllForMemcache() {
		$key = static::getAllKey();
		// #PADC# ----------begin----------memcache→redisに切り替え
		// Memcahced に接続して、値を取得する.
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if(FALSE === $value) {
			// Memcached に値がセットされていなければ、データベースから取得して Memcached にセットする.
			$value = self::findAllBy(array());
			if($value) {
				$redis = Env::getRedisForShare();
				$redis->set($key, $value, static::MEMCACHED_EXPIRE);
			}
		}
		// #PADC# ----------end----------
		return $value;
	}

	/**
	 * 条件にあうすべてのデータをMemcacheから取得する.
	 * もしMemcacheに登録されていなければ、データベースから取得してMemcacheにセットする.
	 *
	 * @return モデルオブジェクトの配列.
	 */
	public static function getAllByForMemcache($params, $order = null, $limitArgs = null) {
		$key = static::getAllByKey($params, $order, $limitArgs);
		// #PADC# ----------begin----------memcache→redisに切り替え
		// Memcahced に接続して、値を取得する.
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if(FALSE === $value) {
			// Memcached に値がセットされていなければ、データベースから取得して Memcached にセットする.
			$value = self::findAllBy($params, $order, $limitArgs);
			if($value) {
				$redis = Env::getRedisForShare();
				$redis->set($key, $value, static::MEMCACHED_EXPIRE);
			}
		}
		// #PADC# ----------end----------
		return $value;
	}

	/**
	 * 条件にあうデータを1件Memcacheから取得する.
	 * もしMemcacheに登録されていなければ、データベースから取得してMemcacheにセットする.
	 *
	 * @return モデルオブジェクトの配列.
	 */
	public static function getByForMemcache($params) {
		$key = static::getByKey($params);
		// #PADC# ----------begin----------memcache→redisに切り替え
		// Memcahced に接続して、値を取得する.
		$rRedis = Env::getRedisForShareRead();
		$value = $rRedis->get($key);
		if(FALSE === $value) {
			// Memcached に値がセットされていなければ、データベースから取得して Memcached にセットする.
			$value = self::findBy($params);
			if($value) {
				$redis = Env::getRedisForShare();
				$redis->set($key, $value, static::MEMCACHED_EXPIRE);
			}
		}
		// #PADC# ----------end----------
		return $value;
	}

	protected static function getVerKey($pdo = null) {
		return Version::getVersion(static::VER_KEY_GROUP,$pdo);
	}

	/**
	 * キャッシュにセットする際のキーを返す.
	 * @param mixed $id
	 */
	protected static function getKey($id,$pdo = null) {
		return Env::MEMCACHE_PREFIX . get_called_class() . self::getVerKey($pdo) . '_' . $id;
	}

	/**
	 * キャッシュに全レコードをセットする際のキーを返す.
	 */
	protected static function getAllKey() {
		return Env::MEMCACHE_PREFIX . get_called_class() . self::getVerKey() . '_all';
	}

	/**
	 * キャッシュに条件にあう全レコードをセットする際のキーを返す.
	 */
	protected static function getAllByKey($params, $order = null, $limitArgs = null) {
		$cond = "";
		foreach($params as $k => $v) {
			$cond .= $k . "_" . (string) $v;
		}
		if(isset($order)) {
			$cond .= str_replace(" ", "_", $order);
		}
		if(isset($limitArgs)) {
			foreach($limitArgs as $k => $v) {
				$cond .= $k . "_" . (string) $v;
			}
		}
		return Env::MEMCACHE_PREFIX . get_called_class() . self::getVerKey() . '_allrecord_' . $cond;
	}

	/**
	 * キャッシュに条件にあうレコードをセットする際のキーを返す.
	 */
	protected static function getByKey($params) {
		$cond = "";
		foreach($params as $k => $v) {
			$cond .= $k . "_" . (string) $v;
		}
		return Env::MEMCACHE_PREFIX . get_called_class() . self::getVerKey() . '_onerecord_' . $cond;
	}

	/**
	 * DBへの負荷を分散させるためにAPCの有効時間をランダムにする値を返す.
	 */
	public static function add_apc_expire() {
		return mt_rand(0, 99);
	}

	/**
	 * Apcにキャッシュしているデータサイズをチェック.
	 */
	public static function checkApcCache($key, $length) {
		$value = apc_fetch($key);
		$len_apc = strlen($value);
		$result = ($length == $len_apc);
		return array($result, $len_apc);
	}

	/**
	 * 各フロントサーバのAPCキャッシュをチェックする.
	 */
	public static function checkAllServerApcCache($length) {
		$result = array();
		$length_str = "";
		if(!is_null($length)){
			$length_str = "&len=".$length;
		}
		foreach(Env::getWebServers() as $host){
			$endpoint = 'http://'.$host.'/check_apc_cache.php?model='.static::TABLE_NAME.'&region='.Env::REGION.$length_str;
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// execute the cURL request and fetch response data
			$response = curl_exec($ch);
			$errno = curl_errno($ch);
			$errmsg = curl_error($ch);
			curl_close($ch);
			// ensure the request succeeded
			if($errno != 0){
				throw new Exception($errmsg, $errno);
			}
			$result[$host] = $response;
		}
		return $result;
	}

}
