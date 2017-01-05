<?php
/**
 * Admin用：助っ人冒険者ユーザー登録
 */
class AdminSetRecommendedHelper extends AdminBaseAction {
	public function action($params) {

		$level = (isset($params['rank']) && $params['rank']) ? $params['rank'] : 0;

		if (isset ( $params ['pid'] ) && $params['pid']) {
			$user_id = $params ['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック

			RecommendedHelperUtil::updateHelpersOfLevel($user_id, $level, false);
		}
		else {
			// pidの指定が無かったら存在するユーザーをランダムに登録
			$pdo_share	= Env::getDbConnectionForShareRead();
			$user_devices = UserDevice::findAllBy(array(), null, null, $pdo_share);
			shuffle($user_devices);

			$loop_cnt = 0;
			foreach ($user_devices as $ud) {
				$user_id = $ud->id;
				$user = User::find($user_id);

				// BANされているユーザーは除外
				if($user->del_status == User::STATUS_NORMAL){
					RecommendedHelperUtil::updateHelpersOfLevel($user_id, $level, false);

					$loop_cnt++;
					if ($loop_cnt >= RecommendedHelperUtil::HELPER_TRIM) {
						break;
					}
				}
			}
		}

		header( 'Location: ./api_admin.php?action=admin_check_recommended_helper&rank1='.$level.'&rank2='.$level.'&request_type='.TYPE_SET_RECOMENDED_HELPER.'&backlink=1' );
		return json_encode ( RespCode::SUCCESS );
	}

}
