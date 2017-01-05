<?php
/**
 * フレンド関係
 */

class Friend extends BaseModel {
	const TABLE_NAME = "friends";

	// ヘルパー選択時に付与する友情ポイント
	const FRIENDSHIP_POINT = 10;

	// #PADC# ----------begin----------
	const FRIEND_STATUS_NONE 	= 0;
	const FRIEND_STATUS_NORMAL 	= 1;
	const FRIEND_STATUS_SNS 	= 2;
	// #PADC# ----------end----------

	// #PADC# フラッグ追加
	protected static $columns = array('user_id1', 'user_id2', 'snsflag');

	/**
	 * 指定されたユーザ同士が友達か返す
	 */
	public static function isFriend($user_id1, $user_id2, $pdo_user1 = null){
		if(!isset($pdo_user1)){
			$pdo_user1 = Env::getDbConnectionForUserRead($user_id1);
		}
		return Friend::findBy(array('user_id1'=> $user_id1, 'user_id2' => $user_id2), $pdo_user1) ? TRUE : FALSE;
	}

	/**
	 * #PADC#
	 * 指定されたユーザ同士の友達状態を返す
	 *
	 * @return 0:フレンドではない、1:フレンド、2:SNSフレンド
	 */
	public static function getFriendStatus($user_id1, $user_id2){
		$pdo = Env::getDbConnectionForUserRead($user_id1);
		$friend12 = Friend::findBy(array('user_id1'=> $user_id1, 'user_id2' => $user_id2), $pdo);
		if ($friend12) {
			if ($friend12->snsflag == 0) {
				return Friend::FRIEND_STATUS_NORMAL;
			}
			else {
				return Friend::FRIEND_STATUS_SNS;
			}
		}
		else {
			return Friend::FRIEND_STATUS_NONE;
		}
	}

	/**
	 * フレンド申請を許可する
	 * @param $pdo 書き込み用のPDO
	 */
	public static function accept($user_id1, $user_id2, $pdo_user1, $pdo_user2){
		$user1 = User::find($user_id1, $pdo_user1, TRUE); // 被申請者
		$user2 = User::find($user_id2, $pdo_user2, TRUE); // 申請者

		// $user1 -> $user2 へのフレンド関係を取得.
		$friend12 = Friend::findBy(array('user_id1'=> $user_id1, 'user_id2' => $user_id2), $pdo_user1);
		// $user2 -> $user1 へのフレンド関係を取得.
		$friend21 = Friend::findBy(array('user_id1'=> $user_id2, 'user_id2' => $user_id1), $pdo_user2);

		// フレンド関係の状態をチェック.
		if( $friend12 || $friend21 ) {
			// 既に友達状態
			return;
		}

		// 双方のフレンド数をチェック. 念のためDBからカウント
		self::checkFriendCounts($user1, $user2, $pdo_user1, $pdo_user2);

		// フレンド関係を作成.
		$friend12 = new Friend();
		$friend12->user_id1 = $user_id1;
		$friend12->user_id2 = $user_id2;
		$friend21 = new Friend();
		$friend21->user_id1 = $user_id2;
		$friend21->user_id2 = $user_id1;
		$friend12->create($pdo_user1);
		$friend21->create($pdo_user2);

		self::updateFriendCount($user1, $pdo_user1);
		self::updateFriendCount($user2, $pdo_user2);
	}

