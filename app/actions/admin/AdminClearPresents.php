<?php
/**
 * Admin用：スタミナプレゼントをテストする為に、送ったプレゼントをクリアします
 */
class AdminClearPresents extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		
		$redis = Env::getRedisForUser();
		$key = CacheKey::getFriendsSendPresent ( $user_id );
		$redis->delete ( $key );
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		$stmt = $pdo->prepare ( 'DELETE FROM ' . UserPresentsSend::TABLE_NAME . ' WHERE sender_id = ?' );
		$stmt->bindParam ( 1, $user_id );
		$result = $stmt->execute ();
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . 'DELETE FROM ' . UserPresentsSend::TABLE_NAME . ' WHERE sender_id = ' . $user_id), Zend_Log::DEBUG );
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
}
