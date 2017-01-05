<?php
/**
 * 海外版翻訳テキスト.
 */

class ConvertText extends BaseMasterModel {
  const TABLE_NAME = "convert_text";
  const VER_KEY_GROUP = "cnv_txt";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  const TYPE_CARD = 1;
  const TYPE_SKILL = 2;
  const TYPE_DUNGEON = 3;
  const TYPE_ENEMY_SKILL = 4;
  const TYPE_WDUNGEON = 5;
  const TYPE_WAVATAR_ITEM = 6;

  protected static $columns = array(
    'id',
    'master_type',
    'org_text',
    'text',
  );
  
  /**
   * #PADC#
   * 海外版テキストをキーとした日本語テキストの配列を取得
   * @return multitype:NULL
   */
  public static function getConvertTextArrayByTextKey()
  { 
	  // 翻訳データ
	  $textData = array();
	  $convertTextData = ConvertText::findAllBy(array());
	  foreach($convertTextData as $_convertTextData)
	  {
	  	$textData[$_convertTextData->text] = $_convertTextData->org_text;
	  }
	  return $textData;
  }
}
