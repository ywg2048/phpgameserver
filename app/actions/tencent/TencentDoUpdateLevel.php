<?php
/**
 * IDIP
 * ユーザーレベル変更
 */
class TencentDoUpdateLevel extends TencentBaseAction {
	const MIN_LEVEL = 1;
	const CLR_LOCK = 0;
	const CLR_OPEN = 1;
	const CLR_CLEAR = 2;
	const CLR_SET_LOCK = 3;
	const CLR_SET_OPEN = 4;
	const CLR_SET_CLEAR = 5;
	const ENABLE_RANKING_DUNGEON = false;
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$openid = $params ['OpenId'];
		$type = $params ['PlatId'];
		$value = $params ['Value'];
		$beginValue = 0;
		$endValue = 0;
		
		$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		$redis = Env::getRedisForUser();
		$redis->delete ( CacheKey::getUserClearDungeonFloors ( $user_id ) );

		/*
		$dungeons = Dungeon::getAll ();
		$dungeon_floors = DungeonFloor::getAll ();
		$user_dungeon_floors = UserDungeonFloor::findAllBy ( array (
				'user_id' => $user_id 
		), null, null, $pdo );
		$dungeon_list = self::getDungeonList ( $dungeons, $dungeon_floors, $user_dungeon_floors );
		*/
		//global $logger;
		// $logger->log ( '$dungeon_list:' . print_r ( $dungeon_list, true ), 7 );

		/*
		if (self::ENABLE_RANKING_DUNGEON) {
			$rank_dungs = RankingDungeon::getAll ();
			$rank_dung_floors = RankingDungeonFloor::getAll ();
			$user_rank_dung_floors = UserRankingDungeonFloor::findAllBy ( array (
					'user_id' => $user_id 
			), null, null, $pdo );
			$rank_dung_list = self::getDungeonList ( $rank_dungs, $rank_dung_floors, $user_rank_dung_floors );
			
			// $logger->log ( '$rank_dung_list:' . print_r ( $rank_dung_list, true ), 7 );
		}
		*/

		// $logger->log ( ':' . count ( $dungeon_list ) . ' ' . count ( $rank_dung_list ), 7 );
		
		// -----debug-----
		// $lock_list = array ();
		// $open_list = array ();
		// $clear_list = array ();
		// foreach ( $dungeon_list as $dungeon_id => $dungeon ) {
		// if ($dungeon ['clear'] == self::CLR_LOCK) {
		// $lock_list [] = $dungeon_id;
		// }
		// if ($dungeon ['clear'] == self::CLR_OPEN) {
		// $open_list [] = $dungeon_id;
		// }
		// if ($dungeon ['clear'] == self::CLR_CLEAR) {
		// $clear_list [] = $dungeon_id;
		// }
		// }
		// $logger->log ( 'lock:(' . count ( $lock_list ) . ')' . print_r ( $lock_list, true ), 7 );
		// $logger->log ( 'open:(' . count ( $open_list ) . ')' . print_r ( $open_list, true ), 7 );
		// $logger->log ( 'clear:(' . count ( $clear_list ) . ')' . print_r ( $clear_list, true ), 7 );
		// ---------------
		//$logger->log ( 'clear_cnt:' . self::count_clear ( $dungeon_list ), 7 );
		// $clear_cnt = self::count_clear ( $dungeon_list );
		//if (self::ENABLE_RANKING_DUNGEON) {
		//	$logger->log ( 'clear_cnt:' . self::count_clear ( $rank_dung_list ), 7 );
		//}
		
		$user = User::find ( $user_id, $pdo, true );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		//$logger->log ( 'user->clear_dungeon_cnt:' . $user->clear_dungeon_cnt, 7 );
		// $logger->log('user:'.print_r($user, true), 7);
		// $beginValue = $user->clear_dungeon_cnt;
		$beginValue = $user->lv;

		$endValue = $beginValue + $value;
		if ($endValue < self::MIN_LEVEL) {
			$endValue = self::MIN_LEVEL;
		}
		$max_level = LevelUp::MAX_USER_LEVEL;
		if ($endValue > $max_level) {
			$endValue = $max_level;
		}


