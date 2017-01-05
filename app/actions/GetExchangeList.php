<?php
/**
 * get ranking point exchange items list for exchange
 * 
 * @author zhudesheng
 */
class GetExchangeList extends BaseAction {
	public function action($params) {
		$userId = $params ['pid'];
		$pdoShare = Env::getDbConnectionForShareRead ();
		
		$cachedLineups = ExchangeLineUp::getCachedLineups ( $pdoShare );
		$groups = ExchangeLineUp::getLineupGroups ( $cachedLineups );
		$exchangeItems = ExchangeItem::getActiveExchangeItems ( $groups, $pdoShare );
		if (empty ( $exchangeItems )) {
			return json_encode ( array (
					"res" => RespCode::SUCCESS,
					"items" => array (),
					"start_time" => 0,
					"end_time" => 0 
			) );
		}
		
		$itemRemains = UserExchangeRemain::getRemains ( $userId, $exchangeItems, $groups );
		
		// define 3 empty array,it be used for sort
		$limits = $adds = $normals = array ();
		foreach ( $exchangeItems as $item ) {
			// $result is array.It be used for keeping data in special order
			$result = array (
					'id' => ( int ) $item->id,
					'ranking_point' => ( int ) $item->ranking_point,
					'no' => $item->bonus_id,
					'piece_id' => ( int ) $item->piece_id,
					'num' => ( int ) $item->amount,
					'add' => ( int ) $item->add_amount 
			);
			
			// .else return how many remain left
			/**
			 * if exchange times do not have limit,nothing need to do
			 * else use user_exchange_remain table to limit user exchange times
			 */
			if ($item->limit_num != 0) {
				/**
				 * Here have exchange times limit,fetch how many exchange times left,
				 * if not found anything,then set max limit number as remain
				 * ( remain is a field in user_exchange_remain table)
				 */
				// $userExchangeRemain = UserExchangeRemain::findBy ( array (
				// 'user_id' => $userId,
				// "item_id" => $item->id
				// ) );
				if (isset ( $itemRemains [$item->id] )) {
					$result ['remain'] = $itemRemains [$item->id];
				} else {
					$result ['remain'] = $item->limit_num;
				}
			}
			
			// sort result,first limit,second adds,third normal
			if (isset ( $result ['remain'] )) {
				$limits [] = $result;
			} elseif ($result ['add'] > 0) {
				$adds [] = $result;
			} else {
				$normals [] = $result;
			}
		}
		
		$results = array_merge ( $limits, $adds, $normals );
		
		list ( $startTime, $endTime ) = ExchangeLineUp::getActiveLineup ( $cachedLineups );
		
		return json_encode ( array (
				"res" => RespCode::SUCCESS,
				"items" => $results,
				"start_time" => $startTime,
				"end_time" => $endTime 
		) );
	}
}