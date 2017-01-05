<?php
/**
 * 5. カード合成.
 */
class CompositeUserCards extends BaseAction {
	// http://pad.localhost/api.php?action=composite_user_cards&pid=1&sid=1&base=1&add1=2&add2=3&add3=4&add4=5&add5=6

	// #PADC# ----------begin----------
	public function action($params){
		$succeed = FALSE;
		$slup = 0;
		$aexp = 0;
		$bonus = 0;
		$cards = array();
		$sevo = null;
		$devo = null;

		// MY : 合成に関する仕様が変更されるため、この部分は修正される予定。
		// if(array_key_exists("add1", $params) && array_key_exists("base", $params)) {
		//   $add_cuids = array(
		//     (int)$params["add1"]
		//   );
		//   if(array_key_exists("add2", $params)) $add_cuids[] = (int)$params["add2"];
		//   if(array_key_exists("add3", $params)) $add_cuids[] = (int)$params["add3"];
		//   if(array_key_exists("add4", $params)) $add_cuids[] = (int)$params["add4"];
		//   if(array_key_exists("add5", $params)) $add_cuids[] = (int)$params["add5"];
		//   if(array_key_exists("sevo", $params)) $sevo = $params["sevo"];
		//   if(array_key_exists("devo", $params)) $devo = $params["devo"];
		//   list($succeed, $slup, $aexp, $coin, $bonus) = UserCard::composite($params["pid"], $params["base"], $sevo, $devo, $add_cuids); 
		// }
		// MY PADC CHANGE : 欠片の情報は1から始まり、連続した数字でなければ判定されない。piece1,piece3といった連番ではないパラメータは無効。
		$piece_ids = array();
		$i = 1;
		$piece_key = 'piece'.$i;
		$piece_num_key = 'piecenum'.$i;
		while(array_key_exists($piece_key, $params))
		{
			$piece_ids[$params[$piece_key]] = array('id' => $params[$piece_key], 'num' => $params[$piece_num_key]);

			// 指定された欠片の個数チェック
			if($params[$piece_num_key] < 1)
			{
				throw new PadException(RespCode::UNKNOWN_ERROR,'invalid num!');
			}

			$i = $i + 1;			
			$piece_key = "piece".$i;
			$piece_num_key = "piecenum".$i;
		}

		list($succeed, $slup, $aexp, $coin, $bonus, $result_pieces) = UserCard::pieceComposite($params["pid"], $params["base"],$piece_ids);
		// $delete_cuids = array((int)$params["add1"]);

		$rcard = GetUserCards::getOneUserCard($params["pid"], $params["base"]);
		// 通信量削減(ver6.0～)
		// MY PADC CHANGE : 返すデータのうち、削除したカード(delete_cuids)をなくし、減らした欠片データを追加。
		$ret = array(
			'res' => ($succeed ? RespCode::SUCCESS : RespCode::FAILED_COMPOSITION),
			'slup' => $slup,
			'aexp' => $aexp,
			'coin' => $coin,
			'bonus' => $bonus,
			'rcard' => $rcard,
			'rpieces' => $result_pieces,
		);
		if ($succeed) {
			// #PADC#
			// ミッションクリア確認（強化合成回数）
			list($ret['ncm'], $ret['clear_mission_list']) = UserMission::checkClearMissionTypes ( $params["pid"], array (
					Mission::CONDITION_TYPE_CARD_COMPOSITE,
					Mission::CONDITION_TYPE_DAILY_CARD_COMPOSITE,
			) );
		}
		// MY PADC DELETE : バージョンによって返す値を変更する必要はないため削除
		// if(isset($params["v"]) && $params["v"] == 1){
		// }else{
		//   $cards = GetUserCards::getAllUserCards($params["pid"]);
		//   $ret = array(
		//     'res' => ($succeed ? RespCode::SUCCESS : RespCode::FAILED_COMPOSITION),
		//     'slup' => $slup,
		//     'aexp' => $aexp,
		//     'coin' => $coin,
		//     'bonus' => $bonus,
		//     'cards' => $cards,
		//   );

		// }
		return json_encode($ret);
	}

	// #PADC# ----------end----------

	/**
	 * このAPIをストレステストする際のダミーデータを作成する.
	 */
	public function createDummyDataForUser($user, $pdo) {
		// 合成用コイン大量付与.
		$user->addCoin(999999);
		$user->card_max = 100;
		$user->update($pdo);

		// カードをランダムに26枚付与. (合成用なので、属性も関係なく付与)
		$cards = Card::getAll();
		array_shift($cards); // ID=1 が「なし」データなので
		for($i=1; $i<=26; $i++) {
			$card = $cards[mt_rand(0, count($cards)-1)];
			UserCard::addCardToUser($user->id, $card->id, mt_rand(1, $card->mlv), 1, $pdo);
		}
	}

}


