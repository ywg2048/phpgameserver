<?php

class ProductList {

  const AGE_16_NUM = 0;  // 16歳未満(5,000円まで)
  const AGE_19_NUM = 1;  // 16～19歳(20,000円まで)
  const AGE_ETC_NUM = 2; // 20歳～(制限なし)

  const AGE_16_LIMIT_PRICE = 5000;
  const AGE_19_LIMIT_PRICE = 20000;

  const SPC_NORMAL = 0;
  const SPC_SPECIAL = 1;

  public static function getBuyAbleProductItems($tier, $user_id, $wmode){
    $limit_price = 0;
    $sum_price = 0;
    if($tier == static::AGE_16_NUM){
      $limit_price = static::AGE_16_LIMIT_PRICE;
    }elseif($tier == static::AGE_19_NUM){
      $limit_price = static::AGE_19_LIMIT_PRICE;
    }
//一時的にこちらに移してます
    $pdo = Env::getDbConnectionForUserRead($user_id);
    if($limit_price > 0){
      // 未成年の場合は当月の購入金額合計を取得.
//      $pdo = Env::getDbConnectionForUserRead($user_id);
      $sum_price += PurchaseLog::totalPurchasesOfThisMonth($user_id, $pdo);
      $sum_price += AdrPurchaseLog::totalPurchasesOfThisMonth($user_id, $pdo);
      $sum_price += AmzPurchaseLog::totalPurchasesOfThisMonth($user_id, $pdo);
      // debug.
      //global $logger;
      //$logger->log(("getBuyAbleProductItems user_id:$user_id sum_price:".$sum_price), Zend_Log::DEBUG);
    }
    $product_items = Env::getProductItems();
    $active_product_bonus_items = self::getActiveProductBonusItems($user_id, $pdo);
    $product_items = array_merge($active_product_bonus_items, $product_items);
    $ret_items = array();
    foreach($product_items as $p){
      $price = (int)mb_ereg_replace("[^0-9]", "", $p['price']);
      $buyable = 1;
      if($limit_price > 0){
        // 当月の購入金額合計＋商品の金額が上限を超えているかをチェック.
        if($sum_price + $price > $limit_price){
          $buyable = 0;
        }
      }
      $p['buyable'] = $buyable;
      $ret_items[] = $p;
    }
    return $ret_items;
  }

  // 魔法石購入ボーナスリストを取得.
  public static function getActiveProductBonusItems($user_id, PDO $pdo){
    $active_bonuses = array();
    $active_bonus_list = ProductBonusItem::getAll();
    foreach($active_bonus_list as $obj){
      if(strtotime($obj->begin_at) > time() || strtotime($obj->finish_at) < time()){
        // 期間外.
        continue;
      }
      $service_area_id = User::getAreaIdByName(Env::SERVICE_AREA);
      if($service_area_id != $obj->area_id){
        // 仕向地が対象外.
        continue;
      }
      $device_type = User::getDeviceTypeOfEnv();
      $product = ProductBonusItem::getProductBonusOfDevice($device_type, $obj);
      if(!UserBuyProductBonusItem::checkPurchased($user_id, $product->code, $pdo)){
        // 未購入の現在有効な魔法石購入ボーナス.
        $active_bonuses[] = array(
          'name' => $product->name,
          'code' => $product->code,
          'price' => $product->price,
          'bmsg' => $product->bmsg,
          'spc' => ProductList::SPC_SPECIAL, //特殊フラグをON.
          'buyable' => 0
        );
      }     
    }
    return $active_bonuses;
  }

}
