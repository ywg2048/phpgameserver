<?php

/**
 * フロア.
 */
class DungeonFloor extends BaseMasterModel {

    const TABLE_NAME = "dungeon_floors";
    const VER_KEY_GROUP = "dung";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'dungeon_id',
        'seq',
        'name',
        // #PADC# ----------begin----------
        'panel_id', // #PADC# フロア看板ID
        // #PADC# ----------end----------
        'diff',
        'sta',
        'waves',
        // #PADC# ----------begin----------
        'total_power', // #PADC# 総合戦闘力
        // #PADC# ----------end----------
        'ext',
        'sr',
        'last',
        'prev_dungeon_floor_id',
        // #PADC# ----------begin----------
        'rticket', // PADC版追加 周回クリア必要チケット数
        // #PADC# ----------end----------
        'bgm1',
        'bgm2',
        'eflag',
        'fr',
        'fr1',
        'fr2',
        'fr3',
        'fr4',
        'fr5',
        'fr6',
        'fr7',
        'fr8',
        'sort',
        'start_at',
        'open_rank',
        // #PADC# ----------begin----------
        'open_clear_dungeon_cnt', // PADC版追加 解放クリアダンジョン数
        // #PADC# ----------end----------
        'star3_required_turn', // #PADC_DY# 最低三星星数
		'daily_max_times', // #PADC_DY# 最大潜入次数
		'h_on', // #PADC_DY# 转珠提示是否开启
		'h_vip', // 转珠提示开启的VIP等级，0为不需要VIP也能开启
    );

    /**
     * 指定されたUserがこのフロアに潜入可能なスタミナを有していればTRUEを返す.
     */
    public function checkStamina($user, $active_bonuses) {
        if ($user->getStamina() >= $this->getStaminaCost($active_bonuses)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * このフロアに潜入するときに消費するスタミナを返す.
     */
    public function getStaminaCost($active_bonuses) {
        $coeff = 10000;
        if (is_array($active_bonuses)) {
            // スタミナ割引ボーナスがあれば、係数を更新する.
            $stamina_bonus = LimitedBonus::getActiveStaminaDungeon($this->dungeon_id, $active_bonuses);
            if ($stamina_bonus) {
                $coeff = $stamina_bonus->args;
            }
        } else {
            throw new PadException(RespCode::UNKNOWN_ERROR, "active bonuses is required");
        }
        return round($this->sta * $coeff / 10000);
    }

    /**
     * 指定されたダンジョンの最初のフロア(seq=1)のDungeonFloorオブジェクトを返す.
     * 存在しなければ例外.
     */
    public static function getFirstDungeonFloor($dungeon) {
        // #PADC# DungeonFloorからstaticへ。
        $dungeon_floor = static::getBy(array("dungeon_id" => $dungeon->id, "seq" => 1));
        if (empty($dungeon_floor)) {
            throw new PadException(RespCode::UNKNOWN_ERROR, "fatal: dungeon_floors data is invalid. first floor is not found.");
        }
        return $dungeon_floor;
    }

    /**
     * このフロアの次に該当する(クリア時に開放する)DungeonFloorのリストを返す.
     */
    public function getAllNextFloors() {
        $params = array("prev_dungeon_floor_id" => $this->id);
        // #PADC# DungeonFloorからstaticへ。
        return static::getAllBy($params);
    }

    /**
     * #PADC# 関数の説明が間違っているので修正
     * prev_dungeon_floor_id に値が入っているDungeonFloorを
     * id => prev_dungeon_floor_id の連想配列のリストにして返す
     */
    public static function getPrevFloors() {
        // #PADC# ----------begin----------
        // MY : 継承したクラスのキャッシュにアクセスするため、可変関数で呼び出す。
        $prev_key_method = 'get' . get_called_class() . 'sPrevKey';
        $key = MasterCacheKey::$prev_key_method();
        // #PADC# ----------end----------
        $value = apc_fetch($key);
        if (FALSE === $value) {
            // #PADC# DungeonFloorからstaticへ。
            $dungeon_floors = static::findAllBy(array(), null, null);
            $value = array();
            foreach ($dungeon_floors as $df) {
                if ($df->prev_dungeon_floor_id > 0) {
                    $value[$df->id] = $df->prev_dungeon_floor_id;
                }
            }
            // #PADC# DungeonFloorからstaticへ。
            apc_store($key, $value, static::MEMCACHED_EXPIRE + static::add_apc_expire());
        }
        return $value;
    }

    /**
     * #PADC#
     * 指定したダンジョンクリア数とクリアダンジョンフロアIDが解放条件となっているDungeonFloorのリストを返す.
     * ※値が一致するものだけを検出する
     */
    // #PADC_DY# ----------begin----------
    // 参数修改为user_clear_dungeon_cnt ===> user_lv
    public static function getNextFloorsByParams($before_user_lv, $lv, $clear_dungeon_floor_ids, $pdo = null) {
        if ($pdo == null) {
            $pdo = Env::getDbConnectionForShare();
        }
         $sql = 'SELECT * FROM ' . static::TABLE_NAME . ' WHERE open_rank > ? AND open_rank <= ? AND prev_dungeon_floor_id IN (' . str_repeat('?,', count($clear_dungeon_floor_ids) - 1) . '?) ORDER BY id ASC';
        $values = array($before_user_lv, $lv);
        $values = array_merge($values, $clear_dungeon_floor_ids);
        $stmt = $pdo->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
        $stmt->execute($values);
        $next_floors = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
        return $next_floors;
    }
    // #PADC_DY# ----------end----------

}
