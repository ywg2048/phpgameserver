<?php
/**
 * Tencent用：ユーザデータを検索
 */
class TencentQueryBanInfo extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$openid = $params ['OpenId'];
		$type = $params ['PlatId'];
		
		$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		$user = User::find ( $user_id );
		if (empty ( $user )) {
			throw new PadException ( RespCode::USER_NOT_FOUND, 'User not found!' );
		}
		
		$punish_info = UserBanMessage::getPunishInfo($user_id, User::PUNISH_BAN);
		
		$result = array_merge ( array (
				'res' => 0,
				'msg' => 'OK' 
		), static::arrangeColumns ( $user, $punish_info ) );
		
		return json_encode ( $result );
	}
	
	/**
	 *
	 * @param User $user        	
	 * @param array $openid        	
	 * @return array
	 */
	public static function arrangeColumns($user, $punish_info) {
		$mapper = array ();
		$mapper ['RoleName'] = isset ( $user ) ? urlencode($user->name) : 'NULL';
		$mapper ['BeginTime'] = 0;
		if($punish_info){
			$end_time = $punish_info['end'];
			$time = BaseModel::strToTime($punish_info['end']) - time();
			if($time > 31536000){
				$time = -1;
			}
			$mapper ['Time'] = $time;
			$mapper ['EndTime'] = $end_time;
			$mapper ['Reason'] = $punish_info['msg'];
		}else{
			$mapper ['Time'] = 0;
			$mapper ['EndTime'] = 0;
			$mapper ['Reason'] = '';
		}
		
		return $mapper;
	}
}
