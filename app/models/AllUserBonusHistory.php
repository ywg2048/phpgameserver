<?php
/**
 * 全ユーザボーナス履歴
 */
class AllUserBonusHistory extends BaseModel {
  const TABLE_NAME = "all_user_bonus_histories";

  protected static $columns = array(
    'user_id',
    'all_user_bonus_id'
  );
  
}
