<?php
/**
 * Admin用：助っ人冒険者ユーザー確認
 */
class AdminCheckRecommendedHelper extends AdminBaseAction {
	public function action($params) {

		$level1 = (isset($params['rank1']) && $params['rank1']) ? $params['rank1'] : 0;
		$level2 = (isset($params['rank2']) && $params['rank2']) ? $params['rank2'] : 0;

		$level_min = min($level1, $level2);
		if ($level_min < 0) {
			$level_min = 0;
		}

		$level_max = max($level1, $level2);
		if ($level_max - $level_min > 100) {
			$level_max = $level_min + 100;
		}

		$helper_ids_array = array();//ユーザーID配列
		for($level=$level_min;$level<=$level_max;$level++){
			$key = RedisCacheKey::getRecommendedHelperLevelKey($level);
			$redis = Env::getRedis(Env::REDIS_POINT);
			$helper_ids = $redis->lRange($key, 0, RecommendedHelperUtil::HELPER_TRIM);
			if(!$helper_ids) {
				continue;
			}
			// 重複ユーザ削除.
			$helper_ids_array[$level] = array_unique($helper_ids);
		}

		$result = array(
				'format' => 'array',
				'確認範囲ランク（クリアダンジョン数）' => array(
					'確認範囲（最少）'			=> $level_min,
					'確認範囲（最大）'			=> $level_max,
				)
		);
		foreach ($helper_ids_array as $level => $helper_ids) {
			$helper_array = array(
					'format' => 'array',
					array('ユーザーID', 'ユーザー名', 'ランク（クリアダンジョン数）', )
			);
			foreach ($helper_ids as $helper_id) {
				$user = User::find($helper_id);
				if($user == false) {
					continue;
				}
				// #PADC_DY# ----------begin----------
				// $helper_array[] = array($helper_id, $user->name, $user->clear_dungeon_cnt);
				$helper_array[] = array($helper_id, $user->name, $user->lv);
				// #PADC_DY# ----------end----------
			}
			$result['ランク'.$level] = $helper_array;
		}

		return json_encode ( $result );
	}

}
