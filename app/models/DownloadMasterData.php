<?php
/**
 * ダウンロード用MASTERデータ.
 */

class DownloadMasterData extends BaseMasterModel {
	const TABLE_NAME = "download_master_data";
	const VER_KEY_GROUP = "down_mas";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	const ID_DUNGEONS		= 1;
	const ID_CARDS			= 2;
	const ID_SKILLS			= 3;
	const ID_ENEMY_SKILLS	= 4;
	const ID_CARDS_VER2		= 5;
	const ID_DUNGEONS_VER2	= 6;
	const ID_CARDS_VER3		= 7;
	const ID_WDUNGEONS		= 8;
	const ID_WAVATAR_ITEMS	= 9;
	const ID_CARDS_VER4		= 10;
	const ID_DUNGEON_SALES	= 11;
	// #PADC# ----------begin----------
	const ID_PIECES			= 1001;// 欠片データ
	const ID_SCENES			= 1002;// シーンデータ
	const ID_MISSIONS		= 1003;// ミッションデータ
	// #PADC# ----------end----------

	// #PADC_DY# ----------begin----------
	const ID_ROADMAP		= 1004;	// user roadmap
    const ID_ACTIVITY		= 1005;	// 活动配置
	const ID_GACHA_LINEUP	= 1006; // gacha lineup
	const ID_PASSIVE_SKILL	= 1007; // 觉醒技能
	const ID_CARNIVAL       = 1008; //##新手嘉年华
	// #PADC_DY# ----------end----------
	protected static $columns = array(
		'id',
		'gzip_data',
		'length',
	);

}
