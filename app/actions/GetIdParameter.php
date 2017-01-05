<?php
/**
 * 30. IDパラメータ取得
 * (ユーザ情報取得)
 */
class GetIdParameter extends BaseAction {
  
  // http://pad.localhost/api.php?action=get_id_parameter&pid=1&sid=1&id=2
  public function action($params){
    $rev = (isset($params["r"])) ? (int)$params["r"] : 1;
    $targetUser = User::getCacheFriendData($params['id'], $rev);

    if( $targetUser === FALSE ){
      global $logger;
      throw new PadException(RespCode::USER_NOT_FOUND, "GetIdParameter USER_NOT_FOUND. pid:".$params['pid']." id:".$params['id']. " __NO_TRACE");
    }
    $res = array();
    $res['res'] = RespCode::SUCCESS;
    $res['data'] = array($targetUser);
    //获取通关最高分记录
    $ranking_id = LimitedRanking::getOpeningRankingDungeon();
    $pdo = Env::getDbConnectionForUserRead($params['id']);
    $ranking_record = UserRankingRecord::findbyUserId(array('user_id'=>$params['id'],'ranking_id'=>$ranking_id),$pdo);
    if(!$ranking_record){
      //没有排名关卡记录
      $res['rank_info'] = array(
        'turns' => 0,
        'floor' => 0,
        'combos' => 0,
        'rare' => 0,
        'score' => 0,
        'rank' =>0,
        'dungeon' => 0
      );
      return json_encode($res);
    }
    //获取已经通关的关
    $user_ranking_dungeon_floors =  UserRankingDungeonFloor::findAllBy(array('user_id'=>$params['id'],'dungeon_id'=>$ranking_id));
    $floor = array();
    foreach ($user_ranking_dungeon_floors as $user_ranking_dungeon_floor) {
      if($user_ranking_dungeon_floor->cleared_at !=null){
        $floor[] = $user_ranking_dungeon_floor->dungeon_floor_id; 
      }
    }
    //获取排名
    $rank =$this->getUserRankInfo($params['id'], $ranking_id);
    $res['rank_info'] = array(
        'turns' => $ranking_record->turns,
        'floor' => end($floor)%1000,
        'combos' => floor($ranking_record->combos/$ranking_record->turns),
        'rare' => $ranking_record->rare,
        'score' => $ranking_record->score,
        'rank' =>$rank,
        'dungeon' => floor(end($floor)/1000)
      );
  return json_encode($res);
  }

  private function getUserRankInfo($user_id, $ranking_id)
  { 
    $redis = Ranking::getRedis();
    $key = RedisCacheKey::getRankingDungeonKey($ranking_id);
    $is_contains = $redis->zScore($key,$user_id);
    if($is_contains){
      //当前用户redis里面有数据，则返回redis排名
      $user_rank = $redis->zRevRank($key, $user_id);
      $user_score = $redis->zScore($key, $user_id);

      return $user_rank+1;
    }
    
    //redis里面没有用户数据,查数据库
    $pdo = Env::getDbConnectionForShareRead($user_id);
    $user_point_ranking = Ranking::findBy(array('user_id' => $user_id, 'ranking_id' => $ranking_id),$pdo);
    
    if (! $user_point_ranking) {
      return array();
    }

    $user_score = $user_point_ranking->score;
    $user_rank = Ranking::getScoreRanking($user_score);
      
    return $user_rank;
  }

}