	/**
	 * フレンド関係の破棄.
	 * (フレンド申請の拒否ではない. フレンド申請の拒否は　@see actions/AcceptFriendRequest.php)
	 *
	 * @param $user_id1 破棄する方
	 * @param $user_id2 破棄される方
	 */
	public static function quit($user_id1, $user_id2){
		try{
			// #PADC# ----------begin----------
			$share = Env::getDbConnectionForShare();
			$u1_device = UserDevice::find($user_id1,$share);
			$u2_device = UserDevice::find($user_id2,$share);
			$pdo_user1 = Env::getDbConnectionForUserWrite($user_id1);
			$pdo_user1->beginTransaction();
			if($u1_device->dbid == $u2_device->dbid)
			{
				$pdo_user2 = $pdo_user1;
			}
			else
			{
				$pdo_user2 = Env::getDbConnectionForUserWrite($user_id2);	
				$pdo_user2->beginTransaction();
			}
			
			// #PADC# ----------end----------
			// $user1 -> $user2 へのフレンド関係を取得.
			$friend12 = Friend::findBy(array('user_id1'=> $user_id1, 'user_id2' => $user_id2), $pdo_user1, TRUE);
			// $user2 -> $user1 へのフレンド関係を取得.
			$friend21 = Friend::findBy(array('user_id1'=> $user_id2, 'user_id2' => $user_id1), $pdo_user2, TRUE);

			if($friend12 == FALSE && $friend21 == FALSE){
				throw new PadException(RespCode::NOT_FRIEND, "NOT_FRIEND $user_id1 and $user_id2 are not friends eath other. __NO_TRACE");
			}

			if($friend12){
				$friend12->delete($pdo_user1);
			}
			if($friend21){
				$friend21->delete($pdo_user2);
			}

			$user1 = User::find($user_id1, $pdo_user1, TRUE);
			$user2 = User::find($user_id2, $pdo_user2, TRUE);

			self::updateFriendCount($user1, $pdo_user1);
			self::updateFriendCount($user2, $pdo_user2);

			$pdo_user1->commit();
			// #PADC# ----------begin----------
			if($pdo_user2->inTransaction())
			{
				$pdo_user2->commit();	
			}
			// #PADC# ----------end----------
		}catch(Exception $e){
			if ($pdo_user1->inTransaction()) {
				$pdo_user1->rollback();
			}
			if ($pdo_user2->inTransaction()) {
				$pdo_user2->rollback();
			}
			throw $e;
		}
	}

	/**
	 * 2人のユーザの通常フレンド数をカウント.(念のため、users に保存された値ではなく、DBから数え直す).
	 */
	private static function checkFriendCounts($user1, $user2, $pdo_user1, $pdo_user2) {
		$sql = "SELECT count(id) FROM friends WHERE user_id1 = ?";
		$stmt = $pdo_user1->prepare($sql);
		// ユーザ1のフレンド数.
		$stmt->bindParam(1, $user1->id);
		$stmt->execute();
		$friendCount1 = $stmt->fetch(PDO::FETCH_NUM);
		// ユーザ2のフレンド数.
		$stmt = $pdo_user2->prepare($sql);
		$stmt->bindParam(1, $user2->id);
		$stmt->execute();
		$friendCount2 = $stmt->fetch(PDO::FETCH_NUM);
		// フレンド数をチェック.
		if($friendCount2[0] >= $user2->friend_max) {
			throw new PadException(RespCode::INVITER_REACHED_MAX_NUM_FRIEND, "Friend count of the inviter reached the maximum. __NO_LOG");
		}
		if($friendCount1[0] >= $user1->friend_max) {
			throw new PadException(RespCode::TARGET_REACHED_MAX_NUM_FRIEND, "Friend count of the invited reached the maximum. __NO_LOG");
		}
	}

	/**
	 * フレンド数をDBからカウントし更新する.
	 * @param User $user
	 * @param PDO $pdo
	 */
	public static function updateFriendCount($user, $pdo) {
		// フレンド数.
		$sql = "SELECT count(id) FROM friends WHERE user_id1 = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $user->id);
		$stmt->execute();
		$friendCount = $stmt->fetch(PDO::FETCH_NUM);

		// フレンド更新.
		$user->fricnt = $friendCount[0];
		$user->update($pdo);
	}

