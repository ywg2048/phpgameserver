<?php
/**
 * 購入したダンジョン.
 */

class UserBuyDungeon extends BaseModel {
  const TABLE_NAME = "user_buy_dungeon";

  protected static $columns = array(
    'id',
    'user_id',
    'dungeon_id',
    'expire_at',
    'buy_at',
  );

  /**
   * ダンジョン購入処理
   */
  public static function buy($user_id, $dungeon_id,$token) {
    try{
    // #PADC# 
      $billno = 0;
      $pdo = Env::getDbConnectionForUserWrite($user_id);
      $pdo->beginTransaction();
      $params = array('user_id' => $user_id, 'dungeon_id' => $dungeon_id);
      //find all user have already purchased and are active dungeons
      $user_buy_dungeons = static::findAllBy($params, null,null,$pdo);
      
      //whether or not user buy the same dungeon,if true,tell user,you have already purchased
      if(!empty($user_buy_dungeons)){
      	foreach ($user_buy_dungeons as $user_buy_dungeon){
      		if((int)$user_buy_dungeon->dungeon_id == (int)$dungeon_id && intval($user_buy_dungeon->expire_at) > time()){
      			throw new PadException(RespCode::ALREADY_PURCHASED,'already purchased');
      		}
      	}
      }

      $dungeon_sales = DungeonSale::getAll();
      $dungeon_sale_ids = array();
      foreach($dungeon_sales as $ds){
      	//get dungeon_sale_id is already begin,and haven't ended dungeon sale
        if(static::strToTime($ds->begin_at) <= time() && static::strToTime($ds->finish_at) > time()){
          $dungeon_sale_ids[] = $ds->id;
        }
      }
      //if no activity is alive,user can't buy any thing
      if(empty($dungeon_sale_ids)){
        // 購入対象のダンジョンが存在しない.
        throw new PadException(RespCode::FAILED_BUY_DUNGEON, " __NO_LOG");
      }
      $dungeon_sale_commodities = array();
		foreach ($dungeon_sale_ids as $dungeon_sale_id){
			$params = array('dungeon_sale_id' => $dungeon_sale_id);
			$dungeon_sale_commodities[$dungeon_sale_id] = DungeonSaleCommodity::getAllBy($params,"dungeon_sale_id ASC");
		}

      foreach($dungeon_sale_commodities as $dungeon_sale_commodity) {
      	$target_dsc = self::getTargetDsc($dungeon_sale_commodity,$dungeon_id);
      	//this is for get first dungeon id if have two activities
      	if(!empty($target_dsc)){
      		break;
      	} 
      }
      
      if(!isset($target_dsc)){
        // 購入対象のダンジョンが存在しない.
        throw new PadException(RespCode::FAILED_BUY_DUNGEON, " __NO_NOG");
      }
      

      $user = User::find($user_id, $pdo, TRUE);

      // #PADC_DY# ----------begin----------
      // #PADC#
      //if($user->clear_dungeon_cnt < GameConstant::DUNGEON_SALE_OPEN_CLEAR_COUNT){
      //	throw new PadException(RespCode::FAILED_BUY_DUNGEON, " __NO_NOG");
      //}
      // 购买关卡为到达特定等级解锁
      $buy_dungeon_roadmap =  Roadmap::getBy(array("unlock_type" => Roadmap::UNLOCK_TYPE_GAME_FUNCTION, "unlock_id" => Roadmap::UNLOCK_ID_BUY_DUNGEON));
      if($user->lv < $buy_dungeon_roadmap->lv){
        throw new PadException(RespCode::FAILED_BUY_DUNGEON, " __NO_NOG");
      }
      // #PADC_DY# ----------end----------
      
      if($user->checkHavingGold($target_dsc->price) === FALSE) {
        // gold不足.
        throw new PadException(RespCode::FAILED_BUY_DUNGEON, " __NO_NOG");
      }
      //#PADC# ------------begin-------------------------------------
      $before_gold = $user->gold;
      $before_pgold = $user->pgold;
      
      $billno = $user->payGold($target_dsc->price,$token, $pdo);
      $after_gold = $user->gold;
      $after_pgold = $user->pgold;
      //#PADC# ------------end-------------------------------------
      $user->update($pdo);
      
      // #PADC#
      UserTlog::sendTlogMoneyFlow($user, -1 * $target_dsc->price, Tencent_Tlog::REASON_BUY_DUNGEON, Tencent_Tlog::MONEY_TYPE_DIAMOND, abs($after_gold - $before_gold), abs($after_pgold - $before_pgold) );

      // delete expired records
      $sql = "DELETE FROM " . static::TABLE_NAME . " WHERE user_id = ? AND expire_at < ?";
      $bind_param = array($user->id,time());
      self::prepare_execute($sql, $bind_param, $pdo);

      $time = time();

      $user_buy_dungeon = new UserBuyDungeon();
      $user_buy_dungeon->user_id = $user_id;
      $user_buy_dungeon->dungeon_id = $dungeon_id;
      $user_buy_dungeon->expire_at = $time + ($target_dsc->open_hour * 3600) + 60; // 念のため60秒追加.
      $user_buy_dungeon->buy_at = static::timetoStr($time);
      $user_buy_dungeon->create($pdo, TRUE);

      $ubd_history = new UserBuyDungeonHistory();
      $ubd_history->user_id = $user_id;
      $ubd_history->dungeon_id = $dungeon_id;
      $ubd_history->expire_at = $time + ($target_dsc->open_hour * 3600) + 60; // 念のため60秒追加.
      $ubd_history->buy_at = static::timetoStr($time);
      //#PADC# ------------begin-------------------------------------
      //　#PADC# coinという名前だが、魔法石の数を保存のようにしておいていただければと思います
      $ubd_history->before_coin = $before_gold + $before_pgold;
      $ubd_history->after_coin = $after_gold + $after_pgold;
      //#PADC# ------------end-------------------------------------
      $ubd_history->create($pdo);

      $pdo->commit();
      
      //#PADC#
      $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
    }catch(Exception $e){
      if ($pdo->inTransaction()) {
        $pdo->rollback();
      }
      // #PADC# 魔法石消費を取り消す ----------begin----------
      if($billno){
      	$user->cancelPay($target_dsc->price, $billno, $token);
      }
      //----------end----------
      throw $e;
    }
    // #PADC#
    return array((int)$user->gold + (int)$user->pgold, strftime("%y%m%d%H%M%S", $user_buy_dungeon->expire_at));
  }
  
