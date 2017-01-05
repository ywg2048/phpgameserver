<?php

/**
 * 56. 連続ガチャ.
 */
class PlayGachaCnt extends BaseAction {
    // http://pad.localhost/api.php?action=play_gacha_cnt&pid=1&sid=1&gtype=1
    const ENCRYPT_RESPONSE = FALSE;

    public function action($params) {
        // このAPIでコールできるのは友情ガチャと課金ガチャのみ.
        $resp_code = RespCode::FAILED_GACHA;
        $user_card_str = 0;
        $bm = 0;
        $grow = null;
        $user_id = $params["pid"];
        $gtype = $params["gtype"];
        if ($gtype == Gacha::TYPE_EXTRA) {
            $grow = $params["grow"];
        }
        if (isset($params["r"]) && $params["r"] >= 2) {
            $bm = (int)$this->decode_params["bm"];
            $rk = (int)$this->decode_params["rk"];
            $bm = $bm - ($rk & 0xFF);
        }
        $single = 0;
        if (isset($params["s"]) && $params["s"] == 1) {
            $single = 1; // ガチャは1回.
        }

        // #PADC# ----------begin----------
        $discount_id = 0; // 初回割引データID
        if (isset($params["dis_id"])) {
            $discount_id = $params["dis_id"];
        }
        // #PADC# ----------end----------

        // #PADC# ----------begin----------
        $token = Tencent_MsdkApi::checkToken($params);
        if (!$token) {
            return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
        }

        // #PADC_DY# 增加购买扫荡券的结果返回
        list($result_pieces, $get_cards, $result_get_pieces, $gold, $fripnt, $gacha_bonus) = Gacha::play($user_id, $bm, $gtype, $single, $grow, $token, $discount_id);
        if ($result_pieces) {
            $resp_code = RespCode::SUCCESS;
            $result_pieces_str = UserPiece::arrangeColumns($result_pieces);
            $get_cards_str = GetUserCards::arrangeColumns($get_cards);

            //QQ report score
            if (!empty($get_cards_str)) {
                User::reportUserCardNum($user_id, $token['access_token']);
            }
        }

        // #PADC# ミッションクリア確認
        list($clear_mission_count, $clear_mission_ids) = UserMission::checkClearMissionTypes($user_id, array(
            Mission::CONDITION_TYPE_BOOK_COUNT,
            Mission::CONDITION_TYPE_DAILY_GACHA_FRIEND,
            Mission::CONDITION_TYPE_DAILY_GACHA_GOLD,
        ));

        //新手嘉年华：钻石扭蛋
        if($gtype != Gacha::TYPE_TUTORIAL){
            //不是友情扭蛋，或者是额外扭蛋但不是友情扭蛋
            if($gtype != Gacha::TYPE_FRIEND || ($gtype == Gacha::TYPE_EXTRA && $gtype != ExtraGacha::TYPE_FRIEND)){
                UserCarnivalInfo::carnivalMissionCheck($user_id,CarnivalPrize::CONDITION_TYPE_DAILY_GACHA_GOLD);
            }
        }
        if(GameConstant::getParam("GachaLimitSwitch")){
           //开启限制
            $user_daily_counts =  UserDailyCounts::findBy(array("user_id"=>$user_id));
            if(!$user_daily_counts){
                $counts_ip = 0;
                $counts_normal = 0;
            }else{
                $counts_ip = $user_daily_counts->ip_daily_count;
                $counts_normal = $user_daily_counts->piece_daily_count;
            }
            $extra_count = GameConstant::getParam("GachaDailyPlayCounts") - $counts_ip;
            $normal_count = GameConstant::getParam("GachaDailyPlayCounts") - $counts_normal; 
        }else{
            //未开启限制
            $extra_count = "-1";
            $normal_count = "-1"; 
        }

        $response = array(
            'res' => $resp_code,
            'gold' => $gold,
            'fripnt' => $fripnt,
            'pieces' => $result_pieces_str,
            'get_pieces' => $result_get_pieces,
            'cards' => $get_cards_str,
            'ncm' => $clear_mission_count,
            'clear_mission_list' => $clear_mission_ids,
            // #PADC_DY# ----------begin----------
            'gacha_bonus' => isset($gacha_bonus) ? array("bonus_id" => $gacha_bonus->bonus_id,
                "piece_id" => $gacha_bonus->piece_id,
                "amount" => $gacha_bonus->amount,
            ) : null,
            // #PADC_DY# ----------end----------
            'extra_count'=>$extra_count,
            'normal_count'=>$normal_count
        );
        return json_encode($response);
        // #PADC# ----------end----------
    }

}
