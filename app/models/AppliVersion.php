<?php
/**
 * #PADC#
 * アプリバージョン管理
 */
class AppliVersion extends BaseMasterModel
{
	const TABLE_NAME = "padc_appli_versions";
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	
	protected static $columns = array(
		'id',
		'begin_at',
		'main',
		'minor',
		'revision',
	);

	/**
	 * 現在有効なバージョンデータを取得
	 */
	public static function getActiveAppliVersion()
	{
		$now = time();
		$latest_date = null;
		$active_data = null;
		$all_datas = self::getAll();
		
		foreach($all_datas as $_data) {

			$begin_at = $_data->begin_at;

			if($now < static::strToTime($begin_at)) {
				continue;
			}

			if($latest_date == null || ($latest_date && $latest_date < $begin_at))
			{
				$latest_date = $begin_at;
				$active_data = $_data;
			}
		}
		return $active_data;
	}

}