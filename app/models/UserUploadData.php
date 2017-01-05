<?php
/**
 * アップロードデータ.
 */
class UserUploadData extends BaseModel {
  const TABLE_NAME = "user_upload_data";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  // ＡＰＩ：データアップロード
  const ZUKAN = 0;	// 図鑑.
  const MONSTAR_FAV = 1;	// モンスターお気に入り.
  const FRIEND_FAV = 2;	// フレンドお気に入り&フレンド使用回数.
  const COUNTER3 = 3;	// 予備.
  const COUNTER4 = 4;	// 予備.
  const COUNTER5 = 5;	// 予備.
  const COUNTER6 = 6;	// 予備.
  const COUNTER7 = 7;	// 予備.

  protected static $columns = array(
    'user_id',
    'type',
    'dcnt',
    'data',
    'updated_at',
    'created_at',
  );

  /**
   * データアップロード.
   */
  public static function upload($user_id, $type, $data, $pdo) {
    $user_upload_data = UserUploadData::findBy(array('user_id' => $user_id, 'type' => $type), $pdo);
    if(!$user_upload_data){
      $user_upload_data = new UserUploadData();
      $user_upload_data->user_id = $user_id;
      $user_upload_data->type = $type;
      $user_upload_data->data = $data;
      $user_upload_data->dcnt = 1;
      $user_upload_data->create($pdo);
    }else{
      // 図鑑の場合、元のデータとPOSTのデータで論理和を掛ける（複数台からのアクセス対策）
      if($type == UserUploadData::ZUKAN){
        $org_data = base64_decode($user_upload_data->data);
        $data = base64_decode($data);
        if(strlen($org_data)>strlen($data)){
          $len = strlen($org_data);
        }else{
          $len = strlen($data);
        }
        $new_data = "";
        for($i=0; $i<$len; $i++) {
          $new_data .= chr(ord(substr($org_data, $i, 1)) | ord(substr($data, $i, 1)));
        }
        $data = base64_encode($new_data);
      }
      $user_upload_data->data = $data;
      $user_upload_data->dcnt = $user_upload_data->dcnt + 1;
      $user_upload_data->update($pdo);
    }
    return array((int)$user_upload_data->dcnt, $user_upload_data->data);
  }

  /**
   * 各データのアップロードカウンタを返す.
   */
  public static function get_dcnt($user_id, $pdo) {
    $user_upload_data = static::findAllBy(array('user_id'=>$user_id), null, null, $pdo, TRUE);
    $dcnt0 = 0;
    $dcnt1 = 0;
    $dcnt2 = 0;
    $dcnt3 = 0;
    $dcnt4 = 0;
    $dcnt5 = 0;
    $dcnt6 = 0;
    $dcnt7 = 0;
    if($user_upload_data){
      foreach($user_upload_data as $data){
        if($data->type == static::ZUKAN){
          $dcnt0 = (int)$data->dcnt;
        }elseif($data->type == static::MONSTAR_FAV){
          $dcnt1 = (int)$data->dcnt;
        }elseif($data->type == static::FRIEND_FAV){
          $dcnt2 = (int)$data->dcnt;
        }elseif($data->type == static::COUNTER3){
          $dcnt3 = (int)$data->dcnt;
        }elseif($data->type == static::COUNTER4){
          $dcnt4 = (int)$data->dcnt;
        }elseif($data->type == static::COUNTER5){
          $dcnt5 = (int)$data->dcnt;
        }elseif($data->type == static::COUNTER6){
          $dcnt6 = (int)$data->dcnt;
        }elseif($data->type == static::COUNTER7){
          $dcnt7 = (int)$data->dcnt;
        }
      }
    }
    return array($dcnt0, $dcnt1, $dcnt2, $dcnt3, $dcnt4, $dcnt5, $dcnt6, $dcnt7);
  }

  /**
   * 図鑑をbit列にして返す
   */
  public static function bitMonsterGuideHex($user_id, $pdo) {
    $user_upload_data = UserUploadData::findBy(array('user_id' => $user_id, 'type' => UserUploadData::ZUKAN), $pdo);
    if(!$user_upload_data){
      return FALSE;
    }
    $bytes = unpack('C*', base64_decode($user_upload_data->data));
    $bit = "";
    $hex = "";
    $n = 0;
    foreach ($bytes as $idx => $byte) {
      for ($i=0;$i<4;$i++) {
        $b = $byte & 0x03;
        $byte = $byte >> 2;
        $flg = $b & 0x02 ? 1 : 0;
        // カードID 0番は排除
        if ($n) {
          $bit = $flg . $bit;
        }
        if (strlen($bit) == 8) {
          $hex = sprintf("%02x", bindec($bit)) . $hex;
          $bit = "";
        }
        $n++;
      }
    }
    if (strlen($bit) != 8) {
      $hex = sprintf("%02x", bindec($bit)) . $hex;
    }
    return pack("H*", $hex);
  }

}
