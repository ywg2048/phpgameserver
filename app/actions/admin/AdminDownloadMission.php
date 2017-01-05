<?php
/**
 * Admin用:DLミッションデータ
 */
class AdminDownloadMission extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_MISSIONS)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true); 
		$missiondata = $decodedata['missions'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			"id",	
			"mission_type",
			"begin_at",
			"finish_at",
			"reward_img",
			"name",
			"description",
			"reward_text",
			"bonus_id",
			"piece_id",	
			"amount",
			"clear_condition",
			"transition_id",
			"time_zone_start",
			"time_zone_end",
		);
		foreach($missiondata as $_key => $_value)
		{
			$tmpdata[] = $_value;
		}
		$result = array(
			'format' => 'array',
			'バージョン' => array(
				'v' => $decodedata['v'],
			),
			'ミッションデータ' => $tmpdata,
		);
		return json_encode($result);
	}
}