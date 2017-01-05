<?php
/**
 * Admin用：ユーザー削除
 */
class AdminDeleteUser extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		
		$pdo_share = Env::getDbConnectionForShare ();
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		try {
			$pdo_share->beginTransaction ();
			$pdo->beginTransaction ();
			
			$user = User::find ( $user_id, $pdo, true );
			if ($user->del_status == USER::STATUS_DEL) {
				throw new PadException ( RespCode::UNKNOWN_ERROR, 'User already deleted!' );
			}
			$user->del_status = USER::STATUS_DEL;
			$user->update ( $pdo );
			
			$user_device = UserDevice::findBy ( array (
					'id' => $user_id 
			), $pdo_share );
			if ($user_device == false) {
				throw new PadException ( RespCode::USER_NOT_FOUND, 'user_device not found!' );
			}

			// キャッシュ削除用に退避
			$type	= $user_device->type;
			$openid	= $user_device->oid;
			$uuid	= $user_device->uuid;

			$user_device->oid = $user_device->oid . '_' . time ();
			$user_device->update ( $pdo_share );

			// キャッシュにデータがあればそちらも削除する
			$key = CacheKey::getUserDeviceByUserId($user_id);
			$redis = Env::getRedisForUser();
			if($redis->exists($key))
			{
				$redis->del($key);
			}
			$key = CacheKey::getUserIdFromUserOpenId($type, $openid);
			if($redis->exists($key))
			{
				$redis->del($key);
			}
			$key = CacheKey::getUserIdFromUserDeviceKey($type, $uuid, $openid);
			if($redis->exists($key))
			{
				$redis->del($key);
			}

			$pdo_share->commit ();
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo_share->inTransaction ()) {
				$pdo_share->rollBack ();
			}
			if ($pdo->inTransaction ()) {
				$pdo->rollBack ();
			}
			throw $e;
		}
		$result = array (
				'res' => RespCode::SUCCESS 
		);
		return json_encode ( $result );
	}
}
