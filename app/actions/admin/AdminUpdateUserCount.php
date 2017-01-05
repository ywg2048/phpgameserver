<?php
/**
 * Admin用：ユーザーカウントデータ変更
 */
class AdminUpdateUserCount extends AdminBaseAction {
	public function action($params) {
		$user_id = isset($params['pid']) ? $params['pid'] : 0;

		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			$user_count = UserCount::findBy(array("user_id"=>$user_id), $pdo);
			$columns = UserCount::getColumns();

			$b_data = clone $user_count;
			foreach($columns as $column) {
				if (isset($params[$column])) {
					$user_count->$column = $params[$column];
				}
			}
			Padc_Log_Log::debugToolEditDBLog( "AdminUpdateUserCount " . BaseModel::timeToStr(time()) . " " . json_encode($b_data) . " => " . json_encode($user_count) );
			$user_count->update($pdo);

			$pdo->commit();

		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		Padc_Log_Log::debugToolEditDBLog( "AdminUpdateUserCount End Success");
		
		header( 'Location: ./api_admin.php?action=admin_check_user_count&pid='.$user_id.'&request_type='.TYPE_CHECK_USER_COUNT );
		return json_encode ( RespCode::SUCCESS );
	}

}
