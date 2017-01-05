<?php
/**
 * ユーザがウェーブで挑む出現モンスター. (このモデル単位ではDB保存しない)
 */

class UserWaveMonster {
	public $wave_monster = null;
	public $dungeon_bonus = null;
	public $level = null;
	public $exp = null;
	public $coin = null;
	public $beat_bonus = null;

	// #PADC# ----------begin----------
	const WAVE_CLASS = 'Wave';
	const DUNGEON_FLOOR_CLASS = 'DungeonFloor';
	const TREASURE_CLASS = 'Treasure';
	// #PADC# ----------end----------

	public function __construct($wm, $db) {
		$this->wave_monster = $wm;
		if($db) {
			// #PADC# 継承先でアクセスするDBを変更するため、Waveクラスを動的クラスに変更。
			$wave_class = static::WAVE_CLASS;
			$wave = $wave_class::get($this->wave_monster->wave_id);
			// #PADC# 継承先でアクセスするDBを変更するため、DungeonFloorクラスを動的クラスに変更。
			$dungeon_floor_class = static::DUNGEON_FLOOR_CLASS;
			$dungeon_floor = $dungeon_floor_class::get($wave->dungeon_floor_id);
			if($dungeon_floor->dungeon_id != $db->dungeon_id) {
				throw new PadException(RespCode::UNKNOWN_ERROR, "specified bonus is not for the dungeon includes this wave_monster.");
			}
		}
		$this->dungeon_bonus = $db;
		$this->setLevelAndAward();
	}

	/**
	 * 宝箱ボーナスをセットする.
	 * セットしたBeatBonusを返す.
	 */
	public function setTreasureBonus() {
		// #PADC# 継承先でアクセスするDBを変更するため、Waveクラスを動的クラスに変更。
		$wave_class = static::WAVE_CLASS;
		$wave = $wave_class::get($this->wave_monster->wave_id);
		// #PADC# 継承先でアクセスするDBをへ脳するため、Treasureクラスを動的クラスに変更。
		$treasure_class = static::TREASURE_CLASS;
		$treasures = $treasure_class::getAllBy(array("dungeon_floor_id" => $wave->dungeon_floor_id));
		if(empty($treasures)) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "data error: undefined treasures for the dungeon_floor(ID=" . $wave->dungeon_floor_id . ").");
		}
		$got_treasure = null;
		// どの宝箱を獲得したか判定.
		$seed = mt_rand(1, 10000);
		$sum = 0;
		foreach($treasures as $treasure) {
			$sum += $treasure->prob;
			if($seed <= $sum) {
				$got_treasure = $treasure;
				break;
			}
		}
		// 宝箱にも時限ボーナスを反映. https://61.215.220.70/redmine-pad/issues/1776
		$coeff_coin = 10000;
		if($this->dungeon_bonus) {
			if($this->dungeon_bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_COINUP) {
				// 時限ボーナスの係数を取り出す.
				$coeff_coin = $this->dungeon_bonus->args;
			}
		}

		// 宝箱がコインとそれ以外で数を変更する
		$b = new BeatBonus();
		$b->item_id = (int)$got_treasure->award_id;
		if ($b->item_id == BaseBonus::COIN_ID) {
			$b->amount = ($got_treasure->amount * $coeff_coin / 10000.0);
		}
		else {
			$b->amount = (int)$got_treasure->amount;
		}
		// $b->wave_monster = $this->wave_monster;
		$this->beat_bonus = $b;
		return $b;
	}

