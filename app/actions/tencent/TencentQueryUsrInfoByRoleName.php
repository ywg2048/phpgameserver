<?php
/**
 * Tencent用：ユーザデータを検索
 */
class TencentQueryUsrInfoByRoleName extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$ptype = static::convertArea ( $params ['AreaId'] );
		$type = $params ['PlatId'];
		$name = $params ['RoleName'];
		
		if ($ptype == 0) {
			throw new PadException ( static::ERR_INVALID_REQ, 'Unknown area!' );
		}
		
		$user = $this->findName ( $ptype, $type, $name );
		
		if (isset ( $user )) {
			return json_encode ( array_merge ( array (
					'res' => 0,
					'msg' => 'OK' 
			), self::arrangeResponse ( $user ) ) );
		}
		
		return json_encode ( array_merge ( array (
				'res' => 0,
				'msg' => 'OK' 
		), self::arrangeResponse () ) );
	}
	
	/**
	 *
	 * @param number $ptype        	
	 * @param number $type        	
	 * @param string $name        	
	 * @return array
	 */
	private function findName($ptype, $type, $name) {
		$dsns = Env::getAllReadDSN ();
		foreach ( $dsns as $dsn => $dsid ) {
			$pdo = new PDO ( $dsn, Env::DB_USERNAME, Env::DB_PASSWORD, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET utf8;' 
			) );
			$pdo->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			if (Env::DB_SET_TIMEZONE == TRUE) {
				Env::setDbTimezone ( Env::DB_TIMEZONE, $pdo );
			}
			
			$users = User::findAllBy ( array (
					'name' => $name 
			), null, null, $pdo );
			foreach ( $users as $user ) {
				if ($user && $user->del_status != User::STATUS_DEL) {
					return $user;
				}
			}
		}
		
		return null;
	}
	
	/**
	 *
	 * @param User $user        	
	 * @return array
	 */
	public static function arrangeResponse($user = null) {
		$openid = isset ( $user ) ? UserDevice::getUserOpenId ( $user->id ) : '';
		$response = array ();
		$response ['IsExist'] = isset ( $user ) ? 1 : 0;
		$response ['OpenId'] = isset ( $user ) ? $openid : '';
		$response ['RoleName'] = isset ( $user ) ? $user->name : '';
		$response ['Job'] = 0;
		// #PADC_DY# ----------begin----------
		// $response ['Level'] = isset ( $user ) ? $user->clear_dungeon_cnt : 0;
		$response ['Level'] = isset ( $user ) ? $user->lv : 0;
		// #PADC_DY# ----------end----------
		$response ['Money'] = isset ( $user ) ? $user->coin : 0;
		$response ['Physical'] = isset ( $user ) ? $user->getStamina () : 0;
		$response ['Diamond'] = isset ( $user ) ? $user->gold + $user->pgold : 0;
		$response ['Exp'] = 0;
		$response ['TitleId'] = 0;
		$response ['Fight'] = 0;
		$response ['IsOnline'] = 0;
		$response ['LastLoginTime'] = isset ( $user ) ? strtotime ( $user->li_last ) : 0;
		$response ['LastLogoutTime'] = 'NULL';
		$response ['MaxPass'] = isset ( $user ) ? $user->last_clear_normal_dungeon_id : 0;
		$response ['Vip'] = isset ( $user ) ? $user->vip_lv : 0;
		$response ['OnlineTime'] = 0;
		return $response;
	}
}
