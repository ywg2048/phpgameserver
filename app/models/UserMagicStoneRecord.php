<?php
/**
 * ユーザモデル.
 */
class UserMagicStoneRecord extends BaseUserModel {
	const TABLE_NAME = "user_magic_stone_record";
	const MEMCACHED_EXPIRE = 864000; // 10日間.
	const COIN_MAX				= 2000000000;
	const ROUND_TICKET_MAX		= 99999999;// 周回チケットの上限
	const MAX_FRIEND_POINT = 20000;
	
	protected static $columns = array(
		'id',
		'events_id',
		'exchange_gold', // 兑换点
		'exchange_list', // 上次兑换商品id列表
		'exchange_refresh_time', // 上次兑换列表自动刷新时间
		'exchange_record', // 已兑换id列表
		// #PADC_DY# ----------end----------
		'refresh_times',//刷新次数
		'created_at',
		'updated_at',
	);
	/**
	 * ID を指定してデータベースからレコードを取得する.
	 * @param mixed $id ID
	 * @param PDO $pdo トランザクション内で実行するときは、PDOオブジェクトを指定すること.
	 * @param boolean $forUpdate 更新用クエリにするかどうか.
	 * @return  モデルオブジェクト.
	 */
	public static function find($id, $pdo = null, $forUpdate = FALSE) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($id);
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
		if($obj  === FALSE) {
			return $obj;
		}
		// 変更前の値を'_'を付けて保存
		$org_value = array();
		foreach($obj as $key => $value){
			$org_value['_'.$key] = $value;
		}
		foreach($org_value as $key => $value){
			$obj->$key = $value;
		}
		unset($org_value);
		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".$id), Zend_Log::DEBUG);
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
			$pdo = Env::getDbConnectionForUserRead($params['id']);
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

		return $objs;
	}
		/**
	 * ボーナスを付与したら、上限数に超えるか
	 * 
	 * @param number $bonusId
	 * @param number $amount
	 * @param number $pieceId
	 * @return boolean
	 */
	public function checkBonusLimit($user,$bonusId, $amount, $pieceId = 0, $pdo = null){
		if($pdo == null){
			$pdo = Env::getDbConnectionForUserRead ( $this->id );
		}
		if($bonusId == BaseBonus::COIN_ID){
			return ($user->coin + $amount > self::COIN_MAX);
		}else if($bonusId == BaseBonus::FRIEND_POINT_ID){
			$user = User::find($this->id);
			return ($user->fripnt + $amount > self::MAX_FRIEND_POINT);
		}else if($bonusId == BaseBonus::ROUND_ID){
			$user = User::find($this->id);
			return ($user->round + $amount > self::ROUND_TICKET_MAX);
		}else if($bonusId == BaseBonus::PIECE_ID) {
			$user_piece = UserPiece::findBy(array(
					'user_id' => $this->id,
					'piece_id' => $pieceId
			), $pdo, false);
			if($user_piece == FALSE)
			{
				return false;
			}
			return ($user_piece->num + $amount >= UserPiece::NUM_MAX);
		}
		return false;
	}

	/**
	 * ボーナスを付与する.
	 * TODO: ログ出力.
	 * @param integer $bonusId
	 * @param integer $amount
	 * @param PDO $pdo
	 */
	// #PADC# ----------begin---------- 
	// パラメータ追加
	public function applyBonus($user,$bonusId, $amount, $pdo, $ex_params = null, $token, $piece_id = null) {
		$result = array();
		$result['no'] = $bonusId;
		$result['amount'] = $amount;
		if($bonusId <= BaseBonus::MAX_CARD_ID) {
			// カードボーナス.
			$plus_hp = isset($ex_params["ph"]) ? $ex_params["ph"] : 0;
			$plus_atk = isset($ex_params["pa"]) ? $ex_params["pa"] : 0;
			$plus_rec = isset($ex_params["pr"]) ? $ex_params["pr"] : 0;
			$psk = isset($ex_params["psk"]) ? $ex_params["psk"] : 0;
			$slv = isset($ex_params["slv"]) ? $ex_params["slv"] : UserCard::DEFAULT_SKILL_LEVEL;
			$user_card = UserCard::addCardToUser($this->id, $bonusId, $amount, $slv, $pdo, $plus_hp, $plus_atk, $plus_rec, $psk);
			$result["user_card"] = $user_card;
		} elseif($bonusId == BaseBonus::COIN_ID) {
			// コインボーナス.
			$user->addCoin($amount);
			$result["coin"] = $user->coin;
		} elseif($bonusId == BaseBonus::MAGIC_STONE_ID) {
			// (無料)魔石ボーナス.
			// #PADC# Add free gold to Tencent server
			$user->presentGold($amount, $token);
			$result['gold'] = ($user->gold+$user->pgold);
		} elseif($bonusId == BaseBonus::PREMIUM_MAGIC_STONE_ID) {
			// (有料)魔石ボーナス.
			$user->addPGold($amount);
			$result['gold'] = ($user->gold+$user->pgold);
			//bonus_idが有償魔法石の場合は無償魔法石のコードに変換して返す
			$result['no']=(int)BaseBonus::MAGIC_STONE_ID;
		} elseif($bonusId == BaseBonus::FRIEND_POINT_ID) {
			// 友情ポイントボーナス.
			$user->addFripnt($amount);
			$result ['fripnt'] = $user->fripnt;
		}
		elseif($bonusId == BaseBonus::STAMINA_ID)
		{
			// スタミナの現在値を計算後に加算する
			$user->getStamina();
			$user->stamina += $amount;
			$user->stamina_max += $amount;
			if ($user->stamina_max > self::STAMINA_STOCK_MAX) {
				$user->stamina_max = self::STAMINA_STOCK_MAX;
			}
			if ($user->stamina > self::STAMINA_STOCK_MAX) {
				$user->stamina = self::STAMINA_STOCK_MAX;
			}
			$result['sta'] = $user->stamina;
			$result['sta_max'] = $user->stamina_max;
			$result['sta_time'] = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));
		}
		elseif($bonusId == BaseBonus::STAMINA_RECOVER_ID)
		{
			$user->addStamina($amount);
			$result['sta'] = $user->stamina;
			$result['sta_time'] = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));
		}
		elseif($bonusId == BaseBonus::FRIEND_MAX_ID)
		{
			$user->friend_max += $amount;
			if ($user->friend_max > self::FRIEND_MAX_LIMIT) {
				$user->friend_max = self::FRIEND_MAX_LIMIT;
			}
			$result['frimax'] = $user->friend_max;
		}
		elseif($bonusId == BaseBonus::EXP_ID) {
			$user->addExp($amount);
			$result['exp'] = $user->exp;
		} elseif($bonusId == BaseBonus::MEDAL_ID) {
			// メダルボーナス.
			$user->addMedal($amount);
			$result['m'] = $user->medal;
		} else if($bonusId == BaseBonus::AVATAR_ID) {
			$avatar_id = isset($ex_params["aid"]) ? $ex_params["aid"] : 0;
			$avatar_lv = isset($ex_params["alv"]) ? $ex_params["alv"] : 1;
			$result = WUserAitem::addBonusAvatar($user, $pdo, $avatar_id, $avatar_lv);
		}
		else if($bonusId == BaseBonus::RANKING_POINT_ID)
		{
			$user->addRankingPoint($amount);
			$result['ranking_point'] = $user->ranking_point;
		}
		else if($bonusId == BaseBonus::CONTINUE_ID){
			$user->setContinue($amount);
			$result['continue'] = $user->cont;
		}
		else if($bonusId == BaseBonus::ROUND_ID){
			$user->addRound($amount);
			$result['round'] = $user->round;
		}
		else if($bonusId == BaseBonus::PIECE_ID) {
			//$piece_id = isset($ex_params["pid"]) ? $ex_params["pid"] : 0;
			if ($piece_id > 0) {
				// カケラ
				$add_result = UserPiece::addUserPieceToUser(
						$user->id,
						$piece_id,
						$amount,
						$pdo
				);
				$result["user_piece"] = $add_result["piece"];

				//#PADC#
				UserTlog::setTlogData(array(
					'piece' => array(
						array(
							'id' => $piece_id,
							'add' => $amount,
							'num' => $add_result["piece"]->num,
						),
					),
				));
				//cardがない可能性があります
				$result["user_card"] = isset($add_result["card"]) ? $add_result["card"] : null;
				if(isset($result["user_card"])){
					// 図鑑登録数の更新
					$user_book = UserBook::getByUserId($user->id, $pdo);
					$user->book_cnt = $user_book->getCountIds();

					//#PADC#
					UserTlog::setTlogData(array(
						'card' => array(
							$result["user_card"]->card_id
						),
					));
				}
			}
			else {
				$result["user_piece"] = null;
				$result["user_card"] = null;
			}
		}
		// #PADC_DY# ----------begin----------
		else if($bonusId == BaseBonus::USER_EXP){
			$user->addExp($amount);
			$result["exp"] = $user->exp;
		}
		else if($bonusId == BaseBonus::USER_VIP_EXP){
			$user->addVipExp($amount);
			$user->refreshVipLv($token);
			$result["tp_gold"] = $user->tp_gold;
			$result["vip_lv"] = $user->vip_lv;
		}
		// #PADC_DY# -----------end-----------
		return $result;
	}


	// 是否已经售罄检查
	public function alreadySoldOut($product_id) {
		return in_array($product_id, $this->getExchangeRecord());
	}
