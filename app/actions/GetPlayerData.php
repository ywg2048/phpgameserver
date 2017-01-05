<?php

/**
 * 33. データ取得
 */
class GetPlayerData extends BaseAction {

    // http://pad.localhost/api.php?action=get_player_data&pid=1&sid=1
    public function action($params) {
        // #PADC# ----------begin----------


        // 魔法石同期のため事前準備としてtokenをチェック
        $token = Tencent_MsdkApi::checkToken($params);
        if (!$token) {
            return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
        }
        // 魔法石の同期
        try {
            User::getUserBalance($params["pid"], $token);
        } catch (PadException $e) {
            //ログインタイミングで魔法石同期に失敗した場合もログインできるようにする
            if ($e->getCode() != RespCode::TENCENT_NETWORK_ERROR && $e->getCode() != RespCode::TENCENT_API_ERROR) {
                throw $e;
            }
        }
        

        // #PADC# ----------end----------
        // 書き込み用PDOを共有するためこのような書き方を行っている
        try {
            $pdo = Env::getDbConnectionForUserWrite($params["pid"]);
            $pdo->beginTransaction();
            $user = $this->getUserAndResetFriendPoint($params["pid"],$pdo);
            AllUserBonus::applyBonuses($user, $pdo);
            // MY TODO : ユーザーの経験値関連はなくなる予定。
            $cur_level_exp = 0;
            $next_level_exp = 0;

            // #PADC_DY# ----------begin----------
            $curLevel = LevelUp::get($user->lv);
            $nextLevel = LevelUp::get($user->lv + 1);
            while($user->levelUp($nextLevel)){
                $curLevel = $nextLevel;
                $nextLevel = LevelUp::get($user->lv + 1);
            }
            $cur_level_exp = (int)$curLevel->required_experience;
            if($user->lv == LevelUp::MAX_USER_LEVEL){
                $next_level_exp = 0;
            }else{
                $next_level_exp = (int)$nextLevel->required_experience;
            }
            // #PADC_DY# ----------end----------

            // #PADC# ----------begin----------
            // 経験値に応じて自動でランクアップ処理(チート対策).
            // $curLevel = LevelUp::get($user->lv);
            // $nextLevel = LevelUp::get($user->lv + 1);
            // while($user->levelUp($nextLevel)){
            //   $curLevel = $nextLevel;
            //   $nextLevel = LevelUp::get($user->lv + 1);
            // }
            // $cur_level_exp = (int)$curLevel->required_experience;
            // if($user->lv == 999){
            // $next_level_exp = 0;
            // }else{
            //   $next_level_exp = (int)$nextLevel->required_experience;
            // }
            // #PADC# ----------end----------
            $user->update($pdo);

            $pdo->commit();
            $mails = User::getMailCount($user->id, User::MODE_NORMAL, $pdo, TRUE);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            throw $e;
        }

        $rev = (isset($params["r"])) ? (int) $params["r"] : 1;
        $res = array();
        $res['res'] = RespCode::SUCCESS;
        $res['friendMax'] = (int) $user->friend_max;
        $res['friends'] = Friend::getFriends($user->id, $rev, $token);

        $res['card'] = GetUserCards::getAllUserCards($user->id);

        // #PADC#
        // 所有する欠片データを追加
        $res['piece'] = UserPiece::arrangeColumns(UserPiece::getUserPieces($user->id));

        // #PADC#
        // getOpenFloor()に渡す引数を変更したので合わせて対応
        $res['ndun'] = GetUserDungeonFloors::getActiveUserDungeonFloorsCompact($user, $rev, $pdo);
        $res['ranking_ndun'] = GetUserDungeonFloors::getActiveUserRankingDungeonFloorsCompact($user, $rev, $pdo);
        // #PASC_DY# ----------begin----------
        //  三星领取记录
        $res['reward'] = array();
        $rewards = UserDungeonRewardHistory::findAllBy(array('user_id' => $params["pid"]));
        foreach($rewards as $reward) {
            $res['reward'][] = array(
                'dungeon_id' => (int) $reward->dungeon_id,
                'step_reward_gained' => $reward->getReward()
            );
        }
        
        // 有效活动记录
        $res['activity'] = array();
        $user_activities = UserActivity::getActiveList($params["pid"],$user);
        foreach($user_activities as $user_activity) {
            if($user_activity->status !=3){
                $activity = Activity::get((int)$user_activity->activity_id);
                list($cost_type, $cost_piece_id, $cost_amount) = $activity->getExchangeItem();
                //判断兑换物品数量够不够
                if ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_PIECE) {
                    // 获得用户用于兑换bonus物品的碎片（货币碎片）
                    $cost_piece = UserPiece::getUserPiece($user->id, $cost_piece_id, $pdo);
                    // 用户的货币碎片数量不足
                    if (!$cost_piece) {
                        $user_activity->status = 1;
                    }
                    if ($cost_piece->num < $cost_amount) {
                        $user_activity->status = 1;
                    }else{
                        $user_activity->status = 2;
                    }
                    
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_COIN) {
                    if ($user->coin < $cost_amount) {
                        $user_activity->status = 1;
                    }else{
                        $user_activity->status = 2;
                    }
                } elseif ($cost_type == Activity::ACTIVITY_TYPE_EXCHANGE_TYPE_GOLD) {
                    if(!$user->checkHavingGold($cost_amount)){
                        $user_activity->status = 1;
                    }else{
                        $user_activity->status = 2;
                    }
                }
            }
            
            $res['activity'][] = array(
                'activity_id' => (int) $user_activity->activity_id,
                'status' => (int) $user_activity->status,
                'ordered_at' => $user_activity->ordered_at,
                'counts' => (int)$user_activity->counts
            );
        }
        global $logger;
        $logger->log("res['activity'] = ".json_encode($res['activity']),Zend_log::DEBUG);
        //已经开启的活动列表
        $res['events_on_off'] = array();
        $events_on_offs = LimitEventsTime::findAllBy(array());
        foreach ($events_on_offs as $events_on_off) {
             $time = time();
            if(LimitEventsTime::isEnabled($time,$events_on_off->id)){
               $res['events_on_off'][] = array(
                'events_type' => (int)$events_on_off->events_type,
                'begin_at' => strftime("%y%m%d%H%M%S", strtotime($events_on_off->begin_at)),
                'finish_at' => strftime("%y%m%d%H%M%S", strtotime($events_on_off->finish_at)),
                'description' => $events_on_off->description
                ); 
            }
        }
        //是否有免费刷新机会
        $res['magic_stone_shop_free_refresh'] = 0;
        $user_record = UserMagicStoneRecord::find($params["pid"]);
        if(!$user_record){
           $res['magic_stone_shop_free_refresh'] = 1; 
        }else{
            if($user_record->refresh_times == 0){
                $res['magic_stone_shop_free_refresh'] = 1; 
            }
        }

