<?php
/**
 * モンスター討伐時のボーナス.
 */
class BeatBonus extends BaseBonus {
	// DB保存の必要がないため、wave_monsterは削除.
	// 復活させる場合は、UserWaveMonster#setDropEggBonus()も確認のこと.
	//public $wave_monster;

	// #PADC# ----------begin----------
	public function setCoin($num) {
		$this->item_id = BaseBonus::COIN_ID;
		$this->amount = $num;
	}
	public function setPiece($id, $num) {
		$this->item_id = BaseBonus::PIECE_ID;
		$this->piece_id = $id;
		$this->amount = $num;
	}
	// #PADC# ----------end----------
}
