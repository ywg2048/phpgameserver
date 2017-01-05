<?php
/**
 * ユーザが受け取ったメール.
 */

class UserMail extends BaseModel {
  const TABLE_NAME = "user_mails";

  const TYPE_NONE = 0;
  const TYPE_FRIEND_REQUEST = 1;
  const TYPE_THANKYOU_GIFT = 2;
  const TYPE_MESSAGE = 3;
  const TYPE_ADMIN_MESSAGE_NORMAL = 4;
  const TYPE_ADMIN_BONUS_NORMAL = 5;
  const TYPE_ADMIN_BONUS_TO_ALL_NORMAL = 6;
  const TYPE_ADMIN_MESSAGE = 7;
  const TYPE_ADMIN_BONUS = 8;
  const TYPE_ADMIN_BONUS_TO_ALL = 9;
  const TYPE_ADMIN_MESSAGE_W = 10;
  const TYPE_ADMIN_BONUS_W = 11;
  const TYPE_ADMIN_BONUS_TO_ALL_W = 12;

  //#PADC#
  //const ADD_GOLD_LOG_TYPE = 4;

  // 最大フレンド申請数
  const FRIEND_REQUEST_MAX_COUNT = 30;
  // 個人間のメールの最大数
  const MESSAGE_MAX_COUNT = 50;

  // フレンド申請の返答
  const ACK_DENY = 0;
  const ACK_ACCEPT = 1;

  const NOT_OFFERED = 0; // 未開封.
  const OFFERED = 1; // アイテム取得済み（開封済み）.

  const NOT_FAVORITE = 0; // お気に入りメールじゃない.
  const FAVORITE = 1; // お気に入りメール.

  protected static $columns = array(
    'type',
    'user_id',
    'sender_id',
    'title',
    'message',
    'data',
    'bonus_id',
    'amount',
    'piece_id',
    'slv',
    'plus_hp',
    'plus_atk',
    'plus_rec',
    'offered',
    'fav'
  );

