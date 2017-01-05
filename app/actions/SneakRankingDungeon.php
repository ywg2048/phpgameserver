<?php
/**
 * #PADC#
 * ランキングダンジョン潜入
 */
class SneakRankingDungeon extends BaseAction {
	// http://pad.localhost/api.php?action=sneak_dungeon&pid=1&sid=1&dung=1&floor=1

	const MEMCACHED_EXPIRE = 120; // 2分.

	public function action($params){
		$user = User::find($params["pid"]);
		$rev = (isset($params["r"])) ? (int)$params["r"] : 1;
		$ranking_id = $params["ranking_id"];
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

		// #PADC# ----------begin----------
		// PADCではモンスター所持数上限がないのでチェックをコメントアウト
		//if($bm > $user->card_max){
		//	throw new PadException(RespCode::EXCEEDED_MAX_NUM_CARD, "SneakDungeon EXCEEDED_MAX_NUM_CARD user_id:".$user->id." bm:$bm. __NO_TRACE");
		//}
		// #PADC# ----------end----------
		// MY TODO : ランキング開催時間の確認。
		if(LimitedRanking::checkOpenRanking($ranking_id))
		{
			// 改修内容
			// ウェーブデータの内容改修（ドロップするカケラ情報）
			// ドロップしたカケラの情報、最終的に手に入れたカケラ＆モンスター情報
			if(array_key_exists("dung", $params) && array_key_exists("floor", $params) && array_key_exists("time", $params)) {
				$sneak_time = $params["time"];
				// 二重アクセス防止の為、同じリクエストの場合はキャッシュの内容を返す
				$key = CacheKey::getSneakRankingDungeon($user->id, $ranking_id, $params["dung"], $params["floor"], $sneak_time);
				$res = $rRedis->get($key);

				if(!$res){
					// ダンジョン&フロア抽出.
					$dungeon = RankingDungeon::get($params["dung"]);

					if($dungeon) {
						// 潜入処理.
						// #PADC# ----------begin----------
						$punish_info = UserBanMessage::getPunishInfo($params["pid"], User::PUNISH_PLAY_BAN_RANKING);
						if($punish_info){
							return json_encode(array(
									'res' => RespCode::PLAY_BAN, 
									'ban_msg' => $punish_info['msg'],
									'ban_end' => $punish_info['end'],
							));
						}
						// #PADC# ----------end----------
						$dungeon_floor = RankingDungeonFloor::getBy(array("dungeon_id" => $dungeon->id, "seq" => $params["floor"]));
						$helper = (isset($params["helper"])) ? $params["helper"] : null;
						$card_id = (isset($this->decode_params["c"])) ? $this->decode_params["c"] : null; // 助っ人カードID.
						$card_lv = (isset($this->decode_params["l"])) ? $this->decode_params["l"] : null; // 助っ人カードLV.
						$skill_lv = (isset($this->decode_params["s"])) ? $this->decode_params["s"] : null; // 助っ人スキルLV.
						$plus = (isset($this->decode_params["p"])) ? explode(",", $this->decode_params["p"]) : null; // 助っ人＋値.
						$curdeck = (isset($params["curdeck"])) ? $params["curdeck"] : -1;
						$cm = (isset($params["cm"])) ? $params["cm"] : null;

						//#PADC#
						$securitySDK = (isset($this->decode_params['sdkres']) ? $this->decode_params['sdkres'] : null);
						$player_hp = (isset($params["mhp"])) ? $params["mhp"] : 1;
						
						list($user, $user_dungeon, $fp, $wave_mons_indexs)
							= UserRankingDungeon::sneak($user, $dungeon, $dungeon_floor, $sneak_time, $helper, $card_id, $card_lv, $skill_lv, $plus, $cm, $curdeck, $rev, FALSE, $ranking_id, $total_power, $securitySDK, $player_hp);
					}
					if($user_dungeon) {
						$result = RespCode::SUCCESS;
						$hash = $user_dungeon->hash;
						$btype = empty($user_dungeon->btype) ? 0 : $user_dungeon->btype;
						$barg = empty($user_dungeon->barg) ? 0 : $user_dungeon->barg;
						$waves = $this->getWaveListForClient($user_dungeon);
						$wave_str = $this->convWaveListForCipher($waves);
						$res = array(
							'res' => $result,
							'hash' => $hash,
							'btype' => $btype,
							'barg' => $barg,
							'fp' => $fp,
							'wave_mons' => $wave_mons_indexs,
						);
						// 6.4.2以降は暗号化したwavesのみ返す.
						if(Env::APP_VERSION < 6.42){
							$res['waves'] = $waves;
						}
						$res['e'] = Cipher::encode(array("waves" => $wave_str));
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
		}

		if(!$res){
			$res = array(
				'res' => RespCode::FAILED_SNEAK,
				'hash' => 0,
				'btype' => 0,
				'barg' => 0,
				'waves' => array(),
				'wave_mons' => array(),
			);
// レスポンス暗号化 6.5で復活予定.
//      $res['e'] = Cipher::encode($res);
			$res = json_encode($res);
		}

		return $res;
	}

	private function convWaveListForCipher($waves){
		$res = '[';
		$comma = '';
		foreach($waves as $wave){
			$res .= $comma.'["w":';
			$comma2 = '';
			foreach($wave->monsters as $m){
				// #PADC#
				// ドロップするカケラIDを追加
				$res .= $comma2.'['.$m->type.','.$m->num.','.$m->lv.','.$m->item.','.$m->inum.','.$m->pval.','.$m->pid.']';
				$comma2 = ',';
			}
			$res .= ']';
			$comma = ',';
		}
		$res .= ']';
		return $res;
	}

	/**
	 * UserDungeonオブジェクトからクライアントが要求する
	 * 潜入データ用データ構造を構築する.
	 */
	private function getWaveListForClient($user_dungeon) {
		$waves = array();
		foreach($user_dungeon->user_waves as $user_wave) {
			$wc = new WaveForClient();
			$wc->seq = (int)$user_wave->wave->seq;
			$wc->monsters = array();
			// #PADC# ----------begin----------
			// ここでモンスター並びのシャッフルは行わないように変更
			// チート対策用のチェックデータを作成するタイミングでシャッフルさせる
			$shuffle_flg = false;
			// #PADC# ----------end----------
			foreach($user_wave->user_wave_monsters as $user_wave_monster) {
				$m = new MonsterForClient();
				// モンスター種類
				if($user_wave_monster->wave_monster->boss == 1) {
					$m->type = 1;	// ボス
					$shuffle_flg = false;
				} elseif($user_wave_monster->wave_monster->boss == 2) {
					$m->type = 2;	// ノトーリアスモンスター
					$shuffle_flg = false;
				} elseif($user_wave_monster->wave_monster->prob >= 10000) {
					$m->type = $user_wave_monster->wave_monster->prob - 10000;	// ウェーブデータの登場確率が10000以上のモンスター
					$shuffle_flg = false;
				} else {
					$m->type = 0;	// 通常敵
				}
				$m->num = (int)$user_wave_monster->wave_monster->card_id;
				$m->lv = (int)$user_wave_monster->level;
				$beat_bonus = $user_wave_monster->beat_bonus;
				if($beat_bonus) {
					$m->item = (int)$beat_bonus->item_id;
					$m->inum = (int)$beat_bonus->amount;
					$m->pval = (int)$beat_bonus->plus_hp + $beat_bonus->plus_atk + $beat_bonus->plus_rec;
					// #PADC#
					$m->pid = (int)$beat_bonus->piece_id;	//カケラID
				} else {
					$m->item = 0;
					$m->inum = 0;
					$m->pval = 0;
					// #PADC#
					$m->pid = 0;	//カケラID
				}
				array_push($wc->monsters, $m);
			}
			if($shuffle_flg){
				shuffle($wc->monsters);
			}
			array_push($waves, $wc);
		}
		return $waves;
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

class WaveForClient {
	public $seq;
	public $monsters;
}

class MonsterForClient {
	public $type;
	public $num;
	public $lv;
	public $item;
	public $inum;
	public $pval;
	// #PADC#
	public $pid;	//カケラID
}
