<?php
/**
 * Tencent用：
 */
class AdminUpdateUserCoin extends AdminBaseAction
{
	// https://pad.localhost/api_tencent.php?action=tencent_update_user_coin&oid=1&c=1&t=0&key=jset
	public function action($params)
	{
		if(isset($params['pid']))
		{
			$user_id	= $params['pid'];
//			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		}
		else
		{
			$openid		= $params['ten_oid'];
			$type		= $params['t'];
			$user_id	= UserDevice::getUserIdFromUserOpenId($type,$openid);
		}
		$coins = $params['c'];
		
		if((int)$coins < 0 ){
			$result = array(
				'res' => RespCode::UNKNOWN_ERROR
			);
		}else{
			static::updateUsersCoins($user_id, $coins);
			$result = array(
					'res' => RespCode::SUCCESS,
			);
		}
		
		return json_encode($result);
	}
	/**
	 * update user coins
	 * 
	 * @param string $openid
	 * @param int $coins
	 */
	public static function updateUsersCoins($user_id,$coins){
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$user = User::find($user_id,$pdo,true);
		if($user == false){
			throw new PadException(RespCode::USER_NOT_FOUND,'user not found!');
		}
		if($user->coin == (int)$coins){
			return;
		}else{
			try {
				$pdo->beginTransaction();
				
				$user->coin = $coins;
				$user->update($pdo);
				$pdo->commit();
			}catch (Exception $e){
				if($pdo->inTransaction()){
					$pdo->rollback();
				}
				throw $e;
			}
		}
	}
}