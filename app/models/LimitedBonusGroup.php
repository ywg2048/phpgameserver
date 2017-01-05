<?php
/**
 * 時間限定ボーナス（グループ別）.
 */

class LimitedBonusGroup extends BaseMasterModel {
  const TABLE_NAME = "limited_bonuses_group";
  const VER_KEY_GROUP = "lim";
  const MEMCACHED_EXPIRE = 3600; // 1時間.
  const LIMITED_BONUSES_GROUP_LOOP = 3;
  const LIMITED_BONUSES_GROUP_DAYS = 2;
  const LIMITED_BONUSES_GROUP_TIME_ST = '08:00:00';
  public static $defaultDungeonPatterns = array(
    'JP' => array(
      array(124, 125, 126), array(122),
      array(124, 125, 126), array(122),
      array(134), array(122),
    ),
    'NA' => array(
      array(122), array(126), array(122), array(134),
      array(122), array(124), array(122), array(134),
      array(122), array(125), array(122), array(134),
    ),
    'KR' => array(
      array(124, 125, 126), array(122),
      array(124, 125, 126), array(122),
      array(134), array(122),
    ),
    'EU' => array(
      array(124, 125, 126), array(122),
      array(124, 125, 126), array(122),
      array(134), array(122),
    ),
  );
  public static $defaultGroupIdPatterns = array(
    'JP' => array(
      array(1, 2, 3, 4, 5), array(2, 3, 4, 5, 1),
      array(3, 4, 5, 1, 2), array(4, 5, 1, 2, 3),
      array(5, 1, 2, 3, 4),
    ),
    'NA' => array(
      array(4, 5, 1, 2, 3), array(5, 1, 2, 3, 4),
      array(1, 2, 3, 4, 5), array(2, 3, 4, 5, 1),
      array(3, 4, 5, 1, 2), 
    ),
    'KR' => array(
      array(1, 2, 3, 4, 5), array(2, 3, 4, 5, 1),
      array(3, 4, 5, 1, 2), array(4, 5, 1, 2, 3),
      array(5, 1, 2, 3, 4),
    ),
    'EU' => array(
      array(1, 2, 3, 4, 5), array(2, 3, 4, 5, 1),
      array(3, 4, 5, 1, 2), array(4, 5, 1, 2, 3),
      array(5, 1, 2, 3, 4),
    ),
  );
  public static $defaultStartTimes = array(
    'JP' => array('08:00:00',),
    'NA' => array('11:00:00', '11:00:00', '11:00:00','07:00:00',),
    'KR' => array('08:00:00',),
    'EU' => array('08:00:00',),
  );
  public static $defualtMaxLoops = array(
    'JP' => array(3,),
    'NA' => array(2,2,2,3,),
    'KR' => array(3,),
    'EU' => array(3,),
  );
  public static $defaultCampDungeonPatterns = array(
    'NA' => array(
      array(127),
    ),
  );
  public static $defaultMorningCampPatterns = array(
    'NA' => array( 
      2, 3, 3, null,
      1, 2, 1, null,
      2, 3, 2, null,
      3, 1, 1, null,
      2, 3, 2, null,
      3, 1, 3, null,
      1, 2, 2, null,
      3, 1, 3, null,
      1, 2, 1, null,
    ),
  );
  public static $defaultNightCampPatterns = array(
    'NA' => array(
      1, 2, 2, null,
      3, 1, 3, null,
      1, 2, 1, null,
      2, 3, 3, null,
      1, 2, 1, null,
      2, 3, 2, null,
      3, 1, 1, null,
      2, 3, 2, null,
      3, 1, 3, null,
    ),
  );
  public static $defaultMorningCampTimes = array(
    'NA' => array(
     '10:00:00', '10:00:00', '08:00:00', null,
     '08:00:00', '08:00:00', '09:00:00', null,
     '09:00:00', '09:00:00', '10:00:00', null,
    ),
  ); 
  public static $defaultNightCampTimes = array(
    'NA' => array(
      '22:00:00', '22:00:00', '20:00:00', null,
      '20:00:00', '20:00:00', '21:00:00', null,
      '21:00:00', '21:00:00', '22:00:00', null,
    ),
  );

