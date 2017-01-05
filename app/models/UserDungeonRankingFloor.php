<?php

/**
 * ユーザのダンジョン/フロア攻略状況
 */
class UserRankingDungeonFloor extends BaseModel {

    const TABLE_NAME = "user_ranking_dungeon_floors";

    protected static $columns = array(
        'user_id',
        'dungeon_id',
        'dungeon_floor_id',
        'first_played_at',
        'cleared_at',
        'cm1_first_played_at',
        'cm1_cleared_at',
        'cm2_first_played_at',
        'cm2_cleared_at',
        // #PADC# ----------begin----------
        'daily_cleared_at',
        // #PADC# ----------end----------
        'max_star', // #PADC_DY# 三星数
        'daily_first_played_at', // #PADC_DY# 当日首次潜入时间
        'daily_played_times', // #PADC_DY# 当日潜入次数
        'daily_recovered_times', // #PADC_DY# 当日恢复次数
    );

    const INITIAL_DUNGEON_ID = 1;
    const INITIAL_DUNGEON_FLOOR_ID = 1001; // 潜入可能な状態で登録
    // #PADC# ----------begin----------
    const DUNGEON_FLOOR_CLASS = 'RankingDungeonFloor';

    // #PADC# ----------end----------

    /**
     * ゲーム開始時から潜入可能なダンジョン/フロアデータを作成する.
     * 二重作成の場合はDB制約違反の例外を返す.
     */
    public static function createDefaultUserDungeonFloor($user_id, $pdo) {
        // #PADC# ----------begin----------
        // CBT4のチュートリアル改修のため、初期ダンジョンの潜入可能状態を修正
        // #PADC# 継承先のクラスが生成されるように、UserDungeonFloorをstaticに変更。
        $u = new static();
        $u->user_id = $user_id;
        $u->dungeon_id = static::INITIAL_DUNGEON_ID;
        $u->dungeon_floor_id = static::INITIAL_DUNGEON_FLOOR_ID;
        $u->create($pdo);
        // #PADC# ----------end----------
        return $u;
    }

    /**
     * 次フロアの開放（新規ダンジョンが追加されたとき用）.
     * GetUserDungeonFloorsから呼ばれる.
     * #PADC#
     * フロアの解放条件にダンジョンクリア数も追加されたので対応
     */
    public static function getOpenFloor($user, $pdo = null) {
        // #PADC# ----------begin----------
        $user_id = $user->id;
        if ($pdo == null) {
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }
        // MY : 継承を考慮して、UserDungeonFloorをstaticに変更。
        $user_dungeon_floors = static::findAllBy(array("user_id" => $user_id), "dungeon_id ASC", null, $pdo);
        $enabled_floors = array();
        $cleared_floors = array();
        foreach ($user_dungeon_floors as $udf) {
            $enabled_floors[] = $udf->dungeon_floor_id;
            if ($udf->cleared_at) {
                $cleared_floors[] = $udf->dungeon_floor_id;
            }
        }

        // 全フロアデータ取得
        // #PADC# 継承先でアクセスするDBを変更できるように、DungeonFloorを動的クラスに変更。
        $dungeon_floor_class = static::DUNGEON_FLOOR_CLASS;
        $dungeon_floors = $dungeon_floor_class::getAllForMemcache();
        foreach ($dungeon_floors as $df) {
            $floor_id = $df->id;
            if (in_array($floor_id, $enabled_floors)) {
                continue;
            } else {
                // #PADC_DY# ----------begin----------
                $prev_floor_id = $df->prev_dungeon_floor_id;
                $open_cnt = $df->open_rank;
                // フロアの開放条件を満たしているか.（「解放条件のダンジョンフロアIDが0、または設定されているダンジョンフロアをクリアしている」かつ「解放に必要なダンジョンクリア数以上クリアしている場合」
                if (($prev_floor_id == 0 || ($prev_floor_id > 0 && in_array($prev_floor_id, $cleared_floors))) && $user->lv >= $open_cnt) {
                    // フロアが未開放の場合フロア開放.
                    // #PADC# 継承を考慮して、UserDungeonFloorをstaticに変更。
                    $user_dungeon_floor = static::enable($user_id, floor($floor_id / 1000), $floor_id, $pdo);
                    $user_dungeon_floors[] = $user_dungeon_floor;
                }
                // #PADC_DY# ----------end----------
            }
        }
        // #PADC# ----------end----------

        return $user_dungeon_floors;
    }

