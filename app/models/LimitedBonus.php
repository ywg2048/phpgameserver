<?php
/**
 * 時間限定ボーナス.
 */

class LimitedBonus extends BaseMasterModel {
  const TABLE_NAME = "limited_bonuses";
  const VER_KEY_GROUP = "lim";
  const MEMCACHED_EXPIRE = 3600; // 1時間.

  protected static $columns = array(
    'id',
    'begin_at',
    'finish_at',
    'dungeon_id',
    'dungeon_floor_id',
    'bonus_type',
    'args',
    'target_id',
    'amount',
    'nm_eggprob',
    'message',
    'area',
    // #PADC# ----------begin----------
    'drop_min',
    'drop_max',
  	'file',
    // #PADC# ----------end----------
  );

  const BONUS_TYPE_DUNG_EXPUP = 1; // 1:ボーナス：経験値
  const BONUS_TYPE_DUNG_COINUP = 2; // 2:ボーナス：コイン
  const BONUS_TYPE_DUNG_EGGUP = 3; // 3:ボーナス：卵確率
  const BONUS_TYPE_FLOOR_NOTORIOUS = 4; // 4:ノートリアス
  const BONUS_TYPE_DUNG_STAMINA = 5; // 5:スタミナ割引
  const BONUS_TYPE_DUNG_OPEN = 6; // 6:ステージ開放
  const BONUS_TYPE_COMPOSITION = 7; // 7:合成係数ボーナス
  const BONUS_TYPE_FRIEND_GACHA = 8; // 8:期間限定友情ガチャ
  const BONUS_TYPE_CHARGE_GACHA = 9; // 9:期間限定課金ガチャ
//  const BONUS_TYPE_GACHA_PRICE = 10; // 10:ガチャ値段
  const BONUS_TYPE_GOOD_EXCELLENT_UP = 11; // 11:大成功超成功確率アップ
  const BONUS_TYPE_DUNG_PLUSEGG = 12; // 12:ダンジョン＋確率設定
  const BONUS_TYPE_FRIEND_GACHA_PLUSEGG = 13; // 13:友情ガチャ＋確率設定
  const BONUS_TYPE_CHARGE_GACHA_PLUSEGG = 14; // 14:レアガチャ＋確率設定
  const BONUS_TYPE_PRESENT_PLUSEGG = 15; // 15:プレゼントガチャ＋確率設定
  const BONUS_TYPE_DUNG_PLUSEGG_UP = 16; // 16:ダンジョン＋確率倍増
  const BONUS_TYPE_SKILL_LV_UP = 17; // 17:スキルレベルアップ
  // #PADC# ----------begin----------
  const BONUS_TYPE_PREMIUM_GACHA = 1001;// 1001:期間限定プレミアムガチャ
  // #PADC# ----------end----------

  public static $areas = array(
    'JP' => 1,
    'HT' => 2,
  );
  
  /**
   * #PADC#
   * LimitedBonusのタイプ詳細を取得
   */
  public static function getLimiteBonusTypes()
  {
	$limitedBonusTypes  = array(
  		LimitedBonus::BONUS_TYPE_DUNG_EXPUP				=> 'ボーナス：経験値',
  		LimitedBonus::BONUS_TYPE_DUNG_COINUP			=> 'ボーナス：コイン',
  		LimitedBonus::BONUS_TYPE_DUNG_EGGUP				=> 'ボーナス：卵確率',
  		LimitedBonus::BONUS_TYPE_FLOOR_NOTORIOUS		=> 'ノートリアス',
  		LimitedBonus::BONUS_TYPE_DUNG_STAMINA			=> 'スタミナ割引',
  		LimitedBonus::BONUS_TYPE_DUNG_OPEN				=> 'ステージ開放',
  		LimitedBonus::BONUS_TYPE_COMPOSITION			=> '合成係数ボーナス',
  		LimitedBonus::BONUS_TYPE_FRIEND_GACHA			=> '期間限定友情ガチャ',
  		LimitedBonus::BONUS_TYPE_CHARGE_GACHA			=> '期間限定課金ガチャ',
  	//	LimitedBonus::BONUS_TYPE_GACHA_PRICE			=> 'ガチャ値段',
  		LimitedBonus::BONUS_TYPE_GOOD_EXCELLENT_UP		=> '大成功超成功確率アップ',
  		LimitedBonus::BONUS_TYPE_DUNG_PLUSEGG			=> 'ダンジョン＋確率設定',
  		LimitedBonus::BONUS_TYPE_FRIEND_GACHA_PLUSEGG	=> '友情ガチャ＋確率設定',
  		LimitedBonus::BONUS_TYPE_CHARGE_GACHA_PLUSEGG	=> 'レアガチャ＋確率設定',
  		LimitedBonus::BONUS_TYPE_PRESENT_PLUSEGG		=> 'プレゼントガチャ＋確率設定',
  		LimitedBonus::BONUS_TYPE_DUNG_PLUSEGG_UP		=> 'ダンジョン＋確率倍増',
  		LimitedBonus::BONUS_TYPE_SKILL_LV_UP			=> 'スキルレベルアップ',
  		LimitedBonus::BONUS_TYPE_PREMIUM_GACHA			=> '期間限定プレミアムガチャ',
  	);
	return $limitedBonusTypes;
  }

