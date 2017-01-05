<?php

class Price {

    /**
     * iOSの価格リストを返す.
     * @return array
     */
    public static function priceArrayIos() {
      if ( Env::REGION == 'NA' || Env::REGION == 'KR' ) {
        // 北米,韓国版価格リスト
        $price_array = array('001'=>99, '006'=>499, '012'=>999, '030'=>2299, '060'=>4399, '085'=>5999);
      } elseif ( Env::REGION == 'EU' ) {
        // 欧州版価格リスト(ポンド・ペンス)
        $price_array = array('001'=>69, '006'=>299, '012'=>699, '030'=>1599, '060'=>3099, '085'=>3999);
      } else {
        // 日本版価格リスト
        $price_array = array('001'=>100, '006'=>500, '012'=>900, '030'=>2000, '060'=>3800, '085'=>5000);
      }
      $add_price_list = ProductBonusItem::getAddPriceList(User::TYPE_IOS);
      $price_array = array_merge($add_price_list, $price_array);
      return $price_array;
    }

    /**
     * Androidの価格リストを返す.
     * @return array
     */
    public static function priceArrayAdr() {
      if ( Env::REGION == 'NA' ) {
        // 北米版価格リスト
        $price_array = array('001'=>107, '006'=>499, '012'=>999, '030'=>2299, '060'=>4399, '085'=>5999);
      } elseif ( Env::REGION == 'KR' ) {
        // 韓国版価格リスト
        $price_array = array('001'=>104, '006'=>499, '012'=>999, '030'=>2298, '060'=>4398, '085'=>5999);
      } elseif ( Env::REGION == 'EU' ) {
        // 欧州版価格リスト(ポンド・ペンス)
        $price_array = array('001'=>69, '006'=>299, '012'=>699, '030'=>1599, '060'=>3099, '085'=>3999);
      } else {
        // 日本版価格リスト
        $price_array = array('001'=>100, '006'=>500, '012'=>900, '030'=>2000, '060'=>3800, '085'=>5000);
      }
      $add_price_list = ProductBonusItem::getAddPriceList(User::TYPE_ANDROID);
      $price_array = array_merge($add_price_list, $price_array);
      return $price_array;
    }

    /**
     * Kindleの価格リストを返す.
     * @return array
     */
    public static function priceArrayAmz() {
      if ( Env::REGION == 'NA' ) {
        // 北米版価格リスト
        $price_array = array('001'=>99, '006'=>499, '012'=>999, '030'=>2299, '060'=>4399, '085'=>5999);
      } else {
        // 日本版価格リスト
        $price_array = array('001'=>100, '006'=>500, '012'=>900, '030'=>2000, '060'=>3800, '085'=>5000);
      }
      $add_price_list = ProductBonusItem::getAddPriceList(User::TYPE_AMAZON);
      $price_array = array_merge($add_price_list, $price_array);
      return $price_array;
    }
    
    /**
     * 通貨単位を返す.
     * @return string $monetary_unit 通貨単位
     */
    public static function monetaryUnit() {
      if ( Env::REGION == 'NA' || Env::REGION == 'KR' ) {
        $monetary_unit = 'USドル';
      } elseif(Env::REGION == 'EU') {
        $monetary_unit = 'ポンド';
      } else {
        $monetary_unit = '円';
      }
      return $monetary_unit;
    }

    /**
     * 価格をフォーマットして返す.
     * @return float $price 価格
     */
    public static function price_format( $price ) {
      $decimals = 0;
      if ( Env::REGION == 'NA' || Env::REGION == 'KR' ) {
        $decimals = 2;
        $price = $price / 100;//セント→ドル表記に変更
      } elseif ( Env::REGION == 'EU' ) {
        $decimals = 2;
        $price = $price / 100;//ペンス→ポンド表記に変更
      }
      return number_format( $price, $decimals );
    }
}
