<?php
/**
 * #PADC#
 * ランキング潜入中ダンジョンデータ.
 */

class UserRankingDungeon extends UserDungeon {
	const TABLE_NAME = "user_ranking_dungeons";

	protected static $columns = array(
		'user_id',
		'dungeon_id',
		'dungeon_floor_id',
		'cm',
		'hash',
		'btype',
		'barg',
		'beat_bonuses',
		'lvup',
		'exp',
		'coin',
		'gold',
		'spent_stamina',
		'stamina_spent_at',
		'cleared_at',
		'sneak_time',
		'helper_card_id',
		'helper_card_lv',
		'continue_cnt',
		'sr',
		// #PADC# ----------begin----------
		'clear_response',
		'round_bonus',
		'check_cheat_info',
		'ranking_id',
		// #PADC# ----------end----------
	);

	const DUNGEON_CLASS = 'RankingDungeon';
	const DUNGEON_FLOOR_CLASS = 'RankingDungeonFloor';
	const WAVE_CLASS = 'RankingWave';
	const USER_WAVE_CLASS = 'UserRankingWave';
	const USER_DUNGEON_CLASS = 'UserRankingDungeon';
	const USER_DUNGEON_FLOOR_CLASS = 'UserRankingDungeonFloor';

	const ENTRY_SUCCESS = 1;
	const ENTRY_FAILD_NOT_OPEN = 2;
	/**
	 * ユーザが指定のダンジョン, フロアに潜入可能である場合に限り既存または新規作成したUserDungeonFloorを返す.
	 * 潜入不可能であればnullを返す.
	 */
	public static function checkSneakable($user, $dungeon, $dungeon_floor, $active_bonuses, $cm = null, $pdo = null) {
		$user_dungeon_floor = null;

		if($dungeon_floor->checkStamina($user, $active_bonuses)) {
			$participate_floor_id = $dungeon_floor->id;
		
			$participate_dungeon_id = floor($participate_floor_id / 1000);
			
			$params = array(
				"user_id" => $user->id,
				"dungeon_id" => $participate_dungeon_id,
				"dungeon_floor_id" => $participate_floor_id
			);
			// MY : ランキング参加条件は通常ダンジョンのクリア状況をチェックする。
			$challenge_ok_dung = UserRankingDungeonFloor::findBy($params, $pdo);
			if(!$challenge_ok_dung){
				// 当前关卡没有，查找前面关卡
				if($participate_floor_id%1000 == 1){
					//第一关，直接返回
					$user_dungeon_floor = UserRankingDungeonFloor::enable($user->id, $dungeon->id, $dungeon_floor->id, $pdo);
				}else{
					//其他需要找到上一关
					$params = array(
						"user_id" => $user->id,
						"dungeon_id" => $dungeon->id,
						"dungeon_floor_id" => ($dungeon_floor->id-1)
					);
					$user_dungeon_pre_floor = UserRankingDungeonFloor::findBy($params, $pdo);
					if($user_dungeon_pre_floor && $user_dungeon_pre_floor->cleared_at){
						$user_dungeon_floor = UserRankingDungeonFloor::enable($user->id, $dungeon->id, $dungeon_floor->id, $pdo);

					}else{
						$user_dungeon_floor = null;
					}
				}
				
			}else{
				$user_dungeon_floor = $challenge_ok_dung;
			}
			if($dungeon->attr & 0x01 == 1){
				// クリア後選択不可フラグが1で、かつクリア済みの場合は潜入不可.
				$user_dungeon_floor = null;
			}
		}
		return $user_dungeon_floor;
	}
	protected static function getActiveBonus($user,$dungeon,$dungeon_floor)
	{
		return array(array(),array());
	}

