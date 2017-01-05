<?php
/**
 * #PADC#
 * ユーザーが挑戦した、もしくは挑戦中のミッションのクラス
 */
class UserMission extends BaseModel
{
	const TABLE_NAME = "user_missions";

	// ミッション状況
	const STATE_NONE		= 0;	// 未開放
	const STATE_CHALLENGE	= 1;	// 挑戦中
	const STATE_CLEAR		= 2;	// クリア（報酬受け取り可能）
	const STATE_RECEIVED	= 3;	// 報酬受け取り済み
	//const STATE_EXPIRED		= 4;	// 期限切れ
	
	// 連続ログインボーナス用の特殊対応
	const PROCESS_LOGIN_STREAK			= true;
	const PROCESS_LOGIN_STREAK_OLD		= false;	// 連続ログインボーナス旧処理
	

	// ID、ユーザーID、ミッションID、ミッション状況、進捗、受注日時
	protected static $columns = array(
		'id',
		'user_id',
		'mission_id',
		'status',
		'progress_num',
		'progress_max',
		'ordered_at',
	);


	/**
	 * 報酬受け取り可能なミッションの数を返す
	 */
	public static function getClearCount($user_id, $pdo=null)
	{
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		$params = array(
			'user_id' => $user_id,
			'status' => UserMission::STATE_CLEAR,
		);
		$clear_mission_count = self::countAllBy($params, $pdo);

		// 連続ログインボーナス用の特殊対応
		if (self::PROCESS_LOGIN_STREAK_OLD) {
			// 連続ログインボーナスを受け取った時間をチェック
			$user = User::find($user_id);
			$now = time();
			// 当日受け取っていなければカウント+1
			if (!static::isSameDay_AM4($now, static::strToTime($user->li_mission_date))) {
				$clear_mission_count += 1;
			}
		}

		return (int)$clear_mission_count;
	}

