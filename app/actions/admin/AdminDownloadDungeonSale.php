<?php
/**
 * Admin用:ダンジョンデータ
 */
class AdminDownloadDungeonSale extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_DUNGEON_SALES)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true);
		$dungeonSaledata = $decodedata['d'];

		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			'data',
		);
		$tmpdata[] = array(
			$dungeonSaledata,
		);				
		$result = array(
			'format' => 'array',
			'バージョン' => array(
				'v' => $decodedata['v'],
			),
			'ダンジョンSaleデータ' => $tmpdata,
		);
		return json_encode($result);
	}
}