	protected static function createUserDungeon($user,$dungeon,$dungeon_floor,$cm,$pdo,$ranking_id = 0)
	{
		// ユーザーのダンジョンデータが存在するかチェック
		// ※他のユーザーと処理が並行するとDeadLockの可能性があるため、FOR UPDATEで取得しない
		$user_dungeon = UserRankingDungeon::findBy(array("user_id" => $user->id), $pdo);
		if (!$user_dungeon) {
			// 存在しなければ先にここでInsert処理が行われるようにする
			$user_dungeon = new UserRankingDungeon();
			$user_dungeon->user_id = $user->id;
			$user_dungeon->create($pdo);
		}
		
		$user_dungeon = UserRankingDungeon::findBy(array("user_id" => $user->id), $pdo, TRUE);
		if (!$user_dungeon) {
			// 先にデータを作成するように変更したのでここでInsertはしない
			// もしここに遷移してくるようであれば問題が発生しているのでエラーを投げる
			throw new PadException(RespCode::FAILED_SNEAK, "not found user_dungeon user_id:".$user->id.". __NO_TRACE");
		}
		$user_dungeon->resetColumns();
		$user_dungeon->ranking_id = $ranking_id;
		$user_dungeon->user_id = $user->id;
		$user_dungeon->dungeon_id = $dungeon->id;
		$user_dungeon->dungeon_floor_id = $dungeon_floor->id;
		$user_dungeon->cm = $cm;
		return $user_dungeon;
	}

	protected static function entryRanking($user,$ranking_id,$score_values,$cleared_at,$cards,$dungeon_floor,$total_power,$pdo)
	{
		$ret = array();
		// MY : RANKING_UPDATE_START
		$ranking_data = LimitedRanking::findBy(array('ranking_id' => $ranking_id));
		if(LimitedRanking::checkOpenRanking($ranking_id,$cleared_at))
		{
			// if(empty($score_values))
			// {
			// 	$err_msg = "no ranking score data";
			// 	throw new PadException(RespCode::FAILED_CLEAR_DUNGEON, $err_msg." __NO_TRACE");
			// }
			$rare = 0;
			foreach($cards as $card)
			{
				$rare = $rare + $card->rare;
			}
			$rare = round($rare / count($cards),2);
			// MY : レア度の計算確認のため、レコードに保存。
			$score_values['v15'] = $rare;
			list($ranking_score,$ranking_rare)= Ranking::calculateScore($score_values, $ranking_data->ranking_rule, $dungeon_floor, $cards,$ranking_id);
			$ranking_score['total'] = round($ranking_score['total']);
			$old_ranking = UserRanking::findBy(array('user_id'=>$user->id,'ranking_id'=>$ranking_id),$pdo);
			$update_ranking = true;
			if($old_ranking)
			{
				if($ranking_score['total'] <= $old_ranking->score)
				{
					// MY : 最高得点では無い場合更新しない。
					$update_ranking = false;
				}
			}
			
			if($update_ranking)
			{
				$lc = $user->getLeaderCardsData();
				$lc_array = join(',',array($lc->id[0],$lc->lv[0],$lc->slv[0],$lc->hp[0],$lc->atk[0],$lc->rec[0],$lc->psk[0]));
				$ranking_values = UserRanking::createUserRankingValues($user->id,$ranking_id,$user->name,$lc_array,$ranking_score['total'],0,$score_values,$total_power);
				UserRanking::updateRanking($pdo,$ranking_values);

			    ############分数加入redis###########
				$key = Ranking::getRankingRedisKey($ranking_id);
				$redis = Ranking::getRedis();
				$redis->zAdd($key, $ranking_score['total'], $user->id);
				############分数加入redis###########
				//更新玩家排名关卡记录数据
				$user_ranking_record = new UserRankingRecord();
				$ranking_record = UserRankingRecord::findbyUserId(array('user_id'=>$user->id,'ranking_id'=>$ranking_id),$pdo);
				if($ranking_record){
					$ranking_record ->ranking_id = $ranking_id;
					$ranking_record ->score = $ranking_score['total'];
					$ranking_record ->combos = $score_values['v0'];
					$ranking_record ->waves = $dungeon_floor->waves;
					$ranking_record ->turns = $score_values['v2'];
					$ranking_record ->rare = $ranking_rare;
					$ranking_record ->update($pdo);
				}else{
					$user_ranking_record ->user_id = $user->id;
					$user_ranking_record ->ranking_id = $ranking_id;
					$user_ranking_record ->score = $ranking_score['total'];
					$user_ranking_record ->combos = $score_values['v0'];
					$user_ranking_record ->waves = $dungeon_floor->waves;
					$user_ranking_record ->turns = $score_values['v2'];
					$user_ranking_record ->rare = $ranking_rare;
					$user_ranking_record ->create($pdo);
				}
				 
			}else{
			
				############分数加入redis###########
				$key = Ranking::getRankingRedisKey($ranking_id);
				$redis = Ranking::getRedis();
				$redis->zAdd($key, $old_ranking->score, $user->id);
				############分数加入redis###########
				
			}

			// $user_ranking_number = Ranking::getScoreRanking(max($ranking_score['total'],$old_ranking->score));
			// unset($ranking_score['total']);
			$user_ranking_number = Ranking::getSelfRank($ranking_id,$user->id,max($ranking_score['total'],$old_ranking->score));
			$ret[] = self::ENTRY_SUCCESS;
			$ret[] = $ranking_score;
			$ret[] = $user_ranking_number;
		}
				
		
		if(count($ret) == 0)
		{
			$ret = array(self::ENTRY_FAILD_NOT_OPEN,array(),0);
		}
		return $ret;
		// MY : RANKING_UPDATE_END
	}

