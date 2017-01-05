<?php
/**
 * #PADC# download all login bonuses data
 * 
 * if apc have no stored data,get data from db and then cache it,else get it from cache
 * @author zhudesheng
 *
 */
class DownloadLoginBonusData extends BaseAction {
	const MEMCACHED_EXPIRE = 84600; //24 hours
	 // #PADC#
  	const MAIL_RESPONSE = FALSE;
	//http://pad.localhost/api.php?action=Download_Login_Bonus_Data&pid=1&sid=1$key=jset
	public function action($get_params){
		$key = MasterCacheKey::getDownloadLoginBonusData();
		$value = apc_fetch($key);
		if(!$value){
			$result = LoginTotalCountBonus::getAllBonuses();
			$value = json_encode(array('res'=>RespCode::SUCCESS,'login_bonuses'=>$result));
			apc_store($key, $value,static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
		}
		return $value;
	}
}