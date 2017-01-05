<?php
/**
 * Admin用：デバッグユーザー登録
 */
class AdminSetDebugUser extends AdminBaseAction {
	public function action($params) {

		$user_id = $params ['pid'];
		$mt_flg	= $params['maintenance'];
		$cc_flg	= $params['cheat_check'];
		$dc_flg	= $params['drop_change'];
		$round_prob	= $params['round_prob'];
		$plus_prob	= $params['plus_prob'];
		$skillup_flg	= $params['skillup_change'];
		$skillup_prob	= $params['skillup_prob'];
		
		if ($user_id) {
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
			$openid = $userDeviceData['oid'];
		}
		else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = null;
			try{
				$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
			} catch ( PadException $e ) {
				// ユーザーが見つからないエラーはそのまま続行する
				// openIdが何も入力されていない場合はエラー
				if (!$openid) {
					throw $e;
				}
			}
		}

		try{
			$pdo = Env::getDbConnectionForShare();
			$pdo->beginTransaction();

			// デバッグユーザーデータを取得
			$debug_user = DebugUser::findBy( array ("open_id" => $openid), $pdo );
			if ($debug_user) {
				$b_data = clone $debug_user;
				$debug_user->user_id = $user_id;
				$debug_user->maintenance_flag = $mt_flg;
				$debug_user->cheatcheck_flag = $cc_flg;
				$debug_user->dropchange_flag = $dc_flg;
				$debug_user->drop_round_prob = $round_prob;
				$debug_user->drop_plus_prob = $plus_prob;
				$debug_user->skillup_change_flag = $skillup_flg;
				$debug_user->skillup_change_prob = $skillup_prob;
				$debug_user->update($pdo);
				
				Padc_Log_Log::debugToolEditDBLog( "AdminSetDebugUser " . BaseModel::timeToStr(time()) . " " . json_encode($b_data) . " => " . json_encode($debug_user) );
			}
			else {
				$debug_user = new DebugUser();
				$debug_user->open_id = $openid;
				$debug_user->user_id = $user_id;
				$debug_user->maintenance_flag = $mt_flg;
				$debug_user->cheatcheck_flag = $cc_flg;
				$debug_user->dropchange_flag = $dc_flg;
				$debug_user->drop_round_prob = $round_prob;
				$debug_user->drop_plus_prob = $plus_prob;
				$debug_user->skillup_change_flag = $skillup_flg;
				$debug_user->skillup_change_prob = $skillup_prob;
				$debug_user->create($pdo);
				
				Padc_Log_Log::debugToolEditDBLog( "AdminSetDebugUser " . BaseModel::timeToStr(time()) . " " . json_encode($debug_user) );
			}

			$pdo->commit();

			// キャッシュクリア
			$redis = Env::getRedisForShare();
			$key = RedisCacheKey::getDebugUserKey();
			$redis->delete($key);
		}
		catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		Padc_Log_Log::debugToolEditDBLog( "AdminSetDebugUser End Success");
		
		header( 'Location: ./api_admin.php?action=admin_check_db_debug_user&request_type='.TYPE_SET_DEBUG_USER.'&backlink=1' );
		return json_encode ( array(RespCode::SUCCESS) );
	}

}
