<?php

/**
 * X. ダンジョンフロアプレイ状況(ダンジョンフラグ)取得.
 * 隠しAPI.
 */
class GetUserDungeonFloors extends BaseAction {

    // http://pad.localhost/api.php?action=get_user_dungeon_floors&pid=1&sid=1
    public function action($params) {
        $result = RespCode::UNKNOWN_ERROR;
        $user_dungeon_floors = array();
        if (Env::ENV !== "production") {
            $user = User::find($params["pid"]);
            if ($user) {
                $user_dungeon_floors = GetUserDungeonFloors::getActiveUserDungeonFloors($user->id);
                $result = RespCode::SUCCESS;
            }
        }
        return json_encode(array('res' => $result, 'dung' => $user_dungeon_floors));
    }

    /**
     * ダンジョンフラグデータを取得.
     * API.31 データ取得から呼ばれることを想定.
     * #PADC#
     * getOpenFloor()に渡す引数を変更したので合わせて対応
     */
    public static function getActiveUserDungeonFloors($user, $pdo = null) {
        // 開放済みのデータを全取得.
        $user_dungeon_floors = UserDungeonFloor::getOpenFloor($user);
        return GetUserDungeonFloors::arrangeColumns($user_dungeon_floors);
    }

    /**
     * ダンジョンフラグデータを取得（コンパクト版）.
     * API.31 データ取得から呼ばれることを想定.
     * #PADC#
     * getOpenFloor()に渡す引数を変更したので合わせて対応
     */
    public static function getActiveUserDungeonFloorsCompact($user, $revision = null, $pdo) {
        // 開放済みのデータを全取得.
        $user_dungeon_floors = UserDungeonFloor::getOpenFloor($user, $pdo = null);

        // #PADC# ----------begin----------
        $now = time();
        // #PADC# ----------end----------
        
        $max_recovery_times = count(GameConstant::getParam('FloorRecoveryGold')); // #PADC_DY# 最大恢复次数
        
        $mapper = array();
        foreach ($user_dungeon_floors as $udf) {
            $dung = (int) $udf->dungeon_id;
            $floor = (int) $udf->dungeon_floor_id % 1000;
            $star = (int) $udf->max_star; // #PADC_DY# 三星星数
            
            // #PADC_DY# ----------begin----------
            $left_playing_times = $udf->getLeftPlayingTimes(); // #PADC_DY# 剩余次数
            $left_recovery_times = $max_recovery_times - (int) $udf->daily_recovered_times; // #PADC_DY# 剩余恢复次数
            // #PADC_DY# ----------end----------

            if ($revision >= 1) {
                // APIリビジョンが1以上はビット圧縮.
                $bit = 0;
                $bit += ($udf->first_played_at) ? 0 : 1;
                $bit += ($udf->cleared_at) ? 2 : 0;
                $bit += ($udf->cm1_first_played_at) ? 0 : 4;
                $bit += ($udf->cm1_cleared_at) ? 8 : 0;
                $bit += ($udf->cm2_first_played_at) ? 0 : 16;
                $bit += ($udf->cm2_cleared_at) ? 32 : 0;
                // #PADC# ----------begin----------
                // デイリークリアフラグ追加（今日クリアしているかどうか）
                $bit += (BaseModel::isSameDay_AM4($now, strtotime($udf->daily_cleared_at))) ? 64 : 0;
                // #PADC# ----------end----------

                $mapper[] = array($dung, $floor, $bit, $star, $left_playing_times, $left_recovery_times); // #PADC_DY# 增加三星星数、剩余次数、剩余恢复次数
            } else {
                $new = ($udf->first_played_at) ? 0 : 1;
                $cleared = ($udf->cleared_at) ? 1 : 0;
                $mapper[] = array($dung, $floor, $new, $cleared, $star, $left_playing_times, $left_recovery_times); // #PADC_DY# 增加三星星数、剩余次数、剩余恢复次数
            }
        }

        // 一度きりダンジョン表示されないユーザ救済.
        // https://61.215.220.70/redmine-pad/issues/3140
        /*
          131 天上からの贈り物
          132 バレンタインダンジョン
          133 モーグリの贈り物
          135 未知の来訪者
          136 ホワイトデーダンジョン
          137 国外版プレゼントダンジョン1
          138 国外版プレゼントダンジョン2
          139 国外版プレゼントダンジョン3
          140 ファミ通App　コラボ
          174 天上からの贈り物
          175 天上からの贈り物
          177 聖夜の贈り物
          178 お正月の祠
          179 お正月の祠
          180 お正月の祠
          190 七夕の日ダンジョン
          329 セブン-イレブン　コラボ
          330 熱血パズドラ部！200回記念
          336 輝かしき女王
          351 ガンホーサンバ　コラボ
          352 たまドラ発見！
          353 かがやきの大広間
         */
        $once_dungeons = array(131, 132, 133, 135, 136, 137, 138, 139, 140, 174, 175, 177, 178, 179, 180, 190, 329, 330, 336, 351, 352, 353);
        foreach ($once_dungeons as $once_dunegon_id) {
            $dung = $once_dunegon_id;
            $floor = 1;
            $star = 0; // #PADC_DY# 三星星数
            $left_playing_times = 0; // #PADC_DY# 剩余次数
            $left_recovery_times = 0; // #PADC_DY# 剩余恢复次数

            $check_flg = false;
            foreach ($user_dungeon_floors as $udf) {
                if ($once_dunegon_id == $udf->dungeon_id) {
                    $check_flg = true;
                    $star = (int) $udf->max_star; // #PADC_DY# 三星星数
                    
                    // #PADC_DY# ----------begin----------
                    $left_playing_times = $udf->getLeftPlayingTimes(); // #PADC_DY# 剩余次数
                    $left_recovery_times = $max_recovery_times - (int) $udf->daily_recovered_times; // #PADC_DY# 剩余恢复次数
                    // #PADC_DY# ----------end----------
                    
                    break;
                }
            }
            if ($check_flg == false) {
                // 一度きりダンジョンフロアが未開放の場合フロア開放.
                if ($revision >= 1) {
                    // APIリビジョンが1以上はビット圧縮.
                    // 0x01:New
                    // 0x04:全員フレンドチャレンジ NEW (未挑戦)
                    // 0x10:助っ人なしチャレンジ NEW (未挑戦)
                    $bit = 21; //  = 1 + 4 + 16.
                    $mapper[] = array($dung, $floor, $bit, $star, $left_playing_times, $left_recovery_times); // #PADC_DY# 增加三星星数、剩余次数、剩余恢复次数
                } else {
                    $new = 1;
                    $cleared = 0;
                    $mapper[] = array($dung, $floor, $new, $cleared, $star, $left_playing_times, $left_recovery_times); // #PADC_DY# 增加三星星数、剩余次数、剩余恢复次数
                }
            }
        }

        return $mapper;
    }


