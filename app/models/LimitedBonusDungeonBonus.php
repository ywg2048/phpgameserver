<?php
/**
 * 時間限定ボーナス.
 */

class LimitedBonusDungeonBonus extends BaseMasterModel {
  const TABLE_NAME = "limited_bonuses_dungeon_bonus";
  const VER_KEY_GROUP = "lim";
  const MEMCACHED_EXPIRE = 3600; // 1時間.

  protected static $columns = array(
    'id',
    'day',
    'start_hour',
    'end_hour',
    'dungeon_id',
    'bonus_type',
    'args',
    'area',
  );

  public static $areas = array(
    'JP' => 1,
    'HT' => 2,
  );

  /**
   * クライアントに送信する時間限定ボーナス（ダンジョンボーナス）のリストを返す.
   */
  public static function getDungeonBonus() {
    $key = MasterCacheKey::getDungeonBonusLimitedBonusesDungeonBonusKey();
    $objs = apc_fetch($key);
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonusDungeonBonus::TABLE_NAME . " WHERE ";
      $sql .= " day in (?, ?) ";
      $sql .= " ORDER BY id ASC ";
      $stmt = $pdo->prepare($sql);
      $now = time();
      // とりあえず24時間先まで取得
      $now_day = date("N", $now);
      $next_day = date("N", $now + 86400);
      $values = array($now_day, $next_day,);
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
      foreach ($objs as $obj) {
        if ($now_day == $obj->day) {
          $obj->begin_at = date("Y-m-d", $now) . sprintf("%02d:00:00", $obj->start_hour);
          $obj->finish_at = $obj->end_hour == 0 ? date("Y-m-d 00:00:00", $now + 86400) : date("Y-m-d", $now) . sprintf(" %02d:00:00", $obj->end_hour);
        } else if ($next_day == $obj->day) {
          $obj->begin_at = date("Y-m-d", ($now + 86400)) . sprintf("%02d:00:00", $obj->start_hour);
          $obj->finish_at = $obj->end_hour == 0 ? date("Y-m-d 00:00:00", strtotime($obj->begin_at) + 86400) : date("Y-m-d", strtotime($obj->begin_at)) . sprintf(" %02d:00:00", $obj->end_hour);
        }
      }
      if(is_array($objs)) {
        apc_store($key, $objs, static::MEMCACHED_EXPIRE + static::add_apc_expire());
      }
    }
    return self::filterServiceArea($objs);
  }

  /**
   * 現在有効または指定時間内に有効となる時限ボーナスの配列を返す.
   * 現在有効なものを取得するときは、これを直接呼ばず、getAllActive*() を使うこと.
   */
  public static function getAllActiveWithMargin() {
    $key = MasterCacheKey::getAllLimitedBonusDungeonBonusWithMargin();
    $objs = apc_fetch($key);
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonusDungeonBonus::TABLE_NAME . " WHERE day in (?, ?) ORDER BY id ASC";
      // 余裕を見て、memcache expire 2倍秒前からキャッシュに入れておく.
      $now = time();
      $now_day = date("N", $now);
      $next_day = date("N", $now + 86400);
      $values = array($now_day, $next_day,);
      $stmt = $pdo->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
      foreach ($objs as $obj) {
        if ($now_day == $obj->day) {
          $obj->begin_at = date("Y-m-d", $now) . sprintf("%02d:00:00", $obj->start_hour);
          $obj->finish_at = $obj->end_hour == 0 ? date("Y-m-d 00:00:00", $now + 86400) : date("Y-m-d", $now) . sprintf(" %02d:00:00", $obj->end_hour);
        } else if ($next_day == $obj->day) {
          $obj->begin_at = date("Y-m-d", ($now + 86400)) . sprintf("%02d:00:00", $obj->start_hour);
          $obj->finish_at = $obj->end_hour == 0 ? date("Y-m-d 00:00:00", strtotime($obj->begin_at) + 86400) : date("Y-m-d", strtotime($obj->begin_at)) . sprintf(" %02d:00:00", $obj->end_hour);
        }
      }
      if(is_array($objs)) {
        apc_store($key, $objs, static::MEMCACHED_EXPIRE + static::add_apc_expire());
      }
    }
    return self::filterServiceArea($objs);
  }

  /**
   * サービスエリアでフィルタをかける
   */
  public static function filterServiceArea($objs) {
    if (defined('Env::SERVICE_AREA')) {
      $values = array();
      foreach ($objs as $obj) {
        if (empty($obj->area) || $obj->area == self::$areas[Env::SERVICE_AREA]) {
          $values[] = $obj;
        }
      }
      return $values;
    }
    return $objs;
  }

  /**
   * 指定のダンジョン潜入時に考慮すべきすべての時間限定ボーナスを返す.
   */
  public static function getActiveForSneakDungeon($dungeon) {
    $bonuses = LimitedBonusDungeonBonus::getAllActiveWithMargin();
    $active_bonuses = array();
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->dungeon_id == $dungeon->id) {
        // LimitedBonusを偽造する
        $limited_bonus = new LimitedBonus();
        $limited_bonus->begin_at = $bonus->begin_at;
        $limited_bonus->finish_at = $bonus->finish_at;
        $limited_bonus->dungeon_id = $bonus->dungeon_id;
        $limited_bonus->dungeon_floor_id = NULL;
        $limited_bonus->bonus_type = $bonus->bonus_type;
        $limited_bonus->args = $bonus->args;
        $limited_bonus->target_id = NULL;
        $limited_bonus->amount = NULL;
        $limited_bonus->nm_eggprob = NULL;
        $active_bonuses[] = $limited_bonus;
      }
    }
    return $active_bonuses;
  }


  /**
   * このボーナスが適用時間内である場合に限りTRUEを返す.
   */
  public function checkEnabled() {
    $now = time();
    if(LimitedBonusDungeonBonus::strToTime($this->begin_at) <= $now && LimitedBonusDungeonBonus::strToTime($this->finish_at) >= $now) {
      return TRUE;
    }
    return FALSE;
  }

}
