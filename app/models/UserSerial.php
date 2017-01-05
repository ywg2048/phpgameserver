<?php
/**
 * ユーザーシリアルモデルのベースクラス.
 * author akamiya@gungho.jp
 */
class UserSerial extends BaseModel {
  const TABLE_NAME = "user_serials";

  const SUCCESS = 0;
  const ERR_PERIOD = 1;
  const ERR_USED_SERIAL = 2;
  const ERR_REGISTERED = 3;
  const ERR_NOT_FOUND = 4;
  const ERR_KUJI_CODE = 5;

  protected static $columns = array(
    'user_id',
    'campaign_id',
    'serial',
  );

  public static function check($user_id, $serial_code){
    $res = static::SUCCESS;
    $campaign = null;
    // チェックデジット確認
    if(!static::check_digit($serial_code)){
      return array($campaign, static::ERR_NOT_FOUND);
    }
    $pdo_serial = Env::getDbConnectionForSerial();
    $serial_maker = SerialMaker::findBy(array('serial_code' => $serial_code), $pdo_serial);
    if($serial_maker === FALSE){
      $res = static::ERR_NOT_FOUND;
    }else{
      $campaign = CampaignSerialCode::get($serial_maker->campaign_id);
      if($campaign == FALSE || !$campaign->checkEnabled()){
        $res = static::ERR_PERIOD;
        return array($campaign, $res);
      }
      // シリアルが既に使われているか.
      $pdo_share = Env::getDbConnectionForShare();
      if($campaign->serial_type == CampaignSerialCode::SERIAL_TYPE_FREE){
        $serial_code = $serial_code . "_". $user_id; // シリアル共通の場合はシリアル＋アカウントIDとする
      }
      $used = UserSerial::findBy(array('serial' => $serial_code), $pdo_share, TRUE);
      if($used){
        $res = static::ERR_USED_SERIAL;
      }
      if($campaign->serial_type == CampaignSerialCode::SERIAL_TYPE_ONCE){
        // 一人一個
        $used_all = UserSerial::findBy(array('user_id' => $user_id, 'campaign_id' => $serial_maker->campaign_id), $pdo_share, TRUE);
        if($used_all){
          $res = static::ERR_REGISTERED;
        }
      }
    }
    return array($campaign, $res);
  }

  public static function get($user_id, $serial_code){
    // チェックデジット確認
    if(!static::check_digit($serial_code)){
      return static::ERR_NOT_FOUND;
    }
    try{
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo_share = Env::getDbConnectionForShare();
      $pdo_serial = Env::getDbConnectionForSerial();
      $pdo->beginTransaction();
      $pdo_share->beginTransaction();
      $user = User::find($user_id, null, TRUE);
      $serial_maker = SerialMaker::findBy(array('serial_code' => $serial_code), $pdo_serial);
      $campaign_id = $serial_maker->campaign_id;
      $campaign = CampaignSerialCode::get($campaign_id);
      if(!$campaign->checkEnabled()){
        // 期間外
        $pdo->rollback();
        $pdo_share->rollback();
        return static::ERR_PERIOD;
      }
      if($campaign->serial_type == CampaignSerialCode::SERIAL_TYPE_FREE){
        $serial_code = $serial_code . "_". $user_id; // シリアル共通の場合はシリアル＋アカウントIDとする
      }
      $used = UserSerial::findBy(array('serial' => $serial_code), $pdo_share, TRUE);
      if($used){
        // ERROR 既に使用されている.
        $pdo->rollback();
        $pdo_share->rollback();
        return static::ERR_USED_SERIAL;
      }
      if($campaign->serial_type == CampaignSerialCode::SERIAL_TYPE_ONCE){
        // 一人一個.
        $used_all = UserSerial::findBy(array('user_id' => $user_id, 'campaign_id' => $campaign_id), $pdo_share, TRUE);
        if($used_all){
          // ERROR 既に該当キャンペーンに申込み済み.
          $pdo->rollback();
        $pdo_share->rollback();
          return static::ERR_REGISTERED;
        }
      }
      // シリアル使用.
      $user_serial = new UserSerial();
      $user_serial->user_id = $user_id;
      $user_serial->campaign_id = $campaign_id;
      $user_serial->serial = $serial_code;
      $user_serial->create($pdo_share);
      // アイテム付与.
      $campaign_serial_items = CampaignSerialItem::findAllBy(array('campaign_id' => $campaign_id), null, null, $pdo_share);
      foreach($campaign_serial_items as $item){
        $param = null;
        if ($item->item_id > 0) {
          $plusparam = array();
          $plushp = (int)$item->plus_hp;
          $plusatk = (int)$item->plus_atk;
          $plusrec = (int)$item->plus_rec;
          $psk = 0;
          $plusparam['slv'] = 1;
          $plusparam['plus'] = array($plushp, $plusatk, $plusrec, $psk);
          $plusparam['message'] = Card::get($item->item_id)->name;
          UserMail::sendAdminMailBonus($user_id, UserMail::TYPE_ADMIN_BONUS_NORMAL, $item->item_id, $item->lv, $pdo, $plusparam);
        } else if ($item->avatar_id > 0) {
          $data = array();
          $data["aid"] = (int)$item->avatar_id;
          $data["alv"] = (int)$item->lv;
          $message = WAvatarItem::get($item->avatar_id)->name;
          UserMail::sendAdminMailBonus($user_id, UserMail::TYPE_ADMIN_BONUS_W, BaseBonus::AVATAR_ID, 1, $pdo, null, $message, $data);
        }
      }
      $pdo->commit();
      $pdo_share->commit();
	// #PADC# PDOException → Exception
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      if ($pdo_share->inTransaction()) {
        $pdo_share->rollback();
      }
      throw $e;
    }
    return static::SUCCESS;
  }

  //チェックデジット
  private static function check_digit($str){
  
    // 当初のシリアルコード作成処理に不具合があったため、4～5文字目が"10"のシリアルコードはチェックを除外する
    $miss = substr($str, 3, 2);
    if($miss == "10"){
      return TRUE;
    }

    // 4桁目のチェックデジットを切り取り
    $dgt = substr($str, 3, 1);
    $serial = substr($str, 0, 3).substr($str, 4);
  
    //文字列を配列に分解
    $card = str_split($serial);
    
    $calc = 0;
    //数字の数だけループする
    for( $x = 0; $x < 15; $x++ ){
      //奇数の場合のみ2倍する
      if( $x % 2 == 0 ) {
        $card[$x] = $card[$x] * 2;
      }
      //2桁の場合は分割して足す
      if( mb_strlen( $card[$x] ) != 1 ){
        $split = str_split($card[$x]);
        $card[$x] = $split[0] + $split[1];
      }
      $calc += $card[$x];
    }
    $calc = (10 - ($calc % 10)) % 10;
  
    // 結果
    if($dgt == $calc) {
      $ret = TRUE;
    }else{
      $ret = FALSE;
    }

    return $ret;
  
  }

}