  protected static $columns = array(
    'id',
    'begin_at',
    'finish_at',
    'dungeon_id',
    'group_type',
    'group_id',
    'message',
  );

  /**
   * 有効な時間限定ボーナス（ダンジョン）のリストを返す.
   */
  public static function getDungeonOpenLimitedBonusesGroup() {
    $key = MasterCacheKey::getDungeonOpenLimitedBonusesGroupKey();
    $objs = apc_fetch($key);
    $defaultLimitedGroups = array();
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonusGroup::TABLE_NAME . " WHERE ";
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
      $interruptGroups = self::getInterruptGroup( $pdo );
      for($nextDays=0;$nextDays<self::LIMITED_BONUSES_GROUP_DAYS;$nextDays++){
        $ymd = date('Y-m-d', $now+(86400*$nextDays));
        if(!isset($interruptGroups[$ymd])){
          $defaultLimitedGroups = array_merge($defaultLimitedGroups, self::getDefaultLimitedBonusesGroup($now, $nextDays));
        }
      }
      if($objs) {
        $objs = array_merge($defaultLimitedGroups, $objs);
      } else {
        $objs = $defaultLimitedGroups;
      }
      apc_store($key, $objs, static::MEMCACHED_EXPIRE - static::add_apc_expire());
    }
    return $objs;
  }

  /**
   * クライアントに送信する時間限定ボーナス（ダンジョン）のリストを返す.
   */
  public static function getDungeonOpen($user_id) {
    $bonuses = LimitedBonusGroup::getDungeonOpenLimitedBonusesGroup();
    $group_bonuses = array();
    foreach($bonuses as $bonus) {
      if($bonus->checkGroupEnabled($user_id)) {
        $group_bonuses[] = $bonus;
      }
    }
    return $group_bonuses;
  }

  /**
   * 現在有効または指定時間内に有効となる時限ボーナスの配列を返す.
   * 現在有効なものを取得するときは、これを直接呼ばず、getAllActive*() を使うこと.
   */
  public static function getAllActiveWithMargin() {
    $key = MasterCacheKey::getAllLimitedBonusGroupWithMargin();
    $objs = apc_fetch($key);
    $defaultLimitedGroups = array();
    if(FALSE === $objs) {
      $pdo = Env::getDbConnectionForShare();
      $sql = "SELECT * FROM " . LimitedBonusGroup::TABLE_NAME . " WHERE begin_at <= ? AND finish_at >= ? ORDER BY id ASC";
      // 余裕を見て、MEMCACHED_EXPIREの2倍前からキャッシュに入れておく.
      $now = time();
      $values = array(LimitedBonusGroup::timeToStr($now + LimitedBonusGroup::MEMCACHED_EXPIRE*2), LimitedBonusGroup::timeToStr($now));
      $stmt = $pdo->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
      $stmt->execute($values);
      $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
      $interruptGroups = self::getInterruptGroup( $pdo );
      for($nextDays=0;$nextDays<self::LIMITED_BONUSES_GROUP_DAYS;$nextDays++) {
        $ymd = date('Y-m-d', $now+(86400*$nextDays));
        if(!isset($interruptGroups[$ymd])){
          $defaultLimitedGroups = array_merge($defaultLimitedGroups, self::getDefaultLimitedBonusesGroup($now, $nextDays));
        }
      }
      if($objs) {
        $objs = array_merge($defaultLimitedGroups, $objs);
      } else {
        $objs = $defaultLimitedGroups;
      }
      apc_store($key, $objs, static::MEMCACHED_EXPIRE - static::add_apc_expire());
    }
    return $objs;
  }

  /**
   * 指定のダンジョン潜入時に考慮すべきすべての時間限定ボーナスを返す.
   */
  public static function getActiveForSneakDungeon($user, $dungeon) {
    $group_bonuses = LimitedBonusGroup::getAllActiveWithMargin();
    $active_bonuses = array();
    foreach($group_bonuses as $group_bonuse) {
      if($group_bonuse->checkEnabled() && $group_bonuse->dungeon_id == $dungeon->id) { 
        if($group_bonuse->checkGroupEnabled($user->id)) {
          // LimitedBonusを偽造する
          $bonuse = new LimitedBonus();
          $bonuse->begin_at = $group_bonuse->begin_at;
          $bonuse->finish_at = $group_bonuse->finish_at;
          $bonuse->dungeon_id = $group_bonuse->dungeon_id;
          $bonuse->dungeon_floor_id = NULL;
          $bonuse->bonus_type = 6;
          $bonuse->args = NULL;
          $bonuse->target_id = NULL;
          $bonuse->amount = NULL;
          $bonuse->nm_eggprob = NULL;
          $active_bonuses[] = $bonuse;
        }
      }
    }
    return $active_bonuses;
  }

  /**
   * 現在有効なすべてのダンジョン開放ボーナスを返す.
   */
  public static function getAllActiveDungeons() {
    $bonuses = LimitedBonusGroup::getAllActiveWithMargin();
    $active_bonuses = array();
    foreach($bonuses as $bonus) {
      if($bonus->checkEnabled()) {
        $active_bonuses[] = $bonus;
      }
    }
    return $active_bonuses;
  }

  /**
   * このボーナスが適用時間内である場合に限りTRUEを返す.
   */
  private function checkEnabled() {
    $now = time();
    if(LimitedBonusGroup::strToTime($this->begin_at) <= $now && LimitedBonusGroup::strToTime($this->finish_at) >= $now) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * ユーザーがこのボーナスが適用対象である場合に限りTRUEを返す.
   */
  private function checkGroupEnabled($user_id) {
    // ユーザーデータのキャッシュ.
    $user_friend_data = User::getCacheFriendData($user_id, User::FORMAT_REV_1);
    if($this->group_type == 0) {
      // 所属属性が一致した時
      if($user_friend_data['at'] == $this->group_id) {
        return TRUE;
      }
    } else if ($this->group_type == 99) {
      // 何も行わない
      return FALSE;
    } else {
      $user_group_id = ($user_id % $this->group_type) + 1;
      // ユーザーグループ（アカウントID÷グループ数の余り）が一致した時
      if($user_group_id == $this->group_id) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * 割り込みLimitedBonusesGroupがあれば,TRUEを返す.
   */
  public static function getInterruptGroup( $pdo ) {
    $sql = "SELECT * FROM " . LimitedBonusGroup::TABLE_NAME;
    $stmt = $pdo->prepare( $sql );
    $stmt->execute();
    $objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
    $interruptGroups = array();
    foreach ( $objs as $obj ) {
      $st_dt = date( 'Y-m-d 00:00:00', strtotime( $obj->begin_at ) );
      $ed_dt = date( 'Y-m-d 00:00:00', strtotime( $obj->finish_at ) - 1 );
      $diff = intval( ( strtotime( $ed_dt ) - strtotime( $st_dt ) ) / 86400 );
      for ( $dt=0;$dt<=$diff;$dt++ ) {
        $ymd = date( 'Y-m-d', strtotime( $st_dt ) + ( 86400 * $dt ) );
        $interruptGroups[$ymd] = TRUE;
      }
    }
    return $interruptGroups;
  }

  /**
   * 通常時のLimitedBonusesGroupを返す.
   */
  public static function getDefaultLimitedBonusesGroup( $now, $nextDays ) {
    if (!isset(self::$defaultDungeonPatterns[Env::REGION])) {
      return array();
    }
    $objs = array();
    $date = intval( ( strtotime( date( 'Y-m-d', $now+(86400*$nextDays) ) ) - strtotime( '2000-01-01' ) ) / 86400 );
    $begin_at = date( 'Y-m-d', $now+(86400*$nextDays) ) . ' ' . self::$defaultStartTimes[Env::REGION][$date%count(self::$defaultStartTimes[Env::REGION])];
    $dungeons = self::$defaultDungeonPatterns[Env::REGION][$date%count(self::$defaultDungeonPatterns[Env::REGION])];
    $groupIds = self::$defaultGroupIdPatterns[Env::REGION][$date%count(self::$defaultGroupIdPatterns[Env::REGION])];
    $group_type = count( $groupIds );
    // LimitedBonusesGroupを生成.
    // 早朝属性別LimitedBonusesGroup
    if (isset(self::$defaultMorningCampPatterns[Env::REGION])) {
      $group_id = self::$defaultMorningCampPatterns[Env::REGION][$date%count(self::$defaultMorningCampPatterns[Env::REGION])];
      $moning_dungeons = self::$defaultCampDungeonPatterns[Env::REGION][$date%count(self::$defaultCampDungeonPatterns[Env::REGION])];
      if (is_null($group_id) === FALSE && is_null($moning_dungeons) === FALSE) {
        $obj = new LimitedBonusGroup();
        $obj->id = 90000 + (($nextDays*1000)+890);
        $obj->begin_at = date('Y-m-d', $now+(86400*$nextDays)).' '.self::$defaultMorningCampTimes[Env::REGION][$date%count(self::$defaultMorningCampTimes[Env::REGION])];
        $obj->finish_at = date('Y-m-d H:i:s', strtotime('1 hour', strtotime($obj->begin_at)));
        $obj->dungeon_id = $moning_dungeons[$date%count($moning_dungeons)];
        $obj->group_type = 0;
        $obj->group_id = $group_id;
        $obj->message = "";
        if (self::strToTime($obj->finish_at) >= $now) {
          $objs[] = $obj;
        }
      }
    }

    // 通常LimitedBonusesGroup
    $camp_obj = null;
    $max_loop = self::$defualtMaxLoops[Env::REGION][$date%count(self::$defualtMaxLoops[Env::REGION])];
    for ( $loop_no=0;$loop_no<$max_loop;$loop_no++ ) {
      foreach ( $groupIds as $id_no => $group_id ) {
        $finish_at = date( 'Y-m-d H:i:s', strtotime( '1 hour', strtotime( $begin_at ) ) );
        $obj = new LimitedBonusGroup();
        $obj->id = 90000 + (($nextDays*1000)+($loop_no*100)+$id_no);
        $obj->begin_at = $begin_at;
        $obj->finish_at = $finish_at;
        $obj->dungeon_id = $dungeons[($id_no+$loop_no)%count($dungeons)];
        $obj->group_type = $group_type;
        $obj->group_id = $group_id;
        $obj->message = "";
        if ( self::strToTime( $finish_at ) >= $now ) {
          $objs[] = $obj;
        }
        $camp_obj = $obj;
        $begin_at = $finish_at;
      }
    }

    // 深夜属性別LimitedBonusesGroup
    if (isset(self::$defaultNightCampPatterns[Env::REGION])) {
      $group_id = self::$defaultNightCampPatterns[Env::REGION][$date%count(self::$defaultNightCampPatterns[Env::REGION])];
      $night_dungeons = self::$defaultCampDungeonPatterns[Env::REGION][$date%count(self::$defaultCampDungeonPatterns[Env::REGION])];
      if (is_null($group_id) === FALSE && is_null($night_dungeons) === FALSE) {
        // 北米版のみ、最後のLimitedBonusesGroupを開始23:00にする.
        if (Env::REGION == 'NA' && $camp_obj) {
          if (count($objs) > 0) {
            $index = count($objs)-1;
            $objs[$index]->begin_at = date('Y-m-d', $now+(86400*$nextDays)).' 23:00:00';
            $objs[$index]->finish_at = date('Y-m-d H:i:s', strtotime('1 hour', strtotime($objs[$index]->begin_at)));
          } else {
            $camp_obj->begin_at = date('Y-m-d', $now+(86400*$nextDays)).' 23:00:00';
            $camp_obj->finish_at = date('Y-m-d H:i:s', strtotime('1 hour', strtotime($camp_obj->begin_at)));
            if (self::strToTime($camp_obj->finish_at) >= $now) {
              $objs[] = $camp_obj;
            }
          }
        }
        $obj = new LimitedBonusGroup();
        $obj->id = 90000 + (($nextDays*1000)+990);
        $obj->begin_at = date('Y-m-d', $now+(86400*$nextDays)).' '.self::$defaultNightCampTimes[Env::REGION][$date%count(self::$defaultNightCampTimes[Env::REGION])];
        $obj->finish_at = date('Y-m-d H:i:s', strtotime('1 hour', strtotime($obj->begin_at)));
        $obj->dungeon_id = $night_dungeons[$date%count($night_dungeons)];
        $obj->group_type = 0;
        $obj->group_id = $group_id;
        $obj->message = "";
        if (self::strToTime($obj->finish_at) >= $now) {
          $objs[] = $obj;
        }
      }
    }

    return $objs;
  }

}
