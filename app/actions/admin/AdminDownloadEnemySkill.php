<?php
/**
 * Admin用:DL敵スキルデータ
 */
class AdminDownloadEnemySkill extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_ENEMY_SKILLS)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true); 
		$enemySkilldata = $decodedata['enemy_skills'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			"name"	=> "name",	
			"help"	=> "help",
			"type"	=> "type",
			"skp1"	=> "skp1",	
			"skp2"	=> "skp2",
			"skp3"	=> "skp3",
			"skp4"	=> "skp4",
			"skp5"	=> "skp5",
			"skp6"	=> "skp6",
			"skp7"	=> "skp7",
			"skp8"	=> "skp8",
			"ratio"	=> "ratio",
			"aip0"	=> "aip0",
			"aip1"	=> "aip1",
			"aip2"	=> "aip2",
			"aip3"	=> "aip3",
			"aip4"	=> "aip4",
		);
		foreach($enemySkilldata as $_key => $_value)
		{
			$tmpdata[] = $_value;
		}
		$result = array(
			'format' => 'array',
			'敵スキルデータ' => $tmpdata,
		);
		return json_encode($result);
	}
}