<?php
class TlogComposite extends TlogBase {
	const EVENT = "Composite";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'CardID',
			'PieceID',
			'CardLevel',
			'AfterCardLevel',
			'Level',
			'VipLevel',
			'PieceNum',
			'GenericUse',
			'GenericNum',
			'Money',
			'HP',
			'Attack',
			'Recover' 
	);
	
	/**
	 * Composite
	 *
	 * @param string $appId        	
	 * @param int $platId        	
	 * @param string $openId        	
	 * @param int $card_id        	
	 * @param int $piece_id        	
	 * @param int $card_lv        	
	 * @param int $after_card_lv        	
	 * @param int $lv        	
	 * @param int $vip_lv        	
	 * @param int $piece_num        	
	 * @param int $generic_use        	
	 * @param int $generic_num        	
	 * @param int $money        	
	 * @param int $hp        	
	 * @param int $attack        	
	 * @param int $recover        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $card_id, $piece_id, $card_lv, $after_card_lv, $lv, $vip_lv, $piece_num, $generic_use, $generic_num, $money, $hp, $attack, $recover) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$card_id,
				$piece_id,
				$card_lv,
				$after_card_lv,
				$lv,
				$vip_lv,
				$piece_num,
				$generic_use,
				$generic_num,
				$money,
				$hp,
				$attack,
				$recover 
		);
		return static::generateMessageFromArray ( $params );
	}
}