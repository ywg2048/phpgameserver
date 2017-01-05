<?php
/**
 * Admin用:カードデータ
 */
class AdminDownloadCard extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_CARDS_VER4)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true); 
		$carddata = $decodedata['card'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			"name",	
			"attr",		"sattr",	"spup",		"mt",		"mt2",
			"rare",		"cost",		"size",		"mlv",		"mcost",
			"ccost",	"scost",
			"pmhpa",	"pmhpb",	"pmhpc",
			"patka",	"patkb",	"patkc",
			"preca",	"precb",	"precc",
			"pexpa",	"pexpb",// 22,23
			"skill",	// 24
			"lskill",	// 25
			"acyc",		// 26	
			"emhpa",	"emhpb",	"emhpc",// 27,28,29
			"eatka",	"eatkb",	"eatkc",// 30,31,32
			"edefa",	"edefb",	"edefc",// 33,34,35
			"edefd",	// 36　 敵最大LV
			"coink",	"expk",// 37,38	
			"gupc",		// 39
			"gup1",		"gup2",		"gup3",		"gup4",		"gup5",// 40,41,42,43,44
			"dev1",		"dev2",		"dev3",		"dev4",		"dev5",// 45,46,47,48,49
			"estu",		// 50
			"aip0",		"aip1",		"aip2",		"aip3",		"aip4",// 51,52,53,54,55
			
		);
		foreach($carddata as $_key => $_value)
		{
			$tmpdata[] = $_value;
		}
		$result = array(
			'format' => 'array',
			'バージョン' => array(
				'v' => $decodedata['v'],
			),
			'カードデータ' => $tmpdata,
		);
		return json_encode($result);
	}
}