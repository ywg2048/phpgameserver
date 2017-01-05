<?php
/**
 * Admin用：ユーザーデータ変更
 */
class AdminUpdateUser extends AdminBaseAction {
	public function action($params) {
		$user_id = isset($params['pid']) ? $params['pid'] : 0;
		$user_data_array = $params['user'];

		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			$user = User::find($user_id, $pdo, TRUE);

			foreach($user_data_array as $_key => $_value) {
				$user->$_key = $_value;
			}
			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);

			$pdo->commit();

		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}

		header( 'Location: ./api_admin.php?action=admin_search_user&pid='.$user_id.'&request_type='.TYPE_SEARCH_USER );
		return json_encode ( RespCode::SUCCESS );
	}

}
