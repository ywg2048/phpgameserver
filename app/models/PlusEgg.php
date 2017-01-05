<?php
/**
 * 新規卵発生モデル（ver2.0-）
 */
class PlusEgg {
  public $hp;
  public $atk;
  public $rec;

  const DUNGEON = 1;	// ダンジョン
  const GACHA_FRIEND = 2;	// 友情ガチャ
  const GACHA_CHARGE = 3;	// レアガチャ
  const GACHA_THANKS = 4;	// プレゼントガチャ

  /**
   * $typeに応じた＋値を返す
   */
  public static function getPlusParam($type, $dungeon_id = null) {
    if($type == PlusEgg::DUNGEON) {
      $ratio = GameConstant::getParam("DungPlusDrop");
    } elseif($type == PlusEgg::GACHA_FRIEND) {
      $ratio = GameConstant::getParam("FrigPlusDrop");
      // 適用可能な友情ガチャ＋確率設定の時限設定をチェック.
      $bonus = LimitedBonus::getActiveFriendGachaPlusEgg();
      if($bonus) {
        $ratio = $bonus->args;
      }
    } elseif($type == PlusEgg::GACHA_CHARGE) {
      $ratio = GameConstant::getParam("RarePlusDrop");
      // 適用可能なレアガチャ＋確率設定の時限設定をチェック.
      $bonus = LimitedBonus::getActiveChargeGachaPlusEgg();
      if($bonus) {
        $ratio = $bonus->args;
      }
    } elseif($type == PlusEgg::GACHA_THANKS) {
      $ratio = GameConstant::getParam("PresPlusDrop");
      // 適用可能なプレゼントガチャ＋確率設定をチェック.
      $bonus = LimitedBonus::getActivePresentGachaPlusEgg();
      if($bonus) {
        $ratio = $bonus->args;
      }
    } else {
      return false;
    }
    $pe = new PlusEgg();
    $pe->hp = 0;
    $pe->atk = 0;
    $pe->rec = 0;
    if($dungeon_id) {
      // 適用可能なダンジョン＋確率設定の時限設定をチェック.
      $bonus = LimitedBonus::getActiveDungeonPlusEgg($dungeon_id);
      if($bonus) {
        $drop_prob = $bonus->args;
      }else{
        // ダンジョンごとの＋値卵ドロップ補正値.
        $dungeon_plus_drop = DungeonPlusDrop::get($dungeon_id);
        $drop_prob = $dungeon_plus_drop->drop_prob;
      }
      // 適用可能なダンジョン＋確率倍増の時限設定をチェック.
      $bonus = LimitedBonus::getActiveDungeonPlusEggUp($dungeon_id);
      if($bonus) {
        $drop_prob = $drop_prob * ($bonus->args / 10000.0);
      }
    } else { 
      $drop_prob = 10000;
    }
    $rand = mt_rand(1, 10000);
    if((($ratio * $drop_prob) / 10000) >= $rand) {
      $div_hp = GameConstant::getParam("PlusHP");
      $div_atk = GameConstant::getParam("PlusATK");
      $rand = mt_rand(1, 12000);
      if($div_hp >= $rand) {
        $pe->hp = 1;
      } elseif (($div_hp + $div_atk) >= $rand) {
        $pe->atk = 1;
      } else {
        $pe->rec = 1;
      }
    }
    return $pe;
  }

}
