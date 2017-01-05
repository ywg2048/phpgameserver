<?php
class TlogItemFlow extends TlogBase {
	const EVENT = 'ItemFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'Level',
			'Sequence',
			'iGoodsType',
			'iGoodsId',
			'Count',
			'AfterCount',
			'Reason',
			'SubReason',
			'iMoney',
			'iMoneyType',
			'AddOrReduce',
			'Rare',
			// #PADC_DY# ----------begin----------
            'iRoundTicket',
			'MissionId'
			// #PADC_DY# -----------end-----------
	);
	
	/**
	 * 装備変更履歴
	 *
	 * @param string $appId
	 *        	ゲームAPPID
	 * @param number $platId
	 *        	ios：0 /android：1
	 * @param string $openId
	 *        	ユーザOpenID
	 * @param number $level
	 *        	ユーザLv
	 * @param number $goodsType
	 *        	装備対応
	 * @param number $goodsId
	 *        	装備ID
	 * @param number $count
	 *        	数
	 * @param number $afterCount
	 *        	動作あとに残る装備数
	 * @param number $reason
	 *        	装備の動作経由１
	 * @param number $subReason
	 *        	装備の動作経由２
	 * @param number $money
	 *        	購入で装備を手に入れる場合、使用金額，なしの場合、０を記載する
	 * @param number $moneyType
	 *        	購入アイテム対応
	 * @param number $addOrReduce
	 *        	増える： 0/減らす： 1
	 * @param number $rare
	 *        	(必填) 稀有度(星级)
	 * @param string $sequence
	 *        	購入処理に子処理がある場合、外部キーとしてを関連処理に結ぶ
	 * @param number $roundTicket
	 *        	(可选)动作涉及的获得扫荡券数
	 * @param number $missionId
	 *        	(可选)动作涉及的任务ID
	 */
	public static function generateMessage($appId, $platId, $openId, $level, $goodsType, $goodsId, $count, $afterCount, $reason, $subReason, $money, $moneyType, $addOrReduce, $rare, $sequence = 0, $roundTicket = 0, $missionId = null) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$level,
				$sequence,
				$goodsType,
				$goodsId,
				$count,
				$afterCount,
				$reason,
				$subReason,
				$money,
				$moneyType,
				$addOrReduce,
				$rare,
				// #PADC_DY# ----------begin----------
                $roundTicket,
				$missionId
				// #PADC_DY# -----------end-----------
		);
		return static::generateMessageFromArray ( $params );
	}
}