    /**
     * 指定のダンジョンフロアを解放する.
     * 作成した、または作成済みのUserDungeonFloorオブジェクトを返す.
     */
    public static function enable($user_id, $dungeon_id, $dungeon_floor_id, $pdo = null) {
        if ($pdo == null) {
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }
        // #PADC# 継承を考慮して、UserDungeonFloorをstaticに変更。
        $user_dungeon_floor = static::findBy(array(
                    "user_id" => $user_id,
                    "dungeon_id" => $dungeon_id,
                    "dungeon_floor_id" => $dungeon_floor_id,
                        ), $pdo, FALSE);
        if (empty($user_dungeon_floor)) {
            // #PADC# 継承を考慮して、UserDungeonFloorをstaticに変更。
            $user_dungeon_floor = new static();
            $user_dungeon_floor->user_id = $user_id;
            $user_dungeon_floor->dungeon_id = $dungeon_id;
            $user_dungeon_floor->dungeon_floor_id = $dungeon_floor_id;
            $user_dungeon_floor->first_played_at = null;
            $user_dungeon_floor->cm1_first_played_at = null;
            $user_dungeon_floor->cm1_cleared_at = null;
            $user_dungeon_floor->cm2_first_played_at = null;
            $user_dungeon_floor->cm2_cleared_at = null;
            $user_dungeon_floor->cleared_at = null;
            $user_dungeon_floor->daily_cleared_at = null;
            $user_dungeon_floor->max_star = 0; // #PADC_DY# 三星数初始化
            $user_dungeon_floor->daily_first_played_at = null; // #PADC_DY# 单日第一次潜入时间
            $user_dungeon_floor->daily_played_times = 0; // #PADC_DY# 当日潜入次数
            $user_dungeon_floor->daily_recovered_times = 0; // #PADC_DY# 当日恢复次数
            $user_dungeon_floor->create($pdo);
        }
        return $user_dungeon_floor;
    }

    /**
     * 指定したダンジョンでクリア済みのフロアを返す
     */
    public static function findCleared($user_id, $dungeon_id, $cm, $pdo = null) {
        if ($pdo == null) {
            $pdo = Env::getDbConnectionForUserRead($user_id);
        }
        $sql = "SELECT * FROM " . static::TABLE_NAME . " ";
        if ($cm == UserDungeon::CM_ALL_FRIEND) {
            // チャレンジモード(全員フレンドチャレンジ).
            $sql.= " WHERE user_id = ? AND dungeon_id = ? AND cm1_cleared_at IS NOT NULL";
        } elseif ($cm == UserDungeon::CM_NOT_USE_HELPER) {
            // チャレンジモード(助っ人無しチャレンジ).
            $sql.= " WHERE user_id = ? AND dungeon_id = ? AND cm2_cleared_at IS NOT NULL";
        } else {
            // ノーマルモード.
            $sql.= " WHERE user_id = ? AND dungeon_id = ? AND cleared_at IS NOT NULL";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $dungeon_id);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
        return $records;
    }

    // データスナップショット作成
    public static function getsnapshots($user_id, $log_date) {
        $pdo = Env::getDbConnectionForUserRead($user_id);
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE user_id = ?";
        $bind_param = array($user_id);
        list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
        $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $snapshot_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH . Env::ENV . "_udfloor_snapshot.log");
        $snapshot_format = '%message%' . PHP_EOL;
        $snapshot_formatter = new Zend_Log_Formatter_Simple($snapshot_format);
        $snapshot_writer->setFormatter($snapshot_formatter);
        $snapshot_logger = new Zend_Log($snapshot_writer);
        foreach ($values as $value) {
            $value = preg_replace('/"/', '""', $value);
            $snapshot_logger->log($log_date . "," . implode(",", $value), Zend_Log::DEBUG);
        }
    }

    /**
     * #PADC#
     * クリア済みのダンジョンフロアを返す
     */
    public static function getCleared($user_id, $pdo, $use_cache = TRUE) {
        $user_dungeon_floors = FALSE;
        // #PADC# 継承先でのキャッシュを変更するため、可変関数呼び出しに変更。
        $cache_method = 'getUserClear' . static::DUNGEON_FLOOR_CLASS . 's';
        $key = CacheKey::$cache_method($user_id);
        if ($use_cache) {
            // #PADC# memcache→redis
            $rRedis = Env::getRedisForUserRead();
            $user_dungeon_floors = $rRedis->get($key);
        }
        if ($user_dungeon_floors == FALSE) {
            $sql = "SELECT * FROM " . static::TABLE_NAME . " " . " WHERE user_id = ? AND cleared_at IS NOT NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            $user_dungeon_floors = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

            // ダンジョンクリア処理中に複数回呼ばれるためキャッシュに残す
            // 1回のダンジョンクリア処理中に保持できればいいので10秒だけキャッシュ
            // 次のダンジョンクリア時にはキャッシュは消えていることを想定している
            $redis = Env::getRedisForUser();
            $redis->set($key, $user_dungeon_floors, 10);
        }
        return $user_dungeon_floors;
    }
    
    /**
     * #PADC_DY#
     * 获取剩余潜入次数
     */
    public function getLeftPlayingTimes() {
        $dungeon_floor = DungeonFloor::get($this->dungeon_floor_id);
        if($dungeon_floor) {
            if(empty($this->daily_first_played_at) || !static::isSameDay_AM4(static::strToTime($this->daily_first_played_at), time())) {
                return (int) $dungeon_floor->daily_max_times;
            }
            
            $daily_left_times = (int) $dungeon_floor->daily_max_times * (1 + (int) $this->daily_recovered_times) - (int) $this->daily_played_times;
            if($daily_left_times >= 0) {
                return $daily_left_times;
            }
        }
        
        return 0;
    }

    /**
     * #PADC_DY#
     * 重置每日潜入次数
     */
    public static function resetDailyPlayTimes($user_id, $pdo) {
        $sql = "UPDATE " . static::TABLE_NAME . " SET daily_first_played_at = null,daily_played_times = 0,daily_recovered_times = 0 WHERE user_id = ?";
        return self::prepare_execute($sql, array($user_id), $pdo);
    }

}
