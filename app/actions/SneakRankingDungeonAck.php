<?php
/**
 * #PADC#
 * ランキングダンジョン潜入ACK
 */
class SneakRankingDungeonAck extends BaseAction {
  // http://pad.localhost/api.php?action=sneak_dungeon_ack&pid=1&sid=1&hash=abc
  public function action($params){
    $user = User::find($params["pid"]);
    $result = RespCode::FAILED_SPEND_STAMINA;
    $sta = $user->getStamina();
    $sta_time = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));

    if(array_key_exists("hash", $params)) {
      // ダンジョン潜入直後にコールされるため、masterから読み取る.
      $pdo = Env::getDbConnectionForUserWrite($params["pid"]);
      $user_dungeon = UserRankingDungeon::findBy(array("user_id" => $user->id, "hash" => $params["hash"]), $pdo);
      if($user_dungeon) {
        //#PADC# ----------begin----------
        $ban_info = self::checkPlayBan($user->id, $user_dungeon->dungeon_id);
        if(isset($ban_info)){
        	return json_encode(array(
				'res' => RespCode::PLAY_BAN,
				'ban_msg' => $ban_info['msg'],
        		'ban_end' => $ban_info['end'],
    		));
        }
        //----------end----------

        $sneak_ranking = TRUE;
        // MY : まだスタミナ消費しておらず、ランキング開催時間を過ぎていた場合は潜入させない。
        if(is_null($user_dungeon->stamina_spent_at))
        {
          if(LimitedRanking::checkOpenRanking($user_dungeon->ranking_id) == FALSE)
          {
            // ランキングダンジョン参加失敗。
            $sneak_ranking = FALSE;
            $result = RespCode::FAILED_SNEAK;
          }
        }
        if($sneak_ranking)
        {
          $user = $user_dungeon->spendStamina();

          if($user) {
            // スタミナ消費成功.
            $result = RespCode::SUCCESS;
            $sta = $user->getStamina();
            $sta_time = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));
            $user_ranking = UserRanking::findBy(array('user_id' => $user->id,'ranking_id' => $user_dungeon->ranking_id));
            if($user_ranking == false)
            {
              $lc = $user->getLeaderCardsData();
              $lc_array = join(',',array($lc->id[0],$lc->lv[0],$lc->slv[0],$lc->hp[0],$lc->atk[0],$lc->rec[0],$lc->psk[0]));
              $value = UserRanking::createUserRankingValues($user->id,$user_dungeon->ranking_id,$user->name,$lc_array,0,0,array(),0);
              UserRanking::entryRanking($value);  
            }
          }
        }
      }
    }

    return json_encode(array(
      'res' => $result,
      'sta' => $sta,
      'sta_time' => $sta_time,
    ));
  }

	public static function checkPlayBan($player_id, $dungeon_id){
		$dungeon = Dungeon::get($dungeon_id);
		if($dungeon) {
			$punish_info = UserBanMessage::getPunishInfo($player_id, User::PUNISH_PLAY_BAN_RANKING);
			if($punish_info){
				return $punish_info;
			}
			return null;
		}
	}
}