	/**
	 * アプリで表示するミッションを返す
	 */
	public static function getMissionList($user_id)
	{
		$user = User::find($user_id);
		// 現在時間と最終ログインが同日かチェックする
		// 異なる場合はエラーを返して再ログインしてもらう
		$now = time();
		if (!static::isSameDay_AM4($now, static::strToTime($user->li_last))) {
			throw new PadException(RespCode::LOGIN_DATE_DIFFERENT, "login date different");
		}
		
		// ミッションデータを全取得
		$all_missions = Mission::getAll();
		$missions = array();
		$mission_groups = array();
		foreach ($all_missions as $mission) {
			$mission_id = $mission->id;
			$group_id = $mission->group_id;
			$missions[$mission_id] = $mission;
			if ($mission->isEnabled($now) && $mission->isSetTimeZone() && $group_id > 0) {
				
				$time_zone_start = ($mission->time_zone_start >= 400) ? $mission->time_zone_start : $mission->time_zone_start + 2400;

				if (array_key_exists($group_id, $mission_groups)) {
					$mission_groups[$group_id]["ids"][] = $mission_id;
					$mission_groups[$group_id]["tzs"][] = $time_zone_start;
				}
				else {
					$mission_groups[$group_id] = array(
						"ids" => array($mission_id),
						"tzs" => array($time_zone_start),
						"add_index" => -1,
						"user_mission" => null,
					);
				}
			}
		}
		// 受取時間の早い順にIDをソート
		foreach ($mission_groups as $group_id => $group) {
			array_multisort($mission_groups[$group_id]["tzs"], SORT_ASC, $mission_groups[$group_id]["ids"]);
		}

		// 挑戦中と報酬受け取り可能なものだけを抽出する
		$states = array(
			UserMission::STATE_CHALLENGE,
			UserMission::STATE_CLEAR,
		);
		$user_missions = UserMission::getByUserId($user_id, $states);

		// 通常タブ、特殊タブ表示用のバッファ
		$special_missions = array();
		$special_statuses = array();
		$special_sort_ids = array();
		$normal_missions = array();
		$normal_statuses = array();
		$normal_sort_ids = array();
		
		foreach ($user_missions as $key => $user_mission) {
			$mission_id = $user_mission->mission_id;
			if (array_key_exists($mission_id, $missions)) {
				$mission = $missions[$mission_id];

				// 有効なものだけ選別する
				if (!$mission->isEnabled($now) || $mission->getCheckTimeZone($now) == Mission::TIME_ZONE_OVER) {
					continue;
				}
				
				$group_id = $mission->group_id;
				// グループIDが設定されているものは1つだけしか表示させないため、どれを表示させるか判別する（配列への追加は後回し）
				if ($mission->isSetTimeZone() && $group_id > 0) {
					$_index = array_search($mission_id, $mission_groups[$group_id]["ids"]);
					$_tzs = $mission_groups[$group_id]["tzs"][$_index];
						
					$add_index = $mission_groups[$group_id]["add_index"];
					if ($add_index < 0) {
						$mission_groups[$group_id]["add_index"] = $_index;
						$mission_groups[$group_id]["user_mission"] = $user_mission;
					}
					else {
						$add_tzs = $mission_groups[$group_id]["tzs"][$add_index];
						if ($_tzs < $add_tzs) {
							$mission_groups[$group_id]["add_index"] = $_index;
							$mission_groups[$group_id]["user_mission"] = $user_mission;
						}
					}
				}
				else {
					if ($mission->tab_category == Mission::TAB_CATEGORY_SPECIAL) {
						$special_missions[] = $user_mission;
						$special_statuses[] = $user_mission->status;
						$special_sort_ids[] = $mission->sort_id;
					}
					else {
						$normal_missions[] = $user_mission;
						$normal_statuses[] = $user_mission->status;
						$normal_sort_ids[] = $mission->sort_id;
					}
				}
			}
		}
		
		// グループIDの中から一つだけ追加する
		foreach ($mission_groups as $group_id => $group) {
			$user_mission = $group["user_mission"];
			if ($user_mission) {
				$mission_id = $user_mission->mission_id;
				$mission = $missions[$mission_id];
				if ($mission->tab_category == Mission::TAB_CATEGORY_SPECIAL) {
					$special_missions[] = $user_mission;
					$special_statuses[] = $user_mission->status;
					$special_sort_ids[] = $mission->sort_id;
				} else {
					$normal_missions[] = $user_mission;
					$normal_statuses[] = $user_mission->status;
					$normal_sort_ids[] = $mission->sort_id;
				}
			}
		}
		
		
		// 連続ログインボーナス用の特殊対応
		if (self::PROCESS_LOGIN_STREAK) {
				
			// 次の日の連続ログインボーナスミッション
			list($nextday_missions, $user_login_str, $next_login_str) = Mission::getLoginStreakMissonsNextDay($user->li_str, $user->li_period);
			foreach ($nextday_missions as $nextday_mission) {
				$user_mission = new UserMission();
				$user_mission->mission_id = $nextday_mission->id;
				$user_mission->status = UserMission::STATE_CHALLENGE;
				$user_mission->progress_num = $user_login_str;
				$user_mission->progress_max = $next_login_str;
				
				if ($nextday_mission->tab_category == Mission::TAB_CATEGORY_SPECIAL) {
					$special_missions[] = $user_mission;
					$special_statuses[] = UserMission::STATE_CHALLENGE;
					$special_sort_ids[] = $nextday_mission->sort_id;
				}
				else {
					$normal_missions[] = $user_mission;
					$normal_statuses[] = UserMission::STATE_CHALLENGE;
					$normal_sort_ids[] = $nextday_mission->sort_id;
				}
			}
				
			// 旧処理のコードは削除
		}
		
		
		// ミッション状態、ソート番号でソート
		array_multisort($normal_statuses, SORT_DESC, $normal_sort_ids, SORT_DESC, $normal_missions);
		array_multisort($special_statuses, SORT_DESC, $special_sort_ids, SORT_DESC, $special_missions);
		
		return array($normal_missions, $special_missions);
	}


