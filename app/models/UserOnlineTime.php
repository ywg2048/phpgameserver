<?php
/**
 * 用户在线时长
 */

class UserOnlineTime extends BaseModel
{
  const TABLE_NAME = "user_online_time";

  protected static $columns = array(
    'id',
    'pid',
    'accumulate_time',
    'last_report_time',
    'is_alert',
    'created_at',
    'updated_at'
  );
}