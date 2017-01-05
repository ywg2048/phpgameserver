<?php
/**
 * ランキング報酬履歴
 */
class UserRankingReward extends BaseModel {
  const TABLE_NAME = "user_ranking_reward";

  protected static $columns = array(
    'user_id',
    'ranking_reward_id'
  );
  
}
