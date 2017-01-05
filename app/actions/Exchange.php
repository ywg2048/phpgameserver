<?php
/**
 * exhange action
 * 
 * exchange ranking point for piece | friend point | round and so on 
 * @author zhudesheng
 *
 */
class Exchange extends BaseAction {
	public function action($params) {
		
		$userId = $params ['pid'];
		$itemId = $params ['item_id'];
		$rev = isset ( $params ['r'] ) ? $params ['r'] : 0;
		$access_token = isset ( $params ['ten_at'] ) ? $params ['ten_at'] : null;
		
		$pdoUser = Env::getDbConnectionForUserWrite ( $userId );
		$pdoShare = Env::getDbConnectionForShareRead();
		
		$user = User::find ( $userId, $pdoUser );
		if (! $user) { // not find user
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found' );
		}

		$cachedLineups = ExchangeLineUp::getCachedLineups ( $pdoShare );
		$groups = ExchangeLineUp::getLineupGroups ( $cachedLineups );
		$exchangeItems = ExchangeItem::getActiveExchangeItems($groups, $pdoShare);
		$exchangeItem = null;
		foreach($exchangeItems as $item){
			if($item->id == $itemId){
				$exchangeItem = $item;
				break;
			}
		}
		if(!$exchangeItem){
			throw new PadException ( RespCode::INVALID_EXCHANGE_ITEM );
		}
		
		$bonusId = $exchangeItem->bonus_id;
		$lineupId = $groups[$exchangeItem->group_id][0]['lineup_id'];
		// check whether or not have enough ranking point,if not,throw a exception.
		if (! $user->checkHaveRankingPoint ( $exchangeItem->ranking_point )) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'not have enough ranking_point' );
		}
		
		$userRemain = UserExchangeRemain::findBy ( array (
				'user_id' => $userId,
				'item_id' => $itemId,
				'lineup_id' => $lineupId
		), $pdoUser, true );
		$amount = ( int ) $exchangeItem->amount + ( int ) $exchangeItem->add_amount;
		
		if ($userRemain != false && $userRemain->remain <= 0) {
			// can't exchange any more
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'exceed exchange limit' );
		}
		
		try {
			$pdoUser->beginTransaction ();
			
			if($user->checkBonusLimit($bonusId, $amount, $exchangeItem->piece_id, $pdoUser)){
				throw new PadException( RespCode::ITEM_REACH_MAX, 'item reach max');
			}
			
			$result = $user->applyBonus ( $bonusId, $amount, $pdoUser, null, null, $exchangeItem->piece_id );
			// pay ranking point
			$before_ranking_point = $user->ranking_point;
			$user->ranking_point = ( int ) $before_ranking_point - ( int ) $exchangeItem->ranking_point;
			$after_ranking_point = $user->ranking_point;
			$user->update($pdoUser);

			// create remain record or update it
			if ($exchangeItem->limit_num != 0) { // limit
				if ($userRemain === FALSE) {
					$userRemain = new UserExchangeRemain ();
					// have already use 1,so it minus 1
					$userRemain->remain = $exchangeItem->limit_num - 1;
					$userRemain->user_id = $userId;
					$userRemain->item_id = $itemId;
					$userRemain->lineup_id = $lineupId;
					$userRemain->create ( $pdoUser );
				} else {
					$userRemain->remain -= 1;
					$userRemain->update ( $pdoUser );
				}

				//remove cache
				$redis = Env::getRedisForUser();
				$key = CacheKey::getUserExchangeRemain ( $userId );
				$redis->delete ( $key );
			}

			$pdoUser->commit ();
		} catch ( Exception $e ) {
			if ($pdoUser->inTransaction ()) {
				$pdoUser->rollBack ();
			}
			throw $e;
		}
		
		$response = array(
				'res' => RespCode::SUCCESS,
				'ranking_point' => $user->ranking_point,
				'item' => User::arrangeBonusResponse($result,$rev)
		);
		
		//#PADC# QQ report score
		if(isset($response['item']['card'])){
			User::reportUserCardNum($userId, $access_token);
		}
		
		return json_encode ( $response );
	}
}