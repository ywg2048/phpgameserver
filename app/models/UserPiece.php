<?php
/**
 * #PADC#
 * ユーザーの所有するカケラのクラス
 */
class UserPiece extends BaseModel
{
	const TABLE_NAME = "user_pieces";

	// #PADC#
	// 該当欠片のモンスターが生成されたことがあるか
	const IS_CREATED		= true;
	const IS_NOT_CREATED	= false;
	const CREATE_DUPLICATE_MAX = 5;
	const NUM_MAX = 99999;
	protected static $columns = array(
		'user_id',
		'piece_id',
		'num',
		'create_card',
		'last_get_time'
	);
	private $master_piece = null;
	private $create_flg = FALSE;
	private $reserve = FALSE;
	private $pdo_share = null;
	public static function createDummy($user_id)
	{
		$dummy_piece = new UserPiece();
		$dummy_piece->user_id = $user_id;
		$dummy_piece->piece_id = 1;
		$dummy_piece->num = 10;
		$dummy_piece->create_card = 0;
		return $dummy_piece;
	}

	public function setMasterPdo($pdo)
	{
		$this->pdo_share = $pdo;
	}
	/**
	 * ユーザーカードのマスターカードデータを返す(キャッシュの負担を減らすため).
	 */
	public function getMaster()
	{
		if(!isset($this->master_piece))
		{
			$this->master_piece = Piece::get($this->piece_id,$this->pdo_share);
		}
		return $this->master_piece;
	}

	/**
	　* このカードを合成するときの獲得経験値を返す.
	　* 引数として、ベースモンスターのCardオブジェクトと、成功ボーナス発生有無を渡す.
	　*/
	public function getCompositeExp(Card $base_card, $num)
	{
		$piece = $this->getMaster();
		// 数
		$exp = GameConstant::getParam("PieceCompositeExp") * $num * $piece->mexp;

		// MY : モンスターの欠片ボーナス、全体を調整する時に容易にするため。
		// if($base_card->gup_piece_id == $piece->id) {
		// 	$exp = $exp * GameConstant::getParam("PieceCompositeSameAttrBonus");
		// }
		// MY PADC DELETE : 大成功ボーナスなどはなくなりました。
		// if($composite_bonus == UserCard::COMPOSITE_BONUS_GOOD)
		// {
		// 	// 成功ボーナス.
		// 	$exp = $exp * GameConstant::getParam("PieceCompositeGoodBonus");
		// }
		// else if($composite_bonus == UserCard::COMPOSITE_BONUS_EXCELLENT)
		// {
		// 	// 大成功ボーナス.
		// 	$exp = $exp * GameConstant::getParam("PieceCompositeExcellentBonus");
		// }
		return round($exp);
	}

	// #PADC# ----------begin----------
	/**
	 * 一旦別定義でおいておくが、UserCardと共通でよければUserCardのメソッドを使用する。
	 * 欠片合成ボーナス発生判定.
	 * PADCでは大成功ボーナスが無くなったので利用していない
	 */
	public static function getCompositeBonus() {
		$excellent_prob = GameConstant::getParam("PieceCompositeExcellentBonusProb");
		$good_prob = GameConstant::getParam("PieceCompositeGoodBonusProb");
		$bonus = LimitedBonus::getCompositeGoodExcellentUp();
		if($bonus) {
			$excellent_prob = ($excellent_prob * $bonus->args / 10000.0);
			$good_prob = ($good_prob * $bonus->args / 10000.0);
		}
		$seed = mt_rand(1, 10000);
		if($seed <= $excellent_prob) {
			return UserCard::COMPOSITE_BONUS_EXCELLENT;
		} else if($seed <= $excellent_prob + $good_prob) {
			return UserCard::COMPOSITE_BONUS_GOOD;
		}
		return UserCard::COMPOSITE_BONUS_NONE;
	}
	/**
	 * 入力した経験値に期間限定ボーナスがあれば適用し、適用後の値を返す.
	 * MY : 一旦別定義にしておくが、共通でよければUserCardのものを使う
	 */
	public static function applyLimitedCompositeBonus($exp) {
		$bonus = LimitedBonus::getActiveComposition();
		if($bonus) {
			return round($exp * $bonus->args / 10000.0);
		}
		return $exp;
	}
	// #PADC# ----------end----------
	/**
	 * ユーザーピースを取得.
	 * なかった場合は生成される。
	 *
	 * @param $user_id		ユーザーID
	 * @param $piece_id		カケラID
	 * @param $pdo			PDO
	 * @param $forUpdate	更新用クエリにするかどうか.
	 * @return UserPiece
	 */
	public static function getUserPiece($user_id, $piece_id, $pdo, $forUpdate = TRUE)
	{
		$user_piece = self::getUserPieceReserve($user_id, $piece_id, $pdo, $forUpdate);
		$user_piece->reserve = FALSE;
		if($user_piece->create_flg)
		{
			$user_piece->create($pdo);
		}

		return $user_piece;
	}

