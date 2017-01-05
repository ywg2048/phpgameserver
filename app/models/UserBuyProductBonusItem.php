<?php
/**
 * 購入した魔法石購入ボーナス.
 */

class UserBuyProductBonusItem extends BaseModel {
  const TABLE_NAME = "user_buy_product_bonus_item";
  const MEMCACHED_EXPIRE = 7200; // 2時間.

  protected static $columns = array(
    'id',
    'user_id',
    'unique_code',
    'code',
    'transaction_id',
    'add_pgold',
    'area_id',
    'device_type',
    'updated_at',
    'created_at',
  );

  // 魔法石購入ボーナス購入を記録.
  public static function setProductBonusItem(User $user, $product_id, $transaction_id, PDO $pdo){
    $unique_code = ProductBonusItem::getUniqueCode($product_id);
    $active_bonus_list = ProductBonusItem::getAll();
    foreach($active_bonus_list as $obj){
      $product = ProductBonusItem::getProductBonusOfDevice($user->device_type, $obj);
      if($product->code == $product_id){
        $product_bonus_item = $obj;
        break;
      }
    }
    if(!isset($product_bonus_item)){
      // 該当するボーナスアイテムが無い.
      return FALSE;
    }
    if(self::checkPurchased($user->id, $product_id, $pdo)){
      // 2回目以降のボーナスアイテム購入.付与する魔法石数を返す(2回目以降).
      return $product_bonus_item->add_pgold_2nd;
    }
    $user_buy_product_bonus_item = New UserBuyProductBonusItem();
    $user_buy_product_bonus_item->user_id = $user->id;
    $user_buy_product_bonus_item->unique_code = $unique_code;
    $user_buy_product_bonus_item->code = $product_id;
    $user_buy_product_bonus_item->transaction_id = $transaction_id;
    $user_buy_product_bonus_item->add_pgold = $product_bonus_item->add_pgold_first;
    $user_buy_product_bonus_item->area_id = $user->area_id;
    $user_buy_product_bonus_item->device_type = $user->device_type;
    $user_buy_product_bonus_item->create($pdo);
    // 付与する魔法石数を返す(初回購入).
    return $product_bonus_item->add_pgold_first;
  }

  // 魔法石購入ボーナスを購入済みかチェック.
  public static function checkPurchased($user_id, $product_id, PDO $pdo){
    $unique_code = ProductBonusItem::getUniqueCode($product_id);
    $param = array('user_id' => $user_id, 'unique_code' => $unique_code);
    $ubpbi = UserBuyProductBonusItem::findBy($param, $pdo, TRUE);
    if(empty($ubpbi)){
      return FALSE; // 未購入.
    }
    return TRUE; // 購入済み.
  }

}
