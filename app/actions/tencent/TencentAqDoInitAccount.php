<?php
/**
 * Tencent Aq用：
 * 
 * @author zhudesheng
 *
 */
class TencentAqDoInitAccount extends TencentBaseAction {
	public function action($params) {
		$openId = $params ['OpenId'];//this is necessary
		$userId = isset($params['RoleId']) ? $params['RoleId'] : null;
		$platId = isset($params ['PlatId']) ? $params ['PlatId'] : null;//ios or andriod
		$cmd = $params ['Cmdid'];
		
		if(!$userId){
			$userId = UserDevice::getUserIdFromUserOpenId ( $platId, $openId );
		}
		if(!$userId){
			throw new PadException(static::ERR_INVALID_REQ,'requst param is incorrect');
		}
		
		//find user instance,else throw a PADCexception
		$pdo = Env::getDbConnectionForUserWrite ( $userId );
		$user = User::find ( $userId, $pdo, true );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		
		//if user have already banned,do nothing
		if($user->del_status != User::STATUS_DEL){
			//if user is playing game,let user log out
			User::kickOff($userId);
			
			// delete user
			$pdo_share = ENV::getDbConnectionForShare();
			try{
				$pdo->beginTransaction();
				$pdo_share->beginTransaction();
				$userDevice = UserDevice::findBy(array('id'=>$userId),$pdo_share);
				if(!$userDevice){
					throw new PadException(RespCode::USER_NOT_FOUND,"user device not find");
				}
				//reserve oid and type,modify user device open id add timestamp
				$oid = $userDevice->oid;
				$type = $userDevice->type;
				$userDevice->oid = $userDevice->oid.'_'.time();
				$userDevice->update($pdo_share);
				
				//update user del_status state
				$user->del_status = user::STATUS_DEL;
				$user->update($pdo);
				//clear user cache
				self::clearUserCache($userId, $openId, $userDevice->uuid, $platId);
				$params = array(
						'oid' => $oid,
						'type' => $type,
						'vip_lv' => $user->vip_lv,
						'tp_gold' => $user->tp_gold,
						'tss_end' => $user->tss_end,
						//maybe add something more
				);
				UserDevicesInherit::SetInheritance($params,$pdo_share);
				
				$pdo->commit();
				$pdo_share->commit();
			}catch (Exception $e){
				if($pdo_share->inTransaction()){
					$pdo_share->rollBack();
				}
				if($pdo->inTransaction()){
					$pdo->rollBack();
				}
				throw $e;
			}
		}
		
		$result = array (
				'res' => RespCode::SUCCESS,
				'msg' => "success",
				'Result' => 0,
				'RetMsg' => "success",
		);
		
		return json_encode ( $result );
	}
		
	/**
	 * delete all cache with inital user
	 * 
	 * @param string $userId
	 * @param string $openId
	 * @param string $uuid
	 * @param int $platId
	 */
	protected static function clearUserCache($userId,$openId,$uuid,$platId){
		
		$key = CacheKey::getUserDeviceByUserId($userId);
		$redis = Env::getRedisForUser();
		if($redis->exists($key))
		{
			$redis->del($key);
		}
		$key = CacheKey::getUserIdFromUserOpenId($platId, $openId);
		if($redis->exists($key))
		{
			$redis->del($key);
		}
		$key = CacheKey::getUserIdFromUserDeviceKey($platId, $uuid, $openId);
		if($redis->exists($key))
		{
			$redis->del($key);
		}
	}
}