	/**
	 *　指定した複数のUserPieceを取得する。
	 * @param $user_id ユーザID
	 * @param $piece_ids 欠片IDの配列
	 * @param $pdo
	 * @return array(UserPiece,...)
	 */
	public static function findByPieceIds($user_id, $piece_ids, $pdo)
	{
		$add_user_pieces = array();
		if(count($piece_ids))
		{
			$sql = 'SELECT * FROM user_pieces WHERE user_id = ? AND piece_id IN (' . str_repeat('?,', count($piece_ids) - 1) . '?) FOR UPDATE';
			$values = array($user_id);
			$values = array_merge($values, $piece_ids);
			$stmt = $pdo->prepare($sql);
			$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
			$stmt->execute($values);
			$add_user_pieces = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
		}
		return $add_user_pieces;
	}

	public static function getUserPieceReserve($user_id, $piece_id, $pdo, $forUpdate = FALSE)
	{
		// MY : まずデータを取得、なければUserPieceを生成する。

		$params = array('user_id' => $user_id,'piece_id' => $piece_id);
		$user_piece = UserPiece::findBy($params, $pdo, $forUpdate);

		if($user_piece == FALSE)
		{
			$user_piece = new UserPiece();
			$user_piece->user_id = $user_id;
			$user_piece->piece_id = $piece_id;
			$user_piece->num = 0;
			$user_piece->create_card = 0;
			$user_piece->create_flg = TRUE;
			$user_piece->last_get_time = User::timeToStr(time());
		}
		else
		{
			$user_piece->create_flg = FALSE;
		}
		$user_piece->reserve = TRUE;

		return $user_piece;
	}

	/**
	 * 所持している欠片情報を一式取得
	 * @param int $user_id
	 * @return mixed
	 */
	public static function getUserPieces($user_id)
	{
		$params = array('user_id' => $user_id);
		$user_pieces = UserPiece::findAllBy($params);
		return $user_pieces;
	}

	/**
	 * 所持している欠片情報を一式取得（欠片IDをキーとした連想配列として整形）
	 * @param int $user_id
	 * @return mixed
	 */
	public static function getUserPiecesWithSetKey($user_id,$key='piece_id')
	{
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$result = array();
		$params = array('user_id' => $user_id);
		$user_pieces = UserPiece::findAllBy($params);

		if(count($user_pieces) > 0)
		{
			foreach($user_pieces as $_data)
			{
				$result[$_data->$key] = $_data;
			}
		}

		return $result;
	}

	/**
	 * ユーザーに指定のカケラを付与し、その結果を返します。
	 * 通常はUserPieceオブジェクトを連想配列データにして返しますが、
	 * モンスターが生成される場合はUserCardを追加して返します。
	 *
	 * @return カケラ付与後のUserPieceオブジェクト、モンスターが生成される場合はUserCardも返す
	 *		例
	 * 		array(
	 * 			'piece' => UserPiece,
	 * 			'card' => UserCard
	 * 		)
	 */
	public static function addUserPieceToUser($user_id, $piece_id, $num, $pdo)
	{
		$user_piece = UserPiece::getUserPiece($user_id, $piece_id, $pdo);
		$add_card = $user_piece->addPiece($num, $pdo);
		$user_piece->update($pdo);

		$result = array(
			'piece' => $user_piece
		);
		if(isset($add_card))
		{
			$result['card'] = $add_card;
		}

		return $result;
	}

