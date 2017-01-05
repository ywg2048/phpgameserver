<?php
/**
 * Admin用：ユーザー図鑑データ確認
 */
class AdminCheckUserBook extends AdminBaseAction {
	public function action($params) {
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
		$unregistered = '<span class="caution">未登録</span>';

		//--------------------------------------------------
		// PDO
		//--------------------------------------------------

		$pdo_user	= Env::getDbConnectionForUserRead($user_id);
		$pdo_share	= Env::getDbConnectionForShareRead();

		//--------------------------------------------------
		// ユーザ詳細情報を取得
		//--------------------------------------------------
		$user = User::find($user_id,$pdo_user,true);
		if($user == false)
		{
			$userInfo = array(
					'ユーザ詳細情報' => $unregistered,
			);
		}
		else {
			$userInfo = array(
					'プレイヤー名'			=> $user->name,
					'図鑑登録数'			=> $user->book_cnt,
			);
		}

		// モンスター名取得
		$card = new Card();
		$cardNames = self::getNamesByDao($card);

		// ユーザー図鑑データ取得
		$user_book = UserBook::getByUserId($user_id, $pdo_user);
		$card_ids = $user_book->getCardArray();

		$user_book_array = array(
				'format' => 'array',
				array('モンスター（ID順）')
		);
		foreach ($card_ids as $key => $cid) {
			$user_book_array[] = array(self::getNameFromArray($cid, $cardNames));
		}

		//--------------------------------------------------
		// レスポンス整形
		//--------------------------------------------------
		$result = array(
				'format' => 'array',
				'ユーザ情報'				=> $userInfo,
				'図鑑データ'				=> $user_book_array,
		);


		return json_encode ( $result );
	}
}
