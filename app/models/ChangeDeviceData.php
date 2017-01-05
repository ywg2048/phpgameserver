<?php
/**
 * 機種変更データ.
 */

class ChangeDeviceData extends BaseModel {
  const TABLE_NAME = "change_device_data";
  const PASS_LENGTH = 9; // 機種変コードの文字数（それに先頭Hを追加）

  const ERR_COMBINATION = 1;
  const ERR_EXPIRATION = 2;
  const ERR_DEL_USER = 3;

  protected static $columns = array(
    'id',
    'user_id',
    'code',
    'created_at',
  );

  // ランダム文字列を生成(16進数)
  public static function generateCode(){
    $str = "";
    $cd = 0;
    for($i = 0; $i < (static::PASS_LENGTH - 1); $i++) {
      $hex = mt_rand(0, 0xF);
      $str = dechex($hex) . $str;
      if(($i % 2) == 0){
        $cd += $hex * 3;
      }else{
        $cd += $hex;
      }
    }
    $hex_cd = dechex($cd);
    $hex_cd = 0xF - hexdec(substr($hex_cd, strlen($hex_cd) - 1, 1));
    $str .= dechex($hex_cd);
    return strtoupper("H".$str);
  }

}