	protected static function getRankingScore($ranking_id,$score_values, $user_dungeon, $cleared_at, $cards, $dungeon_floor)
	{
		$ret = array();
		$ranking_data = LimitedRanking::findBy(array('ranking_id' => $ranking_id));
		if(LimitedRanking::checkOpenRanking($ranking_id,$cleared_at))
		{
			$user_ranking = UserRanking::findBy(array('user_id' => $user_dungeon->user_id,'ranking_id'=>$ranking_id));
			if($user_ranking)
			{
				list($ranking_score,$ranking_rare) = Ranking::calculateScore($score_values, $ranking_data->ranking_rule, $dungeon_floor, $cards,$ranking_id);
				// $ranking_score = Ranking::calculateRankingScore($score_values, $ranking_data->ranking_rule, $dungeon_floor, $cards,$ranking_id);

				$user_ranking_number = Ranking::getScoreRanking(max($ranking_score['total'],$user_ranking->score));
				// unset($ranking_score['total']);
				$ret[] = self::ENTRY_SUCCESS;
				$ret[] = $ranking_score;
				$ret[] = $user_ranking_number;	
			}
		}
		if(count($ret) == 0)
		{
			$ret = array(self::ENTRY_FAILD_NOT_OPEN,array(),0);
		}
		return $ret;
	}
	protected static function getCacheKeyDungeon($user,$user_dungeon,$dungeon_floor,$ranking_id = 0)
	{
		$key = CacheKey::getSneakRankingDungeon($user->id, $ranking_id, $user_dungeon->dungeon_id, $dungeon_floor->seq, $user_dungeon->sneak_time);
		return $key;
	}
	protected static function isRankingDungeon()
	{
		return true;
	}

	// /**
 //     * 指定ダンジョン＆フロアに潜入するエントリポイント.
 //     * UserDungeonを作成し、永続化する.
 //     * @return 更新後のUserと作成したUserDungeonのリスト. 処理に失敗した場合はnull.
 //     * #PADC# 引数にranking_idを追加。
 //     */
 //    public static function sneak($user, $dungeon, $dungeon_floor, $sneak_time, $helper_id, $helper_card_id, $helper_card_lv, $helper_skill_lv, $helper_card_plus, $cm, $curdeck, $rev, $round = FALSE, $ranking_id = 0, $total_power, $securitySDK, $player_hp) {
 //        global $logger;
        
 //        $daily_left_times = 0; // #PADC_DY# 当日剩余次数

 //        if ($dungeon->id != $dungeon_floor->dungeon_id) {
 //            // 指定ダンジョン＆フロアデータ不正.
 //            $logger->log(("specified dungeon_floor is not in dungeon."), Zend_Log::DEBUG);
 //            return array($user, null, 0, array(), $daily_left_times);
 //        }

