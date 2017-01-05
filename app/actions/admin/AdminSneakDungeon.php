<?php
/**
 * 8. ダンジョン潜入シミュレータ
 */
class AdminSneakDungeon extends AdminBaseAction
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

		$dungeon_floor	= $dungeon_floor_class::findBy(array('id' => $dungeonFloorId,));
		$dungeon		= $dungeon_class::findBy(array('id' => $dungeon_floor->dungeon_id,));
		$waves			= $wave_class::getAllBy(array("dungeon_floor_id" => $dungeonFloorId), "seq ASC");

		if ($target_dungeon == self::TARGET_DUNGEON_NORMAL) {
			// 通常ダンジョン
			$active_bonuses = LimitedBonus::getActiveForSneakDungeon($dungeon, $dungeon_floor);

			// ユーザ別ボーナスはいったん無視
			//$active_bonuses_group = LimitedBonusGroup::getActiveForSneakDungeon($user, $dungeon);
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

		// ダンジョンフロア名取得
		$dungeonFloor = new $dungeon_floor_class();
		$dungeonFloorNames = self::getNamesByDao($dungeonFloor);

		$waveInfos = array();
		for($i=0;$i<$checkCnt;$i++)
		{
			$waveInfos[] = self::getWaveInfo($dungeon,$dungeon_floor,$waves,$active_bonuses,$dungeon_bonus,$use_ticket);
		}

		$result = array(
			'format' => 'array',
			'ダンジョンフロア' => array(
				'format' => 'array',
				array('ダンジョンフロア情報','試行回数'),
				array(self::getNameFromArray($dungeonFloorId,$dungeonFloorNames),$checkCnt),
			),
		);

		foreach($waveInfos as $key => $waveInfo)
		{
			$result['試行回数：'.$key] = $waveInfo;
		}

		return json_encode($result);
	}

	/**
	 * ウェーブ詳細取得
	 * @param unknown $dungeon
	 * @param unknown $dungeon_floor
	 * @param unknown $waves
	 * @param unknown $active_bonuses
	 * @param unknown $dungeon_bonus
	 */
	function getWaveInfo($dungeon,$dungeon_floor,$waves,$active_bonuses,$dungeon_bonus,$use_ticket)
	{
		// Waveを決定. (new UserWave()内で出現モンスター判定などなされる.)
		// 10個あるキャッシュからランダムで取得し、なければその場で計算. #186
//		$key = CacheKey::getUserWavesKey($dungeon->id, $dungeon_floor->id, mt_rand(1, 10));
//		$memcache = Env::getMemcache();
//		$user_waves = $memcache->get($key);
		$user_waves = false;
		if($user_waves === FALSE)
		{
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
//			$memcache->set($key, $user_waves, 20); // 20秒キャッシュ.
		}

		/*
		object(UserWave)#39 (10)
		{
			["wave"]=> object(Wave)#11 (9)
			{
				["id"]=> string(7) "1338027"
				["dungeon_floor_id"]=> string(4) "1001"
				["seq"]=> string(1) "1"
				["mons_max"]=> string(1) "1"
				["egg_prob"]=> string(1) "0"
				["tre_prob"]=> string(1) "0"
				["boss"]=> string(1) "0"
				["created_at"]=> string(19) "0000-00-00 00:00:00"
				["updated_at"]=> string(19) "0000-00-00 00:00:00"
			}
			["active_bonuses"]=> array(0)
			{
			}
			["dungeon_bonus"]=> NULL
			["user_wave_monsters"]=> array(1)
			{
				[0]=> object(UserWaveMonster)#35 (6)
				{
					["wave_monster"]=> object(WaveMonster)#38 (11)
					{
							["id"]=> string(7) "9688518"
							["wave_id"]=> string(7) "1338027"
							["card_id"]=> string(2) "36"
							["lv"]=> string(1) "1"
							["lv_rnd"]=> string(1) "0"
							["prob"]=> string(5) "10000"
							["boss"]=> string(1) "0"
							["drop_min"]=> string(1) "3"
							["drop_max"]=> string(1) "3"
							["created_at"]=> string(19) "0000-00-00 00:00:00"
							["updated_at"]=> string(19) "0000-00-00 00:00:00"
					}
					["dungeon_bonus"]=> NULL
					["level"]=> int(1)
					["exp"]=> float(4)
					["coin"]=> float(4)
					["beat_bonus"]=> NULL
				}
			}
			["beat_bonus"]=> NULL
			["sum_exp"]=> NULL
			["sum_coin"]=> NULL
			["sum_width"]=> int(80)
			["cnt_pop_monsters"]=> int(1)
			["notorious_flag":"UserWave":private]=> NULL
		}
			object(UserWave)#34 (10){ ["wave"]=> object(Wave)#16 (9) {
			["id"]=> string(7) "1338028" ["dungeon_floor_id"]=> string(4) "1001" ["seq"]=> string(1) "2" ["mons_max"]=> string(1) "1"
			["egg_prob"]=> string(1) "0" ["tre_prob"]=> string(1) "0" ["boss"]=> string(1) "0"
			["created_at"]=> string(19) "0000-00-00 00:00:00" ["updated_at"]=> string(19) "0000-00-00 00:00:00" }
			["active_bonuses"]=> array(0) { }
			["dungeon_bonus"]=> NULL
			["user_wave_monsters"]=> array(1) {
			[0]=> object(UserWaveMonster)#32 (6) {
			["wave_monster"]=> object(WaveMonster)#33 (11) {
			["id"]=> string(7) "9688519" ["wave_id"]=> string(7) "1338028" ["card_id"]=> string(2) "38" ["lv"]=> string(1) "1" ["lv_rnd"]=> string(1) "0" ["prob"]=> string(5) "10000" ["boss"]=> string(1) "0" ["drop_min"]=> string(1) "3" ["drop_max"]=> string(1) "3" ["created_at"]=> string(19) "0000-00-00 00:00:00" ["updated_at"]=> string(19) "0000-00-00 00:00:00" } ["dungeon_bonus"]=> NULL ["level"]=> int(1) ["exp"]=> float(4) ["coin"]=> float(4) ["beat_bonus"]=> NULL } } ["beat_bonus"]=> NULL ["sum_exp"]=> NULL ["sum_coin"]=> NULL ["sum_width"]=> int(80) ["cnt_pop_monsters"]=> int(1) ["notorious_flag":"UserWave":private]=> NULL }

			object(UserWave)#31 (10){ ["wave"]=> object(Wave)#12 (9) {
			["id"]=> string(7) "1338029" ["dungeon_floor_id"]=> string(4) "1001" ["seq"]=> string(1) "3" ["mons_max"]=> string(1) "1"
			["egg_prob"]=> string(1) "0" ["tre_prob"]=> string(1) "0" ["boss"]=> string(1) "1"
			["created_at"]=> string(19) "0000-00-00 00:00:00" ["updated_at"]=> string(19) "0000-00-00 00:00:00" }
			["active_bonuses"]=> array(0) { }
			["dungeon_bonus"]=> NULL
			["user_wave_monsters"]=> array(1) {
			[0]=> object(UserWaveMonster)#29 (6) {
			["wave_monster"]=> object(WaveMonster)#30 (11) {
			["id"]=> string(7) "9688520" ["wave_id"]=> string(7) "1338029" ["card_id"]=> string(2) "40" ["lv"]=> string(1) "1" ["lv_rnd"]=> string(1) "0" ["prob"]=> string(5) "10000" ["boss"]=> string(1) "1" ["drop_min"]=> string(1) "3" ["drop_max"]=> string(1) "3" ["created_at"]=> string(19) "0000-00-00 00:00:00" ["updated_at"]=> string(19) "0000-00-00 00:00:00" } ["dungeon_bonus"]=> NULL ["level"]=> int(1) ["exp"]=> float(4) ["coin"]=> float(4) ["beat_bonus"]=> NULL } } ["beat_bonus"]=> NULL ["sum_exp"]=> NULL ["sum_coin"]=> NULL ["sum_width"]=> int(80) ["cnt_pop_monsters"]=> int(1) ["notorious_flag":"UserWave":private]=> NULL }
		*/

		$waveInfo = array();
		$waveInfo[] = array(
			'ウェーブID',
			'フロアNo',
			'モンスター数',
			'欠片DROP率',
			'宝箱DROP率',
			'ボス',
			'ボーナス',
			'ダンジョンボーナス',
			'ウェーブモンスター',
		);

		// モンスター名取得
		$card = new Card();
		$cardNames = self::getNamesByDao($card);

		// 欠片名取得
		$piece = new Piece();
		$pieceNames = self::getNamesByDao($piece);

		foreach($user_waves as $_data)
		{
			$wave = $_data->wave;

			$tmpMonsterInfo = array();
			$tmpMonsterInfo[] = array(
				'id',
				'モンスター',
				'lv',
				'lv_rnd',
				'prob',
				'boss',
				'DROP欠片Min',
				'DROP欠片Max',
				'DROP',
			);

			foreach($_data->user_wave_monsters as $_wave_monsters)
			{
				$_wave_monster = $_wave_monsters->wave_monster;
				/*
				 * object(WaveMonster)#40 (11) {
				 * ["id"]=> string(7) "9688565"
				 * ["wave_id"]=> string(7) "1338048"
				 * ["card_id"]=> string(2) "36"
				 * ["lv"]=> string(1) "1"
				 * ["lv_rnd"]=> string(1) "0"
				 * ["prob"]=> string(4) "3000"
				 * ["boss"]=> string(1) "0"
				 * ["drop_min"]=> string(1) "1"
				 * ["drop_max"]=> string(1) "3"
				 * ["created_at"]=> string(19) "0000-00-00 00:00:00"
				 * ["updated_at"]=> string(19) "0000-00-00 00:00:00" }
				 */

				$drop = "-";

				$beat_bonus = $_wave_monsters->beat_bonus;
				if($beat_bonus) {
					if($beat_bonus->item_id == BaseBonus::COIN_ID){
						$drop = $beat_bonus->amount."コイン";
					}
					else if($beat_bonus->item_id == BaseBonus::PIECE_ID){
						$drop = self::getNameFromArray($beat_bonus->piece_id, $pieceNames) . $beat_bonus->amount . "個";
					}
					else if($beat_bonus->item_id == BaseBonus::ROUND_ID){
						$drop = "周回チケット ".$beat_bonus->amount."枚";
					}
				}

				$_tmpArray = array(
					$_wave_monster->id,
					self::getNameFromArray($_wave_monster->card_id,$cardNames),
					$_wave_monster->lv,
					$_wave_monster->lv_rnd,
					$_wave_monster->prob,
					$_wave_monster->boss,
					$_wave_monster->drop_min,
					$_wave_monster->drop_max,
					$drop,
				);
				$tmpMonsterInfo[] = $_tmpArray;
			}

			$waveInfo[] = array(
				'id'					=> $wave->id,
				'seq'					=> $wave->seq,
				'mons_max'				=> $wave->mons_max,
				'egg_prob'				=> $wave->egg_prob,
				'tre_prob'				=> $wave->tre_prob,
				'boss'					=> $wave->boss,
				'active_bonuses'		=> $_data->active_bonuses,
				'dungeon_bonus'			=> ($_data->dungeon_bonus) ? array($_data->dungeon_bonus) : $_data->dungeon_bonus,
				'user_wave_monsters'	=> $tmpMonsterInfo,//$_data->user_wave_monsters,
			);
		}
		$ret = array(
			'format' => 'array',
			'table1' => $waveInfo,
		);

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

			$drop = "-";
			if($round_bonus->item_id == BaseBonus::COIN_ID){
				$drop = $round_bonus->amount."コイン";
			}
			else if($round_bonus->item_id == BaseBonus::PIECE_ID){
				$drop = self::getNameFromArray($round_bonus->piece_id, $pieceNames) . $round_bonus->amount . "個";
			}
			else if($round_bonus->item_id == BaseBonus::ROUND_ID){
				$drop = "周回チケット ".$round_bonus->amount."枚";
			}

			$ret['table2'] = array(
				array("項目", "詳細"),
				array("周回ボーナス", $drop),
			);
		}

		return $ret;
	}

}
