<?php
/**
 * #PADC#
 * アプリで表示するミッションのリストを返す
 */
class GetMissions extends BaseAction {
	public function action($params) {
		$user_id = $params ['pid'];

		// ミッションクリア確認（カテゴリ指定なしで有効時間帯の設定がされているミッションのチェック）
		list($clear_mission_count, $clear_mission_ids) = UserMission::checkClearMissionTypes ( $user_id, array() );
		
		// ミッションリスト取得
		list($user_missions, $special_missions) = UserMission::getMissionList($user_id);

		return json_encode ( array (
			'res' => RespCode::SUCCESS,
			'list' => static::arrangeColumns($user_missions),
			'special_list' => static::arrangeColumns($special_missions),
			'clear_mission_list' => $clear_mission_ids,
		) );
	}

	public static function arrangeColumns($user_missions) {
		$mapper = array();
		foreach ($user_missions as $user_mission) {
			$m = array();
			$m[] = (int)$user_mission->status;
			$m[] = (int)$user_mission->mission_id;
			$m[] = (int)$user_mission->progress_num;
			$m[] = (int)$user_mission->progress_max;

			$mapper[] = $m;
		}
		return $mapper;
	}

}
