<?php
/**
 * #PADC#
 * sync gold and pgold value with tencent server,response client next lv up tp_gold needed and tp_gold
 * 
 */
class ReflectPurchaseGold extends BaseAction {
	const MEMCACHED_EXPIRE = 86400;
	// http://domainname/api.php?action=reflect_purchase_gold
	public function action($params) {
		$user_id = $params ["pid"];

		$token = Tencent_MsdkApi::checkToken($params);
		if (!$token) {
			return json_encode(array(
				'res' => RespCode::TENCENT_TOKEN_ERROR
			));
		}

		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		$user = User::find ( $user_id, $pdo, TRUE );
		
		// get balance and get user object,use user object get tp_gold
		$balance = $user->getBalance($token, $pdo);
        
		$mails = User::getMailCount ( $user_id, 0, $pdo );
		
		$res = array (
            'res' => RespCode::SUCCESS,
            'gold' => $user->gold + $user->pgold,
            'tp_gold' => $user->tp_gold,
            'vip_lv' => $user->vip_lv,
            'mails' => $mails
		);
		if (isset ( $balance ['tss_end'] )) {
			$res ['tss_end'] = $balance ['tss_end'];
		}
		
		$subs_daily_info = SubscriptionBonus::getDailyBonusInfo($params["pid"]);
		if(isset($subs_daily_info['daily'])){
			$res['subs'] = $subs_daily_info['daily'];
		}
		if(isset($subs_daily_info['remain_days'])){
			$res['subs_days'] = $subs_daily_info['remain_days'];
		}

		// 永久月卡
		$subs_forever_daily_info = SubscriptionBonus::getForeverDailyBonusInfo($params["pid"]);
		if(isset($subs_forever_daily_info['daily'])){
			$res['subsf'] = $subs_forever_daily_info['daily'];
		}
		if(isset($subs_forever_daily_info['get_cnt'])){
			$res['get_cnt'] = $subs_forever_daily_info['get_cnt'];
		}

		$vip_lvs = VipBonus::getAvailableBonusLevel($user->id);
		if($vip_lvs){
			$res['vip_lvs'] = $vip_lvs;
		}

        //魔法转盘的有关参数
		$userGoldDialInfo = UserGoldDialInfo::findBy(array('user_id'=>$user_id),$pdo);
		if(!empty($userGoldDialInfo)){
			$res['dail_pgold'] = $userGoldDialInfo->pgold;
			$res['dial_chance']= $userGoldDialInfo->chance;
		}else{
			$res['dail_pgold'] = null;
			$res['dial_chance']= null;
		}

		return json_encode ( $res );
	}
}