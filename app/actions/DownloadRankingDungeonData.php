<?php
/**
 * #PADC#
 * ランキングダンジョンデータダウンロード
 */
class DownloadRankingDungeonData extends BaseAction {
	
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	const FORMAT_VERSION = 1002;
	// #PADC#
  	const MAIL_RESPONSE = FALSE;
	public function action($params){
		$key = MasterCacheKey::getDownloadRankingDungeonData();
		// $value = apc_fetch($key);
		$value = FALSE;
		if(FALSE === $value) {
			$value = self::arrangeColumns(RankingDungeon::getAll());
			apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
		}
		return json_encode(array('res' => RespCode::SUCCESS, 'v' => self::FORMAT_VERSION, 'dungeons' => $value));
	}

	/**
	 * Dungeonのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
	 */
	public static function arrangeColumns($dungeons) {
		$mapper = array();
		foreach($dungeons as $dungeon) {
			$arr = array();
			$arr['i'] = (int)$dungeon->id;
			$arr['n'] = $dungeon->name;
			$arr['a'] = (int)$dungeon->attr;
			$arr['t'] = (int)$dungeon->dtype;
			$arr['k'] = (int)$dungeon->dkind;
			$arr['w'] = (int)$dungeon->dwday;
			$arr['padc_sf'] = $dungeon->share_file;
			// if((int)$dungeon->dsort != ($dungeon->id * 100)){
			// 	$arr['s'] = (int)$dungeon->dsort;
			// }
			$arr['f'] = array();
			$floors = $dungeon->getFloors();
			foreach($floors as $floor) {
				$f = array();
				$f['i'] = (int)$floor->seq;
				$f['n'] = $floor->name;
				$f['s'] = (int)$floor->sta;
				$f['w'] = (int)$floor->waves;
				$f['e'] = (int)$floor->ext;
				if($floor->sr != 0) {
					$f['sr'] = (int)$floor->sr;
				}
				if($floor->bgm1 != 0) {
					$f['b1'] = (int)$floor->bgm1;
				}
				if($floor->bgm2 != 0) {
					$f['b2'] = (int)$floor->bgm2;
				}
				if($floor->eflag != 0) {
					$f['f'] = (int)$floor->eflag;
				}
				if($floor->fr != 0) {
					$f['r'] = (int)$floor->fr;
				}
				if($floor->fr1 != 0) {
					$f['r1'] = (int)$floor->fr1;
				}
				if($floor->fr2 != 0) {
					$f['r2'] = (int)$floor->fr2;
				}
				if($floor->fr3 != 0) {
					$f['r3'] = (int)$floor->fr3;
				}
				if($floor->fr4 != 0) {
					$f['r4'] = (int)$floor->fr4;
				}
				if($floor->fr5 != 0) {
					$f['r5'] = (int)$floor->fr5;
				}
				if($floor->fr6 != 0) {
					$f['r6'] = (int)$floor->fr6;
				}
				if($floor->fr7 != 0) {
					$f['r7'] = (int)$floor->fr7;
				}
				if($floor->fr8 != 0) {
					$f['r8'] = (int)$floor->fr8;
				}
				if($floor->prev_dungeon_floor_id != 0){
					$f['d'] = ($floor->prev_dungeon_floor_id - ($floor->prev_dungeon_floor_id % 1000)) / 1000;
					$f['l'] = $floor->prev_dungeon_floor_id % 1000;
				}
				if ($floor->open_rank != 0) {
					$f['or'] = (int)$floor->open_rank;
				}

				if($floor->open_clear_dungeon_cnt != 0)
				{
					$f['padc_odcnt'] = (int)$floor->open_clear_dungeon_cnt;
				}
				$waves = RankingWave::getAllBy(array('dungeon_floor_id' => $floor->id));
				$padc_cids = array();
				foreach($waves as $wave)
				{
					if($wave->egg_prob > 0)
					{
						$wave_monsters = RankingWaveMonster::getAllBy(array('wave_id' => $wave->id));
						foreach($wave_monsters as $wave_monster)
						{
							if($wave_monster->drop_min > 0)
							{
								// $card = Card::get($wave_monster->card_id);
								$padc_cids[] = (int)$wave_monster->card_id;
							}	
						}
					}
				}
				$padc_cids = array_unique($padc_cids);
				$padc_cids = array_values($padc_cids);
				if(empty($padc_cids) == false)
				{
					$f = array_merge($f,array('padc_cids' => $padc_cids));	
				}
				$f['dmpt'] = (int) $floor->daily_max_times; // #PADC_DY# 单日最大次数
                $floor_recovery_gold = GameConstant::getParam('FloorRecoveryGold');
                $f['dmrt'] = count($floor_recovery_gold); // #PADC_DY# 单日最大恢复次数
                $f['drrg'] = $floor_recovery_gold; // #PADC_DY# 每次回复需要魔法石数量
                $f['padc_tp'] = (int) $floor->total_power; // 総合戦闘力
				$arr['f'][] = $f;
			}
			$plus_drop = RankingDungeonPlusDrop::find($dungeon->id);
			$plus = 0;
			if($plus_drop)
			{
				if($plus_drop->drop_prob > 0)
				{
					$plus = 1;
				}
			}
			$arr['padc_p'] = $plus;
			$mapper[] = $arr;
		}
		return $mapper;
	}
}
