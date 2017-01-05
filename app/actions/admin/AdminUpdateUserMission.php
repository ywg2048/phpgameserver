<?php
/**
 * Admin用：ユーザーミッション変更
 */
class AdminUpdateUserMission extends AdminBaseAction {
	public function action($params) {
		$user_id = isset($params['pid']) ? $params['pid'] : 0;
		
		$mission_id = isset($params['mid']) ? $params['mid'] : 0;
		$update_status = isset($params['status']) ? $params['status'] : 0;
		$ordered_at = isset($params['ordered_at']) ? $params['ordered_at'] : 0;
		
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
		
			// ミッションデータを取得
			$mission = Mission::get($mission_id);
		
			// ユーザーのミッションデータを取得
			$user_mission = UserMission::findBy(array(
					"user_id" => $user_id,
					"mission_id" => $mission_id,
			), $pdo);
				
			if ($user_mission) {
				if ($update_status == UserMission::STATE_NONE) {
					if ($mission && $mission->mission_type == Mission::MISSION_TYPE_DAILY) {
						$user_mission->status = $update_status;
						$user_mission->ordered_at = $ordered_at;
						$user_mission->update($pdo);
					}
					else {
						$user_mission->delete($pdo);
					}
				}
				else {
					$user_mission->user_id = $user_id;
					$user_mission->mission_id = $mission_id;
					$user_mission->status = $update_status;
					$user_mission->ordered_at = $ordered_at;
					$user_mission->update($pdo);
				}
			}
			else {
				if ($update_status != UserMission::STATE_NONE) {
					$user_mission = new UserMission();
					$user_mission->user_id = $user_id;
					$user_mission->mission_id = $mission_id;
					$user_mission->status = $update_status;
					$user_mission->ordered_at = $ordered_at;
					$user_mission->create($pdo);
				}
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
