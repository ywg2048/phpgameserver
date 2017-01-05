<?php
/**
 * 魔法石購入ボーナス.
 */

class ProductBonusItem extends BaseMasterModel {
  const TABLE_NAME = "product_bonus_item";
  const VER_KEY_GROUP = "pbitm";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'begin_at',
    'finish_at',
    'name',
    'ios_code',
    'adr_code',
    'amz_code',
    'ios_tag',
    'adr_tag',
    'amz_tag',
    'ios_price',
    'adr_price',
    'amz_price',
    'add_pgold_first',
    'add_pgold_2nd',
    'area_id',
  );

  // デバイス別のプロダクトIDを返す.
  public static function getProductBonusOfDevice($device_type, ProductBonusItem $product_bonus_item){
    switch ($device_type) {
      case User::TYPE_ANDROID:
        $code = $product_bonus_item->adr_code;
        $tag = $product_bonus_item->adr_tag;
        $price = $product_bonus_item->adr_price;
        break;
      case User::TYPE_AMAZON:
        $code = $product_bonus_item->amz_code;
        $tag = $product_bonus_item->amz_tag;
        $price = $product_bonus_item->amz_price;
        break;
      default:
        $code = $product_bonus_item->ios_code;
        $tag = $product_bonus_item->ios_tag;
        $price = $product_bonus_item->ios_price;
        break;
    }
    $bmsg = $product_bonus_item->name;
    $name = mb_substr($product_bonus_item->name, 0, mb_strpos($product_bonus_item->name, '|'));
    $ret = array(
      'name' => $name,
      'code' => $code,
      'tag' => $tag,
      'price' => $price,
      'bmsg' => $bmsg,
    );
    return (object)$ret;
  }

  // Priceモデルから呼ばれる.
  public static function getAddPriceList($device_type){
    $list = array();
    $active_bonus_list = ProductBonusItem::getAll();
    foreach($active_bonus_list as $obj){
      if($device_type == User::TYPE_IOS){
        $product_id = $obj->ios_code;
        $price = $obj->ios_price;
      }elseif($device_type == User::TYPE_ANDROID){
        $product_id = $obj->adr_code;
        $price = $obj->adr_price;
      }elseif($device_type == User::TYPE_AMAZON){
        $product_id = $obj->amz_code;
        $price = $obj->amz_price;
      }
      $key = ProductBonusItem::getUniqueCode($product_id);
      $list[$key] = $price;
    }
    return $list;
  }

  // "jp.gungho.pad.PadStoneCampaign001_005"の"001_005"の部分をユニークキーとする.
  public static function getUniqueCode($product_id){
    return substr($product_id, strlen($product_id) - 7, 7);
  }

}
