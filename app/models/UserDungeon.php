<?php

/**
 * 潜入中ダンジョンデータ. (プレイ可能ダンジョンとは異なるので注意.)
 */
class UserDungeon extends BaseModel {

    const TABLE_NAME = "user_dungeons";
    // #PADC#
    // const AddGoldLogType = 2;

    const CM_NORMAL = 0; // ノーマルモード.
    const CM_ALL_FRIEND = 1; // チャレンジモード(全員フレンドチャレンジ).
    const CM_NOT_USE_HELPER = 2; // チャレンジモード(助っ人無しチャレンジ).
    const CARD_TAMADORA = 797; // たまドラのカードID.
    // 各waveをこの時間内にクリアするとチート扱いにする.
    const DEFALULT_WAVE_CLEAR_SEC = 5;
    // この時間内にクリアするとチート扱いにするの加算秒数.
    const DEFALULT_CLEAR_ADD_SEC = 2;
    // デバッグアプリのuser-agent.
    const DEBUG_APP_USER_AGENT = '';
    // #PADC_DY# 关卡最大星级
    const DUNGEON_FLOOR_MAX_STAR = 3;

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
        // #PADC# ----------end----------
    );
    public $user_waves = null;

    // #PADC# ----------begin----------
    const DUNGEON_CLASS = 'Dungeon';
    const DUNGEON_FLOOR_CLASS = 'DungeonFloor';
    const RANKING_DUNGEON_FLOOR_CLASS = 'RankingDungeonFloor';
    const WAVE_CLASS = 'Wave';
    const USER_WAVE_CLASS = 'UserWave';
    const USER_DUNGEON_FLOOR_CLASS = 'UserDungeonFloor';
    const USER_RANKING_DUNGEON_FLOOR_CLASS = 'UserRankingDungeonFloor';

    // チュートリアルダンジョンフロアID
    private $tutorial_dungeon_floor_ids = array(1001, 2001, 3001, 4001, 5001);

    // #PADC# ----------end----------

    /**
     * 指定ダンジョン＆フロアに潜入するエントリポイント.
     * UserDungeonを作成し、永続化する.
     * @return 更新後のUserと作成したUserDungeonのリスト. 処理に失敗した場合はnull.
     * #PADC# 引数にranking_idを追加。
     */
    public static function sneak($user, $dungeon, $dungeon_floor, $sneak_time, $helper_id, $helper_card_id, $helper_card_lv, $helper_skill_lv, $helper_card_plus, $cm, $curdeck, $rev, $round = FALSE, $ranking_id = 0, $total_power, $securitySDK, $player_hp) {
        global $logger;
        
        $daily_left_times = 0; // #PADC_DY# 当日剩余次数

        if ($dungeon->id != $dungeon_floor->dungeon_id) {
            // 指定ダンジョン＆フロアデータ不正.
            $logger->log(("specified dungeon_floor is not in dungeon."), Zend_Log::DEBUG);
            return array($user, null, 0, array(), $daily_left_times);
        }

        $challenge_mode = array(self::CM_NORMAL, self::CM_ALL_FRIEND);
        // $challenge_mode = array(self::CM_NORMAL, self::CM_ALL_FRIEND, self::CM_NOT_USE_HELPER); 助っ人なしモードは延期.
        if (!in_array(intval($cm), $challenge_mode)) {
            // チャレンジモード不正パラメータ.
            $logger->log(("illegal param cm:$cm in dungeon."), Zend_Log::DEBUG);
            return array($user, null, 0, array(), $daily_left_times);
        }
        // #PADC# 継承先でアクセスするDBを切り分けるため、Waveクラスを動的クラスに変更。
        $wave_class = static::WAVE_CLASS;
        $waves = $wave_class::getAllBy(array("dungeon_floor_id" => $dungeon_floor->id), "seq ASC");

        $user_dungeon = null;

        // #PADC# ----------begin----------
        // DROP内容変更するかデバッグユーザー情報を取得
        $debugChangeDrop = DebugUser::isDropChangeDebugUser($user->id);
        if ($debugChangeDrop) {
            // 周回チケット、プラス欠片のDROP確率を取得
            list($prob_round, $prob_plus) = DebugUser::getDropChangeProb($user->id);
        }
        // #PADC# ----------end----------
        // #PADC# ----------begin----------
        // ボーナスを継承先と切り分けるため、メソッド化。
        list($active_bonuses, $dungeon_bonus) = static::getActiveBonus($user, $dungeon, $dungeon_floor);
        // #PADC# ----------end----------
        // #PADC# 継承先で使用するクラスを変更するため、UserWaveを動的クラスとして扱う。
        $user_wave_class = static::USER_WAVE_CLASS;

        // Waveを決定. (new UserWave()内で出現モンスター判定などなされる.)
        // 10個あるキャッシュからランダムで取得し、なければその場で計算. #186
        // #PADC# キャッシュの参照を継承先で変更するため、可変関数に変更。
        $get_key_method = 'get' . static::USER_WAVE_CLASS . 'sKey';
        $key = CacheKey::$get_key_method($dungeon->id, $dungeon_floor->id, mt_rand(1, 10));
        // #PADC# memcache→redis
        $rRedis = Env::getRedisForShareRead();
        $user_waves = $rRedis->get($key);
        if ($user_waves === FALSE) {
            $user_waves = array();
            // ノトーリアスモンスターの出現をダンジョン中1回だけにする
            $notorious_chance_wave = 0;
            if (count($waves) > 1) {
                $notorious_chance_wave = mt_rand(1, count($waves) - 1);  // ボスウェーブには登場しない
            }
            $wave_count = 0;
            foreach ($waves as $wave) {
                $wave_count++;
                if ($wave_count == $notorious_chance_wave) {
                    $notorious_chance = TRUE;
                } else {
                    $notorious_chance = false;
                }
                // DROP内容変更フラグチェック
                if ($debugChangeDrop) {
                    // デバッグ用のUserWave作成処理
                    $user_waves[] = new $user_wave_class($dungeon_floor, $wave, $active_bonuses, $dungeon_bonus, $notorious_chance, $prob_round, $prob_plus);
                } else {
                    // #PADC# 継承先でアクセスするDBを変更できるように、UserWaveを動的クラスに変更。
                    $user_waves[] = new $user_wave_class($dungeon_floor, $wave, $active_bonuses, $dungeon_bonus, $notorious_chance);
                }
            }
            $redis = Env::getRedisForShare();
            $redis->set($key, $user_waves, 20); // 20秒キャッシュ.
        }
        // 永続化.
        $pdo = Env::getDbConnectionForUserWrite($user->id);
        try {
            // #PADC# ----------begin----------
            // MY : 周回を行う場合は助っ人の確認をしない。
            $helper_ps_info = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0); // 好友的leader觉醒技能信息（反作弊用）
            if ($round == FALSE) {
                // 助っ人選択
                if ($helper_id) {
                    // ダンジョン情報ドロップ無チェック(ダンジョンデータの”追加フロア情報”が512で割り切れたら友情ポイント無し)
                    if (($dungeon_floor->eflag & Dungeon::NONE_DROP) != Dungeon::NONE_DROP) {
                        list($user, $point) = Helper::useHelper($user->id, $helper_id);
                    } else {
                        $point = 0;
                    }
                    if ($helper_card_id === null || $helper_card_lv === null) {
                        $helper = User::getCacheFriendData($helper_id, User::FORMAT_REV_1);
                        $helper_card_id = $helper['card'];
                        $helper_card_lv = $helper['clv'];
                    }
                    $helper = User::getCacheFriendData($helper_id, User::FORMAT_REV_2);
                    $helper_ps_info = array_slice($helper, -10, 10);
                } else {
                    $point = 0;
                    $helper_card_id = null;
                    $helper_card_lv = null;
                    $helper_skill_lv = null;
                    $helper_card_plus = null;
                }
            } else {
                $point = 0;
                $helper_id = null;
                $helper_card_id = null;
                $helper_card_lv = null;
                $helper_skill_lv = null;
                $helper_card_plus = null;
            }
            // #PADC# ----------end----------
            $pdo->beginTransaction();
            $user = User::find($user->id, $pdo, TRUE);
            // 潜入可否チェック.
            // #PADC# 継承を考慮して、selfからstaticに変更。
            $user_dungeon_floor = static::checkSneakable($user, $dungeon, $dungeon_floor, $active_bonuses, $cm, $pdo);

            if (empty($user_dungeon_floor)) {
                $logger->log(("cannot sneak this dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
                $pdo->rollback();
                return array($user, null, 0, array(), $daily_left_times);
			}
            
            // #PADC_DY# 记录进入关卡次数 ----------begin----------
            $daily_left_times = $user_dungeon_floor->getLeftPlayingTimes();
            if(empty($user_dungeon_floor->daily_first_played_at) || !static::isSameDay_AM4(static::strToTime($user_dungeon_floor->daily_first_played_at), time())) {
                $user_dungeon_floor->daily_first_played_at = static::timeToStr(time());
                $user_dungeon_floor->daily_played_times = 1;
                $user_dungeon_floor->daily_recovered_times = 0;
                $user_dungeon_floor->update($pdo);
            } elseif($daily_left_times <= 0) {
                $logger->log(("no left times. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
                $pdo->rollback();
                return array($user, null, 0, array(), 0);
            } elseif($user_dungeon_floor->daily_played_times >= 0) {
                $user_dungeon_floor->daily_played_times += 1;
                $user_dungeon_floor->update($pdo);
            } else {
                $user_dungeon_floor->daily_played_times = 1;
                $user_dungeon_floor->update($pdo);
            }
            // #PADC_DY# -----------end-----------

            // ダンジョンフロアの開放時間が未来.
            if (isset($dungeon_floor->start_at) && static::strToTime($dungeon_floor->start_at) > time()) {
                $logger->log(("not been opened yet dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
                $pdo->rollback();
                return array($user, null, 0, array(), $daily_left_times);
            }
            // #PADC# ----------begin----------
            if ($round) {
                // フロアをクリアしたことがあるか確認
                if (empty($user_dungeon_floor->cleared_at)) {
                    $logger->log(("not cleared this dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
                    $pdo->rollback();
                    return array($user, null, 0, array(), $daily_left_times);
                }
                // 周回クリア可能なフロアか確認
                if ($dungeon_floor->rticket == 0) {
                    $logger->log(("not round clear this dungeon. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
                    $pdo->rollback();
                    return array($user, null, 0, array(), $daily_left_times);
                }
            }
            // #PADC# ----------end----------
            // カレントデッキ変更.
            if ($curdeck >= 0) {
                $user = UserDeck::changeCurrentDeck($user, $curdeck, $pdo);
            }

            // 潜入データを構築.
            // #PADC# 継承先で初期化する値が変わるため、メソッド化
            $user_dungeon = static::createUserDungeon($user, $dungeon, $dungeon_floor, $cm, $pdo, $ranking_id);

            // コイン入手アップスキル対応.　user_wavesにボーナスを適用.
            //$leader_card = User::getCacheFriendData($user->id, User::FORMAT_REV_1, $pdo);
            //$leader_card_id = $leader_card['card'];
            $t_lc = explode(',',$user->lc);
            $leader_card_id = $t_lc[1];

            // #PADC# 継承先でアクセスする関数を変更できるように、UserDungeonをstaticに変更。
            $coeff_coin = static::calcCoeffCoin($user->id, $helper_id, $leader_card_id, $helper_card_id);

            // ダンジョン情報ドロップ無チェック(ダンジョンデータの”追加フロア情報”が512で割り切れたらドロップ無し)
            if (($dungeon_floor->eflag & Dungeon::NONE_DROP) != Dungeon::NONE_DROP) {
                // #PADC# 継承先でアクセスするDBを変更できるように、UserWaveを動的クラスに変更。
                list($user_dungeon->beat_bonuses, $user_dungeon->exp, $user_dungeon->coin) = $user_wave_class::getEncodedBeatBonusesAndExpAndCoin($user_waves, $coeff_coin);
            } else {
                $user_dungeon->beat_bonuses = json_encode(array());
            }

            // #PADC# ----------begin----------
            if ($round) {
                // 周回クリアによる追加ボーナス
                $round_bonus = null;
                foreach ($user_waves as $wave) {
                    // DROP内容変更フラグチェック
                    if ($debugChangeDrop) {
                        $round_bonus = $wave->debugGetBeatBonus($dungeon->id, $prob_round, $prob_plus);
                    } else {
                        $round_bonus = $wave->getBeatBonus($dungeon->id);
                    }
                    if ($round_bonus) {
                        break;
                    }
                }
                if (!$round_bonus) {
                    // ドロップが無かった場合は強化の欠片1個をボーナスとする
                    $b = new BeatBonus();
                    $b->item_id = BaseBonus::PIECE_ID;
                    $b->piece_id = Piece::PIECE_ID_STRENGTH;
                    $b->amount = 1; // 個数は変更あるかも
                    $round_bonus = $b;
                } else if ($round_bonus->item_id == BaseBonus::COIN_ID) {
                    $round_bonus->amount = round($round_bonus->amount * $coeff_coin);
                }
                // 必要な情報だけに絞ってJsonTextで保存
                $user_dungeon->round_bonus = json_encode(array(
                    "item_id" => $round_bonus->item_id,
                    "amount" => $round_bonus->amount,
                    "piece_id" => $round_bonus->piece_id,
                ));
            }
            // #PADC# ----------end----------

            $user_dungeon->user_waves = $user_waves;
            $user_dungeon->setHash();
            if ($dungeon_bonus) {
                $user_dungeon->btype = $dungeon_bonus->bonus_type;
                $user_dungeon->barg = $dungeon_bonus->args;
            } else {
                $user_dungeon->btype = null;
                $user_dungeon->barg = null;
            }
            $user_dungeon->spent_stamina = $dungeon_floor->getStaminaCost($active_bonuses);

            $user_dungeon->sneak_time = $sneak_time;

            $user_dungeon->helper_id = $helper_id;
            $user_dungeon->helper_card_id = $helper_card_id;
            $user_dungeon->helper_card_lv = $helper_card_lv;
            $user_dungeon->helper_skill_lv = $helper_skill_lv;
            $user_dungeon->helper_card_plus = $helper_card_plus;
            $user_dungeon->continue_cnt = 0;
            $user_dungeon->sr = $dungeon_floor->sr;

            // #PADC# ----------begin----------
            // チート対策のチェック処理のため、潜入時のフロア構造を記録
            $wave_mons_indexs = $user_dungeon->setCheckCheatInfo($pdo, $helper_ps_info);
            // #PADC# ----------end----------

            $user_dungeon->update($pdo);
            // 初潜入であれば記録.
            if ($cm == self::CM_ALL_FRIEND) {
                // チャレンジモード(全員フレンドチャレンジ).
                if (empty($user_dungeon_floor->cm1_first_played_at)) {
                    // #PADC# 継承を考慮して、UserDungeonをstaticに変更。
                    $user_dungeon_floor->cm1_first_played_at = static::timeToStr(time());
                    $user_dungeon_floor->update($pdo);
                }
            } elseif ($cm == self::CM_NOT_USE_HELPER) {
                // チャレンジモード(助っ人無しチャレンジ).
                if (empty($user_dungeon_floor->cm2_first_played_at)) {
                    // #PADC# 継承を考慮して、UserDungeonをstaticに変更。
                    $user_dungeon_floor->cm2_first_played_at = static::timeToStr(time());
                    $user_dungeon_floor->update($pdo);
                }
            } else {
                // ノーマルモード.
                if (empty($user_dungeon_floor->first_played_at)) {
                    // #PADC# 継承を考慮して、UserDungeonをstaticに変更。
                    $user_dungeon_floor->first_played_at = static::timeToStr(time());
                    $user_dungeon_floor->update($pdo);
                }
            }

            $pdo->commit();

            // #PADC# ----------begin----------
            // お勧めヘルパーの候補リストを更新(フレンド登録がMAXでない、かつダンジョンクリア数2以上(チュートリアルダンジョンクリア済み))
            // PADCではリセマラが不可能なのでダンジョンクリア数の条件はカット
            if ($user->fricnt < $user->friend_max /* && $user->clear_dungeon_cnt >= GameConstant::TUTORIAL_DUNGEON_COUNT */) {
                RecommendedHelperUtil::updateHelpersOfLevel($user->id, $user->lv);
            }
            // #PADC# ----------end----------
            // #PADC# PDOException → Exception
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            throw $e;
        }

        // #PADC# ----------begin---------- TLOG SneakDungeon
        if ($user_dungeon) {
            // 潜入時間がフォーマットに合ってないと、エラーが出てしまうので修正
            $date = date_create_from_format('YmdHisu', $sneak_time);
            if ($date) {
                $stime = $date->format('Y-m-d H:i:s');
            } else {
                $stime = $sneak_time;
            }
            UserTlog::sendTlogSneakDungeon($user, $user_dungeon->dungeon_floor_id, $dungeon->dtype, $total_power, $round, $user->round, isset($helper_id) ? $helper_id : 0, $securitySDK, $stime, $ranking_id, $user_dungeon->spent_stamina);
            if (isset($helper_id)) {
                $helper_data = array($helper_id, $helper_card_id, $helper_card_lv, $helper_skill_lv, $helper_card_plus[0], $helper_card_plus[1], $helper_card_plus[2]);
            } else {
                $helper_data = array(0, 0, 0, 0, 0, 0, 0);
            }


            //卡牌的稀有度,卡牌的技能
            $ldeck     = json_decode($user->ldeck,true);
            $card_ps   = array(0=>'',1=>'',2=>'',3=>'',4=>'',5=>'');
            $card_rare = array();
            $card_count= 0;
            foreach($ldeck as $dk){
                $card  = Card::find($dk[1]);
                if(empty($card) || $dk[0]==0)
                {
                    array_push($card_rare,0);
                    $card_ps[$card_count] = '';
                }else{
                    array_push($card_rare,$card->rare);

                    //觉醒技能
                    $user_card = UserCard::findBy(array('user_id'=>$user->id,'cuid'=>$dk[0]));
                    $pskills   = (array)json_decode($user_card->ps,true);
                    if(!empty($pskills)){
                        $i = 0;
                        foreach($pskills as $pskill){
                            if(1 == $pskill){
                                $ps = 'ps'.$i;
                                $card_ps[$card_count] .= $card->$ps.',';
                                $i++;
                            }
                        }
                        $card_ps[$card_count] = trim($card_ps[$card_count],',');
                    }else{
                        $card_ps[$card_count] = '';
                    }
                }
                $card_count++;
            }

            //伙伴的卡牌的稀有度,卡牌的技能
            if(!empty($helper_card_id)){
                $help_card = Card::get($helper_card_id);
                array_push($card_rare,$help_card->rare);

                $helper_card_cuid = UserCard::getHelperUserCard($helper_id,$helper_card_id);
                if(!empty($helper_card_cuid)){
                    $helper_user_card = UserCard::findBy(array('user_id'=>$helper_id,'cuid'=>$helper_card_cuid));

                    $pskills   = json_decode($helper_user_card->ps,true);
                    if(!empty($pskills)){
                        $i  = 0;
                        foreach($pskills as $pskill){
                            if(1 == $pskill){
                                $ps = 'ps'.$i;
                                $card_ps[$card_count] .= $help_card->$ps.',';
                                $i++;
                            }
                        }
                        $card_ps[$card_count] = trim($card_ps[$card_count],',');
                    }else{
                        $card_ps[$card_count] = '';
                    }
                }else{
                    $card_ps[$card_count] = '';
                }
            }else{
                $card_ps[$card_count] = '';
                array_push($card_rare,0);
            }
            $average_rare = floor(array_sum($card_rare)/count($card_rare));   //队伍平均稀有度

            UserTlog::sendTlogSecRoundStartFlow($user, $helper_data, $dungeon, $user_dungeon, $player_hp,$card_rare,$average_rare,$card_ps,count($user_waves));
          //  UserTlog::sendTlogSecRoundStartFlow($user, $helper_data, $dungeon, $user_dungeon, $player_hp);
        }
        // #PADC# ----------end----------

        return array($user, $user_dungeon, $point, $wave_mons_indexs, $daily_left_times - 1);
    }

    // コイン入手アップスキルの係数計算.
    public static function calcCoeffCoin($user_id, $helper_id, $leader_card_id, $helper_card_id) {
        $coinup_1 = FALSE;
        $coinup_2 = FALSE;
        $coeff_coin = 1.0;
        $leader_card_lskill = Card::get($leader_card_id)->lskill;
        $lc_skill = Skill::get($leader_card_lskill);
        if ($lc_skill->sktp == Skill::TYPE_COINUP) {
            $coinup_1 = TRUE;
        }
        if ($helper_card_id) {
            $helper_card_lskill = Card::get($helper_card_id)->lskill;
            $hc_skill = Skill::get($helper_card_lskill);
            if ($hc_skill->sktp == Skill::TYPE_COINUP) {
                // #PADC# ----------begin----------
                // 冒険者のリーダースキルも発動させる（getRevでの判定を削除）
                $coinup_2 = TRUE;
                // #PADC# ----------end----------
            }
        }
        if ($coinup_1 || $coinup_2) {
            if ($coinup_1) {
                $coeff_coin1 = $lc_skill->skp1;
            } else{
                $coeff_coin1 = 100;
            }
            if ($coinup_2) {
                $coeff_coin2 = $hc_skill->skp1;
            } else {
                $coeff_coin2 = 100;
            }
            $coeff_coin = ($coeff_coin1 / 100.0) * ($coeff_coin2 / 100.0);
        }
        return $coeff_coin;
    }

    /**
     * ダンジョン潜入時のスタミナ消費. ダンジョン潜入とは非同期にコールされる.
     * 既にスタミナ消費済みであれば、一切の更新はおこなわない. 関連チケット #440
     * @return 更新後のUser. 処理に失敗した場合はnull.
     */
    function spendStamina() {
        global $logger;
        try {
            $pdo = Env::getDbConnectionForUserWrite($this->user_id);
            $pdo->beginTransaction();

            $user = User::find($this->user_id, $pdo, TRUE);
            // 潜入可否チェック.
            // UserDungeonに保持されているspent_staminaだけスタミナがあるかチェック.
            // 厳密には、この時点で時限ボーナス終了などによりスタミナ量が変動している可能性があるが、
            // 計算量削減のため、このようにしている.
            if ($this->stamina_spent_at === NULL) {
                // 要スタミナ消費.
                if ($user->getStamina() >= $this->spent_stamina) {
                    // スタミナを消費.
                    $user->useStamina($this->spent_stamina);
                    $user->accessed_at = User::timeToStr(time());
                    $user->accessed_on = $user->accessed_at;
                    $user->update($pdo);

                    $now = time();
                    $sql = "UPDATE " . static::TABLE_NAME . " SET stamina_spent_at = ?,updated_at=now() WHERE user_id = ? ";
                    $bind_param = array(self::timeToStr($now), $user->id);
                    self::prepare_execute($sql, $bind_param, $pdo);

                    $pdo->commit();

                    // ダンジョン潜入のログ出力
                    UserLogSneakDungeon::log($this->user_id, $this->dungeon_floor_id, $user->device_type, $user->area_id, date('Y-m-d H:i:s', $now)
                    );
                } else {
                    // スタミナ消費不可.
                    $logger->log(("cannot spend user stamina"), Zend_Log::DEBUG);
                    $pdo->rollback();
                    $user = NULL;
                }
            }
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
     * ダンジョンをクリア. 計算済みのボーナスをユーザに適用する.
     * 既にクリア済みであれば、ボーナス適用はスキップする.
     * レベルアップしたか(TRUE or FALSE)/開放されたDungeonFloorのリスト(既に開放済みだったものも含む)/
     * 入手した経験値/入手したコイン/入手した魔石のリストを返す.
     * 処理に失敗した場合はNULLを返す.
     */
    // #PADC# add parameters
    public function clear($nxc, $decode_params, $client_ver, $token, $round = FALSE, $ranking_id = 0, $score_values = NULL, $total_power = 0) {
        global $logger;
        $lv_up = FALSE;
        $added_exp = 0;
        $added_coin = 0;
        $added_gold = 0;
        $card_count = 0;
        $get_cards = array();
        // #PADC# ----------begin----------
        $deck_cards = array();
        $get_pieces = array();
        $result_pieces = array();
        $round_bonus = array();
        $user_ranking_number = 0;
        $ranking_score = array();
        $score = (isset($decode_params["s"]) ? $decode_params["s"] : NULL);
        $cheat_error = false; //BANによる零収益の場合にも利用する
        $cheat_error_mes = '';
        $ban_msg = null;
        $ban_end = null;
        $level_up_time_cost = 0; //for tlog playerexpflow
        $added_round = 0;
        $qq_vip = User::QQ_ACCOUNT_NORMAL;
        $qq_coin_bonus = 0;
        $qq_exp_bonus = 0;
        $game_center_coin_bonus = 0;
        // #PADC# ----------end----------
        $user = NULL;
        $src = 0; // Sランククリアフラグ.

        $beat_bonuses_array = json_decode($this->beat_bonuses);
        // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonFloorを動的クラスに変更。
        if(!$ranking_id){
            $user_dungeon_floor_class = static::USER_DUNGEON_FLOOR_CLASS;
        }else{
            $user_dungeon_floor_class = static::USER_RANKING_DUNGEON_FLOOR_CLASS;
        }
   
        $user_dungeon_floor = $user_dungeon_floor_class::findBy(array(
                    "user_id" => $this->user_id,
                    "dungeon_id" => $this->dungeon_id,
                    "dungeon_floor_id" => $this->dungeon_floor_id
        ));

        // #PADC# 継承先でアクセスするDBを変更できるように、DungeonFloorを動的クラスに変更。
        if(!$ranking_id){
            $dungeon_floor_class = static::DUNGEON_FLOOR_CLASS;
        }else{
            $dungeon_floor_class = static::RANKING_DUNGEON_FLOOR_CLASS;
        }

        $dungeon_floor = $dungeon_floor_class::get($this->dungeon_floor_id);
        $next_dungeon_floors = $dungeon_floor->getAllNextFloors();

        $log_data = array();

        // #PADC# ランキング用にレスポンスを追加するためのフラグ。ランキング情報取得時に書き換わる。
        $entry_ranking = FALSE;
        try {
            $pdo = Env::getDbConnectionForUserWrite($this->user_id);
            $pdo->beginTransaction();

            // デッドロック回避のために必ずuserデータをロックする.(sneakと同じ順番にロックする(最初にuser、次にuser_dungeon))
            $user = User::find($this->user_id, $pdo, TRUE);
            // #PADC# ----------begin----------
            // セキュリティTlog対応のため、クリア処理前のユーザーのコピーを作っておく
            $before_user = clone $user;
            // #PADC# ----------end----------
            $before_dungeon_cnt = $user->clear_dungeon_cnt;
            // #PADC_DY#
            $before_user_lv = $user->lv;
            // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonをstaticに変更。
            $user_dungeon = static::findBy(array("user_id" => $this->user_id, "hash" => $this->hash), $pdo, TRUE);
            // #PADC_DY#
            $game_center = (int) $user->game_center;
            $platform_type = UserDevice::getUserPlatformType($this->user_id);

            // #PADC# ----------begin----------
            // 周回処理の切り分け。
            if ($round == false) {
                if ($user_dungeon->stamina_spent_at === NULL) {
                    // スタミナが消費されていないままクリアしようとした.
     throw new PadException(RespCode::FAILED_CLEAR_DUNGEON, "cannot clear before spending stamina. __NO_TRACE");
                   
                }
            } else {
                $round_bonus = json_decode($user_dungeon->round_bonus);
            }
            // #PADC# ----------end----------
            $cleared_at = time();
            $before_card_count = UserCard::countAllBy(array('user_id' => $user->id), $pdo);

            // #PADC# ----------begin----------
            // この段階でクリアダンジョン数の条件を満たしていないものは一旦削除
            // 今回のクリアで条件を満たすものは後処理で追加される
            foreach ($next_dungeon_floors as $key => $next_floor) {
                if ($user->lv < $next_floor->open_rank) {
                    unset($next_dungeon_floors[$key]);
                }
            }
            // #PADC# ----------begin----------
            $deck_ids = explode(",", UserDeck::findBy(array('user_id' => $user->id), $pdo)->toCuidsCS());
            $user_cards = UserCard::findByCuids($user->id, $deck_ids, $pdo);
            $cuid_cards = array();
            $cards = array();
            foreach ($user_cards as $user_card) {
                $cuid_cards[$user_card->cuid] = $user_card;
                // #PADC# ランキングスコア計算にデッキの情報が必要になった。
                $cards[] = Card::get($user_card->card_id);
            }
            if (!is_null($user_dungeon->helper_card_id)) {
                $cards[] = Card::get($user_dungeon->helper_card_id);
            }
            
            $flag = 0;
            if(isset($score_values['is_ranking']) && $score_values['is_ranking'] == 1){
                $flag = 1;
            }
            if ($user_dungeon->cleared_at === NULL || $flag) {

                // #PADC_DY# ----------begin----------
                // 下面的代码因为经验值体系恢复，恢复使用
                // 経験値チェックはしなくて良いのでコメントアウト
                if($user->isExpReachedDoubleNextLevel()) {
                    // ユーザが2ランク分以上の経験値を貯めていた場合、チートと判断し50%の確率でエラーとする.
                    if(mt_rand(0, 1) == 1 && $_SERVER['HTTP_USER_AGENT'] != self::DEBUG_APP_USER_AGENT){
                        $err_msg = "exp cheat. user_id:".$this->user_id." lv:".$user->lv." exp:".$user->exp.".";
                        throw new PadException(RespCode::FAILED_CLEAR_DUNGEON, $err_msg." __NO_TRACE");
                    }
                }
                // #PADC_DY# ----------end----------

                // #PADC# ----------begin----------
                // 経験値チェックはしなくて良いのでコメントアウト
                //if($user->isExpReachedDoubleNextLevel()) {
                //	// ユーザが2ランク分以上の経験値を貯めていた場合、チートと判断し50%の確率でエラーとする.
                //	if(mt_rand(0, 1) == 1 && $_SERVER['HTTP_USER_AGENT'] != self::DEBUG_APP_USER_AGENT){
                //		$err_msg = "exp cheat. user_id:".$this->user_id." lv:".$user->lv." exp:".$user->exp.".";
                //		throw new PadException(RespCode::FAILED_CLEAR_DUNGEON, $err_msg." __NO_TRACE");
                //	}
                //}
                // #PADC# ----------end----------
                // #PADC# ----------begin----------
                // モンスター所持制限は実質無くなるのでコメントアウト
                //if(($user->card_max + 10) < $before_card_count) {
                //	// BOX最大数+10より所持モンスターのほうが多い場合、チートと判断し50%の確率でエラーとする.
                //	if(mt_rand(0, 1) == 1 && $_SERVER['HTTP_USER_AGENT'] != self::DEBUG_APP_USER_AGENT){
                //		$err_msg = "box over cheat. user_id:".$this->user_id." card_max:".$user->card_max." card_count:".$before_card_count.".";
                //		throw new PadException(RespCode::FAILED_CLEAR_DUNGEON, $err_msg." __NO_TRACE");
                //	}
                //}
                // #PADC# ----------end----------
                // #PADC# ----------begin----------
                if ($round == FALSE) {
                    // #PADC# スタミナを消費した時間との差分をとる
                    $cleared_time = $cleared_at - strtotime($user_dungeon->stamina_spent_at);
                    $base_sec = $dungeon_floor->waves * self::DEFALULT_WAVE_CLEAR_SEC + self::DEFALULT_CLEAR_ADD_SEC;
                    if ($cleared_time <= $base_sec) {
                        // （WAVE数*5秒+2秒）以内にクリアしたのでチート扱いとする.
                        if ($_SERVER['HTTP_USER_AGENT'] != self::DEBUG_APP_USER_AGENT) {
                            $err_msg = "short time cleared cheat. user_id:" . $this->user_id . " dungeon_floor_id:" . $this->dungeon_floor_id . " base_sec:" . $base_sec . " sec:" . $cleared_time . ".";
                            throw new PadException(RespCode::FAILED_CLEAR_DUNGEON, $err_msg . " __NO_TRACE");
                        }
                    }

                    // #PADC# ----------begin----------
                    // アプリ側から送られたチート判断情報
                    if (isset($decode_params['cchk'])) {
                        $cchk = $decode_params['cchk'];
                        if ($cchk) {
                            $cheat_error = true;
                            $cheat_error_mes = 'appli detect cheat. cchk:' . sprintf("%016db", decbin($cchk));
                            $logger->log(('cheat check error. ' . $cheat_error_mes), Zend_Log::DEBUG);
                        }
                    } else {
                        $cheat_error = true;
                        $cheat_error_mes = 'cchk is none.';
                        $logger->log(('cheat check error. ' . $cheat_error_mes), Zend_Log::DEBUG);
                    }

                    if (!$cheat_error) {
                        $helper_pdo = null;
                        $helper = (isset($decode_params['helper']) ? explode(',', $decode_params['helper']) : NULL);
                        if ($helper) {
                            $helper_id = end($helper); // 一番最後がプレイヤーID
                            if ($helper_id > 0) {
                                $share = Env::getDbConnectionForShare();
                                $u1_device = UserDevice::find($user->id, $share);
                                $u2_device = UserDevice::find($helper_id, $share);
                                if ($u1_device->dbid == $u2_device->dbid) {
                                    $helper_pdo = $pdo;
                                } else {
                                    $helper_pdo = Env::getDbConnectionForUserRead($helper_id);
                                }
                            }
                        }
                        // チート対策のチェック処理
                        list($cheat_error, $cheat_error_mes) = $this->checkCheatInfo($decode_params, $helper_pdo);
                    }

                    if ($cheat_error) {
                        // デバッグユーザー登録されていたらチートエラーフラグをOFF
                        if (DebugUser::isCheatCheckDebugUser($this->user_id)) {
                            $cheat_error = false;
                            $logger->log(('cheat check error through. debug user!'), Zend_Log::DEBUG);
                        }

                        // チュートリアルダンジョンの場合はチートエラーフラグをOFF
                        if (!static::isRankingDungeon()) {
                            if (in_array($this->dungeon_floor_id, $this->tutorial_dungeon_floor_ids)) {
                                $cheat_error = false;
                                $logger->log(('cheat check error through. tutorial dungeon'), Zend_Log::DEBUG);
                            }
                        }
                    }

                    // #PADC# ----------end----------
                } else {
                    $cleared_time = 0;
                    // スタミナと周回チケットをチェック
                    if ($user->getStamina() < $user_dungeon->spent_stamina) {
                        // スタミナ消費不可.
                        throw new PadException(RespCode::LACK_OF_STAMINA, "cannot spend user stamina. __NO_TRACE");
                    }

                    if ($user->round < $dungeon_floor->rticket) {
                        // 周回チケットが足らない.
                        throw new PadException(RespCode::NOT_ENOUGH_USER_ROUND, "not enough user round. __NO_TRACE");
                    }

                    // 周回クリアではチート対策チェックはしない
                }

                if (!$cheat_error) {
                    // ユーザーが零収益の処罰対象だった場合は強制的にチートエラーフラグをON
                    $punish_info = UserBanMessage::getPunishInfo($this->user_id, User::PUNISH_ZEROPROFIT, $pdo);
                    if ($punish_info) {
                        $cheat_error = true;
                        $ban_msg = $punish_info['msg'];
                        $ban_end = $punish_info['end'];
                        $logger->log(('cheat check error force on. punish zero profit user!'), Zend_Log::DEBUG);
                    }
                }
                // #PADC# ----------end----------
                // cleared_at === NULL のときに限りボーナス適用.
                $log_data["before_coin"] = (int) $user->coin;
                $log_data["before_gold"] = (int) ($user->gold);
                $log_data["before_pgold"] = (int) ($user->pgold);
                $log_data["before_round"] = (int) ($user->round);

                // ボーナス適用.
                $log_data["beat_bonuses"] = array();
                //Sランクを超えていた場合、初回はたまドラ付与.
                // #PADC# PADC版ではSランク判定処理はしない
                /* if($this->sr > 0){
                  $srank_flg = UserDungeonScore::updateHighScore($this->user_id, $this->dungeon_floor_id, $this->sr, $score, $pdo);
                  if($srank_flg){
                  $bonus_tamadora = array(
                  'item_id' => self::CARD_TAMADORA,
                  'amount' => 1,
                  );
                  $beat_bonuses_array[] = (object)$bonus_tamadora;
                  $src = 1;
                  }
                  } */
                // #PADC# ----------begin----------
                $apply_results = array();
                $add_cards = array();
                $add_pieces = array();
                // チート検知したら報酬は付与しない
                if ($cheat_error) {
                    // 付与経験値を0に変更
                    $user_dungeon->exp = 0;

                    if ($round) {
                        // 周回ボーナス内容をクリア
                        $round_bonus = array();
                        $user_dungeon->round_bonus = json_encode($round_bonus);
                    }
                } else {
                    // #PADC# QQ会員ボーナス値取得
                    $qq_vip = (int) $user->qq_vip;
                    if ($qq_vip == User::QQ_ACCOUNT_VIP) {
                        $qq_coin_bonus = GameConstant::getParam("QQCoinBonus");
                        $qq_exp_bonus = GameConstant::getParam("QQExpBonus");
                    } else if ($qq_vip == User::QQ_ACCOUNT_SVIP) {
                        $qq_coin_bonus = GameConstant::getParam("QQVipCoinBonus");
                        $qq_exp_bonus = GameConstant::getParam("QQVipExpBonus");
                    }
                    // #PADC_DY# ----------begin----------
                    // 安卓只有游戏中心启动才有加成
                    if ($user->device_type == UserDevice::TYPE_ADR){
                        if($game_center == User::QQ_GAME_CENTER || $game_center == User::WECHAT_GAME_CENTER){
                            $game_center_coin_bonus = GameConstant::getParam("GameCenterCoinBonus");
                        }
                    } else {
                        // 苹果要求游客、QQ游戏中心、微信游戏中心登录都享有相同的加成
                        if($game_center == User::QQ_GAME_CENTER || $game_center == User::WECHAT_GAME_CENTER || $platform_type == UserDevice::PTYPE_GUEST) {
                            $game_center_coin_bonus = GameConstant::getParam("GameCenterCoinBonus");
                        }
                    }
                    // #PADC_DY# ----------end----------
                    foreach ($beat_bonuses_array as $beat_bonus) {
                        // INFO:PADC版ではダンジョンクリア時に直接モンスター付与はされないが一応残しておく
                        if ($beat_bonus->item_id <= BaseBonus::MAX_CARD_ID) {
                            // 付与するアイテムがカードの時.
                            $plus_hp = isset($beat_bonus->plus_hp) ? $beat_bonus->plus_hp : 0;
                            $plus_atk = isset($beat_bonus->plus_atk) ? $beat_bonus->plus_atk : 0;
                            $plus_rec = isset($beat_bonus->plus_rec) ? $beat_bonus->plus_rec : 0;
                            $add_cards[] = UserCard::addCardsToUserReserve(
                                            $user->id, $beat_bonus->item_id, $beat_bonus->amount, UserCard::DEFAULT_SKILL_LEVEL, $pdo, $plus_hp, $plus_atk, $plus_rec, 0 // psk
                            );
                        } else if ($beat_bonus->item_id == BaseBonus::PIECE_ID) {
                            // INFO:カケラ付与
                            $piece_id = $beat_bonus->piece_id;
                            $piece_num = $beat_bonus->amount;
                            $get_pieces[] = array($piece_id, $piece_num);

                            // 同じIDのカケラが複数あっても大丈夫なように対応
                            if (array_key_exists($piece_id, $add_pieces)) {
                                $add_card = $add_pieces[$piece_id]->addPiece($piece_num, $pdo);
                                if ($add_card) {
                                    $add_cards[] = $add_card;
                                }
                            } else {
                                $add_result = UserPiece::addUserPieceToUserReserve(
                                                $user->id, $piece_id, $piece_num, $pdo
                                );
                                $add_pieces[$piece_id] = $add_result['piece'];
                                if (array_key_exists('card', $add_result)) {
                                    $add_cards[] = $add_result['card'];
                                }
                            }
                        } else {
                            $b = new BeatBonus();
                            $b->item_id = $beat_bonus->item_id;
                            $b->amount = $beat_bonus->amount;
                            // #PADC# QQ会員ボーナス適用
                            if ($b->item_id == BaseBonus::COIN_ID) {
                                if ($qq_coin_bonus > 0) {
                                    $b->amount = ceil($b->amount * (10000 + $qq_coin_bonus) / 10000);
                                }
                                if ($game_center_coin_bonus > 0) {
                                    $b->amount = ceil($b->amount * (10000 + $game_center_coin_bonus) / 10000);
                                }
                            }
                            // #PADC# add parameters
                            $user = $b->apply($user, $pdo, null, $token);

                            $log_data["beat_bonuses"][] = array("award_id" => (int) $b->item_id, "amount" => (int) $b->amount);
                        }
                    }

                    // 経験値&コイン付与.
                    // #PADC# QQ会員ボーナス適用
                    if ($qq_exp_bonus > 0) {
                        $user_dungeon->exp = ceil($user_dungeon->exp * (10000 + $qq_exp_bonus) / 10000);
                    }
                    if ($qq_coin_bonus > 0) {
                        $user_dungeon->coin = ceil($user_dungeon->coin * (10000 + $qq_coin_bonus) / 10000);
                    }
                    if ($game_center_coin_bonus > 0) {
                        $user_dungeon->coin = ceil($user_dungeon->coin * (10000 + $game_center_coin_bonus) / 10000);
                    }
                    // #PADC#
                    //$user->addExp($user_dungeon->exp);	// ユーザーへの経験値付与は無し

                    // #PADC_DY# resume to rank-up system, add user's exp
                    $user->addExp($user_dungeon->exp);
                    $user->addCoin($user_dungeon->coin);
                }
                // #PADC# ----------end----------
                // #PADC# 継承先でアクセスするDBを変更できるように、Dungeonを動的クラスに変更。
                $dungeon_class = static::DUNGEON_CLASS;
                // #PADC# 継承先でアクセスするDBを変更できるように、Dungeonを動的クラスに変更。
                $dungeon = $dungeon_class::get($this->dungeon_id);
                if (!$dungeon) {
                    // 該当のダンジョンが存在しない.
                    $err_msg = "Dungeon does not exist. user_id:" . $this->user_id . " dungeon_id:" . $this->dungeon_id . ".";
                    throw new PadException(RespCode::FAILED_CLEAR_DUNGEON, $err_msg . " __NO_TRACE");
                }
                // #PADC_DY# ----------begin----------
                // 三星评价标志位
                $max_star_flag = $dungeon->isSpecialDungeon();
                // #PADC_DY# -----------end-----------
                // チート検出した場合はダンジョンクリア処理も通さない
                if ($cheat_error) {
                    // 開放されるダンジョンフロアを全てクリア
                    $next_dungeon_floors = array();
                } else {
                    // クリアフラグ&ダンジョン初回クリア時の魔石.
                    if ($this->cm == self::CM_ALL_FRIEND) {
                        // チャレンジモード(全員フレンドチャレンジ).
                        $first_cleared_at = $user_dungeon_floor->cm1_cleared_at;
                    } elseif ($this->cm == self::CM_NOT_USE_HELPER) {
                        // チャレンジモード(助っ人無しチャレンジ).
                        $first_cleared_at = $user_dungeon_floor->cm2_cleared_at;
                    } else {
                        // ノーマルモード.
                        $first_cleared_at = $user_dungeon_floor->cleared_at;
                    }

                    if (empty($first_cleared_at)) {
                        // ダンジョンクリア時の魔法石.
                        // 全てのフロアをクリアした時点で初めてボーナス付与する
                        list($dungeon_floor_cnt, $clear_dungeon_floor_cnt) = $this->countClearDungeonFloors($user, $dungeon, $user_dungeon_floor, $pdo);
                        // 既にクリア済みのユーザのダンジョンフロアの数には今回クリアしたダンジョンフロア分の１を追加.
                        // 数が一致すれば全てのフロアをクリア済みと判断できる.
                        if ($dungeon_floor_cnt == ($clear_dungeon_floor_cnt + 1)) {
                            // #PADC# ----------begin----------
                            // 全フロアクリアの報酬が設定されていなければ付与されないようにする
                            $reward_gold = isset($dungeon->reward_gold) ? $dungeon->reward_gold : 0;
                            if ($reward_gold > 0) {
                                $mb = new MagicStoneBonus($reward_gold);
                                // #PADC# add parameters
                                $user = $mb->apply($user, $pdo, null, $token);
                                $log_data["beat_bonuses"][] = array("award_id" => (int) $mb->item_id, "amount" => (int) $mb->amount);
                            }
                            // #PADC# ----------end----------
                        }
                        // クリアフラグ.
                        if ($this->cm == self::CM_ALL_FRIEND) {
                            // チャレンジモード(全員フレンドチャレンジ).
                            // #PADC# 継承を考慮して、UserDungeonからstaticに変更。
                            $user_dungeon_floor->cm1_cleared_at = static::timeToStr($cleared_at);
                        } elseif ($this->cm == self::CM_NOT_USE_HELPER) {
                            // チャレンジモード(助っ人無しチャレンジ).
                            // #PADC# 継承を考慮して、UserDungeonからstaticに変更。
                            $user_dungeon_floor->cm2_cleared_at = static::timeToStr($cleared_at);
                        } else {
                            // ノーマルモード.
                            // #PADC# 継承を考慮して、UserDungeonからstaticに変更。
                            $user_dungeon_floor->cleared_at = static::timeToStr($cleared_at);
                        }
                        // チャレンジダンジョン報酬付与
                        $this->sendChallengeDungeonBonus($pdo);
                        $user_dungeon_floor->update($pdo);

                        // #PADC# ----------begin----------
                        // ダンジョンクリア数をセット
                        $clear_dungeon_cnt = $user->clear_dungeon_cnt;
                        // 単純に+1せずクリアしたダンジョン数をカウントします
                        $user->clear_dungeon_cnt = self::getClearCountDungeon($user->id, $pdo);

                        // ダンジョンクリア数が変化した
                        if ($clear_dungeon_cnt != $user->clear_dungeon_cnt) {
                            // #PADC_DY# ----------begin----------
                            /* levelUp check happend after levelUp, not here, clear_dungeon_cnt added
                            // LevelUpテーブルを参照
                            $nextLevel = LevelUp::get($user->clear_dungeon_cnt);
                            if ($nextLevel) {
                                $user->applyBonus($nextLevel->bonus_id, $nextLevel->amount, $pdo, null, null);
                            }
                            */
                            // #PADC_DY# ----------end----------

                            // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonFloorを動的クラスに変更。
                            $clear_dungeon_floors = $user_dungeon_floor_class::getCleared($this->user_id, $pdo);
                            $clear_dungeon_floor_ids = array();
                            foreach ($clear_dungeon_floors as $cdf) {
                                $clear_dungeon_floor_ids[] = $cdf->dungeon_floor_id;
                            }

                            // #PADC_DY# ----------begin----------
                            // below not use, comment out
                            // ダンジョンクリア数の条件を満たしたダンジョンフロアを取得
                            // #PADC# 継承先でアクセスするDBを変更できるように、DungeonFloorを動的クラスに変更。
                            // $next_dungeon_floors_2 = $dungeon_floor_class::getNextFloorsByParams($user->clear_dungeon_cnt, $clear_dungeon_floor_ids);
                            // #PADC_DY# ----------end----------

                            // set last_clear_normal_dungeon_id for IDIP
                            if ($dungeon->dtype == Dungeon::DUNG_TYPE_NORMAL) {
                                $user->last_clear_normal_dungeon_id = $this->dungeon_id;
                            }
                        }

                        //set last_clear_sp_dungeon_id for IDIP
                        if ($dungeon->dtype != Dungeon::DUNG_TYPE_NORMAL) {
                            $user->last_clear_sp_dungeon_id = $this->dungeon_id;
                        }
                        // #PADC# ----------end----------
                    }

                    // #PADC# ----------begin----------
                    $daily_cleared_at = $user_dungeon_floor->daily_cleared_at;
                    if (empty($daily_cleared_at) || !static::isSameDay_AM4(static::strToTime($daily_cleared_at), $cleared_at)) {
                        // デイリーダンジョンの場合、1日1回だけ報酬が付与される
                        if ($dungeon->isDailyDungeon()) {
                            // デイリーダンジョン報酬付与
                            $this->sendDailyDungeonBonus($cleared_at, $pdo);
                        }
                        // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonを動的クラスに変更。
                        $user_dungeon_floor->daily_cleared_at = $user_dungeon_floor_class::timeToStr($cleared_at);
                        $user_dungeon_floor->update($pdo);
                    }

                    // #PADC# ----------begin----------
                    // フロアクリア回数をカウント
                    $user_count = UserCount::getByUserId($this->user_id, $cleared_at, $pdo);
                    if ($dungeon->isNormalDungeon()) {
                        $user_count->addCount(UserCount::TYPE_CLEAR_NORMAL);
                        $user_count->addCount(UserCount::TYPE_DAILY_CLEAR_NORMAL);
                    } else if ($dungeon->isEventDungeon()) {
                        $user_count->addCount(UserCount::TYPE_CLEAR_SPECIAL);
                        $user_count->addCount(UserCount::TYPE_DAILY_CLEAR_SPECIAL);
                    }
                    $user_count->update($pdo);
                    // #PADC# ----------end----------
                }

                // 取得分を計算.
                $added_exp = (int) $user_dungeon->exp;
                $log_data["after_coin"] = (int) $user->coin;
                $added_coin = $user->coin - $log_data["before_coin"];
                $log_data["after_gold"] = (int) $user->gold;
                $log_data["after_pgold"] = (int) $user->pgold;
                if ($this->cm != self::CM_NORMAL) {
                    $log_data["cm"] = (int) $this->cm;
                }
                $added_gold = ($user->gold + $user->pgold) - ($log_data["before_gold"] + $log_data["before_pgold"]);
                $added_round = $user->round - $log_data["before_round"];
                $log_data["score"] = (int) $score;
                // 石を使わずコンティニューのチート判別.
                $client_continue_cnt = floor($score / 10) % 10; // スコアの2桁目
                $server_continue_cnt = (int) $user_dungeon->continue_cnt;
                if ($server_continue_cnt >= 10) {
                    $server_continue_cnt = 9;
                }
                if ($server_continue_cnt != $client_continue_cnt) {
                    $log_data["continue_cheat"] = 1;
                }

                if ($round) {
                    // 周回ボーナス
                    if ($round_bonus) {
                        if ($round_bonus->item_id <= BaseBonus::MAX_CARD_ID) {
                            // INFO:PADC版では直接モンスター付与はされないので無視する
                        } else if ($round_bonus->item_id == BaseBonus::PIECE_ID) {
                            // INFO:カケラ付与
                            $piece_id = $round_bonus->piece_id;
                            $piece_num = $round_bonus->amount;

                            // 同じIDのカケラが複数あっても大丈夫なように対応
                            if (array_key_exists($piece_id, $add_pieces)) {
                                $add_card = $add_pieces[$piece_id]->addPiece($piece_num, $pdo);
                                if ($add_card) {
                                    $add_cards[] = $add_card;
                                }
                            } else {
                                $add_result = UserPiece::addUserPieceToUserReserve(
                                                $user->id, $piece_id, $piece_num, $pdo
                                );
                                $add_pieces[$piece_id] = $add_result['piece'];
                                if (array_key_exists('card', $add_result)) {
                                    $add_cards[] = $add_result['card'];
                                }
                            }
                        } else {
                            $b = new BeatBonus();
                            $b->item_id = $round_bonus->item_id;
                            $b->amount = $round_bonus->amount;
                            // #PADC# QQ会員ボーナス適用
                            if ($b->item_id == BaseBonus::COIN_ID) {
                                if ($qq_coin_bonus > 0) {
                                    $b->amount = ceil($b->amount * (10000 + $qq_coin_bonus) / 10000);
                                }
                                if ($game_center_coin_bonus > 0) {
                                    $b->amount = ceil($b->amount * (10000 + $game_center_coin_bonus) / 10000);
                                }
                            }
                            // #PADC# add parameters
                            $user = $b->apply($user, $pdo, null, null);

                            $log_data["round_bonuses"][] = array("award_id" => (int) $b->item_id, "amount" => (int) $b->amount);
                        }
                    }
                }

                // 欠片、モンスターの付与
                list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

                foreach ($get_cards as $card) {
                    $equip = array((int) $card->equip1, (int) $card->equip2, (int) $card->equip3, (int) $card->equip4);
                    $log_data["get_cards"][] = array("card_id" => (int) $card->card_id, "cuid" => (int) $card->cuid, "level" => (int) $card->lv, "equip" => $equip);
                }
                foreach ($result_pieces as $piece) {
                    $log_data["result_pieces"][] = array("piece_id" => (int) $piece->piece_id, "num" => (int) $piece->num, "create_card" => (int) $piece->create_card);
                }
                $result_pieces = UserPiece::arrangeColumns($result_pieces);

                // 図鑑登録数の更新
                $user_book = UserBook::getByUserId($this->user_id, $pdo);
                $user->book_cnt = $user_book->getCountIds();

                // チート検知したらランキングのスコア登録はしない
                if (!$cheat_error) {
                    // MY : 共通化させるためランキングにエントリーする処理を呼び出す。ただし、通常ダンジョンでは固定のデータを返す。
                    list($entry_ranking, $ranking_score, $user_ranking_number) = static::entryRanking($user, $ranking_id, $decode_params, static::timeToStr($cleared_at), $cards, $dungeon_floor, $total_power, $pdo);
                }

                // デッキモンスターに経験値付与
                // 処理が被るのでログ出力と併用して行う
                // カレントデッキ情報更新
                $decks = array();
                $card_cnt = 0;
                foreach ($deck_ids as $cuid) {
                    $card_cnt++;
                    if ($cuid > 0) {
                        $card = $cuid_cards[$cuid];
                        $decks["card_id" . $card_cnt] = (int) $card->card_id;
                        $decks["card_lv" . $card_cnt] = (int) $card->lv;

                        $before_lv = (int) $card->lv;
                        // チート検知したら経験値付与はしない
                        if (!$cheat_error) {
                            $base_card = $card->getMaster();
                            $card->exp = min($card->exp + $user_dungeon->exp, $base_card->pexpa);
                            $card->setLevelOnExp();
                            $card->update($pdo);
                        }

                        // レベルが上がった場合にリーダーカードの更新処理を行う
                        if ($before_lv != $card->lv) {
                            // リーダーカードのみ更新処理
                            if ($card_cnt == 1) {
                                $lc_data = $card->setLeaderCard($user);
                                $user->lc = join(",", $lc_data);
                            }

                            // デッキデータの更新処理
                            $ldeck = $card->setLeaderDeckCard($user);
                            $user->ldeck = json_encode($ldeck);
                        }

                        $deck_cards[] = GetUserCards::arrangeColumn($card);
                    }
                }

                // 直近でクリアしたダンジョンIDを入力
                $user->last_clear_dungeon_id = $this->dungeon_id;

                // スタミナと周回チケットを消費
                if ($round) {
                    $user->useStamina($user_dungeon->spent_stamina);
                    $user->addRound(-$dungeon_floor->rticket);
                }

                // #PADC# ----------end----------

                $user->accessed_at = User::timeToStr(time());
                $user->accessed_on = $user->accessed_at;

                // #PADC_DY# ----------begin----------
                //	resume user's level up
                $lv_up = $user->isExpReachedNextLevel();
                if ($lv_up) {
                    $before_user_lv = $user->lv;
                    $curLevel = LevelUp::get($user->lv);
                    $nextLevel = LevelUp::get($user->lv + 1);
                    while($user->levelUp($nextLevel)){
                        $curLevel = $nextLevel;
                        $nextLevel = LevelUp::get($user->lv + 1);
                    }

                    //set last level up time and level up cost
                    $current_time = time();
                    if(empty($user->last_lv_up) || $user->last_lv_up == '0000-00-00 00:00:00'){
                        $level_up_time_cost = 0;
                        $user->last_lv_up = User::timeToStr($current_time);
                    }else {
                        $level_up_time_cost = $current_time - strtotime($user->last_lv_up);
                        //update last_lv_up to last time
                        $user->last_lv_up = User::timeToStr($current_time);
					}

					// #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonFloorを動的クラスに変更。
                    $clear_dungeon_floors = $user_dungeon_floor_class::getCleared($this->user_id, $pdo);
                    $clear_dungeon_floor_ids = array();
                    foreach ($clear_dungeon_floors as $cdf) {
						$clear_dungeon_floor_ids[] = $cdf->dungeon_floor_id;
                    }

                    // #PADC_DY# ----------begin----------
                    // 获取升级后新开放的Dungeon floor
                    // #PADC# 継承先でアクセスするDBを変更できるように、DungeonFloorを動的クラスに変更。
					$next_dungeon_floors_2 = $dungeon_floor_class::getNextFloorsByParams($before_user_lv, $user->lv, $clear_dungeon_floor_ids);
                    $next_dungeon_floors = array_merge($next_dungeon_floors, $next_dungeon_floors_2);
                    // #PADC_DY# ----------end----------

                }
                // #PADC_DY# ----------end----------

                $user->update($pdo);

                // 次フロアの開放.
                foreach ($next_dungeon_floors as $next_floor) {
                    // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonFloorを動的クラスに変更。
                    $user_dungeon_floor_class::enable($user_dungeon->user_id, $next_floor->dungeon_id, $next_floor->id, $pdo);
                }

                // UserDungeonを更新.
                // クリアボーナス適用済みとする.
                // 再度計算しなくて済むよう、
                // 適用されたボーナス値でUserDungeonの各カラムを更新しておき、
                // 次回、値だけを取得するときはこちらを参照する.
                // #PADC# 継承を考慮して、UserDungeonをstaticに変更。
                $user_dungeon->cleared_at = static::timeToStr($cleared_at);
                $user_dungeon->coin = $added_coin;
                $user_dungeon->gold = $added_gold;
                $user_dungeon->lvup = $lv_up;

                // #PADC_DY# 回合数
                $turns = (isset($decode_params["turns"]) ? (int) $decode_params["turns"] : 9999);

                // #PADC_DY# 接关数
                $continue_cnt = (int) $user_dungeon->continue_cnt;

                // #PADC_DY# ----------begin----------
                $max_star = 1;
                if ($continue_cnt <= 0 && $turns <= $dungeon_floor->star3_required_turn) {
                    $max_star = 3;
                } elseif ($continue_cnt <= 0 || $turns <= $dungeon_floor->star3_required_turn) {
                    $max_star = 2;
                }

                // 非普通关卡直接返回三星
                if ($max_star_flag) {
                    $max_star = self::DUNGEON_FLOOR_MAX_STAR;
                }

                if($max_star > $user_dungeon_floor->max_star) {
                    $user_dungeon_floor->max_star = $max_star;
                    $user_dungeon_floor->update($pdo);
                }

                // #PADC_DY# -----------end-----------

                // #PADC# ----------begin----------
                // 入手した欠片などPADCで追加された情報を clear_response に記録する
                $user_dungeon->clear_response = json_encode(array(
                    'get_pieces' => $get_pieces,
                    'result_pieces' => $result_pieces,
                    'deck_cards' => $deck_cards,
                    'get_cards' => $get_cards,
                    'cheat_error' => $cheat_error,
                    'cheat_mes' => $cheat_error_mes,
                    'roundgain' => $added_round,
                    'ban_msg' => $ban_msg,
                    'ban_end' => $ban_end,
                    'qq_vip' => $qq_vip,
                    'qq_coin_bonus' => $qq_coin_bonus,
                    'qq_exp_bonus' => $qq_exp_bonus,
                    'game_center' => $game_center,
                    // #PADC_DY# ----------begin----------
                    'max_star' => isset($max_star) ? $max_star : 0, // #PADC_DY# 获得三星数
                    // #PADC_DY# ----------end----------
                ));
                // #PADC# ----------end----------
                $user_dungeon->update($pdo);

                // クリア結果をログに出力
                if (!is_null($user_dungeon->helper_card_id)) {
                    $decks["helper_id"] = (int) $user_dungeon->helper_card_id;
                    $decks["helper_lv"] = (int) $user_dungeon->helper_card_lv;
                }
                // 負荷対策のためクリアログについてDBへのINSERTをログ出力へ変更。実行位置もcommit直後へ移動(2012/5/21)
                // UserLogClearDungeon::log($user->id, $this->dungeon_floor_id, $user_dungeon->created_at, $cleared_time, $decks, $log_data, $pdo);
                $pdo->commit();

                // 二重アクセス防止のキャッシュ削除
                // #PADC# memcache→redis
                $redis = Env::getRedisForUser();
                // #PADC# 継承先でアクセスするキャッシュを変更するため、可変関数に変更。
                $key = static::getCacheKeyDungeon($user, $user_dungeon, $dungeon_floor, $ranking_id);
                $redis->delete($key);
                UserLogClearDungeon::log($user->id, $this->dungeon_floor_id, $user_dungeon->created_at, $cleared_time, $decks, $log_data, $user_dungeon->continue_cnt, $nxc);

                // 管理画面での表示のため魔宝石増加をCSV形式で残す(2012/6/14)
                if ($added_gold > 0) {
                    UserLogAddGold::log($user->id, UserLogAddGold::TYPE_DUNGEON, $log_data["before_gold"], $user->gold, $log_data["before_pgold"], $user->pgold, $user->device_type);

                    // #PADC# Tlog
                    UserTlog::sendTlogMoneyFlow($user, $user->gold + $user->pgold - $log_data["before_gold"] - $log_data["before_pgold"], Tencent_Tlog::REASON_DUNGEON, Tencent_Tlog::MONEY_TYPE_DIAMOND, $user->gold - $log_data["before_gold"], 0, 0, 0, $added_round);

                    // #PADC#
                    $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
                }
                // #PADC# ----------begin----------
                // Tlog money
                if ($added_coin > 0) {
                    UserTlog::sendTlogMoneyFlow($user, $added_coin, Tencent_Tlog::REASON_DUNGEON, Tencent_Tlog::MONEY_TYPE_MONEY, 0, 0, 0, 0, $added_round);
                }
                // Tlog piece
                if (!empty($get_pieces)) {
                    foreach ($get_pieces as $get_piece) {
                        $piece_id = $get_piece[0];
                        $piece_get = $get_piece[1];
                        foreach ($result_pieces as $result_piece) {
                            if ($piece_id == $result_piece[0]) {
                                $piece_num = $result_piece[1];
                                UserTlog::sendTlogItemFlow($user->id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $piece_get, $piece_num, Tencent_Tlog::ITEM_REASON_DUNGEON, 0, 0, 0, 0, $added_round);
                            }
                        }
                    }
                }
                // Tlog card
                foreach ($get_cards as $get_card) {
                    UserTlog::sendTlogItemFlow($user->id, Tencent_Tlog::GOOD_TYPE_CARD, $get_card->card_id, 1, 1, Tencent_Tlog::ITEM_REASON_DUNGEON, 0, 0, 0, 0, $added_round);
                }
                // Tlog round
                // #PADC# 継承先でアクセスするDBを変更できるように、Dungeonを動的クラスに変更。
                $dungeon = $dungeon_class::get($this->dungeon_id);
                if (isset($user_dungeon->stamina_spent_at) && $user_dungeon->stamina_spent_at > 0) {
                    $dungeon_time = strtotime($user_dungeon->cleared_at) - strtotime($user_dungeon->stamina_spent_at);
                } else {
                    $dungeon_time = 0;
                }

                // Tlog send PlayerExpFlow
                UserTlog::sendTlogPlayerExpFlow($user, $before_dungeon_cnt, $level_up_time_cost, null, Tencent_Tlog::LEVEL_REASON_DUNGEON);
                // if ($before_dungeon_cnt != $user->clear_dungeon_cnt) {

                // #PADC_DY# ----------begin----------
                // send tlog's condition changed, modified
                //Tlog send PlayerExpFlow
                UserTlog::sendTlogPlayerExpFlow($user, $before_user_lv, $level_up_time_cost,null,Tencent_Tlog::LEVEL_REASON_DUNGEON);
                if($lv_up){
                // #PADC_DY# ----------end----------
                    $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_LEVEL, $token);
                }

                $sneakTime = $user_dungeon->stamina_spent_at;
                $securitySDK = isset($decode_params['sdkres']) ? $decode_params['sdkres'] : null;
                $maxComboNum = isset($decode_params['mcn']) ? $decode_params['mcn'] : 0;
				$aveComboNum = isset($decode_params['acn']) ? number_format($decode_params['acn'], 2) : 0;
				if ($dungeon->dkind == Dungeon::DUNG_KIND_COLLABO) {
					UserTlog::sendTlogRoundFlow($user->id, $this->dungeon_floor_id, $dungeon->dkind, isset($score) ? $score : 0, $dungeon_time, Tencent_Tlog::BATTLE_RESULT_SUCCESS, 0, $added_coin, $cheat_error ? 1 : 0, $securitySDK, $sneakTime, $maxComboNum, $aveComboNum, $ranking_id, $added_round, $added_gold, $max_star);

					// 为了上报手Q数据更新dungeon的挑战次数计数，目前只对IP关卡做统计
					$cnt = UserCount::incrUserDungeonDailyChallengeCount($user->id, $this->dungeon_floor_id, $user_dungeon->cleared_at);

                    if ($dungeon_floor->seq == 1) {
                        $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_IP_EASY, $token, $cnt);
                    }else if ($dungeon_floor->seq == 2) {
                        $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_IP_NORMAL, $token, $cnt);
                    } else if ($dungeon_floor->seq == 3){
                        $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_IP_HARD, $token, $cnt);
                    }else if ($dungeon_floor->seq == 4) {
                        //地狱级
                        $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_IP_HELL, $token, $cnt);
                    }else if ($dungeon_floor->seq == 5) {
                        //超级地狱级
                        $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_IP_SUPERHELL, $token, $cnt);
                    }
				} else {
					UserTlog::sendTlogRoundFlow($user->id, $this->dungeon_floor_id, $dungeon->dtype, isset($score) ? $score : 0, $dungeon_time, Tencent_Tlog::BATTLE_RESULT_SUCCESS, 0, $added_coin, $cheat_error ? 1 : 0, $securitySDK, $sneakTime, $maxComboNum, $aveComboNum, $ranking_id, $added_round, $added_gold, $max_star);
				}

                if(isset($decode_params['end_flow'])){
                    $end_flow_params = json_decode($decode_params['end_flow'],true);
                    if(empty($end_flow_params['waves'])){
                        $waves_count_detail =
                            array(
                                'rps'   => 0,
                                'rset'  => null,
                                'rsost' => null,
                                'rsoet' => null,
                                'rbe'   => null,
                                'rab'   => null,
                                'rstc'  => null,
                                'rtct'  => 0,
                                'rac'   => 0,
                                'rgs'   => 0,
                                'rcpmax'=> 0,
                                'rcpmin'=> 0,
                            );
                    }else{
                        $round_pass_stage    = count($end_flow_params['waves']);   //本局通过的波数
                        $round_average_combo = $end_flow_params['avgcmb'];         //平均combo
                        $round_combo_percent_max = $end_flow_params['cmbenmax'];   //combo的最大系数
                        $round_combo_percent_min = $end_flow_params['cmbenmin'];   //combo的最小系数
                        $round_get_score     = $end_flow_params['score'];          //本局获得的分数
                        $round_turn_count_total  = $end_flow_params['turns'];      //本局累计转珠次数

                        $t_waves    = $end_flow_params['waves'];
                        $round_stage_enemy_time     = '';   //每波敌人出现的时间
                        $round_operation_start_time = '';   //每波首次转珠的时间
                        $round_operation_end_time   = '';   //每次最后转珠的时间
                        $round_stage_turn_count     = '';   //每波的转珠次数
                        $details = array();
                        foreach($t_waves as $t_wave){
                            $round_stage_turn_count .= count($t_wave['detail']).',';

                            $enemy_time = $t_wave['monsin'];
                            $round_stage_enemy_time .= $enemy_time.',';

                            $turn_begin = $t_wave['turn0'];
                            $round_operation_start_time .= $turn_begin.',';

                            $turn_end   = $t_wave['turnz'];
                            $round_operation_end_time .= $turn_end.',';

                            foreach($t_wave['detail'] as $detail){
                                $details[] = $detail;
                            }
                        }

                        $round_beat_exchange = '';   //每次转珠，珠子交换的个数
                        $round_attack_base   = '';   //每次转珠的基础伤害，不算加成
                        foreach($details as $detail){
                            $round_beat_exchange .= $detail['len'].',';
                            $round_attack_base   .= $detail['dmg'].',';
                        }

                        $waves_count_detail =
                            array(
                                'rps'   => $round_pass_stage,
                                'rset'  => trim($round_stage_enemy_time,','),
                                'rsost' => trim($round_operation_start_time,','),
                                'rsoet' => trim($round_operation_end_time,','),
                                'rbe'   => trim($round_beat_exchange,','),
                                'rab'   => trim($round_attack_base,','),
                                'rstc'  => trim($round_stage_turn_count,','),
                                'rtct'  => $round_turn_count_total,
                                'rac'   => $round_average_combo,
                                'rgs'   => $round_get_score,
                                'rcpmax'=> $round_combo_percent_max,
                                'rcpmin'=> $round_combo_percent_min,
                            );
                    }
                }else{
                    $waves_count_detail =
                        array(
                            'rps'   => 0,
                            'rset'  => null,
                            'rsost' => null,
                            'rsoet' => null,
                            'rbe'   => null,
                            'rab'   => null,
                            'rstc'  => null,
                            'rtct'  => 0,
                            'rac'   => 0,
                            'rgs'   => 0,
                            'rcpmax'=> 0,
                            'rcpmin'=> 0,
                        );
                }

                UserTlog::sendTlogSecRoundEndFlow($user, $dungeon, $user_dungeon, $decode_params, $before_user, $client_ver,$waves_count_detail);
                // #PADC# ----------end----------
            } else {
                // 既にボーナス適用済みなので、値のみ取得する.
                $lv_up = (bool) $user_dungeon->lvup;
                $added_exp = (int) $user_dungeon->exp;
                $added_coin = (int) $user_dungeon->coin;
                $added_gold = (int) $user_dungeon->gold;
                // #PADC# ----------begin----------
                $clear_response_array = json_decode($user_dungeon->clear_response);
                $get_pieces = $clear_response_array->get_pieces;
                $result_pieces = $clear_response_array->result_pieces;
                $deck_cards = $clear_response_array->deck_cards;
                $get_cards = $clear_response_array->get_cards;
                $cheat_error = $clear_response_array->cheat_error;
                $cheat_error_mes = $clear_response_array->cheat_mes;
                $added_round = $clear_response_array->roundgain;
                $ban_msg = $clear_response_array->ban_msg;
                $ban_end = $clear_response_array->ban_end;
                $qq_vip = $clear_response_array->qq_vip;
                $qq_coin_bonus = $clear_response_array->qq_coin_bonus;
                $qq_exp_bonus = $clear_response_array->qq_exp_bonus;
                $game_center = $clear_response_array->game_center;
                // #PADC# ----------end----------
                // #PADC_DY# ----------begin----------
                $max_star = $clear_response_array->max_star;
                // #PADC_DY# ----------end----------
                $pdo->rollback();
                if (!$cheat_error) {
                    // MY : 共通化させるためランキング情報を取得する処理を呼び出す。ただし、通常ダンジョンでは固定のデータを返す。
                    list($entry_ranking, $ranking_score, $user_ranking_number) = static::getRankingScore($ranking_id, $score_values, $user_dungeon, $user_dungeon->cleared_at, $cards, $dungeon_floor);
                }
            }
            // #PADC# PDOException → Exception
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            throw $e;
        }
        if (!isset($user)) {
            $user = User::find($user_dungeon->user_id, $pdo);
        }

        $res = array(
            'lv_up' => $lv_up,
            'next_dungeon_floors' => $next_dungeon_floors,
            'expgain' => $added_exp,
            'coingain' => $added_coin,
            'goldgain' => $added_gold,
            'exp' => (int) $user->exp,
            'coin' => (int) $user->coin,
            'gold' => ($user->gold + $user->pgold),
            'get_cards' => $get_cards,
            'src' => $src,
            'before_card_count' => $before_card_count,
            // #PADC# ----------begin----------
            'get_pieces' => $get_pieces,
            'result_pieces' => $result_pieces,
            'deck_cards' => $deck_cards,
            'clr_dcnt' => $user->clear_dungeon_cnt,
            'cheat' => $cheat_error_mes,
            'roundgain' => $added_round,
            'ban_msg' => $ban_msg,
            'ban_end' => $ban_end,
            'qq_vip' => $qq_vip,
            'qq_coin_bonus' => $qq_coin_bonus,
            'qq_exp_bonus' => $qq_exp_bonus,
            'game_center' => $game_center,
            // #PADC# ----------end----------
            'max_star' => isset($max_star) ? $max_star : 0, // #PADC_DY# 获得三星数
            'continue_cnt' => (int) $user_dungeon->continue_cnt, // #PADC_DY# 是否接关
            'star3_required_turn' => (int) $dungeon_floor->star3_required_turn, // #PADC_DY# 获得三星所需回合数
        );
        if ($round) {
            $res['round_bonus'] = $round_bonus;
        }
        
        if ($entry_ranking) {
            $res = array_merge($res, array('ranking' => $user_ranking_number));
            $res = array_merge($res, array('score' => $ranking_score));
            $res['ranking_entry'] = $entry_ranking;
        }

        return array($user, (object) $res);
    }

    /**
     * 	チャレンジダンジョンボーナス付与処理.
     */
    function sendChallengeDungeonBonus(PDO $pdo) {
        $challenge_dungeon_bonus_list = ChallengeDungeonBonus::getAll();
        if (!$challenge_dungeon_bonus_list) {
            return;
        }
        $bonus_type = UserMail::TYPE_ADMIN_BONUS_TO_ALL_NORMAL;
        foreach ($challenge_dungeon_bonus_list as $obj) {
            $dungeon_floor_id = sprintf("%d%03d", $obj->dungeon_id, $obj->seq);
            if ($this->dungeon_floor_id == $dungeon_floor_id && time() < strtotime($obj->finish_at)) {
                $data = array();
                $user_id = $this->user_id;
                $message = $obj->message;
                $bonus_id = (int) $obj->bonus_id;
                $amount = (int) $obj->amount;
                if ($bonus_id <= BaseBonus::MAX_CARD_ID) {
                    $data['ph'] = (int) $obj->plus_hp;
                    $data['pa'] = (int) $obj->plus_atk;
                    $data['pr'] = (int) $obj->plus_rec;
                }
                // #PADC# ----------begin----------
                $piece_id = (int) $obj->piece_id;
                UserMail::sendAdminMailMessage(
                        $user_id, $bonus_type, $bonus_id, $amount, $pdo, $message, $data, $piece_id
                );
                // #PADC# ----------end----------
            }
        }
    }

    //クリア済みダンジョンフロア数をノマダン・テクダン別にカウントして返す.
    private function countClearDungeonFloors($user, $dungeon, $user_dungeon_floor, $pdo = null) {
        $dungeon_floors = $dungeon->getFloors();
        // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonFloorを動的クラスに変更。
        $user_dungeon_floor_class = static::USER_DUNGEON_FLOOR_CLASS;
        $user_dungeon_floors = $user_dungeon_floor_class::findCleared($user->id, $dungeon->id, $this->cm, $pdo);
        // #3236 スペダンをノーマルとテクダンに看板を分けどちらかをクリアすると魔法石をあげる. 2013-11-29
        // ダンジョンごとのノマダン・テクダン分類.
        $dungeon_floor_mode = array();
        foreach ($dungeon_floors as $df) {
            if (($df->ext & 0x080) == 0x080) { // extの8ビット目でノマダン・テクダンを判別.
                $mode = 1; // テクダン.
            } else {
                $mode = 0; // ノマダン.
            }
            $dungeon_floor_mode[($dungeon->id * 1000) + $df->seq] = $mode;
        }
        // 今回クリアしたダンジョンのノマダン・テクダン判別.
        foreach ($dungeon_floors as $df) {
            if ((($dungeon->id * 1000) + $df->seq) == $user_dungeon_floor->dungeon_floor_id) {
                $clear_dungeon_mode = $dungeon_floor_mode[$user_dungeon_floor->dungeon_floor_id];
                break;
            }
        }
        // テクダン・ノマダンどちらかのダンジョン内のフロア数.
        $dungeon_floor_cnt = 0;
        foreach ($dungeon_floors as $df) {
            if ($dungeon_floor_mode[($dungeon->id * 1000) + $df->seq] == $clear_dungeon_mode) {
                $dungeon_floor_cnt++;
            }
        }
        // テクダン・ノマダンどちらかのクリア済みフロア数.
        $clear_dungeon_floor_cnt = 0;
        foreach ($user_dungeon_floors as $udf) {
            if ($dungeon_floor_mode[$udf->dungeon_floor_id] == $clear_dungeon_mode) {
                $clear_dungeon_floor_cnt++;
            }
        }
        return array($dungeon_floor_cnt, $clear_dungeon_floor_cnt);
    }

    /**
     * ユーザが指定のダンジョン, フロアに潜入可能である場合に限り既存または新規作成したUserDungeonFloorを返す.
     * 潜入不可能であればnullを返す.
     */
    public static function checkSneakable($user, $dungeon, $dungeon_floor, $active_bonuses, $cm = null, $pdo = null) {
        $user_dungeon_floor = null;
        if ($dungeon_floor->checkStamina($user, $active_bonuses)) {
            $params = array(
                "user_id" => $user->id,
                "dungeon_id" => $dungeon->id,
                "dungeon_floor_id" => $dungeon_floor->id
            );
            if ($dungeon->isNormalDungeon()) {
                // ノーマルダンジョンなので、既存のUserDungeonFloorを取得.
                $user_dungeon_floor = UserDungeonFloor::findBy($params, $pdo);
                if (!$user_dungeon_floor) {
                    // 解放ダンジョンフロアを再チェック(開放済みのデータを全取得).
                    $user_dungeon_floors = UserDungeonFloor::getOpenFloor($user, $pdo);
                    foreach ($user_dungeon_floors as $udf) {
                        if ($udf->dungeon_floor_id == $dungeon_floor->id) {
                            $user_dungeon_floor = $udf;
                            break;
                        }
                    }
                }
            } else if ($dungeon->isLegendDungeon()) {
                // 不思議のダンジョン、とりあえず無条件で解放.
                $user_dungeon_floor = UserDungeonFloor::findBy($params, $pdo);
                if (empty($user_dungeon_floor)) {
                    $user_dungeon_floor = UserDungeonFloor::enable($user->id, $dungeon->id, $dungeon_floor->id, $pdo);
                }
            } else if ($dungeon->isEventDungeon()) {
                // イベントダンジョンなので、開放状態を確認し、
                // 既存のUserDungeonFloorがなければ新規作成.
                // イベントダンジョンは途中フロアであっても(前のフロアをクリアしていなくても)
                // 潜入可能なので、フロア番号などのチェックもしない. (#123)
                // 購入したダンジョンのチェックも追加. (#4739)
                if ($dungeon->isOpened($active_bonuses) || UserBuyDungeon::check($user->id, $dungeon->id, $pdo)) {
                    $user_dungeon_floor = UserDungeonFloor::findBy($params, $pdo);
                    if (empty($user_dungeon_floor)) {
                        $user_dungeon_floor = UserDungeonFloor::enable($user->id, $dungeon->id, $dungeon_floor->id, $pdo);
                    }
                }
            }
            // 一度きりのダンジョンの潜入チェックをサーバー側でも行う#2086.
            if ($user_dungeon_floor && self::isSameDay_AM4(time(),BaseModel::strToTime($user_dungeon_floor->daily_cleared_at))) {
                if ($dungeon->attr & 0x01 == 1) {
                    // クリア後選択不可フラグが1で、かつクリア済みの場合は潜入不可.
                    $user_dungeon_floor = null;
                }
            }
            // 1:全員フレンドチャレンジ 2:助っ人無しチャレンジ の時はテクダンの深淵の魔王城(206005)クリアもチェックする.#2038
            if (in_array($cm, array(1, 2))) {
                $params = array(
                    "user_id" => $user->id,
                    "dungeon_id" => 206,
                    "dungeon_floor_id" => 206005
                );
                $challenge_ok_dung = UserDungeonFloor::findBy($params, $pdo);
                if (!$challenge_ok_dung) {
                    // 潜入不可.
                    $user_dungeon_floor = null;
                }
            }
        }
        return $user_dungeon_floor;
    }

    /**
     * ハッシュ値を生成し、セットする. セットしたハッシュ値を返す.
     */
    public function setHash() {
        $h = uniqid();
        $this->hash = $h;
        return $h;
    }

    /**
     * 潜入中のダンジョンでの行ったコンテニュー回数を記録.
     */
    public static function spendContinue($user_id, $pdo) {
        $sql = "UPDATE " . static::TABLE_NAME . " SET continue_cnt = continue_cnt + 1 WHERE user_id = ? ";
        $bind_param = array($user_id);
        self::prepare_execute($sql, $bind_param, $pdo);
    }

    /**
     * #PADC#
     * 今までにクリアしたダンジョン数を返す.
     * PADCのプレイヤーレベルに相当する
     */
    public static function getClearCountDungeon($user_id, $pdo) {

        // 全ダンジョンデータ
        $all_dungeons = Dungeon::getAll();
        $dungeon_ids = array();
        foreach ($all_dungeons as $d) {
            // ランクアップ可能なダンジョンIDだけを抽出する
            if ($d->rankup_flag > 0) {
                $dungeon_ids[] = $d->id;
            }
        }

        // 全ダンジョンのフロア数をカウント
        $dungeon_floors = DungeonFloor::getAll();
        $dungeon_floor_counts = array();
        foreach ($dungeon_floors as $df) {
            $key = $df->dungeon_id;
            // ランクアップ可能なダンジョンのフロアだけをカウントする
            if (!in_array($key, $dungeon_ids)) {
                continue;
            }

            if (array_key_exists($key, $dungeon_floor_counts)) {
                $dungeon_floor_counts[$key] += 1;
            } else {
                $dungeon_floor_counts[$key] = 1;
            }
        }

        // ユーザーがクリアしたフロア数をダンジョン毎にカウント
        $user_dungeon_floors = UserDungeonFloor::getCleared($user_id, $pdo);
        $clear_dungeon_floor_count = array();
        foreach ($user_dungeon_floors as $udf) {
            $key = $udf->dungeon_id;
            // ランクアップ可能なダンジョンのフロアだけをカウントする
            if (!in_array($key, $dungeon_ids)) {
                continue;
            }

            if (array_key_exists($key, $clear_dungeon_floor_count)) {
                $clear_dungeon_floor_count[$key] += 1;
            } else {
                $clear_dungeon_floor_count[$key] = 1;
            }
        }

        // フロア数とクリアしたフロア数が一致しているダンジョン数をカウント
        $dungeon_clear_count = 0;
        foreach ($clear_dungeon_floor_count as $dungeon_id => $clear_count) {
            if (array_key_exists($dungeon_id, $dungeon_floor_counts)) {
                if ($clear_count == $dungeon_floor_counts[$dungeon_id]) {
                    $dungeon_clear_count++;
                }
            }
        }

        return $dungeon_clear_count;
    }

    /**
     * #PADC#
     * クリアしたノーマルダンジョン数を返す.
     * セキュリティTlog用
     */
    public static function getClearCountNormalDungeon($user_id, $pdo) {
        // 全ダンジョンデータ
        $all_dungeons = Dungeon::getAll();
        $dungeon_ids = array();
        foreach ($all_dungeons as $d) {
            // ノーマルダンジョンIDだけを抽出する
            if ($d->isNormalDungeon()) {
                $dungeon_ids[] = $d->id;
            }
        }

        // 全ダンジョンのフロア数をカウント
        $dungeon_floors = DungeonFloor::getAll();
        $dungeon_floor_counts = array();
        foreach ($dungeon_floors as $df) {
            $key = $df->dungeon_id;
            // ノーマルダンジョンのフロアだけをカウントする
            if (!in_array($key, $dungeon_ids)) {
                continue;
            }

            if (array_key_exists($key, $dungeon_floor_counts)) {
                $dungeon_floor_counts[$key] += 1;
            } else {
                $dungeon_floor_counts[$key] = 1;
            }
        }

        // ユーザーがクリアしたフロア数をダンジョン毎にカウント
        $user_dungeon_floors = UserDungeonFloor::getCleared($user_id, $pdo);
        $clear_dungeon_floor_count = array();
        foreach ($user_dungeon_floors as $udf) {
            $key = $udf->dungeon_id;
            // ノーマルダンジョンのフロアだけをカウントする
            if (!in_array($key, $dungeon_ids)) {
                continue;
            }

            if (array_key_exists($key, $clear_dungeon_floor_count)) {
                $clear_dungeon_floor_count[$key] += 1;
            } else {
                $clear_dungeon_floor_count[$key] = 1;
            }
        }

        // フロア数とクリアしたフロア数が一致しているダンジョン数をカウント
        $dungeon_clear_count = 0;
        foreach ($clear_dungeon_floor_count as $dungeon_id => $clear_count) {
            if (array_key_exists($dungeon_id, $dungeon_floor_counts)) {
                if ($clear_count == $dungeon_floor_counts[$dungeon_id]) {
                    $dungeon_clear_count++;
                }
            }
        }

        return $dungeon_clear_count;
    }

    /**
     * #PADC#
     * デイリーダンジョンボーナス付与処理.
     */
    function sendDailyDungeonBonus($time, PDO $pdo) {
        $dungeon_id = $this->dungeon_id;
        $seq = $this->dungeon_floor_id % 1000;
        $daily_dungeon_bonus_list = DailyDungeonBonus::findAllBy(array(
                    'dungeon_id' => $dungeon_id,
                    'seq' => $seq
        ));
        if (!$daily_dungeon_bonus_list) {
            return;
        }

        $bonus_type = UserMail::TYPE_ADMIN_BONUS_TO_ALL_NORMAL;
        foreach ($daily_dungeon_bonus_list as $obj) {
            $begin_at = static::strToTime($obj->begin_at);
            $finish_at = static::strToTime($obj->finish_at);
            if (($begin_at <= 0 && $finish_at <= 0) ||
                    ($begin_at <= $time && $time <= $finish_at)) {
                $data = array();
                $user_id = $this->user_id;
                $message = $obj->message;
                $bonus_id = (int) $obj->bonus_id;
                $amount = (int) $obj->amount;
                $piece_id = (int) $obj->piece_id;
                // #PADC_DY# ----------begin----------
                // 加入每日关卡通关奖励邮件的标题
                $title = GameConstant::getParam("DailyDungeonBonusMailTitle");
                UserMail::sendAdminMailMessage(
                        $user_id, $bonus_type, $bonus_id, $amount, $pdo, $message, $data, $piece_id, $title
                );
                // #PADC_DY# -----------end-----------
            }
        }
    }

    // #PADC# ----------begin----------
    protected static function getActiveBonus($user, $dungeon, $dungeon_floor) {
        $active_bonuses = LimitedBonus::getActiveForSneakDungeon($dungeon, $dungeon_floor);

        $active_bonuses_group = LimitedBonusGroup::getActiveForSneakDungeon($user, $dungeon);

        $active_bonuses_open_dungeon = LimitedBonusOpenDungeon::getActiveForSneakDungeon($dungeon);

        $active_bonuses_dungeon_bonus = LimitedBonusDungeonBonus::getActiveForSneakDungeon($dungeon);

        $active_bonuses = array_merge($active_bonuses, $active_bonuses_group);
        $active_bonuses = array_merge($active_bonuses, $active_bonuses_open_dungeon);
        $active_bonuses = array_merge($active_bonuses, $active_bonuses_dungeon_bonus);

        $dungeon_bonus = LimitedBonus::getActiveForDungeonForClient($dungeon, $active_bonuses);
        return array($active_bonuses, $dungeon_bonus);
    }

    protected static function createUserDungeon($user, $dungeon, $dungeon_floor, $cm, $pdo, $ranking_id = 0) {
        // ユーザーのダンジョンデータが存在するかチェック
        // ※他のユーザーと処理が並行するとDeadLockの可能性があるため、FOR UPDATEで取得しない
        $user_dungeon = UserDungeon::findBy(array("user_id" => $user->id), $pdo);
        if (!$user_dungeon) {
            // 存在しなければ先にここでInsert処理が行われるようにする
            $user_dungeon = new UserDungeon();
            $user_dungeon->user_id = $user->id;
            $user_dungeon->create($pdo);
        }

        $user_dungeon = UserDungeon::findBy(array("user_id" => $user->id), $pdo, TRUE);
        if (!$user_dungeon) {
            // 先にデータを作成するように変更したのでここでInsertはしない
            // もしここに遷移してくるようであれば問題が発生しているのでエラーを投げる
            throw new PadException(RespCode::FAILED_SNEAK, "not found user_dungeon user_id:" . $user->id . ". __NO_TRACE");
        }
        $user_dungeon->resetColumns();
        $user_dungeon->user_id = $user->id;
        $user_dungeon->dungeon_id = $dungeon->id;
        $user_dungeon->dungeon_floor_id = $dungeon_floor->id;
        $user_dungeon->cm = $cm;
        return $user_dungeon;
    }

    // ランキング用の関数になるため、通常ダンジョンでは固定データを返す。
    protected static function entryRanking($user, $ranking_id, $score_values, $cleared_at, $cards, $dungeon_floor, $total_power, $pdo) {
        $ret = array(FALSE, NULL, 0);
        return $ret;
    }

    // ランキング用の関数になるため、通常ダンジョンでは固定データを返す。
    protected static function getRankingScore($ranking_id, $score_values, $user_dungeon, $cleared_at, $cards, $dungeon_floor) {
        $ret = array(FALSE, NULL, 0);
        return $ret;
    }

    protected static function getCacheKeyDungeon($user, $user_dungeon, $dungeon_floor, $ranking_id = 0) {
        $key = CacheKey::getSneakDungeon($user->id, $user_dungeon->dungeon_id, $dungeon_floor->seq, $user_dungeon->sneak_time);
        return $key;
    }

    protected static function isRankingDungeon() {
        return false;
    }

    // #PADC# ----------end----------

    /**
     * #PADC#
     * 潜入時のwaveモンスター情報を生成する.
     * チート対策用
     */
    public function setCheckCheatInfo($pdo, $helper_ps_info) {

        $deck_ids = explode(",", UserDeck::findBy(array('user_id' => $this->user_id), $pdo)->toCuidsCS());
        $user_cards = UserCard::findByCuids($this->user_id, $deck_ids, $pdo);
        $cuid_cards = array();
        foreach ($user_cards as $user_card) {
            $cuid_cards[$user_card->cuid] = $user_card;
        }

        $user_deck_params = array();
        foreach ($deck_ids as $cuid) {
            if ($cuid > 0) {
                $user_card = $cuid_cards[$cuid];
                $card = Card::get($user_card->card_id);
                $skill = Skill::get($card->skill);
                $user_card_ps_info = isset($user_card->ps) ? json_decode($user_card->ps, true) : array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
                list($hp_add, $atk_add, $rec_add) = UserCard::getAwakeSkillAdditions($user_card_ps_info, $card);

                $user_deck_params[] = array(
                    (int) $user_card->card_id,
                    Card::getCardParam($user_card->lv, $card->mlv, $card->pmhpa, $card->pmhpb, $card->pmhpc) + $user_card->equip1 * 10 + $hp_add,
                    Card::getCardParam($user_card->lv, $card->mlv, $card->patka, $card->patkb, $card->patkc) + $user_card->equip2 * 5 + $atk_add,
                    Card::getCardParam($user_card->lv, $card->mlv, $card->preca, $card->precb, $card->precc) + $user_card->equip3 * 3 + $rec_add,
                    (int) $card->skill,
                    (int) ($skill->ctbs - ($user_card->slv - 1)),
                );
            } else {
                $user_deck_params[] = array(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                );
            }
        }

        $helper_card_params = array(0, 0, 0, 0, 0, 0, 0);
        if ($this->helper_id != null) {
            $card = Card::get($this->helper_card_id);
            $skill = Skill::get($card->skill);
            list($hp_add, $atk_add, $rec_add) = UserCard::getAwakeSkillAdditions($helper_ps_info, $card);

            $helper_card_params = array(
                (int) $this->helper_card_id,
                Card::getCardParam($this->helper_card_lv, $card->mlv, $card->pmhpa, $card->pmhpb, $card->pmhpc) + $this->helper_card_plus[0] * 10 + $hp_add,
                Card::getCardParam($this->helper_card_lv, $card->mlv, $card->patka, $card->patkb, $card->patkc) + $this->helper_card_plus[1] * 5 + $atk_add,
                Card::getCardParam($this->helper_card_lv, $card->mlv, $card->preca, $card->precb, $card->precc) + $this->helper_card_plus[2] * 3 + $rec_add,
                (int) $card->skill,
                (int) ($skill->ctbs - ($this->helper_skill_lv - 1)),
                (int) $this->helper_id,
            );
        }

        $wave_mons_indexs = array();
        $wave_mons_ids = array();
        $wave_mons_params = array();

        // 出現するモンスターの並びをシャッフル
        foreach ($this->user_waves as $user_wave) {
            $shuffle_flg = true;
            foreach ($user_wave->user_wave_monsters as $user_wave_monster) {
                // モンスター種類
                if ($user_wave_monster->wave_monster->boss == 1) {
                    // ボス
                    $shuffle_flg = false;
                } elseif ($user_wave_monster->wave_monster->boss == 2) {
                    // ノトーリアスモンスター
                    $shuffle_flg = false;
                } elseif ($user_wave_monster->wave_monster->prob >= 10000) {
                    // ウェーブデータの登場確率が10000以上のモンスター
                    $shuffle_flg = false;
                }
            }
            if ($shuffle_flg) {
                shuffle($user_wave->user_wave_monsters);
            }

            $card_ids = array();
            foreach ($user_wave->user_wave_monsters as $user_wave_monster) {
                $card_ids[] = (int) $user_wave_monster->wave_monster->card_id;
            }
            $index = mt_rand(1, count($user_wave->user_wave_monsters)) - 1;
            $user_wave_monster = $user_wave->user_wave_monsters[$index];

            $card_id = (int) $user_wave_monster->wave_monster->card_id;
            $level = (int) $user_wave_monster->level;

            $card = Card::get($card_id);

            $emhp = Card::getCardParam($level, $card->edefd, $card->emhpa, $card->emhpb, $card->emhpc);
            $eatk = Card::getCardParam($level, $card->edefd, $card->eatka, $card->eatkb, $card->eatkc);
            $edef = Card::getCardParam($level, $card->edefd, $card->edefa, $card->edefb, $card->edefc);

            $wave_mons_indexs[] = $index;
            $wave_mons_ids[] = $card_ids;

            $mons_params = array(
                (int) $card->id,
                $emhp,
                $eatk,
                $edef,
                (int) $card->acyc,
                (int) $card->estu,
                (int) $card->esturn2,
                (int) $card->aip0,
                (int) $card->aip1,
                (int) $card->aip2,
                (int) $card->aip3,
                (int) $card->aip4,
            );
            for ($i = 0; $i <= 31; $i++) {
                $num = "ai" . $i . "num";
                $aip = "ai" . $i . "aip";
                $rnd = "ai" . $i . "rnd";
                $mons_params[] = (int) $card->$num;
                $mons_params[] = (int) $card->$aip;
                $mons_params[] = (int) $card->$rnd;
            }
            $wave_mons_params[] = $mons_params;
        }

        $this->check_cheat_info = json_encode(
                array(
                    'wave' => $wave_mons_ids,
                    'enemy' => $wave_mons_params,
                    'member' => $user_deck_params,
                    'helper' => $helper_card_params,
                )
        );

        return $wave_mons_indexs;
    }

    /**
     * #PADC#
     * チート対策チェック結果を返す
     * 何らかのチート検知に引っかかったらtrueを返す
     */
    public function checkCheatInfo($params, $helper_pdo = null) {
        global $logger;
        $res_mes = '';

        // ローカル環境の場合、チートチェックはしない
        if (Env::ENV == 'padclocal') {
            return array(false, $res_mes);
        }

        // 動作確認用
        // $params['time'] = '3000,3000,3000';
        // $params['wave1'] = '36,36';
        // $params['wave2'] = '36,40,36,38';
        // $params['wave3'] = '48,50,48';
        // $params['wave4'] = '';
        // $params['wave5'] = '';
        // $params['enemy1'] = '36,35,11,2,3,1,0,0,0,0,0,0,381,0,25,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,35';
        // $params['enemy2'] = '36,35,11,2,3,1,0,0,0,0,0,0,381,0,25,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,35';
        // $params['enemy3'] = '50,50,23,3,2,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,50';
        // $params['enemy4'] = '';
        // $params['enemy5'] = '';
        // $params['member1'] = '1,221,173,132,1,8';
        // $params['member2'] = '0,0,0,0,0,0';
        // $params['member3'] = '0,0,0,0,0,0';
        // $params['member4'] = '0,0,0,0,0,0';
        // $params['member5'] = '0,0,0,0,0,0';
        // $params['helper'] = '0,0,0,0,0,0,0';
        // $params['tdmg'] = '0';
        // $params['trec'] = '1000';
        // $params['mhp'] = '221';
        // $params['mdmg1'] = '1730000';
        // $params['mdmg2'] = '0';
        // $params['mdmg3'] = '0';
        // $params['mdmg4'] = '0';
        // $params['mdmg5'] = '0';
        // $params['mdmg6'] = '0';
        // $params['sint1'] = '8';
        // $params['sint2'] = '0';
        // $params['sint3'] = '0';
        // $params['sint4'] = '0';
        // $params['sint5'] = '0';
        // $params['sint6'] = '0';


        if (!$params) {
            $res_mes = 'check param is none.';
            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
            return array(true, $res_mes);
        }

        $check_cheat_info = json_decode($this->check_cheat_info, true);
        $wave_mons_ids = $check_cheat_info['wave'];
        $wave_mons_params = $check_cheat_info['enemy'];
        $user_deck_params = $check_cheat_info['member'];
        $helper_card_params = $check_cheat_info['helper'];

        // 各waveクリアにかかった時間
        $time = (isset($params['time']) ? explode(',', $params['time']) : NULL);
        if ($time) {
            foreach ($time as $i => $t) {
                // 1秒以内にクリアしているwaveがある場合
                if ($t < 1000) {
                    $res_mes = 'wave' . ($i + 1) . ' clear time is too short.' . $t;
                    $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                    return array(true, $res_mes);
                }
            }
        } else {
            $res_mes = 'wave time is none.';
            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
            return array(true, $res_mes);
        }

        try {
            // 各waveでランダム選出されたモンスターのステータス
            foreach ($wave_mons_params as $i => $param) {
                $enemy = (isset($params['enemy' . ($i + 1)]) ? explode(',', $params['enemy' . ($i + 1)]) : NULL);
                if ($enemy) {
                    foreach ($param as $j => $p) {
                        $err = false;
                        // HP、ATK、DEFの値は計算結果に誤差が出る可能性があるため、±5まで許容する
                        if ($j == 1 || $j == 2 || $j == 3) {
                            if (abs($p - $enemy[$j]) > 5) {
                                $err = true;
                            }
                        } else if ($p != $enemy[$j]) {
                            $err = true;
                        }
                        if ($err) {
                            $res_mes = 'enemy' . ($i + 1) . ' param' . $j . ' wrong. s:' . $p . ' a:' . $enemy[$j];
                            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                            return array(true, $res_mes);
                        }
                    }
                    // 最後に与えたダメージがあるのでHPを超えてるかチェック
                    $dmg = end($enemy);
                    if ($dmg < $param[1]) {
                        $res_mes = 'wave' . ($i + 1) . ' damage is wrong. ' . $dmg;
                        $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                        return array(true, $res_mes);
                    }
                } else {
                    $res_mes = 'enemy' . ($i + 1) . ' is none.';
                    $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                    return array(true, $res_mes);
                }
            }
        } catch (Exception $e) {
            $res_mes = $e->getMessage();
            $res_mes .= ' enemy param is not enough?';
            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
            return array(true, $res_mes);
        }

        try {
            // 各waveモンスターID
            foreach ($wave_mons_ids as $i => $ids) {
                $wave = (isset($params['wave' . ($i + 1)]) ? explode(',', $params['wave' . ($i + 1)]) : NULL);
                if ($wave) {
                    foreach ($ids as $j => $id) {
                        if ($id != $wave[$j]) {
                            $res_mes = 'wave' . ($i + 1) . ' id' . $j . ' is not equrl.';
                            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                            return array(true, $res_mes);
                        }
                    }
                } else {
                    $res_mes = 'wave' . ($i + 1) . ' is none.';
                    $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                    return array(true, $res_mes);
                }
            }
        } catch (Exception $e) {
            $res_mes = $e->getMessage();
            $res_mes .= ' wave param is not enough?';
            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
            return array(true, $res_mes);
        }

        $atk_params = array();
        $skl_turns = array();
        try {
            // ユーザーデッキモンスターのステータス
            foreach ($user_deck_params as $i => $param) {
                $member = (isset($params['member' . ($i + 1)]) ? explode(',', $params['member' . ($i + 1)]) : NULL);
                if ($member) {
                    foreach ($param as $j => $p) {
                        $err = false;
                        // HP、ATK、RECの値は計算結果に誤差が出る可能性があるため、±5まで許容する
                        if ($j == 1 || $j == 2 || $j == 3) {
                            if (abs($p - $member[$j]) > 5) {
                                $err = true;
                            }
                        } else if ($p != $member[$j]) {
                            $err = true;
                        }
                        if ($err) {
                            $res_mes = 'member' . ($i + 1) . ' param' . $j . ' wrong. s:' . $p . ' a:' . $member[$j];
                            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                            return array(true, $res_mes);
                        }
                        // 後で利用するため攻撃力とスキルターン数だけ配列に格納
                        if ($j == 2) {
                            $atk_params[] = $p;
                        }
                        if ($j == 5) {
                            $skl_turns[] = $p;
                        }
                    }
                } else {
                    $res_mes = 'member' . ($i + 1) . ' is none.';
                    $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                    return array(true, $res_mes);
                }
            }
        } catch (Exception $e) {
            $res_mes = $e->getMessage();
            $res_mes .= ' member param is not enough?';
            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
            return array(true, $res_mes);
        }

        // 助っ人の情報
        if ($helper_card_params) {
            $helper = (isset($params['helper']) ? explode(',', $params['helper']) : NULL);
            if ($helper) {
                foreach ($helper_card_params as $i => $param) {
                    if ($param != $helper[$i]) {
                        $res_mes = 'helper param' . $i . ' is not equrl.';
                        $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                        return array(true, $res_mes);
                    }
                    // 後で利用するため攻撃力とスキルターン数だけ配列に格納
                    if ($i == 2) {
                        $atk_params[] = $param;
                    }
                    if ($i == 5) {
                        $skl_turns[] = $param;
                    }
                }

                $helper_id = end($helper); // 一番最後がプレイヤーID
                if ($helper_id > 0 && !User::find($helper_id, $helper_pdo)) {
                    $res_mes = 'player_id ' . $helper_id . ' user is none.';
                    $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                    return array(true, $res_mes);
                }
            } else {
                $res_mes = 'helper is none.';
                $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                return array(true, $res_mes);
            }
        }

        // 被ダメージチェック
        // 累計ダメージと累計回復量と最大HPを比較する
        $tdmg = (isset($params['tdmg']) ? $params['tdmg'] : 0);
        $trec = (isset($params['trec']) ? $params['trec'] : 0);
        $mhp = (isset($params['mhp']) ? $params['mhp'] : 0);
        if ($tdmg >= $trec + $mhp) {
            $res_mes = 'dmg wrong.tdmg:' . $tdmg . ' trec:' . $trec . ' mhp:' . $mhp;
            $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
            return array(true, $res_mes);
        }

        // 攻撃力チート
        // 各モンスターの与えた最大ダメージと理論上の攻撃数値を比較する
        $atk_mag = 500000; //攻撃倍率理論値
        foreach ($atk_params as $i => $atk) {
            $mdmg = (isset($params['mdmg' . ($i + 1)]) ? $params['mdmg' . ($i + 1)] : 0);
            if ($mdmg > $atk * $atk_mag) {
                $res_mes = 'max dmg wrong. mdmg' . ($i + 1) . ':' . $mdmg;
                $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                return array(true, $res_mes);
            }
        }

        // スキルインターバルチェック
        // 各モンスターのスキルインターバル値とスキルの最少ターン数を比較する
        foreach ($skl_turns as $i => $skl) {
            $sint = (isset($params['sint' . ($i + 1)]) ? $params['sint' . ($i + 1)] : 0);
            if ($sint != 0 && $sint < $skl) {
                $res_mes = 'skill interval wrong. sint' . ($i + 1) . ':' . $sint;
                $logger->log(('cheat check error. ' . $res_mes), Zend_Log::DEBUG);
                return array(true, $res_mes);
            }
        }

        // 最後まで通ったらチート検出なし
        $res_mes = 'cheat check success.';
        $logger->log($res_mes, Zend_Log::DEBUG);
        return array(false, $res_mes);
    }

	/**
     * #PADC_DY#
     * 派发三星奖励
     */
    public static function reward($user_id, $dungeon_id, $step, $token = null) {
        $dungeon_reward = DungeonReward::findBy(array(
            'dungeon_id' => $dungeon_id,
            'step' => $step
        ));
        
        $user_dungeon_floors = UserDungeonFloor::findAllBy(array(
            'user_id' => $user_id,
            'dungeon_id' => $dungeon_id
        ));
        if (!$dungeon_reward || !$user_dungeon_floors) {
            throw PadException(RespCode::INVALID_PARAMS, "Error params. user: {$user_id} dung: {$dungeon_id} step: {$step}");
        } else {
            $star = 0;
            foreach($user_dungeon_floors as $user_dungeon_floor) {
                $star += (int) $user_dungeon_floor->max_star;
            }
            if ($star < $dungeon_reward->required_star) {
                throw new PadException(RespCode::STAR_NOT_ENOUGH, 'Star not enough!');
            }
        }

        $pdo = Env::getDbConnectionForUserWrite($user_id);

        $user_dungeon_reward_history = UserDungeonRewardHistory::getByUserId($user_id, $dungeon_id, $pdo);
        if ($user_dungeon_reward_history->checkReward($step)) {
            throw new PadException(RespCode::ALREADY_RECEIVED, 'Already received!');
        }

        try {
            $pdo->beginTransaction();

            // #PADC_DY# 派发奖励
            $user = User::find($user_id, $pdo, TRUE);
            if ($dungeon_reward->bonus_id1 && $dungeon_reward->amount1) {
                $user->applyBonus($dungeon_reward->bonus_id1, $dungeon_reward->amount1, $pdo, null, $token, $dungeon_reward->piece_id1);
            }
            if ($dungeon_reward->bonus_id2 && $dungeon_reward->amount2) {
                $user->applyBonus($dungeon_reward->bonus_id2, $dungeon_reward->amount2, $pdo, null, $token, $dungeon_reward->piece_id2);
            }
            if ($dungeon_reward->bonus_id3 && $dungeon_reward->amount3) {
                $user->applyBonus($dungeon_reward->bonus_id3, $dungeon_reward->amount3, $pdo, null, $token, $dungeon_reward->piece_id3);
            }
            $user->update($pdo);

            // #PADC_DY# 领取记录
            $user_dungeon_reward_history->addReward($step);
            $user_dungeon_reward_history->update($pdo);

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return array(
            'coin' => (int) $user->coin,
            'gold' => $user->gold + $user->pgold
        );
    }

    public static function roundDungeonCnt($user, $dungeon, $dungeon_floor, $user_dungeon_floor, $sneak_time, $curdeck, $cnt, $sta_need, $round_ticket_need, $rev, $total_power, $securitySDK, $player_hp) {
        global $logger;
        $lv_up = FALSE;
        $qq_coin_bonus = 0;
        $qq_exp_bonus = 0;
        $get_cards = array();

        $result_pieces = array();
        $deck_cards = array();
        $game_center = (int) $user->game_center;
        $game_center_coin_bonus = 0;
        $get_key_method = 'get' . static::USER_WAVE_CLASS . 'sKey';
        //        $key = CacheKey::$get_key_method($dungeon->id, $dungeon_floor->id, mt_rand(1, 10));

        // DROP内容変更するかデバッグユーザー情報を取得
        $debugChangeDrop = DebugUser::isDropChangeDebugUser($user->id);
        if ($debugChangeDrop) {
            // 周回チケット、プラス欠片のDROP確率を取得
            list($prob_round, $prob_plus) = DebugUser::getDropChangeProb($user->id);
        }
        //        $rRedis = Env::getRedisForShareRead();
        //        $user_waves = $rRedis->get($key);
        $user_dungeon_floor_class = static::USER_DUNGEON_FLOOR_CLASS;
        $wave_class = static::WAVE_CLASS;
        $waves = $wave_class::getAllBy(array("dungeon_floor_id" => $dungeon_floor->id), "seq ASC");
        $platform_type = UserDevice::getUserPlatformType($user->id);
        list($active_bonuses, $dungeon_bonus) = static::getActiveBonus($user, $dungeon, $dungeon_floor);
        $user_wave_class = static::USER_WAVE_CLASS;

        //            $redis = Env::getRedisForShare();
        //            $redis->set($key, $user_waves, 20); // 20秒キャッシュ.
        //        }
        $next_dungeon_floors = $dungeon_floor->getAllNextFloors();

        try {
            $pdo = Env::getDbConnectionForUserWrite($user->id);
            $pdo->beginTransaction();

            // TODO 要扣除体力
            $user->useStamina($sta_need);
            $user->addRound(-$round_ticket_need);

            $before_card_count = UserCard::countAllBy(array('user_id' => $user->id), $pdo);
            if ($curdeck >= 0) {
                $user = UserDeck::changeCurrentDeck($user, $curdeck, $pdo);
            }

            // 额外奖励条件判断
            $qq_vip = (int) $user->qq_vip;
            if ($qq_vip == User::QQ_ACCOUNT_VIP) {
                $qq_coin_bonus = GameConstant::getParam("QQCoinBonus");
                $qq_exp_bonus = GameConstant::getParam("QQExpBonus");
            } else if ($qq_vip == User::QQ_ACCOUNT_SVIP) {
                $qq_coin_bonus = GameConstant::getParam("QQVipCoinBonus");
                $qq_exp_bonus = GameConstant::getParam("QQVipExpBonus");
            }

            // #PADC_DY# ----------begin----------
            // 安卓只有游戏中心启动才有加成
            if ($user->device_type == UserDevice::TYPE_ADR){
                if($game_center == User::QQ_GAME_CENTER || $game_center == User::WECHAT_GAME_CENTER){
                    $game_center_coin_bonus = GameConstant::getParam("GameCenterCoinBonus");
                }
            } else {
                // 苹果要求游客、QQ游戏中心、微信游戏中心登录都享有相同的加成
                if($game_center == User::QQ_GAME_CENTER || $game_center == User::WECHAT_GAME_CENTER || $platform_type == UserDevice::PTYPE_GUEST) {
                    $game_center_coin_bonus = GameConstant::getParam("GameCenterCoinBonus");
                }
            }

            // #PADC# 継承先でアクセスする関数を変更できるように、UserDungeonをstaticに変更。
            // $leader_card = User::getCacheFriendData($user->id, User::FORMAT_REV_1, $pdo);
            // $leader_card_id = $leader_card['card'];
            $t_lc = explode(',',$user->lc);
            $leader_card_id = $t_lc[1];
            $coeff_coin = static::calcCoeffCoin($user->id, 0, $leader_card_id, 0);
            $total_exp = 0;
            $total_coin = 0;
            $total_get_each_cnt = array();
            $add_cards = array();
            $add_pieces = array();
            for ($i = 0; $i < $cnt; $i++) {
                $user_waves = array();
                $get_pieces = array();
                // ノトーリアスモンスターの出現をダンジョン中1回だけにする
                $notorious_chance_wave = 0;
                if (count($waves) > 1) {
                    $notorious_chance_wave = mt_rand(1, count($waves) - 1);  // ボスウェーブには登場しない
                }
                $wave_count = 0;
                foreach ($waves as $wave) {
                    $wave_count++;
                    if ($wave_count == $notorious_chance_wave) {
                        $notorious_chance = TRUE;
                    } else {
                        $notorious_chance = false;
                    }
                    // DROP内容変更フラグチェック
                    if ($debugChangeDrop) {
                        // デバッグ用のUserWave作成処理
                        $user_waves[] = new $user_wave_class($dungeon_floor, $wave, $active_bonuses, $dungeon_bonus, $notorious_chance, $prob_round, $prob_plus);
                    } else {
                        // #PADC# 継承先でアクセスするDBを変更できるように、UserWaveを動的クラスに変更。
                        $user_waves[] = new $user_wave_class($dungeon_floor, $wave, $active_bonuses, $dungeon_bonus, $notorious_chance);
                    }
                }
                list($beat_bonuses, $exp, $coin) = $user_wave_class::getEncodedBeatBonusesAndExpAndCoin($user_waves, $coeff_coin);
                $logger->log(("get exp: $exp, get coin: $coin"), Zend_Log::DEBUG);

                $total_get_each_cnt[$i]['exp'] = $exp;
                $total_get_each_cnt[$i]['coin'] = $coin;

                $total_exp += $exp;
                $total_coin += $coin;

                $beat_bonuses_array = json_decode($beat_bonuses);
                foreach ($beat_bonuses_array as $beat_bonus) {
                    // INFO:PADC版ではダンジョンクリア時に直接モンスター付与はされないが一応残しておく
                    if ($beat_bonus->item_id <= BaseBonus::MAX_CARD_ID) {
                        // 付与するアイテムがカードの時.
                        $plus_hp = isset($beat_bonus->plus_hp) ? $beat_bonus->plus_hp : 0;
                        $plus_atk = isset($beat_bonus->plus_atk) ? $beat_bonus->plus_atk : 0;
                        $plus_rec = isset($beat_bonus->plus_rec) ? $beat_bonus->plus_rec : 0;
                        $add_cards[] = UserCard::addCardsToUserReserve(
                            $user->id, $beat_bonus->item_id, $beat_bonus->amount, UserCard::DEFAULT_SKILL_LEVEL, $pdo, $plus_hp, $plus_atk, $plus_rec, 0 // psk
                        );
                    } else if ($beat_bonus->item_id == BaseBonus::PIECE_ID) {
                        // INFO:カケラ付与
                        $piece_id = $beat_bonus->piece_id;
                        $piece_num = $beat_bonus->amount;
                        $get_pieces[] = array($piece_id, $piece_num);

                        // 同じIDのカケラが複数あっても大丈夫なように対応
                        if (array_key_exists($piece_id, $add_pieces)) {
                            $add_card = $add_pieces[$piece_id]->addPiece($piece_num, $pdo);
                            if ($add_card) {
                                $add_cards[] = $add_card;
                            }
                        } else {
                            $add_result = UserPiece::addUserPieceToUserReserve(
                                $user->id, $piece_id, $piece_num, $pdo
                            );
                            $add_pieces[$piece_id] = $add_result['piece'];
                            if (array_key_exists('card', $add_result)) {
                                $add_cards[] = $add_result['card'];
                            }
                        }
                    } else {
                        $b = new BeatBonus();
                        $b->item_id = $beat_bonus->item_id;
                        $b->amount = $beat_bonus->amount;
                        // #PADC# QQ会員ボーナス適用
                        if ($b->item_id == BaseBonus::COIN_ID) {
                            if ($qq_coin_bonus > 0) {
                                $b->amount = ceil($b->amount * (10000 + $qq_coin_bonus) / 10000);
                            }
                            if ($game_center_coin_bonus > 0) {
                                $b->amount = ceil($b->amount * (10000 + $game_center_coin_bonus) / 10000);
                            }
                        }
                        // #PADC# add parameters
                        $user->addCoin($b->amount);
                        $total_get_each_cnt[$i]['coin']+=  $b->amount;
                        $log_data["beat_bonuses"][] = array("award_id" => (int)$b->item_id, "amount" => (int)$b->amount);
                    }
                }

                $total_get_each_cnt[$i]['get_pieces'] = $get_pieces;

                // 扫荡会有追加掉落
                $round_bonus = null;
                foreach ($user_waves as $wave) {
                    // DROP内容変更フラグチェック
                    if ($debugChangeDrop) {
                        $round_bonus = $wave->debugGetBeatBonus($dungeon->id, $prob_round, $prob_plus);
                    } else {
                        $round_bonus = $wave->getBeatBonus($dungeon->id);
                    }
                    if ($round_bonus) {
                        break;
                    }
                }
                if (!$round_bonus) {
                    // 没有drop的时候，给一个强化碎片
                    $b = new BeatBonus();
                    $b->item_id = BaseBonus::PIECE_ID;
                    $b->piece_id = Piece::PIECE_ID_STRENGTH;
                    $b->amount = 1; // 個数は変更あるかも
                    $round_bonus = $b;
                } else if ($round_bonus->item_id == BaseBonus::COIN_ID) {
                    $round_bonus->amount = round($round_bonus->amount * $coeff_coin);
                }
                // 周回ボーナス
                if ($round_bonus) {
                    if ($round_bonus->item_id <= BaseBonus::MAX_CARD_ID) {
                        // INFO:PADC版では直接モンスター付与はされないので無視する
                    } else if ($round_bonus->item_id == BaseBonus::PIECE_ID) {
                        // INFO:カケラ付与
                        $piece_id = $round_bonus->piece_id;
                        $piece_num = $round_bonus->amount;

                        // 同じIDのカケラが複数あっても大丈夫なように対応
                        if (array_key_exists($piece_id, $add_pieces)) {
                            $add_card = $add_pieces[$piece_id]->addPiece($piece_num, $pdo);
                            if ($add_card) {
                                $add_cards[] = $add_card;
                            }
                        } else {
                            $add_result = UserPiece::addUserPieceToUserReserve(
                                $user->id, $piece_id, $piece_num, $pdo
                            );
                            $add_pieces[$piece_id] = $add_result['piece'];
                            if (array_key_exists('card', $add_result)) {
                                $add_cards[] = $add_result['card'];
                            }
                        }
                    } else {
                        $b = new BeatBonus();
                        $b->item_id = $round_bonus->item_id;
                        $b->amount = $round_bonus->amount;
                        // #PADC# QQ会員ボーナス適用
                        if ($b->item_id == BaseBonus::COIN_ID) {
                            if ($qq_coin_bonus > 0) {
                                $b->amount = ceil($b->amount * (10000 + $qq_coin_bonus) / 10000);
                            }
                            if ($game_center_coin_bonus > 0) {
                                $b->amount = ceil($b->amount * (10000 + $game_center_coin_bonus) / 10000);
                            }
                        }
                        // #PADC# add parameters
                        $user = $b->apply($user, $pdo, null, null);
                        $total_get_each_cnt[$i]['coin']+=  $b->amount;

                        $log_data["round_bonuses"][] = array("award_id" => (int)$b->item_id, "amount" => (int)$b->amount);
                    }

                }

                $total_get_each_cnt[$i]['round_bonus'] = array("item_id" => $round_bonus->item_id, "amount" => $round_bonus->amount, "piece_id" => $round_bonus->piece_id);

            }
            $logger->log((json_encode($total_get_each_cnt)), Zend_Log::DEBUG);
            // 单次结果

            $deck_ids = explode(",", UserDeck::findBy(array('user_id' => $user->id), $pdo)->toCuidsCS());
            $user_cards = UserCard::findByCuids($user->id, $deck_ids, $pdo);
            $cuid_cards = array();
            $cards = array();
            foreach ($user_cards as $user_card) {
                $cuid_cards[$user_card->cuid] = $user_card;
                // #PADC# ランキングスコア計算にデッキの情報が必要になった。
                $cards[] = Card::get($user_card->card_id);
            }

            // 経験値&コイン付与.
            // #PADC# QQ会員ボーナス適用
            if ($qq_exp_bonus > 0) {
                $total_exp = ceil($total_exp * (10000 + $qq_exp_bonus) / 10000);
            }
            if ($qq_coin_bonus > 0) {
                $total_coin = ceil($total_coin * (10000 + $qq_coin_bonus) / 10000);
            }
            if ($game_center_coin_bonus > 0) {
                $total_coin = ceil($total_coin * (10000 + $game_center_coin_bonus) / 10000);
            }

            // #PADC_DY# resume to rank-up system, add user's exp
            $user->addExp($total_exp);
            $user->addCoin($total_coin);


            // 欠片、モンスターの付与
            list ($result_pieces, $get_cards) = UserPiece::addUserPiecesWithCardsToUserFix($add_pieces, $add_cards, $pdo);

            foreach ($get_cards as $card) {
                $equip = array((int)$card->equip1, (int)$card->equip2, (int)$card->equip3, (int)$card->equip4);
                $log_data["get_cards"][] = array("card_id" => (int)$card->card_id, "cuid" => (int)$card->cuid, "level" => (int)$card->lv, "equip" => $equip);
            }
            foreach ($result_pieces as $piece) {
                $log_data["result_pieces"][] = array("piece_id" => (int)$piece->piece_id, "num" => (int)$piece->num, "create_card" => (int)$piece->create_card);
            }
            $result_pieces = UserPiece::arrangeColumns($result_pieces);

            // 図鑑登録数の更新
            $user_book = UserBook::getByUserId($user->id, $pdo);
            $user->book_cnt = $user_book->getCountIds();

            $user_count = UserCount::getByUserId($user->id, time(), $pdo);
            if ($dungeon->isNormalDungeon()) {
                $user_count->addCount(UserCount::TYPE_CLEAR_NORMAL, $cnt);
                $user_count->addCount(UserCount::TYPE_DAILY_CLEAR_NORMAL, $cnt);
            } else if ($dungeon->isEventDungeon()) {
                $user_count->addCount(UserCount::TYPE_CLEAR_SPECIAL, $cnt);
                $user_count->addCount(UserCount::TYPE_DAILY_CLEAR_SPECIAL, $cnt);
            }
            $user_count->update($pdo);

            // デッキモンスターに経験値付与
            // 処理が被るのでログ出力と併用して行う
            // カレントデッキ情報更新
            $decks = array();
            $card_cnt = 0;

            $card_exp = $total_exp * 0.8;

            foreach ($deck_ids as $cuid) {
                $card_cnt++;
                if ($cuid > 0) {
                    $card = $cuid_cards[$cuid];
                    $decks["card_id" . $card_cnt] = (int)$card->card_id;
                    $decks["card_lv" . $card_cnt] = (int)$card->lv;
                    $before_lv = (int)$card->lv;
                    // チート検知したら経験値付与はしない
                    $base_card = $card->getMaster();
                    $card->exp = min($card->exp + $card_exp, $base_card->pexpa);
                    $card->setLevelOnExp();
                    $card->update($pdo);

                    // レベルが上がった場合にリーダーカードの更新処理を行う
                    if ($before_lv != $card->lv) {
                        // リーダーカードのみ更新処理
                        if ($card_cnt == 1) {
                            $lc_data = $card->setLeaderCard($user);
                            $user->lc = join(",", $lc_data);
                        }

                        // デッキデータの更新処理
                        $ldeck = $card->setLeaderDeckCard($user);
                        $user->ldeck = json_encode($ldeck);
                    }
                    $deck_cards[] = GetUserCards::arrangeColumn($card);
                }
            }
            // 直近でクリアしたダンジョンIDを入力
            // #PADC# ----------end----------
            $user->accessed_at = User::timeToStr(time());
            $user->accessed_on = $user->accessed_at;
            // #PADC_DY# ----------begin----------
            //	resume user's level up
            $lv_up = $user->isExpReachedNextLevel();
            if ($lv_up) {
                $before_user_lv = $user->lv;
                $curLevel = LevelUp::get($user->lv);
                $nextLevel = LevelUp::get($user->lv + 1);
                while ($user->levelUp($nextLevel)) {
                    $curLevel = $nextLevel;
                    $nextLevel = LevelUp::get($user->lv + 1);
                }

                //set last level up time and level up cost
                $current_time = time();
                if (empty($user->last_lv_up) || $user->last_lv_up == '0000-00-00 00:00:00') {
                    $level_up_time_cost = 0;
                    $user->last_lv_up = User::timeToStr($current_time);
                } else {
                    $level_up_time_cost = $current_time - strtotime($user->last_lv_up);
                    //update last_lv_up to last time
                    $user->last_lv_up = User::timeToStr($current_time);
                }

                // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonFloorを動的クラスに変更。
                $clear_dungeon_floors = $user_dungeon_floor_class::getCleared($user->id, $pdo);
                $clear_dungeon_floor_ids = array();
                foreach ($clear_dungeon_floors as $cdf) {
                    $clear_dungeon_floor_ids[] = $cdf->dungeon_floor_id;
                }

                // #PADC_DY# ----------begin----------
                // 获取升级后新开放的Dungeon floor
                // #PADC# 継承先でアクセスするDBを変更できるように、DungeonFloorを動的クラスに変更。
                $dungeon_floor_class = static::DUNGEON_FLOOR_CLASS;
                $next_dungeon_floors_2 = $dungeon_floor_class::getNextFloorsByParams($before_user_lv, $user->lv, $clear_dungeon_floor_ids);
                $next_dungeon_floors = array_merge($next_dungeon_floors, $next_dungeon_floors_2);
                // #PADC_DY# ----------end----------

            }
            // #PADC_DY# ----------end----------
            $user->update($pdo);
            // 次フロアの開放.
            foreach ($next_dungeon_floors as $next_floor) {
                // #PADC# 継承先でアクセスするDBを変更できるように、UserDungeonFloorを動的クラスに変更。
                $user_dungeon_floor_class::enable($user->id, $next_floor->dungeon_id, $next_floor->id, $pdo);
            }

            $next_floors = array();
            foreach ($next_dungeon_floors as $next_dungeon_floor) {
                $next_floors[] = array("dung" => (int) $next_dungeon_floor->dungeon_id, "floor" => (int) $next_dungeon_floor->seq);
            }

            // TODO: 扣userdungeonfloor的次数
            // #PADC_DY# 记录进入关卡次数 ----------begin----------
            $daily_left_times = $user_dungeon_floor->getLeftPlayingTimes();
            if(empty($user_dungeon_floor->daily_first_played_at) || !static::isSameDay_AM4(static::strToTime($user_dungeon_floor->daily_first_played_at), time())) {
                $user_dungeon_floor->daily_first_played_at = static::timeToStr(time());
                $user_dungeon_floor->daily_played_times = $cnt;
                $user_dungeon_floor->daily_recovered_times = 0;
                $user_dungeon_floor->update($pdo);
            } elseif($daily_left_times <= 0) {
                $logger->log(("no left times. user_id:$user->id name:$user->name dungeon:$dungeon->id floor:$dungeon_floor->id"), Zend_Log::DEBUG);
                $pdo->rollback();
                return array($user, null, 0, array(), 0);
            } elseif($user_dungeon_floor->daily_played_times >= 0) {
                $user_dungeon_floor->daily_played_times += $cnt;
                $user_dungeon_floor->update($pdo);
            } else {
                $user_dungeon_floor->daily_played_times = $cnt;
                $user_dungeon_floor->update($pdo);
            }

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            throw $e;
        }

        $res = array(
            'res' => 0,
            'lup' => $lv_up ? 1 : 0,
            'expgain' => $total_exp,
            'coingain' => $total_coin,
            'goldgain' => 0,
            'exp' => (int) $user->exp,
            'coin' => (int) $user->coin,
            'gold' => ($user->gold + $user->pgold),
            'nextfloors' => $next_floors,
            'cards' => GetUserCards::arrangeColumns($get_cards),
            'src' => 0,
            'before_card_count' => $before_card_count,
            // #PADC# ----------begin----------
            'all_gains' => $total_get_each_cnt,
            'pieces' => $result_pieces,
            'decks' => $deck_cards,
            'clr_dcnt' => $user->clear_dungeon_cnt,
            'sta' => $user->stamina,
            'sta_time' => strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time)),
            // #PADC_DY# ----------begin----------
            // 扫荡返回当前用户的最大体力上限
            'stamax' => $user->stamina_max,
            // #PADC_DY# ----------end----------
            'cheat' => '',
            'roundgain' => 0,
            'round' => (int)$user->round,
            'qq_vip' => $qq_vip,
            'qq_coin_bonus' => $qq_coin_bonus,
            'qq_exp_bonus' => $qq_exp_bonus,
            'ten_gc' => $game_center,
            'daily_left_times' => $user_dungeon_floor->getLeftPlayingTimes(), // #PADC_DY# daily_left_times表示表当日剩余关卡次数
        );
        return $res;
    }

}
