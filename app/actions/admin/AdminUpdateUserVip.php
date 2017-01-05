<?php
class AdminUpdateUserVIP extends AdminBaseAction {
	public function action($params) {
		if (isset ( $params ['pid'] )) {
			$user_id = $params ['pid'];
		} else {
			$openid = $params ['ten_oid'];
			$type = $params ['t'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}
		$vip_lv = $params ['vl'];
		$tp_gold = $params ['tpg'];
		$clear_bonus = $params ['clb'];
		
		if ($vip_lv < 0 || $tp_gold < 0) {
			$result = array (
					'res' => RespCode::UNKNOWN_ERROR 
			);
		} else {
			if ($vip_lv > 0) {
				static::updateUsersVip ( $user_id, $vip_lv, $tp_gold );
			}
			if ($clear_bonus) {
				static::clearVipBonus ( $user_id );
			}
			$result = array (
					'res' => RespCode::SUCCESS 
			);
		}
		
		return json_encode ( $result );
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param number $vip_lv        	
	 * @param number $tp_gold        	
	 * @throws PadException
	 */
	public static function updateUsersVip($user_id, $vip_lv, $tp_gold) {
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		$user = User::find ( $user_id, $pdo, true );
		if ($user == false) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'user not found!' );
		}
		$user->vip_lv = $vip_lv;
		$user->tp_gold = $tp_gold;
		$user->update ( $pdo );
	}
	
	/**
	 *
	 * @param number $user_id        	
	 */
	private static function clearVipBonus($user_id) {
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		try {
			$pdo->beginTransaction ();
			$user = User::find($user_id, $pdo, true);
			if(!$user){
				throw new PadException(RespCode::USER_NOT_FOUND);
			}
			if(isset($user->last_vip_weekly) && BaseModel::isSameWeek_AM4(BaseModel::strToTime($user->last_vip_weekly))){
				$user->last_vip_weekly = BaseModel::timeToStr(time() - 604800);
				$user->update($pdo);
			}
				
			$user_vip_bonuses = UserVipBonus::findAllBy ( array (
					'user_id' => $user_id 
			), null, null, $pdo );
			foreach ( $user_vip_bonuses as $user_vip_bonus ) {
				$user_vip_bonus->delete ( $pdo );
			}
			$pdo->commit ();
		} catch ( Exception $e ) {
			if ($pdo->inTransaction ()) {
				$pdo->rollBack ();
			}
			throw $e;
		}
	}
}