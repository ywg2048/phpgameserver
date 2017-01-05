<?php
/**
 * #PADC#
 * ダンジョン潜入（周回）
 *
 */
class SneakDungeonRound extends BaseAction {
	// http://pad.localhost/api.php?action=sneak_dungeon_round&pid=1&sid=1&dung=1&floor=1

	const MEMCACHED_EXPIRE = 120; // 2分.

	public function action($params){
		$user = User::find($params["pid"]);
		$rev = (isset($params["r"])) ? (int)$params["r"] : 1;
		$bm = 0;
		if($rev >= 2){
			$bm = (int)$this->decode_params["bm"];
			$rk = (int)$this->decode_params["rk"];
			$bm = $bm - ($rk & 0xFF);
		}
		//#PADC#
		$total_power = (isset($params['total_power']) ? $params['total_power'] : 0);
		
		// #PADC# memcache→redis
		$rRedis = Env::getRedisForUserRead();
		$res = null;

		// 改修内容
		// ハッシュ値のみを返す
		if(array_key_exists("dung", $params) && array_key_exists("floor", $params) && array_key_exists("time", $params)) {
			$sneak_time = $params["time"];
			// 二重アクセス防止の為、同じリクエストの場合はキャッシュの内容を返す
			$key = CacheKey::getSneakDungeonRound($user->id, $params["dung"], $params["floor"], $sneak_time);
			$res = $rRedis->get($key);
			if(!$res){
				// ダンジョン&フロア抽出.
				$dungeon = Dungeon::get($params["dung"]);
				if($dungeon) {
					// 潜入処理.
					// #PADC# ----------begin----------
					$check_punish_type = User::PUNISH_PLAY_BAN_NORMAL;
					if($dungeon->dkind == Dungeon::DUNG_KIND_BUY){
						$check_punish_type = User::PUNISH_PLAY_BAN_BUYDUNG;
					}else if(!$dungeon->isNormalDungeon()){
						$check_punish_type = User::PUNISH_PLAY_BAN_SPECIAL;
					}
					$punish_info = UserBanMessage::getPunishInfo($params["pid"], $check_punish_type);
					if($punish_info){
						return json_encode(array(
								'res' => RespCode::PLAY_BAN, 
								'ban_msg' => $punish_info['msg'], 
								'ban_end' => $punish_info['end']
						));
					}
					// #PADC# ----------end----------

					$dungeon_floor = DungeonFloor::getBy(array("dungeon_id" => $dungeon->id, "seq" => $params["floor"]));
					// 周回は助っ人選択なし
					$curdeck = (isset($params["curdeck"])) ? $params["curdeck"] : -1;
					$cm = (isset($params["cm"])) ? $params["cm"] : null;

					//#PADC#
					$securitySDK = (isset($this->decode_params['sdkres']) ? $this->decode_params['sdkres'] : null);
					$player_hp = (isset($params["mhp"])) ? $params["mhp"] : 1;
						
					list($user, $user_dungeon)
						= UserDungeon::sneak($user, $dungeon, $dungeon_floor, $sneak_time, null, null, null, null, null, $cm, $curdeck, $rev, TRUE, 0, $total_power, $securitySDK, $player_hp);
				}
				if($user_dungeon) {
					$result = RespCode::SUCCESS;
					$hash = $user_dungeon->hash;
					$res = array(
						'res' => $result,
						'hash' => $hash,
					);
					// #PADC# ----------begin---------- check punish zeroprofit
					$punish_zeroprofit = UserBanMessage::getPunishInfo($params["pid"], User::PUNISH_ZEROPROFIT);
					if(!empty($punish_zeroprofit)){
						$res['ban_msg'] = $punish_zeroprofit['msg'];
						$res['ban_end'] = $punish_zeroprofit['end'];
					}
					// #PADC# ----------end----------
					$res = json_encode($res);
					$redis = Env::getRedisForUser();
					$redis->set($key, $res, static::MEMCACHED_EXPIRE);
				}
			}
		}

		if(!$res){
			$res = array(
				'res' => RespCode::FAILED_SNEAK,
				'hash' => 0,
			);
// レスポンス暗号化 6.5で復活予定.
//      $res['e'] = Cipher::encode($res);
			$res = json_encode($res);
		}

		return $res;
	}

	/**
	 * このAPIをストレステストする際のダミーデータを作成する.
	 */
	public function createDummyDataForUser($user, $pdo) {
		// 1-1,2,3, 2-1,2,3 を開放.
		UserDungeonFloor::enable($user->id, 1, 1001, $pdo);
		UserDungeonFloor::enable($user->id, 1, 1002, $pdo);
		UserDungeonFloor::enable($user->id, 1, 1003, $pdo);
		UserDungeonFloor::enable($user->id, 2, 2001, $pdo);
		UserDungeonFloor::enable($user->id, 2, 2002, $pdo);
		UserDungeonFloor::enable($user->id, 2, 2003, $pdo);
	}

}
