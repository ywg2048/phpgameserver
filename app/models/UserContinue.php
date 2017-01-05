<?php
/**
 * ユーザコンティニューモデル.
 */
class UserContinue extends BaseModel {
	const TABLE_NAME = "user_continue";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array (
			'user_id',
			'hash',
			'used',
			'data'
	);

	/**
	 * #PADC#
	 * コンティニュー用のハッシュを生成しテーブルデータ作成
	 * 無料コンティニューを利用するかの引数を追加
	 */
	public static function saveHash($user_id, $use_cont) {
		$hash = UserContinue::getHash ();
		try {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
			$pdo->beginTransaction ();
			$user = User::find ( $user_id, $pdo, TRUE );
			// #PADC# ----------begin----------
			if ($use_cont) {
				if ($user->cont <= 0) {
					throw new PadException ( RespCode::NOT_ENOUGH_USER_CONTINUE );
				}
			}
			else {
				if (! $user->checkHavingGold ( Shop::PRICE_CONITINUE )) {
					throw new PadException ( RespCode::NOT_ENOUGH_MONEY );
				}
			}
			// #PADC# ----------end----------
			$sql = "REPLACE INTO " . static::TABLE_NAME . " (user_id, hash, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
			$stmt = $pdo->prepare ( $sql );
			$stmt->bindParam ( 1, $user_id );
			$stmt->bindParam ( 2, $hash );
			$stmt->execute ();
			$pdo->commit ();
		// #PADC# PDOException → Exception
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
		return $hash;
	}

	/**
	 * #PADC#
	 * コンティニュー処理実行
	 * 無料コンティニューを利用するかの引数を追加
	 */
	public static function ack($user_id, $hash, $token, $use_cont) {
		// #PADC# ----------begin----------
		User::getUserBalance ( $user_id, $token );
		$billno = false;
		// #PADC# ----------end----------

		global $logger;
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );

		try {
			$pdo->beginTransaction ();

			// コンティニュー直後にコールされるため、masterから読み取る.
			$userContinue = UserContinue::findBy(array('user_id'=>$user_id, 'hash'=>$hash), $pdo);
			if ($userContinue == false) {
				throw new PadException ( RespCode::CONTINUE_HASH_NOT_FOUND );
			}
			if ($userContinue->used == 1) { // 既にack済み
				throw new PadException ( RespCode::CONTINUE_ALREADY_ACKED, "CONTINUE_ALREADY_ACKED user_id=$user_id hash=$hash. __NO_TRACE" );
			}

			$userContinue->used = 1;
			$userContinue->update ( $pdo );

			$user = User::find ( $user_id, $pdo, TRUE );
			// #PADC# ----------begin----------
			if ($use_cont) {
				if ($user->cont <= 0) {
					throw new PadException ( RespCode::NOT_ENOUGH_USER_CONTINUE );
				}

				$log_data ['cont_before'] = ( int ) $user->cont;
				// #PADC# 無料コンティニュー回数消費
				$user->cont -= 1;
				$log_data ['cont_after'] = ( int ) $user->cont;
			}
			else {
				if (! $user->checkHavingGold ( Shop::PRICE_CONITINUE )) {
					throw new PadException ( RespCode::NOT_ENOUGH_MONEY );
				}

				$log_data ['gold_before'] = ( int ) $user->gold;
				$log_data ['pgold_before'] = ( int ) $user->pgold;
				// #PADC# 魔法石消費
				$billno = $user->payGold ( Shop::PRICE_CONITINUE, $token, $pdo );
				$log_data ['gold_after'] = ( int ) $user->gold;
				$log_data ['pgold_after'] = ( int ) $user->pgold;
			}

			$user->accessed_at = User::timeToStr ( time () );
			$user->accessed_on = $user->accessed_at;
			$user->update ( $pdo );
			$log_data ['dev'] = ( int ) $user->device_type;
			$log_data ['area'] = ( int ) $user->area_id;
			// #PADC# ----------end----------

			$user_dungeon = UserDungeon::findBy(array("user_id" => $user->id), $pdo, TRUE);

			$log_data ['dungeon_id'] = ( int ) $user_dungeon->dungeon_id;
			$log_data ['dungeon_floor_id'] = ( int ) $user_dungeon->dungeon_floor_id;

			$userContinue->data = json_encode ( $log_data );
			$userContinue->update ( $pdo );

			UserDungeon::spendContinue ( $user_id, $pdo );

			$pdo->commit ();

			// #PADC# TLOG
			if (!$use_cont) {
				UserTlog::sendTlogMoneyFlow ( $user, - Shop::PRICE_CONITINUE, Tencent_Tlog::REASON_BUY_CONTINUE, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data ['gold_after'] - $log_data ['gold_before']), abs($log_data ['pgold_after'] - $log_data ['pgold_before']));
				
				$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
			}
		// #PADC# PDOException → Exception
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			// #PADC# 魔法石消費を取り消す　----------begin----------
			if ($billno) {
				$user->cancelPay ( Shop::PRICE_CONITINUE, $billno, $token );
			}
			// ----------end----------
			throw $e;
		}
	}

	/**
	 * ハッシュ値を生成し返す.
	 */
	private static function getHash() {
		$h = uniqid ();
		return $h;
	}

	/**
	 * コンテニュー履歴を取得して返す.
	 * @param integer $userId
	 * @param integer $offset
	 * @param integer $perPage
	 */
	public static function findLogsFor($userId, $offset, $perPage = 20) {
		$pdo = Env::getDbConnectionForUserRead ( $userId );
		$sql = <<< SQL
          SELECT
            *
            FROM user_continue
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT $perPage OFFSET $offset
SQL;
		$stmt = $pdo->prepare ( $sql );
		$stmt->bindParam ( 1, $userId );
		$stmt->execute ();
		$values = $stmt->fetchAll ( PDO::FETCH_CLASS, 'UserContinue' );
		return $values;
	}

	/**
	 * コンテニュー数を取得して返す.
	 * @param integer $userId
	 */
	public static function countLogsFor($userId) {
		$pdo = Env::getDbConnectionForUserRead ( $userId );
		$sql = <<< SQL
            SELECT
              count(id)
              FROM user_continue
              WHERE user_id = ?
SQL;
		$stmt = $pdo->prepare ( $sql );
		$stmt->bindParam ( 1, $userId );
		$stmt->execute ();
		$value = $stmt->fetchColumn ();
		return $value;
	}

}