		self::updateUserLevel($user, $endValue, $pdo);

		/*
		if (self::ENABLE_RANKING_DUNGEON) {
			$begin_value_normal = $beginValue;
			$begin_value_rank = self::count_clear ( $rank_dung_list );
			$beginValue = $begin_value_normal + $begin_value_rank;
		}
		if (! self::ENABLE_RANKING_DUNGEON) {
			$target_clear_cnt = $beginValue + $value;
			if ($target_clear_cnt < self::MIN_LEVEL) {
				$target_clear_cnt = self::MIN_LEVEL;
			}
			$max_level = LevelUp::MAX_USER_LEVEL;
			if ($target_clear_cnt > $max_level) {
				$target_clear_cnt = $max_level;
			}
			$add_clear = $target_clear_cnt - $beginValue;
		} else {
			if ($value >= 0) {
				$target_clear_cnt_normal = $begin_value_normal + $value;
				$target_clear_cnt_rank = $begin_value_rank;
			} else {
				$target_clear_cnt_normal = $begin_value_normal;
				$target_clear_cnt_rank = $begin_value_rank + $value;
				if ($target_clear_cnt_rank < 0) {
					$target_clear_cnt_normal += $target_clear_cnt_rank;
					$target_clear_cnt_rank = 0;
				}
			}
			if ($target_clear_cnt_normal < self::MIN_LEVEL) {
				$target_clear_cnt_normal = self::MIN_LEVEL;
			}
			$max_level_normal = self::getMaxLevel ( $dungeon_list );
			$max_level_ranking = self::getMaxLevel ( $rank_dung_list );
			//$logger->log ( '$max_level_normal:' . $max_level_normal . ' $max_level_ranking:' . $max_level_ranking, 7 );
			if ($target_clear_cnt_normal > $max_level_normal) {
				$target_clear_cnt_rank += $target_clear_cnt_normal - $max_level_normal;
				$target_clear_cnt_normal = $max_level_normal;
				if ($target_clear_cnt_rank > $max_level_ranking) {
					$target_clear_cnt_rank = $max_level_ranking;
				}
			}
			$add_clear = $target_clear_cnt_normal - $begin_value_normal;
			$add_clear_rank = $target_clear_cnt_rank - $begin_value_rank;
			//$logger->log ( '$add_clear:' . $add_clear . ' $add_clear_rank:' . $add_clear_rank, 7 );
		}
		
		if ($add_clear > 0) {
			self::addClear ( $dungeon_list, $add_clear ,$user);
		} else if ($add_clear < 0) {
			self::removeClear ( $dungeon_list, - $add_clear );
		}
		if (self::ENABLE_RANKING_DUNGEON) {
			if ($add_clear_rank > 0) {
				self::addClear ( $rank_dung_list, $add_clear_rank );
			} else if ($add_clear_rank < 0) {
				self::removeClear ( $rank_dung_list, - $add_clear_rank );
			}
		}
		$endValue = $user->lv + $add_clear;
		// $endValue = UserDungeon::getClearCountDungeon ( $user->id, $pdo );
		if (self::ENABLE_RANKING_DUNGEON) {
			$endValue += self::count_clear ( $rank_dung_list );
		}
		
		// if ($add_clear != 0) {
		if (! self::ENABLE_RANKING_DUNGEON) {
			self::apply ( $dungeon_list, $user_dungeon_floors, $user, $pdo, $endValue);
		} else {
			self::apply ( $dungeon_list, $user_dungeon_floors, $user, $pdo, $endValue, $rank_dung_list, $user_rank_dung_floors );
		}
		// }
		//$logger->log ( 'clear_cnt:' . self::count_clear ( $dungeon_list ), 7 );
		//$logger->log ( 'getClearCountDungeon:' . UserDungeon::getClearCountDungeon ( $user->id, $pdo ), 7 );
		*/


