<?php
/**
 * #PADC#
 * 連続ログイン回数管理
 */
class LoginPeriod extends BaseMasterModel {
	const TABLE_NAME = "padc_login_periods";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	protected static $columns = array(
		'id',				// ID
		'begin_at',			// 開始日時
		'finish_at',		// 終了日時
		'max_login_streak',	// 最大連続ログイン回数
	);

	/**
	 * 有効な連続ログイン定義を取得
	 */
	public static function getActiveLoginPeriod()
	{
		$date = null;
		$activeLoginPeriod = null;
		$activeLoginPeriods = self::getAllActiveLoginPeriod();
		foreach($activeLoginPeriods as $_activeLoginPeriod)
		{
			if($date == null || ($date && $date < $_activeLoginPeriod->begin_at))
			{
				$date = $_activeLoginPeriod->begin_at;
				$activeLoginPeriod = $_activeLoginPeriod;
			}
		}
		return $activeLoginPeriod;
	}

	/**
	 * 有効な連続ログイン定義一式を取得
	 * @return multitype:unknown
	 */
	public static function getAllActiveLoginPeriod()
	{
		$activeLoginPeriods = array();
		$nowtime = Padc_Time_Time::getDate();
		$loginPeriods = LoginPeriod::getAll();
		foreach($loginPeriods as $loginPeriod)
		{
			if($loginPeriod->begin_at <= $nowtime && $nowtime < $loginPeriod->finish_at)
			{
				$activeLoginPeriods[] = $loginPeriod;
			}
		}
		return $activeLoginPeriods;
	}
	
	/**
	 * 指定日時のログイン期間データを取得
	 */
	public static function getLoginPeriod($time)
	{
		$str_time = static::timeToStr($time);
		$date = null;
		$ret = null;
		$loginPeriods = LoginPeriod::getAll();
		foreach($loginPeriods as $loginPeriod)
		{
			if($loginPeriod->begin_at <= $str_time && $str_time < $loginPeriod->finish_at)
			{
				if($date == null || ($date && $date < $loginPeriod->begin_at))
				{
					$date = $loginPeriod->begin_at;
					$ret = $loginPeriod;
				}
			}
		}
		return $ret;
	}
}
