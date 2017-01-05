<?php
/**
 * ウェーブに出現するモンスター.
 */

class WaveMonster extends BaseMasterModel {
  const TABLE_NAME = "wave_monsters";
  const VER_KEY_GROUP = "dung";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'wave_id',
    'card_id',
    'lv',
    'lv_rnd',
    'prob',
    'boss',
    // #PADC# ----------begin----------
    'drop_min',
    'drop_max',
    // #PADC# ----------end----------
  );

  /**
   * カードに規定された属性を取得する.
   */
  public function getCardAttr() {
    $card = Card::get($this->card_id);
    if($card) {
      return $card->attr;
    } else {
      throw new PadException(RespCode::UNKNOWN_ERROR, "DATA ERROR: card not found.");
    }
  }

  /**
   * カードに規定されたサイズを取得する.
   */
  public function getMonsterWidth() {
    $card = Card::get($this->card_id);
    if($card) {
      return Card::$size_to_width[$card->size];
    } else {
      throw new PadException(RespCode::UNKNOWN_ERROR, "DATA ERROR: card not found.");
    }
  }

}
