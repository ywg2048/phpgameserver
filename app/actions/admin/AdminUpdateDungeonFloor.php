<?php
/**
 * Admin用：ダンジョンフローの状態変更
 */
class AdminUpdateDungeonFloor extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$dungeon_floor_id = isset ( $params ['dfid'] ) ? $params ['dfid'] : null;
		$cleared = isset ( $params ['clr'] ) ? $params ['clr'] : 0;
		$reset = isset ( $params ['reset'] ) ? $params ['reset'] : 0;
		// #PADC_DY# ----------begin----------
		$star = isset ( $params ['star'] ) ? $params ['star'] : 0;
		if ($star > 3 || $star < 0 ){
			$star = 3;
		}
		// #PADC_DY# ----------end----------
		$user = null;
		if (isset ( $user_id )) {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
			$user = User::find ( $user_id, $pdo, true );
		}
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}
		
		try {
			$pdo->beginTransaction ();
			if (isset ( $dungeon_floor_id ) || $reset) {
				$this->resetUserDungeonFloor ( $user_id, $pdo );
				$user->clear_dungeon_cnt = 0;
			}
			
			if (isset ( $dungeon_floor_id ) && ! $reset) {
				$found = false;
				while ( ! $found ) {
					$udfs = $this->findUnclearedUserDungeonFloors ( $user_id, $pdo );
					if (empty ( $udfs )) {
						$found = true;
						break;
					}
					foreach ( $udfs as $udf ) {
						if ($udf->dungeon_floor_id == $dungeon_floor_id && ! $cleared) {
							$found = true;
							break;
						}

						$udf->first_played_at = UserDungeon::timeToStr ( time () );
						$udf->cleared_at = UserDungeon::timeToStr ( time () );
						// #PADC_DY#
						$udf->max_star = $star;
						$udf->update ( $pdo );
						
						$redis = Env::getRedisForUser();
						$redis->delete ( CacheKey::getUserClearDungeonFloors ( $user_id ) );
						
						$user->clear_dungeon_cnt = UserDungeon::getClearCountDungeon ( $user_id, $pdo );
						
						if ($udf->dungeon_floor_id == $dungeon_floor_id && $cleared) {
							$found = true;
							break;
						}
					}
					
					// 条件を満たすフロアを開放
					$opened_udfs = UserDungeonFloor::getOpenFloor ( $user, $pdo );
				}
			}
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
