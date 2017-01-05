<?php
class GetSubscriptionDailyBonus extends BaseAction {
	public function action($params) {
		$user_id = $params ['pid'];
		$token = Tencent_MsdkApi::checkToken ( $params );
		
		$pdo = ENV::getDbConnectionForUserWrite ( $user_id );
		$user = User::find ( $user_id, $pdo );
		if (! $user) {
			throw new PadException ( RespCode::USER_NOT_FOUND );
		}
		
		if (! $user->duringSubscription ()) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'No subscribe' );
		}
		
		if ($user->isGetSubscriptionBonusToday ()) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'Already get today' );
		}
		
		try {
			$user->presentGold ( Env::SUBSCRIPTION_DAILY_GOLD, $token );
			$user->last_subs_daily = BaseModel::timeToStr ( time () );
			$gold = $user->gold + $user->pgold;
			//最後のボーナスなら、期間終了にします。
			if(BaseModel::strToTime($user->tss_end) - time () < 86400){
				$user->tss_end = BaseModel::timeToStr(time());
			}
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
				'gold' => $gold
		);

		$subs_daily_info = SubscriptionBonus::getDailyBonusInfo($params["pid"]);
		if(isset($subs_daily_info['remain_days'])){
			$res['subs_days'] = $subs_daily_info['remain_days'];
		}
		
		return json_encode ( $res );
	}
}