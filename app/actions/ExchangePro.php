<?php

/**
 * #PADC_DY#
 *
 * 兑换商品
 */
class ExchangePro extends BaseAction
{
	public function action($params)
	{
		$user_id = $params['pid'];
		$product_id = $params['product_id'];
		$rev = isset ($params['r']) ? $params['r'] : 0;
		$token = Tencent_MsdkApi::checkToken($params);

		$pdo = Env::getDbConnectionForUserWrite($user_id);

		$user = User::find($user_id, $pdo);
		if (!$user) {
			throw new PadException(RespCode::USER_NOT_FOUND, 'user not found');
		}

		if ($user->alreadySoldOut($product_id)) {
			throw new PadException(RespCode::UNKNOWN_ERROR, 'good has already sold out');
		}

		$exchangeItem = ExchangeProduct::get($product_id);
		if (!$exchangeItem) {
			throw new PadException(RespCode::INVALID_EXCHANGE_ITEM);
		}

		if ($user->exchange_point < $exchangeItem->exchange_point) {
			throw new PadException(RespCode::UNKNOWN_ERROR, 'not have enough exchange_point');
		}

		try {
			$pdo->beginTransaction();

			$bonus_id = (int)$exchangeItem->bonus_id;
			$amount = (int)$exchangeItem->amount;
			$piece_id = (int)$exchangeItem->piece_id;

			if ($user->checkBonusLimit($bonus_id, $amount, $piece_id, $pdo)) {
				throw new PadException(RespCode::ITEM_REACH_MAX, 'item reach max');
			}

			$result = $user->applyBonus($bonus_id, $amount, $pdo, null, null, $piece_id);
			$user->exchange_point -= (int)$exchangeItem->exchange_point;
			$user->addExchangeRecord($product_id);
			$user->update($pdo);

			$exchange_history = new UserExchangeHistory();
			$exchange_history->user_id = $user_id;
			$exchange_history->product_id = $product_id;
			$exchange_history->create($pdo);

			$pdo->commit();

			// tlog send
			if($bonus_id == BaseBonus::PIECE_ID){
				UserTlog::sendTlogItemFlow($user->id, Tencent_Tlog::GOOD_TYPE_PIECE, $piece_id, $amount, $result['user_piece']->num, Tencent_Tlog::ITEM_REASON_EXCHANGE_ITEM, 0, 0, 0, 0, 0);
			}

		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			throw $e;
		}

		$response = array(
			'res' => RespCode::SUCCESS,
			'exchange_point' => $user->exchange_point,
			'exchange_record' => $user->getExchangeRecord(),
			'item' => User::arrangeBonusResponse($result, $rev)
		);


		if (isset($response['item']['card'])) {
			User::reportUserCardNum($user_id, $token['access_token']);
		}

		return json_encode($response);
	}
}