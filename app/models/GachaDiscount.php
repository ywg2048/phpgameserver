<?php
/**
 * #PADC#
 * ガチャ初回割引データ
 */
class GachaDiscount extends BaseMasterModel
{
	const TABLE_NAME = "padc_gacha_discount";

	// ID、ガチャタイプ、ガチャ回数（1回or10連）、割引率、開始日時、終了日時
	protected static $columns = array(
		'id',
		'gacha_type',
		'gacha_count',
		'ratio',
		'begin_at',
		'finish_at',
	);

	/**
	 * 有効な初回割引データを取得
	 * 指定したガチャタイプで有効な割引データを取得する
	 * 指定が無い場合は有効期間内のものを全て取得
	 *
	 * @param int $gacha_type  ガチャタイプ
	 * @param int $gacha_count  ガチャ回数
	 */
	public static function getActiveGachaDiscount($gacha_type, $gacha_count)
	{
		$date = null;
		$activeGachaDiscount = null;
		$activeGachaDiscounts = self::getAllActiveGachaDiscount();
		foreach($activeGachaDiscounts as $_activeGachaDiscount)
		{
			if ($gacha_type != $_activeGachaDiscount->gacha_type) {
				continue;
			}

			if ($gacha_count != $_activeGachaDiscount->gacha_count) {
				continue;
			}

			if($date == null || ($date && $date < $_activeGachaDiscount->begin_at))
			{
				$date = $_activeGachaDiscount->begin_at;
				$activeGachaDiscount = $_activeGachaDiscount;
			}
		}
		return $activeGachaDiscount;
	}

	/**
	 * 有効な初回割引データを取得
	 */
	public static function getAllActiveGachaDiscount()
	{
		$activeGachaDiscounts = array();
		$nowtime = Padc_Time_Time::getDate();
		$gachaDiscounts = GachaDiscount::getAll();
		foreach($gachaDiscounts as $gachaDiscount)
		{
			if($gachaDiscount->begin_at <= $nowtime && $nowtime < $gachaDiscount->finish_at)
			{
				$activeGachaDiscounts[] = $gachaDiscount;
			}
		}
		return $activeGachaDiscounts;
	}

}