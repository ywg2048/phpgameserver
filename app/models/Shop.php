<?php

class Shop {

  // #PADC# 1→60に変更
  const PRICE_STAMINA		= 60;
  const PRICE_CARD_SLOT		= 1;
  const PRICE_FRIEND_MAX	= 1;
  // #PADC# 1→60に変更
  const PRICE_CONITINUE		= 60;
  const PRICE_W_STAMINA		= 1;
  const PRICE_W_CONITINUE	= 1;

  const NUM_CARD_SLOTS = 5;
  const NUM_FRIEND_MAX = 5;

  const KEY_A1 = 0;
  const KEY_A2 = 0;
  const KEY_B1 = 0;
  const KEY_B2 = 0;

  // #PADC# パラメータ追加
  public static function buyStamina($user_id, $token){
    $purchased = false;
    //#PADC# ----------begin----------
    User::getUserBalance($user_id, $token);
    $billno = 0;
    //#PADC# ----------end----------
    try{
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();
      $user = User::find($user_id, $pdo, TRUE);
      $stamina = $user->getStamina();
      if($stamina >= $user->stamina_max){
        $purchased = true;
      }elseif($user->checkHavingGold(Shop::PRICE_STAMINA)){
        $log_data['gold_before'] = (int)$user->gold;
        $log_data['pgold_before'] = (int)$user->pgold;
        $purchased = true;
        
        // #PADC#　魔法石消費 
        $billno = $user->payGold(Shop::PRICE_STAMINA, $token, $pdo);
        
        // #PADC#　スタミナ全快 ----------begin----------
        // スタミナ回復購入で上限以上に回復する.（getRevでの判定を削除）
        $user->stamina = $stamina + $user->stamina_max;
        // #PADC# ----------end----------

        // 回復時間が未来の可能性があるので現在時間にセットし全回復済みとする
        $user->stamina_recover_time = User::timeToStr(time());
        $user->accessed_at = User::timeToStr(time());
        $user->accessed_on = $user->accessed_at;
        $user->update($pdo);
        $log_data['gold_after'] = (int)$user->gold;
        $log_data['pgold_after'] = (int)$user->pgold;
        $log_data['dev'] = (int)$user->device_type;
        $log_data['area'] = (int)$user->area_id;


        // 购买体力活动如果开启
        $activity = Activity::getByType(Activity::ACTIVITY_TYPE_STA_BUY_COUNT);
        if($activity){
          $uac = UserActivityCount::getUserActivityCount($user_id, $pdo);
          $uac->addCounts(Activity::ACTIVITY_TYPE_STA_BUY_COUNT);
          $uac->update($pdo);
        }


        UserLogBuyStamina::log($user->id, $log_data, $pdo);
        
        // #PADC# TLOG
        UserTlog::sendTlogMoneyFlow($user, -Shop::PRICE_STAMINA, Tencent_Tlog::REASON_BUY_STAMINA, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($log_data["gold_after"] - $log_data["gold_before"]), abs($log_data["pgold_after"] - $log_data["pgold_before"]));
        
        // #PADC
        $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
      }


      $pdo->commit();
	// #PADC# PDOException → Exception
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      // #PADC# 魔法石消費を取り消す ----------begin----------
      if($billno){
      	$user->cancelPay(Shop::PRICE_STAMINA, $billno, $token);
      }
      //----------end----------
      throw $e;
    }
    
    if(!$purchased){
      throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id=$user_id)'s gold($user->gold) and pgold($user->pgold) are not enough to buy stamina. __NO_TRACE");
    }
    return $user;
  }

  // #PADC# パラメータ追加
  public static function buyCardSlot($user_id, $cur, $token){
    $purchased = false;
    //#PADC# ----------begin----------
    User::getUserBalance($user_id, $token);
    $billno = 0;
    //#PADC# ----------end----------
    try{
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();
      $user = User::find($user_id, $pdo, TRUE);
      if($user->checkHavingGold(Shop::PRICE_CARD_SLOT)){
        if(!is_null($cur) && $user->card_max != $cur){
          throw new PadException(RespCode::UNKNOWN_ERROR, "buyCardSlot cur error User:$user_id card_max:$user->card_max cur:$cur .");
        }
        $log_data['gold_before'] = (int)$user->gold;
        $log_data['pgold_before'] = (int)$user->pgold;
        // #PADC# 魔法石消費
        $billno = $user->payGold(Shop::PRICE_CARD_SLOT, $token, $pdo);
        $purchased = true;
        // 最大カードスロット1拡張
        $user->card_max += Shop::NUM_CARD_SLOTS;
        $user->accessed_at = User::timeToStr(time());
        $user->accessed_on = $user->accessed_at;
        $user->update($pdo);
        $log_data['gold_after'] = (int)$user->gold;
        $log_data['pgold_after'] = (int)$user->pgold;
        $log_data['dev'] = (int)$user->device_type;
        $log_data['area'] = (int)$user->area_id;
        UserLogExpandNumCards::log($user->id, $log_data, $pdo);
        
        // #PADC# TLOG
        //UserTlog::sendTlogMoneyFlow($user, -Shop::PRICE_CARD_SLOT, Tencent_Tlog::REASON_BUY_CARD_SLOT);
      }
      $pdo->commit();
	// #PADC# PDOException → Exception
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      // #PADC# 魔法石消費を取り消す ----------begin----------
      if($billno){
      	$user->cancelPay(Shop::PRICE_CARD_SLOT, $billno, $token);
      }
      //----------end----------
      throw $e;
    }
    if(!$purchased){
      throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id=$user_id)'s gold($user->gold) and pgold($user->pgold) are not enough to expand a card slot.");
    }
    return $user;
  }

  public static function buyFriendMax($user_id, $cur, $mode = null, $token){
    $purchased = false;
    //#PADC# ----------begin----------
    User::getUserBalance($user_id, $token);
    $billno = 0;
    //#PADC# ----------end----------
    try{
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();
      $user = User::find($user_id, $pdo, TRUE);
      if($user->checkHavingGold(Shop::PRICE_FRIEND_MAX)){
        if(!is_null($cur) && $user->friend_max != $cur){
          throw new PadException(RespCode::UNKNOWN_ERROR, "buyFriendMax cur error User:$user_id friend_max:$user->friend_max cur:$cur .");
        }
        $log_data['gold_before'] = (int)$user->gold;
        $log_data['pgold_before'] = (int)$user->pgold;
        // #PADC# 魔法石消費
        $billno = $user->payGold(Shop::PRICE_FRIEND_MAX, $token, $pdo);
        $purchased = true;
        // 最大フレンド数1拡張
        $user->friend_max += Shop::NUM_FRIEND_MAX;
        $user->accessed_at = User::timeToStr(time());
        $user->accessed_on = $user->accessed_at;
        $user->update($pdo);
        $log_data['gold_after'] = (int)$user->gold;
        $log_data['pgold_after'] = (int)$user->pgold;
        $log_data['dev'] = (int)$user->device_type;
        $log_data['area'] = (int)$user->area_id;
//W版からの購入であることを残すようにする
        if(isset($mode)) $log_data['m'] = $mode;
        UserLogBuyFriendMax::log($user->id, $log_data, $pdo);
        
		// #PADC# TLOG
		//UserTlog::sendTlogMoneyFlow($user, -Shop::PRICE_FRIEND_MAX, Tencent_Tlog::REASON_BUY_FRIEND_MAX);
      }
      $pdo->commit();
	// #PADC# PDOException → Exception
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      // #PADC# 魔法石消費を取り消す ----------begin----------
      if($billno){
        $user->cancelPay(Shop::PRICE_FRIEND_MAX, $billno, $token);
      }
      // ---------end----------
      throw $e;
    }
    if(!$purchased){
      throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id=$user_id)'s gold($user->gold) and pgold($user->pgold) are not enough to buy friend_max.");
    }
    return $user;
  }

  public static function buyWStamina($user_id){
    $purchased = false;
    try{
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();
      $user = User::find($user_id, $pdo, TRUE);
      if($user->w_stamina == User::W_STAMINA_MAX){
        $purchased = true;
      }elseif($user->checkHavingGold(Shop::PRICE_W_STAMINA)){
        $log_data['gold_before'] = (int)$user->gold;
        $log_data['pgold_before'] = (int)$user->pgold;
        $purchased = true;
        $user->addGold(-Shop::PRICE_W_STAMINA, $pdo);
        // スタミナ全快
        $user->w_stamina = User::W_STAMINA_MAX;
        $user->accessed_at = User::timeToStr(time());
        $user->accessed_on = $user->accessed_at;
        $user->update($pdo);
        $log_data['gold_after'] = (int)$user->gold;
        $log_data['pgold_after'] = (int)$user->pgold;
        $log_data['dev'] = (int)$user->device_type;
        $log_data['area'] = (int)$user->area_id;
        WUserLogBuyStamina::log($user->id, $log_data, $pdo);
      }
      $pdo->commit();
	// #PADC# PDOException → Exception
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
    
    if(!$purchased){
      throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id=$user_id)'s gold($user->gold) and pgold($user->pgold) are not enough to buy stamina. __NO_TRACE");
    }
    return $user;
  }

  public static function buyWContinue($user_id, $sid, $cint){
    $purchased = false;
    $check2 = 0;
    try{
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();
      $ud = WUserDungeon::find($user_id, $pdo);
      $user = User::find($user_id, $pdo, TRUE);
      $retry_check = 0;
      if($ud->continue_cnt > 0){
        $retry_check = (crc32($sid) + (($ud->continue_cnt - 1) * self::KEY_A1 + self::KEY_A2)) & 0xFFFFFFFF;
      }
      if($cint > 0 && $cint == $retry_check){
        // cintが同じ内容だった場合リトライとみなす.
        $purchased = true;
      }else{
        $check1 = (crc32($sid) + ($ud->continue_cnt * self::KEY_A1 + self::KEY_A2)) & 0xFFFFFFFF;
        if(!empty($ud->cleared_at) || (int)$cint !== $check1){
          // チェックサム不整合.
          throw new PadException(RespCode::CONTINUE_HASH_NOT_FOUND, "W_CONTINUE_HASH_NOT_FOUND user_id=$user_id cint=$cint check=$check1. __NO_TRACE");
        }
        if($user->checkHavingGold(Shop::PRICE_W_CONITINUE)){
          // コンティニュー処理.
          $purchased = true;
          $log_data['gold_before'] = (int)$user->gold;
          $log_data['pgold_before'] = (int)$user->pgold;
          $user->addGold(-Shop::PRICE_W_CONITINUE, $pdo);
          $ud->continue_cnt++;
          $ud->cint = $cint;
          $ud->update($pdo);
          $user->accessed_at = User::timeToStr(time());
          $user->accessed_on = $user->accessed_at;
          $user->update($pdo);
          $log_data['gold_after'] = (int)$user->gold;
          $log_data['pgold_after'] = (int)$user->pgold;
          $log_data['dev'] = (int)$user->device_type;
          $log_data['area'] = (int)$user->area_id;
          WUserLogBuyContinue::log($user->id, $log_data, $pdo);
        }
      }
      $check2 = (crc32($sid) + ($ud->continue_cnt * self::KEY_B1 + self::KEY_B2)) & 0xFFFFFFFF;
      $pdo->commit();
	// #PADC# PDOException → Exception
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
    
    if(!$purchased){
      throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id=$user_id)'s gold($user->gold) and pgold($user->pgold) are not enough to buy continue. __NO_TRACE");
    }
    return array($user, $check2);
  }
  
  //とりあえずここに置きます…
  /**
   * 課金通貨購入(仮)
   */
	public static function buyGold($user_id, $add_gold){
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user = User::find($user_id, $pdo, TRUE);
			$gold_before = $user->gold;
			$pgold_before = $user->pgold;
			$user->addPGold($add_gold);
			$user->update($pdo);

			UserLogAddGold::log($user->id, UserLogAddGold::TYPE_PURCHASE, $gold_before, $user->gold, $pgold_before, $user->pgold, $user->device_type);
			$pdo->commit();
		// #PADC# PDOException → Exception			
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		
		return $user;
	}
    
    /**
    * #PADC_DY#
    * 购买更多次数
    */
    public static function buyFloorContinue($user_id, $dung, $floor,$is_ranking, $token) {
        $floor = $dung * 1000 + $floor;

        $dungeon_floor = DungeonFloor::get($floor);
        if($is_ranking == 1){
          $dungeon_floor = RankingDungeonFloor::get($floor);
        }
        if (!$dungeon_floor) {
            throw new PadException(RespCode::INVALID_PARAMS, "Error params. dung: {$dung} floor: {$floor}");
        } elseif ($dung != $dungeon_floor->dungeon_id) {
            throw new PadException(RespCode::INVALID_PARAMS, "Error params. dung: {$dung} floor: {$floor}");
        }
        
        $pdo = Env::getDbConnectionForUserWrite($user_id);
        
        $user_dungeon_floor = UserDungeonFloor::findBy(array(
            'user_id' => $user_id,
            'dungeon_id' => $dung,
            'dungeon_floor_id' => $floor
        ), $pdo, TRUE);

        if($is_ranking == 1){
          $user_dungeon_floor = UserRankingDungeonFloor::findBy(array(
            'user_id' => $user_id,
            'dungeon_id' => $dung,
            'dungeon_floor_id' => $floor
          ), $pdo, TRUE);
        }
        
        $floor_recovery_gold = GameConstant::getParam('FloorRecoveryGold');
        if (!$user_dungeon_floor) {
            throw new PadException(RespCode::INVALID_PARAMS, "Error params. user: {$user_id} dung: {$dung} floor: {$floor}");
        } elseif((int) $user_dungeon_floor->daily_recovered_times >= count($floor_recovery_gold)) {
            throw new PadException(RespCode::TIMES_USED_OUT, 'Recovery times used out!');
        }
        
        User::getUserBalance($user_id, $token);
        $billno = 0;
        
        try {
            $pdo->beginTransaction();
            
            $user = User::find($user_id, $pdo, TRUE);
            $gold_before = $user->gold;
			$pgold_before = $user->pgold;
            
            $gold_required = $floor_recovery_gold[(int) $user_dungeon_floor->daily_recovered_times];
            if($user->checkHavingGold($gold_required)) {
                $billno = $user->payGold($gold_required, $token, $pdo);
            
                if($user_dungeon_floor->daily_recovered_times <= 0) {
                    $user_dungeon_floor->daily_recovered_times = 1;
                } else {
                    $user_dungeon_floor->daily_recovered_times += 1;
                }
                
                $result = $user->update($pdo) && $user_dungeon_floor->update($pdo);
                $pdo->commit();
                
                UserLogAddGold::log($user->id, UserLogAddGold::TYPE_PURCHASE, $gold_before, $user->gold, $pgold_before, $user->pgold, $user->device_type);
                // #PADC_DY# TLOG
                UserTlog::sendTlogMoneyFlow($user, -$gold_required, Tencent_Tlog::REASON_RESET_DUNGEON_TIMES, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($user->gold - $gold_before), abs($user->pgold - $pgold_before), 0, Tencent_Tlog::SUBREASON_BUY_SNEAK_DUNGEON_COUNT);
                
                if(!$billno || !$result) {
                    throw new PadException(RespCode::UNKNOWN_ERROR, 'Save data error!');
                }
            } else {
                throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id={$user->id})'s gold($user->gold) and pgold($user->pgold) are not enough to buy continue. __NO_TRACE");
            }
            
            return array(
                'buyed_floor_times' => (int) $dungeon_floor->daily_max_times,
                'daily_recovered_times' => $user_dungeon_floor->daily_recovered_times,
                'daily_left_recovery_times' => count($floor_recovery_gold) - $user_dungeon_floor->daily_recovered_times,
                'gold' => $user->gold + $user->pgold
            );
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            if ($billno) {
                $user->cancelPay(Shop::PRICE_CONITINUE, $billno, $token);
            }
            throw $e;
        }
    }

	/**
	 * #PADC_DY#
	 * 交换所刷新扣魔法石
	 */
	public static function buyExchangeRefresh($user_id, $token) {
		User::getUserBalance($user_id, $token);
		$billno = 0;

		try {
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			$user = User::find($user_id, $pdo, TRUE);
			$gold_before = $user->gold;
			$pgold_before = $user->pgold;

			$gold_required = (int)GameConstant::getParam('ExchangeRefreshGold');
			if($user->checkHavingGold($gold_required)) {
				$billno = $user->payGold($gold_required, $token, $pdo);

				$result = $user->update($pdo);

				UserLogAddGold::log($user->id, UserLogAddGold::TYPE_PURCHASE, $gold_before, $user->gold, $pgold_before, $user->pgold, $user->device_type);

              $pdo->commit();

              // #PADC_DY# TLOG
              UserTlog::sendTlogMoneyFlow($user, -$gold_required, Tencent_Tlog::REASON_EXCHANGE_REFRESH, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($user->gold - $gold_before), abs($user->pgold - $pgold_before), 0, Tencent_Tlog::SUBREASON_BUY_EXCHANGE_REFRESH);

              if(!$billno || !$result) {
					throw new PadException(RespCode::UNKNOWN_ERROR, 'Save data error!');
				}
			} else {
				throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id={$user->id})'s gold($user->gold) and pgold($user->pgold) are not enough to buy exchange refresh. __NO_TRACE");
			}

			return array(
				'gold' => $user->gold + $user->pgold
			);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			if ($billno) {
				$user->cancelPay(Shop::PRICE_CONITINUE, $billno, $token);
			}
			throw $e;
		}
	}

  /**
   * #PADC_DY#
   * 限时魔法石商店刷新扣魔法石
   */
  public static function buyExchangeMagicStoneRefresh($user_id,$refresh_times, $token) {
    User::getUserBalance($user_id, $token);
    $billno = 0;

    try {
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();

      $user = User::find($user_id, $pdo, TRUE);
      $gold_before = $user->gold;
      $pgold_before = $user->pgold;
     //按次数去取价格，扣得魔法石递增
     
      $RefreshGold = "ExchangeMagicStoneRefreshGold".$refresh_times;
      
      

      $gold_required = (int)GameConstant::getParam($RefreshGold);
      if($user->checkHavingGold($gold_required)) {
        $billno = $user->payGold($gold_required, $token, $pdo);

        $result = $user->update($pdo);

        $pdo->commit();
        UserLogAddGold::log($user->id, UserLogAddGold::TYPE_PURCHASE, $gold_before, $user->gold, $pgold_before, $user->pgold, $user->device_type);
        UserTlog::sendTlogMoneyFlow($user, -$gold_required, Tencent_Tlog::REASON_MAGICSTONE_SHOP_REFRESH, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($user->gold - $gold_before), abs($user->pgold - $pgold_before), 0, 0);

        if(!$billno || !$result) {
          throw new PadException(RespCode::UNKNOWN_ERROR, 'Save data error!');
        }
      } else {
        throw new PadException(RespCode::NOT_ENOUGH_MONEY, "User(id={$user->id})'s gold($user->gold) and pgold($user->pgold) are not enough to buy exchange refresh. __NO_TRACE");
      }

      return array(
        'gold' => $user->gold + $user->pgold
      );
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      if ($billno) {
        $user->cancelPay(Shop::PRICE_CONITINUE, $billno, $token);
      }
      throw $e;
    }
  }
}
