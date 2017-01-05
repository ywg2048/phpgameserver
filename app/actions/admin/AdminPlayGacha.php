<?php
/**
 * Admin用：ガチャシミュレータ
 */
class AdminPlayGacha extends AdminBaseAction
{
	/**
	 * @see AdminBaseAction::action()
	 */
	public function action($params)
	{
		$gacha_type = isset($params['gacha_type']) ? $params['gacha_type'] : Gacha::TYPE_FRIEND;
		$gacha_id	= isset($params['gacha_id']) ? $params['gacha_id'] : 0;
		$gacha_cnt	= isset($params['cnt']) ? $params['cnt'] : 1;

		$maxCnt = 100000;
		// 試行回数が過剰な数字の場合すり切る
		if($gacha_cnt > $maxCnt)
		{
			$gacha_cnt = $maxCnt;
		}

		if($gacha_type == Gacha::TYPE_EXTRA)
		{
			$params = array(
				'id' => $gacha_id,
			);
			$extraGacha = new ExtraGacha();
			$extra_gacha = $extraGacha->findBy($params);
			if($extra_gacha === null || $extra_gacha == false)
			{
				throw new PadException(RespCode::UNKNOWN_ERROR,'target extra gacha is not found!');
			}
		}
		
		// コスト計算
		$cost = '';
		if($gacha_type == Gacha::TYPE_FRIEND) {
			$cost = '友情ポイント:' . (GameConstant::getParam("FriendGachaPrice") * $gacha_cnt);
		}
		else if($gacha_type == Gacha::TYPE_EXTRA) {
			if($extra_gacha->gacha_type == ExtraGacha::TYPE_FRIEND){
				$cost = '友情ポイント:' . ($extra_gacha->price * $gacha_cnt);
			}
			else if($extra_gacha->gacha_type == ExtraGacha::TYPE_CHARGE){
				$cost = '魔法石:' . ($extra_gacha->price * $gacha_cnt);
			}
		}
		else if($gacha_type == Gacha::TYPE_PREMIUM) {
			$cost = '魔法石:' . (Gacha::COST_MAGIC_STONE_PREMIUM * $gacha_cnt);
		}
		else if($gacha_type == Gacha::TYPE_TUTORIAL) {
			$cost = '魔法石:' . (Gacha::COST_MAGIC_STONE_PREMIUM * $gacha_cnt);;
		}
		
		// 欠片名取得
		$piece = new Piece();
		$pieceNames = self::getNamesByDao($piece);

		$gacha_result = array('format' => 'array');
		$gacha_result[] = array(
			'実行回数',
			'レア度',
			'欠片ID',
			'生成前入手数',
			'生成済み入手数',
			'入手LV',
			'最小LV',
			'最大LV',
			'入手の重み',
		);

		$time_start = microtime(true);
		$loop_check = 100;
		while($gacha_cnt / $loop_check > 100) {
			$loop_check *= 10;
		}
		
		$total_gacha_nums = array();// 入手回数をセットする配列
		for($i=0;$i<$gacha_cnt;$i++)
		{
			set_time_limit(30);
// 			if ($i < 10 || $i % $loop_check == 0) {
// 				Padc_Log_Log::writeLog('memory_get_usage : ' . memory_get_usage(true));
// 				Padc_Log_Log::writeLog('memory_get_peak_usage : '. memory_get_peak_usage(true));
// 				$time_end = microtime(true);
// 				Padc_Log_Log::writeLog("mark${i}_1 : " . sprintf("%.9f",$time_end - $time_start));
// 				$time_start = $time_end;
// 			}
				
			if($gacha_type == Gacha::TYPE_EXTRA)
			{
				$gacha_prize = Gacha::takeGachaPrize($gacha_type, $extra_gacha->gacha_id);
			}
			else
			{
				$gacha_prize = Gacha::takeGachaPrize($gacha_type);
			}

// 			if ($i < 10 || $i % $loop_check == 0) {
// 				$time_end = microtime(true);
// 				Padc_Log_Log::writeLog("mark${i}_2 : " . sprintf("%.9f",$time_end - $time_start));
// 				$time_start = $time_end;
// 			}
			// 結果は最大100件まで
			if ($i < 100) {
				$gacha_result[] = array(
						$i+1,
						$gacha_prize->rare,
						self::getNameFromArray($gacha_prize->piece_id,$pieceNames),
						$gacha_prize->piece_num,
						$gacha_prize->piece_num2,
						$gacha_prize->getLevel(),
						$gacha_prize->min_level,
						$gacha_prize->max_level,
						$gacha_prize->prob,
				);
			}

// 			if ($i < 10 || $i % $loop_check == 0) {
// 				$time_end = microtime(true);
// 				Padc_Log_Log::writeLog("mark${i}_3 : " . sprintf("%.9f",$time_end - $time_start));
// 				$time_start = $time_end;
// 			}
			// 入手回数をセット
			if(isset($total_gacha_nums[$gacha_prize->piece_id]))
			{
				$total_gacha_nums[$gacha_prize->piece_id]++;
			}
			else
			{
				$total_gacha_nums[$gacha_prize->piece_id] = 1;
			}
// 			if ($i < 10 || $i % $loop_check == 0) {
// 				$time_end = microtime(true);
// 				Padc_Log_Log::writeLog("mark${i}_4 : " . sprintf("%.9f",$time_end - $time_start));
// 				$time_start = $time_end;
// 			}
		}
		
// 		$time_end = microtime(true);
// 		Padc_Log_Log::writeLog('loop end : ' . sprintf("%.9f",$time_end - $time_start));
// 		$time_start = $time_end;
		
		// 総計をセット
		$gacha_total = array('format' => 'array');
		$gacha_total[] = array(
				'欠片ID',
				'入手回数',
				'入手確率',
				'設定確率',
		);
		
		if($gacha_type == Gacha::TYPE_EXTRA)
		{
			$gacha_prizes = Gacha::getGachaPrizes($gacha_type, $extra_gacha->gacha_id);
		}
		else
		{
			$gacha_prizes = Gacha::getGachaPrizes($gacha_type);
		}
		$gacha_id = $gacha_prizes[0]->gacha_id;
		$sum_prob = GachaPrize::getSumProbByGachaId($gacha_id);
		
		foreach($gacha_prizes as $gacha_prize) {
			$_piece_id = $gacha_prize->piece_id;
			$_cnt = isset($total_gacha_nums[$_piece_id]) ? $total_gacha_nums[$_piece_id]: 0;
			$gacha_total[] = array(
					self::getNameFromArray($_piece_id,$pieceNames),
					$_cnt,
					number_format($_cnt * 100 / $gacha_cnt, 3) . '%',
					number_format($gacha_prize->prob * 100 / $sum_prob, 3) . '%',
			);
		}
		
		// ガチャタイプを取得
		$gachaTypes = self::getGachaType();

		$result = array(
			'format'	=> 'array',
			'ガチャタイプ'	=> array(
				'format' => 'array',
				array('タイプ','試行回数','コスト','確率母数'),
				array(self::getNameFromArray($gacha_type,$gachaTypes),$gacha_cnt,$cost,$sum_prob),
			),
			'実行結果(最初の100件まで)'	=> $gacha_result,
			'総計'		=> $gacha_total,
		);
		
		return json_encode ($result);
	}

	static private function getGachaType()
	{
		$types = array(
			Gacha::TYPE_FRIEND	=> '友情ガチャ',
			Gacha::TYPE_CHARGE	=> 'レアガチャ',
			Gacha::TYPE_EXTRA	=> '追加ガチャ',
			Gacha::TYPE_PREMIUM	=> 'プレミアムガチャ',
			Gacha::TYPE_TUTORIAL	=> 'チュートリアルガチャ',
		);
		return $types;
	}
}
