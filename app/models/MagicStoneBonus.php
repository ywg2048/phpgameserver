<?php
/**
 * 魔石ボーナス.
 */
class MagicStoneBonus extends BaseBonus {

  function __construct($amount) {
    $this->item_id = static::MAGIC_STONE_ID;
    $this->amount = $amount;
  }
}