  /**
   * クライアントに送信する時間限定ボーナスのリストを返す.
   */
  public static function getAllActiveToday() {
    // キーに曜日を含めることで、日付が変わったときに取りなおしさせる.
    // 次の週が来る頃には期限切れなのでOK.
    $key = MasterCacheKey::getTodayAllLimitedBonusesKey(date("N"));
    $objs = apc_fetch($key);
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      // 必要なボーナスタイプを絞り込みする.
      $sql = "SELECT * FROM " . LimitedBonus::TABLE_NAME . " WHERE ";
      $sql .= "begin_at <= ? AND ";
      $sql .= "finish_at >= ? AND ";
      // #PADC# ----------end----------BONUS_TYPE_PREMIUM_GACHAを追加,BONUS_TYPE_FLOOR_NOTORIOUSを追加
      $sql .= "bonus_type IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";
      $sql .= "ORDER BY id ASC";
      $now = time();
      // とりあえず24時間(+キャッシュ時間)先まで取得
      $begin = date("Y-m-d H:i:s", $now + 86400 + static::MEMCACHED_EXPIRE);
      $end = date("Y-m-d H:i:s", $now);
      $values = array(
        $begin,
        $end,
        LimitedBonus::BONUS_TYPE_DUNG_EXPUP,
        LimitedBonus::BONUS_TYPE_DUNG_COINUP,
        LimitedBonus::BONUS_TYPE_DUNG_EGGUP,
        LimitedBonus::BONUS_TYPE_DUNG_STAMINA,
        LimitedBonus::BONUS_TYPE_COMPOSITION,
        LimitedBonus::BONUS_TYPE_FRIEND_GACHA,
        LimitedBonus::BONUS_TYPE_CHARGE_GACHA,
        LimitedBonus::BONUS_TYPE_GOOD_EXCELLENT_UP,
        LimitedBonus::BONUS_TYPE_DUNG_PLUSEGG,
        LimitedBonus::BONUS_TYPE_FRIEND_GACHA_PLUSEGG,
        LimitedBonus::BONUS_TYPE_CHARGE_GACHA_PLUSEGG,
        LimitedBonus::BONUS_TYPE_PRESENT_PLUSEGG,
        LimitedBonus::BONUS_TYPE_DUNG_PLUSEGG_UP,
        LimitedBonus::BONUS_TYPE_SKILL_LV_UP,
      	LimitedBonus::BONUS_TYPE_PREMIUM_GACHA,
        LimitedBonus::BONUS_TYPE_FLOOR_NOTORIOUS,
      );
      // #PADC# ----------end----------
      $stmt = $pdo->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
      if($objs) {
        apc_store($key, $objs, static::MEMCACHED_EXPIRE + static::add_apc_expire());
      }
    }
    return self::filterServiceArea($objs);
  }

  /**
   * クライアントに送信する時間限定ボーナス（ダンジョン）のリストを返す.
   */
  public static function getDungeonOpen() {
    $key = MasterCacheKey::getDungeonOpenLimitedBonusesKey();
    $objs = apc_fetch($key);
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonus::TABLE_NAME . " WHERE ";
      $sql .= "begin_at <= ? AND ";
      $sql .= "finish_at >= ? AND ";
      $sql .= "bonus_type = ? ";
      $sql .= "ORDER BY id ASC";
      $stmt = $pdo->prepare($sql);
      $now = time();
      // とりあえず24時間(+キャッシュ時間)先まで取得
      $begin = date("Y-m-d H:i:s", $now + 86400 + static::MEMCACHED_EXPIRE);
      $end = date("Y-m-d H:i:s", $now);
      $values = array(
        $begin,
        $end,
        LimitedBonus::BONUS_TYPE_DUNG_OPEN,
      );
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
      if($objs) {
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
    $key = MasterCacheKey::getAllLimitedBonusWithMargin();
    $objs = apc_fetch($key);
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonus::TABLE_NAME . " WHERE begin_at <= ? AND finish_at >= ? ORDER BY id ASC";
      // 余裕を見て、memcache expire 2倍秒前からキャッシュに入れておく.
      $now = time();
      $values = array(LimitedBonus::timeToStr($now + LimitedBonus::MEMCACHED_EXPIRE*2), LimitedBonus::timeToStr($now));
      $stmt = $pdo->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
      if($objs) {
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
   * 指定の期間内ボーナスリストから、
   * すべてのダンジョン向け時間限定ボーナス(クライアントが必要なもののみ)を返す.
   * 有効時間の判定はおこなわないので、active_bonusesには有効時間内のボーナスリストを渡すこと.
   */
  private static function getAllActiveForAllDungeonsForClient($active_bonuses) {
    $dungeon_bonuses = array();
    $bonus_types_for_dungeon = array(
      LimitedBonus::BONUS_TYPE_DUNG_EXPUP,
      LimitedBonus::BONUS_TYPE_DUNG_COINUP,
      LimitedBonus::BONUS_TYPE_DUNG_EGGUP,
    );
    foreach($active_bonuses as $bonus) {
      if(in_array($bonus->bonus_type, $bonus_types_for_dungeon)) {
        $dungeon_bonuses[] = $bonus;
      }
    }
    return $dungeon_bonuses;
  }

  /**
   * 指定の有効なボーナスリストから、
   * 指定ダンジョンに適用されるボーナス(クライアントが必要なもののみ)があれば返す.
   * 有効時間の判定はおこなわないので、active_bonusesには有効時間内のボーナスリストを渡すこと.
   */
  public static function getActiveForDungeonForClient($dungeon, $active_bonuses) {
    $dungeon_bonus = null;
    $bonus_types_for_client = array(
      LimitedBonus::BONUS_TYPE_DUNG_EXPUP,
      LimitedBonus::BONUS_TYPE_DUNG_COINUP,
      LimitedBonus::BONUS_TYPE_DUNG_EGGUP,
    );
    foreach($active_bonuses as $bonus) {
      if($bonus->dungeon_id == $dungeon->id && in_array($bonus->bonus_type, $bonus_types_for_client)) {
        $dungeon_bonus = $bonus;
        // 1件のみという前提. https://61.215.220.70/redmine-pad/issues/40
        break;
      }
    }
    return $dungeon_bonus;
  }

  /**
   * 指定の有効なボーナスリストから、
   * 指定ダンジョンフロアに出現しうるノトーリアスモンスターボーナスがあれば返す.
   * 有効時間の判定はおこなわないので、active_bonusesには指定ダンジョンかつ有効時間内のボーナスリストを渡すこと.
   */
  public static function getActiveNotoriousBonus($dungeon_floor_id, $active_bonuses) {
    $notorious_bonus = null;
    foreach($active_bonuses as $bonus) {
      if($bonus->bonus_type == LimitedBonus::BONUS_TYPE_FLOOR_NOTORIOUS && $bonus->dungeon_floor_id == $dungeon_floor_id) {
        $notorious_bonus = $bonus;
        // 1件のみという前提.
        break;
      }
    }
    return $notorious_bonus;
  }

  /**
   * 指定のダンジョン潜入時に考慮すべきすべての時間限定ボーナスを返す.
   */
  public static function getActiveForSneakDungeon($dungeon) {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonuses = array();
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->dungeon_id == $dungeon->id) {
        $active_bonuses[] = $bonus;
      }
    }
    return $active_bonuses;
  }

  /**
   * 現在有効なすべてのダンジョン開放ボーナスを返す.
   */
  public static function getAllActiveDungeons() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonuses = array();
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_OPEN) {
        $active_bonuses[] = $bonus;
      }
    }
    return $active_bonuses;
  }

  /**
   * 現在適用可能な友情ガチャボーナスを返す.
   * なければnullを返す. (=デフォルトのガチャを使用する)
   */
  public static function getActiveFriendGacha() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_FRIEND_GACHA) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }


  /**
   * 現在適用可能な課金ガチャボーナスを返す.
   * なければnullを返す. (=デフォルトのガチャを使用する)
   */
  public static function getActiveChargeGacha() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_CHARGE_GACHA) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 現在適用可能なプレミアム課金ガチャボーナスを返す.
   * なければnullを返す. (=デフォルトのガチャを使用する)
   */
  public static function getActivePremiumGacha() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_PREMIUM_GACHA) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }


  /**
   * 現在適用可能な合成係数ボーナスを返す.
   * なければnullを返す. (=経験値増ボーナスは無し)
   */
  public static function getActiveComposition() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_COMPOSITION) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }
  /**
   * 現在適用可能な大成功・超成功確立アップボーナスを返す.
   * なければnullを返す.
   */
  public static function getCompositeGoodExcellentUp() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_GOOD_EXCELLENT_UP) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 現在適用可能なスキルレベルアップボーナスを返す.
   * なければnullを返す.
   */
  public static function getActiveSkillLvUp() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_SKILL_LV_UP) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 現在適用可能なダンジョン＋確率設定を返す.
   * なければnullを返す.
   */
  public static function getActiveDungeonPlusEgg($dungeon_id) {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->dungeon_id == $dungeon_id && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_PLUSEGG) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 現在適用可能な友情ガチャ＋確率設定を返す.
   * なければnullを返す.
   */
  public static function getActiveFriendGachaPlusEgg() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_FRIEND_GACHA_PLUSEGG) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 現在適用可能なレアガチャ＋確率設定を返す.
   * なければnullを返す.
   */
  public static function getActiveChargeGachaPlusEgg() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_CHARGE_GACHA_PLUSEGG) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 現在適用可能なプレゼントガチャ＋確率設定を返す.
   * なければnullを返す.
   */
  public static function getActivePresentGachaPlusEgg() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_PRESENT_PLUSEGG) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 現在適用可能なダンジョン＋確率倍増を返す.
   * なければnullを返す.
   */
  public static function getActiveDungeonPlusEggUp($dungeon_id) {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->dungeon_id == $dungeon_id && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_PLUSEGG_UP) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }

  /**
   * 指定の有効なボーナスリストから、
   * 指定ダンジョンで適用できるダンジョン開放ボーナスがあれば返す.
   * 有効時間の判定はおこなわないので、active_bonusesには有効時間内のボーナスリストを渡すこと.
   */
  public static function getActiveOpenedForDungeon($dungeon, $active_bonuses) {
    $open_bonus = null;
    foreach($active_bonuses as $bonus) {
      if($bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_OPEN && $bonus->dungeon_id == $dungeon->id) {
        $open_bonus = $bonus;
        // 1件のみという前提.
        break;
      }
    }
    return $open_bonus;
  }

  /**
   * 指定の有効なボーナスリストから、
   * 指定ダンジョンで適用できるスタミナ割引ボーナスがあれば返す.
   * 有効時間の判定はおこなわないので、active_bonusesには有効時間内のボーナスリストを渡すこと.
   */
  public static function getActiveStaminaDungeon($dungeon_id, $active_bonuses) {
    $stamina_bonus = null;
    foreach($active_bonuses as $bonus) {
      if($bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_STAMINA && $bonus->dungeon_id == $dungeon_id) {
        $stamina_bonus = $bonus;
        // 1件のみという前提.
        break;
      }
    }
    return $stamina_bonus;
  }


  /**
   * 現在適用可能な課金ガチャ値段ボーナスを返す.
   * なければnullを返す. (=デフォルトの価格を使用する)
   */
/* 使う予定がないのでコメントアウト.
  public static function getActiveGachaPrice() {
    $bonuses = LimitedBonus::getAllActiveWithMargin();
    $active_bonus = null;
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled() && $bonus->bonus_type == LimitedBonus::BONUS_TYPE_GACHA_PRICE) {
        $active_bonus = $bonus;
        break;
      }
    }
    return $active_bonus;
  }
*/

  /**
   * このボーナスが適用時間内である場合に限りTRUEを返す.
   */
  public function checkEnabled() {
    $now = time();
    if(LimitedBonus::strToTime($this->begin_at) <= $now && LimitedBonus::strToTime($this->finish_at) >= $now) {
      return TRUE;
    }
    return FALSE;
  }

}
