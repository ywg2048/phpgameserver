<?php
/**
 * Tencent用：アイテム削除
 */
class TencentDoDelItem extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$type = $params ['PlatId'];
		$openid = $params ['OpenId'];
		$item_id = $params ['ItemId'];
		$uuid = $params ['Uuid'];
		$item_num = $params ['ItemNum'];
		$source = isset($params ['Source'])? $params ['Source'] : null;
		$serial = isset($params ['Serial'])? $params ['Serial'] : null;
				
		$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		if ($item_id == BaseBonus::COIN_ID) {
			$result = self::delCoin ( $user_id, $item_num, $pdo );
		} else if ($item_id == BaseBonus::FRIEND_POINT_ID) {
			$result = self::delFriendPoint ( $user_id, $item_num, $pdo );
		} else if ($item_id == BaseBonus::PIECE_ID) {
			$result = self::delPiece ( $user_id, $uuid, $item_num, $pdo );
		} else {
			throw new PadException(RespCode::UNKNOWN_ERROR, "Unsupported item id");
		}

		return json_encode ( array (
				'res' => 0,
				'msg' => 'OK',
				'Result' => 0,
				'RetMsg' => 'OK' 
		) );
	}
    
	public static function delCoin($user_id, $num, $pdo) {
		$user = User::find ( $user_id, $pdo, true );
		if ($user == false) {
			return 'User not found!';
		}
		if ($num > 0) {
			if ($user->coin > $num) {
				$user->coin -= $num;
			} else {
				$user->coin = 0;
			}
			$user->update ( $pdo );
		}
		return 0;
	}
    
	public static function delFriendPoint($user_id, $num, $pdo) {
		$user = User::find ( $user_id, $pdo, true );
		if ($user == false) {
			return 'User not found!';
		}
		if ($num > 0) {
			$fripnt_before = $user->fripnt;
			if ($user->fripnt > $num) {
				$user->fripnt -= $num;
			} else {
				$user->fripnt = 0;
			}
			$user->update ( $pdo );
			$fripnt_after = $user->fripnt;
			
			//TLOG friend point
			UserTlog::sendTlogMoneyFlow($user, $fripnt_after - $fripnt_before, Tencent_Tlog::REASON_IDIP, Tencent_Tlog::MONEY_TYPE_FRIEND_POINT);
		}
		return 0;
	}
    
	public static function delPiece($user_id, $piece_id, $num, $pdo) {
		$user_piece = UserPiece::findBy ( array (
				'user_id' => $user_id,
				'piece_id' => $piece_id 
		), $pdo, true );
		if (! $user_piece) {
			return 'Piece not found!';
		}
		
		if ($num > 0) {
			if ($user_piece->num > $num) {
				$user_piece->num -= $num;
			} else {
				$user_piece->num = 0;
			}
			$user_piece->update ( $pdo );
		}
		return 0;
	}
}
