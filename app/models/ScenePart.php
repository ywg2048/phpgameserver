<?php
/**
 * #PADC#
 * シーンパーツ
 */

class ScenePart extends BaseMasterModel {
	const TABLE_NAME = "padc_sceneparts";
	const VER_KEY_GROUP = "padcscenepart";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// ID、ファイル名、基点からの位置
	protected static $columns = array(
		'id',
		'filename',
		'zpos',
	);
}
