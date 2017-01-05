<?php
/**
 * ダンジョン潜入シミュレータ4
 */
class AdminSneakDungeon4 extends AdminBaseAction
{
	// ダンジョン系クラス名
	private $dungeon_class			= "Dungeon";
	private $dungeon_floor_class	= "DungeonFloor";
	private $wave_class				= "Wave";
	private $user_wave_class		= "UserWave";
	private $wave_monster_class		= "WaveMonster";

	const TARGET_DUNGEON_NORMAL		= 0;
	const TARGET_DUNGEON_RANKING	= 1;

	const OUTPUT_CSV_NONE		= 0;
	const OUTPUT_CSV_ADD		= 1;
	const OUTPUT_CSV_ONLY		= 2;

	public function action($params)
	{
// 		//INFO:処理に時間がかかるのでローカル専用機能にするべきかも
// 		if(Env::ENV != 'padclocal') {
// 			$result = array(
// 				'format' => 'array',
// 				'ローカル環境専用機能です' => array(
// 					'format' => 'html',
// 					'',
// 				),
// 			);
// 			return json_encode($result);
// 		}

		$start_time = microtime(true);

		$target_dungeon = $params['target_dungeon'];// 対象ダンジョン
		$dungeonFloorIds = ($target_dungeon == self::TARGET_DUNGEON_NORMAL) ? $params['dfid'] : $params['rank_dfid'];// ダンジョンフロアID
		$checkCnt		= (int)$params['cnt'];// 試行回数
		$use_ticket = $params['use_ticket'];// 周回チケット利用
		$output_csv = isset($params['output_csv']) ? $params['output_csv'] : self::OUTPUT_CSV_NONE;// CSV出力
		$csv_text = "";

		$maxCnt = 1000;
		if(Env::ENV == 'padclocal')
		{
			// ローカル環境の場合、試行回数上限を多めに設定できるようにする。
			$maxCnt = 1000;
		}

		// 試行回数が過剰な数字の場合すり切る
		if($checkCnt > $maxCnt)
		{
			$checkCnt = $maxCnt;
		}

		// ランキングダンジョン用クラス名に変更
		if ($target_dungeon == self::TARGET_DUNGEON_RANKING) {
			$this->dungeon_class = "RankingDungeon";
			$this->dungeon_floor_class = "RankingDungeonFloor";
			$this->wave_class = "RankingWave";
			$this->user_wave_class = "UserRankingWave";
			$this->wave_monster_class = "RankingWaveMonster";
		}

		$dungeon_class = $this->dungeon_class;
		$dungeon_floor_class = $this->dungeon_floor_class;
		$wave_class = $this->wave_class;
		$wave_monster_class = $this->wave_monster_class;

		// 必要なテーブルをIDをキーにした配列にして取得
		$get_dungeon_floors	= $dungeon_floor_class::getAll();
		$dungeon_floors = array();
		foreach($get_dungeon_floors as $df) {
			$dungeon_floors[$df->id] = $df;
		}

		$get_dungeons	= $dungeon_class::getAll();
		$dungeons = array();
		foreach($get_dungeons as $d) {
			$dungeons[$d->id] = $d;
		}

		$get_pieces	= Piece::getAll();
		$pieces = array();
		foreach($get_pieces as $p) {
			$pieces[$p->id] = $p;
		}

		// ダンジョンフロア名取得
		$dungeon = new $dungeon_class();
		$dungeonNames = self::getNamesByDao($dungeon);

		// ダンジョンフロア名取得
		$dungeonFloor = new $dungeon_floor_class();
		$dungeonFloorNames = self::getNamesByDao($dungeonFloor);

		// モンスター名取得
		$card = new Card();
		$cardNames = self::getNamesByDao($card);

		// 欠片名取得
		$piece = new Piece();
		$pieceNames = self::getNamesByDao($piece);

		$result = array(
				'format' => 'array',
				'実行結果' => array(
						'format' => 'array',
						array('ダンジョンフロア数', '各ループ回数'),
						array(count($dungeonFloorIds), $checkCnt),
				),
		);

		$dungeon_waves = $wave_class::debugGetWaves($dungeonFloorIds);
		foreach($dungeonFloorIds as $dungeon_floor_id) {

			$total_exp = 0;
			$total_coin = 0;
			$total_round = 0;
			$get_pieces = array();
			$drop_counts = array();
			$wave_monsters = array();
			$_wave_monsters = array();

			$dungeon_floor	= $dungeon_floors[$dungeon_floor_id];
			$dungeon		= $dungeons[$dungeon_floor->dungeon_id];
			//$waves			= $wave_class::getAllBy(array("dungeon_floor_id" => $dungeon_floor_id), "seq ASC");
			$waves			= $dungeon_waves[$dungeon_floor_id];

			if ($target_dungeon == self::TARGET_DUNGEON_NORMAL) {
				// 通常ダンジョン
				$active_bonuses = LimitedBonus::getActiveForSneakDungeon($dungeon, $dungeon_floor);
				// ユーザ別ボーナスはいったん無視
				//		$active_bonuses_group = LimitedBonusGroup::getActiveForSneakDungeon($user, $dungeon);
				$active_bonuses_group = array();
				$active_bonuses_open_dungeon = LimitedBonusOpenDungeon::getActiveForSneakDungeon($dungeon);
				$active_bonuses_dungeon_bonus = LimitedBonusDungeonBonus::getActiveForSneakDungeon($dungeon);

				$active_bonuses = array_merge($active_bonuses, $active_bonuses_group);
				$active_bonuses = array_merge($active_bonuses, $active_bonuses_open_dungeon);
				$active_bonuses = array_merge($active_bonuses, $active_bonuses_dungeon_bonus);

				$dungeon_bonus = LimitedBonus::getActiveForDungeonForClient($dungeon, $active_bonuses);
			}
			else {
				// ランキングダンジョンは時間限定ボーナスデータは無し
				$active_bonuses = array();
				$dungeon_bonus = array();
			}

			// 指定された回数分繰り返す
			for ($i=0; $i < $checkCnt; $i++) {
				set_time_limit(30);

				$get_exp = 0;
				$get_coin = 0;
				$beat_bonuses = null;

				// Waveを決定. (new UserWave()内で出現モンスター判定などなされる.)
				$user_waves = array();
				// ノトーリアスモンスターの出現をダンジョン中1回だけにする
				$notorious_chance_wave = 0;
				if(count($waves) > 1){
					$notorious_chance_wave = mt_rand(1, count($waves) - 1);  // ボスウェーブには登場しない
				}
				$wave_count = 0;
				foreach($waves as $wave) {
					$wave_count++;
					if($wave_count == $notorious_chance_wave) {
						$notorious_chance = TRUE;
					} else {
						$notorious_chance = false;
					}
					// ノートリアスモンスターが出現する場合、欠片ドロップ率を書き換えてしまうためクローンを渡す
					$clone_wave = clone $wave;
					$user_wave_class = $this->user_wave_class;
					$user_waves[] = new $user_wave_class($dungeon_floor, $clone_wave, $active_bonuses, $dungeon_bonus, $notorious_chance);

					// 出現する可能性のあるモンスターをあらかじめ選出する
					$_wave_monsters = $wave_monster_class::getAllBy(array("wave_id" => $wave->id), "prob DESC, id ASC");
					foreach($_wave_monsters as $_wave_monster) {
						$cid = (int)$_wave_monster->card_id;
						$mlv = (int)$_wave_monster->lv;
						if (isset($wave_monsters[$cid])) {
							if (!isset($wave_monsters[$cid][$mlv])) {
								$wave_monsters[$cid][$mlv] = 0;
							}
						}
						else {
							$wave_monsters[$cid][$mlv] = 0;
						}
					}

					// ノートリアスモンスターも出現可能性があるモンスターとして追加
					$notorious_bonus = LimitedBonus::getActiveNotoriousBonus($dungeon_floor_id, $active_bonuses);
					if($notorious_bonus) {
						$cid = (int)$notorious_bonus->target_id;
						$mlv = (int)$notorious_bonus->amount;
						if (isset($wave_monsters[$cid])) {
							if (!isset($wave_monsters[$cid][$mlv])) {
								$wave_monsters[$cid][$mlv] = 0;
							}
						}
						else {
							$wave_monsters[$cid][$mlv] = 0;
						}
					}
				}

				// ダンジョン情報ドロップ無チェック(ダンジョンデータの”追加フロア情報”が512で割り切れたらドロップ無し)
				if(($dungeon_floor->eflag & Dungeon::NONE_DROP) != Dungeon::NONE_DROP){
					list($beat_bonuses, $get_exp, $get_coin) =
					UserWave::getEncodedBeatBonusesAndExpAndCoin($user_waves, 1.0);
				}else{
					$beat_bonuses = json_encode(array());
				}

				$total_exp += $get_exp;
				$total_coin += $get_coin;

				$beat_bonuses_array = json_decode($beat_bonuses);
				foreach($beat_bonuses_array as $beat_bonus) {
					if($beat_bonus->item_id == BaseBonus::PIECE_ID) {
						$piece_id = $beat_bonus->piece_id;
						$piece_num = $beat_bonus->amount;
						if (array_key_exists($piece_id, $get_pieces)) {
							$get_pieces[$piece_id] += $piece_num;
						}
						else {
							$get_pieces[$piece_id] = $piece_num;
						}
						if (array_key_exists($piece_id, $drop_counts)) {
							$drop_counts[$piece_id] += 1;
						}
						else {
							$drop_counts[$piece_id] = 1;
						}
					}
					else if($beat_bonus->item_id == BaseBonus::COIN_ID) {
						$total_coin += $beat_bonus->amount;
					}
					else if($beat_bonus->item_id == BaseBonus::ROUND_ID) {
						$total_round += $beat_bonus->amount;
					}
				}

				if ($use_ticket) {
					// 周回クリアによる追加ボーナス
					$round_bonus = null;
					foreach($user_waves as $wave) {
						$round_bonus = $wave->getBeatBonus($dungeon->id);
						if ($round_bonus) {
							break;
						}
					}
					if (!$round_bonus) {
						// ドロップが無かった場合は強化の欠片1個をボーナスとする
						$round_bonus = new BeatBonus();
						$round_bonus->setPiece(Piece::PIECE_ID_STRENGTH, 1);
					}

					if($round_bonus->item_id == BaseBonus::COIN_ID){
						$total_coin += $round_bonus->amount;
					}
					else if($round_bonus->item_id == BaseBonus::PIECE_ID){
						$piece_id = $round_bonus->piece_id;
						$piece_num = $round_bonus->amount;
						if (array_key_exists($piece_id, $get_pieces)) {
							$get_pieces[$piece_id] += $piece_num;
						}
						else {
							$get_pieces[$piece_id] = $piece_num;
						}
						if (array_key_exists($piece_id, $drop_counts)) {
							$drop_counts[$piece_id] += 1;
						}
						else {
							$drop_counts[$piece_id] = 1;
						}
					}
					else if($round_bonus->item_id == BaseBonus::ROUND_ID){
						$total_round += $round_bonus->amount;
					}
				}

				// 出現モンスター集計
				foreach($user_waves as $user_wave) {
					foreach($user_wave->user_wave_monsters as $user_wave_monster) {
						// モンスター種類
						$cid = (int)$user_wave_monster->wave_monster->card_id;
						$mlv = (int)$user_wave_monster->level;
						if (isset($wave_monsters[$cid])) {
							if (isset($wave_monsters[$cid][$mlv])) {
								$wave_monsters[$cid][$mlv] += 1;
							}
							else {
								$wave_monsters[$cid][$mlv] = 1;
							}
						}
						else {
							$wave_monsters[$cid][$mlv] = 1;
						}
					}
				}
			}

			$dungeon_name = self::getNameFromArray($dungeon->id,$dungeonNames);
			$dungeon_floor_name = self::getNameFromArray($dungeon_floor_id,$dungeonFloorNames);

			$avg_exp = round(floatval($total_exp)/$checkCnt, 2);
			$avg_coin = round(floatval($total_coin)/$checkCnt, 2);
			$avg_round = round(floatval($total_round)/$checkCnt, 2);

			if ($output_csv != self::OUTPUT_CSV_ONLY) {
				$result[$dungeon_name.' '.$dungeon_floor_name] = array(
						'format' => 'array',
						'table1' => array(
								array('', '合計', '平均'),
								array('EXP', $total_exp, $avg_exp),
								array('コイン', $total_coin, $avg_coin),
								array('周回チケット', $total_round, $avg_round),
						),
				);
			}

			if ($output_csv != self::OUTPUT_CSV_NONE) {
				//$csv_text = $dungeon->id.','.$dungeon->name.','.$dungeon_floor_id.','.$dungeon_floor->name.','.$total_exp.','.$total_coin.','.$total_round.','.round(floatval($total_exp)/$checkCnt, 2);
				//$csv_text = "$dungeon->id,$dungeon->name,$dungeon_floor_id,$dungeon_floor->name,$total_exp,$total_coin,$total_round,$avg_exp,$avg_coin,$avg_round";
				$csv_text .= "$dungeon->id,$dungeon->name,$dungeon_floor_id,$dungeon_floor->name,$total_exp,$total_coin,$total_round,$avg_exp,$avg_coin,$avg_round<br>";
			}

			if (count($get_pieces) > 0) {
				if ($output_csv != self::OUTPUT_CSV_ONLY) {
					$result[$dungeon_name.' '.$dungeon_floor_name]['table2'][] = array('欠片', 'DROP回数', '総DROP数', '1個あたりの経験値量', 'DROP数に紐付く経験値量', '1個あたりの進化経験値量', 'DROP数に進化経験値量');
				}
				ksort($get_pieces);
				foreach($get_pieces as $_piece_id => $_piece_num)
				{
					$mexp = $pieces[$_piece_id]->mexp;
					$eexp = $pieces[$_piece_id]->eexp;
					$total_mexp = $mexp * $_piece_num;
					$total_eexp = $eexp * $_piece_num;
					$drop_count = isset($drop_counts[$_piece_id]) ? $drop_counts[$_piece_id] : 0;
					if ($output_csv != self::OUTPUT_CSV_ONLY) {
						$result[$dungeon_name.' '.$dungeon_floor_name]['table2'][] = array(
								self::getNameFromArray($_piece_id, $pieceNames),
								$drop_count,
								$_piece_num,
								$mexp,
								$total_mexp,
								$eexp,
								$total_eexp,
						);
					}
					if ($output_csv != self::OUTPUT_CSV_NONE) {
						$csv_text .= "$dungeon->id,$dungeon->name,$dungeon_floor_id,$dungeon_floor->name,$_piece_id,$pieceNames[$_piece_id],$drop_count,$_piece_num,$mexp,$total_mexp,$eexp,$total_eexp<br>";
					}
				}
			}

			if ($output_csv != self::OUTPUT_CSV_ONLY) {
				$result[$dungeon_name.' '.$dungeon_floor_name]['table3'][] = array('モンスター', 'LV', '総出現数');
			}
			ksort($wave_monsters);
			foreach($wave_monsters as $cid => $wm) {
				foreach($wm as $lv => $cnt) {
					if ($output_csv != self::OUTPUT_CSV_ONLY) {
						$result[$dungeon_name.' '.$dungeon_floor_name]['table3'][] = array(
								self::getNameFromArray($cid, $cardNames),
								$lv,
								$cnt,
						);
					}
					if ($output_csv != self::OUTPUT_CSV_NONE) {
						$csv_text .= "$dungeon->id,$dungeon->name,$dungeon_floor_id,$dungeon_floor->name,$cid,$cardNames[$cid],$lv,$cnt<br>";
					}
				}
			}
		}

		if ($output_csv != self::OUTPUT_CSV_NONE) {
			$csv_key = 'ダンジョンID、ダンジョン名、フロアID、フロア名、合計EXP、合計コイン、合計周回チケット、平均EXP、平均コイン、平均周回チケット<br>';
			$csv_key .= 'ダンジョンID、ダンジョン名、フロアID、フロア名、欠片ID、欠片名、DROP回数、総DROP数、1個あたりの経験値量、DROP数に紐付く経験値量、1個あたりの進化経験値量、DROP数に進化経験値量<br>';
			$csv_key .= 'ダンジョンID、ダンジョン名、フロアID、フロア名、モンスターID、モンスター名、LV、総出現数<br>';
			$result['CSV'] = array(
					'内容' => $csv_key,
					'csv' => $csv_text,
			);
		}

		$end_time = microtime(true);
		$result['実行時間:'.round($end_time - $start_time, 3).'秒'] = array('format' => 'array');
		return json_encode($result);
	}

	function getWaves($dungeonFloorIds) {
		$wave_class = $this->wave_class;
		$waves = $wave_class::getWaves($dungeonFloorIds);
		$ret = array();
		foreach($waves as $_wave) {
			$ret[$_wave->dungeon_floor_id][] = $_wave;
		}
		return $ret;
	}

}
