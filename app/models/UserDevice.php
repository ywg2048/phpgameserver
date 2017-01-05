<?php
/**
 * ユーザ端末モデル.
 */
class UserDevice extends BaseModel {
	const TABLE_NAME = "user_devices";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	const TYPE_IOS = 0;
	const TYPE_ADR = 1;
	const TYPE_AMZ = 2;

	// #PADC# ----------begin----------

	// platform type
	const PTYPE_GUEST	= 0;// guest login
	const PTYPE_QQ		= 1;// QQ user
	const PTYPE_WECHAT	= 2;// WeChat user

	// ユーザIDの接頭値
	const PRE_USER_ID_ANDROID_QQ		= 1;
	const PRE_USER_ID_ANDROID_WECHAT	= 2;
	const PRE_USER_ID_IOS_QQ			= 3;
	const PRE_USER_ID_IOS_WECHAT		= 4;
	const PRE_USER_ID_IOS_GUEST			= 5;

	// #PADC# ----------end----------

	protected static $columns = array(
		'type',
		'uuid',
		'version',
		'id',
		'dbid',
	// #PADC#
		'oid',
		'ptype',
	);

	public static $type_names = array(
		0 => 'iOS', 1 => 'Android', 2 => 'Kindle'
	);

	/**
	 * ユーザのuuid付け替え
	 */
	public static function changeUuid($user_id, $target_user_id, $pdo, $admin_id = null, $old_del = FALSE) {

		$log_data = array();

		$user_device = UserDevice::findBy(array("id" => $user_id), $pdo);
		$target_user_device = UserDevice::findBy(array("id" => $target_user_id), $pdo);
		$user_uuid = $user_device->uuid;
		$user_type = $user_device->type;
		$target_user_uuid = $target_user_device->uuid;
		$target_user_type = $target_user_device->type;

		// #PADC# ----------begin----------memcache→redisに切り替え
		$redis = Env::getRedisForUser();
		// キャッシュのユーザー情報削除.
		$key = CacheKey::getUserIdFromUserDeviceKey($user_type, $user_uuid);
		$redis->delete($key, 0);
		$key = CacheKey::getUserIdFromUserDeviceKey($target_user_type, $target_user_uuid);
		$redis->delete($key, 0);
		// キャッシュのセッション情報削除
		$key = CacheKey::getUserSessionKey($user_id);
		$redis->delete($key, 0);
		$key = CacheKey::getUserSessionKey($target_user_id);
		$redis->delete($key, 0);

		// それぞれのデバイスタイプも付け替え.
		$pdo_user1 = Env::getDbConnectionForUserWrite($user_id);
		$pdo_user1->beginTransaction();
		// #PADC# ----------begin----------
		if($target_user_device->dbid == $user_device->dbid)
		{
			$pdo_user2 = $pdo_user1;
		}
		else
		{
			$pdo_user2 = Env::getDbConnectionForUserWrite($target_user_id);
			$pdo_user2->beginTransaction();
		}
		// #PADC# ----------end----------

		try{
			$change_device_logs = UserLogChangeDevice::findAllBy(array("user_id" => $target_user_id), null, null, $pdo_user2);
			foreach($change_device_logs as $cdl){
				$add_time = strtotime($cdl->created_at);
				// 10秒以内のログ存在チェック.
				if((time() - $add_time) < 10){
					$pdo_user1->rollback();
					// #PADC# ----------begin----------
					if ($pdo_user2->inTransaction()) {
						$pdo_user2->rollback();
					}
					// #PADC# ----------end----------
					return FALSE;
				}
			}
			$user1 = User::find($user_id, $pdo_user1, TRUE);
			$log_data['new_uuid'] = $user_uuid;
			$log_data['new_lv'] = $user1->lv;
			$log_data['new_dev'] = $user1->dev;
			$log_data['new_osv'] = $user1->osv;
			$log_data['new_type'] = $user1->device_type;
			$log_data['new_area'] = $user1->area_id;
			$user1->device_type = $target_user_type;
			if($old_del){
				// 旧ユーザを削除.
				$user1->del_status = User::STATUS_DEL;
				// #PADC# ----------begin----------memcache→redisに切り替え
				// キャッシュからも削除.
				$redis = Env::getRedisForUser();
				$key = CacheKey::getUserFriendDataFormatKey2($user1->id);
				$redis->delete($key);
			}
			$user1->update($pdo_user1);

			$user2 = User::find($target_user_id, $pdo_user2, TRUE);
			// 引き継ぎ先が通常ユーザー以外の場合はエラーとする.
			if($user2->del_status != User::STATUS_NORMAL){
				$pdo_user1->rollback();
				// #PADC# ----------begin----------
				if ($pdo_user2->inTransaction()) {
					$pdo_user2->rollback();
				}
				// #PADC# ----------end----------
				return FALSE;
			}
			$log_data['old_accessed_at'] = $user2->accessed_at;
			$log_data['old_uuid'] = $target_user_uuid;
			$log_data['old_lv'] = $user2->lv;
			$log_data['old_dev'] = $user2->dev;
			$log_data['old_osv'] = $user2->osv;
			$log_data['old_type'] = $user2->device_type;
			$log_data['old_area'] = $user2->area_id;

			$log_data['before_gold'] = $user2->gold;
			$log_data['before_pgold'] = $user2->pgold;
			$user2->device_type = $user_type;
			$log_data['after_gold'] = $user1->gold;
			$log_data['after_pgold'] = $user1->pgold;
			$user2->update($pdo_user2);

			UserLogChangeDevice::log($target_user_id, $user_id, $log_data, $admin_id, $pdo_user2);
			$log_date=date("YmdHis");
			Friend::getsnapshots($user_id,$log_date);
			UserCard::getsnapshots($user_id,$log_date);
			UserDeck::getsnapshots($user_id,$log_date);
			UserDungeonFloor::getsnapshots($user_id,$log_date);
			User::getsnapshots($user_id,$log_date);
			Friend::getsnapshots($target_user_id,$log_date);
			UserCard::getsnapshots($target_user_id,$log_date);
			UserDeck::getsnapshots($target_user_id,$log_date);
			UserDungeonFloor::getsnapshots($target_user_id,$log_date);
			User::getsnapshots($target_user_id,$log_date);

			// キー重複対策で一旦ダミーのUUIDをセット.
			$user_device->uuid = $user_device->uuid."_".date("YmdGis");
			$user_device->update($pdo);

			$target_user_device->uuid = $user_uuid;
			$target_user_device->type = $user_type;
			$target_user_device->update($pdo);

			$user_device = UserDevice::findBy(array("id" => $user_id), $pdo);
			if(!$old_del){
				// uuidを戻す.
				$user_device->uuid = $target_user_uuid;
			}
			$user_device->type = $target_user_type;
			$user_device->update($pdo);

			$pdo_user1->commit();
			// #PADC# ----------begin----------
			if ($pdo_user2->inTransaction()) {
				$pdo_user2->commit();
			}
			// #PADC# ----------end----------

		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo_user1->inTransaction()) {
				$pdo_user1->rollback();
			}
			if ($pdo_user2->inTransaction()) {
				$pdo_user2->rollback();
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * ユーザのuuid変更
	 */
	public static function replaceUuid($user_id, $uuid, $type, $pdo_share, $debug = null, $write_log = FALSE) {

		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$pdo->beginTransaction();
		try{
			$user = User::find($user_id, $pdo, TRUE);
			// 引き継ぎが通常ユーザー以外の場合はエラーとする.
			if($user->del_status != User::STATUS_NORMAL){
				$pdo->rollback();
				return FALSE;
			}
			$user_device = UserDevice::findBy(array("id" => $user_id), $pdo_share);
			// 同じuuidのユーザが存在した場合、ダミーのuuidに置き換える(デバッグ用).
			if($debug===TRUE){
				$user_device_org = UserDevice::findBy(array("type" => $type, "uuid" => $uuid), $pdo_share);
				if($user_device_org){
					// #PADC# ----------begin----------memcache→redisに切り替え
					$redis = Env::getRedisForUser();
					$key = CacheKey::getUserIdFromUserDeviceKey($type, $uuid);
					$redis->delete($key, 0);
					$key = CacheKey::getUserSessionKey($user_device_org->id);
					$redis->delete($key, 0);
					$user_device_org->uuid .= "" . time();
					$user_device_org->update($pdo_share);
				}
			}else{
				$log_data = array();
				$log_data['new_uuid'] = $uuid;
				$log_data['new_dev'] = "";
				$log_data['new_osv'] = "";
				$log_data['new_type'] = $type;
				if(defined('Env::SERVICE_AREA') && defined('User::AREA_'.Env::SERVICE_AREA)) {
						$log_data['new_area'] = constant('User::AREA_'.Env::SERVICE_AREA);
				}else{
						$log_data['new_area'] = "";
				}
				$log_data['old_accessed_at'] = $user->accessed_at;
				$log_data['old_uuid'] = $user_device->uuid;
				$log_data['old_lv'] = $user->lv;
				$log_data['old_dev'] = $user->dev;
				$log_data['old_osv'] = $user->osv;
				$log_data['old_type'] = $user->device_type;
				$log_data['old_area'] = $user->area_id;
				$log_data['before_gold'] = $user->gold;
				$log_data['before_pgold'] = $user->pgold;
			}
			// #PADC# ----------begin----------memcache→redisに切り替え
			$redis = Env::getRedisForUser();
			$key = CacheKey::getUserIdFromUserDeviceKey($user_device->type, $user_device->uuid);
			$redis->delete($key, 0);
			$key = CacheKey::getUserSessionKey($user_device->id);
			$redis->delete($key, 0);
			$user_device->uuid = $uuid;
			$user_device->type = $type;
			$user_device->update($pdo_share);
			if($user->device_type != $type){
				$user->device_type = $type;
				$user->update($pdo);
			}
			if(isset($log_data) && $write_log === TRUE){
				UserLogChangeDevice::log($user_id, null, $log_data, null, $pdo);
				$log_date=date("YmdHis");
				Friend::getsnapshots($user_id,$log_date);
				UserCard::getsnapshots($user_id,$log_date);
				UserDeck::getsnapshots($user_id,$log_date);
				UserDungeonFloor::getsnapshots($user_id,$log_date);
				User::getsnapshots($user_id,$log_date);
			}
			$pdo->commit();
		// #PADC# PDOException → Exception
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * #PADC# get userid list by openid list
	 *
	 * @param array $openids
	 * @return array
	 */
	public static function findAllUserIdByOpenids($openids){
		if(empty($openids)){
			return array();
		}

		$pdo = Env::getDbConnectionForShare();
		$sql = "SELECT * FROM " . static::TABLE_NAME;

		$num = count($openids);

		$sql .= " WHERE oid IN ( " . str_repeat('?,',count($openids) - 1) . "? )";

		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->execute($openids);
		$objs = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

		if(Env::ENV !== "production"){
			global $logger;
			$logger->log("sql_query: ".$sql." bind: ".join(",",$openids), Zend_Log::DEBUG);
		}

		$result = array();
		foreach($objs as $device){
			$result[] = $device->id;
		}

		return $result;
	}


	/**
	 * #PADC#
	 *
	 * @param int $type
	 * @param int uuid
	 * @param string openid
	 * @throws PadException
	 * @return int
	 */
	public static function getUserIdFromUserDeviceKey($type, $uuid, $openid)
	{
		// #PADC# ----------begin----------memcache→redisに切り替え
		$rRedis = Env::getRedisForUserRead();
		$key = CacheKey::getUserIdFromUserDeviceKey($type, $uuid, $openid);
		$user_id = $rRedis->get($key);
		if($user_id === FALSE)
		{
			$pdo_share = Env::getDbConnectionForShare();
			$user_device = UserDevice::findBy(array('type' => $type, 'oid' => $openid), $pdo_share);
			if($user_device)
			{
				$user_id = $user_device->id;
				if($user_device->uuid != $uuid)
				{
					$user_device->uuid = $uuid;
					$user_device->update($pdo_share);
				}
				$redis = Env::getRedisForUser();
				$redis->set($key, $user_id, static::MEMCACHED_EXPIRE);
			}
			else
			{
				// 登録上限チェック
				if(Env::CHECK_SIGNUP_LIMIT)
				{
					$rRedis = Env::getRedisForShareRead();
					SignupLimit::checkSignupLimit(Padc_Time_Time::getDate("Y-m-d"),$rRedis,$openid);
				}
				throw new PadException(RespCode::USER_NOT_FOUND, "User device not found!");
			}
		}
		return $user_id;
	}

	/**
	 *
	 * @param number $type
	 * @param string $openid
	 * @throws PadException
	 * @return number
	 */
	public static function getUserIdFromUserOpenId($type, $openid)
	{
		// #PADC# ----------begin----------memcache→redisに切り替え
		$rRedis = Env::getRedisForUserRead();
		$key = CacheKey::getUserIdFromUserOpenId($type, $openid);
		$user_id = $rRedis->get($key);
		if($user_id === FALSE) {
			$pdo_share = Env::getDbConnectionForShare();
			$user_device = UserDevice::findBy(array('type' => $type, 'oid' => $openid), $pdo_share);
			if($user_device){
				$user_id = $user_device->id;
				$redis = Env::getRedisForUser();
				$redis->set($key, $user_id, static::MEMCACHED_EXPIRE);
			}else{
				throw new PadException(RespCode::USER_NOT_FOUND, "User device not found!");
			}
		}
		return $user_id;
	}

	/**
	 * #PADC#
	 *
	 * @param int $user_id
	 * @throws PadException
	 * @return int
	 */
	public static function getUserDeviceType($user_id)
	{
		$userDeviceData = self::getUserDeviceFromRedis($user_id);
		return $userDeviceData['t'];
	}

	/**
	 * #PADC#
	 *
	 * @param int $user_id
	 * @throws PadException
	 * @return int
	 */
	public static function getUserPlatformType($user_id)
	{
		$userDeviceData = self::getUserDeviceFromRedis($user_id);
		return $userDeviceData['pt'];
	}

	/**
	 * #PADC#
	 *
	 * @param int $user_id
	 * @throws PadException
	 * @return string
	 */
	public static function getUserOpenId($user_id)
	{
		$userDeviceData = self::getUserDeviceFromRedis($user_id);
		return $userDeviceData['oid'];
	}

	/**
	 * #PADC#
	 *
	 * @param number $user_id
	 * @throws PadException
	 * @return string
	 */
	public static function getUserDeviceVersion($user_id)
	{
		$userDeviceData = self::getUserDeviceFromRedis($user_id);
		return $userDeviceData['v'];
	}

	/**
	 * #PADC#
	 * @param int $user_id
	 * @throws PadException
	 * @return array('t' => type, 'oid' => openid, 'pt' => platform_type, 'v' => version);
	 */
	public static function getUserDeviceFromRedis($user_id)
	{
		// #PADC# ----------begin----------memcache→redisに切り替え
		$key = CacheKey::getUserDeviceByUserId($user_id);
		$rRedis = Env::getRedisForUserRead();
		$userDeviceData = $rRedis->get($key);
		if($userDeviceData === FALSE)
		{
			$pdo_share = Env::getDbConnectionForShare();
			$user_device = UserDevice::find($user_id, $pdo_share);
			if($user_device)
			{
				$redis = Env::getRedisForUser();
				$userDeviceData = self::setUserDeviceToRedis(
					$redis,
					$key,
					$user_device->type,
					$user_device->oid,
					$user_device->ptype,
					$user_device->version
				);
			}else{
				throw new PadException(RespCode::USER_NOT_FOUND, "User device not found!");
			}
		}
		return json_decode($userDeviceData,true);
	}

	/**
	 * プレイヤーIDをキーとしてユーザ情報をRedisに保存
	 * @param RedisObject $redis
	 * @param string $key
	 * @param int $type
	 * @param string $openid
	 * @param int $ptype
	 * @param float $version
	 */
	public static function setUserDeviceToRedis($redis,$key,$type,$openid,$ptype,$version)
	{
		$tmpData = array(
			't'		=> $type,
			'oid'	=> $openid,
			'pt'	=> $ptype,
			'v'		=> $version,
		);
		$userDeviceData = json_encode($tmpData);
		$redis->set($key, $userDeviceData, UserDevice::MEMCACHED_EXPIRE);
		return $userDeviceData;
	}

	/**
	 * 登録されているユーザIDの最大値を取得
	 * @param date $date Y-m-d H:i:s
	 * @return number
	 */
	public static function getMaxUserId($date=null)
	{
		$maxUserId = 0;
		$pdo = Env::getDbConnectionForShareRead();

		$sql = "SELECT id FROM " . static::TABLE_NAME;
		if($date)
		{
			$sql .= ' where created_at < ? ';
		}
		$sql .= " ORDER BY id DESC LIMIT 1;";
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		if($date)
		{
			$stmt->bindParam(1,$date);
		}
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_CLASS);
		if($result)
		{
			$maxUserId = (int)$result->id;
		}
		return $maxUserId;
	}
	
	/**
	 * 
	 * @param number $user_ids
	 * @param PDO $pdo_share;
	 */
	public static function getOpenids($user_ids, $pdo_share = null){
		//global $logger;
		$openids = array();
		$search_ids = array();
		foreach($user_ids as $user_id){
			$user_device = self::getUserDeviceFromRedis($user_id);
			$key = CacheKey::getUserDeviceByUserId($user_id);
			$rRedis = Env::getRedisForUserRead();
			$user_device_data = $rRedis->get($key);
			if($user_device_data === FALSE){
				$search_ids []= $user_id;
			}else{
				$user_device_data = json_decode($rRedis->get($key));
				$openids[$user_id] = $user_device_data->oid;
			}
		}
		//$logger->log('$openids from cache:'. print_r($openids, true), 7);
		
		if(!empty($search_ids)){
			if(!$pdo_share){
				$pdo_share = Env::getDbConnectionForShareRead();
			}
			$sql = "SELECT * FROM " . static::TABLE_NAME;
			$sql .= " WHERE id IN ( " . str_repeat('?,',count($search_ids) - 1) . "? );";
			
			$bind_param = $search_ids;
			list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo_share);
			$user_devices = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
			foreach($user_devices as $user_device){
				$openids [$user_device->id] = $user_device->oid;
			}
		}
		
		//$logger->log('$openids:'. print_r($openids, true), 7);
		return $openids;
	}

	/**
	 * #PADC#
	 * プラットフォームとOSタイプから先頭IDを取得
	 */
	public static function getPreUserId($devicetype,$ptype)
	{
		if($devicetype == self::TYPE_ADR)
		{
			if($ptype == self::PTYPE_QQ)
			{
				$preUserId = self::PRE_USER_ID_ANDROID_QQ;
			}
			elseif($ptype == self::PTYPE_WECHAT)
			{
				$preUserId = self::PRE_USER_ID_ANDROID_WECHAT;
			}
		}
		elseif($devicetype == self::TYPE_IOS)
		{
			if($ptype == self::PTYPE_QQ)
			{
				$preUserId = self::PRE_USER_ID_IOS_QQ;
			}
			elseif($ptype == self::PTYPE_WECHAT)
			{
				$preUserId = self::PRE_USER_ID_IOS_WECHAT;
			}
			elseif($ptype == self::PTYPE_GUEST)
			{
				$preUserId = self::PRE_USER_ID_IOS_GUEST;
			}
		}

		return $preUserId;
	}
	
	/**
	 * #PADC#
	 * ユーザーIDとプレユーザーIDから表示IDを取得
	 */
	public static function convertPlayerIdToDispId($pre_pid, $user_id) {
		if ($pre_pid == 0)
		{
			// pre_pidが0の時はまだデータがないため0で返す
			return 0;
		}
		// 元々のIDが9桁を下回る場合、頭を0で埋める
		$str = str_split(sprintf("%09d", $user_id));
		
		$ret = strval($pre_pid);
		$ret .= $str[6];
		$ret .= $str[3];
		$ret .= $str[0];
		$ret .= $str[5];
		$ret .= $str[7];
		$ret .= $str[1];
		$ret .= $str[4];
		$ret .= $str[2];
		$ret .= $str[8];
		// 元々のIDが9桁を超える場合、その分を後ろにappend
		for($i = 9; $i < count($str); $i++) {
			$ret .= $str[$i];
		}
		return $ret;
	}
	
	/**
	 * #PADC#
	 * 表示用IDからユーザーIDを取得
	 */
	public static function convertDispIdToPlayerId($disp_id) {
		if ($disp_id == 0)
		{
			// disp_idが0の時はまだデータがないため0で返す
			return 0;
		}
		$str = str_split($disp_id);
		if (count($str) < 10)
		{
			// 9桁を下回るIDは変換失敗として、0で返す
			return 0;
		}
		$ret = '';
		// 先頭はplatformIDなので無視
		$ret .= $str[3];
		$ret .= $str[6];
		$ret .= $str[8];
		$ret .= $str[2];
		$ret .= $str[7];
		$ret .= $str[4];
		$ret .= $str[1];
		$ret .= $str[5];
		$ret .= $str[9];
		// IDが10桁を超える場合、その分を後ろにappend
		for($i = 10; $i < count($str); $i++) {
			$ret .= $str[$i];
		}
		return $ret;
	}
	
}
