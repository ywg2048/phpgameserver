<?php
/**
 * #PADC#
 * Tlogリアルタイム集計用ログ
 */

class TbOnlineCnt extends BaseModel {
	const TABLE_NAME = "tb_zlmc_onlinecnt";

	const HAS_UPDATED_AT = false;
	const HAS_CREATED_AT = false;

	protected static $columns = array(
		'gameappid',
		'timekey',
		'gsid',
		'zoneareaid',
		'onlinecntios',
		'onlinecntandroid',
	);

	/**
	 * ログ出力
	 * @param string $gamepppid
	 * @param int $timekey
	 * @param string $gsid
	 * @param int $zoneareaid
	 * @param int $onlinecntios
	 * @param int $onlinecntandroid
	 */
	public static function log($gamepppid,$timekey,$gsid,$zoneareaid,$onlinecntios,$onlinecntandroid)
	{
		$tbOnlineCnt = new TbOnlineCnt();
		$tbOnlineCnt->gameappid			= $gamepppid;
		$tbOnlineCnt->timekey			= $timekey;
		$tbOnlineCnt->gsid				= $gsid;
		$tbOnlineCnt->zoneareaid		= $zoneareaid;
		$tbOnlineCnt->onlinecntios		= $onlinecntios;
		$tbOnlineCnt->onlinecntandroid	= $onlinecntandroid;
		$pdo = Env::getDbConnectionForTlog();
		$tbOnlineCnt->create($pdo);
		return;
	}
}
