<?php
class TlogGuideFlow extends TlogBase {
	const EVENT = 'GuideFlow';
	protected static $columns = array (
			'event',
			'vGameSvrID',
			'dtEventTime',
			'vGameAppID',
			'PlatID',
			'vopenid',
			'iGuideID',
			'iLevel',
			'iroleid',
			'iFullVer' 
	);
	
	/**
	 *
	 * @param string $appId        	
	 * @param number $platId        	
	 * @param string $openId        	
	 * @param number $guide_id        	
	 * @param number $level        	
	 * @param number $role_id        	
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $guide_id, $level = 0, $role_id = 0, $full_ver = 0) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				$openId,
				$guide_id,
				$level,
				$role_id,
				$full_ver
		);
		return static::generateMessageFromArray ( $params );
	}
}
