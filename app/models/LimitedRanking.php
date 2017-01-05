<?php
/**
 * #PADC#
 * ランキングマスターデータ
 */
class LimitedRanking extends BaseMasterModel {
  const TABLE_NAME = "padc_limited_ranking";
  const VER_KEY_GROUP = "padcranking";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'ranking_id',
    'message',
    'ranking_dungeon_id',
    'ranking_floor_id',
    'start_time',
    'end_time',
    'aggregate_end_time',
    'reward_get_end_time',
    'ranking_rule',
    'ranking_rule_url'
  );

  public static function checkOpenRanking($ranking_id, $check_time = null)
  {
    $current_time = strftime('%Y%m%d%H%M%S',time());
    if($check_time)
    {
      $current_time = '"'.$check_time.'"';
    }
    $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE ranking_id = ? AND '.$current_time.' >= start_time  AND '.$current_time.' < end_time';
    $values = array($ranking_id);
    $pdo = Env::getDbConnectionForShare();
    $stmt = $pdo->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
    $stmt->execute($values);
    $open_ranking = $stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
    return $open_ranking;
  }
  public static function getCurrentRanking($ranking_id, $check_time = null){
    $current_time = strftime('%Y%m%d%H%M%S',time());
    if($check_time)
    {
      $current_time = '"'.$check_time.'"';
    }
    $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE ranking_id = ? AND '.$current_time.' >= start_time  AND '.$current_time.' < reward_get_end_time';
    $values = array($ranking_id);
    $pdo = Env::getDbConnectionForShare();
    $stmt = $pdo->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
    $stmt->execute($values);
    $open_ranking = $stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
    return $open_ranking;
  }
  public static function getAggregateRanking($pdo = null)
  {
    $current_time = time();
    $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE '.strftime('%Y%m%d%H%M%S',$current_time).' >= start_time  AND '.strftime('%Y%m%d%H%M%S',$current_time).' < aggregate_end_time';
    $values = array();
    if($pdo == null)
    {
      $pdo = Env::getDbConnectionForShare();  
    }
    $stmt = $pdo->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
    $stmt->execute($values);
    $open_ranking = $stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
    return $open_ranking;
  }

  public static function getLastRanking($pdo = null)
  {
    $current_time = time();
    $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE '.strftime('%Y%m%d%H%M%S',$current_time).' >= end_time ORDER BY end_time DESC LIMIT 1 ';
    $values = array();
    if($pdo == null)
    {
      $pdo = Env::getDbConnectionForShare();  
    }
    $stmt = $pdo->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
    $stmt->execute($values);
    $open_ranking = $stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
    return $open_ranking; 
  }
  //获取当前开放的排名关卡id
  public static function getOpeningRankingDungeon(){
    $allrankings = self::findAllBy(array(),'start_time');
    $ranking_id = 0;
    foreach ($allrankings as $ranking) {
      if(self::getCurrentRanking($ranking->ranking_id)){
        $ranking_id = $ranking->ranking_id;
      }
    }
    return $ranking_id;
  }
}
