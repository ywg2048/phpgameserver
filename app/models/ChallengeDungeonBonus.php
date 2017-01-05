<?php
/**
 * チャレンジダンジョン報酬
 */
class ChallengeDungeonBonus extends BaseMasterModel {
	const TABLE_NAME = "challenge_dungeon_bonus";
	const VER_KEY_GROUP = "c_dung_b";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array(
		'id',
		'finish_at',
		'dungeon_id',
		'seq',
		'bonus_id',
		'amount',
		// #PADC# ----------begin----------
		'piece_id',
		// #PADC# ----------end----------
		'plus_hp',
		'plus_atk',
		'plus_rec',
		'message',
		// #PADC# ----------begin----------
		//'created_at',
		//'updated_at',
		// #PADC# ----------end----------
	);
}
