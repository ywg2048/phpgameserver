<?php
/**
 * TencentAQ：ユーザデータを検索
 */
class TencentAqQueryUsrInfo extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		
		if(isset($params ['Roleid'])){
			$user_id = $params ['Roleid'];
		}else if(isset($params ['OpenId']) && isset($params ['PlatId'])){
			$openid = $params ['OpenId'];
			$type = $params ['PlatId'];
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		}else{
			throw new PadException(static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		$user = User::find ( $user_id );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'User not found!' );
		}
		
		$result = array_merge ( array (
				'res' => 0,
				'msg' => 'OK' 
		), static::arrangeColumns ( $user) );
		
		return json_encode ( $result );
	}
	
	/**
	 *
	 * @param User $user        	
	 * @param string $openid        	
	 * @return array
	 */
	public static function arrangeColumns($user) {
		$mapper = array ();
		$mapper ['RoleName'] = urlencode($user->name);
		// #PADC_DY# ----------begin----------
		// $mapper ['Level'] = $user->clear_dungeon_cnt;
		$mapper ['Level'] = $user->lv;
		// #PADC_DY# ----------end----------
		$mapper ['Vip'] = $user->vip_lv;
		$mapper ['Stone'] = $user->gold + $user->pgold;
		$mapper ['Money'] = $user->coin;
		$mapper ['MainDup'] = $user->last_clear_normal_dungeon_id;
		$mapper ['SpecialDup'] = $user->last_clear_sp_dungeon_id;
	
		return $mapper;
	}
}
