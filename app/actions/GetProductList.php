<?php
/**
 * 課金商品リスト取得
 */
class GetProductList extends BaseAction {

	// http://pad.localhost/api.php?action=get_product_list&pid=2&sid=1
	public function action($params){
		// #PADC# 
		$res = array();
		$res['res'] = RespCode::SUCCESS;

		// 最低価格
		$res['unit_price'] = 1;
		// 最低価格時の個数
		$res['unit_count'] = 10;

		// INFO:必要な情報は name、count、buyable
		// codeとbmsgは現状アプリでは利用していない
		$res['items'] = array(
			array(
				'name' => '魔法石 60个',
				'code' => 'test_code.1001',
				'count' => 60,
				'buyable' => 1,
				'bmsg' => 'test message 1',
			),
			array(
				'name' => '魔法石 300个',
				'code' => 'test_code.1002',
				'count' => 300,
				'buyable' => 1,
				'bmsg' => 'test message 2',
			),
			array(
				'name' => '魔法石 980个',
				'code' => 'test_code.1003',
				'count' => 980,
				'buyable' => 1,
				'bmsg' => 'test message 3',
			),
			array(
				'name' => '魔法石 1980个',
				'code' => 'test_code.1004',
				'count' => 1980,
				'buyable' => 1,
				'bmsg' => 'test message 4',
			),
			array(
				'name' => '魔法石 2580个',
				'code' => 'test_code.1005',
				'count' => 2580,
				'buyable' => 1,
				'bmsg' => 'test message 5',
			),
			array(
				'name' => '魔法石 3280个',
				'code' => 'test_code.1006',
				'count' => 3280,
				'buyable' => 1,
				'bmsg' => 'test message 6',
			),
			array(
				'name' => '魔法石 6480个',
				'code' => 'test_code.1007',
				'count' => 6480,
				'buyable' => 1,
				'bmsg' => 'test message 7',
			),
		);
		
		// #PADC# ----------begin----------
		$subs_daily_info = SubscriptionBonus::getDailyBonusInfo($params["pid"]);
		if(isset($subs_daily_info['daily'])){
			$res['subs'] = $subs_daily_info['daily'];
		}
		if(isset($subs_daily_info['remain_days'])){
			$res['subs_days'] = $subs_daily_info['remain_days'];
		}
		// #PADC# ----------end----------

		// 永久月卡
		$subs_forever_daily_info = SubscriptionBonus::getForeverDailyBonusInfo($params["pid"]);
		if(isset($subs_forever_daily_info['daily'])){
			$res['subsf'] = $subs_forever_daily_info['daily'];
		}
		if(isset($subs_forever_daily_info['get_cnt'])){
			$res['get_cnt'] = $subs_forever_daily_info['get_cnt'];
		}


		return json_encode($res);

		// #PADC# ----------begin----------
		// PAD版の処理はコメントアウトしておきます
		/*
		$tier = 2; // 20歳以上として扱う
		if(isset($params["tier"])){
			$tier = $params["tier"];
		}
		$user_id = $params["pid"];
		$wmode = isset($params['m']) ? $params['m'] : User::MODE_NORMAL;
		$items = ProductList::getBuyAbleProductItems($tier, $user_id, $wmode);
		return json_encode(array('res' => RespCode::SUCCESS, 'items' => $items));
		*/
		// #PADC# ----------end----------
	}
}
