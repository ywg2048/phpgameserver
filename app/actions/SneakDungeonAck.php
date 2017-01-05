<?php
/**
 * 44. ダンジョン潜入ACK
 */
class SneakDungeonAck extends BaseAction {
  // http://pad.localhost/api.php?action=sneak_dungeon_ack&pid=1&sid=1&hash=abc
  public function action($params){
    $user = User::find($params["pid"]);
    $result = RespCode::FAILED_SPEND_STAMINA;
    $sta = $user->getStamina();
    $sta_time = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));

    if(array_key_exists("hash", $params)) {
      // ダンジョン潜入直後にコールされるため、masterから読み取る.
      $pdo = Env::getDbConnectionForUserWrite($params["pid"]);
      $user_dungeon = UserDungeon::findBy(array("user_id" => $user->id, "hash" => $params["hash"]), $pdo);
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
      	
      	$user = $user_dungeon->spendStamina();
        if($user) {
          // スタミナ消費成功.
          $result = RespCode::SUCCESS;
          $sta = $user->getStamina();
          $sta_time = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));
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
			$check_punish_type = User::PUNISH_PLAY_BAN_NORMAL;
			if($dungeon->dkind == Dungeon::DUNG_KIND_BUY){
				$check_punish_type = User::PUNISH_PLAY_BAN_BUYDUNG;
			}else if(!$dungeon->isNormalDungeon()){
				$check_punish_type = User::PUNISH_PLAY_BAN_SPECIAL;
			}
			$punish_info = UserBanMessage::getPunishInfo($player_id, $check_punish_type);
			if($punish_info){
				return $punish_info;
			}
			return null;
		}
	}
}

