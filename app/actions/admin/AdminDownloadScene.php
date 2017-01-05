<?php
/**
 * Admin用:シーンデータ
 */
class AdminDownloadScene extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_SCENES)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true); 
		$sceneData = $decodedata['scenes'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			"id"			=> "id",
			"start_zpos"	=> "start_zpos",
			"end_zpos"		=> "end_zpos",
			"bg_filename"	=> "bg_filename",
			"dungeon_id1"	=> "dungeon_id1",
			"iconx1"		=> "iconx1",
			"icony1"		=> "icony1",
			"dungeon_id2"	=> "dungeon_id2",
			"iconx2"		=> "iconx2",
			"icony2"		=> "icony2",
			"dungeon_id3"	=> "dungeon_id3",
			"iconx3"		=> "iconx3",
			"icony3"		=> "icony3",
			"dungeon_id4"	=> "dungeon_id4",
			"iconx4"		=> "iconx4",
			"icony4"		=> "icony4",
			"dungeon_id5"	=> "dungeon_id5",
			"iconx5"		=> "iconx5",
			"icony5"		=> "icony5",
			"dungeon_id6"	=> "dungeon_id6",
			"iconx6"		=> "iconx6",
			"icony6"		=> "icony6",
		);
		foreach($sceneData as $_key => $_value)
		{
			$tmpdata[] = $_value;
		}
		$sceneData = $tmpdata;

		$scenePartData = $decodedata['sceneparts'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			"id"		=> "id",
			"filename"	=> "filename",
			"zpos"		=> "zpos",
		);
		foreach($scenePartData as $_key => $_value)
		{
			$tmpdata[] = $_value;
		}
		$scenePartData = $tmpdata;
		
		
		$result = array(
			'format'		=> 'array',
			'シーンデータ'		=> $sceneData,
			'シーンパーツデータ'	=> $scenePartData,
		);
		return json_encode($result);
	}
}