<?php
/**
 * サポートデータ.
 */

class SupportData extends BaseMasterModel {
  const TABLE_NAME = "support_data";
  const MEMCACHED_EXPIRE = 86400; // 24時間.
  const PASS_LENGTH = 9; // 秘密のコードの文字数

  const AUTH_TWITTER = 0; // TwitterAccount
  const AUTH_FACEBOOK = 1; // FacebookAccount
  const AUTH_GOOGLE_ID = 2; // GoogleAccount
  const AUTH_SECRET_CODE = 3; // 秘密のコード

  protected static $columns = array(
    'id',
    'user_id',
    'secret_code',
    'auth_type',
    'auth_data',
  );

  // ランダム文字列を生成(16進数)
  public static function generateSecretCode(){
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
    return strtoupper($str);
  }

}