// 兑换记录
	public function getExchangeRecord() {
		if(!empty($this->exchange_record)) {
			return array_map('intval', json_decode($this->exchange_record, true));
		} else {
			return array();
		}
	}
// 增加兑换记录
	public function addExchangeRecord($product_id) {
		$exchange_record = $this->getExchangeRecord();
		if (!in_array($product_id, $exchange_record)) {
			$exchange_record[] = $product_id;
			$exchange_record = array_unique($exchange_record);
			sort($exchange_record);
			$this->exchange_record = json_encode($exchange_record);
		}
	}
	public function update($pdo = null){
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($this->id);
		}
		list($columns, $values) = $this->getValuesForUpdate();
		$sql = 'UPDATE ' . static::TABLE_NAME . ' SET ';
		$setStmts = array();
		foreach($columns as $column) {
			$setStmts[] = $column . '=?';
		}
		$setStmts[] = 'updated_at=now()';
		$sql .= join(',', $setStmts);
		$sql .= ' WHERE id = ?';
		$stmt = $pdo->prepare($sql);
		$values = array_merge($values, array($this->id));
		$result = $stmt->execute($values);
		return $result;
	}

	/**
	 * $columns に対応する値の配列を返す.
	 * @return array カラムの配列、値の配列からなる配列.
	 */
	protected function getValuesForUpdate() {
		$values = array();
		$columns = array();
		foreach (static::$columns as $column) {
			$_key = '_'.$column;
			// 元の値から変化しているも項目のみ更新する
			if(isset($this->$_key) && $this->$column === $this->$_key) continue;
			$columns[] = $column;
			$values[] = $this->$column;
		}
		return array($columns, $values);
	}
	// 刷新后情况购买记录
	public function emptyExchangeRecord(){
		$this->exchange_record = "";
	}

}