	/**
	 * 指定した条件タイプのミッションのクリア判定を行う
	 * 条件タイプは配列でも指定可能
	 */
	public static function checkClearMissionTypes($user_id, $condition_type)
	{
		$user = User::find($user_id);
		if (!is_array($condition_type)) {
			$condition_type = array($condition_type);
		}

		$now = time();
		$clear_mission_ids = array();
		$sort_ids = array();
		
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			// ユーザーのミッションデータを全取得
			$get_user_missions = UserMission::getByUserId($user_id, null, $pdo);
			$user_missions = array();
			if ($get_user_missions) {
				foreach ($get_user_missions as $user_mission) {
					$user_missions[$user_mission->mission_id] = $user_mission;
				}
			}

			$user_dungeon_floors = null;
			$user_count = null;
			$dungeon_floor_clear_mission_keys = Mission::getDungeonFloorClearKeys();
			$user_count_mission_keys = Mission::getUserCountKeys();
				
			// 指定されたカテゴリタイプのミッションデータを全取得
			// クリア条件、開放条件を配列にして保持、条件から必要な情報をあらかじめ取得しておく
			$all_missions = Mission::getAll();
			$missions = array();
			foreach ($all_missions as $mission) {
				// 有効期間内でないものは無視
				if (!$mission->isEnabled($now)) {
					continue;
				}
				
				// 有効時間帯の設定がされているミッションは緊急ミッションとみなして必ずチェックする
				if (in_array($mission->condition_type, $condition_type) || $mission->isSetTimeZone()) {
					$mission->_clear_conditions = $mission->getClearConditions();
					$mission->_open_conditions = $mission->getOpenConditions();
					$missions[$mission->id] = $mission;
					
					if (is_null($user_dungeon_floors)) {
						foreach ($dungeon_floor_clear_mission_keys as $mission_key) {
							if (array_key_exists($mission_key, $mission->_clear_conditions)) {
								$user_dungeon_floors = UserDungeonFloor::getCleared($user_id, $pdo);
								break;
							}
							if (array_key_exists($mission_key, $mission->_open_conditions)) {
								$user_dungeon_floors = UserDungeonFloor::getCleared($user_id, $pdo);
								break;
							}
						}
					}
					if (is_null($user_count)) {
						foreach ($user_count_mission_keys as $mission_key) {
							if (array_key_exists($mission_key, $mission->_clear_conditions)) {
								$user_count = UserCount::getByUserId($user_id, $now, $pdo);
								break;
							}
							if (array_key_exists($mission_key, $mission->_open_conditions)) {
								$user_count = UserCount::getByUserId($user_id, $now, $pdo);
								break;
							}
						}
					}
				}
			}

			// ミッションのクリア判定を行う
			foreach ($missions as $mission_id => $mission) {

				$check_open = false;
				$check_clear = false;
				$is_create = false;

				if (array_key_exists($mission_id, $user_missions)) {
					$user_mission = $user_missions[$mission_id];

					// 挑戦中の場合、クリア判定を行う
					if ($user_mission->status == UserMission::STATE_CHALLENGE) {
						$check_clear = true;
					}
					// デイリーミッションの場合、受注日時が当日でなければクリア判定を行う
					else if ($mission->mission_type == Mission::MISSION_TYPE_DAILY) {
						$ordered_at = BaseModel::strToTime($user_mission->ordered_at);
						if (!BaseModel::isSameDay_AM4($ordered_at, $now)) {
							$check_clear = true;
						}
					}
				}
				else {
					// まだユーザーミッションにデータが無い場合
					$is_create = true;
					$check_open = true;
				}
				
				if ($check_open) {
					// ミッションの開放判定を行う
					// 前提となるミッションがあるか、ある場合は報酬受け取り済かチェック
					$prev_mission_id = $mission->prev_id;
					if ($prev_mission_id == 0) {

						// 解放条件
						$open_result = self::checkOpenConditions($mission->_open_conditions, $user, $user_dungeon_floors, $user_count);
						if ($open_result) {
							$check_clear = true;
						}

					}
					else {
						if (array_key_exists($prev_mission_id, $user_missions)) {
							$prev_user_mission = $user_missions[$prev_mission_id];
							// 前提ミッションの報酬を受け取り済みの場合、クリア判定を行う
							if ($prev_user_mission->status == UserMission::STATE_RECEIVED) {

								// 解放条件
								$open_result = self::checkOpenConditions($mission->_open_conditions, $user, $user_dungeon_floors, $user_count);
								if ($open_result) {
									$check_clear = true;
								}

							}
						}
					}
				}
				
				if ($check_clear) {
					// 有効時間帯のチェック
					$check_timezone = $mission->getCheckTimeZone($now);
					
					// ミッションのクリア判定を行う
					if ($is_create) {
						$user_mission = new UserMission();
						$user_mission->user_id = $user_id;
						$user_mission->mission_id = $mission_id;
						if ($check_timezone == Mission::TIME_ZONE_OK) {
							$user_mission->checkClearConditions($mission->_clear_conditions, $user, $user_dungeon_floors, $user_count, $now);
						}
						else if ($check_timezone == Mission::TIME_ZONE_NG) {
							$user_mission->status = UserMission::STATE_CHALLENGE;
						}
						else if ($check_timezone == Mission::TIME_ZONE_OVER) {
							$user_mission->status = UserMission::STATE_NONE;
						}
						$user_mission->ordered_at = BaseModel::timeToStr($now);
						$user_mission->create($pdo);
						// クリアしたミッションIDを保持
						if ($user_mission->status == UserMission::STATE_CLEAR) {
							UserTlog::sendTlogMissionFlow($user, (int)$mission_id);
							$clear_mission_ids[] = (int)$mission_id;
							$sort_ids[] = (int)$mission->sort_id;
						}
					}
					else {
						$user_mission = $user_missions[$mission_id];
						$before_status = $user_mission->status;
						if ($check_timezone == Mission::TIME_ZONE_OK) {
							$user_mission->checkClearConditions($mission->_clear_conditions, $user, $user_dungeon_floors, $user_count, $now);
						}
						else if ($check_timezone == Mission::TIME_ZONE_NG) {
							$user_mission->status = UserMission::STATE_CHALLENGE;
						}
						else if ($check_timezone == Mission::TIME_ZONE_OVER) {
							$user_mission->status = UserMission::STATE_NONE;
						}
							// デイリーミッションの場合、当日受けたものでなければ受注日時を更新
						if ($mission->mission_type == Mission::MISSION_TYPE_DAILY) {
							$ordered_at = BaseModel::strToTime($user_mission->ordered_at);
							if (!BaseModel::isSameDay_AM4($ordered_at, $now)) {
								$user_mission->ordered_at = BaseModel::timeToStr($now);
							}
						}
						$user_mission->update($pdo);
						// クリアしたミッションIDを保持
						if ($user_mission->status == UserMission::STATE_CLEAR) {
							if ($before_status != UserMission::STATE_CLEAR) {
								UserTlog::sendTlogMissionFlow($user, (int)$mission_id);
							}
							$clear_mission_ids[] = (int)$mission_id;
							$sort_ids[] = (int)$mission->sort_id;
						}
					}
				}
			}

			$pdo->commit();

		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}

