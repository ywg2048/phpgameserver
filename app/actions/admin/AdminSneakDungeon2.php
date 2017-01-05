<?php
/**
 * ダンジョン潜入シミュレータ2
 * ※ 機能拡張した4があるので使用していません
 */
class AdminSneakDungeon2 extends AdminBaseAction
{
	// ダンジョン系クラス名
	private $dungeon_class			= "Dungeon";
	private $dungeon_floor_class	= "DungeonFloor";
	private $wave_class				= "Wave";
	private $user_wave_class		= "UserWave";

	const TARGET_DUNGEON_NORMAL		= 0;
	const TARGET_DUNGEON_RANKING	= 1;

	public function action($params)
	{
		$target_dungeon = $params['target_dungeon'];// 対象ダンジョン
		$dungeonFloorId = ($target_dungeon == self::TARGET_DUNGEON_NORMAL) ? $params['dfid'] : $params['rank_dfid'];// ダンジョンフロアID
		$checkCnt		= (int)$params['cnt'];// 試行回数
		$use_ticket = $params['use_ticket'];// 周回チケット利用

		$maxCnt = 100;
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
		}

		$dungeon_class = $this->dungeon_class;
		$dungeon_floor_class = $this->dungeon_floor_class;
		$wave_class = $this->wave_class;

		// ダンジョン&フロア抽出.
		$dungeon_floor	= $dungeon_floor_class::findBy(array('id' => $dungeonFloorId,));
		$dungeon		= $dungeon_class::findBy(array('id' => $dungeon_floor->dungeon_id,));
		$waves = $wave_class::getAllBy(array("dungeon_floor_id" => $dungeon_floor->id), "seq ASC");

		$total_exp = 0;
		$total_coin = 0;
		$total_round = 0;
		$get_pieces = array();
		$total_round_bonus = array();

		if($dungeon && $dungeon_floor) {

			if ($target_dungeon == self::TARGET_DUNGEON_NORMAL) {
				// 通常ダンジョン
				$active_bonuses = LimitedBonus::getActiveForSneakDungeon($dungeon, $dungeon_floor);

				// グループ別時間限定ボーナスは考慮しない
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
					}
					else if($round_bonus->item_id == BaseBonus::ROUND_ID){
						$total_round += $round_bonus->amount;
					}
				}

			}

		}

		// ダンジョンフロア名取得
		$dungeonFloor = new $dungeon_floor_class();
		$dungeonFloorNames = self::getNamesByDao($dungeonFloor);

		// 欠片名取得
		$piece = new Piece();
		$pieceNames = self::getNamesByDao($piece);

		// 結果表示用整形
		$tmpPiece	= array(
			'format' => 'array',
			array('欠片ID','入手数'),
		);
		ksort($get_pieces);
		foreach($get_pieces as $_piece_id => $_piece_num)
		{
			$tmpPiece[] = array(self::getNameFromArray($_piece_id, $pieceNames),$_piece_num);
		}
		$get_pieces = $tmpPiece;

		$result = array(
			'format' => 'array',
			'ダンジョンフロア' => array(
				'format' => 'array',
				array('ダンジョンフロア情報','試行回数'),
				array(self::getNameFromArray($dungeonFloorId,$dungeonFloorNames),$checkCnt),
			),
			'結果' => array(
				'EXP' => $total_exp,
				'コイン' => $total_coin,
				'周回チケット' => $total_round,
			),
			'入手欠片' => $get_pieces,
		);
		return json_encode($result);
	}
}
