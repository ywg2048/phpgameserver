<?php
/**
 * Admin用：友情ポイント変更
 */
class AdminUpdateUserFripnt extends AdminBaseAction
{
	public function action($params)
	{
		if(isset($params['pid']))
		{
			$user_id	= $params['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		}
		else
		{
			$openid		= $params['ten_oid'];
			$type		= $params['t'];
			$user_id	= UserDevice::getUserIdFromUserOpenId($type,$openid);
		}
		$fripnt	= $params['fripnt'];

		// 上限・下限チェック
		$fripnt = (int)$fripnt;
		if($fripnt < 0)
		{
			$fripnt = 0;
		}
		elseif($fripnt > User::MAX_FRIEND_POINT_BEFORE740)
		{
			$fripnt = User::MAX_FRIEND_POINT_BEFORE740;
		}

		self::updateUsersFripnt($user_id, $fripnt);
		$result = array(
			'res' => RespCode::SUCCESS,
		);

		return json_encode($result);
	}

	/**
	 * update user fripnt
	 * @param string $openid
	 * @param int $fripnt
	 */
	public static function updateUsersFripnt($user_id,$fripnt)
	{
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$user = User::find($user_id,$pdo,true);
		if($user == false)
		{
			throw new PadException(RespCode::USER_NOT_FOUND,'user not found!');
		}

		try
		{
			$pdo->beginTransaction();
			$user->fripnt = $fripnt;
			$user->update($pdo);
			$pdo->commit();
		}
		catch (Exception $e)
		{
			if($pdo->inTransaction())
			{
				$pdo->rollback();
			}
			throw $e;
		}
		return;
	}
}