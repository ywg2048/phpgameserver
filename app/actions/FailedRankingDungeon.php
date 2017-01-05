<?php
/**
 * #PADC#
 * ランキングダンジョン潜入失敗
 */
class FailedRankingDungeon extends BaseAction {
	public function action($params) {
		$user_id = $params ["pid"];
		$pdo = Env::getDbConnectionForUserRead ( $user_id );
		$user = User::find ( $user_id, $pdo );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found' );
		}
		
		// ハッシュキーの存在チェック.
		if(!array_key_exists("hash", $params)) {
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}
		
		// ユーザーダンジョンの存在チェック.
		$dungeon_id = $params ['dung'];
		$dungeon_floor_id = $params ['floor'] + $dungeon_id * 1000;
		$user_dungeon = UserRankingDungeon::findBy( array(
				"user_id" => $user_id,
				'dungeon_id' => $dungeon_id,
				'dungeon_floor_id' => $dungeon_floor_id,
		), $pdo);
		if (! $user_dungeon || ! $user_dungeon->stamina_spent_at || $user_dungeon->hash != $params["hash"]) {
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}
		
		$dungeon = RankingDungeon::get($dungeon_id);
		if(!$dungeon) {
			// 該当のダンジョンが存在しない.
			return json_encode(array('res' => RespCode::UNKNOWN_ERROR));
		}
		
		// get sneak time
		$sneakTime = $user_dungeon->stamina_spent_at;
		

		// 終了方式
		$end_type = -1;
		if ($params["etype"] == 1) {
			$end_type = 1;//失敗
		}
		else if ($params["etype"] == 2) {
			$end_type = 3;//途中やめる
		}
		
		// クライアントバージョン文字列
		$app_verision = isset($params['appv']) ? $params['appv'] : 'unknown';
		$app_revision = isset($params['appr']) ? $params['appr'] : 'unknown';
		$client_verision = $app_verision . '_' . $app_revision;

		if(isset($decode_params['end_flow'])){
			$end_flow_params = json_decode($decode_params['end_flow'],true);
			if(empty($end_flow_params['waves'])){
				$waves_count_detail =
					array(
						'rps'   => 0,
						'rset'  => null,
						'rsost' => null,
						'rsoet' => null,
						'rbe'   => null,
						'rab'   => null,
						'rstc'  => null,
						'rtct'  => 0,
						'rac'   => 0,
						'rgs'   => 0,
						'rcpmax'=> 0,
						'rcpmin'=> 0,
					);
			}else{
				$round_pass_stage    = count($end_flow_params['waves']);   //本局通过的波数
				$round_average_combo = $end_flow_params['avgcmb'];         //平均combo
				$round_combo_percent_max = $end_flow_params['cmbenmax'];   //combo的最大系数
				$round_combo_percent_min = $end_flow_params['cmbenmin'];   //combo的最小系数
				$round_get_score     = $end_flow_params['score'];          //本局获得的分数
				$round_turn_count_total  = $end_flow_params['turns'];      //本局累计转珠次数

				$t_waves    = $end_flow_params['waves'];
				$round_stage_enemy_time     = '';   //每波敌人出现的时间
				$round_operation_start_time = '';   //每波首次转珠的时间
				$round_operation_end_time   = '';   //每次最后转珠的时间
				$round_stage_turn_count     = '';   //每波的转珠次数
				$details = array();
				foreach($t_waves as $t_wave){
					$round_stage_turn_count .= count($t_wave['detail']).',';

					$enemy_time = $t_wave['monsin'];
					$round_stage_enemy_time .= $enemy_time.',';

					$turn_begin = $t_wave['turn0'];
					$round_operation_start_time .= $turn_begin.',';

					$turn_end   = $t_wave['turnz'];
					$round_operation_end_time .= $turn_end.',';

					foreach($t_wave['detail'] as $detail){
						$details[] = $detail;
					}
				}

				$round_beat_exchange = '';   //每次转珠，珠子交换的个数
				$round_attack_base   = '';   //每次转珠的基础伤害，不算加成
				foreach($details as $detail){
					$round_beat_exchange .= $detail['len'].',';
					$round_attack_base   .= $detail['dmg'].',';
				}

				$waves_count_detail =
					array(
						'rps'   => $round_pass_stage,
						'rset'  => trim($round_stage_enemy_time,','),
						'rsost' => trim($round_operation_start_time,','),
						'rsoet' => trim($round_operation_end_time,','),
						'rbe'   => trim($round_beat_exchange,','),
						'rab'   => trim($round_attack_base,','),
						'rstc'  => trim($round_stage_turn_count,','),
						'rtct'  => $round_turn_count_total,
						'rac'   => $round_average_combo,
						'rgs'   => $round_get_score,
						'rcpmax'=> $round_combo_percent_max,
						'rcpmin'=> $round_combo_percent_min,
					);
			}
		}else{
			$waves_count_detail =
				array(
					'rps'   => 0,
					'rset'  => null,
					'rsost' => null,
					'rsoet' => null,
					'rbe'   => null,
					'rab'   => null,
					'rstc'  => null,
					'rtct'  => 0,
					'rac'   => 0,
					'rgs'   => 0,
					'rcpmax'=> 0,
					'rcpmin'=> 0,
				);
		}

		// Tlog送信
		UserTlog::sendTlogFailedSneak ( $user, $dungeon_id, $sneakTime );
		UserTlog::sendTlogSecRoundEndFlow_Failed($end_type, $user, $dungeon, $user_dungeon, $this->decode_params, $client_verision,$waves_count_detail);
		
		$res = array (
				'res' => RespCode::SUCCESS 
		);
		return json_encode ( $res );
	}
}
