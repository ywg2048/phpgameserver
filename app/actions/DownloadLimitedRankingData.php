<?php
/**
 * #PADC#
 * ランキングデータ取得
 */
class DownloadLimitedRankingData extends BaseAction {
  const MEMCACHED_EXPIRE = 86400; // 24時間.
  const FORMAT_VERSION = 1001;
  // #PADC#
  const MAIL_RESPONSE = FALSE;
  public function action($params){
    $res = null;
    $key = MasterCacheKey::getDownloadRankingData();
    $res = apc_fetch($key);
    $use_cache = true;
    if($res == null)
    {
      $ranking = LimitedRanking::getAllByForMemcache(array(),'start_time ASC');
      $res = self::arrangeColumns($ranking);
      apc_store($key, $res, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
      $use_cache = false;
      $reward = self::getrewardobj($ranking);
    }else{
      foreach ($res as $k => $v) {
          $arr[] = $v['id']; 
      }

      $reward = self::getreward($arr);
    }
    // $reward = self::getreward(array(1,2));
    return json_encode(array('res' => RespCode::SUCCESS, 'v' => self::FORMAT_VERSION, 'events' => $res));
  }

  /**
   * LimitedRankingのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  private function arrangeColumns($datas) {
    $mapper = array();
    foreach($datas as $data)
    {
      $arr = array();
      $arr['id'] = $data->ranking_id;
      $arr['start_time'] = strftime("%y%m%d%H%M%S", strtotime($data->start_time));
      $arr['end_time'] = strftime("%y%m%d%H%M%S", strtotime($data->end_time));
      $arr['reward_get_end_time'] = strftime("%y%m%d%H%M%S", strtotime($data->reward_get_end_time));
      $arr['dungeon_id'] = (int)$data->ranking_dungeon_id;
      // $arr['floor_id'] = (int)$data->ranking_floor_id;
      $arr['rule'] = (int)$data->ranking_rule;
      $arr['rule_page_url'] = $data->ranking_rule_url;
      $arr['message'] = $data->message;
      $arr['reward'] = RankingReward::getRewardList($data->ranking_id);
      // MY : ランキングの報酬は現在必要ないのでコメントアウト
      // $ranking_rewards = RankingReward::findAllBy(array('ranking_id' => $data->ranking_id));
      // $arr['rewards'] = $ranking_rewards;
      $mapper[] = $arr;      
    }

    return $mapper;
  }

  private function getreward($datas){
    foreach($datas as $data){
    
        $ranking_id = $data;

      $reward[] = RankingReward::getRewardList($ranking_id);
    }
    return $reward;
  }

   private function getrewardobj($datas){
    foreach($datas as $data){
    
        $ranking_id = $data->ranking_id;

      $reward[] = RankingReward::getRewardList($ranking_id);
    }
    return $reward;
  }
}