  /*
   * メールを削除する
   */
  public static function deleteMail($user_id, $mail_id, $all_flg, $wmode){
    // #PADC# $pdoをtryの外に移動します。エラーの時$pdo not foundを防ぐ
    $pdo = Env::getDbConnectionForUserWrite($user_id);
    try{
      $mails = null;
      $pdo->beginTransaction();
      if(!$all_flg && $mail_id){
        // 個別メールの削除.
        $params = array('id' => $mail_id, 'offered' => UserMail::OFFERED, 'fav' => UserMail::NOT_FAVORITE);
        $mail = UserMail::findBy($params, $pdo, TRUE);
        if($mail == FALSE || $mail->user_id != $user_id){
          $pdo->rollback();
          return TRUE;
        }
        UserMail::deleteMailLog($mail, true, $pdo);
        $mail->delete($pdo);
        User::resetMailCount($user_id); // メール件数のキャッシュ削除.
      }elseif($all_flg){
        $params = array('user_id' => $user_id, 'offered' => UserMail::OFFERED, 'fav' => UserMail::NOT_FAVORITE, 'type' => UserMail::TYPE_MESSAGE);
        $mails = UserMail::findAllBy($params, null, null, $pdo);
        foreach($mails as $mail){
          UserMail::deleteMailLog($mail, true, $pdo);
        }
        $sql = 'DELETE FROM ' . static::TABLE_NAME;
        $sql .= ' WHERE user_id = ? AND offered = ? AND fav = ? AND TYPE <> ? AND TYPE <> ? AND TYPE <> ?';
        $stmt = $pdo->prepare($sql);
        if($wmode == User::MODE_NORMAL){
          $not_type = array(self::TYPE_ADMIN_MESSAGE_W, self::TYPE_ADMIN_BONUS_W, self::TYPE_ADMIN_BONUS_TO_ALL_W);
        }elseif($wmode == User::MODE_W){
          $not_type = array(self::TYPE_ADMIN_MESSAGE_NORMAL, self::TYPE_ADMIN_BONUS_NORMAL, self::TYPE_ADMIN_BONUS_TO_ALL_NORMAL);
        }
        $bind_param = array_merge(array($user_id, UserMail::OFFERED, UserMail::NOT_FAVORITE), $not_type);
        list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
        $mails = User::getMailCount($user_id, $wmode, $pdo, FALSE); // 一括削除の時はメール件数を返す.
      }
      $pdo->commit();
      return $mails;
    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
  }

  private static function deleteMailLog($mail, $isDeletedByUser, PDO $pdo){
    if($mail->type != UserMail::TYPE_MESSAGE){
      return;
    }
    $log_data = array();
    $log_data['sender_id'] = $mail->sender_id;
    $log_data['recipient_id'] = $mail->user_id;
    $log_data['message'] = $mail->message;
    $log_data['delByUser'] = $isDeletedByUser;
    $log_data['created_at'] = $mail->created_at;
    // user_id はメールの送信者としている.
    UserLogDeleteMail::log($mail->user_id, $log_data, $pdo);
  }

  /*
   * メールを受信する.
   * アイテム付きのメールであればユーザに付与する.
   */
  // #PADC# add parameters
  public static function getMail($mail_id, $user_id, $token){
  	$mail = UserMail::findBy(array('id'=>$mail_id, 'user_id'=>$user_id));
    if($mail == FALSE){
      throw new PadException(RespCode::MAIL_NOT_FOUND, "receiver (id=$user_id) mail_id=$mail_id.");
    }
    $user = NULL;
    $result = NULL;
    $item_offered = UserMail::OFFERED;
    if($mail->offered != 1){ // アイテム付与
    	$admin_mails = array(
        UserMail::TYPE_ADMIN_BONUS,
        UserMail::TYPE_ADMIN_BONUS_TO_ALL,
        UserMail::TYPE_ADMIN_BONUS_NORMAL,
        UserMail::TYPE_ADMIN_BONUS_TO_ALL_NORMAL,
        UserMail::TYPE_ADMIN_BONUS_W,
        UserMail::TYPE_ADMIN_BONUS_TO_ALL_W,
      );
      if(in_array($mail->type, $admin_mails) && $mail->bonus_id != BaseBonus::MAIL_ID){
        // 今回付与するので item_offered は 0.
        $item_offered = UserMail::NOT_OFFERED;
        try{
          $pdo = Env::getDbConnectionForUserWrite($user_id);
          $pdo->beginTransaction();
          $mail = UserMail::find($mail->id, $pdo, TRUE);
          $mail->offered = UserMail::OFFERED;
          $ex_params = array();
          $ex_params["ph"] = empty($mail->plus_hp) ? 0 : $mail->plus_hp;
          $ex_params["pa"] = empty($mail->plus_atk) ? 0 : $mail->plus_atk;
          $ex_params["pr"] = empty($mail->plus_rec) ? 0 : $mail->plus_rec;
          $ex_params["psk"] = 0;
          $ex_params["slv"] = empty($mail->slv) ? UserCard::DEFAULT_SKILL_LEVEL : $mail->slv;
          $ex_params["aid"] = 0;
          $ex_params["alv"] = 1;
          if($mail->message != ""){
            $data = json_decode($mail->message);
            if(isset($data->plus)){
              list($ex_params["ph"], $ex_params["pa"], $ex_params["pr"]) = $data->plus;
            }
            if(isset($data->slv)){
              $ex_params["slv"] = $data->slv;
            }
          }
          // Ver7.2～
          if ($mail->data) {
            $jdata = json_decode($mail->data);
            $cs = array("slv", "ph", "pa", "pr", "psk", "aid", "alv");
            $cs[] = 'pid';// #PADC# dataにpid（欠片）があれば$ex_paramsに追加
            foreach ($cs as $c) {
              if (isset($jdata->$c)) {
                $ex_params[$c] = $jdata->$c;
              }
            }
          }

          $user = User::find($user_id, $pdo, TRUE);
          $gold_before = $user->gold;
          $pgold_before = $user->pgold;
          //$coin_before = $user->coin;
          //$fripnt_before = $user->fripnt;
          UserTlog::beginTlog($user, array(
          		'money_reason' => Tencent_Tlog::REASON_BONUS,
          		'money_subreason' => Tencent_Tlog::SUBREASON_MAIL_BONUS,
          		'item_reason' => Tencent_Tlog::ITEM_REASON_BONUS,
          		'item_subreason' => Tencent_Tlog::ITEM_SUBREASON_MAIL_BONUS,
          ));
          // #PADC# add parameters
          $result = $user->applyBonus($mail->bonus_id, $mail->amount, $pdo, $ex_params, $token, $mail->piece_id);
          $gold_after = $user->gold;
          $pgold_after = $user->pgold;
          //$coin_after = $user->coin;
          //$fripnt_after = $user->fripnt;

          // アバター付与の場合
          if ($mail->bonus_id == BaseBonus::AVATAR_ID) {
            $jdata = json_decode($mail->data);
            $jdata->before_lv = (int)$result["before_lv"];
            $jdata->after_lv = (int)$result["after_lv"];
            $mail->data = json_encode($jdata);
          }
          $mail->update($pdo);

          $user->accessed_at = User::timeToStr(time());
          $user->accessed_on = $user->accessed_at;
          $user->update($pdo);

          $pdo->commit();

          if($mail->bonus_id == BaseBonus::MAGIC_STONE_ID || $mail->bonus_id == BaseBonus::PREMIUM_MAGIC_STONE_ID){
            UserLogAddGold::log($user->id, UserLogAddGold::TYPE_MAIL, $gold_before, $gold_after, $pgold_before, $pgold_after, $user->device_type);

            // #PADC# Tlog
            //UserTlog::sendTlogMoneyFlow($user, $pgold_after + $gold_after - $pgold_before - $gold_before, Tencent_Tlog::REASON_MAIL);
          }
          // #PADC# ----------begin----------
          //if($mail->bonus_id == BaseBonus::COIN_ID){
          //	UserTlog::sendTlogMoneyFlow($user, $coin_after- $coin_before, Tencent_Tlog::REASON_MAIL, Tencent_Tlog::MONEY_TYPE_MONEY);
          //}
          //if($mail->bonus_id == BaseBonus::FRIEND_POINT_ID){
          //	UserTlog::sendTlogMoneyFlow($user, $fripnt_after - $fripnt_before, Tencent_Tlog::REASON_MAIL, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT);
          //}
          UserTlog::commitTlog($user, $token);
          // #PADC# ----------end----------

        }catch(Exception $e){
          if ($pdo->inTransaction()) {
            $pdo->rollback();
          }
          throw $e;
        }
      }else{
        // 通常メールは既読にする.
        $pdo = Env::getDbConnectionForUserWrite($user_id);
        $pdo->beginTransaction();
        $mail = UserMail::find($mail->id, $pdo, TRUE);
        $mail->offered = UserMail::OFFERED;
        $mail->update($pdo);
        $pdo->commit();
        
        // #PADC# ----------begin---------　
        // 通常メールだけ、SNSとしてTlogに記入
        if($mail->sender_id > 0){
        	UserTlog::sendTlogSnsFlow($user_id, 1, Tencent_Tlog::SNSTYPE_RECEIVEEMAIL, $mail->sender_id);
        }
        // #PADC# ----------end----------
      }
    }
    if($user == NULL){
      $user = User::find($user_id);
    }
    // アバター付与時、メッセージにレベルアップ文言を追加.
    if ($mail->bonus_id == BaseBonus::AVATAR_ID) {
      $jdata = json_decode($mail->data);
      $br = $mail->message ? "\n" : "";
      $avatar = WAvatarItem::get($jdata->aid);
      if ($jdata->before_lv == $jdata->after_lv) {
        if ($jdata->after_lv == $avatar->mlv) {
          $mail->message .= sprintf("%s\n運営より、%sが届きました。\n\n%sのレベルはこれ以上\n上がりません。", $br, $avatar->name, $avatar->name);
        } else {
          $mail->message .= sprintf("%s\n運営より、%sLv%dが届きました。", $br, $avatar->name, $jdata->after_lv);
        }
      } else if ($jdata->before_lv < $jdata->after_lv) {
        $mail->message .= sprintf("%s\n運営より、%sが届きました。\n\n%sのレベルが\n%d/%dにアップしました。", $br, $avatar->name, $avatar->name, $jdata->after_lv, $avatar->mlv);
      }
    }

    return array($user, $mail, $item_offered, $result);
  }


  // 機種変コードや秘密のコードが含まれていた場合は送信者に警告メールを返す.
  private static function isIncludeCode($user_id, $message){
    $message = mb_convert_kana($message, "a", "UTF-8");
    if (preg_match('/(^|[^0-9a-z])(H?[0-9a-f]{9})([^0-9a-z]|$)/i', $message)) {
      // 秘密のコードの置換.
      $pdo_share = Env::getDbConnectionForShare();
      $support_data = SupportData::findBy(array('user_id' => $user_id), $pdo_share);
      if(isset($support_data->secret_code)){
        if (preg_match('/'.$support_data->secret_code.'/i', $message)) {
          if(Env::SERVICE_AREA=="HT"){
            $admin_message =<<< MSG
【！】由於包含「秘密のコード」，因此郵件無法發送。

「秘密のコード」是只有本人才知道的重要情報。
如果告訴其他人，會發生他人冒充遊戲持有者的危險，並且由於此原因，會出現今後無法再進行遊戲的可能性。
【請一定不要！】將「秘密のコード」告訴其他人。

※關於由此發生的損害責任，運營團隊不予負擔。

詳情請參見如下網址。
http://goe.bz/safe
MSG;
          }else{
            $admin_message =<<< MSG
【！】「秘密のコード」が含まれていたため、メールを送信できませんでした。

「秘密のコード」は、あなただけが知っている大切な情報です。
ほかの人に教えると、このゲームデータの持ち主であることをなりすまされてしまう恐れがあり、それにより、今後、このゲームデータでプレイできなくなる可能性があります。
「秘密のコード」を他人に【絶対に教えてはいけません！】

※これによって生じた損害の責任について、運営チームでは負いかねます。

くわしくはこちらをご覧ください。
http://goe.bz/safe
MSG;
          }
          self::sendAdminMail($user_id, $admin_message, UserMail::TYPE_ADMIN_MESSAGE);
          return TRUE;
        }
      }
      // 機種変コードの置換.
      $pdo_sender = Env::getDbConnectionForUserWrite($user_id);
      $cdd = ChangeDeviceData::findBy(array('user_id'=>$user_id), $pdo_sender);
      if(isset($cdd->code)){
        if (preg_match('/'.substr($cdd->code, 1, 9).'/i', $message)) {
          if(Env::SERVICE_AREA=="HT"){
            $admin_message =<<< MSG
【！】由於包含「機種変コード」，因此郵件無法發送。

「機種変コード」是將您的遊戲數據進行轉移的重要代碼。
如果將其告訴其他人，等於將重要的遊戲數據交予他人，
今後，會出現無法再進行此遊戲的可能性。
【請一定不要！】將「機種変コード」告訴其他人。

※關於由此發生的損害責任，運營團隊不予負擔。

詳情請參見如下網址。
http://goe.bz/safe
MSG;
          }else{
            $admin_message =<<< MSG
【！】「機種変コード」が含まれていたため、メールを送信できませんでした。

「機種変コード」は、あなた自身がこのゲームデータを、移行するためのコードです。
ほかの人に教えることは、大事なゲームデータを他人にわたすこととなり、
今後、このゲームデータでプレイできなくなる可能性があります。
「機種変コード」を他人に【絶対に教えてはいけません！】

※これによって生じた損害の責任について、運営チームでは負いかねます。

くわしくはこちらをご覧ください。
http://goe.bz/safe
MSG;
          }
          self::sendAdminMail($user_id, $admin_message, UserMail::TYPE_ADMIN_MESSAGE);
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  // #PADC# add title
  public static function sendMail($sender_id, $receiver_id, $message, $title = null){  	
    if(!Friend::isFriend($sender_id, $receiver_id)){
      throw new PadException(RespCode::NOT_FRIEND);
    }
    // 機種変コードや秘密のコードが含まれていた場合は送信者に警告メールを返す(国内版のみ).
    if(Env::REGION == 'JP' && self::isIncludeCode($sender_id, $message)){
      return TRUE;
    }

    $sender = User::find($sender_id);

    $pdo = Env::getDbConnectionForUserWrite($receiver_id);
    $pdo->beginTransaction();
    $receiver = User::find($receiver_id, $pdo);
    try{
      // メール受信設定チェック.
      if($receiver->checkRejectUserMail()){
        throw new PadException(RespCode::REJECT_USER_MAIL, " __NO_LOG");
      }
      $message_count = UserMail::countAllBy(array('user_id'=>$receiver_id, 'type'=>UserMail::TYPE_MESSAGE), $pdo);
      if($message_count >= UserMail::MESSAGE_MAX_COUNT ){
        // 受信者のメールboxがフルなので一番古いのを削除
        $oldest_mail = UserMail::findAllBy(array('user_id'=>$receiver_id, 'type'=>UserMail::TYPE_MESSAGE), "created_at ASC", array('limit'=>1), $pdo);
        if(count($oldest_mail)>0){
          $oldest_mail[0]->delete($pdo);
          UserMail::deleteMailLog($oldest_mail[0], false, $pdo);
        }
      }
      // メール保存(送信)
      $mail = new UserMail();
      $mail->user_id = $receiver_id;
      $mail->sender_id = $sender_id;
      $mail->type = UserMail::TYPE_MESSAGE;
      // #PADC#
      $mail->title = $title;
      $mail->message = $message;
      $mail->create($pdo);
      $pdo->commit();
      // 受取側ユーザのメール受信数キャッシュリセット.
      User::resetMailCount($receiver_id);
      
      // #PADC#
      UserTlog::sendTlogSnsFlow($sender_id, 1, Tencent_Tlog::SNSTYPE_SENDEMAIL, $receiver_id);
      $data = array(
      		'receiver_id' => $receiver_id,
      		'user_id' => $sender_id,
            // #PADC_DY# ----------begin----------
      		// 'RoleLevel' => $receiver->clear_dungeon_cnt,
      		'RoleLevel' => $receiver->lv,
            // #PADC_DY# ----------end----------
      		'TitleContents' => $title,
      		'ChatContents' => $message,
      		'ChatType' => 0,//$mail->type
            'RoleName' => $sender->name,
            'ReceiverName' => $receiver->name,
      );

      //send Tlog Sec Talk Flow
      UserTlog::sendTlogSecTalkFlow($data);
      
    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
  }

  // #PADC# add title
  public static function sendAdminMail($receiver_id, $message, $bonus_type, $data = null, $title = null){
    try{
      $pdo = Env::getDbConnectionForUserWrite($receiver_id);
      $pdo->beginTransaction();
      // メール保存(送信)
      static::sendAdminMailMessage($receiver_id, $bonus_type, BaseBonus::MAIL_ID, NULL, $pdo, $message, $data, $title);
      $pdo->commit();
    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }
  }

  /*
   * 運営からのメール送信(旧) 非推奨　廃止予定
   **/
  public static function sendAdminMailBonus($user_id, $bonus_type, $bonus_id, $amount, $pdo, $param = null, $message = "", $data = null){
    // メール保存(送信)
    if(is_array($param)) {
      $data = array();
      if($bonus_id <= BaseBonus::MAX_CARD_ID){
        $data["slv"] = isset($param["slv"]) ? (int)$param["slv"] : UserCard::DEFAULT_SKILL_LEVEL;
        $data["ph"] = isset($param["plus"][0]) ? (int)$param["plus"][0] : 0;
        $data["pa"] = isset($param["plus"][1]) ? (int)$param["plus"][1] : 0;
        $data["pr"] = isset($param["plus"][2]) ? (int)$param["plus"][2] : 0;
        $data["psk"] = isset($param["plus"][3]) ? (int)$param["plus"][3] : 0;
      }
      $message = $param['message'];
    }
    static::sendAdminMailMessage($user_id, $bonus_type, $bonus_id, $amount, $pdo, $message, $data);
  }

  /*
   * 運営からのメール送信
   * @param integer $user_id ユーザID
   * @param integer $bonus_type メール種別(UserMailのconst TYPE_ADMIN_* のいずれかを指定)
   * @param integer $bonus_id 共通報酬番号(BaseBonusのconstまたはカードID)
   * @param integer $amount 共通報酬の個数、またはカードのLV #NULLで省略可
   * @param PDO $pdo 書き込み用PDO
   * @param string $messege メッセージ文言 #NULLで省略可
   * @param array $data カードもしくはアバターアイテムのパラメータ連想配列 #NULLで省略可
   *   カード送信時のフォーマット
   *     "slv" integer カードのスキルレベル #省略可
   *     "ph"  integer プラス値 HP #省略可
   *     "pa"  integer プラス値 ATK #省略可
   *     "pr"  integer プラス値 REC #省略可
   *     "psk" integer 覚醒スキル #省略可
   *   アバターアイテム送信時のフォーマット
   *     "aid" integer アバターID
   *     "alv" integer アバターレベル
   **/
  public static function sendAdminMailMessage($user_id, $bonus_type, $bonus_id, $amount, PDO $pdo, $message, Array $data = null, $piece_id = 0, $title = null){
    // メール保存(送信)
    $mail = new UserMail();
    $mail->user_id = $user_id;
    $mail->sender_id = 0; // admin
    $mail->type = $bonus_type;
    $mail->bonus_id = $bonus_id;
    $mail->amount = $amount;
    // #PADC#
    $mail->title = $title;
    $mail->message = $message;
    $mail->data = json_encode($data);
    $mail->piece_id = $piece_id;
    $mail->create($pdo);
    // 受取側ユーザのメール受信数更新
    User::resetMailCount($user_id);
  }

  public static function getMails($user_id, $types, $offset, $limit, $mode = 0){
    $admin_mails = array(UserMail::TYPE_ADMIN_MESSAGE_NORMAL, UserMail::TYPE_ADMIN_BONUS_NORMAL, UserMail::TYPE_ADMIN_BONUS_TO_ALL_NORMAL);
    $admin_mails_w = array(UserMail::TYPE_ADMIN_MESSAGE_W, UserMail::TYPE_ADMIN_BONUS_W, UserMail::TYPE_ADMIN_BONUS_TO_ALL_W);
    if ($mode == User::MODE_NORMAL) {
      $types = array_merge($types, $admin_mails);
    } else if (User::MODE_W) {
      $types = array_merge($types, $admin_mails_w);
    }

    $offset = (int) $offset;
    $limit = (int) $limit;
    $sql = "SELECT * FROM " . UserMail::TABLE_NAME . " WHERE ";
    $sql .= "user_id = ? AND ";
    $sql .= "type IN ( " . join(',', $types) . ") ";
    $sql .= "ORDER BY id ASC LIMIT $offset, $limit";
    $values = array(
      $user_id,
    );
    $pdo = Env::getDbConnectionForUserRead($user_id);
    $stmt = $pdo->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
    $stmt->execute($values);
    $mails = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());

	if(Env::ENV !== "production"){
		global $logger;
		$logger->log(("sql_query: ".$sql."; bind: ". join(",",$values)), Zend_Log::DEBUG);
	}
        
	return $mails;
  }

  /**
   * $sender_id から $receiver_id に対してフレンド申請メールを送る
   */
  public static function sendFriendRequest($sender_id, $receiver_id, $admin_flg = FALSE, $mode = 0){
  	// #PADC# ----------begin----------
  	// 送信者と受信者が同じIDの場合エラー
	if($sender_id == $receiver_id)
  	{
      throw new PadException(RespCode::UNKNOWN_ERROR, "sender (id=$sender_id) and  receiver (id=$receiver_id) are the same. __NO_LOG");
  	}
  	// #PADC# ----------end----------
  	$requestMail = UserMail::findBy(array('type'=> UserMail::TYPE_FRIEND_REQUEST, 'user_id'=>$receiver_id, 'sender_id'=>$sender_id));
    if($requestMail){
      // already requested.
      return;
    }
    if(Friend::isFriend($sender_id, $receiver_id)){
      throw new PadException(RespCode::ALREADY_FRIEND, "sender (id=$sender_id) and  receiver (id=$receiver_id) are already friends. __NO_LOG");
    }
    $mail = new UserMail();
    $mail->type = UserMail::TYPE_FRIEND_REQUEST;
    $mail->message = null;
    $pdo = Env::getDbConnectionForUserWrite($receiver_id);
    $pdo->beginTransaction();
    try{
      $receiver = User::find($receiver_id, $pdo, TRUE);
      if($receiver === FALSE){
        throw new PadException(RespCode::UNKNOWN_ERROR, "mail receiver (id=$receiver_id) doesn't exist");
      }
      if($admin_flg === FALSE){
        // フレンド申請拒否設定チェック.
        if($receiver->checkRejectFriendRequest($mode)){
          throw new PadException(RespCode::REJECT_FRIEND_REQUEST, " __NO_LOG");
        }
        $FORMAT_REV_1 = 1;
        $sender_fri_data = User::getCacheFriendData($sender_id, User::FORMAT_REV_1,$pdo);
        if($sender_fri_data['fri'] >= $sender_fri_data['friMax']){
          throw new PadException(RespCode::SENDER_TOO_MANY_FRIENDS, "The number of sender(id=$sender_id)'s friends has exceeded a limit. __NO_LOG");
        }
        if($receiver->fr_cnt >= UserMail::FRIEND_REQUEST_MAX_COUNT){
          throw new PadException(RespCode::RECEIVER_TOO_MANY_FRIENDS, "The number of receiver(id=$receiver_id)'s friend requests has exceeded a limit. __NO_LOG");
        }
        if($receiver->fricnt >=  $receiver->friend_max){
          throw new PadException(RespCode::RECEIVER_TOO_MANY_FRIENDS, "The number of receiver(id=$receiver_id)'s friends has exceeded a limit. __NO_LOG");
        }
      }
      $mail->user_id = $receiver->id;
      $mail->sender_id = $sender_id;
      $mail->create($pdo);

      $receiver->fr_cnt++;
      $receiver->update($pdo);
      
      $pdo->commit();

      User::resetMailCount($receiver_id);

    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      throw $e;
    }

    // #PADC# Tlog
    UserTlog::sendTlogSnsFlow($sender_id, 1, Tencent_Tlog::SNSTYPE_INVITE, $receiver_id);
    
    return $mail;
  }

  /**
   * フレンド申請に対しての許可、または拒否を行い、申請メールを削除する.
   * @param $user_mail_id 申請メールid
   * @param $ack 0 for 拒否, 1 for 許可
   */
  public static function replyFriendRequest($recipient_user_id, $user_mail_id, $ack){
    try{
      // #PADC# pdoのプールによって同一dbidのコネクションを取得すると問題が発生するため、チェック用にUserDeviceを取得
      $share = Env::getDbConnectionForShare();
      $u1_device = UserDevice::find($recipient_user_id,$share);

      $pdo_user1 = Env::getDbConnectionForUserWrite($recipient_user_id);
      $pdo_user1->beginTransaction();

      $mail = UserMail::findBy(array('id' => $user_mail_id, 'user_id' => $recipient_user_id), $pdo_user1, TRUE);
      
      if($mail === FALSE){
        User::resetMailCount($recipient_user_id);
//        throw new PadException(RespCode::UNKNOWN_ERROR, "mail (id=$user_mail_id) not found.");
        return true;
      }
      if($mail->type != UserMail::TYPE_FRIEND_REQUEST){
        throw new PadException(RespCode::UNKNOWN_ERROR, "mail (id=$user_mail_id) is not a friend request. __NO_LOG");
      }
      if($mail->user_id != $recipient_user_id){
        throw new PadException(RespCode::UNKNOWN_ERROR, "The recipient(id=$mail->user_id) of the mail (id=$user_mail_id) is not this user(id=$recipient_user_id). __NO_LOG");
      }

      // #PADC# ----------begin----------
      // MY pdoのプールによって同一dbidのコネクションを取得すると問題が発生するため、チェック用にUserDeviceを取得して判定
      $u2_device = UserDevice::find($mail->sender_id,$share);
      if($u2_device->dbid != $u1_device->dbid)
      {
        $pdo_user2 = Env::getDbConnectionForUserWrite($mail->sender_id);
        $pdo_user2->beginTransaction();
      }
      else
      {
        $pdo_user2 = $pdo_user1;
      }
      
      // check is friend already
      if(Friend::isFriend($mail->user_id, $mail->sender_id, $pdo_user1)){
        $mail->delete($pdo_user1);
      	$pdo_user1->commit();
        throw new PadException(RespCode::UNKNOWN_ERROR, "Already friend");
      }
      
      // #PADC# ----------end----------
      if($ack == UserMail::ACK_ACCEPT){
        Friend::accept($mail->user_id, $mail->sender_id, $pdo_user1, $pdo_user2);
        $mail->delete($pdo_user1);
        // 反対方向にもフレンド申請が送信されていたら、今回フレンド関係が成立したので削除する
        $request_mail_reverse = UserMail::findBy(array('type'=>UserMail::TYPE_FRIEND_REQUEST, 'user_id'=>$mail->sender_id, 'sender_id'=>$mail->user_id), $pdo_user2);
        if($request_mail_reverse){
          $request_mail_reverse->delete($pdo_user2);
          $recipient = User::find($request_mail_reverse->user_id, $pdo_user2, TRUE);
          $recipient->fr_cnt--;
          $recipient->update($pdo_user2);
          User::resetMailCount($recipient->id);
        }
      }else if ($ack == UserMail::ACK_DENY){
        $mail->delete($pdo_user1);
      }else{
        throw new PadException(RespCode::UNKNOWN_ERROR, "Unknown ack value ($ack)");
      }
      $user = User::find($recipient_user_id, $pdo_user1, TRUE);
      $user->fr_cnt--;
      $user->accessed_at = User::timeToStr(time());
      $user->accessed_on = $user->accessed_at;
      $user->update($pdo_user1);
      $pdo_user1->commit();

      // #PADC# ----------begin----------
      if($pdo_user2->inTransaction())
      {
        $pdo_user2->commit();  
      }
      // #PADC# ----------end----------
      
      //#PADC# ----------begin----------
      if($ack == UserMail::ACK_ACCEPT){
      	UserTlog::sendTlogSnsFlow($recipient_user_id, 1, Tencent_Tlog::SNSTYPE_ACCEPT_INVITE, $mail->sender_id);
      }else{
      	UserTlog::sendTlogSnsFlow($recipient_user_id, 1, Tencent_Tlog::SNSTYPE_REFUSE_INVITE, $mail->sender_id);
      }
      //#PADC# ----------end----------
      
      User::resetMailCount($recipient_user_id);
    }catch(Exception $e){
      if(isset($pdo_user1) && $pdo_user1->inTransaction()) {
        $pdo_user1->rollback();
      }
      if(isset($pdo_user2) && $pdo_user2->inTransaction()){
        $pdo_user2->rollback();
      }
      throw $e;
    }
  }

  /**
   * $sender_id から $receiver_id に対してお礼返しを送る
   * @param $pdo 書き込み用PDO
   */
  public static function sendThankYouGift($sender_id, $receiver_id, $pdo){
    $isFriend = Friend::isFriend($sender_id,$receiver_id);
    if($isFriend===FALSE){
      throw new PadException(RespCode::NOT_FRIEND);
    }
    $mail = new UserMail();
    $mail->type = UserMail::TYPE_THANKYOU_GIFT;
    $mail->message = null;
    $receiver = User::find($receiver_id, $pdo, TRUE);
    if($receiver===FALSE){
      throw new PadException(RespCode::UNKNOWN_ERROR, "mail receiver (id=$receiver_id) doesn't exist");
    }
    $receiver->pback_cnt++;
    $receiver->update($pdo);

    $mail->user_id = $receiver->id;
    $mail->sender_id = $sender_id;
    $mail->create($pdo);
  }

  /**
   * $sender_id から $receiver_id に対してお礼返しを送る
   * @param $pdo 書き込み用PDO
   */
  public static function receiveThankYouGift($receiver_id, $pdo){
    $mail = UserMail::findBy(array('user_id'=> $receiver_id, 'type'=>UserMail::TYPE_THANKYOU_GIFT), $pdo, TRUE);
    if( $mail == FALSE ){
      throw new PadException(RespCode::UNKNOWN_ERROR, "A thank-you gift mail (user id=$receiver_id) not found");
    }
    $sender_id = $mail->sender_id;
    $mail->delete($pdo);
    return $sender_id;
  }

  /**
   * メールお気に入り登録
   * $user_idの$mail_idに対応するお気に入りフラグ(fav)を立てる
   */
  public static function saveFav($user_id, $mail_id, $fav){
    if($fav != UserMail::NOT_FAVORITE && $fav != UserMail::FAVORITE){
      throw new PadException(RespCode::UNKNOWN_ERROR, "Invalid parameter fav = $fav.");
    }
    $pdo = Env::getDbConnectionForUserWrite($user_id);
    $sql = 'UPDATE ' . static::TABLE_NAME;
    $sql .= ' SET fav = ?';
    $sql .= ' WHERE user_id = ? AND id = ?';
    $bind_param = array($fav, $user_id, $mail_id);
    self::prepare_execute($sql, $bind_param, $pdo);
    return TRUE;
  }

  /**
   * メールのタイプでカウント.
   */
  public static function countTypeBy($user_id, $pdo) {
    $mail_cnt = 0;
    $w_mail_cnt = 0;
    $params = array($user_id);
    $sql = "select type from user_mails where user_id = ?";
    list($result, $stmt) = self::prepare_execute($sql, $params, $pdo);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
      switch ($r['type']) {
        case UserMail::TYPE_FRIEND_REQUEST:
        case UserMail::TYPE_MESSAGE:
        case UserMail::TYPE_ADMIN_MESSAGE: 
        case UserMail::TYPE_ADMIN_BONUS:
        case UserMail::TYPE_ADMIN_BONUS_TO_ALL:
          $mail_cnt++;
          $w_mail_cnt++;
          break;
        case UserMail::TYPE_THANKYOU_GIFT:
        case UserMail::TYPE_ADMIN_MESSAGE_NORMAL:
        case UserMail::TYPE_ADMIN_BONUS_NORMAL:
        case UserMail::TYPE_ADMIN_BONUS_TO_ALL_NORMAL:
          $mail_cnt++;
          break;
        case UserMail::TYPE_ADMIN_MESSAGE_W:
        case UserMail::TYPE_ADMIN_BONUS_W:
        case UserMail::TYPE_ADMIN_BONUS_TO_ALL_W:
          $w_mail_cnt++;
          break;
      }
    }

    return array($mail_cnt, $w_mail_cnt);
  }

  /**
   * idip用清除用户发送邮件
   * @param $user_id
   * @param $sender_id
   */
  public static function clearSpeak($user_id, $sender_id) {
    $pdo = Env::getDbConnectionForUserWrite($user_id);
    $sql = 'DELETE FROM ' . static::TABLE_NAME;
    $sql .= ' WHERE user_id = ? AND sender_id = ? AND type = ' . UserMail::TYPE_MESSAGE;
    $bind_param = array($user_id, $sender_id);
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($bind_param);
  }

}
