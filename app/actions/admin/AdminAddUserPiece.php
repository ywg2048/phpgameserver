<?php
/**
 * Admin用：ユーザ所持欠片の追加
 */
class AdminAddUserPiece extends AdminBaseAction
{
	/**
	 * @see AdminBaseAction::action()
	 */
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
		$piece_id	= $params ['piece_id'];
		$amount		= $params ['amount'];

		if($user_id === FALSE)
		{
			throw new PadException(RespCode::USER_NOT_FOUND,'user not found!');
		}

		try {
			$pdo = Env::getDbConnectionForUserWrite ( $user_id );
			$pdo->beginTransaction ();

			$user_piece = UserPiece::getUserPiece($user_id, $piece_id, $pdo);
			$user_piece->addPiece($amount, $pdo);
			$user_piece->update($pdo);

			// 図鑑登録数の更新
			$user = User::find ( $user_id, $pdo );
			$user_book = UserBook::getByUserId($user_id, $pdo);
			$user->book_cnt = $user_book->getCountIds();
			$user->update ( $pdo );

			$pdo->commit ();
		} catch ( Exception $e ) {
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
