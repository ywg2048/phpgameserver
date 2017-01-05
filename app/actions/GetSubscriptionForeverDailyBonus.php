<?php
class GetSubscriptionForeverDailyBonus extends BaseAction {
	public function action($params) {
		$user_id = $params ['pid'];
		$token = Tencent_MsdkApi::checkToken ( $params );
		
		$pdo = ENV::getDbConnectionForUserWrite ( $user_id );
		$user = User::find ( $user_id, $pdo );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}
		
		if (! $user->tss_forever_end) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'No subscribe' );
		}
		
		if ($user->isGetForeverSubscriptionBonusToday ()) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'Already get today' );
		}
		
		try {
			$user->presentGold ( Env::SUBSCRIPTION_DAILY_GOLD, $token );
			$user->last_forever_subs_daily = BaseModel::timeToStr ( time () );
			$gold = $user->gold + $user->pgold;
			$user->tss_forever_cnt += 1;

			$user->update($pdo);
			UserTlog::sendTlogMoneyFlow($user, Env::SUBSCRIPTION_DAILY_GOLD, Tencent_Tlog::REASON_BONUS, Tencent_Tlog::MONEY_TYPE_DIAMOND, Env::SUBSCRIPTION_DAILY_GOLD, 0, 0, Tencent_Tlog::SUBREASON_SUBSCRIPTION_BONUS);
			UserTlog::sendTlogMonthlyReward($user, Env::SUBSCRIPTION_DAILY_GOLD);
			$user->reportScore(Tencent_MsdkApi::SCORE_TYPE_GOLD, $token);
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollBack ();
			}
			throw $e;
		}
		
		$res = array (
				'res' => RespCode::SUCCESS ,
				'get_gold' => Env::SUBSCRIPTION_DAILY_GOLD,
				'gold' => $gold,
				'get_cnt' => $user->tss_forever_cnt,
		);

		return json_encode ( $res );
	}
}