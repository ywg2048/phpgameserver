<?php
/**
 * 2. ダンジョン販売データダウンロード
 */
class GetDungSale extends BaseAction {
  // http://pad.localhost/api.php?action=get_dung_sale&pid=1&sid=1
  const MEMCACHED_EXPIRE = 86400; // 24時間.
  // #PADC#
  const MAIL_RESPONSE = FALSE;
  const ENCRYPT_RESPONSE = FALSE;
  public function action($params){

    $key = MasterCacheKey::getDownloadDungeonSaleData();
    $value = apc_fetch($key);
    if(FALSE === $value) {
      $ds = DownloadMasterData::find(DownloadMasterData::ID_DUNGEON_SALES);
      if (isset($ds->gzip_data)) {
        $value = $ds->gzip_data;
      } else {
        $value = '{"res":0,"v":1,"d":""}';
      }
      apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
    }
    return $value;
  }

  /**
   * DungeonSaleのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  public static function arrangeColumns($dungeon_sales) {
    $data = "";
    foreach($dungeon_sales as $ds) {
      $arr = array("T");
      $arr[] = date("ymdHis", strtotime($ds->begin_at));
      $arr[] = date("ymdHis", strtotime($ds->finish_at));
      $arr[] = $ds->font_color;
      $arr[] = $ds->panel_color;
      $arr[] = $ds->message;
      $data .= implode(",", $arr)."\n";

      foreach ($ds->commodities as $dsc) {
        $cs = array("D");
        $cs[] = (int)$dsc->dungeon_id;
        $cs[] = (int)$dsc->price;
        $cs[] = (int)$dsc->open_hour;
        $cs[] = $dsc->message;
        $data .= implode(",", $cs)."\n";
      }
    }
    return $data;
  }

}
