<?php
/**
 * #PADC#
 * 時間関連処理クラス
 */
class Padc_Time_Time
{
	private static $_date = null;

	/**
	 * 指定のフォーマットでの日付を返す
	 * @param string $format
	 * @param date $time
	 * @return string
	 */
	public static function getDate($format='Y-m-d H:i:s',$time=null)
	{
		if(!$time)
		{
			$time = self::getTimeStamp();
		}
		
		if(is_null(self::$_date))
		{
			return date($format,$time);
		}

		return date($format,self::$_date);
	}

	/**
	 * 指定のフォーマットでのタイムスタンプを返す 
	 */
	public static function getTimeStamp()
	{
		$date = new DateTime();
		return $date->format('U');
	}

	/**
	 * 指定の日付から指定の日数加算した日数を取得
	 *
	 * @param int $day
	 * @return date yyyy-mm-dd
	 */
	public static function getDateAdjustDay($date,$day)
	{
		$daystr = $day;
		if($day > 0)
		{
			$daystr = '+' . $day;
		}
		return date("Y-m-d", strtotime($date . $daystr . ' day'));
	}
}
