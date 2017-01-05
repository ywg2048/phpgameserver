<?php
/**
 *　キャッシュキー生成Utilクラス
 */
class CacheKey {

	public static function getUserSessionKey($user_id){
		return Env::MEMCACHE_PREFIX . 'user_ses_' . $user_id;
	}

	public static function getRecommendedHelperKey($level, $slot){
		return Env::MEMCACHE_PREFIX . 'rcmd_help_' . $level . '_' . $slot;
	}

	public static function getSessionKey($sessionKey){
		return Env::MEMCACHE_PREFIX . 'ses_' . $sessionKey;
	}

	public static function getUserWavesKey($dungeon_id, $dungeon_floor_id, $random_id) {
		return Env::MEMCACHE_PREFIX . 'user_waves_' . $dungeon_id . '_' . $dungeon_floor_id . '_' . $random_id;
	}

	public static function getUserLockApiKey($pid) {
		return Env::MEMCACHE_PREFIX . 'api_lock_user_' . $pid;
	}

	public static function getSneakDungeon($pid, $dung, $floor, $sneak_time) {
		return Env::MEMCACHE_PREFIX . 'sneak_dungeon_' . $pid . "_" . $dung . "_" . $floor . "_" . $sneak_time;
	}

	public static function getSneakDungeonRound($pid, $dung, $floor, $sneak_time) {
		return Env::MEMCACHE_PREFIX . 'sneak_dungeon_round_' . $pid . "_" . $dung . "_" . $floor . "_" . $sneak_time;
	}

	public static function getUseHelperKey($user_id1, $user_id2) {
		return Env::MEMCACHE_PREFIX . 'use_helper_' . $user_id1 . '_' . $user_id2;
	}

	public static function getFriendPointKey($user_id) {
		return Env::MEMCACHE_PREFIX . 'friend_point_key_' . $user_id;
	}

	public static function getUserFriendDataFormatKey2($user_id) {
//    return Env::MEMCACHE_PREFIX . 'user_friend_data_format_' . $user_id;
		// 一番多く使われるキャッシュなので内容変更ついでにキーを短くした.
		return Env::MEMCACHE_PREFIX . 'ufdf_' . $user_id;
	}

	// #PADC# ----------begin----------
	public static function getUserIdFromUserDeviceKey($type, $uuid, $openid) {
		return Env::MEMCACHE_PREFIX . 'userdevice_' . $type . '_' . $uuid . '_' . $openid;
	}

	public static function getUserIdFromUserOpenId($type, $openid){
		return Env::MEMCACHE_PREFIX . 'userdevice_' . $type . '_' . $openid;
	}

	/*
	public static function getUserPlatformType($user_id) {
		return Env::MEMCACHE_PREFIX . 'user_platform_type_' . $user_id;
	}

	public static function getUserOpenId($user_id){
		return Env::MEMCACHE_PREFIX . 'user_open_id_' . $user_id;
	}

	public static function getUserDeviceVersion($user_id){
		return Env::MEMCACHE_PREFIX . 'user_device_version_' . $user_id;
	}
	*/

	public static function getUserDeviceByUserId($user_id){
		return Env::MEMCACHE_PREFIX . 'user_device_' . $user_id;
	}

	public static function getFriendsSendPresent($user_id){
		return Env::MEMCACHE_PREFIX . 'friends_send_present_' . $user_id;
	}
	public static function getReceivedPresentIds($user_id){
		return Env::MEMCACHE_PREFIX . 'Received__present_Ids_' . $user_id;
	}
	// #PADC# ----------end----------

	public static function getDbIdFromUserDeviceKey($user_id) {
		return Env::MEMCACHE_PREFIX . 'userdbid_' . $user_id;
	}

	public static function getAccessBlock($ip_addr) {
		return Env::MEMCACHE_PREFIX . 'access_block_' . $ip_addr;
	}

	public static function getIpAddrCounter($ip_addr) {
		return Env::MEMCACHE_PREFIX . 'ip_addr_count_' . $ip_addr;
	}

	public static function getUseWHelperKey($user_id1, $user_id2) {
		return Env::MEMCACHE_PREFIX . 'use_w_helper_' . $user_id1 . '_' . $user_id2;
	}

	public static function getPwPstart($pid, $dfloor, $df) {
		return Env::MEMCACHE_PREFIX . 'p_start' . $pid . "_" . $dfloor . "_" . $df;
	}

	public static function getRecommendedWHelperKey($dungeon_floor_id, $slot){
		return Env::MEMCACHE_PREFIX . 'w_rcmd_help_' . $dungeon_floor_id . '_' . $slot;
	}

	public static function getMailCountKey($user_id) {
		return Env::MEMCACHE_PREFIX . 'mail_count_' . $user_id;
	}

	// #PADC# ----------begin----------
	public static function getUserClearDungeonFloors($user_id) {
		return Env::MEMCACHE_PREFIX . 'clear_dungeon_floors_' . $user_id;
	}
	// ランキング
	public static function getSneakRankingDungeon($pid, $ranking_id, $dung, $floor, $sneak_time) {
		return Env::MEMCACHE_PREFIX . 'sneak_ranking_dungeon_' . $pid . "_" . $ranking_id . "_" . $dung . "_" . $floor . "_" . $sneak_time;
	}

	public static function getUserRankingWavesKey($dungeon_id, $dungeon_floor_id, $random_id) {
		return Env::MEMCACHE_PREFIX . 'user_ranking_waves_' . $dungeon_id . '_' . $dungeon_floor_id . '_' . $random_id;
	}
	public static function getUserClearRankingDungeonFloors($user_id) {
		return Env::MEMCACHE_PREFIX . 'clear_ranking_dungeon_floors_' . $user_id;
	}
	public static function getNgMailLists(){
		return Env::MEMCACHE_PREFIX.'ngmail_lists';
	}
	public static function getNgNameLists(){
		return Env::MEMCACHE_PREFIX.'ngname_lists';
	}
	public static function getAllInheritance(){
		return Env::MEMCACHE_PREFIX . 'all_inheritances'; 
	}
	public static function getUserBanMessage($user_id, $punish_type){
		return Env::MEMCACHE_PREFIX . 'user_ban_message_' . $user_id . '_' . $punish_type;
	}
	public static function getUserExchangeRemain($user_id){
		return Env::MEMCACHE_PREFIX . 'user_exchange_remain_' . $user_id;
	}
	
	// #PADC# ----------end----------
}

?>
