<?php
/**
 * #PADC#
 * プラスのカケラ
 * PAD版のPlusEggの代わり
 */
class PlusPiece {
	public $hp;
	public $atk;
	public $rec;

	// PADC_INFO:現状、ダンジョン以外に利用するかどうかわからない
	const DUNGEON = 1;	// ダンジョン
	const GACHA_FRIEND = 2;	// 友情ガチャ
	const GACHA_CHARGE = 3;	// レアガチャ
	const GACHA_THANKS = 4;	// プレゼントガチャ

	/**
	 * $typeに応じた＋値を返す
	 */
	public static function getPlusParam($type, $dungeon_id = null) {

		$pp = new PlusPiece();
		$pp->hp = 0;
		$pp->atk = 0;
		$pp->rec = 0;

		if($type == PlusPiece::DUNGEON) {
			$ratio = GameConstant::getParam("DungPlusDrop");
		} else {
			return $pp;
		}
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
			$div_rec = GameConstant::getParam("PlusREC");
			$rand = mt_rand(1, ($div_hp + $div_atk + $div_rec));
			if($div_hp >= $rand) {
				$pp->hp = 1;
			} elseif (($div_hp + $div_atk) >= $rand) {
				$pp->atk = 1;
			} else {
				$pp->rec = 1;
			}
		}
		return $pp;
	}

}