 //        $challenge_mode = array(self::CM_NORMAL, self::CM_ALL_FRIEND);
 //        // $challenge_mode = array(self::CM_NORMAL, self::CM_ALL_FRIEND, self::CM_NOT_USE_HELPER); 助っ人なしモードは延期.
 //        if (!in_array(intval($cm), $challenge_mode)) {
 //            // チャレンジモード不正パラメータ.
 //            $logger->log(("illegal param cm:$cm in dungeon."), Zend_Log::DEBUG);
 //            return array($user, null, 0, array(), $daily_left_times);
 //        }
 //        // #PADC# 継承先でアクセスするDBを切り分けるため、Waveクラスを動的クラスに変更。
 //        $wave_class = static::WAVE_CLASS;
 //        $waves = $wave_class::getAllBy(array("dungeon_floor_id" => $dungeon_floor->id), "seq ASC");

 //        $user_dungeon = null;

 //        // #PADC# ----------begin----------
 //        // DROP内容変更するかデバッグユーザー情報を取得
 //        $debugChangeDrop = DebugUser::isDropChangeDebugUser($user->id);
 //        if ($debugChangeDrop) {
 //            // 周回チケット、プラス欠片のDROP確率を取得
 //            list($prob_round, $prob_plus) = DebugUser::getDropChangeProb($user->id);
 //        }
 //        // #PADC# ----------end----------
 //        // #PADC# ----------begin----------
 //        // ボーナスを継承先と切り分けるため、メソッド化。
 //        list($active_bonuses, $dungeon_bonus) = static::getActiveBonus($user, $dungeon, $dungeon_floor);
 //        // #PADC# ----------end----------
 //        // #PADC# 継承先で使用するクラスを変更するため、UserWaveを動的クラスとして扱う。
 //        $user_wave_class = static::USER_WAVE_CLASS;

 //        // Waveを決定. (new UserWave()内で出現モンスター判定などなされる.)
 //        // 10個あるキャッシュからランダムで取得し、なければその場で計算. #186
 //        // #PADC# キャッシュの参照を継承先で変更するため、可変関数に変更。
 //        $get_key_method = 'get' . static::USER_WAVE_CLASS . 'sKey';
 //        $key = CacheKey::$get_key_method($dungeon->id, $dungeon_floor->id, mt_rand(1, 10));
 //        // #PADC# memcache→redis
 //        $rRedis = Env::getRedisForShareRead();
 //        $user_waves = $rRedis->get($key);
 //        if ($user_waves === FALSE) {
 //            $user_waves = array();
 //            // ノトーリアスモンスターの出現をダンジョン中1回だけにする
 //            $notorious_chance_wave = 0;
 //            if (count($waves) > 1) {
 //                $notorious_chance_wave = mt_rand(1, count($waves) - 1);  // ボスウェーブには登場しない
 //            }
 //            $wave_count = 0;
 //            foreach ($waves as $wave) {
 //                $wave_count++;
 //                if ($wave_count == $notorious_chance_wave) {
 //                    $notorious_chance = TRUE;
 //                } else {
 //                    $notorious_chance = false;
 //                }
 //                // DROP内容変更フラグチェック
 //                if ($debugChangeDrop) {
 //                    // デバッグ用のUserWave作成処理
 //                    $user_waves[] = new $user_wave_class($dungeon_floor, $wave, $active_bonuses, $dungeon_bonus, $notorious_chance, $prob_round, $prob_plus);
 //                } else {
 //                    // #PADC# 継承先でアクセスするDBを変更できるように、UserWaveを動的クラスに変更。
 //                    $user_waves[] = new $user_wave_class($dungeon_floor, $wave, $active_bonuses, $dungeon_bonus, $notorious_chance);
 //                }
 //            }
 //            $redis = Env::getRedisForShare();
 //            $redis->set($key, $user_waves, 20); // 20秒キャッシュ.
 //        }
 //        // 永続化.
 //        $pdo = Env::getDbConnectionForUserWrite($user->id);
 //        try {
 //            // #PADC# ----------begin----------
 //            // MY : 周回を行う場合は助っ人の確認をしない。
 //            $helper_ps_info = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0); // 好友的leader觉醒技能信息（反作弊用）
 //            if ($round == FALSE) {
 //                // 助っ人選択
 //                if ($helper_id) {
 //                    // ダンジョン情報ドロップ無チェック(ダンジョンデータの”追加フロア情報”が512で割り切れたら友情ポイント無し)
 //                    if (($dungeon_floor->eflag & Dungeon::NONE_DROP) != Dungeon::NONE_DROP) {
 //                        list($user, $point) = Helper::useHelper($user->id, $helper_id);
 //                    } else {
 //                        $point = 0;
 //                    }
 //                    if ($helper_card_id === null || $helper_card_lv === null) {
 //                        $helper = User::getCacheFriendData($helper_id, User::FORMAT_REV_1);
 //                        $helper_card_id = $helper['card'];
 //                        $helper_card_lv = $helper['clv'];
 //                    }
 //                    $helper = User::getCacheFriendData($helper_id, User::FORMAT_REV_2);
 //                    $helper_ps_info = array_slice($helper, -10, 10);
 //                } else {
 //                    $point = 0;
 //                    $helper_card_id = null;
 //                    $helper_card_lv = null;
 //                    $helper_skill_lv = null;
 //                    $helper_card_plus = null;
 //                }
 //            } else {
 //                $point = 0;
 //                $helper_id = null;
 //                $helper_card_id = null;
 //                $helper_card_lv = null;
 //                $helper_skill_lv = null;
 //                $helper_card_plus = null;
 //            }
 //            // #PADC# ----------end----------
 //            $pdo->beginTransaction();
 //            $user = User::find($user->id, $pdo, TRUE);
 //            // 潜入可否チェック.
 //            // #PADC# 継承を考慮して、selfからstaticに変更。
 //            $user_dungeon_floor = static::checkSneakable($user, $dungeon, $dungeon_floor, $active_bonuses, $cm, $pdo);