// 	/**
// 	 * 卵ドロップボーナスをセットする.
// 	 * ただし、このモンスターにドロップするデータがなければ無効.
// 	 * 取得時レベルは討伐したモンスターと同等. (amountにセット)
// 	 * セットしたBeatBonusを返す.
// 	 */
// 	public function setDropEggBonus($dungeon_id) {
// 		$card = Card::get($this->wave_monster->card_id);
// 		$seed = mt_rand(1, 10000);
// 		$b = new BeatBonus();
// 		// DBに保存する必要がなさそうなので、wave_monsterは保存しない.
// 		// $b->wave_monster = $this->wave_monster;
// 		if($seed <= $card->drop_prob1) {
// 			$b->item_id = $card->drop_card_id1;
// 		} else if($seed <= $card->drop_prob1 + $card->drop_prob2) {
// 			$b->item_id = $card->drop_card_id2;
// 		} else if($seed <= $card->drop_prob1 + $card->drop_prob2 + $card->drop_prob3) {
// 			$b->item_id = $card->drop_card_id3;
// 		} else {
// 			$b->item_id = $card->drop_card_id4;
// 		}
// 		if($b->item_id !== null) {
// 			// 以下の種類の場合は、「＋」値を付けない https://61.215.220.70/redmine-pad/issues/764
// 			$arr_outside_plus_egg = array(
// 				Card::MONSTER_TYPE_EVOLUTION, // 00:進化用モンスター
// 				Card::MONSTER_TYPE_FEED, // 14:強化合成用モンスター
// 				Card::MONSTER_TYPE_MONEY, // 15:換金用モンスター
// 			);
// 			$bcard = Card::get($b->item_id);
// 			if(!in_array($bcard->mt, $arr_outside_plus_egg)) {
// 				// 卵＋値を取得
// 				$plus_egg = PlusEgg::getPlusParam(PlusEgg::DUNGEON, $dungeon_id);
// 				$b->plus_hp = $plus_egg->hp;
// 				$b->plus_atk = $plus_egg->atk;
// 				$b->plus_rec = $plus_egg->rec;
// 			} else {
// 				$b->plus_hp = 0;
// 				$b->plus_atk = 0;
// 				$b->plus_rec = 0;
// 			}
// 			if($this->level > $bcard->mlv){
// 				// 討伐したモンスターのレベルより、取得したモンスターの最大レベルが低い場合は最大レベルをセット.
// 				$b->amount = $bcard->mlv;
// 			} else{
// 				$b->amount = $this->level;
// 			}
// 			$this->beat_bonus = $b;
// 			return $b;
// 		} else {
// 			return null;
// 		}
// 	}

	/**
	 * #PADC#
	 * カケラドロップボーナスをセットする.
	 * セットしたBeatBonusを返す.
	 */
	public function setDropPieceBonus($dungeon_id) {
		$piece_id = 0;
		$amount = 0;
		// ノートリアスモンスターの場合はプラスの欠片判定を行わない
		if ($this->wave_monster->boss != 2) {
			$plus_piece = PlusPiece::getPlusParam(PlusPiece::DUNGEON, $dungeon_id);
			if ($plus_piece->hp > 0) {
				$piece_id = Piece::PIECE_ID_PLUS_HP;
				$amount = 1;
			}
			else if ($plus_piece->atk > 0) {
				$piece_id = Piece::PIECE_ID_PLUS_ATK;
				$amount = 1;
			}
			else if ($plus_piece->rec > 0) {
				$piece_id = Piece::PIECE_ID_PLUS_REC;
				$amount = 1;
			}
		}

		if($piece_id == 0) {
			$card = Card::get($this->wave_monster->card_id);
			$seed = mt_rand(1, 10000);
			if($seed <= $card->drop_prob1) {
				$piece_id = (int)$card->drop_card_id1;
			} else if($seed <= $card->drop_prob1 + $card->drop_prob2) {
				$piece_id = (int)$card->drop_card_id2;
			} else if($seed <= $card->drop_prob1 + $card->drop_prob2 + $card->drop_prob3) {
				$piece_id = (int)$card->drop_card_id3;
			} else {
				$piece_id = (int)$card->drop_card_id4;
			}
			$amount = mt_rand($this->wave_monster->drop_min, $this->wave_monster->drop_max);
			// 最低でも1個ドロップ
			if ($amount == 0) {
				$amount = 1;
			}
		}

		if($piece_id != 0 && $amount != 0) {
			$b = new BeatBonus();
			$b->setPiece($piece_id, $amount);
			$this->beat_bonus = $b;
			return $b;
		}
		else {
			return null;
		}
	}

	/**
	 * 出現レベル、獲得経験値、コインを決定する.
	 */
	private function setLevelAndAward() {
		$this->level = $this->wave_monster->lv + mt_rand(0, $this->wave_monster->lv_rnd);

		// 時限ボーナスの係数を取り出す.
		$coeff_exp = 10000;
		$coeff_coin = 10000;
		if($this->dungeon_bonus) {
			if($this->dungeon_bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_EXPUP) {
				$coeff_exp = $this->dungeon_bonus->args;
			} else if($this->dungeon_bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_COINUP) {
				$coeff_coin = $this->dungeon_bonus->args;
			}
		}

		// ゲーム定数を適用する.
		$coeff_exp = $coeff_exp * GameConstant::getParam("BeatExpLevel");
		$coeff_coin = $coeff_coin * GameConstant::getParam("BeatCoinLevel");

		$card = Card::get($this->wave_monster->card_id);
		$this->exp = round($card->expk * $this->level * $coeff_exp / 10000.0);
		$this->coin = round($card->coink * $this->level * $coeff_coin / 10000.0);
	}

	/**
	 * #PADC#
	 * ドロップボーナスにプラスの欠片をセットする.
	 * セットしたBeatBonusを返す.
	 */
	public function debugSetDropPlusPiece() {

		$div_hp = GameConstant::getParam("PlusHP");
		$div_atk = GameConstant::getParam("PlusATK");
		$div_rec = GameConstant::getParam("PlusREC");
		$rand = mt_rand(1, ($div_hp + $div_atk + $div_rec));
		if($div_hp >= $rand) {
			$piece_id = Piece::PIECE_ID_PLUS_HP;
		} elseif (($div_hp + $div_atk) >= $rand) {
			$piece_id = Piece::PIECE_ID_PLUS_ATK;
		} else {
			$piece_id = Piece::PIECE_ID_PLUS_REC;
		}

		$b = new BeatBonus();
		$b->setPiece($piece_id, 1);
		$this->beat_bonus = $b;
		return $b;
	}

	/**
	 * #PADC#
	 * ドロップボーナスに周回チケットをセットする.
	 * セットしたBeatBonusを返す.
	 */
	public function debugSetDropRoundTicket() {
		$b = new BeatBonus();
		$b->item_id = BaseBonus::ROUND_ID;
		$b->amount = 1;
		$this->beat_bonus = $b;
		return $b;
	}
}