	// フレンドデータ取得
	public static function getFriends($user_id, $rev, $token = null) {
		$pdo = Env::getDbConnectionForUserRead($user_id);
		$sql = "SELECT user_id2, snsflag FROM ". Friend::TABLE_NAME ." WHERE user_id1 = ? ORDER BY created_at ASC";
		$bind_param = array($user_id);
		list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
		$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$user_ids = array();
		$sns_flgs = array();
		foreach($values as $value) {
			$user_ids[] = $value['user_id2'];
			$sns_flgs[ $value['user_id2'] ] = $value['snsflag'];
		}
		// キャッシュから取得（なければDBから）
		$records = User::getMultiCacheFriendData($user_ids, $rev);
		// #PADC# ----------begin----------
		if($rev >= 2){
			// 送ったスタミナプレゼント情報を取得
			$user_send_presents = UserPresentsSend::findAllBy(array("sender_id" => $user_id), null, null, $pdo);
			$send_times = array();
			foreach($user_send_presents as $value) {
				$send_times[ $value->receiver_id ] = date('ymdHis',strtotime($value->created_at));
			}
			
			$user_oids = UserDevice::getOpenids($user_ids);

			if(!empty($token)){
				$user_vips = User::getUserFriendVips($user_id, $token, null, $pdo, null, $user_ids);
				//global $logger;
				//$logger->log('$user_vips:'.print_r($user_vips, true), 7);
			}
			if(!isset($user_vips) || !$user_vips){
				$user_vips = array();
			}
			
			// フレンドの情報を上書きする
			// SNSフレンド、スタミナプレゼント時間、openid、QQVIP
			foreach($records as $key => $value) {
				$pid = $value[1];
				if (array_key_exists($pid, $sns_flgs)) {
					$records[$key][BaseUserModel::FRIEND_SNS] = (int)$sns_flgs[$pid];
				}
				if (array_key_exists($pid, $send_times)) {
					$records[$key][BaseUserModel::FRIEND_LAST_PRESENT_TIME] = $send_times[$pid];
				}
				if (array_key_exists($pid, $user_oids)){
					$records[$key][BaseUserModel::FRIEND_OID] = $user_oids[$pid];
				}
				if (array_key_exists($pid, $user_vips)){
					$records[$key][BaseUserModel::FRIEND_QQ_VIP] = $user_vips[$pid];
				}
			}
		}
		// #PADC# ----------end----------

		return $records;
	}

