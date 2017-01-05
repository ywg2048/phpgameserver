<?php
/**
 * #PADC#
 * シーン
 */

class Scene extends BaseMasterModel {
	const TABLE_NAME = "padc_scenes";
	const VER_KEY_GROUP = "padcscene";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// ID、シーンの開始位置、シーンの終了位置、背景ファイル名、[ダンジョンID、アイコンX座標・Y座標]*6
	protected static $columns = array(
		'id',
		'start_zpos',
		'end_zpos',
		'bg_filename',
		'dungeon_id1',
		'iconx1',
		'icony1',
		'dungeon_id2',
		'iconx2',
		'icony2',
		'dungeon_id3',
		'iconx3',
		'icony3',
		'dungeon_id4',
		'iconx4',
		'icony4',
		'dungeon_id5',
		'iconx5',
		'icony5',
		'dungeon_id6',
		'iconx6',
		'icony6',
	);
}
