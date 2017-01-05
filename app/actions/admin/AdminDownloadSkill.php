<?php
/**
 * Admin用:DLスキルデータ
 */
class AdminDownloadSkill extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_SKILLS)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true); 
		$skilldata = $decodedata['skill'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			"name"	=> "name",	
			"help"	=> "help",
			"sktp"	=> "sktp",
			"skp1"	=> "skp1",
			"skp2"	=> "skp2",
			"skp3"	=> "skp3",
			"skp4"	=> "skp4",
			"skp5"	=> "skp5",
			"skp6"	=> "skp6",
			"skp7"	=> "skp7",	
			"skp8"	=> "skp8",
			"lcap"	=> "lcap",
			"ctbs"	=> "ctbs",
			"ctel"	=> "ctel",
		);
		foreach($skilldata as $_key => $_value)
		{
			$tmpdata[] = $_value;
		}
		$result = array(
			'format' => 'array',
			'スキルデータ' => $tmpdata,
		);
		return json_encode($result);
	}
}