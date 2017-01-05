<?php
/**
 * ランキング集計時間テーブル
 * 
 */
class RankingAggregateData extends BaseModel {
  const TABLE_NAME = "padc_ranking_aggregate_data";
  const BASE_TIME = 900; // 15分
  const NEXT_INTERVAL = 3600; // 1時間毎
  const NEXT_MARGIN = 60; // 1分
  const END_MARGIN = 300;
  protected static $columns = array(
  	'id',
  	'time'
  );


  public function checkAggregateEnd($check_time)
  {
  	$aggregate_end = false;
    $aggregate_time = self::strToTime($this->time);
    $check_time = self::strToTime($check_time);
  	if($aggregate_time > $check_time + self::END_MARGIN)
  	{
  		$aggregate_end = true;
  	}
  	return $aggregate_end;
  }
  public static function getNextAggregateTime($time)
  {
    $base_time = GameConstant::getParam('RankingAggregateBaseTime');
    $interval_time = GameConstant::getParam('RankingAggregateIntervalTime');
    // MY : 基準時間調整
    $calc_base_time = ($time - $base_time);
    $ret = ($calc_base_time - ($calc_base_time % $interval_time));
    // MY : 調整分戻し。
    $ret = $ret + $base_time;
    // MY : 次回時間加算
    $ret = $ret + $interval_time + self::NEXT_MARGIN;
    return $ret;
  }
}
