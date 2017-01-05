<?php
/**
 * #PADC#
 * ユーザーが利用したガチャ割引データ
 */
class UserGachaDiscount extends BaseModel
{
	const TABLE_NAME = "user_gacha_discount";

	// ID、ユーザーID、割引データID
	protected static $columns = array(
		'id',
		'user_id',
		'discount_id',
	);

	/**
	 * ユーザーが利用した割引データを作成する
	 */
	public static function setUsed($user_id, $discount_id, $pdo) {
		$user_gacha_discount = new UserGachaDiscount();
		$user_gacha_discount->user_id = $user_id;
		$user_gacha_discount->discount_id = $discount_id;
		$user_gacha_discount->create($pdo);
	}

	/**
	 * 指定したユーザーが利用可能な割引データを返す
	 */
	public static function getActiveGachaDiscount($user_id) {
		
		$gacha_discounts = GachaDiscount::getAllActiveGachaDiscount();
		$user_gacha_discounts = UserGachaDiscount::findAllBy(array('user_id' => $user_id));
		
		// 利用済のガチャ割引データIDを抜き出す
		$used_gacha_discount_ids = array();
		foreach ($user_gacha_discounts as $user_gacha_discount) {
			$used_gacha_discount_ids[] = $user_gacha_discount->discount_id;
		}

		$res = array();
		foreach ($gacha_discounts as $gd) {
			if (!in_array($gd->id, $used_gacha_discount_ids)) {
				$r = array();
				$r['id'] = (int)$gd->id;
				$r['gtype'] = (int)$gd->gacha_type;
				$r['gcnt'] = (int)$gd->gacha_count;
				$r['ratio'] = (int)$gd->ratio;
				$r['start'] = strftime("%y%m%d%H%M%S", strtotime($gd->begin_at));
				$r['end'] = strftime("%y%m%d%H%M%S", strtotime($gd->finish_at));
				$res[] = $r;
			}
		}
		return $res;
	}

	/**
	 * デバッグ機能
	 * ユーザーが利用した割引データを削除する
	 */
	public static function deleteGachaDiscount($user_id, $discount_id) {
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();

			// 指定のユーザーデータを取得
			$user_gacha_discounts = UserGachaDiscount::findBy(array('user_id' => $user_id, 'discount_id' => $discount_id), $pdo, TRUE);
			if ($user_gacha_discounts) {
				$user_gacha_discounts->delete($pdo);
			}

			$pdo->commit();

		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
	}
		
}