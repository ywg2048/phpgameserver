<?php
class TlogBase {
	const EVENT = '';
	protected static $game_svr_id = 0;
	protected static $zone_id = 0;
	protected static $columns = array ();
	
	/**
	 *
	 * @param array $params
	 * @return string        	
	 */
	public static function generateMessageFromArray($params) {
		$msg = '';
		$cnt = count ( $params );
		for($i = 0; $i < $cnt; $i ++) {
			if ($i > 0) {
				$msg .= '|';
			}
			if (isset ( $params [$i] )) {
				$msg .= $params [$i];
			} else {
				$msg .= 'NULL';
			}
		}
		return $msg;
	}
	
	/**
	 * set game server id
	 *
	 * @param int $id        	
	 */
	public static function setGameServerId($id) {
		static::$game_svr_id = $id;
	}
	
	/**
	 * set zone id
	 *
	 * @param int $id        	
	 */
	public static function setZoneId($id) {
		static::$zone_id = $id;
	}
	
	/**
	 * make time string
	 *
	 * @return string
	 */
	protected static function makeTime($t = null) {
		if(!isset($t)){
			$t = time();
		}
		return strftime ( '%Y-%m-%d %H:%M:%S', $t );
	}
	
	/**
	 * get game server id
	 *
	 * @return number
	 */
	protected static function getGameSvrId() {
		if (! isset ( static::$game_svr_id )) {
			throw new Exception ( 'game_svr_id not defined' );
		}
		return static::$game_svr_id;
	}
	
	/**
	 * get zone id
	 *
	 * @return number
	 */
	protected static function getZoneId() {
		if (! isset ( static::$zone_id )) {
			throw new Exception ( 'zone_id not defined' );
		}
		return static::$zone_id;
	}
	
	/**
	 * 
	 * @return array
	 */
	public static function getColumns(){
		return static::$columns;
	}
}
