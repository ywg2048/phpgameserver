<?php
/**
 * Admin用：ダンジョンフローの状態変更
 */
class AdminUpdateDungeonFloor2 extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$dungeon_floor_ids = isset ( $params ['dfid'] ) ? $params ['dfid'] : array();

		$cleared = isset ( $params ['clr'] ) ? $params ['clr'] : 0;
		$reset = isset ( $params ['reset'] ) ? $params ['reset'] : 0;

		// #PADC_DY# ----------begin----------
		$star = isset ( $params ['star'] ) ? $params ['star'] : 0;
		if ($star > 3 || $star < 0 ){
			$star = 3;
		}
		// #PADC_DY# ----------end----------

		if (!isset ( $user_id )) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}

		try {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
			$pdo->beginTransaction ();

			$user = User::find ( $user_id, $pdo, true );

			if ($reset) {
				$this->resetUserDungeonFloor ( $user_id, $pdo );
				$user->clear_dungeon_cnt = 0;
			}
			else {
				// 全フロアデータ取得
				$all_dungeon_floors = DungeonFloor::getAll();
				$dungeon_floors = array();
				foreach($all_dungeon_floors as $df){
					$dungeon_floors[$df->id] = $df;
				}

				// 指定されたダンジョンフロアの前提フロアIDをさかのぼってクリア状態にするフロアIDを洗い出す
				// ただし解放に必要なダンジョンクリア数は考慮していない
				$cleaar_dungeon_floor_ids = array();
				foreach($dungeon_floor_ids as $key => $dfid){
					$check_floor_id = $dfid;
					while ($check_floor_id != 0) {
						if (in_array($check_floor_id, $cleaar_dungeon_floor_ids)) {
							break;
						}
						else {
							$cleaar_dungeon_floor_ids[] = $check_floor_id;
							if (array_key_exists($check_floor_id, $dungeon_floors)) {
								$check_floor_id = $dungeon_floors[$check_floor_id]->prev_dungeon_floor_id;
							}
							else {
								$check_floor_id = 0;
							}
						}
					}
				}
				$cleaar_dungeon_floor_ids = array_unique($cleaar_dungeon_floor_ids);
				sort($cleaar_dungeon_floor_ids);

				foreach($cleaar_dungeon_floor_ids as $floor_id){
					$user_dungeon_floor = UserDungeonFloor::enable($user_id, floor($floor_id / 1000), $floor_id, $pdo);
					$user_dungeon_floor->first_played_at = UserDungeon::timeToStr ( time () );
					$user_dungeon_floor->cleared_at = UserDungeon::timeToStr ( time () );
					// #PADC_DY#
					$user_dungeon_floor->max_star = $star;
					$user_dungeon_floor->update ( $pdo );
				}

				$user->clear_dungeon_cnt = UserDungeon::getClearCountDungeon ( $user_id, $pdo );
				UserDungeonFloor::getOpenFloor($user, $pdo);

			}

			$redis = Env::getRedisForUser();
			$redis->delete ( CacheKey::getUserClearDungeonFloors ( $user_id ) );

			$user->update ( $pdo );
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}

		return json_encode ( array (
				'res' => RespCode::SUCCESS
		) );
	}

	/**
	 *
	 * @param number $user_id
	 * @param PDO $pdo
	 * @return bool
	 */
	private function resetUserDungeonFloor($user_id, $pdo) {
		$stmt = $pdo->prepare ( 'DELETE FROM ' . UserDungeonFloor::TABLE_NAME . ' WHERE user_id = ? AND dungeon_floor_id != ?' );
		$result = $stmt->execute ( array (
				$user_id,
				UserDungeonFloor::INITIAL_DUNGEON_FLOOR_ID
		) );

		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . 'DELETE FROM ' . UserDungeonFloor::TABLE_NAME . ' WHERE user_id = ' . $user_id . ' AND dungeon_floor_id != ' . UserDungeonFloor::INITIAL_DUNGEON_FLOOR_ID), Zend_Log::DEBUG );
		}

		$cnt = UserDungeonFloor::countAllBy ( array (), $pdo );

		$udf = UserDungeonFloor::findBy ( array (
				'user_id' => $user_id,
				'dungeon_floor_id' => UserDungeonFloor::INITIAL_DUNGEON_FLOOR_ID
		), $pdo, true );
		if ($udf && isset ( $udf->cleared_at ) && strtotime ( $udf->cleared_at ) > 0) {
			$udf->cleared_at = NULL;
			$udf->update ( $pdo );
		}
	}

	/**
	 *
	 * @param number $user_id
	 * @param PDO $pdo
	 * @return array
	 */
	private function findUnclearedUserDungeonFloors($user_id, $pdo) {
		$sql = 'SELECT * FROM ' . UserDungeonFloor::TABLE_NAME;
		$sql .= ' WHERE user_id = ?';
		$sql .= ' AND (cleared_at IS NULL OR cleared_at="0000-00-00 00:00:00")';
		$sql .= ' ORDER BY dungeon_id ASC';
		$sql .= ' FOR UPDATE';

		$stmt = $pdo->prepare ( $sql );
		$stmt->setFetchMode ( PDO::FETCH_CLASS, 'UserDungeonFloor' );
		$values = array (
				$user_id
		);
		$stmt->execute ( $values );
		$objs = $stmt->fetchAll ( PDO::FETCH_CLASS, 'UserDungeonFloor' );

		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $values )), Zend_Log::DEBUG );
		}

		return $objs;
	}
}