        // 首充奖励领取记录
        $res['first_charge_gift_received'] = (int) $user->first_charge_gift_received;
        $res['first_charge_record'] = array();
        foreach($user->getFirstChargeRecord() as $t) {
            $res['first_charge_record'][] = (int) GameConstant::getParam('ChargeG'. $t);
        }
        // #PADC_DY# -----------end-----------

        $login_mes = LoginMessage::getMessage($user->area_id);

        if (isset($login_mes['mes'])) {
            $res['msg'] = $login_mes['mes'];
        } else {
            $res['msg'] = GameConstant::getParam("NoticeMessage");
        }


        if (isset($login_mes['one_mes'])) {
            $res['omsg'] = $login_mes['one_mes'];
        } else {
            $res['omsg'] = GameConstant::getParam("NoticeMessage");
        }

        if (isset($login_mes['next_time']) && isset($login_mes['next_mes'])) {
            $res['msg2datepd'] = strftime("%y%m%d%H%M%S", strtotime($login_mes['next_time']));
            $res['msg2'] = $login_mes['next_mes'];
            $res['omsg2'] = $login_mes['next_one_mes'];
        }

        $gacha_mes = GachaMessage::getMessage($user->area_id);
        if (isset($gacha_mes['mes'])) {
            $res['gmsg'] = $gacha_mes['mes'];
        } else {
            $res['gmsg'] = GameConstant::getParam("GachaMessage");
        }
        if (isset($gacha_mes['next_time']) && isset($gacha_mes['next_mes'])) {
            $res['gms2gdatepd'] = strftime("%y%m%d%H%M%S", strtotime($gacha_mes['next_time']));
            $res['gmsg2'] = $gacha_mes['next_mes'];
        }
        $extra_gacha = ExtraGacha::getMessage($user->area_id);
        // 140916 インセル鈴木さんに言われて7.21まで一時的にコメントアウト.
        // if(!empty($extra_gacha)) {
        $res['egacha'] = $extra_gacha;
        // }
        $dcnt = UserUploadData::get_dcnt($user->id, $pdo);
        if (!empty($dcnt)) {
            $res['dcnt'] = $dcnt;
        }
        $dbou = UserBuyDungeon::get_dbou($user->id, $pdo);
        if (!empty($dbou)) {
            $res['dbou'] = $dbou;
        }
        $res['mails'] = $mails;
        $res['pback'] = (int) $user->pback_cnt;
        $res['lstreaks'] = (int) $user->li_str;
        $res['logins'] = (int) $user->li_days;
        $bonuses = LoginStreakBonus::getBonuses($user->li_str);
        $bonus = LoginTotalCountBonus::getBonus($user->li_days);
        $res['litems'] = GetPlayerData::arrangeItemColumns($bonuses);

