<?php
/**
 * #PADC#
 * SPシーンパーツ
 */

class SpScenePart extends BaseMasterModel {
	const TABLE_NAME = "padc_spsceneparts";
	const VER_KEY_GROUP = "padcspscenepart";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// ID、ファイル名、基点からの位置
	protected static $columns = array(
		'id',
		'filename',
		'zpos',
	);
}
