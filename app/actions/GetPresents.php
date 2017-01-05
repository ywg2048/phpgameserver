<?php
/**
 * #PADC#
 */
class GetPresents extends BaseAction {
	public function action($params) {
		$user_id = $params ['pid'];
		
		$presents = UserPresentsReceive::getUnreceivedPresents ( $user_id );

		$user = User::find($user_id);
		if(!$user){
			throw new PadException(RespCode::USER_NOT_FOUND);
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS,
				'presents' => static::arrangeColumns ( $presents, $user_id ),
				'limit' => UserPresentsReceive::getPresentLimit($user),
		) );
	}

	/**
	 * リストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
	 *
	 * @param array $presents
	 * @return array
	 */
	public static function arrangeColumns($presents, $user_id) {
		$pdo_receiver = Env::getDbConnectionForUserRead ( $user_id );

		$mapper = array ();
		foreach ( $presents as $present ) {
			$arr = array ();
			$arr [] = (int)$present->id;
			$sender_id = $present->sender_id;
			$arr [] = (int)$sender_id;
			$arr [] = date('ymdHis',strtotime($present->expire_at));
			$mapper [] = $arr;
		}
		return $mapper;
	}
}