        $res['litemT'] = $bonus ? array('bonus_id' => (int) $bonus->bonus_id, 'amount' => (int) $bonus->amount) : null;

        $res['cver'] = Version::getVersion(Card::VER_KEY_GROUP);
        $res['sver'] = Version::getVersion(Skill::VER_KEY_GROUP);
        $res['dver'] = Version::getVersion(Dungeon::VER_KEY_GROUP);
        $res['pver'] = Version::getVersion(LimitedBonus::VER_KEY_GROUP);
        $res['msver'] = Version::getVersion(EnemySkill::VER_KEY_GROUP);
        $res['dsver'] = Version::getVersion(DungeonSale::VER_KEY_GROUP);
        // #PADC# ----------begin----------
        $res['padc_pver'] = Version::getVersion(Piece::VER_KEY_GROUP);
        $res['padc_sver'] = Version::getVersion(Scene::VER_KEY_GROUP);
        $res['padc_vver'] = Version::getVersion(VipBonus::VER_KEY_GROUP);
        $res['padc_lver'] = Version::getVersion(LoginTotalCountBonus::VER_KEY_GROUP);

        $res['padc_miver'] = Version::getVersion(Mission::VER_KEY_GROUP);
        $res['padc_rver'] = Version::getVersion(LimitedRanking::VER_KEY_GROUP);
        $res['padc_rdver'] = Version::getVersion(RankingDungeon::VER_KEY_GROUP);
        $res['padc_qvver'] = Version::getVersion(QqVipBonus::VER_KEY_GROUP);
        // #PADC# ----------end----------
        // #PADC_DY# ----------begin----------
        $res['padc_rmver'] = Version::getVersion(Roadmap::VER_KEY_GROUP);
        $res['padc_acver'] = Version::getVersion(Activity::VER_KEY_GROUP);
        $res['padc_glver'] = Version::getVersion(RecommendDeck::VER_KEY_GROUP);
        //新手嘉年华
        $res['padc_cnver'] = Version::getVersion(CarnivalPrize::VER_KEY_GROUP);
        // #PADC_DY# -----------end-----------