 //            if (empty($user_dungeon_floor)) {
 //                $logger->log(("cannot sneak this dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
 //                $pdo->rollback();
 //                return array($user, null, 0, array(), $daily_left_times);
 //            }
            
 //            // #PADC_DY# 记录进入关卡次数 ----------begin----------
 //            $daily_left_times = $user_dungeon_floor->getLeftPlayingTimes();
 //            if(empty($user_dungeon_floor->daily_first_played_at) || !static::isSameDay_AM4(static::strToTime($user_dungeon_floor->daily_first_played_at), time())) {
 //                $user_dungeon_floor->daily_first_played_at = static::timeToStr(time());
 //                $user_dungeon_floor->daily_played_times = 1;
 //                $user_dungeon_floor->daily_recovered_times = 0;
 //                $user_dungeon_floor->update($pdo);
 //            } elseif($daily_left_times <= 0) {
 //                $logger->log(("no left times. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
 //                $pdo->rollback();
 //                return array($user, null, 0, array(), 0);
 //            } elseif($user_dungeon_floor->daily_played_times >= 0) {
 //                $user_dungeon_floor->daily_played_times += 1;
 //                $user_dungeon_floor->update($pdo);
 //            } else {
 //                $user_dungeon_floor->daily_played_times = 1;
 //                $user_dungeon_floor->update($pdo);
 //            }
 //            // #PADC_DY# -----------end-----------

 //            // ダンジョンフロアの開放時間が未来.
 //            if (isset($dungeon_floor->start_at) && static::strToTime($dungeon_floor->start_at) > time()) {
 //                $logger->log(("not been opened yet dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
 //                $pdo->rollback();
 //                return array($user, null, 0, array(), $daily_left_times);
 //            }
 //            // #PADC# ----------begin----------
 //            if ($round) {
 //                // フロアをクリアしたことがあるか確認
 //                if (empty($user_dungeon_floor->cleared_at)) {
 //                    $logger->log(("not cleared this dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
 //                    $pdo->rollback();
 //                    return array($user, null, 0, array(), $daily_left_times);
 //                }
 //                // 周回クリア可能なフロアか確認
 //                if ($dungeon_floor->rticket == 0) {
 //                    $logger->log(("not round clear this dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
 //                    $pdo->rollback();
 //                    return array($user, null, 0, array(), $daily_left_times);
 //                }
 //            }
 //            // #PADC# ----------end----------
 //            // カレントデッキ変更.
 //            if ($curdeck >= 0) {
 //                $user = UserDeck::changeCurrentDeck($user, $curdeck, $pdo);
 //            }

