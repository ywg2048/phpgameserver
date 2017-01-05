<?php
/**
 * ユーザが挑むウェーブ. (このモデルの単位では保存はしない)
 */

class UserWave {
	public $wave = null;
	public $active_bonuses = null;
	public $dungeon_bonus = null;
	public $user_wave_monsters = null;
	public $beat_bonus = null;
	public $sum_exp = null;
	public $sum_coin = null;
	public $sum_width = null;
	public $cnt_pop_monsters = null;
	private $notorious_flag = null;

	// 出現モンスターのサイズ累計上限.
	const MONSTER_WIDTH_MAX = 620;

	// #PADC# ----------begin----------
	const WAVE_MONSTER_CLASS = 'WaveMonster';
	const USER_WAVE_MONSTER_CLASS = 'UserWaveMonster';
	// #PADC# ----------end----------

	/**
	 * #PADC#
	 * 引数に $prob_round と $prob_plus を追加
	 * デバッグ管理ツール用にDROPを周回チケットとプラスの欠片に変更する判定処理を追加
	 */
	public function __construct($df, $w, $bs, $db, $notorious_chance, $prob_round = null, $prob_plus = null) {
		$this->wave = $w;
		$dungeon_floor = $df;
		if(is_array($bs)) {
			foreach($bs as $b) {
				if(($b instanceof LimitedBonus) === FALSE) {
					throw new PadException(RespCode::UNKNOWN_ERROR, "active bonuses' type are invalid.");
				}
			}
			$this->active_bonuses = $bs;
		} else {
			throw new PadException(RespCode::UNKNOWN_ERROR, "active bonuses' type are invalid.");
		}
		if($db) {
			if(($db instanceof LimitedBonus) === FALSE || $db->dungeon_id != $dungeon_floor->dungeon_id) {
				throw new PadException(RespCode::UNKNOWN_ERROR, "dungeon bonus is not for this wave.");
			}
		}
		$this->dungeon_bonus = $db;

		$this->selectMonsters($notorious_chance);

		// ダンジョン情報ドロップ無チェック(ダンジョンデータの”追加フロア情報”が512で割り切れたらドロップ無し)
		if(($dungeon_floor->eflag & Dungeon::NONE_DROP) != Dungeon::NONE_DROP){
			// #PADC# ----------begin----------
			// $prob_round と $prob_plus が定義されていなければ通常処理のまま
			if ($prob_round === null || $prob_plus === null) {
				$this->setBeatBonus($dungeon_floor->dungeon_id);
			}
			else {
				$this->debugSetBeatBonus($dungeon_floor->dungeon_id, $prob_round, $prob_plus);
			}
			// #PADC# ----------end----------
		}
	}

