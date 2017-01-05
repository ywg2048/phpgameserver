<?php
class TlogVipLevelUp extends TlogBase {
	const EVENT = "VipLevelUp";
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
			'GameName',
			'AddExp',
			'IsLvUp',
			'VipExp',
			'LvUpExp'
	);
	
	/**
	 * vip tlog
	 *
	 * @param string $appId        	
	 * @param int $platId        	
	 * @param string $openId        	
	 * @param int $user_lv        	
	 * @param int $vip_lv        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $user_lv, $vip_lv,$GameName,$AddExp,$IsLvUp,$VipExp,$LvUpExp) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$user_lv,
				$vip_lv,
				$GameName,
				$AddExp,
				$IsLvUp,
				$VipExp,
				$LvUpExp
		);
		return static::generateMessageFromArray ( $params );
	}
}