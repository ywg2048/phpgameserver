<?php

/**
 * #PADC_DY#
 *
 * 获取交换商品列表
 */
class GetExchangeProductList extends BaseAction
{
	public function action($params)
	{
		$user_id = $params['pid'];
		$refresh = !empty($params['refresh']) ? true : false;
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			throw new PadException(RespCode::TENCENT_TOKEN_ERROR, "token error");
		}
		$pdoShare = Env::getDbConnectionForShareRead();

		$exchangeItems = ExchangeProduct::getActiveExchangeItems($user_id, $refresh, $pdoShare, $token);
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
				'exchange_point' => (int)$item->exchange_point,
				'bonus_id' => (int)$item->bonus_id,
				'amount' => (int)$item->amount,
				'piece_id' => (int)$item->piece_id,
				'favor' => (int)$item->favor,
			);
		}

		$user = User::find($user_id);

		return json_encode(array(
			"res" => RespCode::SUCCESS,
			"gold" => $user->gold + $user->pgold,
			"items" => $res,
			"refresh_time" => GameConstant::getParam('ExchangeRefreshTime'),
            'exchange_record' => $user->getExchangeRecord(),
			"time" => time()
		));
	}
}