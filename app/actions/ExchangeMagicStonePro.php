<?php

/**
 * #PADC_DY#
 *
 * 限时兑换魔法石商品
 */
class ExchangeMagicStonePro extends BaseAction
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
		$product_id = $params['product_id'];
		$rev = isset ($params['r']) ? $params['r'] : 0;
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			throw new PadException(RespCode::TENCENT_TOKEN_ERROR, "token error");
		}
		$pdo = Env::getDbConnectionForUserWrite($user_id);

		$user = User::find($user_id, $pdo);
		$before_gold = $user->gold;
		$before_pgold = $user->pgold;

		if (!$user) {
			throw new PadException(RespCode::USER_NOT_FOUND, 'user not found');
		}

		$exchangeItem = ExchangeMagicStoneProduct::get($product_id);
		if (!$exchangeItem) {
			throw new PadException(RespCode::INVALID_EXCHANGE_ITEM);
		}
		if (!$user->checkHavingGold($exchangeItem->cost)) {
			throw new PadException(RespCode::NOT_ENOUGH_MONEY, 'not have enough gold');
		}
		//判断限时 TODO
		

		try {
			$pdo->beginTransaction();

			$bonus_id = (int)$exchangeItem->bonus_id;
			$amount = (int)$exchangeItem->amount;
			$piece_id = (int)$exchangeItem->piece_id;
			$user_record = UserMagicStoneRecord::find($user_id, $pdo);

			if(!$user_record){
				//没有记录则新建
				$record = new UserMagicStoneRecord();
				$record->id= $user_id;
				$record->exchange_gold = $exchangeItem->cost;
				$record->refresh_times = 0;
				$record->events_id = $events_id;
				$record->create($pdo);
				//重新查找刚插入的记录
				$user_record = UserMagicStoneRecord::find($user_id, $pdo);
			}else{
				//有记录则去验证是否买过此物品
				if ($user_record->alreadySoldOut($product_id)) {
					throw new PadException(RespCode::UNKNOWN_ERROR, 'good has already sold out');
				}	
			}
		
			if ($user_record->checkBonusLimit($user,$bonus_id, $amount, $piece_id, $pdo)) {
				throw new PadException(RespCode::ITEM_REACH_MAX, 'item reach max');
			}

			$result = $user_record->applyBonus($user,$bonus_id, $amount, $pdo, null, null, $piece_id);
			//消耗魔法石
			// if($_SERVER['SERVER_NAME'] == "192.168.0.212"){
			// 	//本地环境token有错误
			// 	// $user->paygold($exchangeItem->cost,$token);
			// }else{ 
				$user->payGold($exchangeItem->cost,$token,$pdo);
				$user->accessed_at = User::timeToStr(time());
				$user->accessed_on = $user->accessed_at;
				$user->update($pdo);
			// }
			
			$user_record->addExchangeRecord($product_id);

			$user_record->update($pdo);

			$exchange_history = new UserExchangeMagicStoneHistory();
			$exchange_history->user_id = $user_id;
			$exchange_history->product_id = $product_id;
			$exchange_history->events_id = $events_id;
			$exchange_history->create($pdo);

			$pdo->commit();

			UserTlog::sendTlogMoneyFlow($user,($user->gold + $user->pgold)-($before_gold + $before_pgold), Tencent_Tlog::REASON_MAGICSTONE_SHOP_EXCHANGE, Tencent_Tlog::MONEY_TYPE_DIAMOND,abs($user->gold - $before_gold),abs($user->pgold - $before_pgold),0,0);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			throw $e;
		}
		$response = array(
			'res' => RespCode::SUCCESS,
			'gold' => ($user->gold+$user->pgold),
			'exchange_record' => $user_record->getExchangeRecord(),
			'item' => User::arrangeBonusResponse($result, $rev)
		);


		if (isset($response['item']['card'])) {
			User::reportUserCardNum($user_id, $token['access_token']);
		}

		return json_encode($response);
	}
}