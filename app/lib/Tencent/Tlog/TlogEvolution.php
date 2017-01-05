<?php
class TlogEvolution extends TlogBase {
	const EVENT = "Evolution";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'CardId',
			'AfterCardId',
			'CardAttribute',
			'Level',
			'VipLevel',
			'PieceNum',
			'GenericUse',
			'GenericPieceNum',
			'GenericPieceAttribute',
			'Money'
	);

	/**
	 * Evolution
	 * 
	 * @param string $appId
	 * @param int $platId
	 * @param string $openId
	 * @param int $card_id
	 * @param int $after_card_id
	 * @param int $card_attribute
	 * @param int $lv
	 * @param int $vip_lv
	 * @param int $piece_num
	 * @param int $generic_use
	 * @param int $generic_num
	 * @param int $generic_piece_attr
	 * @param int $money
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId,$card_id,$after_card_id,$card_attribute,$lv,$vip_lv,$piece_num,$generic_use,$generic_num,$generic_piece_attr,$money){
		$params = array(
				static::EVENT,
				static::getGameSvrId(),
				static::makeTime(),
				$appId,
				$platId,
				static::getZoneId(),
				$openId,
				$card_id,
				$after_card_id,
				$card_attribute,
				$lv,
				$vip_lv,
				$piece_num,
				$generic_use,
				$generic_num,
				$generic_piece_attr,
				$money
		);
		return static::generateMessageFromArray($params);
	}

}