  /**
   * 
   * @param array $values
   * @param int $id
   * @return int
   */
  protected static function getTargetDsc($values,$id){
  	$target = null;
  	foreach ($values as $value){
  		if($value->dungeon_id == $id) {
  			$target = $value;
  			break;
  		}
  	}
  	return $target;
  }

  /**
   * 購入済みダンジョンチェック
   */
  public static function check($user_id, $dungeon_id, $pdo) {
    $ubd = static::findBy(array('user_id' => $user_id, 'dungeon_id' => $dungeon_id), $pdo);
    if(!empty($ubd) && intval($ubd->expire_at) >= time()){
      return TRUE;
    }
    return FALSE;
  }

  /**
   * 購入したダンジョン情報を返す.
   */
  public static function get_dbou($user_id, $pdo) {
    $dbou_arr = array();
    $sql = "SELECT * FROM " . static::TABLE_NAME;
    $sql .= " WHERE user_id = ?";
    $sql .= " AND expire_at >= ?";
    $sql .= " ORDER BY buy_at asc";
    $stmt = $pdo->prepare($sql);
    $bind_param = array($user_id, time());
    $stmt->execute($bind_param);
    $user_buy_dungeons = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
    if($user_buy_dungeons){
      foreach($user_buy_dungeons as $ubd){
        $dbou_arr[] = array((int)$ubd->dungeon_id, strftime("%y%m%d%H%M%S", $ubd->expire_at));
      }
    }
    return $dbou_arr;
  }

}
