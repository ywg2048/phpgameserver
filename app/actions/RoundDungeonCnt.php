<?php

/**
 * #PADC#
 * ダンジョン潜入（周回）
 *
 */
class RoundDungeonCnt extends BaseAction {
    // http://pad.localhost/api.php?action=round_dungeon_cnt&pid=1&sid=1&dung=1&floor=1

    const MEMCACHED_EXPIRE = 120; // 2分.

    public function action($params) {
        global $logger;
        $user = User::find($params["pid"]);
        $rev = (isset($params["r"])) ? (int)$params["r"] : 1;
        /*
        $bm = 0;
        if ($rev >= 2) {
            $bm = (int)$this->decode_params["bm"];
            $rk = (int)$this->decode_params["rk"];
            $bm = $bm - ($rk & 0xFF);
        }
        */
        $cnt = (isset($params["cnt"])) ? (int)$params["cnt"] : 1;

        $access_token = isset ( $params ['ten_at'] ) ? $params ['ten_at'] : null;
        //#PADC#
        $total_power = (isset($params['total_power']) ? $params['total_power'] : 0);

        $have_card_cnt = (isset($params["c"]) ? $params["c"] : 0); // 所持しているカード枚数（ダンジョン潜入前）.
        $api_version = (isset($params["v"]) ? $params["v"] : 0); // レスポンスバージョン.

        // #PADC# memcache→redis
        $rRedis = Env::getRedisForUserRead();
        $res = null;
        // 改修内容
        // ハッシュ値のみを返す
        if (array_key_exists("dung", $params) && array_key_exists("floor", $params) && array_key_exists("time", $params)) {
            $sneak_time = $params["time"];
            // 二重アクセス防止の為、同じリクエストの場合はキャッシュの内容を返す
            $key = CacheKey::getSneakDungeonRound($user->id, $params["dung"], $params["floor"], $sneak_time);
            $res = $rRedis->get($key);
            if (!$res) {
                // ダンジョン&フロア抽出.
                $dungeon = Dungeon::get($params["dung"]);
                if ($dungeon) {
                    // #PADC# ----------begin----------
                    $check_punish_type = User::PUNISH_PLAY_BAN_NORMAL;
                    if ($dungeon->dkind == Dungeon::DUNG_KIND_BUY) {
                        $check_punish_type = User::PUNISH_PLAY_BAN_BUYDUNG;
                    } else if (!$dungeon->isNormalDungeon()) {
                        $check_punish_type = User::PUNISH_PLAY_BAN_SPECIAL;
                    }
                    $punish_info = UserBanMessage::getPunishInfo($params["pid"], $check_punish_type);
                    if ($punish_info) {
                        return json_encode(array(
                            'res' => RespCode::PLAY_BAN,
                            'ban_msg' => $punish_info['msg'],
                            'ban_end' => $punish_info['end']
                        ));
                    }
                    // #PADC# ----------end----------

                    $dungeon_floor = DungeonFloor::getBy(array("dungeon_id" => $dungeon->id, "seq" => $params["floor"]));

                    if ($dungeon_floor->rticket == 0) {
                        $logger->log(("dungeon floor can not round!"), Zend_Log::DEBUG);
                        return json_encode(array('res' => RespCode::FAILED_SNEAK, 'hash' => 0));
                    }

                    // 周回は助っ人選択なし
                    $curdeck = (isset($params["curdeck"])) ? $params["curdeck"] : -1;
                    $cm = (isset($params["cm"])) ? $params["cm"] : null;

                    // 检查体力是否足够
                    $sta_need = $dungeon_floor->sta * $cnt;

                    $cur_sta = $user->getStamina();

                    if ($cur_sta < $sta_need) {
                        $logger->log(("stamina not enough for round! need: $sta_need, current stamina: $cur_sta "), Zend_Log::DEBUG);
                        return json_encode(array('res' => RespCode::FAILED_SNEAK, 'hash' => 0));
                    }
                    // 检查关卡剩余次数
                    $param = array(
                        "user_id" => $user->id,
                        "dungeon_id" => $dungeon->id,
                        "dungeon_floor_id" => $dungeon_floor->id
                    );
                    // 获得该关卡用户信息
                    $user_dungeon_floor = UserDungeonFloor::findBy($param);
                    // 该关卡没达到三星评价无法扫荡
                    if ($user_dungeon_floor->max_star < UserDungeon::DUNGEON_FLOOR_MAX_STAR) {
                        $logger->log(("not 3 star!"), Zend_Log::DEBUG);
                        return json_encode(array('res' => RespCode::FAILED_SNEAK, 'hash' => 0));
                    }
                    // 该关卡剩余次数
                    $left_time = $user_dungeon_floor->getLeftPlayingTimes();
                    if ($left_time < $cnt) {
                        $logger->log(("not enough play times! need: $cnt, current left: $left_time"), Zend_Log::DEBUG);
                        return json_encode(array('res' => RespCode::FAILED_SNEAK, 'hash' => 0));
                    }
                    // 检测扫荡券数量
                    // 本次扫荡需要的扫荡券数量
                    $round_ticket_need = $dungeon_floor->rticket * $cnt;
                    if($user->round < $round_ticket_need){
                        $logger->log(("not enough round tickets! need: $round_ticket_need, current left: $user->round"), Zend_Log::DEBUG);
                        return json_encode(array('res' => RespCode::FAILED_SNEAK, 'hash' => 0));
                    }

                    //#PADC#
                    $securitySDK = (isset($this->decode_params['sdkres']) ? $this->decode_params['sdkres'] : null);
                    $player_hp = (isset($params["mhp"])) ? $params["mhp"] : 1;

                    $res = UserDungeon::roundDungeonCnt($user, $dungeon, $dungeon_floor, $user_dungeon_floor, $sneak_time, $curdeck, $cnt, $sta_need, $round_ticket_need, $rev, $total_power, $securitySDK, $player_hp);

                    User::reportUserCardNum($user->id, $access_token);

                    if($api_version == 2 && $have_card_cnt == (int)$res['before_card_count']){
                        $cards = $res['cards'];
                        $res_version = 2;
                    }else{
                        $cards = GetUserCards::getAllUserCards($user->id);
                        $res_version = 0;
                    }

                    list($clear_mission_count, $clear_mission_ids) = UserMission::checkClearMissionTypes ( $user->id, array (
                        Mission::CONDITION_TYPE_USER_RANK,
                        Mission::CONDITION_TYPE_DUNGEON_CLEAR,
                        Mission::CONDITION_TYPE_BOOK_COUNT,
                        Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR,
                        Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_NORMAL,
                        Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_SPECIAL,
                    ) );

                }
            }
        }
        $res['v'] = $res_version;
        $res['cards'] = $cards;
        $res['ncm'] = $clear_mission_count;
        $res['clear_mission_list'] = $clear_mission_ids;
        return json_encode($res);
    }
}