 //            // 潜入データを構築.
 //            // #PADC# 継承先で初期化する値が変わるため、メソッド化
 //            $user_dungeon = static::createUserDungeon($user, $dungeon, $dungeon_floor, $cm, $pdo, $ranking_id);

 //            // コイン入手アップスキル対応.　user_wavesにボーナスを適用.
 //            $leader_card = User::getCacheFriendData($user->id, User::FORMAT_REV_1, $pdo);
 //            $leader_card_id = $leader_card['card'];
 //            // #PADC# 継承先でアクセスする関数を変更できるように、UserDungeonをstaticに変更。
 //            $coeff_coin = static::calcCoeffCoin($user->id, $helper_id, $leader_card_id, $helper_card_id);

 //            // ダンジョン情報ドロップ無チェック(ダンジョンデータの”追加フロア情報”が512で割り切れたらドロップ無し)
 //            if (($dungeon_floor->eflag & Dungeon::NONE_DROP) != Dungeon::NONE_DROP) {
 //                // #PADC# 継承先でアクセスするDBを変更できるように、UserWaveを動的クラスに変更。
 //                list($user_dungeon->beat_bonuses, $user_dungeon->exp, $user_dungeon->coin) = $user_wave_class::getEncodedBeatBonusesAndExpAndCoin($user_waves, $coeff_coin);
 //            } else {
 //                $user_dungeon->beat_bonuses = json_encode(array());
 //            }

 //            // #PADC# ----------begin----------
 //            if ($round) {
 //                // 周回クリアによる追加ボーナス
 //                $round_bonus = null;
 //                foreach ($user_waves as $wave) {
 //                    // DROP内容変更フラグチェック
 //                    if ($debugChangeDrop) {
 //                        $round_bonus = $wave->debugGetBeatBonus($dungeon->id, $prob_round, $prob_plus);
 //                    } else {
 //                        $round_bonus = $wave->getBeatBonus($dungeon->id);
 //                    }
 //                    if ($round_bonus) {
 //                        break;
 //                    }
 //                }
 //                if (!$round_bonus) {
 //                    // ドロップが無かった場合は強化の欠片1個をボーナスとする
 //                    $b = new BeatBonus();
 //                    $b->item_id = BaseBonus::PIECE_ID;
 //                    $b->piece_id = Piece::PIECE_ID_STRENGTH;
 //                    $b->amount = 1; // 個数は変更あるかも
 //                    $round_bonus = $b;
 //                } else if ($round_bonus->item_id == BaseBonus::COIN_ID) {
 //                    $round_bonus->amount = round($round_bonus->amount * $coeff_coin);
 //                }
 //                // 必要な情報だけに絞ってJsonTextで保存
 //                $user_dungeon->round_bonus = json_encode(array(
 //                    "item_id" => $round_bonus->item_id,
 //                    "amount" => $round_bonus->amount,
 //                    "piece_id" => $round_bonus->piece_id,
 //                ));
 //            }
 //            // #PADC# ----------end----------

 //            $user_dungeon->user_waves = $user_waves;
 //            $user_dungeon->setHash();
 //            if ($dungeon_bonus) {
 //                $user_dungeon->btype = $dungeon_bonus->bonus_type;
 //                $user_dungeon->barg = $dungeon_bonus->args;
 //            } else {
 //                $user_dungeon->btype = null;
 //                $user_dungeon->barg = null;
 //            }
 //            $user_dungeon->spent_stamina = $dungeon_floor->getStaminaCost($active_bonuses);

 //            $user_dungeon->sneak_time = $sneak_time;

 //            $user_dungeon->helper_id = $helper_id;
 //            $user_dungeon->helper_card_id = $helper_card_id;
 //            $user_dungeon->helper_card_lv = $helper_card_lv;
 //            $user_dungeon->helper_skill_lv = $helper_skill_lv;
 //            $user_dungeon->helper_card_plus = $helper_card_plus;
 //            $user_dungeon->continue_cnt = 0;
 //            $user_dungeon->sr = $dungeon_floor->sr;

