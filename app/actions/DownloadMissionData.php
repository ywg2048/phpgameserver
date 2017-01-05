<?php
/**
 *  ミッションデータダウンロード
 */
class DownloadMissionData extends BaseAction {
	// http://pad.localhost/api.php?action=download_mission_data&pid=1&sid=1
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	const KEY_A = 0xd3955be7;
	const KEY_B = 0x7b53bc57;

	//1002:CBT4以降
	//1003:緊急ミッション対応
	const FORMAT_VER = 1003;
	
	// #PADC#
	const MAIL_RESPONSE = FALSE;
	const ENCRYPT_RESPONSE = FALSE;
	public function action($params){

// 		$missions = Mission::getAll();		
// 		// 削除フラグが立ったデータがあれば配列からカット
// 		foreach ($missions as $key => $mission) {
// 			if ($mission->del_flg) {
// 				unset($missions[$key]);
// 			}
// 		}

// 		$res['res'] = RespCode::SUCCESS;
// 		$res['v'] = DownloadMissionData::FORMAT_VER;
// 		$res['ckey'] = self::getCheckSum($missions, DownloadMissionData::FORMAT_VER);
// 		$res['missions'] = self::arrangeColumns($missions);

// 		return json_encode($res);

		// 最終的にDownloadMasterDataテーブルにデータを登録してそれを返すように対応
		$key = MasterCacheKey::getDownloadMissionData();
		$value = apc_fetch($key);
		if(FALSE === $value) {
			$value = DownloadMasterData::find(DownloadMasterData::ID_MISSIONS)->gzip_data;
			apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
		}
		return $value;
	}


	public static function getCheckSum($missions, $ver) {
		$sum = 0;
		foreach ($missions as $mission) {
			$mission_sum = 0;
			$mission_sum = ($mission_sum + (int)$mission->mission_type * 1) & 0xFFFFFFFF;
			$mission_sum = ($mission_sum + (int)$mission->bonus_id * 2) & 0xFFFFFFFF;
			$mission_sum = ($mission_sum + (int)$mission->piece_id * 3) & 0xFFFFFFFF;
			$mission_sum = ($mission_sum + (int)$mission->amount * 4) & 0xFFFFFFFF;
			$mission_sum = ($mission_sum + (int)$mission->transition_id * 5) & 0xFFFFFFFF;
			
			$sum = ($sum + $mission_sum * (int)$mission->id) & 0xFFFFFFFF;
		}
		return (($sum ^ static::KEY_A) + static::KEY_B) & 0xFFFFFFFF;
	}

	public static function arrangeColumns($missions) {
		$mapper = array();
		foreach ($missions as $mission) {
			$m = array();
			$m[] = (int)$mission->id;
			$m[] = (int)$mission->mission_type;
			$m[] = $mission->begin_at ? strftime("%y%m%d%H%M%S", strtotime($mission->begin_at)) : "0";
			$m[] = $mission->finish_at ? strftime("%y%m%d%H%M%S", strtotime($mission->finish_at)) : "0";
			$m[] = $mission->reward_img;
			$m[] = $mission->name;
			$m[] = $mission->description;
			$m[] = $mission->reward_text;
			$m[] = (int)$mission->bonus_id;
			$m[] = (int)$mission->piece_id;
			$m[] = (int)$mission->amount;
			$m[] = $mission->clear_condition;
			$m[] = (int)$mission->transition_id;
			$m[] = ($mission->time_zone_start === null ? null : (int)$mission->time_zone_start);
			$m[] = ($mission->time_zone_end === null ? null : (int)$mission->time_zone_end);
				
			$mapper[] = $m;
		}
		return $mapper;
	}

}
