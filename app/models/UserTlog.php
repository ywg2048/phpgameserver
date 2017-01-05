<?php
class UserTlog {
	private static $logdata = null;
	
	/**
	 * #PADC# Tlogログイン出力
	 *
	 * @param User $user        	
	 * @param number $channel_id
	 *        	チャンネルID、アプリ側MSDK登録取得
	 * @throws PadException
	 */
	public static function sendTlogLogin($user, $channel_id, $device_id, $game_center) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$version = $userDeviceData ['v'];
		$subs = $user->duringSubscription () ? 1 : 0;
		$gold = $user->pgold + $user->gold;
		$coin = $user->coin;
		// #PADC_DY# ----------begin----------
		// Padc_Log_Log::writePlayerLogin ( $user->device_type, $openid, $user->clear_dungeon_cnt, $user->fricnt, $version, $ptype, $channel_id, $user->vip_lv, $subs, $gold, $coin,$device_id );
		Padc_Log_Log::writePlayerLogin ( $user->device_type, $openid, $user->lv, $user->fricnt, $version, $ptype, $channel_id, $user->vip_lv, $subs, $gold, $coin,$device_id, $game_center);
		// #PADC_DY# ----------end----------
	}
	
	/** #PADC_DY# 修改方法参数$clear_dungeon_cnt-->$lv
	 * send Tlog Player Logout
	 * 
	 * @param number $user_id
	 * @param number $lv
	 * @param number $fricnt
	 * @param number $li_last
	 * @param number $logoutTime
	 * @param number $loginChannel
	 * @param number $device_id
	 */
	public static function sendTlogPlayerLogout($user_id, $lv, $fricnt, $li_last, $logoutTime, $loginChannel, $device_id, $vip_lv, $subs, $gold, $coin) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$type = $userDeviceData ['t'];
		$level = $lv;
		$playerFriendsNum = $fricnt;
		$clientVersion = $userDeviceData ['v'];
		$last_login = BaseModel::strToTime($li_last);
		$onlineTime = $logoutTime - $last_login;
		Padc_Log_Log::sendPlayerLogout ( $type, $openid, $onlineTime, $level, $playerFriendsNum, $clientVersion, $loginChannel, $device_id, $vip_lv, $subs, $gold, $coin, $ptype );
	}
	
	/**
	 * #PADC# Tlog通貨（コイン、魔法石、フレンドポイント）変更記録
	 *
	 * @param User $user        	
	 * @param number $money_add
	 *        	通貨変更数
	 * @param number $reason
	 *        	通貨変更理由　Tencent_Tlog::REASON_***
	 * @param number $money_type
	 *        	Tencent_Tlog::MONEY_TYPE_***
	 * @param number $sequence
	 *        	関連性を示すシーケンス番号,Tencent_Tlog::getSequence($user_id)で取得します。
	 * @param number $subReason
	 *        	詳しい理由、現状ガチャの時だけ必要、Tencent_Tlog::SUBREASON_***
	 * @throws PadException
	 */
	public static function sendTlogMoneyFlow($user, $money_add, $reason, $money_type = Tencent_Tlog::MONEY_TYPE_DIAMOND, $goldFree = 0, $goldBuy = 0, $sequence = 0, $subReason = 0, $roundTicket = 0, $gachaId = null, $missionId = null) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$addOrReduce = ($money_add >= 0) ? Tencent_Tlog::ADD : Tencent_Tlog::REDUCE;
		$moneyAfter = 0;
		if ($money_type == Tencent_Tlog::MONEY_TYPE_DIAMOND) {
			$moneyAfter = $user->gold + $user->pgold;
		} else if ($money_type == Tencent_Tlog::MONEY_TYPE_MONEY) {
			$moneyAfter = $user->coin;
		} else if ($money_type == Tencent_Tlog::MONEY_TYPE_FRIEND_POINT) {
			$moneyAfter = $user->fripnt;
		}
		// #PADC_DY# ----------begin----------
		Padc_Log_Log::sendMoneyFlow ( $user->device_type, $openid, $user->lv, abs ( $money_add ), $reason, $addOrReduce, $money_type, $moneyAfter, $goldFree, $goldBuy, $ptype, $sequence, $subReason, $roundTicket, $gachaId, $missionId );
		// #PADC_DY# ----------end----------
	}
	
	/**
	 * #PADC# TlogSNS情報
	 *
	 * @param number $user_id        	
	 * @param number $count
	 *        	送信数
	 * @param number $snsType
	 *        	SNS種類、Tencent_Tlog::SNSTYPE_***
	 * @param number $target_user_id
	 *        	目標ユーザーID
	 */
	public static function sendTlogSnsFlow($user_id, $count, $snsType, $target_user_id = 0) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$device_type = $userDeviceData ['t'];
		if ($target_user_id > 0) {
			$targetUserDevice = UserDevice::getUserDeviceFromRedis ( $target_user_id );
			$targetOpenID = $targetUserDevice ['oid'];
		} else {
			$targetOpenID = 0;
		}
		Padc_Log_Log::sendSnsFlow ( $device_type, $openid, $count, $snsType, $targetOpenID, $ptype );
	}
	
	/**
	 * #PADC# Tlog item flow
	 *
	 * @param number $user_id
	 *        	ユーザーID
	 * @param number $good_type
	 *        	アイテム種類　Tencent_Tlog::GOOD_TYPE_***
	 * @param number $item_id
	 *        	欠片/カード ID
	 * @param number $count
	 *        	変更数
	 * @param number $after_count
	 *        	変更後の数
	 * @param string $reason
	 *        	理由 Tencent_Tlog::ITEM_REASON_***
	 * @param string $subreason
	 *        	詳しい理由
	 * @param number $money
	 *        	通貨使用数
	 * @param number $money_type
	 *        	通貨種類　Tencent_Tlog::MONEY_TYPE_***
	 * @param number $sequence
	 *        	関連性を示すシーケンス番号,Tencent_Tlog::getSequence($user_id)で取得します。
	 */
	public static function sendTlogItemFlow($user_id, $good_type, $item_id, $count, $after_count, $reason, $subreason, $money, $money_type, $sequence = 0, $roundTicket = 0, $missionId = null) {
		$user = User::find ( $user_id );
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$type = $user->device_type;
		$addOrReduce = ($count >= 0) ? Tencent_Tlog::ADD : Tencent_Tlog::REDUCE;
		
		$rare = 0;
		if ($good_type == Tencent_Tlog::GOOD_TYPE_PIECE) {
			$piece = Piece::get ( $item_id );
			$rare = $piece->rare;
		} else if ($good_type == Tencent_Tlog::GOOD_TYPE_CARD) {
			$card = Card::get ( $item_id );
			$rare = $card->rare;
		}
		// #PADC_DY# ----------begin----------
		Padc_Log_Log::sendItemFlow ( $type, $openid, $user->lv, $good_type, $item_id, abs ( $count ), $after_count, $reason, $subreason, abs ( $money ), $money_type, $addOrReduce, $rare, $ptype, $sequence, $roundTicket, $missionId );
		// #PADC_DY# ----------end----------
	}
	
	/**
	 * #PADC# Tlog ダンジョン情報
	 *
	 * @param number $user_id
	 *        	ユーザーID
	 * @param number $floorId
	 *        	ダンジョンフローID
	 * @param number $dungeonType
	 *        	ダンジョン種類　Dungeon::DUNG_TYPE_***
	 * @param number $score
	 *        	スコア
	 * @param number $coin
	 *        	コイン
	 * @param number $cheat        	
	 * @param string $securitySDK        	
	 * @param string $sneakTime        	
	 * @param int $maxComboNum        	
	 */
	public static function sendTlogRoundFlow($user_id, $floorId, $dungeon_type, $score, $dungeon_time, $result, $rank, $coin, $cheat, $securitySDK, $sneakTime, $maxComboNum, $aveComboNum, $ranking_id = 0, $roundTicket = 0, $diamond = 0, $starRating = 0) {
		$user = User::find ( $user_id );
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		$type = $userDeviceData ['t'];
		
		if (( int ) $ranking_id > 0) {
			$battle_type = Tencent_Tlog::BATTLE_TYPE_RANKING;
		} else {
			$battle_type = Tencent_Tlog::BATTLE_TYPE_NORMAL;
			if ($dungeon_type == Dungeon::DUNG_TYPE_EVENT) {
				$battle_type = Tencent_Tlog::BATTLE_TYPE_SPC;
			} elseif ($dungeon_type == Dungeon::DUNG_TYPE_TECHNICAL) {
				$battle_type = Tencent_Tlog::BATTLE_TYPE_UNLOCK;
			} elseif ($dungeon_type == Dungeon::DUNG_KIND_COLLABO) {
				$battle_type = Tencent_Tlog::BATTLE_TYPE_IP;
			}
		}
		
		Padc_Log_Log::sendRoundFlow ( $type, $openid, $floorId, $battle_type, $score, $dungeon_time, $result, $rank, $coin, $cheat, $securitySDK, $sneakTime, $maxComboNum, $aveComboNum, $ptype, $roundTicket, $diamond, $starRating );
	}
	
	/**
	 *
	 * @param User $user        	
	 * @param number $floor_id        	
	 * @param number $dungeon_type        	
	 * @param number $$total_power        	
	 * @param boolean $use_round        	
	 * @param number $round_num        	
	 * @param number $friend_id        	
	 * @param string $securitySDK        	
	 * @param string $sneakTime        	
	 */
	public static function sendTlogSneakDungeon($user, $floor_id, $dungeon_type, $total_power, $use_round, $round_num, $friend_id, $securitySDK, $sneakTime, $ranking_id = 0, $useStamina = 0) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		
		// if it have ranking_id > 0,then change battle type to BATTLE_RANKING = 3
		if (( int ) $ranking_id > 0) {
			$battle_type = Tencent_Tlog::BATTLE_TYPE_RANKING;
		} else {
			$battle_type = Tencent_Tlog::BATTLE_TYPE_NORMAL;
			if ($dungeon_type == Dungeon::DUNG_TYPE_EVENT) {
				$battle_type = Tencent_Tlog::BATTLE_TYPE_SPC;
			} elseif ($dungeon_type == Dungeon::DUNG_TYPE_TECHNICAL) {
				$battle_type = Tencent_Tlog::BATTLE_TYPE_UNLOCK;
			} elseif ($dungeon_type == Dungeon::DUNG_KIND_COLLABO) {
				$battle_type = Tencent_Tlog::BATTLE_TYPE_IP;
			}
		}
		
		$ldeck = json_decode ( $user->ldeck );
		$deck = array ();
		foreach ( $ldeck as $ldeck_card ) {
			$deck_card = Card::get($ldeck_card[1]); // #PADC_DY#
			$deck [] = array (
				$ldeck_card [1], // card id
				$ldeck_card [2], // lv
				$ldeck_card [3], // skill lv
				(int)$deck_card->rare, // #PADC_DY# rare
			);
		}
		
		if ($friend_id) {
			$friend_device = UserDevice::getUserDeviceFromRedis ( $friend_id );
			$foid = $friend_device ['oid'];
		} else {
			$foid = null;
		}
		
		//get total power
		$pdo = Env::getDbConnectionForUserRead($user->id);
		$userDeckInfo = self::getUserDeckInfo($user, $pdo, array(0, 0, 0, 0, 0, 0, 0));
		$total_power = $userDeckInfo['stl_13'];

		// #PADC_DY# ----------begin----------
		Padc_Log_Log::sendSneakDungeon ( $type, $openid, $floor_id, $battle_type, json_encode ( $deck ), $total_power, $use_round ? 1 : 0, $round_num, $foid, $securitySDK, $user->lv, $user->vip_lv, $sneakTime, $ptype, $useStamina );
		// #PADC_DY# ----------end----------
	}
	
	/**
	 * #PADC#
	 *
	 * @param number $user_id        	
	 * @param array $deck_list        	
	 * @param PDO $pdo        	
	 */
	public static function sendTlogDeckFlow($user_id, $deck_list, $pdo, $totalPower = 0) {
		$cuids = array ();
		foreach ( $deck_list as $key => $desk ) {
			foreach ( $deck_list [$key] as $cuid ) {
				$cuids [] = $cuid;
			}
		}
		$cuids = array_unique ( $cuids );
		$user_cards = UserCard::findByCuids ( $user_id, $cuids, $pdo );
		$cuid_cards = array ();
		foreach ( $user_cards as $user_card ) {
			$cuid_cards [$user_card->cuid] = $user_card;
		}
		
		$decks = array ();
		foreach ( $deck_list as $key => $desk ) {
			$deck_data = array ();
			foreach ( $deck_list [$key] as $cuid ) {
				if ($cuid > 0) {
					$card = $cuid_cards [$cuid];
					$card_data = array (
							( int ) $card->card_id,
							( int ) $card->lv,
							( int ) $card->slv 
					)
					// (int)$card->equip1,
					// (int)$card->equip2,
					// (int)$card->equip3,
					;
					$deck_data [] = $card_data;
				}
			}
			$decks [] = $deck_data;
		}
		
		// $logger->log(' $decks:'.json_encode($decks), Zend_Log::DEBUG);
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user_id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		
		//get total power
		$user = User::find($user_id, $pdo);
		$userDeckInfo = self::getUserDeckInfo($user, $pdo, array(0, 0, 0, 0, 0, 0, 0));
		$totalPower = $userDeckInfo['stl_13'];
		
		Padc_Log_Log::sendDeckFlow ( $type, $openid, json_encode ( $decks ), $totalPower, $ptype );
	}
	
	/**
	 * #PADC# send vip level and user lv to tlog
	 *
	 * @param User $user 
	 * @param int $AddExp
	 * @param int $IsLvUp
	 */
	public static function sendTlogVipLevel($user,$AddExp,$IsLvUp, $LvUpExp) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		if($IsLvUp){
			$IsLvUp = 1;
		}else{
			$IsLvUp = 0;
		}
		// #PADC_DY# ----------begin----------
		// Padc_Log_Log::sendVipLevel($type, $openid, $user->clear_dungeon_cnt, $user->vip_lv, $ptype, $user->name, $AddExp, $IsLvUp, $user->tp_gold, $LvUpExp);
		Padc_Log_Log::sendVipLevel($type, $openid, $user->lv, $user->vip_lv, $ptype, $user->name, $AddExp, $IsLvUp, $user->tp_gold, $LvUpExp);
		// #PADC_DY# ----------end----------
	}
	/**
	 * #PADC# send subscribtion monthly rewards to tlog
	 *
	 * @param User $user        	
	 * @param int $gold        	
	 */
	public static function sendTlogMonthlyReward($user, $gold) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		// #PADC_DY# ----------begin----------
		// Padc_Log_Log::sendMonthlyReward ( $type, $openid, $user->clear_dungeon_cnt, $user->vip_lv, $gold, $ptype );
		Padc_Log_Log::sendMonthlyReward ( $type, $openid, $user->lv, $user->vip_lv, $gold, $ptype );
		// #PADC_DY# ----------end----------
	}
	/**
	 * send Mission Flow
	 *
	 * @param User $user        	
	 * @param int $mission_id        	
	 */
	public static function sendTlogMissionFlow($user, $mission_id) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		// #PADC_DY# ----------begin----------
		// Padc_Log_Log::sendMissionFlow ( $type, $openid, $mission_id, $user->clear_dungeon_cnt, $user->vip_lv, $ptype );
		Padc_Log_Log::sendMissionFlow ( $type, $openid, $mission_id, $user->lv, $user->vip_lv, $ptype );
		// #PADC_DY# ----------end----------
	}
	
	/**
	 * send share
	 *
	 * @param int $user_id        	
	 * @param int $share_type        	
	 * @param int $dungeon_id        	
	 * @param int $card_id        	
	 */
	public static function sendTlogShareFlow($user_id, $share_type, $dungeon_id, $card_id) {
		$user = User::find ( $user_id );
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		// #PADC_DY# ----------begin----------
		// $user_lv = $user->clear_dungeon_cnt;
		$user_lv = $user->lv;
		// #PADC_DY# ----------end----------
		$vip_lv = $user->vip_lv;
		
		Padc_Log_Log::sendShareFlow ( $type, $openid, $share_type, $dungeon_id, $card_id, $user_lv, $vip_lv, $ptype );
	}
	/**
	 * send Tlog Ranking
	 *
	 * @param User $user        	
	 * @param int $dungeonId        	
	 * @param int $rankingId        	
	 * @param string $timeStamp        	
	 * @param array $userData
	 *        	ランキングTOP100ユーザーデータ：[[ユーザID,OpenID,ユーザー名、スコア],...]
	 */
	public static function sendTlogRanking($dungeonId, $rankingId, $timeStamp, array $userData, $type = UserDevice::TYPE_IOS, $ptype = UserDevice::PTYPE_QQ) {
		$userData = array_chunk ( $userData, 20 );
		$userData1 = isset ( $userData [0] ) ? $userData [0] : array ();
		$userData2 = isset ( $userData [1] ) ? $userData [1] : array ();
		$userData3 = isset ( $userData [2] ) ? $userData [2] : array ();
		$userData4 = isset ( $userData [3] ) ? $userData [3] : array ();
		$userData5 = isset ( $userData [4] ) ? $userData [4] : array ();
		
		Padc_Log_Log::sendRanking ( $type, $dungeonId, $rankingId, $timeStamp, json_encode ( $userData1 ), json_encode ( $userData2 ), json_encode ( $userData3 ), json_encode ( $userData4 ), json_encode ( $userData5 ), $ptype );
	}
	
	/**
	 * send Tlog Failed Sneak
	 * 
	 * @param User $user        	
	 * @param int $dungeonId        	
	 * @param string $sneakTime        	
	 */
	public static function sendTlogFailedSneak($user, $dungeonId, $sneakTime) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$type = $userDeviceData ['t'];
		$openId = $userDeviceData ['oid'];
		$ptype = $userDeviceData ['pt'];
		
		Padc_Log_Log::sendFailedSneak ( $type, $openId, $dungeonId, $sneakTime, $ptype );
	}
	
	/**
	 *
	 * send composite tlog
	 *
	 * @param User $user        	
	 * @param UserCard $before_user_card        	
	 * @param UserCard $after_user_card        	
	 * @param Card $base_card        	
	 * @param array $tlog_composite__pieces        	
	 * @param int $money        	
	 */
	public static function sendTlogComposite($user, $before_user_card, $after_user_card, $base_card, $tlog_composite__pieces, $money) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		$card_id = $after_user_card->card_id;
		$card_lv = $before_user_card->lv;
		$after_card_lv = $after_user_card->lv;
		// #PADC_DY# ----------begin----------
		// $lv = $user->clear_dungeon_cnt;
		$lv = $user->lv;
		// #PADC_DY# ----------end----------
		$vip_lv = $user->vip_lv;
		$hp = $after_user_card->equip1;
		$attack = $after_user_card->equip2;
		$recover = $after_user_card->equip3;
		$piece_id = $base_card->gup_piece_id;
		$generic_num = 0;
		$piece_num = 0;
		
		foreach ( $tlog_composite__pieces as $pieceId => $pieceNum ) {
			if ($piece_id == $pieceId) {
				$piece_num += $pieceNum;
			} else {
				$generic_num += $pieceNum;
			}
		}
		if ($generic_num) {
			$generic_use = 0;
		} else {
			$generic_use = 1;
		}
		Padc_Log_Log::sendComposite ( $type, $openid, $card_id, $piece_id, $card_lv, $after_card_lv, $lv, $vip_lv, $piece_num, $generic_use, $generic_num, $money, $hp, $attack, $recover, $ptype );
	}
	
	/**
	 * send evolution tlog
	 *
	 * @param User $user        	
	 * @param UserCard $before_user_card        	
	 * @param UserCard $after_user_card        	
	 * @param Card $base_card        	
	 * @param Card $target_card        	
	 * @param array $tlog_pieces        	
	 * @param int $money        	
	 */
	public static function sendTlogEvolution($user, $before_user_card, $after_user_card, $base_card, $target_card, $tlog_pieces, $money) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		$piece_id = $base_card->gup_piece_id;
		$card_id = $before_user_card->card_id;
		$after_card_id = $after_user_card->card_id;
		$card_attribute = $target_card->attr;
		$piece_num = 0;
		$generic_num = 0;
		$generic_piece_attr = - 1;
		foreach ( $tlog_pieces as $pieceId => $piece ) {
			if ($piece_id == $pieceId) {
				$piece_num += abs ( $piece ['piece_add'] );
			} else {
				$generic_piece_attr = $piece ['master_piece']->attr;
				$generic_num += abs ( $piece ['piece_add'] );
			}
		}
		if ($generic_num) {
			$generic_use = 0;
		} else {
			$generic_use = 1;
		}
		// #PADC_DY# ----------begin----------
		// $lv = $user->clear_dungeon_cnt;
		$lv = $user->lv;
		// #PADC_DY# ----------end----------
		$vip_lv = $user->vip_lv;
		
		Padc_Log_Log::sendEvolution ( $type, $openid, $card_id, $after_card_id, $card_attribute, $lv, $vip_lv, $piece_num, $generic_use, $generic_num, $generic_piece_attr, $money, $ptype );
	}
	/**
	*@param User $user 用户信息
	*@param $awake_piece_id 觉醒碎片id
	*@param $name 觉醒碎片名称
	*By: YuanWenGuang
	*/
	public static function sendTlogAwakeSkill($user,$awake_piece_id,$card_id,$ps_id,$awake_skill_piece_num,$coin,$user_card_lv){
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		$Level = $user->lv;
		$VipLevel = $user->vip_lv;
		Padc_Log_Log::sendAwakeSkill($type,$openid,$Level,$VipLevel,$awake_piece_id,$card_id,$ps_id,$awake_skill_piece_num,$coin,$user_card_lv,$ptype);
	}

	/**
	 *
	 * @param User $user        	
	 * @param array $tlog_data        	
	 */
	public static function beginTlog($user, $tlog_data = null) {
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( 'beginTlog $tlog_data:' . print_r ( $tlog_data, true ), Zend_Log::DEBUG );
		}
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}
		if (isset ( self::$logdata )) {
			if (Env::ENV !== "production") {
				$logger->log ( 'Tlog Warning: Not commit.  TRACE:' . print_r ( debug_backtrace (), true ), Zend_Log::WARN );
			}
		}
		self::$logdata = array (
				'gold' => $user->gold + $user->pgold,
				'coin' => $user->coin,
				'fripnt' => $user->fripnt,
				'round' => $user->round // #PADC_DY# 扫荡券
		);
		if (isset ( $tlog_data )) {
			self::setTlogData ( $tlog_data );
		}
	}
	
	/**
	 *
	 * @param array $tlog_data        	
	 */
	public static function setTlogData($tlog_data) {
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( 'setTlogData $tlog_data:' . print_r ( $tlog_data, true ), Zend_Log::DEBUG );
		}
		if (! isset ( self::$logdata )) {
			// if (Env::ENV !== "production") {
			// $logger->log ( 'Tlog Warning: Not begin. TRACE:' . print_r ( debug_backtrace (), true ), Zend_Log::WARN );
			// }
			return;
		}
		self::$logdata = array_merge_recursive ( self::$logdata, $tlog_data );
	}
	
	/**
	 *
	 * @param $User $user        	
	 */
	public static function commitTlog($user, $token = null, $mission_id = null) {
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( 'commitTlog self::$logdata' . print_r ( self::$logdata, true ), Zend_Log::DEBUG );
		}
		if (! isset ( self::$logdata )) {
			if (Env::ENV !== "production") {
				$logger->log ( 'Tlog Warning: Not begin.  TRACE:' . print_r ( debug_backtrace (), true ), Zend_Log::WARN );
			}
			return;
		}
		
		$data = self::$logdata;
		$sequence = Tencent_Tlog::getSequence ( $user->id );
		$money_cost = 0;
		$money_cost_type = 0;
		// #PADC_DY# ----------begin----------
		$roundTicket = 0;
		if (isset($data['round'])) {
			$roundTicket = $user->round - $data['round'];
		}
		// #PADC_DY# -----------end-----------
		if (isset ( $data ['gold'] ) || isset ( $data ['coin'] ) || isset ( $data ['fripnt'] )) {
			$money_reason = isset ( $data ['money_reason'] ) ? $data ['money_reason'] : 0;
			$money_subreason = isset ( $data ['money_subreason'] ) ? $data ['money_subreason'] : 0;
			if (! $money_reason) {
				if (Env::ENV !== "production") {
					$logger->log ( 'Tlog Warning: No money_reason.  TRACE:' . print_r ( debug_backtrace (), true ), Zend_Log::WARN );
				}
			} else {
				if (isset ( $data ['gold'] )) {
					$gold_before = $data ['gold'];
					$gold_after = $user->pgold + $user->gold;
					$money_cost = $gold_after - $gold_before;
					$money_cost_type = Tencent_Tlog::MONEY_TYPE_DIAMOND;
					if (Env::ENV !== "production") {
						$logger->log ( 'gold_before:' . $gold_before . ' gold_after:' . $gold_after, Zend_Log::DEBUG );
					}
					if ($gold_before != $gold_after) {
						self::sendTlogMoneyFlow ( $user, $gold_after - $gold_before, $money_reason, Tencent_Tlog::MONEY_TYPE_DIAMOND, $gold_after - $gold_before, 0, $sequence, $money_subreason, $roundTicket, null, $mission_id );

						if(isset($token)){
							$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
						}
					}
				}
				if (isset ( $data ['coin'] )) {
					$coin_before = $data ['coin'];
					$coin_after = $user->coin;
					$money_cost = $coin_after - $coin_before;
					$money_cost_type = Tencent_Tlog::MONEY_TYPE_MONEY;
					if (Env::ENV !== "production") {
						$logger->log ( 'coin_before:' . $coin_before . ' coin_after:' . $coin_after, Zend_Log::DEBUG );
					}
					if ($coin_before != $coin_after) {
						self::sendTlogMoneyFlow ( $user, $coin_after - $coin_before, $money_reason, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, $sequence, $money_subreason, $roundTicket, null, $mission_id );
					}
				}
				if (isset ( $data ['fripnt'] )) {
					$fripnt_before = $data ['fripnt'];
					$fripnt_after = $user->fripnt;
					$money_cost = $fripnt_after - $fripnt_before;
					$money_cost_type = Tencent_Tlog::MONEY_TYPE_FRIEND_POINT;
					if (Env::ENV !== "production") {
						$logger->log ( '$fripnt_before:' . $fripnt_before . ' $fripnt_after:' . $fripnt_after, Zend_Log::DEBUG );
					}
					if ($fripnt_before != $fripnt_after) {
						self::sendTlogMoneyFlow ( $user, $fripnt_after - $fripnt_before, $money_reason, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT, 0, 0, $sequence, $money_subreason, $roundTicket, null, $mission_id );
					}
				}
			}
		}
		if (isset ( $data ['piece'] ) || isset ( $data ['card'] )) {
			$item_reason = isset ( $data ['item_reason'] ) ? $data ['item_reason'] : 0;
			$item_subreason = isset ( $data ['item_subreason'] ) ? $data ['item_subreason'] : 0;
			if (! $item_reason) {
				if (Env::ENV !== "production") {
					$logger->log ( 'Tlog Warning: No item_reason.  TRACE:' . print_r ( debug_backtrace (), true ), Zend_Log::WARN );
				}
			} else {
				if (isset ( $data ['piece'] )) {
					if (Env::ENV !== "production") {
						$logger->log ( 'data piece:' . print_r ( $data ['piece'], true ), 7 );
					}
					foreach ( $data ['piece'] as $piece ) {
						$piece_id = $piece ['id'];
						$piece_add = $piece ['add'];
						$piece_num = $piece ['num'];
						self::sendTlogItemFlow ( $user->id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $piece_add, $piece_num, $item_reason, $item_subreason, $money_cost, $money_cost_type, $sequence, $roundTicket, $mission_id );
					}
				}
				if (isset ( $data ['card'] )) {
					if (Env::ENV !== "production") {
						$logger->log ( 'data card:' . print_r ( $data ['card'], true ), 7 );
					}
					foreach ( $data ['card'] as $card_id ) {
						self::sendTlogItemFlow ( $user->id, Tencent_Tlog::GOOD_TYPE_CARD, $card_id, 1, 1, $item_reason, $item_subreason, $money_cost, $money_cost_type, $sequence, $roundTicket, $mission_id );
					}
				}
			}
		}
		
		self::$logdata = null;
	}
	
	/**
	 * send Tlog Player Exp Flow
	 * 
	 * @param User $user        	
	 * @param User $before_user        	
	 * @param int $time        	
	 * @param int $expChange        	
	 */
	public static function sendTlogPlayerExpFlow($user, $before_dungeon_cnt, $time = 0, $expChange = 1, $reason) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		// #PADC_DY# ----------begin----------
		// $afterLevel = $user->clear_dungeon_cnt;
		$afterLevel = $user->lv;
		// #PADC_DY# ----------end----------
		$reason = $reason;
		$subReason = 0;
		
		Padc_Log_Log::sendPlayerExpFlow ( $type, $openid, $expChange, $before_dungeon_cnt, $afterLevel, $time, $reason, $subReason, $ptype );
	}


	public static function sendTlogSecRoundStartFlow($user, $helper_data, $dungeon, $user_dungeon, $player_hp,$card_rare,$average_rare,$card_ps,$wave_count){

		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		
		$AreaID = self::PType2AreaId($ptype);
		
		// UNIXタイムスタンプへ変換
		$date = date_create_from_format('ymdHisu', $user_dungeon->sneak_time);
		if ($date) {
			$BattleID = strtotime($date->format('Y-m-d H:i:s'));
		}
		else {
			$BattleID = $user_dungeon->sneak_time;
		}
		
		$pdo = Env::getDbConnectionForUserRead($user->id);
		$userDeckInfo = self::getUserDeckInfo($user, $pdo, $helper_data);
		$helper_openId = ($helper_data[0] ? UserDevice::getUserOpenId($helper_data[0]) : 0);
		
		$userWaveInfo = array(
				'stl_81' => 0,
				'stl_82' => 0,
				'stl_83' => 0,
				'stl_84' => 0,
				'stl_85' => 0,
				'stl_86' => 0,
				'stl_87' => 0,
				'stl_88' => 0,
				'stl_89' => 0,
				'stl_90' => 0,
				'stl_91' => 0,
				'stl_92' => 0,
				'stl_93' => 0,
				'stl_94' => 0,
				'stl_95' => 0,
				'stl_96' => 0,
				'stl_97' => 0,
				'stl_98' => 0,
				'stl_99' => 0,
				'stl_100' => 0,
				'stl_101' => 0,
				'stl_102' => '',
		);
		if($user_dungeon instanceof UserDungeon){
			if ($dungeon->isNormalDungeon()) {
				$dungeon_type = 1;
			}
			else {
				$dungeon_type = 2;
			}
			if ($dungeon->dkind == Dungeon::DUNG_KIND_BUY) {
				$dungeon_type = 4;
			}
		}
		else if($user_dungeon instanceof UserRankingDungeon){
			$dungeon_type = 3;
		}
		
		// 敵スキルデータ
		$all_ene_skills = EnemySkill::getAll();
		$ene_skills = array();
		foreach ($all_ene_skills as $es) {
			$ene_skills[$es->id] = $es;
		}
		
		$wave_cnt = 0;
		$mons_cnt = 0;
		$boss_skill_max_damage = 0;
		$boss_skill_min_damage = 0;
		foreach ($user_dungeon->user_waves as $user_wave) {
			$userWaveInfo['stl_81']++;
			foreach($user_wave->user_wave_monsters as $user_wave_monster) {
				$userWaveInfo['stl_82']++;
				
				$monster = $user_wave_monster->wave_monster;
				// モンスターのステータス計算
				$card = Card::get($monster->card_id);
				$mons_hp = Card::getCardParam($monster->lv, $card->edefd, $card->emhpa, $card->emhpb, $card->emhpc);
				$mons_atk = Card::getCardParam($monster->lv, $card->edefd, $card->eatka, $card->eatkb, $card->eatkc);
				$mons_def = Card::getCardParam($monster->lv, $card->edefd, $card->edefa, $card->edefb, $card->edefc);
					
				// モンスター種類
				if($user_wave_monster->wave_monster->boss == 1) {
					// ボス
					$userWaveInfo['stl_92']++;
					if ($userWaveInfo['stl_93'] < $mons_hp) {
						$userWaveInfo['stl_93'] = $mons_hp;
					}
					if ($userWaveInfo['stl_94'] == 0 || $userWaveInfo['stl_94'] > $mons_hp) {
						$userWaveInfo['stl_94'] = $mons_hp;
					}
					$userWaveInfo['stl_95'] += $mons_hp;
					
					if ($userWaveInfo['stl_96'] < $mons_atk) {
						$userWaveInfo['stl_96'] = $mons_atk;
					}
					if ($userWaveInfo['stl_97'] == 0 || $userWaveInfo['stl_97'] > $mons_atk) {
						$userWaveInfo['stl_97'] = $mons_atk;
					}
					
					if ($userWaveInfo['stl_98'] < $card->acyc) {
						$userWaveInfo['stl_98'] = $card->acyc;
					}
					if ($userWaveInfo['stl_99'] == 0 || $userWaveInfo['stl_99'] > $card->acyc) {
						$userWaveInfo['stl_99'] = $card->acyc;
					}
					
					if ($userWaveInfo['stl_100'] < $monster->lv) {
						$userWaveInfo['stl_100'] = $monster->lv;
					}
					if ($userWaveInfo['stl_101'] == 0 || $userWaveInfo['stl_101'] > $monster->lv) {
						$userWaveInfo['stl_101'] = $monster->lv;
					}
					
					for ($i=0;$i<=31;$i++) {
						$skillNum = 'ai'.$i.'num';
						if ($card->$skillNum > 0) {
							if (strlen($userWaveInfo['stl_102']) == 0) {
								$userWaveInfo['stl_102'] .= $card->$skillNum;
							}
							else {
								$userWaveInfo['stl_102'] .= ','.$card->$skillNum;
							}
						}
						
						$ene_skill = $ene_skills[$card->$skillNum];
						$damage = self::_checkEnemySkillMaxDamage($player_hp, $mons_atk, $ene_skill->type, $ene_skill->skp1, $ene_skill->skp2, $ene_skill->skp3);
						if ($damage > 0) {
							if ($boss_skill_max_damage < $damage) {
								$boss_skill_max_damage = $damage;
							}
							if ($boss_skill_min_damage == 0 || $boss_skill_min_damage > $damage) {
								$boss_skill_min_damage = $damage;
							}
						}
						
					}
					
				} else {
					if ($userWaveInfo['stl_83'] < $mons_hp) {
						$userWaveInfo['stl_83'] = $mons_hp;
					}
					if ($userWaveInfo['stl_84'] == 0 || $userWaveInfo['stl_84'] > $mons_hp) {
						$userWaveInfo['stl_84'] = $mons_hp;
					}
					$userWaveInfo['stl_85'] += $mons_hp;
					
					if ($userWaveInfo['stl_86'] < $mons_atk) {
						$userWaveInfo['stl_86'] = $mons_atk;
					}
					if ($userWaveInfo['stl_87'] == 0 || $userWaveInfo['stl_87'] > $mons_atk) {
						$userWaveInfo['stl_87'] = $mons_atk;
					}
					
					if ($userWaveInfo['stl_88'] < $card->acyc) {
						$userWaveInfo['stl_88'] = $card->acyc;
					}
					if ($userWaveInfo['stl_89'] == 0 || $userWaveInfo['stl_89'] > $card->acyc) {
						$userWaveInfo['stl_89'] = $card->acyc;
					}
					
					if ($userWaveInfo['stl_90'] < $monster->lv) {
						$userWaveInfo['stl_90'] = $monster->lv;
					}
					if ($userWaveInfo['stl_91'] == 0 || $userWaveInfo['stl_91'] > $monster->lv) {
						$userWaveInfo['stl_91'] = $monster->lv;
					}
				}
			}
		}
		
		$logBody = array (
				'pt' => $ptype,
				'PlatID' => $type,
				'OpenID' => $openid,
				'AreaID' => $AreaID,
				'BattleID' => $BattleID,
				'ClientStartTime' => User::timeToStr(time()),
				'UserName' => $user->name,
				'SvrUserMoney1' => (isset ( $user )) ? $user->coin : 0,
				'SvrUserMoney2' => (isset ( $user )) ? ($user->gold + $user->pgold) : 0,
				// #PADC_DY# ----------begin----------
				//'UserLevel' => (isset ( $user )) ? $user->clear_dungeon_cnt : 0,
				'UserLevel' => (isset ( $user )) ? $user->lv : 0,
				// #PADC_DY# ----------end----------
				'UserVipLevel' => (isset ( $user )) ? $user->vip_lv : 0,
				'UserMoney3' => (isset ( $user )) ? $user->getStamina () : 0,
				'SvrRoundType' => $dungeon_type,
				'SvrMapid' => $dungeon->id,
				'SvrTopMapid' => UserDungeon::getClearCountNormalDungeon($user->id, $pdo),
				'SvrUserTroopNum' => $userDeckInfo['stl_12'],
				'SvrUserPower' => $userDeckInfo['stl_13'], // -from app
				'SvrUserHP' => $userDeckInfo['stl_14'],
				'SvrUserAttack1' => $userDeckInfo['stl_15'],
				'SvrUserAttack2' => $userDeckInfo['stl_16'],
				'SvrUserAttack3' => $userDeckInfo['stl_17'],
				'SvrUserAttack4' => $userDeckInfo['stl_18'],
				'SvrUserAttack5' => $userDeckInfo['stl_19'],
				'SvrUserResilience' => $userDeckInfo['stl_20'],
				'SvrUserCardNum1' => $userDeckInfo['stl_21'],
				'SvrUserCardLevel1' => $userDeckInfo['stl_22'],
				'SvrUserCardInfoA1' => $userDeckInfo['stl_23'],
				'SvrUserCardInfoB1' => $userDeckInfo['stl_24'],
				'SvrUserCardInfoC1' => $userDeckInfo['stl_25'],
				'SvrUserCardInfoD1' => $userDeckInfo['stl_26'],
				'SvrUserCardAtt1' => $userDeckInfo['stl_27'],
				'SvrUserCardHP1' => $userDeckInfo['stl_28'],
				'SvrUserCardResilience1' => $userDeckInfo['stl_29'],
				'UserCardSkill1' => $userDeckInfo['stl_30'],
				'SvrUserCardNum2' => $userDeckInfo['stl_31'],
				'SvrUserCardLevel2' => $userDeckInfo['stl_32'],
				'SvrUserCardInfoA2' => $userDeckInfo['stl_33'],
				'SvrUserCardInfoB2' => $userDeckInfo['stl_34'],
				'SvrUserCardInfoC2' => $userDeckInfo['stl_35'],
				'SvrUserCardInfoD2' => $userDeckInfo['stl_36'],
				'SvrUserCardAtt2' => $userDeckInfo['stl_37'],
				'SvrUserCardHP2' => $userDeckInfo['stl_38'],
				'SvrUserCardResilience2' => $userDeckInfo['stl_39'],
				'UserCardSkill2' => $userDeckInfo['stl_40'],
				'SvrUserCardNum3' => $userDeckInfo['stl_41'],
				'SvrUserCardLevel3' => $userDeckInfo['stl_42'],
				'SvrUserCardInfoA3' => $userDeckInfo['stl_43'],
				'SvrUserCardInfoB3' => $userDeckInfo['stl_44'],
				'SvrUserCardInfoC3' => $userDeckInfo['stl_45'],
				'SvrUserCardInfoD3' => $userDeckInfo['stl_46'],
				'SvrUserCardAtt3' => $userDeckInfo['stl_47'],
				'SvrUserCardHP3' => $userDeckInfo['stl_48'],
				'SvrUserCardResilience3' => $userDeckInfo['stl_49'],
				'UserCardSkill3' => $userDeckInfo['stl_50'],
				'SvrUserCardNum4' => $userDeckInfo['stl_51'],
				'SvrUserCardLevel4' => $userDeckInfo['stl_52'],
				'SvrUserCardInfoA4' => $userDeckInfo['stl_53'],
				'SvrUserCardInfoB4' => $userDeckInfo['stl_54'],
				'SvrUserCardInfoC4' => $userDeckInfo['stl_55'],
				'SvrUserCardInfoD4' => $userDeckInfo['stl_56'],
				'SvrUserCardAtt4' => $userDeckInfo['stl_57'],
				'SvrUserCardHP4' => $userDeckInfo['stl_58'],
				'SvrUserCardResilience4' => $userDeckInfo['stl_59'],
				'UserCardSkill4' => $userDeckInfo['stl_60'],
				'SvrUserCardNum5' => $userDeckInfo['stl_61'],
				'SvrUserCardLevel5' => $userDeckInfo['stl_62'],
				'SvrUserCardInfoA5' => $userDeckInfo['stl_63'],
				'SvrUserCardInfoB5' => $userDeckInfo['stl_64'],
				'SvrUserCardInfoC5' => $userDeckInfo['stl_65'],
				'SvrUserCardInfoD5' => $userDeckInfo['stl_66'],
				'SvrUserCardAtt5' => $userDeckInfo['stl_67'],
				'SvrUserCardHP5' => $userDeckInfo['stl_68'],
				'SvrUserCardResilience5' => $userDeckInfo['stl_69'],
				'UserCardSkill5' => $userDeckInfo['stl_70'],
				'SvrUserCardNum6' => $userDeckInfo['stl_71'],
				'SvrUserCardLevel6' => $userDeckInfo['stl_72'],
				'SvrUserCardInfoA6' => $userDeckInfo['stl_73'],
				'SvrUserCardInfoB6' => $userDeckInfo['stl_74'],
				'SvrUserCardInfoC6' => $userDeckInfo['stl_75'],
				'SvrUserCardInfoD6' => $userDeckInfo['stl_76'],
				'SvrUserCardAtt6' => $userDeckInfo['stl_77'],
				'SvrUserCardHP6' => $userDeckInfo['stl_78'],
				'SvrUserCardResilience6' => $userDeckInfo['stl_79'],
				'UserCardSkill6' => $userDeckInfo['stl_80'],
				'SvrMonsterBatch' => $userWaveInfo['stl_81'],
				'SvrMonsterNum' => $userWaveInfo['stl_82'],
				'SvrMonsterHPMax' => $userWaveInfo['stl_83'],
				'SvrMonsterHPMin' => $userWaveInfo['stl_84'],
				'SvrMonsterHPTotal' => $userWaveInfo['stl_85'],
				'SvrMonsterAttMax' => $userWaveInfo['stl_86'],
				'SvrMonsterAttMin' => $userWaveInfo['stl_87'],
				'SvrMonsterAttCDMax' => $userWaveInfo['stl_88'],
				'SvrMonsterAttCDMin' => $userWaveInfo['stl_89'],
				'SvrMonsterLevelMax' => $userWaveInfo['stl_90'],
				'SvrMonsterLevelMin' => $userWaveInfo['stl_91'],
				'SvrBossNum' => $userWaveInfo['stl_92'],
				'SvrBossHPMax' => $userWaveInfo['stl_93'],
				'SvrBossHPMin' => $userWaveInfo['stl_94'],
				'SvrBossHPTotal' => $userWaveInfo['stl_95'],
				'SvrBossAttMax' => $userWaveInfo['stl_96'],
				'SvrBossAttMin' => $userWaveInfo['stl_97'],
				'SvrBossAttCDMax' => $userWaveInfo['stl_98'],
				'SvrBossAttCDMin' => $userWaveInfo['stl_99'],
				'SvrBossLevelMax' => $userWaveInfo['stl_100'],
				'SvrBossLevelMin' => $userWaveInfo['stl_101'],
				'SvrBossSkillNum' => $userWaveInfo['stl_102'],
				'SvrMonsterSkillDemageMax' => $boss_skill_max_damage,
				'SvrMonsterSkillDemageMin' => $boss_skill_min_damage,
				'TeammateOpenID' => $helper_openId,
			 	'SrvUserCard1RareClass' =>$card_rare[0],
				'SrvUserCard2RareClass' =>$card_rare[1],
				'SrvUserCard3RareClass' =>$card_rare[2],
				'SrvUserCard4RareClass' =>$card_rare[3],
				'SrvUserCard5RareClass' =>$card_rare[4],
				'SrvUserCard6RareClass' =>$card_rare[5],
				'SrvTeamCardRareClass'  =>$average_rare,
				'SrvUserCard1AwakenSkill'=>$card_ps[0],
				'SrvUserCard2AwakenSkill'=>$card_ps[1],
				'SrvUserCard3AwakenSkill'=>$card_ps[2],
				'SrvUserCard4AwakenSkill'=>$card_ps[3],
				'SrvUserCard5AwakenSkill'=>$card_ps[4],
				'SrvUserCard6AwakenSkill'=>$card_ps[5],
				'SrvRoundStage'=>$wave_count,
		);
		Padc_Log_Log::sendSecRoundStartFlow ( $logBody );
	}

	public static function sendTlogSecRoundEndFlow($user, $dungeon, $user_dungeon, $params, $before_user, $client_ver,$waves_count_detail) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		
		$AreaID = self::PType2AreaId($ptype);
		
		// UNIXタイムスタンプへ変換
		$date = date_create_from_format('ymdHisu', $user_dungeon->sneak_time);
		if ($date) {
			$BattleID = strtotime($date->format('Y-m-d H:i:s'));
		}
		else {
			$BattleID = $user_dungeon->sneak_time;
		}
		
		// 各waveクリアにかかった時間
		$time = (isset($params['time']) ? explode(',', $params['time']) : array());
		$clear_time = 0;
		$wave8_clear_time = 0;
		foreach ($time as $i => $t) {
			$clear_time += $t;
			if ($i >= 7) {
				$wave8_clear_time += $t;
			}
		}
		
		if($user_dungeon instanceof UserDungeon){
			if ($dungeon->isNormalDungeon()) {
				$dungeon_type = 1;
			}
			else {
				$dungeon_type = 2;
			}
			if ($dungeon->dkind == Dungeon::DUNG_KIND_BUY) {
				$dungeon_type = 4;
			}
		}
		else if($user_dungeon instanceof UserRankingDungeon){
			$dungeon_type = 3;
		}
		
		// 各情報
		$added_exp = (int) $user_dungeon->exp;
		$added_coin = (int) $user_dungeon->coin;
		$added_gold = (int) $user_dungeon->gold;
		$clear_response_array = json_decode($user_dungeon->clear_response);
		$get_pieces = $clear_response_array->get_pieces;
		$deck_cards = $clear_response_array->deck_cards;
		$get_cards = $clear_response_array->get_cards;
		$cheat_error = $clear_response_array->cheat_error;
		$added_round = $clear_response_array->roundgain;
		
		
		$get_card_ids = array();
		foreach ($get_cards as $card) {
			$get_card_ids[] = $card->card_id;
		}
		
		$get_piece_ids = array();
		foreach ($get_pieces as $piece) {
			$get_piece_ids[] = $piece[0];
		}
		
		$deck_card_lvs = array();
		foreach ($deck_cards as $card) {
			$deck_card_lvs[] = $card->lv;
		}
		
		
		$member1 = (isset($params['member1']) ? explode(',', $params['member1']) : NULL);
		$member2 = (isset($params['member2']) ? explode(',', $params['member2']) : NULL);
		$member3 = (isset($params['member3']) ? explode(',', $params['member3']) : NULL);
		$member4 = (isset($params['member4']) ? explode(',', $params['member4']) : NULL);
		$member5 = (isset($params['member5']) ? explode(',', $params['member5']) : NULL);
		$helper = (isset($params['helper']) ? explode(',', $params['helper']) : NULL);
		
		$now = time();
		$logBody = array (
				'pt' => $ptype,
				'PlatID' => $type,
				'OpenID' => $openid,
				'AreaID' => $AreaID,
				
				'BattleID' => $BattleID,
				'ClientEndTime' => User::timeToStr($now),
				'ClientVersion' => $client_ver,
				'UserIP' => self::getIP(),
				
				'Result' => ($cheat_error ? 1 : 0),
				'RoundEndType' => 0,
				'RoundTimeUse' => ($now - strtotime($user_dungeon->stamina_spent_at))*1000,
				'ClientRoundTime' => $clear_time,
				'RoundExp' => $added_exp,
				'RoundGold' => $added_coin,
				'RoundExploit' => 0,
				'RoundFriendshipPoint' => 0,
				'RoundCardExp' => implode(',', $get_card_ids),
				'DropItemType' => implode(',', $get_piece_ids),
				
				'RoundAnimalTotal' => (isset($params['stl_15']) ? $params['stl_15']: 0),
				'RoundKillAnimalTotal' => (isset($params['stl_16']) ? $params['stl_16']: 0),
				'RoundType' => $dungeon_type,
				'RoundMapID' => $dungeon->id,
				'staminaBeforeBattle' => $before_user->stamina,
				'staminaAfterBattle' => $user->getStamina(),
				
				'RoundCostMoney1' => 0,
				'RoundCostMoney2' => 0,
				
				'RoundVitTotal' => $user->getStamina(),
				'RoundGoldTotal' => $user->coin,
				'RoundExploitTotal' => $user->fripnt,
				'RoundDiamondTotal' => ($user->gold + $user->pgold),
				'RoundCardLevelTotal' => implode(',', $deck_card_lvs),
				
				'SecPauseTimeTotal' => 0,
				'PauseTimeDetail' => 0,
				'PauseTimeervalDetail' => 0,
				
				'RoundScreenTime1' => (isset($time[0]) ? $time[0]: 0),
				'RoundScreenTime2' => (isset($time[1]) ? $time[1]: 0),
				'RoundScreenTime3' => (isset($time[2]) ? $time[2]: 0),
				'RoundScreenTime4' => (isset($time[3]) ? $time[3]: 0),
				'RoundScreenTime5' => (isset($time[4]) ? $time[4]: 0),
				'RoundScreenTime6' => (isset($time[5]) ? $time[5]: 0),
				'RoundScreenTime7' => (isset($time[6]) ? $time[6]: 0),
				'RoundScreenTime8' => $wave8_clear_time,
				'RoundSpeed' => 0,
				
				'UserCard1AttackMax' => (isset($params['stl_40']) ? $params['stl_40']: 0),
				'UserCard2AttackMax' => (isset($params['stl_41']) ? $params['stl_41']: 0),
				'UserCard3AttackMax' => (isset($params['stl_42']) ? $params['stl_42']: 0),
				'UserCard4AttackMax' => (isset($params['stl_43']) ? $params['stl_43']: 0),
				'UserCard5AttackMax' => (isset($params['stl_44']) ? $params['stl_44']: 0),
				'UserCard6AttackMax' => (isset($params['stl_45']) ? $params['stl_45']: 0),
				'ComboBuffMax' => (isset($params['mcn']) ? 1+0.25*($params['mcn']-1): 1),
				'UserCard1AttackAllCount' => (isset($params['stl_47']) ? $params['stl_47']: 0),
				'UserCard2AttackAllCount' => (isset($params['stl_48']) ? $params['stl_48']: 0),
				'UserCard3AttackAllCount' => (isset($params['stl_49']) ? $params['stl_49']: 0),
				'UserCard4AttackAllCount' => (isset($params['stl_50']) ? $params['stl_50']: 0),
				'UserCard5AttackAllCount' => (isset($params['stl_51']) ? $params['stl_51']: 0),
				'UserCard6AttackAllCount' => (isset($params['stl_52']) ? $params['stl_52']: 0),
				'UserCard1AttackCount' => (isset($params['stl_53']) ? $params['stl_53']: 0),
				'UserCard2AttackCount' => (isset($params['stl_54']) ? $params['stl_54']: 0),
				'UserCard3AttackCount' => (isset($params['stl_55']) ? $params['stl_55']: 0),
				'UserCard4AttackCount' => (isset($params['stl_56']) ? $params['stl_56']: 0),
				'UserCard5AttackCount' => (isset($params['stl_57']) ? $params['stl_57']: 0),
				'UserCard6AttackCount' => (isset($params['stl_58']) ? $params['stl_58']: 0),
				'UserCard1ActiveSkillCount' => (isset($params['stl_59']) ? $params['stl_59']: 0),
				'UserCard2ActiveSkillCount' => (isset($params['stl_60']) ? $params['stl_60']: 0),
				'UserCard3ActiveSkillCount' => (isset($params['stl_61']) ? $params['stl_61']: 0),
				'UserCard4ActiveSkillCount' => (isset($params['stl_62']) ? $params['stl_62']: 0),
				'UserCard5ActiveSkillCount' => (isset($params['stl_63']) ? $params['stl_63']: 0),
				'UserCard6ActiveSkillCount' => (isset($params['stl_64']) ? $params['stl_64']: 0),
				'UserCard1ActiveSkillCDMax' => (isset($member1) ? $member1[5]: 0),
				'UserCard2ActiveSkillCDMax' => (isset($member2) ? $member2[5]: 0),
				'UserCard3ActiveSkillCDMax' => (isset($member3) ? $member3[5]: 0),
				'UserCard4ActiveSkillCDMax' => (isset($member4) ? $member4[5]: 0),
				'UserCard5ActiveSkillCDMax' => (isset($member5) ? $member5[5]: 0),
				'UserCard6ActiveSkillCDMax' => (isset($helper) ? $helper[5]: 0),
				'UserCard1ActiveAttackMax' => (isset($params['stl_71']) ? $params['stl_71']: 0),
				'UserCard2ActiveAttackMax' => (isset($params['stl_72']) ? $params['stl_72']: 0),
				'UserCard3ActiveAttackMax' => (isset($params['stl_73']) ? $params['stl_73']: 0),
				'UserCard4ActiveAttackMax' => (isset($params['stl_74']) ? $params['stl_74']: 0),
				'UserCard5ActiveAttackMax' => (isset($params['stl_75']) ? $params['stl_75']: 0),
				'UserCard6ActiveAttackMax' => (isset($params['stl_76']) ? $params['stl_76']: 0),
				'UserCard1ActiveAttackNumMax' => (isset($params['stl_77']) ? $params['stl_77']: 0),
				'UserCard2ActiveAttackNumMax' => (isset($params['stl_78']) ? $params['stl_78']: 0),
				'UserCard3ActiveAttackNumMax' => (isset($params['stl_79']) ? $params['stl_79']: 0),
				'UserCard4ActiveAttackNumMax' => (isset($params['stl_80']) ? $params['stl_80']: 0),
				'UserCard5ActiveAttackNumMax' => (isset($params['stl_81']) ? $params['stl_81']: 0),
				'UserCard6ActiveAttackNumMax' => (isset($params['stl_82']) ? $params['stl_82']: 0),
				'UserCard1AttackTotal' => (isset($params['stl_83']) ? $params['stl_83']: 0),
				'UserCard2AttackTotal' => (isset($params['stl_84']) ? $params['stl_84']: 0),
				'UserCard3AttackTotal' => (isset($params['stl_85']) ? $params['stl_85']: 0),
				'UserCard4AttackTotal' => (isset($params['stl_86']) ? $params['stl_86']: 0),
				'UserCard5AttackTotal' => (isset($params['stl_87']) ? $params['stl_87']: 0),
				'UserCard6AttackTotal' => (isset($params['stl_88']) ? $params['stl_88']: 0),
				'UserCard1SkillTotal' => (isset($params['stl_89']) ? $params['stl_89']: 0),
				'UserCard2SkillTotal' => (isset($params['stl_90']) ? $params['stl_90']: 0),
				'UserCard3SkillTotal' => (isset($params['stl_91']) ? $params['stl_91']: 0),
				'UserCard4SkillTotal' => (isset($params['stl_92']) ? $params['stl_92']: 0),
				'UserCard5SkillTotal' => (isset($params['stl_93']) ? $params['stl_93']: 0),
				'UserCard6SkillTotal' => (isset($params['stl_94']) ? $params['stl_94']: 0),
				
				'CardHP' => (isset($params['mhp']) ? $params['mhp']: 0),
				'CardInitHP' => (isset($params['stl_96']) ? $params['stl_96']: 0),
				'HPAddCount' => (isset($params['trec']) ? $params['trec']: 0),
				'HPReduceCount' => (isset($params['tdmg']) ? $params['tdmg']: 0),
				'HPEndCount' => (isset($params['stl_99']) ? $params['stl_99']: 0),
				'CardDeathCount' => $user_dungeon->continue_cnt,
				'CardReviveCount' => $user_dungeon->continue_cnt,
				
				'BreadCount' => (isset($params['stl_102']) ? $params['stl_102']: 0),
				'BreadEatCount' => (isset($params['stl_103']) ? $params['stl_103']: 0),
				'BreadAddHPMax' => (isset($params['stl_104']) ? $params['stl_104']: 0),
				'BreadAddHPMin' => (isset($params['stl_105']) ? $params['stl_105']: 0),
				'BreadAddHPTotal' => (isset($params['stl_106']) ? $params['stl_106']: 0),
				'SkillAddHPCount' => (isset($params['stl_107']) ? $params['stl_107']: 0),
				'SkillAddHPTotal' => (isset($params['stl_108']) ? $params['stl_108']: 0),
				
				'RoundMonsterRefreshCount' => count($time),
				'RoundMonsterCount' => (isset($params['stl_110']) ? $params['stl_110']: 0),
				'RoundBossCount' => (isset($params['stl_111']) ? $params['stl_111']: 0),
				'RoundKillMonsterCount' => (isset($params['stl_112']) ? $params['stl_112']: 0),
				'RoundKillBossCount' => (isset($params['stl_113']) ? $params['stl_113']: 0),
				'RoundEndMonsterCount' => (isset($params['stl_114']) ? $params['stl_114']: 0),
				'RoundEndBossCount' => (isset($params['stl_115']) ? $params['stl_115']: 0),
				'BossHPMax' => (isset($params['stl_116']) ? $params['stl_116']: 0),
				'BossHPMin' => (isset($params['stl_117']) ? $params['stl_117']: 0),
				'BossHurtMax' => (isset($params['stl_118']) ? $params['stl_118']: 0),
				'BossHurtMin' => (isset($params['stl_119']) ? $params['stl_119']: 0),
				'BossHurtCount' => (isset($params['stl_120']) ? $params['stl_120']: 0),
				'BossHurtTotal' => (isset($params['stl_121']) ? $params['stl_121']: 0),
				'MonsterHPMax' => (isset($params['stl_122']) ? $params['stl_122']: 0),
				'MonsterHPMin' => (isset($params['stl_123']) ? $params['stl_123']: 0),
				'MonsterHPTotal' => (isset($params['stl_124']) ? $params['stl_124']: 0),
				'MonsterHurtMax' => (isset($params['stl_125']) ? $params['stl_125']: 0),
				'MonsterHurtMin' => (isset($params['stl_126']) ? $params['stl_126']: 0),
				'MonsterHurtCount' => (isset($params['stl_127']) ? $params['stl_127']: 0),
				'MonsterHurtTotal' => (isset($params['stl_128']) ? $params['stl_128']: 0),
				'BossAddHPMax' => (isset($params['stl_129']) ? $params['stl_129']: 0),
				'BossAddHPMin' => (isset($params['stl_130']) ? $params['stl_130']: 0),
				'BossAddHPCount' => (isset($params['stl_131']) ? $params['stl_131']: 0),
				'BossAddHPTotal' => (isset($params['stl_132']) ? $params['stl_132']: 0),
				'MonsterAddHPMax' => (isset($params['stl_133']) ? $params['stl_133']: 0),
				'MonsterAddHPMin' => (isset($params['stl_134']) ? $params['stl_134']: 0),
				'MonsterAddHPCount' => (isset($params['stl_135']) ? $params['stl_135']: 0),
				'MonsterAddHPTotal' => (isset($params['stl_136']) ? $params['stl_136']: 0),
								
				'BossCDMax' => (isset($params['stl_137']) ? $params['stl_137']: 0),
				'BossCDMin' => (isset($params['stl_138']) ? $params['stl_138']: 0),
				'BossAttackCount' => (isset($params['stl_139']) ? $params['stl_139']: 0),
				'BossUseSkillCount' => (isset($params['stl_140']) ? $params['stl_140']: 0),
				'BossTimeTotal' => (isset($params['stl_141']) ? $params['stl_141']: 0),
				'BossAttackMax' => (isset($params['stl_142']) ? $params['stl_142']: 0),
				'BossAttackMin' => (isset($params['stl_143']) ? $params['stl_143']: 0),
				'BossSkillDamageMax' => (isset($params['stl_144']) ? $params['stl_144']: 0),
				'BossSkillDamageMin' => (isset($params['stl_145']) ? $params['stl_145']: 0),
				'BossAttackTotal' => (isset($params['stl_146']) ? $params['stl_146']: 0),
								
				'EnemyCDMax' => (isset($params['stl_147']) ? $params['stl_147']: 0),
				'EnemyCDMin' => (isset($params['stl_148']) ? $params['stl_148']: 0),
				'EnemyAttackCount1' => (isset($params['stl_149']) ? $params['stl_149']: 0),
				'EnemyUseSkillCount' => (isset($params['stl_150']) ? $params['stl_150']: 0),
				'EnemyTimeTotal' => (isset($params['stl_151']) ? $params['stl_151']: 0),
				'EnemyAttackMax' => (isset($params['stl_152']) ? $params['stl_152']: 0),
				'EnemyAttackMin' => (isset($params['stl_153']) ? $params['stl_153']: 0),
				'EnemySkillDamageMax1' => (isset($params['stl_154']) ? $params['stl_154']: 0),
				'EnemySkillDamageMin1' => (isset($params['stl_155']) ? $params['stl_155']: 0),
				'EnemyAttackTotal' => (isset($params['stl_156']) ? $params['stl_156']: 0),
								
				'CardHurtCount' => (isset($params['stl_157']) ? $params['stl_157']: 0),
				'CardHurtMax' => (isset($params['stl_158']) ? $params['stl_158']: 0),
				'CardHurtMin' => (isset($params['stl_159']) ? $params['stl_159']: 0),
				'CardHurtTotal' => (isset($params['tdmg']) ? $params['tdmg']: 0),
								
				'RoundComboCount' => (isset($params['stl_161']) ? $params['stl_161']: 0),
				'RoundComboMax' => (isset($params['mcn']) ? $params['mcn']: 0),
				'RoundComboMin' => (isset($params['stl_163']) ? $params['stl_163']: 0),
				'RoundComboTotal' => (isset($params['stl_164']) ? $params['stl_164']: 0),
				'RoundAutoClear' => (isset($params['stl_165']) ? $params['stl_165']: 0),
				'RoundManualClear' => (isset($params['stl_166']) ? $params['stl_166']: 0),
				'ThreeClear' => (isset($params['stl_167']) ? $params['stl_167']: 0),
				'FourClear' => (isset($params['stl_168']) ? $params['stl_168']: 0),
				'FiveClear' => (isset($params['stl_169']) ? $params['stl_169']: 0),
				'SpecialClear' => (isset($params['stl_170']) ? $params['stl_170']: ''),
				'TouchCount' => (isset($params['stl_171']) ? $params['stl_171']: 0),
				'TouchDistanceMax' => (isset($params['stl_172']) ? $params['stl_172']: 0),
				'TouchDistanceMin' => (isset($params['stl_173']) ? $params['stl_173']: 0),
				'TouchDistanceTotal' => (isset($params['stl_174']) ? $params['stl_174']: 0),
				'TouchTimeMax' => (isset($params['stl_175']) ? $params['stl_175']: 0),
				'TouchTimeMin' => (isset($params['stl_176']) ? $params['stl_176']: 0),
				'TouchTimeTotal' => (isset($params['stl_177']) ? $params['stl_177']: 0),
				'Count1' => (isset($params['stl_178']) ? $params['stl_178']: 0),
				'Count2' => (isset($params['stl_179']) ? $params['stl_179']: 0),
				'Count3' => (isset($params['stl_180']) ? $params['stl_180']: 0),
				'Count4' => (isset($params['stl_181']) ? $params['stl_181']: 0),
				'Count5' => (isset($params['stl_182']) ? $params['stl_182']: 0),
				'Count6' => (isset($params['stl_183']) ? $params['stl_183']: 0),
				'Count7' => (isset($params['stl_184']) ? $params['stl_184']: 0),
				'Count8' => (isset($params['stl_185']) ? $params['stl_185']: 0),
				'Count9' => (isset($params['stl_186']) ? $params['stl_186']: 0),
				'Count10' => (isset($params['stl_187']) ? $params['stl_187']: 0),
				'Count11' => (isset($params['stl_188']) ? $params['stl_188']: 0),
				'Count12' => (isset($params['stl_189']) ? $params['stl_189']: 0),
				'Count13' => (isset($params['stl_190']) ? $params['stl_190']: 0),
				'Count14' => (isset($params['stl_191']) ? $params['stl_191']: 0),
				'Count15' => (isset($params['stl_192']) ? $params['stl_192']: 0),
				'UserOpDesc' => 0,

				'RoundPassStage'               =>$waves_count_detail['rps'],
			 	'RoundStageEnemyTime'          =>$waves_count_detail['rset'],
				'RoundStageOperationStartTime' =>$waves_count_detail['rsost'],
				'RoundStageOperationEndTime'   =>$waves_count_detail['rsoet'],
				'RoundBeadExchange'            =>$waves_count_detail['rbe'],
				'RoundAttackBase'              =>$waves_count_detail['rab'],
				'RoundStageTurnCount'          =>$waves_count_detail['rstc'],
				'RoundTurnCountTotal'          =>$waves_count_detail['rtct'],
				'RoundAverageCombo'            =>$waves_count_detail['rac'],
				'RoundGetScore'                =>$waves_count_detail['rgs'],
				'RoundComboPercentMax'         =>$waves_count_detail['rcpmax'],
				'RoundComboPercentMin'         =>$waves_count_detail['rcpmin'],
		);

		Padc_Log_Log::sendSecRoundEndFlow ( $logBody );
	}
	public static function sendTlogSecRoundEndFlow_Failed($end_type, $user, $dungeon, $user_dungeon, $params, $client_ver,$waves_count_detail) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		
		$AreaID = self::PType2AreaId($ptype);
		
		// UNIXタイムスタンプへ変換
		$date = date_create_from_format('ymdHisu', $user_dungeon->sneak_time);
		if ($date) {
			$BattleID = strtotime($date->format('Y-m-d H:i:s'));
		}
		else {
			$BattleID = $user_dungeon->sneak_time;
		}
		
		// 各waveクリアにかかった時間
		$time = (isset($params['time']) ? explode(',', $params['time']) : array());
		$clear_time = 0;
		$wave8_clear_time = 0;
		foreach ($time as $i => $t) {
			$clear_time += $t;
			if ($i >= 7) {
				$wave8_clear_time += $t;
			}
		}
		
		if($user_dungeon instanceof UserDungeon){
			if ($dungeon->isNormalDungeon()) {
				$dungeon_type = 1;
			}
			else {
				$dungeon_type = 2;
			}
			if ($dungeon->dkind == Dungeon::DUNG_KIND_BUY) {
				$dungeon_type = 4;
			}
		}
		else if($user_dungeon instanceof UserRankingDungeon){
			$dungeon_type = 3;
		}
		
		$ldeck = $user->getLeaderDecksData();
		$deck_card_lvs = array();
		foreach($ldeck as $ld){
			$deck_card_lvs[] = $ld[2];
		}
		
		$member1 = (isset($params['member1']) ? explode(',', $params['member1']) : NULL);
		$member2 = (isset($params['member2']) ? explode(',', $params['member2']) : NULL);
		$member3 = (isset($params['member3']) ? explode(',', $params['member3']) : NULL);
		$member4 = (isset($params['member4']) ? explode(',', $params['member4']) : NULL);
		$member5 = (isset($params['member5']) ? explode(',', $params['member5']) : NULL);
		$helper = (isset($params['helper']) ? explode(',', $params['helper']) : NULL);
		
		$now = time();
		$logBody = array (
				'pt' => $ptype,
				'PlatID' => $type,
				'OpenID' => $openid,
				'AreaID' => $AreaID,
				
				'BattleID' => $BattleID,
				'ClientEndTime' => User::timeToStr($now),
				'ClientVersion' => $client_ver,
				'UserIP' => self::getIP(),
				
				'Result' => 0,
				'RoundEndType' => $end_type,
				'RoundTimeUse' => ($now - strtotime($user_dungeon->stamina_spent_at))*1000,
				'ClientRoundTime' => $clear_time,
				'RoundExp' => 0,
				'RoundGold' => 0,
				'RoundExploit' => 0,
				'RoundFriendshipPoint' => 0,
				'RoundCardExp' => '',
				'DropItemType' => '',
				
				'RoundAnimalTotal' => (isset($params['stl_15']) ? $params['stl_15']: 0),
				'RoundKillAnimalTotal' => (isset($params['stl_16']) ? $params['stl_16']: 0),
				'RoundType' => $dungeon_type,
				'RoundMapID' => $dungeon->id,
				'staminaBeforeBattle' => $user->stamina,
				'staminaAfterBattle' => $user->getStamina(),
				
				'RoundCostMoney1' => 0,
				'RoundCostMoney2' => 0,
				
				'RoundVitTotal' => $user->getStamina(),
				'RoundGoldTotal' => $user->coin,
				'RoundExploitTotal' => $user->fripnt,
				'RoundDiamondTotal' => ($user->gold + $user->pgold),
				'RoundCardLevelTotal' => implode(',', $deck_card_lvs),
				
				'SecPauseTimeTotal' => 0,
				'PauseTimeDetail' => 0,
				'PauseTimeervalDetail' => 0,
				
				'RoundScreenTime1' => (isset($time[0]) ? $time[0]: 0),
				'RoundScreenTime2' => (isset($time[1]) ? $time[1]: 0),
				'RoundScreenTime3' => (isset($time[2]) ? $time[2]: 0),
				'RoundScreenTime4' => (isset($time[3]) ? $time[3]: 0),
				'RoundScreenTime5' => (isset($time[4]) ? $time[4]: 0),
				'RoundScreenTime6' => (isset($time[5]) ? $time[5]: 0),
				'RoundScreenTime7' => (isset($time[6]) ? $time[6]: 0),
				'RoundScreenTime8' => $wave8_clear_time,
				'RoundSpeed' => 0,
				
				'UserCard1AttackMax' => (isset($params['stl_40']) ? $params['stl_40']: 0),
				'UserCard2AttackMax' => (isset($params['stl_41']) ? $params['stl_41']: 0),
				'UserCard3AttackMax' => (isset($params['stl_42']) ? $params['stl_42']: 0),
				'UserCard4AttackMax' => (isset($params['stl_43']) ? $params['stl_43']: 0),
				'UserCard5AttackMax' => (isset($params['stl_44']) ? $params['stl_44']: 0),
				'UserCard6AttackMax' => (isset($params['stl_45']) ? $params['stl_45']: 0),
				'ComboBuffMax' => (isset($params['mcn']) ? 1+0.25*($params['mcn']-1): 1),
				'UserCard1AttackAllCount' => (isset($params['stl_47']) ? $params['stl_47']: 0),
				'UserCard2AttackAllCount' => (isset($params['stl_48']) ? $params['stl_48']: 0),
				'UserCard3AttackAllCount' => (isset($params['stl_49']) ? $params['stl_49']: 0),
				'UserCard4AttackAllCount' => (isset($params['stl_50']) ? $params['stl_50']: 0),
				'UserCard5AttackAllCount' => (isset($params['stl_51']) ? $params['stl_51']: 0),
				'UserCard6AttackAllCount' => (isset($params['stl_52']) ? $params['stl_52']: 0),
				'UserCard1AttackCount' => (isset($params['stl_53']) ? $params['stl_53']: 0),
				'UserCard2AttackCount' => (isset($params['stl_54']) ? $params['stl_54']: 0),
				'UserCard3AttackCount' => (isset($params['stl_55']) ? $params['stl_55']: 0),
				'UserCard4AttackCount' => (isset($params['stl_56']) ? $params['stl_56']: 0),
				'UserCard5AttackCount' => (isset($params['stl_57']) ? $params['stl_57']: 0),
				'UserCard6AttackCount' => (isset($params['stl_58']) ? $params['stl_58']: 0),
				'UserCard1ActiveSkillCount' => (isset($params['stl_59']) ? $params['stl_59']: 0),
				'UserCard2ActiveSkillCount' => (isset($params['stl_60']) ? $params['stl_60']: 0),
				'UserCard3ActiveSkillCount' => (isset($params['stl_61']) ? $params['stl_61']: 0),
				'UserCard4ActiveSkillCount' => (isset($params['stl_62']) ? $params['stl_62']: 0),
				'UserCard5ActiveSkillCount' => (isset($params['stl_63']) ? $params['stl_63']: 0),
				'UserCard6ActiveSkillCount' => (isset($params['stl_64']) ? $params['stl_64']: 0),
				'UserCard1ActiveSkillCDMax' => (isset($member1) ? $member1[5]: 0),
				'UserCard2ActiveSkillCDMax' => (isset($member2) ? $member2[5]: 0),
				'UserCard3ActiveSkillCDMax' => (isset($member3) ? $member3[5]: 0),
				'UserCard4ActiveSkillCDMax' => (isset($member4) ? $member4[5]: 0),
				'UserCard5ActiveSkillCDMax' => (isset($member5) ? $member5[5]: 0),
				'UserCard6ActiveSkillCDMax' => (isset($helper) ? $helper[5]: 0),
				'UserCard1ActiveAttackMax' => (isset($params['stl_71']) ? $params['stl_71']: 0),
				'UserCard2ActiveAttackMax' => (isset($params['stl_72']) ? $params['stl_72']: 0),
				'UserCard3ActiveAttackMax' => (isset($params['stl_73']) ? $params['stl_73']: 0),
				'UserCard4ActiveAttackMax' => (isset($params['stl_74']) ? $params['stl_74']: 0),
				'UserCard5ActiveAttackMax' => (isset($params['stl_75']) ? $params['stl_75']: 0),
				'UserCard6ActiveAttackMax' => (isset($params['stl_76']) ? $params['stl_76']: 0),
				'UserCard1ActiveAttackNumMax' => (isset($params['stl_77']) ? $params['stl_77']: 0),
				'UserCard2ActiveAttackNumMax' => (isset($params['stl_78']) ? $params['stl_78']: 0),
				'UserCard3ActiveAttackNumMax' => (isset($params['stl_79']) ? $params['stl_79']: 0),
				'UserCard4ActiveAttackNumMax' => (isset($params['stl_80']) ? $params['stl_80']: 0),
				'UserCard5ActiveAttackNumMax' => (isset($params['stl_81']) ? $params['stl_81']: 0),
				'UserCard6ActiveAttackNumMax' => (isset($params['stl_82']) ? $params['stl_82']: 0),
				'UserCard1AttackTotal' => (isset($params['stl_83']) ? $params['stl_83']: 0),
				'UserCard2AttackTotal' => (isset($params['stl_84']) ? $params['stl_84']: 0),
				'UserCard3AttackTotal' => (isset($params['stl_85']) ? $params['stl_85']: 0),
				'UserCard4AttackTotal' => (isset($params['stl_86']) ? $params['stl_86']: 0),
				'UserCard5AttackTotal' => (isset($params['stl_87']) ? $params['stl_87']: 0),
				'UserCard6AttackTotal' => (isset($params['stl_88']) ? $params['stl_88']: 0),
				'UserCard1SkillTotal' => (isset($params['stl_89']) ? $params['stl_89']: 0),
				'UserCard2SkillTotal' => (isset($params['stl_90']) ? $params['stl_90']: 0),
				'UserCard3SkillTotal' => (isset($params['stl_91']) ? $params['stl_91']: 0),
				'UserCard4SkillTotal' => (isset($params['stl_92']) ? $params['stl_92']: 0),
				'UserCard5SkillTotal' => (isset($params['stl_93']) ? $params['stl_93']: 0),
				'UserCard6SkillTotal' => (isset($params['stl_94']) ? $params['stl_94']: 0),
				
				'CardHP' => (isset($params['mhp']) ? $params['mhp']: 0),
				'CardInitHP' => (isset($params['stl_96']) ? $params['stl_96']: 0),
				'HPAddCount' => (isset($params['trec']) ? $params['trec']: 0),
				'HPReduceCount' => (isset($params['tdmg']) ? $params['tdmg']: 0),
				'HPEndCount' => (isset($params['stl_99']) ? $params['stl_99']: 0),
				'CardDeathCount' => $user_dungeon->continue_cnt,
				'CardReviveCount' => $user_dungeon->continue_cnt,
				
				'BreadCount' => (isset($params['stl_102']) ? $params['stl_102']: 0),
				'BreadEatCount' => (isset($params['stl_103']) ? $params['stl_103']: 0),
				'BreadAddHPMax' => (isset($params['stl_104']) ? $params['stl_104']: 0),
				'BreadAddHPMin' => (isset($params['stl_105']) ? $params['stl_105']: 0),
				'BreadAddHPTotal' => (isset($params['stl_106']) ? $params['stl_106']: 0),
				'SkillAddHPCount' => (isset($params['stl_107']) ? $params['stl_107']: 0),
				'SkillAddHPTotal' => (isset($params['stl_108']) ? $params['stl_108']: 0),
				
				'RoundMonsterRefreshCount' => count($time),
				'RoundMonsterCount' => (isset($params['stl_110']) ? $params['stl_110']: 0),
				'RoundBossCount' => (isset($params['stl_111']) ? $params['stl_111']: 0),
				'RoundKillMonsterCount' => (isset($params['stl_112']) ? $params['stl_112']: 0),
				'RoundKillBossCount' => (isset($params['stl_113']) ? $params['stl_113']: 0),
				'RoundEndMonsterCount' => (isset($params['stl_114']) ? $params['stl_114']: 0),
				'RoundEndBossCount' => (isset($params['stl_115']) ? $params['stl_115']: 0),
				'BossHPMax' => (isset($params['stl_116']) ? $params['stl_116']: 0),
				'BossHPMin' => (isset($params['stl_117']) ? $params['stl_117']: 0),
				'BossHurtMax' => (isset($params['stl_118']) ? $params['stl_118']: 0),
				'BossHurtMin' => (isset($params['stl_119']) ? $params['stl_119']: 0),
				'BossHurtCount' => (isset($params['stl_120']) ? $params['stl_120']: 0),
				'BossHurtTotal' => (isset($params['stl_121']) ? $params['stl_121']: 0),
				'MonsterHPMax' => (isset($params['stl_122']) ? $params['stl_122']: 0),
				'MonsterHPMin' => (isset($params['stl_123']) ? $params['stl_123']: 0),
				'MonsterHPTotal' => (isset($params['stl_124']) ? $params['stl_124']: 0),
				'MonsterHurtMax' => (isset($params['stl_125']) ? $params['stl_125']: 0),
				'MonsterHurtMin' => (isset($params['stl_126']) ? $params['stl_126']: 0),
				'MonsterHurtCount' => (isset($params['stl_127']) ? $params['stl_127']: 0),
				'MonsterHurtTotal' => (isset($params['stl_128']) ? $params['stl_128']: 0),
				'BossAddHPMax' => (isset($params['stl_129']) ? $params['stl_129']: 0),
				'BossAddHPMin' => (isset($params['stl_130']) ? $params['stl_130']: 0),
				'BossAddHPCount' => (isset($params['stl_131']) ? $params['stl_131']: 0),
				'BossAddHPTotal' => (isset($params['stl_132']) ? $params['stl_132']: 0),
				'MonsterAddHPMax' => (isset($params['stl_133']) ? $params['stl_133']: 0),
				'MonsterAddHPMin' => (isset($params['stl_134']) ? $params['stl_134']: 0),
				'MonsterAddHPCount' => (isset($params['stl_135']) ? $params['stl_135']: 0),
				'MonsterAddHPTotal' => (isset($params['stl_136']) ? $params['stl_136']: 0),
								
				'BossCDMax' => (isset($params['stl_137']) ? $params['stl_137']: 0),
				'BossCDMin' => (isset($params['stl_138']) ? $params['stl_138']: 0),
				'BossAttackCount' => (isset($params['stl_139']) ? $params['stl_139']: 0),
				'BossUseSkillCount' => (isset($params['stl_140']) ? $params['stl_140']: 0),
				'BossTimeTotal' => (isset($params['stl_141']) ? $params['stl_141']: 0),
				'BossAttackMax' => (isset($params['stl_142']) ? $params['stl_142']: 0),
				'BossAttackMin' => (isset($params['stl_143']) ? $params['stl_143']: 0),
				'BossSkillDamageMax' => (isset($params['stl_144']) ? $params['stl_144']: 0),
				'BossSkillDamageMin' => (isset($params['stl_145']) ? $params['stl_145']: 0),
				'BossAttackTotal' => (isset($params['stl_146']) ? $params['stl_146']: 0),
								
				'EnemyCDMax' => (isset($params['stl_147']) ? $params['stl_147']: 0),
				'EnemyCDMin' => (isset($params['stl_148']) ? $params['stl_148']: 0),
				'EnemyAttackCount1' => (isset($params['stl_149']) ? $params['stl_149']: 0),
				'EnemyUseSkillCount' => (isset($params['stl_150']) ? $params['stl_150']: 0),
				'EnemyTimeTotal' => (isset($params['stl_151']) ? $params['stl_151']: 0),
				'EnemyAttackMax' => (isset($params['stl_152']) ? $params['stl_152']: 0),
				'EnemyAttackMin' => (isset($params['stl_153']) ? $params['stl_153']: 0),
				'EnemySkillDamageMax1' => (isset($params['stl_154']) ? $params['stl_154']: 0),
				'EnemySkillDamageMin1' => (isset($params['stl_155']) ? $params['stl_155']: 0),
				'EnemyAttackTotal' => (isset($params['stl_156']) ? $params['stl_156']: 0),
								
				'CardHurtCount' => (isset($params['stl_157']) ? $params['stl_157']: 0),
				'CardHurtMax' => (isset($params['stl_158']) ? $params['stl_158']: 0),
				'CardHurtMin' => (isset($params['stl_159']) ? $params['stl_159']: 0),
				'CardHurtTotal' => (isset($params['tdmg']) ? $params['tdmg']: 0),
								
				'RoundComboCount' => (isset($params['stl_161']) ? $params['stl_161']: 0),
				'RoundComboMax' => (isset($params['mcn']) ? $params['mcn']: 0),
				'RoundComboMin' => (isset($params['stl_163']) ? $params['stl_163']: 0),
				'RoundComboTotal' => (isset($params['stl_164']) ? $params['stl_164']: 0),
				'RoundAutoClear' => (isset($params['stl_165']) ? $params['stl_165']: 0),
				'RoundManualClear' => (isset($params['stl_166']) ? $params['stl_166']: 0),
				'ThreeClear' => (isset($params['stl_167']) ? $params['stl_167']: 0),
				'FourClear' => (isset($params['stl_168']) ? $params['stl_168']: 0),
				'FiveClear' => (isset($params['stl_169']) ? $params['stl_169']: 0),
				'SpecialClear' => (isset($params['stl_170']) ? $params['stl_170']: ''),
				'TouchCount' => (isset($params['stl_171']) ? $params['stl_171']: 0),
				'TouchDistanceMax' => (isset($params['stl_172']) ? $params['stl_172']: 0),
				'TouchDistanceMin' => (isset($params['stl_173']) ? $params['stl_173']: 0),
				'TouchDistanceTotal' => (isset($params['stl_174']) ? $params['stl_174']: 0),
				'TouchTimeMax' => (isset($params['stl_175']) ? $params['stl_175']: 0),
				'TouchTimeMin' => (isset($params['stl_176']) ? $params['stl_176']: 0),
				'TouchTimeTotal' => (isset($params['stl_177']) ? $params['stl_177']: 0),
				'Count1' => (isset($params['stl_178']) ? $params['stl_178']: 0),
				'Count2' => (isset($params['stl_179']) ? $params['stl_179']: 0),
				'Count3' => (isset($params['stl_180']) ? $params['stl_180']: 0),
				'Count4' => (isset($params['stl_181']) ? $params['stl_181']: 0),
				'Count5' => (isset($params['stl_182']) ? $params['stl_182']: 0),
				'Count6' => (isset($params['stl_183']) ? $params['stl_183']: 0),
				'Count7' => (isset($params['stl_184']) ? $params['stl_184']: 0),
				'Count8' => (isset($params['stl_185']) ? $params['stl_185']: 0),
				'Count9' => (isset($params['stl_186']) ? $params['stl_186']: 0),
				'Count10' => (isset($params['stl_187']) ? $params['stl_187']: 0),
				'Count11' => (isset($params['stl_188']) ? $params['stl_188']: 0),
				'Count12' => (isset($params['stl_189']) ? $params['stl_189']: 0),
				'Count13' => (isset($params['stl_190']) ? $params['stl_190']: 0),
				'Count14' => (isset($params['stl_191']) ? $params['stl_191']: 0),
				'Count15' => (isset($params['stl_192']) ? $params['stl_192']: 0),
				'UserOpDesc' => 0,

				'RoundPassStage'               =>$waves_count_detail['rps'],
				'RoundStageEnemyTime'          =>$waves_count_detail['rset'],
				'RoundStageOperationStartTime' =>$waves_count_detail['rsost'],
				'RoundStageOperationEndTime'   =>$waves_count_detail['rsoet'],
				'RoundBeadExchange'            =>$waves_count_detail['rbe'],
				'RoundAttackBase'              =>$waves_count_detail['rab'],
				'RoundStageTurnCount'          =>$waves_count_detail['rstc'],
				'RoundTurnCountTotal'          =>$waves_count_detail['rtct'],
				'RoundAverageCombo'            =>$waves_count_detail['rac'],
				'RoundGetScore'                =>$waves_count_detail['rgs'],
				'RoundComboPercentMax'         =>$waves_count_detail['rcpmax'],
				'RoundComboPercentMin'         =>$waves_count_detail['rcpmin'],
		);
		Padc_Log_Log::sendSecRoundEndFlow ( $logBody );
	}
	
	/**
	 * send Tlog Security Talk Flow
	 * @param array $data
	 * $data['user_id']
	 * $data['receiver_id']
	 * $data['RoleLevel']
	 * $data['ChatType']
	 * $data['TitleContents']
	 * $data['ChatContents']
	 * are all necessary
	 * 
	 */
	public static function sendTlogSecTalkFlow($data){
		$userDeviceData = UserDevice::getUserDeviceFromRedis ($data['user_id'] );
		$userDeviceData_receiver = UserDevice::getUserDeviceFromRedis ( $data['receiver_id'] );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		$areaId = self::PType2AreaId($ptype);		
		$ip = self::getIP();
		$time = time();
		$openid_receiver = $userDeviceData_receiver[ 'oid' ];
		$user = User::find($data['user_id']);
		$logBody = array(
				'pt' 				 => $ptype,
				'OpenID'			 => $openid,
				'PlatID'			 => $type,
				'AreaID'			 => $areaId,//default
				// #PADC_DY# ----------begin----------
				// 'RoleLevel'      	 => $user->clear_dungeon_cnt,
				'RoleLevel'      	 => $user->lv,
				// #PADC_DY# ----------end----------
				'UserIP'     	 	 => $ip,
				'ReceiverOpenID' 	 => $openid_receiver,
				'ReceiverRoleLevel'  => $data['RoleLevel'],
				'ReceiverIP'      	 => 0,
				'ChatType'      	 => $data['ChatType'],
				'TitleContents'      => isset($data['TitleContents'])? $data['TitleContents'] : 0,
				'ChatContents'       => $data['ChatContents'],
				'RoleName'           => $data['RoleName'],
				'ReceiverName'       => $data['ReceiverName'],
		);
		Padc_Log_Log::sendSecTalkFlow( $logBody );
	}
	
	/**
	 * get ip address
	 * @return string
	 */
	public static function getIP(){
		if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			return $_SERVER["REMOTE_ADDR"];
		}
	}
	
	/**
	 * 	 * this function will interpret ptype to areaid like below:
	 * type ---->ptype  ===> areaid
	 * guest	0				3
	 * qq		1				1
	 * wechat	2				0
	 *
	 * 2 is not in areaid,so be carefull when you are going to use it
	 * @param int $ptype
	 * @return int
	 */
	public static function PType2AreaId($ptype){
		switch ($ptype){
			case UserDevice::PTYPE_GUEST:
				return ENV::SECURITY_AREA_GUEST;
				break;
			case UserDevice::PTYPE_QQ:
				return ENV::SECURITY_AREA_QQ;
				break;
			case UserDevice::PTYPE_WECHAT:
				return ENV::SECURITY_AREA_WECHAT;
				break;
		}
	}
	
	
	private static function getUserDeckInfo($user, $pdo, $helper_data){
		global $logger;
		$res = array();
		$param = new DeckParamData();
		$user_deck = UserDeck::findBy(array('user_id'=>$user->id), $pdo);
		$cur_deck = $user_deck->deck_num;
		$res['stl_12'] = $cur_deck;
		
		$decks = array();
		$user_ldeck = json_decode($user->ldeck);
		foreach($user_ldeck as $key => $dcard){
			$card_id = $dcard[1];
			$card = Card::get($card_id);
			if ($card) {
				$card_lv = $dcard[2];
				$skill_id = $card->skill;
				$skill = Skill::get($skill_id);
				$skill_lv = $dcard[3];
				$card_hp = Card::getCardParam($card_lv, $card->mlv, $card->pmhpa, $card->pmhpb, $card->pmhpc) + $dcard[4] * 10;
				$card_atk = Card::getCardParam($card_lv, $card->mlv, $card->patka, $card->patkb, $card->patkc) + $dcard[5] * 5;
				$card_rec = Card::getCardParam($card_lv, $card->mlv, $card->preca, $card->precb, $card->precc) + $dcard[6] * 3;
				
				$decks []= array(
						'id' => $card_id,
						'lv' => $card_lv,
						'slv' => $skill_lv,
						'hp' => $card_hp,
						'atk' => $card_atk,
						'rec' => $card_rec,
						'attr' => $card->attr,
						'sattr' => $card->sattr,
						'mt' => $card->mt,
						'mt2' => $card->mt2,
						'skill' => $skill_id,
						'lskill' => $card->lskill,
						'skillcd' => $skill->ctbs - $skill_lv + 1,
				);
				
				// デッキ戦闘力の計算
				$param->total_hp += $card_hp;
				$param->total_rec += $card_rec;
				$param->attr_atk[$card->attr] += $card_atk;
				if ($card->sattr >= 0) {
					if ($card->attr == $card->sattr) {
						$param->attr_atk[$card->attr] += ceil($card_atk / 10);
					}
					else {
						$param->attr_atk[$card->sattr] += ceil($card_atk / 3);
					}
				}
			}
			else {
				// デッキにモンスターが居ない場合
				$decks []= array(
						'id' => 0,
						'lv' => 0,
						'slv' => 0,
						'hp' => 0,
						'atk' => 0,
						'rec' => 0,
						'attr' => -1,
						'sattr' => -1,
						'mt' => 0,
						'mt2' => 0,
						'skill' => 0,
						'lskill' => 0,
						'skillcd' => 0,
				);
			}
		}
		if ($param->total_hp < 0) {
			$param->total_hp = 1;
		}
		if ($param->total_rec < 0) {
			$param->total_rec = 0;
		}
		foreach($param->attr_atk as $k => $v){
			if ($v < 0) {
				$v = 0;
			} 
			$param->total_atk += $v;
		}
		$param->total_pow = floor($param->total_hp / 10) + floor($param->total_atk / 5) + floor($param->total_rec / 3);
		
		
		if($helper_data[0]){
			$card_id = $helper_data[1];
			$card_lv = $helper_data[2];
			$card = Card::get($card_id);
			$skill_id = $card->skill;
			$skill = Skill::get($skill_id);
			$skill_lv = $helper_data[3];
			$card_hp = Card::getCardParam($card_lv, $card->mlv, $card->pmhpa, $card->pmhpb, $card->pmhpc) + $helper_data[4] * 10;
			$card_atk = Card::getCardParam($card_lv, $card->mlv, $card->patka, $card->patkb, $card->patkc) + $helper_data[5] * 5;
			$card_rec = Card::getCardParam($card_lv, $card->mlv, $card->preca, $card->precb, $card->precc) + $helper_data[6] * 3;
			$decks []= array(
					'id' => $card_id,
					'lv' => $card_lv,
					'slv' => $skill_lv,
					'hp' => $card_hp,
					'atk' => $card_atk,
					'rec' => $card_rec,
					'attr' => $card->attr,
					'sattr' => $card->sattr,
					'mt' => $card->mt,
					'mt2' => $card->mt2,
					'skill' => $skill_id,
					'lskill' => $card->lskill,
					'skillcd' => $skill->ctbs - $skill_lv + 1,
			);
		}
		else {
			// 助っ人モンスターが居ない場合
			$decks []= array(
					'id' => 0,
					'lv' => 0,
					'slv' => 0,
					'hp' => 0,
					'atk' => 0,
					'rec' => 0,
					'attr' => -1,
					'sattr' => -1,
					'mt' => 0,
					'mt2' => 0,
					'skill' => 0,
					'lskill' => 0,
					'skillcd' => 0,
			);
		}
		//$logger->log('$decks:'.print_r($decks, true), 7);
		
		$res['stl_13'] = $param->total_pow;
		$res['stl_14'] = $param->total_hp;
		$res['stl_15'] = $param->attr_atk[0];
		$res['stl_16'] = $param->attr_atk[1];
		$res['stl_17'] = $param->attr_atk[2];
		$res['stl_18'] = $param->attr_atk[3];
		$res['stl_19'] = $param->attr_atk[4];
		$res['stl_20'] = $param->total_rec;
		
		foreach($decks as $k => $deck){
			$res['stl_'.(21+$k*10)] = $deck['id'];
			$res['stl_'.(22+$k*10)] = $deck['lv'];
			$res['stl_'.(23+$k*10)] = $deck['mt'];
			$res['stl_'.(24+$k*10)] = $deck['mt2'];
			$res['stl_'.(25+$k*10)] = $deck['attr']+1;
			$res['stl_'.(26+$k*10)] = $deck['sattr']+1;
			$res['stl_'.(27+$k*10)] = $deck['atk'];
			$res['stl_'.(28+$k*10)] = $deck['hp'];
			$res['stl_'.(29+$k*10)] = $deck['rec'];
			$res['stl_'.(30+$k*10)] = $deck['skill'].','.$deck['slv'].','.$deck['skillcd'].','.$deck['lskill'];
		}
		
		return $res;
	}
	
	
	/**
	 * send tlog change name
	 * @param User $user
	 * @param string $BeforeName
	 * @param string $AfterName
	 */
	public static function sendTlogChangeName($user,$BeforeName,$AfterName){
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		
		Padc_Log_Log::sendChangeName($type, $openid, $ptype, $BeforeName, $AfterName);
	}
    
	/**
	 * send Tlog Monthly Card
	 * @param User $user
	 * @param string $dtEndTime
	 */
	public static function sendTlogMonthlyCard($user,$endTime){
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		
		Padc_Log_Log::sendMonthlyCard($type, $openid, $ptype, $endTime);
	}

	/**
	 * send Tlog Monthly Card
	 * @param User $user
	 * @param string $dtEndTime
	 */
	public static function sendTlogForeverMonthlyCard($user,$endTime){
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$openid = $userDeviceData ['oid'];
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];

		Padc_Log_Log::sendForeverMonthlyCard($type, $openid, $ptype, $endTime);
	}
    
	/**
	 * send Tlog Exchange Item
	 * @param User $user
	 * @param string $dtEndTime
	 */
	public static function sendTlogExchangeItem($user, $money, $exchangeType){
		$userDeviceData = UserDevice::getUserDeviceFromRedis($user->id);
		$openid = $userDeviceData['oid'];
		$type = $userDeviceData['t'];
		$ptype = $userDeviceData['pt'];
		
		Padc_Log_Log::sendExchangeItem($type, $openid, $ptype, $money, $exchangeType);
	}
	
	/**
	 *
	 * 敵の攻撃スキルによる最大ダメージを計算
	 * @param player_hp プレイヤーの最大HP
	 * @param enemy_attack 敵の攻撃力
	 * @param skillType 敵のスキルマスターデータのtypeの値
	 * @param skp1 敵のスキルマスターデータのskp1の値
	 * @param skp2 敵のスキルマスターデータのskp2の値
	 * @param skp3 敵のスキルマスターデータのskp3の値
	 * @return スキルによる最大ダメージ　攻撃スキルでない場合は-1
	 *
	 */
	private static function _checkEnemySkillMaxDamage($player_hp, $enemy_attack, $skillType, $skp1, $skp2, $skp3){
		switch($skillType)
		{
			case	EnemySkill::ENEMY_SKILL_REPEAT_ATK:		//	15:@1～@2回連続攻撃
				// 引数２の倍率で、引数１の最大回数分攻撃
				return (int)($enemy_attack*$skp3*0.01)*$skp2;
			case	EnemySkill::ENEMY_SKILL_48:		//	48:@1%で攻撃しつつ、@2色のドロップをお邪魔ドロップに差し替え
			case	EnemySkill::ENEMY_SKILL_62:		//	62:@1%で攻撃しつつ、盤面くらやみ
			case	EnemySkill::ENEMY_SKILL_63:		//	63:@1%で攻撃しつつ、@2～@3ターンの間、@4(0:指定無し、1:リーダー、2:助っ人、3:リーダーと助っ人、4:サブ)@5体（@4が0、3、4の時に効果）
			case	EnemySkill::ENEMY_SKILL_64:		//	64:@1%で攻撃しつつ、@2個の毒ドロップを作成　@3に1で回復を除外 @4 が１の時猛毒
				return (int)($enemy_attack*$skp1*0.01);
			case	EnemySkill::ENEMY_SKILL_47:				//	47:@1%の確率で、@2%の攻撃力で先制攻撃
				return (int)($enemy_attack*$skp2*0.01);
			case	EnemySkill::ENEMY_SKILL_50:				//	50:プレイヤーのHPが@1%になるダメージ
				return (int)($player_hp * $skp1 *0.01);
			default:
				break;
		}
		return -1;
	}

	/**
	 * 发送玩家领取嘉年华奖励时，对应完成的任务类型和领取奖品的ID
	 * @param $user
	 * @param $prizeid
	 * @param $mtype
	 * @throws PadException
	 */
	public static function sendTlogCarnivalReceivePrizeId($user,$prizeid,$mtype,$desc)
	{
		$userDeviceData = UserDevice::getUserDeviceFromRedis($user->id);
		$openid = $userDeviceData['oid'];
		$type = $userDeviceData['t'];
		$ptype = $userDeviceData['pt'];

		Padc_Log_Log::sendCarnivalReceivePrizeId($type, $openid, $ptype,$prizeid,$mtype,$desc);
	}
	
	
}
