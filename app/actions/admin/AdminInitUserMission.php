<?php
/**
 * Admin用：ユーザーミッション初期化
 */
class AdminInitUserMission extends AdminBaseAction {
	public function action($params) {
		$user_id = isset($params['pid']) ? $params['pid'] : 0;
		
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
		
			// ユーザーのミッションデータを取得
			$user_missions = UserMission::findAllBy(array(
				"user_id" => $user_id,
			), null, null, $pdo);
				
			foreach ($user_missions as $user_mission) {
				$user_mission->delete($pdo);
			}
			
			$pdo->commit();
		
		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		
		//UserMission::updateStatus($user_id, $params['mission']);
		header( 'Location: ./api_admin.php?action=admin_check_user_mission&pid='.$user_id.'&request_type='.TYPE_CHECK_USER_MISSION );
		return json_encode ( RespCode::SUCCESS );
	}

}
