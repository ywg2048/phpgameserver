<?php

/**
 * #PADC_DY#
 *
 * 获取魔法石兑换商品列表
 */
class GetExchangeMagicStoneProductList extends BaseAction
{
	public function action($params)
	{	
		//首先检测活动是否开放
		$time = time();
		$events_type = LimitEventsTime::MAGIC_STONE_SHOP;
		$events_id = LimitEventsTime::getTypeById($events_type);
		if($events_id == null){
            throw new PadException(RespCode::ACTIVITY_NOT_IN_TIME, "It's not in events time");
        }
		if(!LimitEventsTime::isEnabled($time,$events_id)){
			throw new PadException(RespCode::ACTIVITY_NOT_IN_TIME, "It's not in activity time");
		}
		
     
		$user_id = $params['pid'];
		$refresh = !empty($params['refresh']) ? true : false;
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			throw new PadException(RespCode::TENCENT_TOKEN_ERROR, "token error");
		}
		$pdoShare = Env::getDbConnectionForShareRead();
		//查询有没有记录
		$user_record_type = UserMagicStoneRecord::findAllBy(array('id'=>$user_id,'events_id'=>$events_id));
		$user_record1 = UserMagicStoneRecord::find($user_id);
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		if($user_record1 && !$user_record_type){
			//有其他期的记录，没有本次记录，则更新之前的记录
			$user_record1->id = $user_id;
			$user_record1->events_id = $events_id;
			$user_record1->exchange_list = "";
			$user_record1->exchange_record = "";
			$user_record1->refresh_times = 0;
			$ret = $user_record1->update($pdo);
		}
		$exchangeItems = ExchangeMagicStoneProduct::getActiveExchangeItems($user_id,$refresh, $pdoShare, $token);
		if (empty ($exchangeItems)) {
			return json_encode(array(
				"res" => RespCode::SUCCESS,
				"items" => null
			));
		}
		$res = array();
		foreach ($exchangeItems as $item) {
			$res[] = array(
				'id' => (int)$item->id,
				'gold' => (int)$item->cost,
				'bonus_id' => (int)$item->bonus_id,
				'amount' => (int)$item->amount,
				'piece_id' => (int)$item->piece_id,
				'mark' => $item->mark,
				// 'file' => $item->file
			);
		}

		$user = User::find($user_id);

		if (!$user) {
			throw new PadException(RespCode::USER_NOT_FOUND, 'user not found');
		}

		$user_record = UserMagicStoneRecord::find($user_id);
		//第一次打开商店
		if(!$user_record){
			//没有记录则新建
				$record = new UserMagicStoneRecord();
				$record->id= $user_id;
				$record->exchange_gold = 0;
				$record->refresh_times = 0;
				$record->events_id = $events_id;
				$record->create($pdo);

			return json_encode(array(
				"res" => RespCode::SUCCESS,
				"gold" => ($user->gold + $user->pgold),
				"items" => $res,
				"refresh_time" => GameConstant::getParam('ExchangeMagicStoneShopRefreshTime'),
	            'exchange_record' => array(),
				"time" => time(),
				"refresh_price" => 0,
				"left_refresh_times" => GameConstant::getParam('MaxRefresh'),
				"is_first_refresh" => 1,
			));
		}
		//首次刷新免费，之后就需要扣魔法石
		if($user_record->refresh_times==0){
				return json_encode(array(
				"res" => RespCode::SUCCESS,
				"gold" => ($user->gold + $user->pgold),
				"items" => $res,
				"refresh_time" => GameConstant::getParam('ExchangeMagicStoneShopRefreshTime'),
	            'exchange_record' => $user_record->getExchangeRecord(),
				"time" => time(),
				"refresh_price" => 0,
				"left_refresh_times" => max(GameConstant::getParam('MaxRefresh')-$user_record->refresh_times+1,0),
				"is_first_refresh" => 1,
			));
		}
		//不是第一次刷新
		return json_encode(array(
			"res" => RespCode::SUCCESS,
			"gold" => ($user->gold + $user->pgold),
			"items" => $res,
			"refresh_time" => GameConstant::getParam('ExchangeMagicStoneShopRefreshTime'),
            'exchange_record' => $user_record->getExchangeRecord(),
			"time" => time(),
			"refresh_price" => GameConstant::getParam("ExchangeMagicStoneRefreshGold".min(8,$user_record->refresh_times)),
			"left_refresh_times" => max(GameConstant::getParam('MaxRefresh')-$user_record->refresh_times+1,0),
			"is_first_refresh" => 0,
		));
	}
}