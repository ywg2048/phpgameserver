<?php
/**
 * Admin用:ダンジョンデータ
 */
class AdminDownloadDungeon extends AdminBaseAction
{
	public function action($params)
	{
		$value = DownloadMasterData::find(DownloadMasterData::ID_DUNGEONS_VER2)->gzip_data;
		$value = gzinflate(substr($value, 10, -4));

		$decodedata = json_decode($value,true); 
		$dungeondata = $decodedata['dungeons'];
		$tmpdata = array('format'=>'array');
		$tmpdata[] = array(
			'id',
			'name',
			'attr',
			'dtype',
			'dkind',
			'dwday',
			'padc_rf',
			'padc_uf',
			'padc_sf',
			'padc_rg',
			'ダンジョンフロア情報',
			'padc_pid',
			'plus_drop',
		);
		foreach($dungeondata as $_key => $_value)
		{
			// フロアデータは階層が深いため表示用に整形
			if(isset($_value['f']))
			{
				foreach($_value['f'] as $_k => $_v)
				{
					// key => value　を配列の形式で固める
					foreach($_v as $_k2 => $_v2)
					{
						if(!is_array($_v2))
						{
							$_v2 = array($_v2);
						}
						$_value['f'][$_k][$_k2] = array_merge(array($_k2),$_v2);
					}
				}
			}

			$tmpdata[] = $_value;
		}
		$result = array(
			'format' => 'array',
			'バージョン' => array(
				'v' => $decodedata['v'],
			),
			'ダンジョンデータ' => $tmpdata,
		);
		return json_encode($result);
	}
}