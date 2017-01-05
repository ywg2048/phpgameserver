<?php
/**
 * 全ユーザボーナス
 */
class AllUserBonus extends BaseMasterModel {
  const TABLE_NAME = "all_user_bonuses";
  const VER_KEY_GROUP = "all_user_b";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'begin_at',
    'finish_at',
    'device_type',
    'bonus_id',
    'amount',
  	// #PADC# ----------begin----------
    'piece_id',
	'title',
  	// #PADC# ----------end----------
  	'message',
    'distribution_at',
    'slv',
    'plus_hp',
    'plus_atk',
    'plus_rec',
    'area',
    'bonus_type',
  );

  const MASTER_AREA_JP = 1;
  const MASTER_AREA_HT = 2;

  // #PADC# 不要な処理のため削除
  // iOS6.0未満、Android4.0未満を切るための暫定処理.2014年末に消す.-- START --
  private static function getUnSupportMailBonusJp($area_id, $device_type){
  	/*
    $unsupport_mail = new AllUserBonus();
    if($area_id == User::AREA_JP && $device_type == User::TYPE_IOS){
      $unsupport_mail->id = 90001; // 適当にきめうち.
      $message = "■iOS6.0未満の端末では、プレイができなくなります■\n\n";
      $message .= "※本メールは、iOS6.0未満の端末でプレイされている方にお送りしております※\n\n";
      $message .= "パズドラの対応環境について、2014年12月頃を目安に「iOS6.0以降の対応機種」に変更させていただきます。\n\n";
      $message .= "変更後、iOS6.0未満の端末では、プレイできなくなりますので、OSのアップデートや端末の移行などをご検討ください。\n\n";
      $message .= "●詳しくは運営サイトをご確認ください。http://goe.bz/i6";
    }elseif($area_id == User::AREA_JP && $device_type == User::TYPE_ANDROID){
      $unsupport_mail->id = 90002; // 適当にきめうち.
      $message = "■Android4.0未満の端末では、プレイができなくなります■\n\n";
      $message .= "※本メールは、Android4.0未満の端末でプレイされている方にお送りしてます※\n\n";
      $message .= "パズドラの対応環境を、2014年12月頃を目安に「Android4.0以降の対応機種」に変更させていただきます。\n\n";
      $message .= "変更後、Android4.0未満の端末では、プレイできなくなりますので、OSのアップデートや端末の移行などをご検討ください。\n\n";
      $message .= "●詳しくは運営サイトをご確認ください。http://goe.bz/a4";
    }elseif($area_id == User::AREA_HT && $device_type == User::TYPE_IOS){
      $unsupport_mail->id = 90003; // 適当にきめうち.
      $message = "【重要】\n";
      $message .= "由於為重要通知，將以中文進行公告\n";
      $message .= "\n";
      $message .= "■iOS系統版本未達6.0之機體，將無法繼續進行遊戲■\n";
      $message .= "※此通知會寄送給使用未達iOS6.0系統版本之機體來進行遊戲的玩家※\n";
      $message .= "Puzzle & Dragons的對應環境將於日本時間2014年12月左右，\n";
      $message .= "變更為「對應iOS6.0系統以上之機體」。\n";
      $message .= "變更後，iOS系統版本未達6.0之機體，將無法繼續進行遊戲。請考慮進行系統的升級或是更換機體。\n";
      $message .= "\n";
      $message .= "●詳情請參照如下網址：http://goe.bz/ios6";
    }elseif($area_id == User::AREA_HT && $device_type == User::TYPE_ANDROID){
      $unsupport_mail->id = 90004; // 適当にきめうち.
      $message = "【重要】\n";
      $message .= "由於為重要通知，將以中文進行公告\n";
      $message .= "\n";
      $message .= "■Android系統版本未達4.0之機體，將無法繼續進行遊戲■\n";
      $message .= "※此通知會寄送給使用未達Android4.0系統版本之機體來進行遊戲的玩家※\n";
      $message .= "Puzzle & Dragons的對應環境將於日本時間2014年12月左右，\n";
      $message .= "變更為「對應Android4.0系統以上之機體」。\n";
      $message .= "變更後，Android系統版本未達4.0之機體，將無法繼續進行遊戲。請考慮進行系統的升級或是更換機體。\n";
      $message .= "\n";
      $message .= "●詳情請參照如下網址：http://goe.bz/an4";
    }
    $unsupport_mail->begin_at = '2014-10-01 04:00:00';
    $unsupport_mail->finish_at = '2015-01-01 04:00:00';
    $unsupport_mail->device_type = NULL;
    $unsupport_mail->bonus_id = BaseBonus::MAIL_ID;
    $unsupport_mail->amount = 0;
    $unsupport_mail->message = $message;
    $unsupport_mail->distribution_at = NULL;
    $unsupport_mail->slv = NULL;
    $unsupport_mail->plus_hp = NULL;
    $unsupport_mail->plus_atk = NULL;
    $unsupport_mail->plus_rec = NULL;
    $unsupport_mail->area = NULL;
    $unsupport_mail->bonus_type = UserMail::TYPE_ADMIN_BONUS_TO_ALL;
    return $unsupport_mail;
    */
  }
  // iOS6.0未満、Android4.0未満を切るための暫定処理.2014年末に消す.-- END --
  
  /**
   * 現在有効なボーナスをユーザに付与し、そのボーナスデータを返す.
   * ボーナスが存在しなかったり、既に付与済みであれば空の配列を返す.
   */
  public static function applyBonuses($user, $pdo){
    $applied_bonuses = array();
    $active_bonuses = AllUserBonus::getActiveBonuses();

    // #PADC# -----begin----- 削除
    // iOS6.0未満、Android4.0未満を切るための暫定処理.2014年末に消す.-- START --
    /*
    if(Env::REGION == "JP" && $user->osv < 6.0 && $user->device_type == User::TYPE_IOS){
      $unsupport_mail_bonus = AllUserBonus::getUnSupportMailBonusJp($user->area_id, User::TYPE_IOS);
      $active_bonuses = array_merge($active_bonuses, array($unsupport_mail_bonus));
    }elseif(Env::REGION == "JP" && $user->osv < 4.0 && $user->device_type == User::TYPE_ANDROID){
      $unsupport_mail_bonus = AllUserBonus::getUnSupportMailBonusJp($user->area_id, User::TYPE_ANDROID);
      $active_bonuses = array_merge($active_bonuses, array($unsupport_mail_bonus));
    }
    */
    // iOS6.0未満、Android4.0未満を切るための暫定処理.2014年末に消す.-- END --
    // #PADC# -----end-----
    
    $histories = AllUserBonusHistory::findAllBy(array('user_id'=>$user->id), null, null, $pdo, null);
    $history_ids = array();
    foreach($histories as $history){
      $history_ids[$history->id] = $history->all_user_bonus_id;
    }
    foreach($active_bonuses as $bonus){
      if($bonus->distribution_at != null && $user->created_at >= $bonus->distribution_at){
        // 登録日時が配布対象外のユーザ
        continue;
      }

      /*
       * NULL:全て
       * 0:iOS
       * 1:Android
       * 2:Kindle
       * 3:iOS & Android
       * 4:iOS & Kindle
       * 5:Android & Kindle
       */
      $type_arr = array();
      if($user->getUserDevice() == UserDevice::TYPE_IOS){
        $type_arr = array(0, 3, 4);
      }elseif($user->getUserDevice() == UserDevice::TYPE_ADR){
        $type_arr = array(1, 3, 5);
      }elseif($user->getUserDevice() == UserDevice::TYPE_AMZ){
        $type_arr = array(2, 4, 5);
      }
      if($bonus->device_type != null && !in_array($bonus->device_type, $type_arr)){
        // 端末タイプが配布対象外のユーザ
        continue;
      }
      // 仕向地
      if(isset($bonus->area)){
        if($bonus->area == self::MASTER_AREA_JP && $user->getAreaId() != User::AREA_JP){
          // ボーナス対象の仕向地が日本のみで、ユーザーが日本以外の場合.
          continue;
        }elseif($bonus->area == self::MASTER_AREA_HT && $user->getAreaId() != User::AREA_HT){
          // ボーナス対象の仕向地が香港・台湾のみで、ユーザーが香港・台湾以外の場合.
          continue;
        }
      }
      if(in_array($bonus->id, $history_ids)){
        // 既に該当ボーナスを付与済み
        continue;
      }
      $all_user_bonus_history = new AllUserBonusHistory();
      $all_user_bonus_history->user_id = $user->id;
      $all_user_bonus_history->all_user_bonus_id = $bonus->id;
      $all_user_bonus_history->create($pdo);
      AllUserBonus::apply($user->id, $bonus, $pdo);
      $applied_bonuses[] = $bonus;
    }
    // 不要な付与履歴を削除.
    AllUserBonus::delExpirationHistory($active_bonuses, $history_ids, $pdo);
    return $applied_bonuses;
  }

  // 不要な付与履歴を削除.
  private static function delExpirationHistory($active_bonuses, $history_ids, $pdo){
    $active_bonus_ids = array();
    foreach($active_bonuses as $bonus){
      $active_bonus_ids[] = $bonus->id;
    }
    $del_ids = array();
    foreach($history_ids as $id => $all_user_bonus_id){
      if(!in_array($all_user_bonus_id, $active_bonus_ids)){
        $del_ids[] = $id;        
      }
    }
    if(count($del_ids) > 0){
      // 全ユーザーボーナス履歴(AllUserBonusHistory)のall_user_bonus_idから、
      // 現在有効な全ユーザーボーナスgetActiveBonuses()のidを除外した残りを削除する.
      AllUserBonusHistory::delete_ids($del_ids, $pdo);
    }
  }

  /**
   * 現在有効なボーナスを返す.
   * 一つも存在しない場合は空の配列を返す.
   */
  public static function getActiveBonuses(){
    // 有効期限内のボーナスの連想配列
    $bonuses = AllUserBonus::getEnableBonuses();
    $time = time();
    $b = array();
    foreach($bonuses as $bonus){
      if($bonus->isInTimeRange($time)){
        $b[] = $bonus;
      }
    }
    return $b;
  }

  public function isInTimeRange($time){
    if(static::strToTime($this->begin_at) <= $time
    && $time <= static::strToTime($this->finish_at)){
      return true;
    }
    return false;
  }

  /**
   * 現在以降有効なボーナスを返す.
   * 一つも存在しない場合は空の配列を返す.
   */
  private static function getEnableBonuses(){
    $key = MasterCacheKey::getEnableAllUserBonuses();
    $b = apc_fetch($key);
    if(FALSE === $b) {
      // 有効期限内のボーナスの連想配列
      $bonuses = AllUserBonus::getAll();
      $time = time();
      $b = array();
      foreach($bonuses as $bonus){
        if($bonus->isEnable($time)){
          $b[] = $bonus;
        }
      }
      apc_store($key, $b, static::MEMCACHED_EXPIRE + static::add_apc_expire());
    }
    return $b;
  }

  private function isEnable($time){
    if($time <= static::strToTime($this->finish_at)){
      return true;
    }
    return false;
  }

  /**
   * ボーナスをメールで送信する.
   */
  public static function apply($user_id, $bonus, $pdo) {
    $bonus_type = isset($bonus->bonus_type) ? $bonus->bonus_type : UserMail::TYPE_ADMIN_BONUS_NORMAL;
    $data = array();
    if ($bonus->bonus_id <= BaseBonus::MAX_CARD_ID) {
      $data["slv"] = isset($bonus->slv) ? (int)$bonus->slv : UserCard::DEFAULT_SKILL_LEVEL;
      $data["ph"] = isset($bonus->plus_hp) ? (int)$bonus->plus_hp : 0;
      $data["pa"] = isset($bonus->plus_atk) ? (int)$bonus->plus_atk : 0;
      $data["pr"] = isset($bonus->plus_rec) ? (int)$bonus->plus_rec : 0;
      $data["psk"] = 0;
    }
    // #PADC# タイトルを追加
    UserMail::sendAdminMailMessage($user_id, $bonus_type, $bonus->bonus_id, $bonus->amount, $pdo, $bonus->message, $data, $bonus->piece_id, $bonus->title);
  }
}