		// ソートID順にソート
		array_multisort($sort_ids, SORT_DESC, $clear_mission_ids);
		
		// 報酬を受け取れるミッションの数を返す
		return array(self::getClearCount($user_id), $clear_mission_ids);
	}

	/**
	 * ミッション報酬を受け取る
	 */
	public static function receiveReward($user_id, $mission_id, $token)
	{
		// 指定されたミッションデータを取得
		$mission = Mission::get($mission_id);
		if($mission == FALSE){
			throw new PadException(RespCode::UNKNOWN_ERROR, "mission not found (id=$mission_id)");
		}

		$user = User::find($user_id);
		$result = NULL;
		$item_offered = 1;//報酬付与済み
		$clear_mission_ids = array();
		$sort_ids = array();
		
		$params = array(
			'user_id' => $user_id,
			'mission_id' => $mission_id,
		);
		$user_mission = UserMission::findBy($params);

		// 現在時間と最終ログインが同日かチェックする
		// 異なる場合はエラーを返して再ログインしてもらう
		$now = time();
		if (!static::isSameDay_AM4($now, static::strToTime($user->li_last))) {
			throw new PadException(RespCode::LOGIN_DATE_DIFFERENT, "login date different");
		}

		// 連続ログインボーナスの場合とそれ以外で分岐
		if (self::PROCESS_LOGIN_STREAK_OLD && $mission->condition_type == Mission::CONDITION_TYPE_LOGIN_STREAK) {
			// 前回連続ログインボーナスを受け取った時間をチェック
			if (!static::isSameDay_AM4($now, static::strToTime($user->li_mission_date))) {
				// 報酬を付与
				$item_offered = 0;
			}
		}
		else {
			// ユーザーのミッションデータが報酬受け取り可能な状態かチェック
			if ($user_mission) {
				if ($user_mission->status <= UserMission::STATE_CHALLENGE) {
					throw new PadException(RespCode::UNKNOWN_ERROR, "mission is not clear (user_id=$user_id, mission_id=$mission_id)");
				}
				else if ($user_mission->status == UserMission::STATE_CLEAR) {
					// 受け取り可能な時間帯かチェックする
					if ($mission->getCheckTimeZone($now) == Mission::TIME_ZONE_OK) {
						// 報酬を付与
						$item_offered = 0;
					}
					else {
						throw new PadException(RespCode::MISSION_REWARD_TIME_OUT, "mission reward is time out");
					}
				}
			}
			else {
				throw new PadException(RespCode::UNKNOWN_ERROR, "mission is not clear (user_id=$user_id, mission_id=$mission_id)");
			}
		}

		if (!$item_offered) {
			// 報酬を付与
			try{
				$pdo = Env::getDbConnectionForUserWrite($user_id);
				$pdo->beginTransaction();

				// TLOG
				UserTlog::beginTlog($user, array(
					'money_reason' => Tencent_Tlog::REASON_BONUS,
					'money_subreason' => Tencent_Tlog::SUBREASON_MISSION_BONUS,
					'item_reason' => Tencent_Tlog::ITEM_REASON_BONUS,
					'item_subreason' => Tencent_Tlog::ITEM_SUBREASON_MISSION_BONUS,
				));

				$user = User::find($user_id, $pdo, TRUE);
				$gold_before = $user->gold;
				$pgold_before = $user->pgold;
				//$coin_before = $user->coin;
				$result = $user->applyBonus($mission->bonus_id, $mission->amount, $pdo, null, $token, $mission->piece_id);
				$gold_after = $user->gold;
				$pgold_after = $user->pgold;
				//$coin_after = $user->coin;

				// 連続ログインボーナスを受け取った時間を更新
				if (self::PROCESS_LOGIN_STREAK_OLD && $mission->condition_type == Mission::CONDITION_TYPE_LOGIN_STREAK) {
					$user->li_mission_date = User::timeToStr($now);
				}

				$user->accessed_at = User::timeToStr(time());
				$user->accessed_on = $user->accessed_at;
				$user->update($pdo);

				if ($user_mission) {
					// 報酬受け取り済みにする
					$user_mission = UserMission::findBy($params, $pdo, TRUE);
					$user_mission->status = UserMission::STATE_RECEIVED;
					$user_mission->update($pdo);

					// クリアしたミッションIDが解放条件になっているミッションをチェック
					$next_missions = Mission::getAllBy(array("prev_id"=>$mission_id));
					if ($next_missions) {
						
						$user_dungeon_floors = null;
						$user_count = null;
						$dungeon_floor_clear_mission_keys = Mission::getDungeonFloorClearKeys();
						$user_count_mission_keys = Mission::getUserCountKeys();
						
						foreach ($next_missions as $next_mission) {

							// 有効期間内でないものは無視
							if (!$next_mission->isEnabled($now)) {
								continue;
							}
							
							// クリア条件、開放条件
							$next_mission->_clear_conditions = $next_mission->getClearConditions();
							$next_mission->_open_conditions = $next_mission->getOpenConditions();
							
							if (is_null($user_dungeon_floors)) {
								foreach ($dungeon_floor_clear_mission_keys as $mission_key) {
									if (array_key_exists($mission_key, $next_mission->_clear_conditions)) {
										$user_dungeon_floors = UserDungeonFloor::getCleared($user_id, $pdo);
										break;
									}
									if (array_key_exists($mission_key, $next_mission->_open_conditions)) {
										$user_dungeon_floors = UserDungeonFloor::getCleared($user_id, $pdo);
										break;
									}
								}
							}
							if (is_null($user_count)) {
								foreach ($user_count_mission_keys as $mission_key) {
									if (array_key_exists($mission_key, $next_mission->_clear_conditions)) {
										$user_count = UserCount::getByUserId($user_id, $now, $pdo);
										break;
									}
									if (array_key_exists($mission_key, $next_mission->_open_conditions)) {
										$user_count = UserCount::getByUserId($user_id, $now, $pdo);
										break;
									}
								}
							}
								

							// ミッションの解放判定を行う
							// 解放条件
							$open_result = self::checkOpenConditions($next_mission->_open_conditions, $user, $user_dungeon_floors, $user_count);
								
							if ($open_result) {
								// 有効時間帯のチェック
								$check_timezone = $mission->getCheckTimeZone($now);
								// ミッションのクリア判定も行う
								// クリア条件
								$new_mission = new UserMission();
								$new_mission->user_id = $user_id;
								$new_mission->mission_id = $next_mission->id;
								if ($check_timezone == Mission::TIME_ZONE_OK) {
									$new_mission->checkClearConditions($next_mission->_clear_conditions, $user, $user_dungeon_floors, $user_count, $now);
								}
								else if ($check_timezone == Mission::TIME_ZONE_NG) {
									$new_mission->status = UserMission::STATE_CHALLENGE;
								}
								else if ($check_timezone == Mission::TIME_ZONE_OVER) {
									$new_mission->status = UserMission::STATE_NONE;
								}
								$new_mission->ordered_at = BaseModel::timeToStr($now);
								$new_mission->create($pdo);
								// クリアしたミッションIDを保持
								if ($new_mission->status == UserMission::STATE_CLEAR) {
									UserTlog::sendTlogMissionFlow($user, (int)$new_mission->mission_id);
									$clear_mission_ids[] = (int)$next_mission->id;
									$sort_ids[] = (int)$next_mission->sort_id;
								}
							}
						}
					}
				}

				if($mission->bonus_id == BaseBonus::MAGIC_STONE_ID || $mission->bonus_id == BaseBonus::PREMIUM_MAGIC_STONE_ID){
					UserLogAddGold::log($user->id, UserLogAddGold::TYPE_MISSION, $gold_before, $gold_after, $pgold_before, $pgold_after, $user->device_type);
					// UserTlog::sendTlogMoneyFlow($user, $pgold_after + $gold_after - $pgold_before - $gold_before, Tencent_Tlog::REASON_MAIL);
				}
				// if($mission->bonus_id == BaseBonus::COIN_ID){
					// UserTlog::sendTlogMoneyFlow($user, $coin_after- $coin_before, Tencent_Tlog::REASON_MAIL, Tencent_Tlog::MONEY_TYPE_MONEY);
				// }

				$pdo->commit();

				//TLOG
				UserTlog::commitTlog($user, $token, $mission_id);

			}catch(Exception $e){
				if ($pdo->inTransaction()) {
					$pdo->rollback();
				}
				throw $e;
			}
		}

		// ソートID順にソート
		array_multisort($sort_ids, SORT_DESC, $clear_mission_ids);
		
		return array($item_offered, $result, $clear_mission_ids);
	}

	/**
	 * 指定したミッション1件を報酬受け取り可能状態にする
	 * これが呼ばれる前にクリア条件の判定は終わっているものとする
	 *
	 * ※今のところ利用予定はありません
	 * クリア判定がサーバー側では不可能で、アプリ側でクリア判定を行うミッションが今後追加された場合利用する
	 */
	public function setClear($user_id, $mission_id, $pdo)
	{
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
		}
		$params = array(
			'user_id' => $user_id,
			'mission_id' => $mission_id,
		);
		$user_mission = UserMission::findBy($params, $pdo, TRUE);

		if ($user_mission) {
			if ($user_mission->status == UserMission::STATE_CHALLENGE) {
				$user_mission->status = UserMission::STATE_CLEAR;
				$user_mission->update($pdo);
			}
		}
		else {
			$user_mission = new UserMission();
			$user_mission->user_id = $user_id;
			$user_mission->mission_id = $mission_id;
			$user_mission->status = UserMission::STATE_CLEAR;
			$user_mission->create($pdo);
		}
	}


	/**
	 * 指定のユーザーのミッションを取得する
	 * ミッションの状態も指定できる
	 */
	public static function getByUserId($user_id, $states = null, $pdo = null)
	{
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($user_id);
		}
		// SQLの組み立て.
		$conditions = array();
		$values = array(
				$user_id,
		);
		if(!empty($states)) {
			foreach($states as $v) {
				$values[] = $v;
			}
		}

		$sql = "SELECT * FROM " . static::TABLE_NAME . " WHERE user_id=?";
		if(!empty($states)) {
			$sql .= " AND status IN (" . str_repeat('?,', count($states) - 1) . "?)";
		}

		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($values);

		$objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ". join(",",$values)), Zend_Log::DEBUG);
		}

		return $objs;
	}

	/**
	 * ミッションの解放条件判定を行う
	 * 開放条件にはデイリーミッションキーは使用できない前提
	 */
	private static function checkOpenConditions($conditions, $user, $user_dungeon_floors, $user_count)
	{
		foreach ($conditions as $key => $value) {
			if ($key == Mission::CONDITION_KEY_TOTAL_LOGIN) {
				// 通算ログイン数
				if ($user->li_days < $value) {
					return false;
				}
			}
			else if ($key == Mission::CONDITION_KEY_USER_RANK) {
				// #PADC_DY# ----------begin----------
				// check condition recovery to user lv, from user clear dungeon count.
				// if ($user->clear_dungeon_cnt < $value) {
				if ($user->lv < $value) {
					return false;
				}
				// #PADC_DY# ----------end----------
			}
			else if ($key == Mission::CONDITION_KEY_DUNGEON_CLEAR) {
				// 特定ダンジョンクリア（複数判定可）
				$check_dungeon_ids = array();
				if (is_array($value)) {
					$check_dungeon_ids = $value;
				}
				else {
					$check_dungeon_ids[] = $value;
				}

				foreach ($check_dungeon_ids as $dungeon_id) {
					$dungeon = Dungeon::get($dungeon_id);
					// 存在しないダンジョンIDを設定されていても大丈夫なように対処
					if ($dungeon) {
						$dungeon_floor_count = $dungeon->getFloorCount();
					}
					else {
						return false;
					}

					$clear_floor_count = 0;
					foreach($user_dungeon_floors as $udf){
						if ($dungeon_id == $udf->dungeon_id) {
							$clear_floor_count++;
						}
					}

					if ($dungeon_floor_count != $clear_floor_count) {
						return false;
					}
				}
			}
			else if ($key == Mission::CONDITION_KEY_FLOOR_CLEAR) {
				// 特定フロアクリア（複数判定可）
				$check_dungeon_floor_ids = array();
				if (is_array($value)) {
					$check_dungeon_floor_ids = $value;
				}
				else {
					$check_dungeon_floor_ids[] = $value;
				}

				$clear_dungeon_floor_ids = array();
				foreach($user_dungeon_floors as $udf){
					$clear_dungeon_floor_ids[] = $udf->dungeon_floor_id;
				}

				foreach ($check_dungeon_floor_ids as $dungeon_floor_id) {
					if (!in_array($dungeon_floor_id, $clear_dungeon_floor_ids)) {
						return false;
					}
				}
			}
			else if ($key == Mission::CONDITION_KEY_BOOK_COUNT) {
				// 図鑑登録数
				if ($user->book_cnt < $value) {
					return false;
				}
			}
			else if ($key == Mission::CONDITION_KEY_CARD_EVOLVE) {
				// 進化合成回数
				if ($user_count->card_evolve < $value) {
					return false;
				}
			}
			else if ($key == Mission::CONDITION_KEY_CARD_COMPOSITE) {
				// 強化合成回数
				if ($user_count->card_composite < $value) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * ミッションのクリア条件判定を行う
	 * 判定と同時に進捗数値も入力する
	 */
	private function checkClearConditions($conditions, $user, $user_dungeon_floors, $user_count, $check_time = null)
	{
		if (is_null($check_time)) {
			$check_time = time();
		}
		
		$this->status = UserMission::STATE_CLEAR;
		$this->progress_num = 0;
		$this->progress_max = 0;

		foreach ($conditions as $key => $value) {
			if ($key == Mission::CONDITION_KEY_TOTAL_LOGIN) {
				$this->progress_num += $user->li_days;
				$this->progress_max += $value;

				// 通算ログイン数
				if ($user->li_days < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_USER_RANK) {
				// #PADC_DY# ----------begin----------
				// user level replace clear dungeon count
				$this->progress_num += $user->lv;
				$this->progress_max += $value;

				// 到達ランク
				// user level replace clear dungeon count
				if ($user->lv < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
				// #PADC_DY# ----------end----------
			}
			else if ($key == Mission::CONDITION_KEY_DUNGEON_CLEAR) {
				// 特定ダンジョンクリア（複数判定可）
				$check_dungeon_ids = array();
				if (is_array($value)) {
					$check_dungeon_ids = $value;
				}
				else {
					$check_dungeon_ids[] = $value;
				}

				foreach ($check_dungeon_ids as $dungeon_id) {
					$dungeon = Dungeon::get($dungeon_id);
					// 存在しないダンジョンIDを設定されていても大丈夫なように対処
					if ($dungeon) {
						$dungeon_floor_count = $dungeon->getFloorCount();
					}
					else {
						// 存在しないので仮値としてフロア数1とする
						$dungeon_floor_count = 1;
					}

					$clear_floor_count = 0;
					foreach($user_dungeon_floors as $udf){
						if ($dungeon_id == $udf->dungeon_id) {
							$clear_floor_count++;
						}
					}

					$this->progress_num += $clear_floor_count;
					$this->progress_max += $dungeon_floor_count;
					if ($dungeon_floor_count != $clear_floor_count) {
						$this->status = UserMission::STATE_CHALLENGE;
					}
				}
			}
			else if ($key == Mission::CONDITION_KEY_FLOOR_CLEAR) {
				// 特定フロアクリア（複数判定可）
				$check_dungeon_floor_ids = array();
				if (is_array($value)) {
					$check_dungeon_floor_ids = $value;
				}
				else {
					$check_dungeon_floor_ids[] = $value;
				}

				$clear_dungeon_floor_ids = array();
				foreach($user_dungeon_floors as $udf){
					$clear_dungeon_floor_ids[] = $udf->dungeon_floor_id;
				}

				$this->progress_max += count($check_dungeon_floor_ids);
				foreach ($check_dungeon_floor_ids as $dungeon_floor_id) {
					if (in_array($dungeon_floor_id, $clear_dungeon_floor_ids)) {
						$this->progress_num += 1;
					}
					else {
						$this->status = UserMission::STATE_CHALLENGE;
					}
				}
			}
			else if ($key == Mission::CONDITION_KEY_BOOK_COUNT) {
				$this->progress_num += $user->book_cnt;
				$this->progress_max += $value;

				// 図鑑登録数
				if ($user->book_cnt < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_CARD_EVOLVE) {
				$this->progress_num += $user_count->card_evolve;
				$this->progress_max += $value;

				// 進化合成回数
				if ($user_count->card_evolve < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_CARD_COMPOSITE) {
				$this->progress_num += $user_count->card_composite;
				$this->progress_max += $value;

				// 強化合成回数
				if ($user_count->card_composite < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			////////////////////////////////////////////////////////////////////////////////////////
			// 以降デイリーミッション
			else if ($key == Mission::CONDITION_KEY_DAILY_FLOOR_CLEAR) {
				// 特定フロアクリア
				$check_dungeon_floor_ids = array();
				if (is_array($value)) {
					$check_dungeon_floor_ids = $value;
				}
				else {
					$check_dungeon_floor_ids[] = $value;
				}
				
				$clear_dungeon_floor_ids = array();
				foreach($user_dungeon_floors as $udf){
					if (BaseModel::isSameDay_AM4($check_time, BaseModel::strToTime($udf->daily_cleared_at)) ) {
						$clear_dungeon_floor_ids[] = $udf->dungeon_floor_id;
					}
				}
				
				$this->progress_max += count($check_dungeon_floor_ids);
				foreach ($check_dungeon_floor_ids as $dungeon_floor_id) {
					if (in_array($dungeon_floor_id, $clear_dungeon_floor_ids)) {
						$this->progress_num += 1;
					}
					else {
						$this->status = UserMission::STATE_CHALLENGE;
					}
				}
			}
			else if ($key == Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_NORMAL) {
				$this->progress_num += $user_count->clear_normal_daily;
				$this->progress_max += $value;

				// フロアクリア回数（ノーマルダンジョン）
				if ($user_count->clear_normal_daily < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_SPECIAL) {
				$this->progress_num += $user_count->clear_special_daily;
				$this->progress_max += $value;

				// フロアクリア回数（スペシャルダンジョン）
				if ($user_count->clear_special_daily < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_DAILY_GACHA_FRIEND) {
				$this->progress_num += $user_count->gacha_friend_daily;
				$this->progress_max += $value;

				// ガチャ回数（友情ポイントガチャ）
				if ($user_count->gacha_friend_daily < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_DAILY_GACHA_GOLD) {
				$this->progress_num += $user_count->gacha_gold_daily;
				$this->progress_max += $value;

				// ガチャ回数（魔法石ガチャ）
				if ($user_count->gacha_gold_daily < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_DAILY_CARD_COMPOSITE) {
				$this->progress_num += $user_count->card_composite_daily;
				$this->progress_max += $value;

				// モンスターの強化回数
				if ($user_count->card_composite_daily < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_DAILY_CARD_EVOLVE) {
				$this->progress_num += $user_count->card_evolve_daily;
				$this->progress_max += $value;

				// モンスターの進化回数
				if ($user_count->card_evolve_daily < $value) {
					$this->status = UserMission::STATE_CHALLENGE;
				}
			}
			else if ($key == Mission::CONDITION_KEY_DAILY_LOGIN_STREAK) {
				// 連続ログイン日数
				if ($user->li_str == $value) {
					
					$login_periods = array(	User::DEFAULT_LOGIN_PERIOD_ID);
					if (array_key_exists(Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD, $conditions)) {
						if (is_array($conditions[Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD])) {
							$login_periods = $conditions[Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD];
						}
						else {
							$login_periods = array(	$conditions[Mission::CONDITION_KEY_DAILY_LOGIN_PERIOD]);
						}
					}
					
					if (in_array($user->li_period, $login_periods)) {
						$this->progress_num += $value;
						$this->progress_max += $value;
					}
					else {
						// 連続ログインに関して条件を満たさないものは未開放とします
						$this->status = UserMission::STATE_NONE;
					}
					
				}
				else {
					// 連続ログインに関して条件を満たさないものは未開放とします
					$this->status = UserMission::STATE_NONE;
				}
			}
			
		}
	}


	/**
	 * デバッグ機能
	 * 指定のユーザーのミッション状態を変更する
	 */
	public static function updateStatus($user_id, $status_array) {
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			// ミッションデータを全取得
			$all_missions = Mission::getAll();
			$missions = array();
			foreach ($all_missions as $mission) {
				$missions[$mission->id] = $mission;
			}
				
			// ユーザーのミッションデータを全取得
			$get_user_missions = UserMission::getByUserId($user_id, null, $pdo);
			$user_missions = array();
			if ($get_user_missions) {
				foreach ($get_user_missions as $user_mission) {
					$user_missions[$user_mission->mission_id] = $user_mission;
				}
			}
			
			foreach ($status_array as $mission_id => $update_status) {

				if ($update_status == UserMission::STATE_NONE) {
					if (array_key_exists($mission_id, $user_missions)) {
						$user_mission = $user_missions[$mission_id];
						if (array_key_exists($mission_id, $missions) && $missions[$mission_id]->mission_type == Mission::MISSION_TYPE_DAILY) {
							$user_mission->status = $update_status;
							$user_mission->ordered_at = 0;
							$user_mission->update($pdo);
						}
						else {
							$user_mission->delete($pdo);
						}
					}
				}
				else {
					if (array_key_exists($mission_id, $user_missions)) {
						$user_mission = $user_missions[$mission_id];
						if ($user_mission->status != $update_status) {
							$user_mission->status = $update_status;
							$user_mission->update($pdo);
						}
					}
					else {
						$user_mission = new UserMission();
						$user_mission->user_id = $user_id;
						$user_mission->mission_id = $mission_id;
						$user_mission->status = $update_status;
						$user_mission->create($pdo);
					}
				}
			}

			$pdo->commit();

		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
	}
}
