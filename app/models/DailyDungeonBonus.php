<?php
/**
 * #PADC#
 * デイリーダンジョン報酬
 */
class DailyDungeonBonus extends BaseMasterModel {
	const TABLE_NAME = "padc_daily_dungeon_bonus";
	const VER_KEY_GROUP = "padc_d_dung_b";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array(
		'id',
		'begin_at',
		'finish_at',
		'dungeon_id',
		'seq',
		'bonus_id',
		'amount',
		'piece_id',
		'message',
	);
	
	/**
	 * 指定されたダンジョンの現在有効なクリア報酬リストを返す
	 */
	static public function getActiveBonusList($dungeon_id) {
		
		$daily_dungeon_bonus_list = DailyDungeonBonus::findAllBy ( array (
				'dungeon_id' => $dungeon_id,
		) );
		
		$list = array();
		$now = time();
		foreach($daily_dungeon_bonus_list as $obj){
			$begin_at = static::strToTime($obj->begin_at);
			$finish_at = static::strToTime($obj->finish_at);
			if (($begin_at <= 0 && $finish_at <= 0) ||
			($begin_at <= $now && $now <= $finish_at))
			{
				$list[] = array(
					"floor" => (int)$obj->seq,
					"bonus_id" => (int)$obj->bonus_id,
					"amount" => (int)$obj->amount,
					"piece_id" => (int)$obj->piece_id,
				);
			}
		}
		
		return $list;
	}
	
}