	/**
	 * 指定のカケラを加算したときの結果を返します。
	 * 通常はUserPieceオブジェクトを連想配列データにして返しますが、
	 * モンスターが生成される場合はUserCardを追加して返します。
	 *
	 * この関数が呼ばれた時点ではまだDBへのデータ作成は行われません。
	 * 複数種類のカケラを一度に付与する際に一旦この関数を利用して最終的な内容を求め
	 * addUserPiecesWithCardsToUserFix()で一度に追加、更新を行う設計です。
	 *
	 * @param int $user_id
	 * @param int $piece_id
	 * @param int $num
	 * @param PDO $pdo
	 * @param array $initInfo
	 * @return カケラ付与後のUserPieceオブジェクト、モンスターが生成される場合はUserCardも返す
	 *		例
	 * 		array(
	 * 			'piece' => UserPiece,
	 * 			'card' => UserCard
	 * 		)
	 */
	public static function addUserPieceToUserReserve($user_id, $piece_id, $num, $pdo, $initInfo=null, $share_pdo = null)
	{
		$user_piece = UserPiece::getUserPieceReserve($user_id, $piece_id, $pdo);
		$user_piece->setMasterPdo($share_pdo);
		if($initInfo)
		{
			$add_card = $user_piece->addPieceWithInitInfo($num, $pdo, $initInfo);
		}
		else
		{
			$add_card = $user_piece->addPiece($num, $pdo);
		}

		$result = array(
			'piece' => $user_piece
		);
		if(isset($add_card))
		{
			$result['card'] = $add_card;
		}

		return $result;
	}

	/**
	 * ユーザーのカケラ、カードの更新を行います
	 *
	 * @param array $add_pieces
	 * @param array $add_cards
	 * @param unknown $pdo
	 */
	public static function addUserPiecesWithCardsToUserFix(Array $add_pieces, Array $add_cards, $pdo) {

		if(count($add_cards) > 0){
			$add_cards_result = UserCard::addCardsToUserFix($add_cards, $pdo);
		}
		else {
			$add_cards_result = array();
		}

		if(count($add_pieces) > 0){
			UserPiece::updateUserPiecesToUserFix($add_pieces, $pdo);
		}

		return array($add_pieces, $add_cards_result);
	}


	/**
	 * ユーザーカケラオブジェクトの配列をデータベースに反映する.
	 * @param PDO $pdo
	 * @return 実行結果.
	 */
	public static function updateUserPiecesToUserFix(Array $add_pieces, $pdo) {
		$add_piece = array_shift($add_pieces);
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($add_piece->user_id);
		}
		// SQLを構築.
		list($columns, $values) = $add_piece->getValues();
		global $logger;
		$sql = 'INSERT INTO ' . static::TABLE_NAME . ' (' . join(',', $columns);
		if(static::HAS_CREATED_ON === TRUE) $sql .= ',created_on';
		if(static::HAS_CREATED_AT === TRUE) $sql .= ',created_at';
		if(static::HAS_UPDATED_AT === TRUE) $sql .= ',updated_at';
		$sql .= ') VALUES ';
		$sql2 = '(' . str_repeat('?,', count($columns) - 1) . '?';
		if(static::HAS_CREATED_ON === TRUE) $sql2 .= ',CURRENT_DATE()';
		if(static::HAS_CREATED_AT === TRUE) $sql2 .= ',now()';
		if(static::HAS_UPDATED_AT === TRUE) $sql2 .= ',now()';
		$sql2 .= ')';
		$sql3 = ' ON DUPLICATE KEY UPDATE num=VALUES(num),create_card=VALUES(create_card),last_get_time=VALUES(last_get_time)';
		$sql .= str_repeat($sql2 . ',', count($add_pieces)) . $sql2 . $sql3;
		foreach($add_pieces as $obj){
			list($columns, $val) = $obj->getValues();
			$values = array_merge($values, $val);
		}

