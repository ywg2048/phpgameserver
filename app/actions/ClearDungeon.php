<?php

/**
 * 9. ダンジョンクリア.
 */
class ClearDungeon extends BaseAction {

    // http://pad.localhost/api.php?action=clear_dungeon&pid=1&sid=1&hash=abc
    public function action($params) {
        $user_id = $params["pid"];

        // #PADC#
        $token = Tencent_MsdkApi::checkToken($params);
        if (!$token) {
            return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
        }

        // ハッシュキーの存在チェック.
        if (!array_key_exists("hash", $params)) {
            return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
        }

        // ユーザーダンジョンの存在チェック.
        $user_dungeon = UserDungeon::findBy(array("user_id" => $user_id));
        if (!$user_dungeon) {
            return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
        }

        global $logger;
        $dung = (isset($params["dung"]) ? $params["dung"] : 0);
        $floor = (isset($params["floor"]) ? $params["floor"] : 0);
        $dungeon_floor_id = $dung * 1000 + $floor;
        // ダンジョン、フロアの一致チェック.
        if ($user_dungeon->dungeon_id != $dung || $user_dungeon->dungeon_floor_id != $dungeon_floor_id) {
            $logger->log("InvalidClearDungeon user_id:" . $user_id . " dung:" . $dung . " floor:" . $floor . " db_dungeon_id:" . $user_dungeon->dungeon_id . " db_dungeon_floor_id:" . $user_dungeon->dungeon_floor_id, Zend_Log::INFO);
            return json_encode(array('res' => RespCode::FAILED_CLEAR_DUNGEON));
        }

        // ハッシュキーの一致チェック.
        if ($user_dungeon->hash != $params["hash"]) {
            $logger->log("InvalidClearHash user_id:" . $user_id . " hash:" . $params["hash"] . " db_hash:" . $user_dungeon->hash . " dung:" . $dung . " floor:" . $floor, Zend_Log::INFO);
            return json_encode(array('res' => RespCode::INVALID_CLEAR_HASH));
        }

        // クライアントバージョン文字列
        $app_verision = isset($params['appv']) ? $params['appv'] : 'unknown';
        $app_revision = isset($params['appr']) ? $params['appr'] : 'unknown';
        $client_verision = $app_verision . '_' . $app_revision;

        // https://61.215.220.70/redmine-pad/issues/915
        // メモリ書き換えチート対策
        $nxc = (isset($params["nxc"]) ? $params["nxc"] : NULL);
        $score = (isset($this->decode_params["s"]) ? $this->decode_params["s"] : NULL);

        // ダンジョンクリア処理実行.
        // #PADC# Tencentサーバーに無料魔法石を追加する為に、パラメータ追加
        // #PADC# チート対策チェックのため、アプリから送られた情報を送るよう修正
        list($user, $res) = $user_dungeon->clear($nxc, $this->decode_params, $client_verision, $token);
        $next_floors = array();
        foreach ($res->next_dungeon_floors as $next_dungeon_floor) {
            $next_floors[] = array("dung" => (int) $next_dungeon_floor->dungeon_id, "floor" => (int) $next_dungeon_floor->seq);
        }
        // ダンジョンで取得したカードを含めてリストをとり直す.
        $api_version = (isset($params["v"]) ? $params["v"] : 0); // レスポンスバージョン.
        $have_card_cnt = (isset($params["c"]) ? $params["c"] : 0); // 所持しているカード枚数（ダンジョン潜入前）.
        if ($api_version == 2 && $have_card_cnt == $res->before_card_count) {
            $cards = GetUserCards::arrangeColumns($res->get_cards);
            $res_version = 2;
        } else {
            $cards = GetUserCards::getAllUserCards($user_id);
            $res_version = 0;
        }

        // #PADC# ミッションクリア確認
        list($clear_mission_count, $clear_mission_ids) = UserMission::checkClearMissionTypes($user_id, array(
                    Mission::CONDITION_TYPE_USER_RANK,
                    Mission::CONDITION_TYPE_DUNGEON_CLEAR,
                    Mission::CONDITION_TYPE_BOOK_COUNT,
                    Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR,
                    Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_NORMAL,
                    Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_SPECIAL,
        ));

        // #PADC#
        User::reportUserCardNum($user_id, $token['access_token']);

        // #PADC# ----------begin----------
        // レスポンス内容更新予定
        // 最終的に手に入れたカードやカケラ、経験値付与後のカードデータetc.
        $return = array(
            'res' => RespCode::SUCCESS,
            'v' => $res_version,
            'lup' => $res->lv_up ? 1 : 0,
            'expgain' => $res->expgain,
            'coingain' => $res->coingain,
            'goldgain' => $res->goldgain,
            'exp' => $res->exp,
            'coin' => $res->coin,
            'gold' => $res->gold,
            'nextfloors' => $next_floors,
            'cards' => $cards,
            // #PADC# ----------begin----------
            'get_pieces' => $res->get_pieces,
            'pieces' => $res->result_pieces,
            'decks' => $res->deck_cards,
            'clr_dcnt' => $res->clr_dcnt,
            'ncm' => $clear_mission_count,
            'clear_mission_list' => $clear_mission_ids,
            'sta' => $user->getStamina(),
            'sta_time' => strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time)),
            'stamax' => $user->stamina_max,
            'cheat' => $res->cheat,
            'roundgain' => $res->roundgain,
            'round' => (int) $user->round,
            'qq_vip' => $res->qq_vip,
            'qq_coin_bonus' => $res->qq_coin_bonus,
            'qq_exp_bonus' => $res->qq_exp_bonus,
            'ten_gc' => $res->game_center,
            // #PADC# ----------end----------
            'max_star' => $res->max_star, // #PADC_DY# 获得三星数
            'continue_cnt' => $res->continue_cnt, // #PADC_DY# 是否接关
            'star3_required_turn' => $res->star3_required_turn, // #PADC_DY# 获得三星所需回合数
        );
        //Sランク達成でたまドラを配布.
        if ($res->src == 1) {
            $return['src'] = 1;
        }
        // BANメッセージがある場合、レスポンス内容に追加
        if ($res->ban_msg) {
            $return['ban_msg'] = $res->ban_msg;
        }
        // #PADC# ----------end----------

        //新手嘉年华：通关特定的关卡
        UserCarnivalInfo::carnivalMissionCheck($user->id,CarnivalPrize::CONDITION_TYPE_DUNGEON_CLEAR,$user_dungeon->dungeon_floor_id);

        //新手嘉年华：玩家的等级
        UserCarnivalInfo::carnivalMissionCheck($user->id,CarnivalPrize::CONDITION_TYPE_USER_RANK,$user->lv);

        return json_encode($return);
    }

    /**
     * このAPIをストレステストする際のダミーデータを作成する.
     */
    public function createDummyDataForUser($user, $pdo) {
        // 1-1,2,3, 2-1,2,3 を開放.
        $d_fs = array(
            array(1, 1001),
            array(1, 1002),
            array(1, 1003),
            array(2, 2001),
            array(2, 2002),
            array(2, 2003),
        );
        foreach ($d_fs as $d_f) {
            UserDungeonFloor::enable($user->id, $d_f[0], $d_f[1], $pdo);
        }

        // いずれかのダンジョンに潜入.
        $d_f = $d_fs[mt_rand(0, count($d_fs) - 1)];
        UserDungeon::sneak($user, Dungeon::get($d_f[0]), DungeonFloor::get($d_f[1]));
    }

}