		return (json_encode ( array (
				'res' => 0,
				'msg' => 'OK',
				'Result' => 0,
				'RetMsg' => 'OK',
				'BeginValue' => $beginValue,
				'EndValue' => $endValue 
		) ));
	}
	
	/**
	 * generate dungeon info array
	 *
	 * @param array<Dungeon> $dungeons        	
	 * @param array<DungeonFloor> $dungeon_floors        	
	 * @param array<UserDungeonFloor> $user_dungeon_floors        	
	 * @return array
	 */
	private static function getDungeonList($dungeons, $dungeon_floors, $user_dungeon_floors) {
		$dungeon_list = array ();
		
		foreach ( $dungeons as $dungeon ) {
			$dungeon_list [$dungeon->id] = array (
					'floors' => array (),
					'prev_dung' => 0,
					'next_dungs' => array (),
					'rankup' => $dungeon->rankup_flag 
			);
		}
		
		foreach ( $dungeon_floors as $dungeon_floor ) {
			$floor = array (
					'clear' => self::CLR_LOCK 
			);
			if (isset ( $dungeon_list [$dungeon_floor->dungeon_id] )) {
				$dungeon_list [$dungeon_floor->dungeon_id] ['floors'] [$dungeon_floor->id] = $floor;
				
				if ($dungeon_floor->id % 1000 == 1) {
					$prev_dung_id = ( int ) ($dungeon_floor->prev_dungeon_floor_id / 1000);
					if (isset ( $dungeon_list [$prev_dung_id] )) {
						$dungeon_list [$dungeon_floor->dungeon_id] ['prev_dung'] = $prev_dung_id;
						$dungeon_list [$prev_dung_id] ['next_dungs'] [] = ($dungeon_floor->dungeon_id);
					}
					$dungeon_list [$dungeon_floor->dungeon_id] ['open_rank'] = $dungeon_floor->open_rank;
				}
			}
		}
		
		//global $logger;
		foreach ( $user_dungeon_floors as $udf ) {
			$floor = &$dungeon_list [$udf->dungeon_id] ['floors'] [$udf->dungeon_floor_id];
			if (isset ( $udf->cleared_at ) && $udf->cleared_at != '0000-00-00 00:00:00') {
				//$logger->log ( 'floor ' . $udf->dungeon_floor_id . ' clear', 7 );
				$floor ['clear'] = self::CLR_CLEAR;
			} else {
				//$logger->log ( 'floor ' . $udf->dungeon_floor_id . ' open', 7 );
				$floor ['clear'] = self::CLR_OPEN;
			}
		}
		//if (isset ( $dungeon_list [164] )) {
			//$logger->log ( '164 before:' . print_r ( $dungeon_list [164], true ), 7 );
		//}
		
		foreach ( $dungeon_list as $dungeon_id => &$dungeon ) {
			// if (isset ( $dungeon_list [164] )) {
			// $logger->log ( 'did:' . $dungeon_id . ' 164:' . print_r ( $dungeon_list [164], true ), 7 );
			// }
			$clear = self::CLR_CLEAR;
			foreach ( $dungeon ['floors'] as &$floor ) {
				if ($floor ['clear'] != self::CLR_CLEAR) {
					$clear = self::CLR_LOCK;
					break;
				}
			}
			$dungeon ['clear'] = $clear;
		}
		//if (isset ( $dungeon_list [164] )) {
		//	$logger->log ( '164: after' . print_r ( $dungeon_list [164], true ), 7 );
		//}
		
		// if (count ( $dungeon_list ) == 34) {
		// self::aaa ();
		// }
		
		return $dungeon_list;
	}
	
	/**
	 * count cleared dungeon
	 *
	 * @param array $dungeon_list        	
	 * @return number
	 */
	private static function count_clear(&$dungeon_list) {
		$count = 0;
		foreach ( $dungeon_list as $dungeon ) {
			if (($dungeon ['clear'] == self::CLR_CLEAR || $dungeon ['clear'] == self::CLR_SET_CLEAR) && $dungeon ['rankup']) {
				$count ++;
			}
		}
		return $count;
	}
	
	/**
	 * add cleared dungeon num
	 *
	 * @param array $dungeon_list        	
	 * @param number $add_num        	
	 */
	private static function addClear(&$dungeon_list, $add_num, $user) {
		//global $logger;
		//$logger->log ( 'addClear ' . $add_num, 7 );
		$cnt = $add_num;
		$clear_cnt = self::count_clear ( $dungeon_list );
		$rank = $user->lv;
		while ( $cnt > 0 ) {
			foreach ( $dungeon_list as $dungeon_id => &$dungeon ) {
				if (! self::isCleared ( $dungeon_list, $dungeon_id ) && self::isCleared ( $dungeon_list, $dungeon ['prev_dung'] ) && $rank >= $dungeon ['open_rank']) {
					//$logger->log ( 'found dungeon ' . $dungeon_id . ' can clear', 7 );
					self::setClear ( $dungeon_list, $dungeon_id );
					if ($dungeon ['rankup']) {
						$rank ++;
						$cnt --;
					}
					break;
				}
			}
		}
	}
	
	/**
	 * dec cleared dungeon num
	 *
	 * @param array $dungeon_list        	
	 * @param number $remove_num        	
	 */
	private static function removeClear(&$dungeon_list, $remove_num) {
		//global $logger;
		//$logger->log ( 'removeClear ' . $remove_num, 7 );
		$cnt = $remove_num;
		while ( $cnt > 0 ) {
			foreach ( $dungeon_list as $dungeon_id => &$dungeon ) {
				if ($dungeon ['clear'] == self::CLR_CLEAR && self::isNoneNextCleared ( $dungeon_list, $dungeon_id )) {
					self::unsetClear ( $dungeon_list, $dungeon_id );
					if ($dungeon ['rankup']) {
						$cnt --;
					}
					break;
				}
			}
		}
	}
	
	/**
	 * apply change to DB
	 *
	 * @param array $dungeon_list        	
	 * @param array<UserDungeonFloor> $user_dungeon_floors        	
	 * @param User $user        	
	 * @param PDO $pdo        	
	 * @param array $rank_dung_list        	
	 * @param array<UserRankingDungeonFloor> $user_rank_dung_floors        	
	 * @throws Exception
	 */
	private static function apply(&$dungeon_list, &$user_dungeon_floors, $user, $pdo, $endValue, $rank_dung_list = null, $user_rank_dung_floors = null) {
		//global $logger;
		//$logger->log ( 'applyClear', 7 );
		try {
			$pdo->beginTransaction ();
			
			self::applyExist ( $dungeon_list, $user_dungeon_floors, $pdo );
			self::applyCreate ( $dungeon_list, $user_dungeon_floors, $user, $pdo );
			$user->lv = $endValue;
			// 修改等级调整为当前等级的满经验-1的状态。
			$newLevel = LevelUp::get($user->lv + 1);
			$user->exp = $newLevel->required_experience - 1;
			
			if (isset ( $rank_dung_list ) && isset ( $user_rank_dung_floors )) {
				self::applyExist ( $rank_dung_list, $user_rank_dung_floors, $pdo );
				self::applyCreate ( $rank_dung_list, $user_rank_dung_floors, $user, $pdo, true );
				$user->lv = $endValue;
				$newLevel = LevelUp::get($user->lv);
				$user->exp = $newLevel->required_experience;
			}
			
			// $user->clear_dungeon_cnt = UserDungeon::getClearCountDungeon ( $user->id, $pdo );
			//$logger->log ( 'user->clear_dungeon_cnt:' . $user->clear_dungeon_cnt, 7 );
			$user->update ( $pdo );
			
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo && $pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
	}
	
	/**
	 * apply change to exist db record
	 *
	 * @param array $dungeon_list        	
	 * @param array<UserDungeonFloor> $user_dungeon_floors        	
	 * @param PDO $pdo        	
	 */
	private static function applyExist(&$dungeon_list, &$user_dungeon_floors, $pdo) {
		//global $logger;
		//$logger->log ( 'applyExist', 7 );
		foreach ( $user_dungeon_floors as $udf ) {
			if (isset ( $dungeon_list [$udf->dungeon_id] )) {
				$dungeon = &$dungeon_list [$udf->dungeon_id];
				if (isset ( $dungeon ['floors'] [$udf->dungeon_floor_id] )) {
					$floor = &$dungeon ['floors'] [$udf->dungeon_floor_id];
					if ($floor ['clear'] == self::CLR_SET_CLEAR) {
						//$logger->log ( 'applyExist ' . $udf->dungeon_floor_id . ' set clear', 7 );
						if (! isset ( $udf->cleared_at ) || $udf->cleared_at == '0000-00-00 00:00:00') {
							$udf->cleared_at = BaseModel::timeToStr ( time () );
							$udf->update ( $pdo );
						}
						$floor ['clear'] = self::CLR_CLEAR;
					} else if ($floor ['clear'] == self::CLR_SET_LOCK) {
						//$logger->log ( 'applyExist ' . $udf->dungeon_floor_id . ' set lock', 7 );
						$udf->delete ( $pdo );
						$floor ['clear'] = self::CLR_LOCK;
					} else if ($floor ['clear'] == self::CLR_SET_OPEN) {
						//$logger->log ( 'applyExist ' . $udf->dungeon_floor_id . ' set open', 7 );
						if (isset ( $udf->cleared_at )) {
							$udf->cleared_at = null;
							$udf->update ( $pdo );
						}
						$floor ['clear'] = self::CLR_OPEN;
					}
				}
			}
		}
	}
	
	/**
	 * create new user dungeon floor
	 *
	 * @param array $dungeon_list        	
	 * @param array $user_dungeon_floors        	
	 * @param User $user        	
	 * @param PDO $pdo        	
	 * @param boolean $isranking        	
	 */
	private static function applyCreate(&$dungeon_list, &$user_dungeon_floors, $user, $pdo, $isranking = false) {
		//global $logger;
		//$logger->log ( 'applyCreate', 7 );
		foreach ( $dungeon_list as $dungeon_id => &$dungeon ) {
			foreach ( $dungeon ['floors'] as $floor_id => &$floor ) {
				if ($floor ['clear'] == self::CLR_SET_CLEAR) {
					//$logger->log ( 'apply ' . $floor_id . ' set clear', 7 );
					if (! $isranking) {
						self::createUserDungeonFloor ( $user->id, $dungeon_id, $floor_id, 1, $pdo );
					} else {
						self::createUserRankingDungeonFloor ( $user->id, $dungeon_id, $floor_id, 1, $pdo );
					}
				} else if ($floor ['clear'] == self::CLR_SET_OPEN) {
					//$logger->log ( 'apply ' . $floor_id . ' set open', 7 );
					if (! $isranking) {
						self::createUserDungeonFloor ( $user->id, $dungeon_id, $floor_id, 0, $pdo );
					} else {
						self::createUserRankingDungeonFloor ( $user->id, $dungeon_id, $floor_id, 0, $pdo );
					}
				}
			}
		}
	}
	
	/**
	 * check dungeon cleared or not
	 *
	 * @param array $dungeon_list        	
	 * @param number $gungeon_id        	
	 * @return boolean
	 */
	private static function isCleared(&$dungeon_list, $gungeon_id) {
		if ($gungeon_id == 0) {
			return true;
		}
		$dungeon = $dungeon_list [$gungeon_id];
		$clear = $dungeon ['clear'];
		return ($clear == self::CLR_CLEAR || $clear == self::CLR_SET_CLEAR);
	}
	
	/**
	 * set dungeon cleared
	 *
	 * @param array $dungeon_list        	
	 * @param number $dungeon_id        	
	 */
	private static function setClear(&$dungeon_list, $dungeon_id) {
		//global $logger;
		//$logger->log ( 'setClear ' . $dungeon_id, 7 );
		$dungeon = &$dungeon_list [$dungeon_id];
		if ($dungeon ['clear'] != self::CLR_CLEAR) {
			//$logger->log ( 'dungeon ' . $dungeon_id . ' set clear', 7 );
			$dungeon ['clear'] = self::CLR_SET_CLEAR;
		}
		foreach ( $dungeon ['floors'] as $floor_id => &$floor ) {
			if ($floor ['clear'] != self::CLR_CLEAR) {
				//$logger->log ( 'floor ' . $floor_id . ' set clear', 7 );
				$floor ['clear'] = self::CLR_SET_CLEAR;
			}
		}
		foreach ( $dungeon ['next_dungs'] as $next_dung_id ) {
			$next_dungeon = &$dungeon_list [$next_dung_id];
			if ($next_dungeon ['clear'] != self::CLR_OPEN) {
				//$logger->log ( 'next dungeon ' . $next_dung_id . ' set open', 7 );
				$next_dungeon ['clear'] = self::CLR_SET_OPEN;
			}
			$cnt = 0;
			foreach ( $next_dungeon ['floors'] as $floor_id => &$floor ) {
				if ($cnt == 0) {
					if ($floor ['clear'] != self::CLR_OPEN) {
						//$logger->log ( 'next floor ' . $floor_id . ' ' . $floor ['clear'] . ' set open', 7 );
						$floor ['clear'] = self::CLR_SET_OPEN;
					}
				} else {
					if ($floor ['clear'] != self::CLR_LOCK) {
						//$logger->log ( 'next floor ' . $floor_id . ' ' . $floor ['clear'] . ' set lock', 7 );
						$floor ['clear'] = self::CLR_SET_LOCK;
					}
				}
				$cnt ++;
			}
		}
	}
	
	/**
	 * set dungeon not cleared
	 *
	 * @param array $dungeon_list        	
	 * @param number $dungeon_id        	
	 */
	private static function unsetClear(&$dungeon_list, $dungeon_id) {
		//global $logger;
		//$logger->log ( 'unsetClear ' . $dungeon_id, 7 );
		$dungeon = &$dungeon_list [$dungeon_id];
		
		foreach ( $dungeon ['next_dungs'] as $next_dung_id ) {
			$next_dungeon = &$dungeon_list [$next_dung_id];
			if ($next_dungeon ['clear'] != self::CLR_LOCK) {
				//$logger->log ( 'next dungeon ' . $next_dung_id . ' ' . $next_dungeon ['clear'] . ' set lock', 7 );
				$next_dungeon ['clear'] = self::CLR_SET_LOCK;
			}
			foreach ( $next_dungeon ['floors'] as $floor_id => &$floor ) {
				if ($floor ['clear'] != self::CLR_LOCK) {
					//$logger->log ( 'next floor ' . $floor_id . ' ' . $floor ['clear'] . ' set lock', 7 );
					$floor ['clear'] = self::CLR_SET_LOCK;
				} else {
					//$logger->log ( 'next floor ' . $floor_id . ' ' . $floor ['clear'], 7 );
				}
			}
		}
		
		if ($dungeon ['clear'] != self::CLR_OPEN) {
			//$logger->log ( 'dungeon ' . $dungeon_id . ' ' . $dungeon ['clear'] . ' set open', 7 );
			$dungeon ['clear'] = self::CLR_SET_OPEN;
		}
		$cnt = 0;
		foreach ( $dungeon ['floors'] as $floor_id => &$floor ) {
			// set first floor open, set rest floor lock
			if ($cnt == 0) {
				if ($floor ['clear'] != self::CLR_OPEN) {
					//$logger->log ( 'floor ' . $floor_id . ' ' . $floor ['clear'] . ' set open', 7 );
					$floor ['clear'] = self::CLR_SET_OPEN;
				}
			} else {
				if ($floor ['clear'] != self::CLR_LOCK) {
					//$logger->log ( 'next floor ' . $floor_id . ' ' . $floor ['clear'] . ' set lock', 7 );
					$floor ['clear'] = self::CLR_SET_LOCK;
				}
			}
			$cnt ++;
		}
	}
	
	/**
	 * check if none of next dungeons is cleared
	 *
	 * @param array $dungeon_list        	
	 * @param number $dungeon_id        	
	 * @return boolean
	 */
	private static function isNoneNextCleared(&$dungeon_list, $dungeon_id) {
		$clear = 0;
		foreach ( $dungeon_list [$dungeon_id] ['next_dungs'] as $next_dungeon_id ) {
			if (self::isCleared ( $dungeon_list, $next_dungeon_id )) {
				$clear = 1;
				break;
			}
		}
		return (! $clear);
	}
	
	/**
	 * count max dungeons can be cleared
	 *
	 * @param array $dungeon_list        	
	 * @return number
	 */
	private static function getMaxLevel(&$dungeon_list) {
		$max_level = 0;
		foreach ( $dungeon_list as $dungeon_id => &$dungeon ) {
			if ($dungeon ['rankup']) {
				$max_level ++;
			}
		}
		return $max_level;
	}
	
	/**
	 * create new UserDungeonFloor
	 *
	 * @param number $user_id        	
	 * @param number $dungeon_id        	
	 * @param number $dungeon_floor_id        	
	 * @param number $cleared        	
	 * @param PDO $pdo        	
	 */
	private static function createUserDungeonFloor($user_id, $dungeon_id, $dungeon_floor_id, $cleared, $pdo) {
		$udf = new UserDungeonFloor ();
		$udf->user_id = $user_id;
		$udf->dungeon_id = $dungeon_id;
		$udf->dungeon_floor_id = $dungeon_floor_id;
		if ($cleared) {
			$time = BaseModel::timeToStr ( time () );
			$udf->first_played_at = $time;
			$udf->cleared_at = $time;
		} else {
			$udf->first_played_at = null;
			$udf->cleared_at = null;
		}
		$udf->cm1_first_played_at = null;
		$udf->cm1_cleared_at = null;
		$udf->cm2_first_played_at = null;
		$udf->cm2_cleared_at = null;
		$udf->daily_cleared_at = null;
		$udf->create ( $pdo );
	}
	
	/**
	 * create new UserRankingDungeonFloor
	 *
	 * @param unknown $user_id        	
	 * @param unknown $dungeon_id        	
	 * @param unknown $dungeon_floor_id        	
	 * @param unknown $cleared        	
	 * @param unknown $pdo        	
	 */
	private static function createUserRankingDungeonFloor($user_id, $dungeon_id, $dungeon_floor_id, $cleared, $pdo) {
		$urdf = new UserRankingDungeonFloor ();
		$urdf->user_id = $user_id;
		$urdf->dungeon_id = $dungeon_id;
		$urdf->dungeon_floor_id = $dungeon_floor_id;
		if ($cleared) {
			$time = BaseModel::timeToStr ( time () );
			$urdf->first_played_at = $time;
			$urdf->cleared_at = $time;
		} else {
			$urdf->first_played_at = null;
			$urdf->cleared_at = null;
		}
		$urdf->cm1_first_played_at = null;
		$urdf->cm1_cleared_at = null;
		$urdf->cm2_first_played_at = null;
		$urdf->cm2_cleared_at = null;
		$urdf->create ( $pdo );
	}

	/**
	 * PADC_DY# 更新等级逻辑不需要开启其他关卡，只做等级变更操作
	 * @param $user
	 * @param $new_lv
	 * @throws Exception
	 */
	private static function updateUserLevel($user, $new_lv, $pdo){
		try {
			$pdo->beginTransaction ();
			$user->lv = $new_lv;
			// 修改等级调整为当前等级的满经验-1的状态。
			$newLevel = LevelUp::get($user->lv + 1);
			$user->exp = $newLevel->required_experience - 1;

			$user->update ( $pdo );

			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo && $pdo->inTransaction ()) {
				$pdo->rollback ();
			}
			throw $e;
		}
	}
}