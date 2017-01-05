<?php
/**
 * Tencent用API：ユーザステータス変更
 */
class AdminUpdateUserStatus extends AdminBaseAction
{
	//// https://pad.localhost/api_tencent.php?action=tencent_update_user_status&oid=1&t=0&s=1
	public function action($params)
	{
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$status = $params['s'];
		
		if(0 <= (int)$status && (int)$status <= 6){
			static::updateUserStatus($user_id, $status);
			$result = array(
					'res' => RespCode::SUCCESS,
			);
		}else{
			$result = array(
					'res' => RespCode::UNKNOWN_ERROR
			);
		}
		
		return json_encode($result);
	}
	/**
	 * update user's status
	 * 
	 * @param string $user_id
	 * @param int $status
	 * @throws PDOException
	 */
	public static function updateUserStatus($user_id,$status){
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$user = User::find($user_id,$pdo,true);
		if($user == false){
			throw new PadException(RespCode::USER_NOT_FOUND,'user not found!');
		}
		if($user->del_status == (int)$status){
			return;
		}else{
			try {
				$pdo->beginTransaction();
				$user->del_status = $status;
				$user->update($pdo);
				$pdo->commit();
			}
			catch (Exception $e){
				if($pdo->inTransaction()){
					$pdo->rollback();
				}
				throw $e;
			}
		}
	}
}
