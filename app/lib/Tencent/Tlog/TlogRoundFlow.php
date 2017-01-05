<?php
class TlogRoundFlow extends TlogBase {
	const EVENT = 'RoundFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'BattleID',
			'BattleType',
			'RoundScore',
			'RoundTime',
			'Result',
			'Rank',
			'Gold',
			'Cheat',
			'SecuritySDK',
			'SneakTime',
			'MaxComboNum',
			'AveComboNum',
            // #PADC_DY# ----------begin----------
            'RoundTicket',
            'Diamond',
            'StarRating'
            // #PADC_DY# -----------end-----------
	);
	
	/**
	 * バトル情報
	 *
	 * @param string $appId
	 *        	ゲームAPPID
	 * @param number $platId
	 *        	ios：0 /android：1
	 * @param string $openId
	 *        	ユーザOpenID
	 * @param number $battleId
	 *        	ダンジョンID
	 * @param number $battleType
	 *        	バトルタイプ
	 * @param number $roundScore
	 *        	バトルの得点
	 * @param number $roundTime
	 *        	バトル時間(秒)
	 * @param number $result
	 *        	バトル結果
	 * @param number $rank
	 *        	ランキング
	 * @param number $gold
	 *        	コイン
	 * @param number $cheat
	 *        	チート
	 * @param string $securitySDK
	 *        	安全SDKからのデータ
	 * @param string $sneakTime
	 *        	潜入時間
	 * @param number $maxComboNum
	 *        	最大コンボ数
	 * @param number $aveComboNum
	 *        	平均コンボ数
	 * @param number $roundTicket
	 *        	获得扫荡券数量
	 * @param number $diamond
	 *        	获得魔法石数量
	 * @param number $starRating
	 *        	評価の星数
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $battleId, $battleType, $roundScore, $roundTime, $result, $rank, $gold, $cheat, $securitySDK, $sneakTime, $maxComboNum, $aveComboNum, $roundTicket = 0, $diamond = 0, $starRating = 0) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$battleId,
				$battleType,
				$roundScore,
				$roundTime,
				$result,
				$rank,
				$gold,
				$cheat,
				$securitySDK,
				$sneakTime,
				$maxComboNum,
				$aveComboNum,
                // #PADC_DY# ----------begin----------
                $roundTicket,
                $diamond,
                $starRating
                // #PADC_DY# -----------end-----------
		);
		return static::generateMessageFromArray ( $params );
	}
}