		// INSERT実行.
		$stmt = $pdo->prepare($sql);
		$result = $stmt->execute($values);

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",",$values))."; last_insert_id: ".$pdo->lastInsertId(), Zend_Log::DEBUG);
		}

		return $result;
	}

	/**
	 * カードを生成できたら生成する。
	 * 単体で生成される仕様は現状ないが、必要になったらprivate => publicに変更。
	 * @param PDO $pdo
	 * @param array $initInfo
	 * @return 生成されたカード、なければnull.
	 */
	public function createCard($pdo,$initInfo=null,$force_create = false)
	{
		$piece = $this->getMaster();
		$add_card = null;
		if($piece->isTypeMonster())
		{
			if(($this->create_card == 0 || $force_create) && $this->num >= $piece->gcnt)
			{
				$card_id = $piece->cid;

				// 追加時のステータス設定（デフォルト値）
				$initValues = array(
					'lv'		=> UserCard::DEFAULT_LEVEL,
					'slv'		=> UserCard::DEFAULT_SKILL_LEVEL,
					'equip1'	=> 0,
					'equip2'	=> 0,
					'equip3'	=> 0,
					'equip4'	=> 0,
				);
				// 追加時の初期ステータスに設定する値がある場合上書き
				if($initInfo)
				{
					foreach($initInfo as $_key => $_value)
					{
						if(in_array($_key, $initValues))
						{
							$initValues[$_key] = $_value;
						}
					}
				}

				if($this->reserve) {
					$add_card = UserCard::addCardsToUserReserve(
							$this->user_id,
							$card_id,
							$initValues['lv'],
							$initValues['slv'],
							$pdo,
							$initValues['equip1'],
							$initValues['equip2'],
							$initValues['equip3'],
							$initValues['equip4']
					);
				}
				else {
					$add_card = UserCard::addCardToUser(
						$this->user_id,
						$card_id,
						$initValues['lv'],
						$initValues['slv'],
						$pdo,
						$initValues['equip1'],
						$initValues['equip2'],
						$initValues['equip3'],
						$initValues['equip4']
					);
				}

				$this->num -= $piece->gcnt;
				$this->create_card += 1;
			}
		}
		return $add_card;
	}

	/**
	 * MY : 加算処理。
	 * 欠片を加算する。
	 * モンスターが生成できた場合は生成する。
	 *
	 * @param $num
	 * @param $pdo
	 * @return 生成されたカード、なければnull.
	 */
	public function addPiece($num, $pdo)
	{
		$this->num += $num;
		$this->num = min($this->num,self::NUM_MAX);
		$this->last_get_time = User::timeToStr(time());
		// MY : 現在の所持数と生成有無を確認して生成をする。
		$add_card = $this->createCard($pdo);
		return $add_card;
	}

	/**
	 * MY : 加算処理。
	 * addPieceと基本的には同処理で欠片を加算する。
	 * モンスターが生成できた場合は生成する。生成時の初期LV等設定する場合はこちらを使用
	 *
	 * @param int $num
	 * @param PDO $pdo
	 * @param array $initInfo
	 * @return 生成されたカード、なければnull.
	 */
	public function addPieceWithInitInfo($num, $pdo, $initInfo)
	{
		$this->num += $num;
		$this->num = min($this->num,self::NUM_MAX);
		$this->last_get_time = User::timeToStr(time());
		// MY : 現在の所持数と生成有無を確認して生成をする。
		$add_card = $this->createCard($pdo,$initInfo);
		return $add_card;
	}

	/**
	 * 碎片出售获得宠物点.
	 */
	public static function sellPiecesForExchangePoint($user_id, $piece_datas)
	{
		try
		{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$after_pieces = array();


			$piece_ids = array_keys($piece_datas);

			// 强化碎片 -- Piece::PIECE_ID_STRENGTH
			$piece_ids = array_merge($piece_ids,array(Piece::PIECE_ID_STRENGTH));

			//取出玩家要出售的碎片
			$user_pieces = UserPiece::findByPieceIds($user_id, $piece_ids, $pdo);


			$user = User::find($user_id, $pdo, TRUE);
			$tlog_pieces = array();//TLOG

			// MY : そのまま渡すと文字列となってしまうため、数値化する。
			$before_exchange_point = $user->exchange_point;

			$exchange_point = (int)$user->exchange_point;
			$strength_piece = null;
			$profit_exchange_point = 0;
			// MY : 指定した欠片のデータが無い。(没有指定的碎片)

			if(count($piece_ids) != count($user_pieces))
			{
				foreach($piece_datas as $key => $data)
				{
					$have = false;
					foreach($user_pieces as $user_piece)
					{
						if($data['id'] == $user_piece->piece_id)
						{
							$have = true;
						}
					}
					if($have == false)
					{
						$pdo->rollback();
						return array(RespCode::FAILED_SELL_PIECE,$before_exchange_point, $exchange_point,  $after_pieces);
					}
				}

			}

			foreach($user_pieces as $user_piece)
			{

				if(array_key_exists($user_piece->piece_id,$piece_datas))
				{
					$num = $piece_datas[$user_piece->piece_id]['num'];

					if($user_piece->num != $num)
					{
						$pdo->rollback();
						return array(RespCode::FAILED_SELL_PIECE,$before_exchange_point, $exchange_point,  $after_pieces);
					}
					//计算碎片出售的时候，玩家可以得到的金币 ++
					//ecost -- 每个碎片出售时，玩家获得的交易点数
					$piece_point= $user_piece->getMaster()->ecost * $num;
					$profit_exchange_point += $piece_point;
					$user_piece->subtractPiece($num);
					//Tlog： 每个碎片出售的时候，相应数据的记录 ++
					$tlog_pieces[$user_piece->piece_id] = array('piece_add' => -$num, 'after_piece_num' => $user_piece->num,'exchange_point' => $piece_point);
				}
			}

			// 強化の欠片が多重更新になることを避けるため、最後に一括で更新する。
			//遍历取出的user_piece对象，更新到数据库
			foreach($user_pieces as $user_piece)
			{
				$user_piece->update($pdo);
				//重新安排显示列的顺序 ++
				$after_pieces[] = UserPiece::arrangeColumn($user_piece);
			}
			//增加用户的金币 ++
			$user->exchange_point += $profit_exchange_point;
			$user->update($pdo);
			// MY : addCoin関数を通すことでuser->coinがintに変化している
			$exchange_point = $user->exchange_point;
			$pdo->commit();

			// #PADC# ----------begin----------
			//向腾讯发送游戏的数据 ++
			//$sequence = Tencent_Tlog::getSequence($user_id);
			//UserTlog::sendTlogMoneyFlow($user, $profit_exchange_point, Tencent_Tlog::REASON_SELL_PIECE, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, $sequence);
			//foreach($tlog_pieces as $piece_id => $tlog_piece){
			//	UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $tlog_piece['piece_add'], $tlog_piece['after_piece_num'], Tencent_Tlog::ITEM_REASON_SELL, 0, $tlog_piece['exchange_point'], Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
			//}
			// #PADC# ----------end----------
		}
			// #PADC# PDOException → Exception
		catch(Exception $e)
		{
			if($pdo != null)
			{
				if($pdo->inTransaction())
				{
					$pdo->rollback();
				}
			}
			throw $e;
		}

		return array(RespCode::SUCCESS,$before_exchange_point,$exchange_point,$after_pieces);
	}


	/**
	 *　欠片消費。
	 */
	public function subtractPiece($num)
	{
		$this->num -= $num;
	}
	/**
	 * 欠片の売却処理.
	 */
	public static function sellPieces($user_id, $piece_datas)
	{
		try
		{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$after_pieces = array();

			$piece_ids = array_keys($piece_datas);

			$piece_ids = array_merge($piece_ids,array(Piece::PIECE_ID_STRENGTH));
			$user_pieces = UserPiece::findByPieceIds($user_id, $piece_ids, $pdo);
			
			$user = User::find($user_id, $pdo, TRUE);
			$tlog_pieces = array();//TLOG

			// MY : そのまま渡すと文字列となってしまうため、数値化する。
			$before_coin = (int)$user->coin;
			$coin = (int)$user->coin;
			$strength_piece = null;
			$price = 0;
			$get_strength_num = 0;
			// MY : 指定した欠片のデータが無い。
			if(count($piece_ids) != count($user_pieces))
			{
				foreach($piece_datas as $key => $data)
				{

					$have = false;
					foreach($user_pieces as $user_piece)
					{
						if($data['id'] == $user_piece->piece_id)
						{
							$have = true;
						}
					}
					if($have == false)
					{
						$pdo->rollback();
						return array(RespCode::FAILED_SELL_PIECE,$before_coin, $coin,  $after_pieces);
					}
				}
				
			}

			foreach($user_pieces as $user_piece)
			{
				if($user_piece->piece_id == Piece::PIECE_ID_STRENGTH)
				{
					$strength_piece = $user_piece;
				}
				if(array_key_exists($user_piece->piece_id,$piece_datas))
				{
					$num = $piece_datas[$user_piece->piece_id]['num'];
					if($user_piece->num != $num)
					{
						$pdo->rollback();
						return array(RespCode::FAILED_SELL_PIECE,$before_coin, $coin,  $after_pieces);
					}
					$piece_price = $user_piece->getMaster()->scost * $num;
					$price += $piece_price;
					// $get_strength_num += $user_piece->getMaster()->tcost * $num;
					$user_piece->subtractPiece($num);
					//Tlog
					$tlog_pieces[$user_piece->piece_id] = array('piece_add' => -$num, 'after_piece_num' => $user_piece->num,'coin' => $piece_price);
				}
			}
			/*
			if($get_strength_num > 0)
			{
				if(empty($strength_piece))
				{
					$strength_piece = UserPiece::getUserPiece($user_id, Piece::PIECE_ID_STRENGTH, $pdo, TRUE);
					$user_pieces[] = $strength_piece;
				}
				$strength_piece->addPiece($get_strength_num,$pdo);
				//Tlog
				$tlog_pieces[Piece::PIECE_ID_STRENGTH] = array('piece_add' => $get_strength_num, 'after_piece_num' => $strength_piece->num, 'coin' => 0);//Tlog
			}
			*/

			// 強化の欠片が多重更新になることを避けるため、最後に一括で更新する。
			foreach($user_pieces as $user_piece)
			{
				$user_piece->update($pdo);
				$after_pieces[] = UserPiece::arrangeColumn($user_piece);
			}
			$user->addCoin($price);
			$user->update($pdo);
			// MY : addCoin関数を通すことでuser->coinがintに変化している
			$coin = $user->coin;
			$pdo->commit();

			// #PADC# ----------begin----------
			$sequence = Tencent_Tlog::getSequence($user_id);
			UserTlog::sendTlogMoneyFlow($user, $price, Tencent_Tlog::REASON_SELL_PIECE, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, $sequence);
			foreach($tlog_pieces as $piece_id => $tlog_piece){
				UserTlog::sendTlogItemFlow($user_id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $tlog_piece['piece_add'], $tlog_piece['after_piece_num'], Tencent_Tlog::ITEM_REASON_SELL, 0, $tlog_piece['coin'], Tencent_Tlog::MONEY_TYPE_MONEY, $sequence);
			}
			// #PADC# ----------end----------
		}
		// #PADC# PDOException → Exception
		catch(Exception $e)
		{
			if($pdo != null)
			{
				if($pdo->inTransaction())
				{
					$pdo->rollback();
				}
			}
			throw $e;
		}
		return array(RespCode::SUCCESS,$before_coin,$coin,$after_pieces);
	}

	/**
	 * 該当の欠片のモンスターを生成したことがああるか
	 * @param UserPiece $user_piece
	 * @return boolean
	 */
	public static function isCreated($user_piece)
	{
		if($user_piece && $user_piece->create_card > 0)
		{
			return true;
		}
		return false;
	}

	/**
	 * ユーザが保有している欠片をクライアントが要求するフォーマットに変換する.
	 * @param unknown $user_piece
	 * @param number $rev
	 */
	public static function arrangeColumn($user_piece, $rev = 0) {
		$arr = array();
		$arr[] = (int)$user_piece->piece_id;
		$arr[] = (int)$user_piece->num;
		$arr[] = (int)$user_piece->create_card;
		$arr[] = date('ymdHis',strtotime($user_piece->last_get_time));
		return $arr;
	}

	/**
	 * ユーザが保有している欠片をクライアントが要求するフォーマットに変換する.
	 * @param unknown $user_pieces
	 * @param number $rev
	 */
	public static function arrangeColumns($user_pieces, $rev = 0) {
		$mapper = array();
		foreach($user_pieces as $user_piece) {
			$mapper[] = static::arrangeColumn($user_piece, $rev);
		}
		return $mapper;
	}
	/**
	 * This function is specified for tencent api which need [num>=0] data
	 * @param string $param
	 * @param array $limitArgs
	 * @param object $pdo
	 * @return object
	 */
	public static function findAllExists($param, $limitArgs = null, $pdo = null) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($param);
		}
		$values = array($param);
		$sql = "SELECT * FROM " . static::TABLE_NAME." WHERE user_id=? AND num >=1";
		if(isset($limitArgs) && array_key_exists('limit', $limitArgs)) {
			if(array_key_exists('offset', $limitArgs)) {
				$sql .= " LIMIT " . $limitArgs['offset'] . ", " . $limitArgs['limit'];
			}
			else {
				$sql .= " LIMIT " . $limitArgs['limit'];
			}
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
	/**
	 * count all user exists pieces,then return the value
	 *
	 * @param int $param
	 * @param object $pdo
	 * @return number
	 */
	public static function countAllExists($param,$pdo)
	{
		$values = array($param);
		$sql = "SELECT count(*) as count FROM " . static::TABLE_NAME." where user_id = ? and num >= 1";
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute($values);
		$records = $stmt->fetchAll();
		$count = 0;
		if(!empty($records[0])){
			$count = $records[0]['count'];
		}

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind:".join(",",$values)), Zend_Log::DEBUG);
		}
		return $count;
	}

	// モンスターの追加生成が可能かチェックする。
	public function checkAdditionalMonster($pdo)
	{
		$search = array('gup_piece_id' => $this->piece_id);
		$cards = Card::findAllBy($search);
		$search = array_merge($search,array('gup_final' => 1));
		$final_monsters = Card::findAllBy($search);
		$find_cards = array();
		foreach($cards as $card)
		{
			$find_cards[] = $card->id;
		}
		$sql = 'SELECT * FROM user_cards WHERE user_id = ? AND card_id IN (' . str_repeat('?,', count($find_cards) - 1) . '?)';
		$values = array($this->user_id);
		$values = array_merge($values, $find_cards);
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, 'UserCard');
		$stmt->execute($values);
		$group_cards = $stmt->fetchAll(PDO::FETCH_CLASS,'UserCard');
		$ret_no_final = array();
		$ret_final = array();
		foreach($group_cards as $group_card)
		{
			$base = $group_card->getMaster();
			if($group_card->lv < $base->mlv || $base->gup_final != 1)
			{
				$ret_no_final[] = $group_card;
			}
			else
			{
				$ret_final[] = $group_card;
			}
		}
		$create_max = self::CREATE_DUPLICATE_MAX;
		$final_num = count($ret_final);
		$no_final_num = count($ret_no_final);
		$create_sum_max = count($final_monsters)*$create_max;
		$ret = false;
		if($no_final_num == 0)
		{
			if($final_num < $create_sum_max)
			{
				$ret = true;
			}
		}
		return $ret;
	}
}