	/**
	 * UserWaveのリストを走査し、すべてのウェーブをクリアした際の
	 * BeatBonusのリスト(json)と獲得経験値のリストを返す.
	 * 2012/10/22 コイン入手アップスキル対応 akamiya.
	 */
	public static function getEncodedBeatBonusesAndExpAndCoin($user_waves, $coeff_coin) {
		$all_beat_bonuses = array();
		$all_exp = 0;
		$all_coin = 0;
		$drop_coin = 0;
		foreach($user_waves as $user_wave) {
			$beat_bonus = $user_wave->beat_bonus;
			if($beat_bonus) {
				if($beat_bonus->item_id == BaseBonus::COIN_ID){
					$beat_bonus->amount = round($beat_bonus->amount * $coeff_coin);
					$user_wave->beat_bonus = $beat_bonus;
				}
				$all_beat_bonuses[] = $beat_bonus;
			}
			$user_wave->setSumExpAndCoinCoeff($coeff_coin);
			$all_exp += $user_wave->sum_exp;
			$all_coin += $user_wave->sum_coin;// * $coeff_coin;
		}
		return array(json_encode($all_beat_bonuses), $all_exp, $all_coin);
	}

// 	/**
// 	 * 卵をドロップしたときに限りTRUEを返す.
// 	 */
// 	public function checkDropEgg() {
// 		$prob = $this->wave->egg_prob;
// 		// 時限ボーナス適用.
// 		if($this->dungeon_bonus && $this->dungeon_bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_EGGUP) {
// 			$prob = round($prob * $this->dungeon_bonus->args / 10000.0);
// 		}
// 		if(count($this->user_wave_monsters) > 0 && mt_rand(1, 10000) <= $prob) {
// 			return TRUE;
// 		}
// 		return FALSE;
// 	}
	/**
	 * #PADC#
	 * カケラをドロップしたときに限りTRUEを返す.
	 */
	public function checkDropPiece() {
		$prob = $this->wave->egg_prob;
		// 時限ボーナス適用.
		if($this->dungeon_bonus && $this->dungeon_bonus->bonus_type == LimitedBonus::BONUS_TYPE_DUNG_EGGUP) {
			$prob = round($prob * $this->dungeon_bonus->args / 10000.0);
		}
		if(count($this->user_wave_monsters) > 0 && mt_rand(1, 10000) <= $prob) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 宝箱を発見したと判定されたときに限りTRUEを返す.
	 */
	public function checkFindTreasure() {
		if(count($this->user_wave_monsters) > 0 && mt_rand(1, 10000) <= $this->wave->tre_prob) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * ノトーリアスモンスターが発生したと判定されたときに限りTRUEを返す.
	 * ボスウェーブでは無条件で出現しない.
	 */
	public function checkPopNotorious($notorious_bonus) {
		if(!$this->wave->boss && $notorious_bonus && $notorious_bonus->bonus_type == LimitedBonus::BONUS_TYPE_FLOOR_NOTORIOUS && mt_rand(1, 10000) <= $notorious_bonus->args) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * ウェーブに出現させるモンスターを決定し、さらに出現レベルを決定する.
	 */
	private function selectMonsters($notorious_chance) {
		$this->user_wave_monsters = array();
		// ノトーリアス出現判定.ダンジョン中に１回のみノトーリアスチェックする
		if($this->active_bonuses && $notorious_chance) {
			$notorious_bonus = LimitedBonus::getActiveNotoriousBonus($this->wave->dungeon_floor_id, $this->active_bonuses);
			if($this->checkPopNotorious($notorious_bonus)) {
				// ノトーリアス出現.
				// #PADC# 継承先で参照クラスを変更するため、WaveMonsterを動的クラスに変更。
				$wave_monster_class = static::WAVE_MONSTER_CLASS;
				$wm = new $wave_monster_class();
				$wm->wave_id = $this->wave->id;
				$wm->card_id = $notorious_bonus->target_id;
				$wm->lv = $notorious_bonus->amount;
				$wm->lv_rnd = 0;
				$wm->prob = 10000;
				$wm->boss = 2;	// ノトーリアス
				// #PADC# ----------begin----------
				// 欠片のドロップ最大、最少値
				$wm->id = $notorious_bonus->id;
				$wm->drop_min = $notorious_bonus->drop_min;
				$wm->drop_max = $notorious_bonus->drop_max;
				// #PADC# ----------end----------
				$this->notorious_flag = TRUE;
				// #PADC# 継承先で参照クラスを変更するため、UserWaveMonsterを動的クラスに変更。
				$user_wave_monster_class = static::USER_WAVE_MONSTER_CLASS;
				$this->user_wave_monsters[] = new $user_wave_monster_class($wm, $this->dungeon_bonus);
				//ノトーリアスの卵ドロップ率適用（上書き）
				if(isset($notorious_bonus->nm_eggprob)) {
					$this->wave->egg_prob = (int)($notorious_bonus->nm_eggprob);
				}
				// ノトーリアスが出現した場合は他のモンスターは出現しない.
				return;
			}
		}

		// ノトーリアス出現無し. サイズと出現数制限内で、出現モンスターを決定する.
		$this->sum_width = 0;
		$this->cnt_pop_monsters = 0;

		// 通常のモンスター出現設定.
		// 100%出現モンスター確定.
		$wave_monsters = $this->setFixedWaveMonsters();

		// 確率設定済みモンスター出現判定.
		$wave_monsters = $this->setHasProbWaveMonsters($wave_monsters);

		// 確率未設定モンスター出現判定.
		$this->setNoneProbWavemonsters($wave_monsters);
	}

	/**
	 * 自身のwaveに属する100%出現モンスターを出現状態として確定し、
	 * 残りの(100%指定以外の)wave_monstersを返す.
	 */
	private function setFixedWaveMonsters() {
		global $logger;
		// 全ての候補モンスターを取得.
		// 処理の簡単のため出現確率降順, id昇順で取得すること.
		// #PADC# 継承先で参照クラスを変更するため、WaveMonsterを動的クラスに変更。
		$wave_monster_class = static::WAVE_MONSTER_CLASS;
		$wave_monsters = $wave_monster_class::getAllBy(array("wave_id" => $this->wave->id), "prob DESC, id ASC");

		// 100%出現モンスターをセットし、候補リストから取り除く.
		foreach($wave_monsters as $wave_monster) {
			if($wave_monster->prob >= 10000) {
				$this->sum_width += $wave_monster->getMonsterWidth();
				if($this->sum_width <= UserWave::MONSTER_WIDTH_MAX) {
					// #PADC# 継承先で参照クラスを変更するため、UserWaveMonsterを動的クラスに変更。
					$user_wave_monster_class = static::USER_WAVE_MONSTER_CLASS;
					$this->user_wave_monsters[] = new $user_wave_monster_class($wave_monster, $this->dungeon_bonus);
					$this->cnt_pop_monsters++;
				} else {
					// サイズ超過. (データ規定上、ここにくることはないはず)
					$logger->log(("DATA ERROR: sum of 100% monster size is over limit."), Zend_Log::WARN);
					break;
				}
			} else {
				break;
			}
		}

		for($i=0; $i<$this->cnt_pop_monsters; $i++) {
			array_shift($wave_monsters);
		}

		return $wave_monsters;
	}

	/**
	 * 指定されたwave_monstersリストから、確率設定済みのモンスターの出現判定をおこない、
	 * 出現有無を決定する. 残り(確率設定が無い)のwave_monstersリストを返す.
	 */
	private function setHasProbWaveMonsters($wave_monsters) {
		// 最大モンスター数に達するもしくはサイズ上限を超えるまで確率設定済みのモンスター出現判定をする.
		while($this->sum_width <= UserWave::MONSTER_WIDTH_MAX && count($wave_monsters) > 0 && $this->cnt_pop_monsters < $this->wave->mons_max) {
			$wave_monster = $wave_monsters[0];
			if(isset($wave_monster->prob) && $wave_monster->prob > 0) {
				$wave_monster = array_shift($wave_monsters);
				$seed = mt_rand(1, 10000);
				if($seed <= $wave_monster->prob) {
					$this->sum_width += $wave_monster->getMonsterWidth();
					if($this->sum_width <= UserWave::MONSTER_WIDTH_MAX) {
						// #PADC# 継承先で参照クラスを変更するため、UserWaveMonsterを動的クラスに変更。
						$user_wave_monster_class = static::USER_WAVE_MONSTER_CLASS;
						$this->user_wave_monsters[] = new $user_wave_monster_class($wave_monster, $this->dungeon_bonus);
						$this->cnt_pop_monsters++;
					} else {
						// サイズ超過.
						break;
					}
				}
			} else {
				break;
			}
		}
		return $wave_monsters;
	}

	/**
	 * 指定された確率未設定wave_monstersリストから、ランダムに出現判定をする.
	 */
	private function setNoneProbWaveMonsters($wave_monsters) {
		// 最大モンスター数に達するもしくはサイズ上限を超えるまで確率未設定のモンスター出現判定をする.
		if(count($wave_monsters) > 0) {
			while($this->sum_width <= UserWave::MONSTER_WIDTH_MAX && $this->cnt_pop_monsters < $this->wave->mons_max) {
				// この時点でwave_monstersに残っているのは、確率未設定モンスターのみ.
				$wave_monster = $wave_monsters[mt_rand(0, count($wave_monsters) - 1)];
				$this->sum_width += $wave_monster->getMonsterWidth();
				if($this->sum_width <= UserWave::MONSTER_WIDTH_MAX) {
					// #PADC# 継承先で参照クラスを変更するため、UserWaveMonsterを動的クラスに変更。
					$user_wave_monster_class = static::USER_WAVE_MONSTER_CLASS;
					$this->user_wave_monsters[] = new $user_wave_monster_class($wave_monster, $this->dungeon_bonus);
					$this->cnt_pop_monsters++;
				} else {
					// サイズ超過.
					break;
				}
			}
		}
	}

	/**
	 * ランダムで一体にのみボーナスをセットする.
	 */
	private function setBeatBonus($dungeon_id) {
		// #PADC# ----------begin----------
		if($this->checkDropPiece()) {
			// カケラドロップ. ランダムで一体にカケラボーナスを付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			$this->beat_bonus = $user_wave_monster->setDropPieceBonus($dungeon_id);
		} else if($this->checkFindTreasure()) {
			// 宝箱発見. ランダムで一体に宝箱ボーナスを付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			$this->beat_bonus = $user_wave_monster->setTreasureBonus();
		}
		// #PADC# ----------end----------
	}

	/**
	 * ウェーブに出現するすべてのモンスターを倒した際の獲得経験値とコインをセットする.
	 */
	private function setSumExpAndCoinCoeff($coeff_coin) {
		$this->sum_exp = 0;
		$this->sum_coin = 0;
		foreach($this->user_wave_monsters as $user_wave_monster) {
			$this->sum_exp += $user_wave_monster->exp;
			// リーダースキルコインアップ対応.
			$this->sum_coin += round($user_wave_monster->coin * $coeff_coin);
		}
	}

	/**
	 * #PADC#
	 * 周回クリア用のドロップボーナスを取得する
	 * setBeatBonus()と同処理、beat_bonusにはセットされず戻り値として返す
	 */
	public function getBeatBonus($dungeon_id) {
		if($this->checkDropPiece()) {
			// カケラドロップ. ランダムで一体にカケラボーナスを付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			return $user_wave_monster->setDropPieceBonus($dungeon_id);
		} else if($this->checkFindTreasure()) {
			// 宝箱発見. ランダムで一体に宝箱ボーナスを付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			return $user_wave_monster->setTreasureBonus();
		}
		return null;
	}

	/**
	 * #PADC#
	 * デバッグユーザー用処理
	 * ランダムで一体にのみボーナスをセットする.
	 * ドロップを周回チケット、プラス欠片にするか判定を行う
	 * どちらも外れた場合は通常のドロップ設定処理を呼ぶ
	 */
	private function debugSetBeatBonus($dungeon_id, $prob_round, $prob_plus) {
		if(mt_rand(1, 10000) <= $prob_round) {
			// ランダムで一体に周回チケットを付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			$this->beat_bonus = $user_wave_monster->debugSetDropRoundTicket();
		}
		else if(mt_rand(1, 10000) <= $prob_plus) {
			// ランダムで一体にプラスの欠片を付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			$this->beat_bonus = $user_wave_monster->debugSetDropPlusPiece();
		}
		else {
			// 確率から外れた場合は通常のドロップ処理を行う
			$this->setBeatBonus($dungeon_id);
		}
	}

	/**
	 * #PADC#
	 * デバッグユーザー用処理
	 * 周回クリア用のドロップボーナスを取得する
	 * debugSetBeatBonus()と同処理、beat_bonusにはセットされず戻り値として返す
	 * ドロップを周回チケット、プラス欠片にするか判定を行う
	 * どちらも外れた場合は通常のドロップ設定処理を呼ぶ
	 */
	public function debugGetBeatBonus($dungeon_id, $prob_round, $prob_plus) {
		if(mt_rand(1, 10000) <= $prob_round) {
			// ランダムで一体に周回チケットを付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			return $user_wave_monster->debugSetDropRoundTicket();
		}
		else if(mt_rand(1, 10000) <= $prob_plus) {
			// ランダムで一体にプラスの欠片を付与.
			$user_wave_monster = $this->user_wave_monsters[mt_rand(0, count($this->user_wave_monsters)-1)];
			return $user_wave_monster->debugSetDropPlusPiece();
		}
		else {
			// 確率から外れた場合は通常のドロップ処理を行う
			return $this->getBeatBonus($dungeon_id);
		}
	}
}
