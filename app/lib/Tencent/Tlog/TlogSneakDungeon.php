<?php
class TlogSneakDungeon extends TlogBase {
	const EVENT = 'SneakDungeon';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'BattleID',
			'BattleType',
			'deck',
			'totalPower',
			'RoundTicket',
			'RoundTicketNum',
			'FriendOpenID',
			'SecuritySDK',
			'Level',
			'VipLevel',
			'SneakTime',
            'UseStamina' // #PADC_DY#
	);
	
	/**
	 * ダンジョン潜入
	 *
	 * @param string $appId
	 *        	APP_ID
	 * @param number $platId
	 *        	ios：0 /android：1
	 * @param string $openId
	 *        	ユーザOpenID
	 * @param number $battle_id
	 *        	dungeon floor id
	 * @param number $battle_type        	
	 * @param string $deck
	 *        	挑戦チームデータ
	 * @param number $totalPoser        	
	 * @param number $roundTicket        	
	 * @param string $friendOpenID        	
	 * @param number $securitySDK        	
	 * @param number $level        	
	 * @param number $vipLevel        	
	 * @param string $sneakTime
	 * @param number $useStamina
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $battle_id, $battle_type, $deck, $totalPower, $roundTicket, $roundTicketNum, $friendOpenID, $securitySDK, $level, $vipLevel, $sneakTime, $useStamina) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$battle_id,
				$battle_type,
				$deck,
				$totalPower,
				$roundTicket,
				$roundTicketNum,
				$friendOpenID,
				$securitySDK,
				$level,
				$vipLevel,
				$sneakTime,
                $useStamina // #PADC_DY#
		);
		return static::generateMessageFromArray ( $params );
	}
}
