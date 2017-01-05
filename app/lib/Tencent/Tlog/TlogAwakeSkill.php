<?php
class TlogAwakeSkill extends TlogBase {
	const EVENT = "AwakeSkill";
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'Level',
			'VipLevel',
			'AwakePieceId',
			'CardId',
			'SkillId',
			'PieceNum',
			'Money',
			'CardLevel'
	);

	/**
	 * Awakeskill
	 * 
	 * @param string $appId
	 * @param int $platId
	 * @param string $openId
	 * @param int $awake_piece_id
	 * @param string $name
	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId,$Level,$VipLevel,$awake_piece_id,$card_id,$ps_id,$awake_skill_piece_num,$coin,$user_card_lv){
		$params = array(
				static::EVENT,
				static::getGameSvrId(),
				static::makeTime(),
				$appId,
				$platId,
				static::getZoneId(),
				$openId,
				$Level,
				$VipLevel,
				$awake_piece_id,
				$card_id,
				$ps_id,
				(int)$awake_skill_piece_num,
				$coin,
				$user_card_lv
		);
		return static::generateMessageFromArray($params);
	}

}