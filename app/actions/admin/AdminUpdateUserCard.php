<?php
/**
 * Admin用：ユーザーカードデータ変更
 */
class AdminUpdateUserCard extends AdminBaseAction {
	public function action($params) {
		$user_id = isset($params['pid']) ? $params['pid'] : 0;
		$cuid = isset($params['cuid']) ? $params['cuid'] : 0;
		
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			$user_card = UserCard::findBy(array("user_id"=>$user_id, "cuid"=>$cuid), $pdo);
			
			$card = $user_card->getMaster();
			$user_card->lv = $params['lv'];
			$user_card->slv = $params['slv'];
			$user_card->equip1 = $params['plus_hp'];
			$user_card->equip2 = $params['plus_atk'];
			$user_card->equip3 = $params['plus_def'];
			$user_card->exp = $card->getExpOnLevel($params['lv']);
			$user_card->update($pdo);
			
			$pdo->commit();

		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		
		header( 'Location: ./api_admin.php?action=admin_check_user_card&pid='.$user_id.'&request_type='.TYPE_CHECK_USER_CARD );
		return json_encode ( RespCode::SUCCESS );
	}

}
