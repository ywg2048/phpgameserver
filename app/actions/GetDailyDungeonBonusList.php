<?php
/**
 * #PADC#
 * デイリーダンジョンの報酬内容のリストを返す
 */
class GetDailyDungeonBonusList extends BaseAction {
	public function action($params) {
		$user_id = $params ['pid'];
		$dungeon_id = isset($params['dung'])? $params['dung'] : 0;
		
		// デイリーダンジョンかチェックする
		$dungeon = Dungeon::get($dungeon_id);
		if(!$dungeon || !$dungeon->isDailyDungeon()) {
			// 該当のダンジョンが存在しない.
			Padc_Log_Log::writeLog(('daily dungeon not found. dungeon_id:'.$dungeon_id), Zend_Log::DEBUG);
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}
		
		// ダンジョンが開放されているかチェックする
		$active_bonuses = LimitedBonus::getAllActiveDungeons();
		if (!$dungeon->isOpened($active_bonuses)) {
			// 現在ダンジョンが開放されていない
			Padc_Log_Log::writeLog(('dungeon is not open. dungeon_id:'.$dungeon_id), Zend_Log::DEBUG);
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}
		
		$bonus_list = DailyDungeonBonus::getActiveBonusList($dungeon_id);

		return json_encode ( array (
				'res' => RespCode::SUCCESS,
				'list' => $bonus_list
		) );
	}

}
