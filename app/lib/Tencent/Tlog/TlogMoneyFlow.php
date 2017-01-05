<?php
class TlogMoneyFlow extends TlogBase {
	const EVENT = 'MoneyFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'vGameAppid',
			'PlatID',
			'iZoneAreaID',
			'vopenid',
			'Sequence',
			'Level',
			'AfterMoney',
			'iMoney',
			'Reason',
			'SubReason',
			'AddOrReduce',
			'iMoneyType',
			'iDiamondFree',
			'iDiamondBuy',
            'iRoundTicket', // #PADC_DY#
            'iGachaId', // #PADC_DY#
            'MissionId', // #PADC_DY#
	);
	
	/**
	 * 魔法石（コイン）の使用履歴
	 *
	 * @param string $appId        	
	 * @param number $platId
	 *        	(必須)ios：0 /android：1
	 * @param string $openId
	 *        	(必須)ユーザOpenID
	 * @param number $level
	 *        	(必須)ユーザLv
	 * @param number $money
	 *        	(必須)課金/消費の魔法石（コイン）数
	 * @param number $reason
	 *        	(必須)魔法石（コイン）の動作経由１
	 * @param number $addOrReduce
	 *        	(必須)購入： 0/消費： 1
	 * @param number $moneyType
	 *        	(必須)購入アイテム対応
	 * @param number $afterMoney
	 *        	(任意)動作後に残る魔法石（コイン）数
	 * @param number $goldFree
	 * 			(可选)动作涉及的赠送魔法石
	 * @param number $goldBuy
	 * 			(可选)动作涉及的赠送魔法石
	 * @param number $roundTicket
	 * 			(可选)动作涉及的获得扫荡券数
	 * @param string $sequence
	 *        	(任意)購入処理に子処理がある場合、外部キーとしてを関連処理に結ぶ
	 * @param string $subReason
	 *        	(任意)魔法石（コイン）の動作経由２
	 * @return string
	 */
	public static function generateMessage($appId, $platId, $openId, $level, $money, $reason, $addOrReduce, $moneyType, $afterMoney, $goldFree = 0, $goldBuy = 0, $sequence = 0, $subReason = 0, $roundTicket = 0, $gachaId = null, $missionId = null) {
		$params = array (
				static::EVENT,
				static::getGameSvrId (),
				static::makeTime (),
				$appId,
				$platId,
				static::getZoneId (),
				$openId,
				$sequence,
				$level,
				$afterMoney,
				$money,
				$reason,
				$subReason,
				$addOrReduce,
				$moneyType,
				$goldFree,
				$goldBuy,
				$roundTicket, // #PADC_DY#
				$gachaId, // #PADC_DY#
				$missionId, // #PADC_DY#
		);
		return static::generateMessageFromArray ( $params );
	}
}
