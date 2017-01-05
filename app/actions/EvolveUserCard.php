<?php
/**
 * 6. カード進化.
 */
class EvolveUserCard extends BaseAction {
	// http://pad.localhost/api.php?action=evolve_user_card&pid=1&sid=1&base=1&evo=2
	public function action($params) {
		//#PADC#
		$access_token = isset ( $params ['ten_at'] ) ? $params ['ten_at'] : null;
		
		$succeed = FALSE;
		$cards = array();
		$coin = 0;
		// #PADC# ----------begin----------
		// MY PADC CHANGE : 欠片の情報は1から始まり、連続した数字でなければ判定されない。piece1,piece3といった連番ではないパラメータは無効。
		$add_piece_datas = array();
		$i = 1;
		$piece_key = 'piece'.$i;
		$piece_num_key = 'piecenum'.$i;
		while(array_key_exists($piece_key, $params))
		{
			$add_piece_datas[$params[$piece_key]] = array('id' => $params[$piece_key], 'num' => $params[$piece_num_key]);
			$i = $i + 1;
			$piece_key = "piece".$i;
			$piece_num_key = "piecenum".$i;
		}
		// MY PADC CHANGE : 進化処理は欠片の仕様によって変更。
		if(array_key_exists("base", $params) && array_key_exists("evo", $params)) {

			list($succeed, $coin, $result_pieces) = UserCard::pieceEvolve($params["pid"], $params["base"], $params["evo"], $add_piece_datas);

		}
		$rcard = GetUserCards::getOneUserCard ( $params ["pid"], $params ["base"] );
		
		//#PADC#
		User::reportUserCardNum($params["pid"], $access_token);
		
		$return = array (
			'res' => ($succeed ? RespCode::SUCCESS : RespCode::FAILED_EVOLUTION),
			'coin' => $coin,
			'rcard' => $rcard,
			'rpieces' => $result_pieces
		);
		if ($succeed) {
			// #PADC#
			// ミッションクリア確認（図鑑登録数）
			list($return['ncm'], $return['clear_mission_list']) = UserMission::checkClearMissionTypes ( $params["pid"], array (
					Mission::CONDITION_TYPE_BOOK_COUNT,
					Mission::CONDITION_TYPE_CARD_EVOLVE,
					Mission::CONDITION_TYPE_DAILY_CARD_EVOLVE,
			) );
		}
		return json_encode($return);
		// #PADC# ----------end----------
		// MY PADC DELETE : 返却値が変更されるため削除。
		// if(isset($params["v"]) && $params["v"] == 1){
		//   $rcard = GetUserCards::getOneUserCard($params["pid"], $params["base"]);
		//   return json_encode(array(
		//     'res' => ($succeed ? RespCode::SUCCESS : RespCode::FAILED_EVOLUTION),
		//     'coin' => $coin,
		//     'rcard' => $rcard,
		//     'dcuid' => explode(",", $params['cuid']),
		//   ));
		// }else{
		//   $cards = GetUserCards::getAllUserCards($params["pid"]);
		//   return json_encode(array(
		//     'res' => ($succeed ? RespCode::SUCCESS : RespCode::FAILED_EVOLUTION),
		//     'coin' => $coin,
		//     'cards' => $cards,
		//   ));
		// }
	}

	/**
	 * このAPIをストレステストする際のダミーデータを作成する.
	 */
	public function createDummyDataForUser($user, $pdo) {
		// 合成用コイン大量付与.
		$user->addCoin(999999);
		$user->update($pdo);

		// 進化用ベースカードを付与. (150: ドラゴンシードを使用して進化できるもの)
		$cards = Card::getAllBy(array("gup1" => 150), "id ASC");
		foreach($cards as $card) {
			UserCard::addCardToUser($user->id, $card->gupc, 1, 1, $pdo);
		}

		// ドラゴンシードをベースカード枚数分付与.
		for($i=1; $i<=count($cards); $i++) {
			UserCard::addCardToUser($user->id, 150, 1, 1, $pdo);
		}
	}

}
