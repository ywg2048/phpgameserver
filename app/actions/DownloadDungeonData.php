<?php

/**
 * 2. ダンジョンデータダウンロード
 */
class DownloadDungeonData extends BaseAction {

    // http://pad.localhost/api.php?action=download_dungeon_data&pid=1&sid=1
    const MEMCACHED_EXPIRE = 86400; // 24時間.
    // #PADC#
    const MAIL_RESPONSE = FALSE;
    const ENCRYPT_RESPONSE = FALSE;

    public function action($params) {
        $key = MasterCacheKey::getDownloadDungeonData();
        $value = apc_fetch($key);
        if (FALSE === $value) {
            $value = DownloadMasterData::find(DownloadMasterData::ID_DUNGEONS_VER2)->gzip_data;
            apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
        }
        return $value;
    }

    /**
     * Dungeonのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
     */
    public static function arrangeColumns($dungeons) {
        $mapper = array();
        foreach ($dungeons as $dungeon) {
            $arr = array();
            $arr['i'] = (int) $dungeon->id;
            $arr['n'] = $dungeon->name;
            $arr['a'] = (int) $dungeon->attr;
            $arr['t'] = (int) $dungeon->dtype;
            // #PADC# ----------begin----------
            $arr['k'] = (int) $dungeon->dkind; // SPダンジョン種類
            // #PADC# ----------end----------
            $arr['w'] = (int) $dungeon->dwday;
            if ((int) $dungeon->dsort != ($dungeon->id * 100)) {
                $arr['s'] = (int) $dungeon->dsort;
            }
            // #PADC# ----------begin----------
            $arr['padc_rf'] = (int) $dungeon->rankup_flag; // ランクアップフラグ
            $arr['padc_uf'] = (int) $dungeon->url_flag; // URLフラグ
            $arr['padc_sf'] = $dungeon->share_file; // share画像
            $arr['padc_rg'] = (int) $dungeon->reward_gold; // 初回クリア報酬の魔法石

            $dungeon_panel_id = Dungeon::DUNGEON_PANEL_NORMAL; // ダンジョン用パネル色ID
            // #PADC# ----------end----------
            $arr['f'] = array();
            foreach ($dungeon->floors as $floor) {
                $f = array();
                $f['i'] = (int) $floor->seq;
                $f['n'] = $floor->name;
                $f['s'] = (int) $floor->sta;
                $f['w'] = (int) $floor->waves;
                $f['e'] = (int) $floor->ext;
                if ($floor->sr != 0) {
                    $f['sr'] = (int) $floor->sr;
                }
                if ($floor->bgm1 != 0) {
                    $f['b1'] = (int) $floor->bgm1;
                }
                if ($floor->bgm2 != 0) {
                    $f['b2'] = (int) $floor->bgm2;
                }
                if ($floor->eflag != 0) {
                    $f['f'] = (int) $floor->eflag;
                }
                if ($floor->fr != 0) {
                    $f['r'] = (int) $floor->fr;
                }
                if ($floor->fr1 != 0) {
                    $f['r1'] = (int) $floor->fr1;
                }
                if ($floor->fr2 != 0) {
                    $f['r2'] = (int) $floor->fr2;
                }
                if ($floor->fr3 != 0) {
                    $f['r3'] = (int) $floor->fr3;
                }
                if ($floor->fr4 != 0) {
                    $f['r4'] = (int) $floor->fr4;
                }
                if ($floor->fr5 != 0) {
                    $f['r5'] = (int) $floor->fr5;
                }
                if ($floor->fr6 != 0) {
                    $f['r6'] = (int) $floor->fr6;
                }
                if ($floor->fr7 != 0) {
                    $f['r7'] = (int) $floor->fr7;
                }
                if ($floor->fr8 != 0) {
                    $f['r8'] = (int) $floor->fr8;
                }
                if ($floor->prev_dungeon_floor_id != 0) {
                    $f['d'] = ($floor->prev_dungeon_floor_id - ($floor->prev_dungeon_floor_id % 1000)) / 1000;
                    $f['l'] = $floor->prev_dungeon_floor_id % 1000;
                }
                if ($floor->start_at) {
                    $f['t'] = strftime("%y%m%d%H%M%S", strtotime($floor->start_at));
                }
                if ($floor->open_rank != 0) {
                    $f['or'] = (int) $floor->open_rank;
                }
                // #PADC# ----------begin----------
                if ($floor->open_clear_dungeon_cnt != 0) {
                    $f['padc_odcnt'] = (int) $floor->open_clear_dungeon_cnt;
                }
                if (isset($floor->padc_cids)) {
                    $f['padc_cids'] = $floor->padc_cids;
                }
                $f['padc_rt'] = (int) $floor->rticket; // 周回チケット必要数
                $f['padc_tp'] = (int) $floor->total_power; // 総合戦闘力
                // パネルの色指定
                $floor_panel_id = Dungeon::DUNGEON_PANEL_NORMAL;
                if ($floor->panel_id) {
                    $floor_panel_id = $floor->panel_id;
                } else {
                    // 制限が設定されているダンジョンか？
                    if ($floor->fr != 0 || $floor->eflag != 0) {
                        $floor_panel_id = Dungeon::DUNGEON_PANEL_LIMIT;
                    } else {
                        // 敵がスキルを使ってくるダンジョンか？
                        $_ext = sprintf("%010d", decbin($floor->ext));
                        $_checkext = substr($_ext, 2, 1);
                        if ($_checkext == 1) {
                            $floor_panel_id = Dungeon::DUNGEON_PANEL_SKILL;
                        }
                    }
                }
                $f['padc_pid'] = $floor_panel_id;

                // より優先度の高いIDが設置されている場合そちらを設定する
                if ($floor_panel_id > $dungeon_panel_id) {
                    $dungeon_panel_id = $floor_panel_id;
                }
                // #PADC# ----------end----------
                
                $f['s3rt'] = (int) $floor->star3_required_turn; // #PADC_DY# 获得三星需要回合数
                $f['dmpt'] = (int) $floor->daily_max_times; // #PADC_DY# 单日最大次数
                $floor_recovery_gold = GameConstant::getParam('FloorRecoveryGold');
                $f['dmrt'] = count($floor_recovery_gold); // #PADC_DY# 单日最大恢复次数
                $f['drrg'] = $floor_recovery_gold; // #PADC_DY# 每次回复需要魔法石数量
                $f['h_on'] = (int) $floor->h_on; // #PADC_DY# 转珠提示是否启用
                $f['h_vip'] = (int) $floor->h_vip; // #PADC_DY# 启用转珠提示要求的最低vip等级

                $arr['f'][] = $f;
            }

            // #PADC_DY# 三星奖励配置数据 ----------begin----------
            $arr['bonus'] = array();
            foreach ($dungeon->bonus as $bonus) {
                $arr['bonus'][] = array(
                    'id' => $bonus->id,
                    'step' => (int) $bonus->step,
                    'required_star' => (int) $bonus->required_star,
                    'bonus_id1' => (int) $bonus->bonus_id1,
                    'amount1' => (int) $bonus->amount1,
                    'piece_id1' => (int) $bonus->piece_id1,
                    'bonus_id2' => (int) $bonus->bonus_id2,
                    'amount2' => (int) $bonus->amount2,
                    'piece_id2' => (int) $bonus->piece_id2,
                    'bonus_id3' => (int) $bonus->bonus_id3,
                    'amount3' => (int) $bonus->amount3,
                    'piece_id3' => (int) $bonus->piece_id3
                );
            }
            // #PADC_DY# -----------end-----------

            // #PADC# ----------begin----------
            $arr['padc_pid'] = $dungeon_panel_id; // ダンジョンのパネル色IDをセット
            $arr['padc_p'] = (int) $dungeon->padc_p;
            // #PADC# ----------end----------
            $mapper[] = $arr;
        }
        return $mapper;
    }

}
