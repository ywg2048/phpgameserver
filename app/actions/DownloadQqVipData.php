<?php
/**
 * #PADC#
 */
class DownloadQqVipData extends BaseAction {
	const MEMCACHED_EXPIRE = 84600; // 24 hours
	                                
	// #PADC#
	const MAIL_RESPONSE = FALSE;
	public function action($get_params) {
		$key = MasterCacheKey::getDownloadQqVipData ();
		$value = apc_fetch ( $key );
		if (! $value) {
			$result = QqVipBonus::getDownloadData ();
			$value = json_encode ( array (
					'res' => RespCode::SUCCESS,
					'bonuses' => $result 
			) );
			apc_store ( $key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire () );
		}
		return $value;
	}
}