	// フレンドデータ取得
	public static function getFriendids($user_id, $pdo = null) {
		if(!$pdo){
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		$sql = "SELECT user_id2 FROM ". Friend::TABLE_NAME ." WHERE user_id1 = ? ORDER BY created_at ASC";
		$bind_param = array($user_id);
		list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
		$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$records = array();
		foreach($values as $value) {
			//#PADC# $ids -> $records
			$records[] = $value['user_id2'];
		}
		//#PADC# $ids -> $records
		return $records;
	}

	// データスナップショット作成
	public static function getsnapshots($user_id,$log_date) {
		$pdo = Env::getDbConnectionForUserRead($user_id);
		$sql = "SELECT * FROM ". self::TABLE_NAME ." WHERE user_id1 = ?";
		$bind_param = array($user_id);
		list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
		$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$snapshot_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_friends_snapshot.log");
		$snapshot_format = '%message%'.PHP_EOL;
		$snapshot_formatter = new Zend_Log_Formatter_Simple($snapshot_format);
		$snapshot_writer->setFormatter($snapshot_formatter);
		$snapshot_logger = new Zend_Log($snapshot_writer);
		foreach($values as $value) {
				$value=preg_replace('/"/', '""',$value);
				$snapshot_logger->log($log_date.",".implode(",",$value), Zend_Log::DEBUG);
		}
	}

	/**
	 * #PADC# SNSフレンドとしてフレンドを追加します
	 *
	 * @param int $user_id
	 * @param array $friends_ids
	 * @throws PadException
	 * @throws PDOException
	 */
	public static function addSnsFriends($user_id, $friends_ids){
		$share = Env::getDbConnectionForShare();
		$u1_device = UserDevice::find($user_id,$share);	
		$pdo_user1 = Env::getDbConnectionForUserWrite($user_id);
		foreach($friends_ids as $friend_id){
			$u2_device = UserDevice::find($friend_id,$share);
			$unmatch_dbid = true;
			if($u2_device->dbid == $u1_device->dbid)
			{
				$pdo_user2 = $pdo_user1;
				$unmatch_dbid = false;
			}
			else
			{
				$pdo_user2 = Env::getDbConnectionForUserWrite($friend_id);	
			}
			
			try{
				$pdo_user1->beginTransaction();
				if($unmatch_dbid)
				{
					$pdo_user2->beginTransaction();	
				}

				$reach_max = false;
				try{
					static::accept($user_id, $friend_id, $pdo_user1, $pdo_user2);
				}catch(Exception $e){
					//フレンドがいっぱいでも続きます
					if($e instanceof PadException){
						$code = $e->getCode();
						if($code == RespCode::TARGET_REACHED_MAX_NUM_FRIEND || $code == RespCode::INVITER_REACHED_MAX_NUM_FRIEND ){
							$reach_max = true;
						}else{
							throw $e;
						}
					}else{
						throw $e;
					}
				}

				if(!$reach_max){
					static::setSnsFlag(1, $user_id, $friend_id, $pdo_user1, $pdo_user2);
				}

				$pdo_user2->commit();
				if($unmatch_dbid)
				{
					$pdo_user1->commit();	
				}
			}catch(Exception $e){
				if ($pdo_user1->inTransaction()) {
					$pdo_user1->rollback();
				}
				if ($pdo_user2->inTransaction()) {
					$pdo_user2->rollback();
				}
				throw $e;
			}
		}
	}

	/**
	 * #PADC# snsflagをセットする。
	 *
	 * @param int $flag
	 * @param int $user_id1
	 * @param int $user_id2
	 * @param string $pdo_user1
	 * @param string $pdo_user2
	 */
	private static function setSnsFlag($flag, $user_id1, $user_id2, $pdo_user1 = null, $pdo_user2 = null){
		// $user1 -> $user2 へのフレンド関係を取得.
		$friend12 = Friend::findBy(array('user_id1'=> $user_id1, 'user_id2' => $user_id2), $pdo_user1);
		// $user2 -> $user1 へのフレンド関係を取得.
		$friend21 = Friend::findBy(array('user_id1'=> $user_id2, 'user_id2' => $user_id1), $pdo_user2);

		$friend12->snsflag = $flag;
		$friend12->update($pdo_user1);

		$friend21->snsflag = $flag;
		$friend21->update($pdo_user2);
	}

	/**
	 * #PADC# id list に対してsnsflagをセットする
	 *
	 * @param int $user_id
	 * @param array $friends_ids
	 * @param int $flag
	 * @throws PDOException
	 */
	public static function setFriendsFlag($user_id, $friends_ids, $flag){
		if(empty($friends_ids)){
			return;
		}
		$share = Env::getDbConnectionForShare();
		$u1_device = UserDevice::find($user_id,$share);
		$pdo_user1 = Env::getDbConnectionForUserWrite($user_id);

		$sql = "SELECT * FROM " . static::TABLE_NAME;
		$sql .= " WHERE user_id1 = ? AND user_id2 IN ( " . str_repeat('?,',count($friends_ids) - 1) . "? )";
		$sql .= " AND snsflag = " . ($flag + 1) % 2;
		$sql .= " FOR UPDATE";

			$bind_param = array($user_id);
			$bind_param = array_merge($bind_param, $friends_ids);
			list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo_user1);
		$objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

		foreach($objs as $friend){
			$friend_id = $friend->user_id2;
			$u2_device = UserDevice::find($friend_id,$share);
			$unmatch_dbid = true;
			if($u2_device->dbid == $u1_device->dbid)
			{
				$pdo_user2 = $pdo_user1;
				$unmatch_dbid = false;
			}
			else
			{
				$pdo_user2 = Env::getDbConnectionForUserWrite($friend_id);
			}

			try{
				$pdo_user1->beginTransaction();
				if($unmatch_dbid)
				{
					$pdo_user2->beginTransaction();	
				}
				
				static::setSnsFlag($flag, $user_id, $friend_id, $pdo_user1, $pdo_user2);

				$pdo_user1->commit();
				if($unmatch_dbid)
				{
					$pdo_user2->commit();	
				}				
			}catch(Exception $e){
				if ($pdo_user1->inTransaction()) {
					$pdo_user1->rollback();
				}
				if ($pdo_user2->inTransaction()) {
					$pdo_user2->rollback();
				}
				throw $e;
			}
		}
	}
}