    /**
     * ダンジョンフラグデータを取得（コンパクト版）.
     * API.31 データ取得から呼ばれることを想定.
     * #PADC#
     * getOpenFloor()に渡す引数を変更したので合わせて対応
     */
    public static function getActiveUserRankingDungeonFloorsCompact($user, $revision = null, $pdo) {
        // 開放済みのデータを全取得.
        $user_dungeon_floors = UserRankingDungeonFloor::getOpenFloor($user, $pdo = null);

        // #PADC# ----------begin----------
        $now = time();
        // #PADC# ----------end----------
        
        $max_recovery_times = count(GameConstant::getParam('FloorRecoveryGold')); // #PADC_DY# 最大恢复次数
        
        $mapper = array();
        foreach ($user_dungeon_floors as $udf) {
            $dung = (int) $udf->dungeon_id;
            $floor = (int) $udf->dungeon_floor_id % 1000;
            $star = (int) $udf->max_star; // #PADC_DY# 三星星数
            
            // #PADC_DY# ----------begin----------
            $left_playing_times = $udf->getLeftPlayingTimes(); // #PADC_DY# 剩余次数
            $left_recovery_times = $max_recovery_times - (int) $udf->daily_recovered_times; // #PADC_DY# 剩余恢复次数
            // #PADC_DY# ----------end----------
            

            if ($revision >= 1) {
                // APIリビジョンが1以上はビット圧縮.
                $bit = 0;
                $bit += ($udf->first_played_at) ? 0 : 1;
                $bit += ($udf->cleared_at) ? 2 : 0;
                $bit += ($udf->cm1_first_played_at) ? 0 : 4;
                $bit += ($udf->cm1_cleared_at) ? 8 : 0;
                $bit += ($udf->cm2_first_played_at) ? 0 : 16;
                $bit += ($udf->cm2_cleared_at) ? 32 : 0;
                // #PADC# ----------begin----------
                // デイリークリアフラグ追加（今日クリアしているかどうか）
                $bit += (BaseModel::isSameDay_AM4($now, strtotime($udf->daily_cleared_at))) ? 64 : 0;
                // #PADC# ----------end----------

                $mapper[] = array($dung, $floor, $bit, $star, $left_playing_times, $left_recovery_times); // #PADC_DY# 增加三星星数、剩余次数、剩余恢复次数
            } else {
                $new = ($udf->first_played_at) ? 0 : 1;
                $cleared = ($udf->cleared_at) ? 1 : 0;
                $mapper[] = array($dung, $floor, $new, $cleared, $star, $left_playing_times, $left_recovery_times); // #PADC_DY# 增加三星星数、剩余次数、剩余恢复次数
            }
        }

        return $mapper;
    }

    /**
     * UserDungeonFloorのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
     */
    public static function arrangeColumns($user_dungeon_floors) {
        // マッパー関数. TODO チューニング..
        $mapper = function($user_dungeon_floor) {
            unset($user_dungeon_floor->id);
            unset($user_dungeon_floor->user_id);
            $user_dungeon_floor->dung = (int) $user_dungeon_floor->dungeon_id;
            unset($user_dungeon_floor->dungeon_id);
            $user_dungeon_floor->floor = (int) $user_dungeon_floor->dungeon_floor_id % 1000;
            unset($user_dungeon_floor->dungeon_floor_id);
            if ($user_dungeon_floor->first_played_at) {
                $user_dungeon_floor->new = 0;
            } else {
                $user_dungeon_floor->new = 1;
            }
            unset($user_dungeon_floor->first_played_at);
            if ($user_dungeon_floor->cleared_at) {
                $user_dungeon_floor->cleared = 1;
            } else {
                $user_dungeon_floor->cleared = 0;
            }
            unset($user_dungeon_floor->sneak_cnt);
            unset($user_dungeon_floor->clear_cnt);
            unset($user_dungeon_floor->cleared_at);
            unset($user_dungeon_floor->created_at);
            unset($user_dungeon_floor->updated_at);
            return $user_dungeon_floor;
        };
        return array_map($mapper, $user_dungeon_floors);
    }

}
