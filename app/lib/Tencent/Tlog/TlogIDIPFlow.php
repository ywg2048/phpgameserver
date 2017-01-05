<?php
class TlogIDIPFlow extends TlogBase {
	const EVENT = 'IDIPFLOW';
	protected static $columns = array (
			'event',
			'dtEventTime',
			'Area_id',
			'vopenid',
			'Item_id',
			'Item_num',
			'Serial',
			'Source',
			'Cmd',
			'uuid' 
	);
	
	/**
	 * IDIPログ
	 *
	 * @param string $openId        	
	 * @param number $item_id        	
	 * @param number $item_num        	
	 * @param number $serial        	
	 * @param string $source        	
	 * @param number $cmd        	
	 * @param number $uuid        	
	 * @return string
	 */
	public static function generateMessage($area_id, $openId, $item_id, $item_num, $serial, $source, $cmd, $uuid) {
		$params = array (
				static::EVENT,
				static::makeTime (),
				$area_id,
				$openId,
				$item_id,
				$item_num,
				$serial,
				$source,
				$cmd,
				$uuid 
		);
		return static::generateMessageFromArray ( $params );
	}
}