        $res['cardMax'] = (int) $user->card_max;
        $res['name'] = $user->name;
        $res['lv'] = (int) $user->lv;
        $res['exp'] = (int) $user->exp;
        $res['camp'] = (int) $user->camp;
        $res['cost'] = (int) $user->cost_max;
        $res['sta'] = (int) $user->getStamina();
        $res['sta_max'] = (int) $user->stamina_max;
        $res['sta_time'] = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));
        $res['gold'] = (int) ($user->gold + $user->pgold);
        $res['coin'] = (int) $user->coin;
        // $res['max_decks'] = (int)$user->decks_max;
        $res['max_decks'] = User::INIT_DECKS_MAX; // 13-05-01 decks_maxは販売しない方針になったため、今後使用しない.
        $user_deck = UserDeck::findBy(array('user_id' => $user->id), $pdo, TRUE);
        $res['curDeck'] = (int) $user_deck->deck_num;
        $res['decks'] = $user_deck->getUserDecks((int) $user->lv);
        $res['deck'] = $user_deck->toCuidsCS();

        $res['fripnt'] = (int) $user->fripnt;
        //扭蛋限制次数
        if(GameConstant::getParam("GachaLimitSwitch")){
            $user_daily_counts =  UserDailyCounts::findBy(array("user_id"=>$user->id));
            if(!$user_daily_counts){
                $counts_ip = 0;
                $counts_normal = 0;
            }else{
                $counts_ip = $user_daily_counts->ip_daily_count;
                $counts_normal = $user_daily_counts->piece_daily_count;
            }
            $res['extra_count'] = GameConstant::getParam("GachaDailyPlayCounts") - $counts_ip;
            $res['normal_count'] = GameConstant::getParam("GachaDailyPlayCounts") - $counts_normal;
        }else{
            $res['extra_count'] = "-1";
            $res['normal_count'] = "-1";
        }
        

        $res['ranking_point'] = $user->ranking_point;
        $res['fp_by_frd'] = $user->fp_by_frd;
        $res['fp_by_non_frd'] = $user->fp_by_non_frd;
        $res['fripntadd'] = $user->fripntadd;
        $res['pbflg'] = ($user->pbflg > 0) ? 1 : 0;
        $res['curLvExp'] = $cur_level_exp;
        $res['nextLvExp'] = $next_level_exp;
        $res['sr'] = (User::getStaminaRecoverInterval() / 60);

        $res['us'] = (int) $user->us;
        // クライアント動作禁止フラグの追加（ビットフィールド）
        // https://61.215.220.70/redmine-pad/issues/980
        // メール受信を旧仕様に :2
        // チートチェックしない :4
        $res['caf'] = 6; // 2+4
        // GALAXY S2 OSV2.3.3で全体エフェクトでアプリが落ちる対策. 20131218
        if ($user->osv == '2.3.3') {
            $galaxy_s2_list = array(
                "GT-I9100",
                "GT-I9100G",
                "GT-I9100M",
                "GT-I9100P",
                "GT-I9100T",
                "GT-I9103",
                "GT-I9108",
                "GT-I9210",
                "ISW11SC",
                "logandsdtv",
                "rs2vepxx",
                "s2ve",
                "s2vep",
                "s2vepzs",
                "SC-02C",
                "SC-03D",
                "SCH-i929",
                "SCH-R760",
                "SGH-I727R",
                "SGH-I757M",
                "SGH-I777",
                "SGH-T989",
                "SGH-T989D",
                "SGH-T989DSGH-I777",
                "SHV-E120K",
                "SHV-E120L",
                "SHV-E120S",
                "SHW-M250K",
                "SHW-M250L",
                "SHW-M250S",
                "SKY_SC-02CISW11SC",
                "SPH-D710",
                "SPH-D710VMUB",
                "t1cmcc",
            );
            if (in_array($user->dev, $galaxy_s2_list)) {
                // 全体攻撃をオフする :512 Version 6.3.0～
                $res['caf'] = 518; // 2+4+512
            }
        }

        // #PADC# ----------begin----------
        // クリアダンジョン数
        $res['clr_dcnt'] = (int) $user->clear_dungeon_cnt;
        // スタミナプレゼント数
        $res['sta_pcnt'] = count(UserPresentsReceive::getUnreceivedPresents($user->id));
        // ミッション報酬受け取り可能数
        $res['ncm'] = UserMission::getClearCount($user->id);
        $res['vip_lv'] = (int) $user->vip_lv;
        $res['tp_gold'] = (int) $user->tp_gold;
        $res['use_money'] = (int) $user->tp_gold_period / GameConstant::GOLD_TO_MONEY_RATE;
        $res['use_stone'] = (int) $user->tc_gold_period;
        $res['exchange_point'] = (int) $user->exchange_point;
        $res['exchange_record'] = $user->getExchangeRecord();
        $res['recharge_day'] = (int) $user->count_p6;

        // 统计类活动用
        $uac = UserActivityCount::getUserActivityCount($user->id,$pdo);
        $res['coin_total'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_COIN_CONSUM)) ? 0 : $uac->coin_consum;
        $res['stamina_buy'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_STA_BUY_COUNT)) ? 0 : $uac->sta_buy_count;
        $res['gacha_cnt'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_GACHA_COUNT)) ? 0 : $uac->gacha_count;
        $res['card_evolve'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_CARD_EVO_COUNT)) ? 0 : $uac->card_evo_count;
        $res['skill_awake'] = empty(Activity::getByType(Activity::ACTIVITY_TYPE_SKILL_AWAKE_COUNT)) ? 0 : $uac->skill_awake_count;


        //新手嘉年华：玩家的等级
        UserCarnivalInfo::carnivalMissionCheck($user->id,CarnivalPrize::CONDITION_TYPE_USER_RANK,$user->lv);

        //新手嘉年华
        $res['car_on'] = UserCarnivalInfo::isCarnivalOpenForUser($user->id,$pdo) ? 1 : 0;
        if(1 == $res['car_on']){
            $user_carnival_info = UserCarnivalInfo::findBy(array('user_id'=>$user->id));
            $res['car_start_at'] = strftime("%y%m%d%H%M%S", strtotime($user_carnival_info->start_at));
            $res['car_end_at']   = strftime("%y%m%d%H%M%S", strtotime($user_carnival_info->end_at));
        }else{
            $res['car_start_at'] = null;
            $res['car_end_at']   = null;
        }

        // 周回チケット数
        $res['round'] = (int) $user->round;
        // 無料コンティニュー回数
        $res['cont'] = (int) $user->cont;
        if (isset($user->tss_end)) {
            $res['tss_end'] = (int) User::strToTime($user->tss_end);
        }
        $vip_lvs = VipBonus::getAvailableBonusLevel($user->id);
        if ($vip_lvs) {
            $res['vip_lvs'] = $vip_lvs;
        }
        $res['li_mdays'] = (int) $user->li_mdays;
        $res['weekly'] = $user->isVipWeeklyBonusAvailable() ? 1 : 0;
        $subs_daily_info = SubscriptionBonus::getDailyBonusInfo($user->id);
        $res['subs'] = isset($subs_daily_info['daily']) ? 1 : 0;
        $res['limit'] = UserPresentsReceive::getPresentLimit($user);
        // 永久月卡领取情况
        $subs_forever_daily_info = SubscriptionBonus::getForeverDailyBonusInfo($user->id);
        $res['subsf'] = isset($subs_forever_daily_info['daily']) ? $subs_forever_daily_info['daily'] : 0;

        // 利用可能なガチャ割引データ
        $gacha_discount = UserGachaDiscount::getActiveGachaDiscount($user->id);
        $res['dgacha'] = $gacha_discount;

        // ガチャの値段（1回と10連）
        $res['gacha_price'] = Gacha::COST_MAGIC_STONE_PREMIUM;
        $res['gacha_price_10'] = Gacha::COST_MAGIC_STONE_PREMIUM_10;

        $res['qq_vip'] = $user->qq_vip;
        //$res['qq_vip_login'] = $user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_LOGIN_BONUS);
        $res['qq_vip_purchase'] = $user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS, User::QQ_ACCOUNT_VIP);
        $res['qq_vip_novice'] = $user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, User::QQ_ACCOUNT_VIP);
        $res['qq_svip_purchase'] = $user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_PURCHASE_BONUS, User::QQ_ACCOUNT_SVIP);
        $res['qq_svip_novice'] = $user->checkQqVipBonusAvalible(QqVipBonus::TYPE_QQ_VIP_NOVICE_BONUS, User::QQ_ACCOUNT_SVIP);
        $res['qq_vip_expire'] = $user->qq_vip_expire;
        $res['qq_svip_expire'] = $user->qq_svip_expire;
        // #PADC# ----------end----------

        $res['r'] = $rev;

        return json_encode($res);
    }

    /**
     * ユーザを返す.
     * 前回からの友情ポイント、何人のフレンド/非フレンドから得たのかをクリアしてDBに保存する.
     */
    private function getUserAndResetFriendPoint($user_id, $pdo) {
        $user = User::find($user_id, $pdo, TRUE);
        $redis = Env::getRedis(Env::REDIS_POINT);
        $key = RedisCacheKey::getFriendPointKey($user_id);
        $value = $redis->get($key);
        if ($value) {
            list($fripntadd, $fp_by_frd, $fp_by_non_frd) = $value;
            //#PADC#
            $fripnt_before = $user->fripnt;
            $user->addFripnt($fripntadd);
            $redis->delete($key);
            if ($fripntadd > 0) {
                $user->accessed_at = User::timeToStr(time());
                $user->accessed_on = $user->accessed_at;
                $user->update($pdo);

                //#PADC# ----------begin----------
                $fripnt_after = $user->fripnt;
                UserTlog::sendTlogMoneyFlow($user, $fripnt_after - $fripnt_before, Tencent_Tlog::REASON_HELP_USERS, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT);
                //#PADC# ----------end----------
            }
        } else {
            $fripntadd = 0;
            $fp_by_frd = 0;
            $fp_by_non_frd = 0;
        }
        // reset
        $user->fripntadd = $fripntadd;
        $user->fp_by_frd = $fp_by_frd;
        $user->fp_by_non_frd = $fp_by_non_frd;
        return $user;
    }

    /**
     * アイテム(ボーナス)リストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
     */
    public static function arrangeItemColumns($items) {
        // マッパー関数. TODO チューニング..
        $mapper = function($item) {
            $item->bonus_id = (int) $item->bonus_id;
            $item->amount = (int) $item->amount;
            unset($item->id);
            unset($item->days);
            unset($item->created_at);
            unset($item->updated_at);
            return $item;
        };
        return array_map($mapper, $items);
    }

    /**
     * All User Bonusリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
     */
    public static function arrangeAllUserBonusesColumns($items) {
        // マッパー関数. TODO チューニング..
        $mapper = function($item) {
            $item->bonus_id = (int) $item->bonus_id;
            $item->amount = (int) $item->amount;
            unset($item->id);
            unset($item->begin_at);
            unset($item->finish_at);
            unset($item->created_at);
            unset($item->updated_at);
            return $item;
        };
        return array_map($mapper, $items);
    }

}
