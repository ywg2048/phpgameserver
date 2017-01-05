<?php
/**
 * シリアルモデルのベースクラス.
 * author akamiya@gungho.jp
 */
class SerialMaker extends BaseModel {
  const TABLE_NAME = "serial_maker";

  protected static $columns = array(
    'serial_code',
    'campaign_id',
  );

}