 //            // #PADC# ----------begin----------
 //            // チート対策のチェック処理のため、潜入時のフロア構造を記録
 //            $wave_mons_indexs = $user_dungeon->setCheckCheatInfo($pdo, $helper_ps_info);
 //            // #PADC# ----------end----------

 //            $user_dungeon->update($pdo);
 //            // 初潜入であれば記録.
 //            if ($cm == self::CM_ALL_FRIEND) {
 //                // チャレンジモード(全員フレンドチャレンジ).
 //                if (empty($user_dungeon_floor->cm1_first_played_at)) {
 //                    // #PADC# 継承を考慮して、UserDungeonをstaticに変更。
 //                    $user_dungeon_floor->cm1_first_played_at = static::timeToStr(time());
 //                    $user_dungeon_floor->update($pdo);
 //                }
 //            } elseif ($cm == self::CM_NOT_USE_HELPER) {
 //                // チャレンジモード(助っ人無しチャレンジ).
 //                if (empty($user_dungeon_floor->cm2_first_played_at)) {
 //                    // #PADC# 継承を考慮して、UserDungeonをstaticに変更。
 //                    $user_dungeon_floor->cm2_first_played_at = static::timeToStr(time());
 //                    $user_dungeon_floor->update($pdo);
 //                }
 //            } else {
 //                // ノーマルモード.
 //                if (empty($user_dungeon_floor->first_played_at)) {
 //                    // #PADC# 継承を考慮して、UserDungeonをstaticに変更。
 //                    $user_dungeon_floor->first_played_at = static::timeToStr(time());
 //                    $user_dungeon_floor->update($pdo);
 //                }
 //            }

 //            $pdo->commit();

 //            // #PADC# ----------begin----------
 //            // お勧めヘルパーの候補リストを更新(フレンド登録がMAXでない、かつダンジョンクリア数2以上(チュートリアルダンジョンクリア済み))
 //            // PADCではリセマラが不可能なのでダンジョンクリア数の条件はカット
 //            if ($user->fricnt < $user->friend_max /* && $user->clear_dungeon_cnt >= GameConstant::TUTORIAL_DUNGEON_COUNT */) {
 //                RecommendedHelperUtil::updateHelpersOfLevel($user->id, $user->lv);
 //            }
 //            // #PADC# ----------end----------
 //            // #PADC# PDOException → Exception
 //        } catch (Exception $e) {
 //            if ($pdo->inTransaction()) {
 //                $pdo->rollback();
 //            }
 //            throw $e;
 //        }

 //        // #PADC# ----------begin---------- TLOG SneakDungeon
 //        if ($user_dungeon) {
 //            // 潜入時間がフォーマットに合ってないと、エラーが出てしまうので修正
 //            $date = date_create_from_format('YmdHisu', $sneak_time);
 //            if ($date) {
 //                $stime = $date->format('Y-m-d H:i:s');
 //            } else {
 //                $stime = $sneak_time;
 //            }
 //            UserTlog::sendTlogSneakDungeon($user, $user_dungeon->dungeon_floor_id, $dungeon->dtype, $total_power, $round, $user->round, isset($helper_id) ? $helper_id : 0, $securitySDK, $stime, $ranking_id, $user_dungeon->spent_stamina);
 //            if (isset($helper_id)) {
 //                $helper_data = array($helper_id, $helper_card_id, $helper_card_lv, $helper_skill_lv, $helper_card_plus[0], $helper_card_plus[1], $helper_card_plus[2]);
 //            } else {
 //                $helper_data = array(0, 0, 0, 0, 0, 0, 0);
 //            }

 //            UserTlog::sendTlogSecRoundStartFlow($user, $helper_data, $dungeon, $user_dungeon, $player_hp);
 //        }
 //        // #PADC# ----------end----------

 //        return array($user, $user_dungeon, $point, $wave_mons_indexs, $daily_left_times - 1);
  //  }
}
