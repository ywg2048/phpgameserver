<?php
/**
 * 54.Android課金通貨購入
 * 
 *　（使わないAPI）　
 *
 * arguments:
 *   pid プレイヤーID
 *   gold  購入数
 * response:
 *   res 共通エラー番号
 *   url
 *   token 
 */
class AdrPurchaseGold extends BaseAction {
	// api.php?action=adr_purchase_gold&pid=1&gold=1
	public function action($params) {
		$user_id = $params ['pid'];
		$add_gold = $params ['gold'];
		
		//仮通貨購入
		$user = Shop::buyGold ( $user_id, $add_gold );
		$gold = $user->gold + $user->pgold;
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS,
				'url' => 'http://test.test',	//仮URL
				'token' => 'testoken',	//仮token
				'gold' => $gold,	//TODO:削除
		) );
	}
}