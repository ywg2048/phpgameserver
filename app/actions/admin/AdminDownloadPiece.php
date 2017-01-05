<?php
/**
 * Admin用:DL欠片データ
 */
class AdminDownloadPiece extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_PIECES)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true); 
		$piecedata = $decodedata['pieces'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			"id"		=> "id",
			"name"		=> "name",
			"category"	=> "category",
			"type"		=> "type",
			"cid"		=> "cid",
			"gcnt"		=> "gcnt",
			"attr"		=> "attr",
			"sattr"		=> "sattr",
			"mt"		=> "mt",
			"mt2"		=> "mt2",
			"rare"		=> "rare",
			"mexp"		=> "mexp",
			"eexp"		=> "eexp",
			"scost"		=> "scost",
			"tcost"		=> "tcost",	
		);
		foreach($piecedata as $_key => $_value)
		{
			$tmpdata[] = $_value;
		}
		$result = array(
			'format' => 'array',
			'欠片データ' => $tmpdata,
		);
		return json_encode($result);
	}
}