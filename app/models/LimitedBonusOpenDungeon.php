<?php
/**
 * 時間限定ボーナス.
 */

class LimitedBonusOpenDungeon extends BaseMasterModel {
  const TABLE_NAME = "limited_bonuses_open_dungeon";
  const VER_KEY_GROUP = "lim";
  const MEMCACHED_EXPIRE = 3600; // 1時間.

  protected static $columns = array(
    'id',
    'pattern',
    'begin_at',
    'finish_at',
    'area',
    'dungeon_id',
  );

  public static $areas = array(
    'JP' => 1,
    'HT' => 2,
  );

  /**
   * クライアントに送信する時間限定ボーナス（ダンジョン）のリストを返す.
   */
  public static function getDungeonOpen() {
    $key = MasterCacheKey::getDungeonOpenLimitedBonusesOpenDungeonKey();
    $objs = apc_fetch($key);
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonusOpenDungeon::TABLE_NAME . " WHERE ";
      $sql .= "begin_at <= ? AND ";
      $sql .= "finish_at >= ? ";
      $sql .= "ORDER BY id ASC";
      $stmt = $pdo->prepare($sql);
      $now = time();
      // とりあえず24時間(+キャッシュ時間)先まで取得
      $begin = date("Y-m-d H:i:s", $now + 86400 + static::MEMCACHED_EXPIRE);
      $end = date("Y-m-d H:i:s", $now);
      $values = array(
        $begin,
        $end,
      );
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
      if(is_array($objs)) {
        apc_store($key, $objs, static::MEMCACHED_EXPIRE - static::add_apc_expire());
      }
    }
    return self::filterServiceArea($objs);
  }

  /**
   * 現在有効または指定時間内に有効となる時限ボーナスの配列を返す.
   * 現在有効なものを取得するときは、これを直接呼ばず、getAllActive*() を使うこと.
   */
  public static function getAllActiveWithMargin() {
    $key = MasterCacheKey::getAllLimitedBonusOpenDungeonWithMargin();
    $objs = apc_fetch($key);
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonusOpenDungeon::TABLE_NAME . " WHERE begin_at <= ? AND finish_at >= ? ORDER BY id ASC";
      // 余裕を見て、memcache expire 2倍秒前からキャッシュに入れておく.
      $now = time();
      $values = array(LimitedBonusOpenDungeon::timeToStr($now + LimitedBonusOpenDungeon::MEMCACHED_EXPIRE*2), LimitedBonusOpenDungeon::timeToStr($now));
      $stmt = $pdo->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
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
    $bonuses = LimitedBonusOpenDungeon::getAllActiveWithMargin();
    $active_bonuses = array();
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->dungeon_id == $dungeon->id) {
        // LimitedBonusを偽造する
        $limited_bonus = new LimitedBonus();
        $limited_bonus->begin_at = $bonus->begin_at;
        $limited_bonus->finish_at = $bonus->finish_at;
        $limited_bonus->dungeon_id = $bonus->dungeon_id;
        $limited_bonus->dungeon_floor_id = NULL;
        $limited_bonus->bonus_type = 6;
        $limited_bonus->args = NULL;
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
    if(LimitedBonusOpenDungeon::strToTime($this->begin_at) <= $now && LimitedBonusOpenDungeon::strToTime($this->finish_at) >= $now) {
      return TRUE;
    }
    return FALSE;
  }

}
