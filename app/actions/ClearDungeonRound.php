<?php
/**
 * #PADC#
 * ダンジョンクリア（周回）
 */
class ClearDungeonRound extends BaseAction {
	// http://pad.localhost/api.php?action=clear_dungeon_round&pid=1&sid=1&hash=abc
	public function action($params){
		$user_id = $params["pid"];
		$access_token = isset ( $params ['ten_at'] ) ? $params ['ten_at'] : null;
		
		// ハッシュキーの存在チェック.
		if(!array_key_exists("hash", $params)) {
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}

		// ユーザーダンジョンの存在チェック.
		$user_dungeon = UserDungeon::findBy(array("user_id" => $user_id));
		if(!$user_dungeon) {
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}

		// ハッシュキーの一致チェック.
		if($user_dungeon->hash != $params["hash"]){
			$dung = (isset($params["dung"]) ? $params["dung"] : "");
			$floor = (isset($params["floor"]) ? $params["floor"] : "");
			global $logger;
			$logger->log("InvalidClearHash user_id:".$user_id." hash:".$params["hash"]." db_hash:".$user_dungeon->hash." dung:".$dung." floor:".$floor, Zend_Log::INFO);
			return json_encode(array('res' => RespCode::INVALID_CLEAR_HASH));
		}

		// クライアントバージョン文字列
		$app_verision = isset($params['appv']) ? $params['appv'] : 'unknown';
		$app_revision = isset($params['appr']) ? $params['appr'] : 'unknown';
		$client_verision = $app_verision . '_' . $app_revision;
		
		// ダンジョン周回クリア処理実行.
		list($user, $res) = $user_dungeon->clear(null,$this->decode_params,$client_verision,null,TRUE);

		// #PADC_DY# ----------begin----------
		// 现在扫荡也有可能开放新的关卡，所以这里需要返回下面的内容
		$next_floors = array();
		foreach ($res->next_dungeon_floors as $next_dungeon_floor) {
			$next_floors[] = array("dung" => (int) $next_dungeon_floor->dungeon_id, "floor" => (int) $next_dungeon_floor->seq);
		}

		// #PADC_DY# ----------end----------
		$sta = $user->getStamina();
		$sta_time = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));

		// ダンジョンで取得したカードを含めてリストをとり直す.
		$api_version = (isset($params["v"]) ? $params["v"] : 0); // レスポンスバージョン.
		$have_card_cnt = (isset($params["c"]) ? $params["c"] : 0); // 所持しているカード枚数（ダンジョン潜入前）.
		if($api_version == 2 && $have_card_cnt == $res->before_card_count){
			$cards = GetUserCards::arrangeColumns($res->get_cards);
			$res_version = 2;
		}else{
			$cards = GetUserCards::getAllUserCards($user_id);
			$res_version = 0;
		}

		// #PADC# ミッションクリア確認
		// ユーザーランク、ダンジョンクリアにおいては周回機能の仕様上ミッションクリアとなる場合は無いが一応
		list($clear_mission_count, $clear_mission_ids) = UserMission::checkClearMissionTypes ( $user_id, array (
				Mission::CONDITION_TYPE_USER_RANK,
				Mission::CONDITION_TYPE_DUNGEON_CLEAR,
				Mission::CONDITION_TYPE_BOOK_COUNT,
				Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR,
				Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_NORMAL,
				Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_SPECIAL,
		) );

		// #PADC#
		User::reportUserCardNum($user_id, $access_token);
		
		// #PADC# ----------begin----------
		// レスポンス内容更新予定
		// 最終的に手に入れたカードやカケラ、経験値付与後のカードデータetc.
		$return = array(
			'res' => RespCode::SUCCESS,
			'v' => $res_version,
			'lup' => $res->lv_up ? 1 : 0,
			'expgain' => $res->expgain,
			'coingain' => $res->coingain,
			'goldgain' => $res->goldgain,
			'exp' => $res->exp,
			'coin' => $res->coin,
			'gold' => $res->gold,
			// #PADC_DY# ----------begin----------
			'nextfloors' => $next_floors,
			// #PADC_DY# ----------end----------
			'cards' => $cards,
			// #PADC# ----------begin----------
			'get_pieces' => $res->get_pieces,
			'pieces' => $res->result_pieces,
			'decks' => $res->deck_cards,
			'clr_dcnt' => $res->clr_dcnt,
			'ncm' => $clear_mission_count,
			'clear_mission_list' => $clear_mission_ids,
			'sta' => $sta,
			'sta_time' => $sta_time,
			// #PADC_DY# ----------begin----------
			// 扫荡返回当前用户的最大体力上限
			'stamax' => $user->stamina_max,
			// #PADC_DY# ----------end----------
			'cheat' => $res->cheat,
			'roundgain' => $res->roundgain,
			'round' => (int)$user->round,
			'round_bonus' => $res->round_bonus,
			'qq_vip' => $res->qq_vip,
			'qq_coin_bonus' => $res->qq_coin_bonus,
			'qq_exp_bonus' => $res->qq_exp_bonus,
			'ten_gc' => $res->game_center,
			// #PADC# ----------end----------
		);
		// BANメッセージがある場合、レスポンス内容に追加
		if($res->ban_msg){
			$return['ban_msg'] = $res->ban_msg;
		}
		// #PADC# ----------end----------
		return json_encode($return);
	}

	/**
	 * このAPIをストレステストする際のダミーデータを作成する.
	 */
	public function createDummyDataForUser($user, $pdo) {
		// 1-1,2,3, 2-1,2,3 を開放.
		$d_fs = array(
			array(1, 1001),
			array(1, 1002),
			array(1, 1003),
			array(2, 2001),
			array(2, 2002),
			array(2, 2003),
		);
		foreach($d_fs as $d_f) {
			UserDungeonFloor::enable($user->id, $d_f[0], $d_f[1], $pdo);
		}

		// いずれかのダンジョンに潜入.
		$d_f = $d_fs[mt_rand(0, count($d_fs)-1)];
		UserDungeon::sneak($user, Dungeon::get($d_f[0]), DungeonFloor::get($d_f[1]));
	}

}


