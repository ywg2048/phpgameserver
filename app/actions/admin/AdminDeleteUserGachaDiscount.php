<?php
/**
 * Admin用：ユーザーガチャ初回割引利用データ削除
 */
class AdminDeleteUserGachaDiscount extends AdminBaseAction {
	public function action($params) {
		$user_id = isset($params['pid']) ? $params['pid'] : 0;
		$discount_id = isset($params['dis_id']) ? $params['dis_id'] : 0;
		UserGachaDiscount::deleteGachaDiscount($user_id, $discount_id);
		header( 'Location: ./api_admin.php?action=admin_check_user_gacha_discount&pid='.$user_id.'&request_type='.TYPE_CHECK_USER_GACHA_DISCOUNT );
		return json_encode ( RespCode::SUCCESS );
	}
}
