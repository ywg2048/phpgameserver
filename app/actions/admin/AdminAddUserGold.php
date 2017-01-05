<?php
/**
 * Admin用：魔法石付与
 */
class AdminAddUserGold extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$gold = $params ['g'];
		$token = Tencent_MsdkApi::checkToken ( $params );
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		$user = User::find ( $user_id, $pdo, true );
		
		$user->getBalance($token, $pdo);
		
		if ($gold > 0) {
			$user->presentGold ( $gold, $token );
		} else if ($gold < 0) {
			$user->payGold ( - $gold, $token, $pdo );
		}
		$user->update ( $pdo );
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS,
				'gold' => $user->gold + $user->pgold 
		) );
	}
}
