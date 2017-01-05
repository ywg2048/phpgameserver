<?php
/**
 * #PADC#
 * ミッションの報酬を受け取る
 */
class GetMissionReward extends BaseAction {

	// http://pad.localhost/api.php?action=get_mission_reward&pid=2&sid=1&mid=1
	public function action($params){
		$rev = isset($params['r']) ? $params['r'] : 0;
		$user_id = $params ['pid'];

		// Tencentサーバーに無料魔法石を追加する為に、パラメータ追加
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		
		// ミッションの報酬付与チェック
		list($item_offered, $result, $clear_mission_ids_1) = UserMission::receiveReward(
				$user_id,
				$params['mid'],
				$token
		);

		$res = array();
		$res['res'] = RespCode::SUCCESS;
		$res['item_offered'] = $item_offered;

		$res['item'] = User::arrangeBonusResponse($result,$rev);

		// #PADC#
		// ミッションクリア確認（図鑑登録数）
		list($res['ncm'], $clear_mission_ids_2) = UserMission::checkClearMissionTypes ( $user_id, array (
				Mission::CONDITION_TYPE_BOOK_COUNT,
		) );
		$clear_mission_ids = array_merge($clear_mission_ids_1, $clear_mission_ids_2);
		$res['clear_mission_list'] = $clear_mission_ids;
		
		// ミッションリスト取得
		list($user_missions, $special_missions) = UserMission::getMissionList($user_id);
		$res['list'] = GetMissions::arrangeColumns($user_missions);
		$res['special_list'] = GetMissions::arrangeColumns($special_missions);
		
		if(isset($res['item']['card'])){
			User::reportUserCardNum($user_id, $token['access_token']);
		}

		return json_encode($res);
	}

}
