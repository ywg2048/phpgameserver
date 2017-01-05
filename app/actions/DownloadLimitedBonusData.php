<?php
/**
 * 35. ノトーリアス＆ボーナス取得
 */
class DownloadLimitedBonusData extends BaseAction {
  // #PADC#
  const MAIL_RESPONSE = FALSE;
  // http://pad.localhost/api.php?action=download_limited_bonus_data&pid=1&sid=1
  public function action($params){
    $user_id = $params['pid'];
    $bonuses = LimitedBonus::getAllActiveToday();
    $dungeon_open_bonuses = LimitedBonus::getDungeonOpen();
    $bonuses = array_merge($bonuses, $dungeon_open_bonuses);
    // グループ別の時間限定ダンジョンを取得（ユーザーごとに異なる）
    $bonuses_group = LimitedBonusGroup::getDungeonOpen($user_id);
    $bonuses = $this->arrangeColumns($bonuses);
    $bonuses_group = $this->arrangeColumns_group($bonuses_group);
    $bonuses = array_merge($bonuses, $bonuses_group);
    // ダンジョン開放設定
    $bonuses_open_dungeon = LimitedBonusOpenDungeon::getDungeonOpen();
    $bonuses_open_dungeon = $this->arrangeColumns_open_dungeon($bonuses_open_dungeon);
    $bonuses = array_merge($bonuses, $bonuses_open_dungeon);
    // ダンジョンボーナス設定
    $bonuses_dungeon_bonus = LimitedBonusDungeonBonus::getDungeonBonus();
    $bonuses_dungeon_bonus = $this->arrangeColumns_dungeon_bonus($bonuses_dungeon_bonus);
    $bonuses = array_merge($bonuses, $bonuses_dungeon_bonus);
    return json_encode(array('res' => RespCode::SUCCESS, 'v' => 2, 'bonuses' => $bonuses));
  }

  /**
   * LimitedBonusのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  private function arrangeColumns($bonuses) {
    $mapper = array();
    foreach($bonuses as $bonus) {
      $arr = array();
      $arr['s'] = strftime("%y%m%d%H%M%S", strtotime($bonus->begin_at));
      $arr['e'] = strftime("%y%m%d%H%M%S", strtotime($bonus->finish_at));
      if($bonus->dungeon_id){
        $arr['d'] = (int)$bonus->dungeon_id;
      }
      if($bonus->dungeon_floor_id){
        $arr['f'] = (int)$bonus->dungeon_floor_id;
      }
      $arr['b'] = (int)$bonus->bonus_type;
      if($bonus->args){
      	// #PADC# ----------begin----------
      	// ノートリアスの場合、argsを0で上書き
      	if($bonus->bonus_type == LimitedBonus::BONUS_TYPE_FLOOR_NOTORIOUS)
      	{
	      	$arr['a'] = 0;
      	}
      	else
      	{
	      	$arr['a'] = (int)$bonus->args;
      	}
      	// #PADC# ----------end----------
      }
      if($bonus->target_id){
        $arr['i'] = (int)$bonus->target_id;
      }
      if($bonus->message){
        $arr['m'] = $bonus->message;
      }
      // #PADC# ----------begin----------
      // ガチャ画像ファイル名
      if(isset($bonus->file) && $bonus->file)
      {
      	$arr['padc_f'] = $bonus->file;
      }
      $arr['padc_lb_id'] = $bonus->id;
      // #PADC# ----------end----------
      $mapper[] = $arr;
    }
    return $mapper;
  }

  /**
   * LimitedBonusGroupのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  private function arrangeColumns_group($bonuses) {
    $mapper = array();
    foreach($bonuses as $bonus) {
      $arr = array();
      $arr['s'] = strftime("%y%m%d%H%M%S", strtotime($bonus->begin_at));
      $arr['e'] = strftime("%y%m%d%H%M%S", strtotime($bonus->finish_at));
      $arr['d'] = $bonus->dungeon_id ? (int)$bonus->dungeon_id : 0;
      $arr['b'] = 6;
      $arr['m'] = isset($bonus->message) ? $bonus->message : "";
      $arr['padc_lbg_id'] = $bonus->id;
      $mapper[] = $arr;
    }
    return $mapper;
  }

  /**
   * LimitedBonusOpenDungeonのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  private function arrangeColumns_open_dungeon($bonuses) {
    $mapper = array();
    foreach($bonuses as $bonus) {
      $arr = array();
      $arr['s'] = strftime("%y%m%d%H%M%S", strtotime($bonus->begin_at));
      $arr['e'] = strftime("%y%m%d%H%M%S", strtotime($bonus->finish_at));
      $arr['d'] = $bonus->dungeon_id ? (int)$bonus->dungeon_id : 0;
      $arr['b'] = 6;
      $arr['m'] = isset($bonus->message) ? $bonus->message : "";
      $arr['padc_lbod_id'] = $bonus->id;
      $mapper[] = $arr;
    }
    return $mapper;
  }

  /**
   * LimitedBonusDungeonBonusのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  private function arrangeColumns_dungeon_bonus($bonuses) {
    $mapper = array();
    foreach($bonuses as $bonus) {
      $arr = array();
      $arr['s'] = strftime("%y%m%d%H%M%S", strtotime($bonus->begin_at));
      $arr['e'] = strftime("%y%m%d%H%M%S", strtotime($bonus->finish_at));
      $arr['d'] = $bonus->dungeon_id ? (int)$bonus->dungeon_id : 0;
      $arr['b'] = (int)$bonus->bonus_type;
      $arr['a'] = (int)$bonus->args;
      $arr['padc_lbdb_id'] = $bonus->id;
      $mapper[] = $arr;
    }
    return $mapper;
  }
}
