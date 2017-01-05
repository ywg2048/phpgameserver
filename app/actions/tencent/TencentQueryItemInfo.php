<?php
/**
 * Tencent用：ユーザーアイテムリスト
 */
class TencentQueryItemInfo extends TencentBaseAction {
	const MAX_USRITEMLIST_NUM = 100;
	
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$openid = $params ['OpenId'];
		$type = $params ['PlatId'];
		$pageNo = $params ['PageNo'];
		
		$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		
		$pdo = Env::getDbConnectionForUserRead ( $user_id );
		
		$count = UserPiece::countAllExists ($user_id, $pdo);
		
		$offset = $pageNo * self::MAX_USRITEMLIST_NUM;
		$user_pieces = UserPiece::findAllExists ($user_id,
				array ( 'offset' => $offset,
						'limit' => self::MAX_USRITEMLIST_NUM 
		) );
		
		$result = array_merge ( array (
				'res' => RespCode::SUCCESS,
				'msg' => 'OK' 
		), static::arrangeColumns ( $user_pieces, $count ) );
		
		return json_encode ( $result );
	}
	
	/**
	 *
	 * @param array $user_pieces        	
	 * @param number $count        	
	 * @return array
	 */
	public static function arrangeColumns($user_pieces, $count) {
		$mapper = array ();
		$mapper ['UsrItemList_count'] = $count;
		$mapper ['UsrItemList'] = array ();
		if (isset ( $user_pieces )) {
			foreach ( $user_pieces as $user_piece ) {
				$num = $user_piece->num;
				$arr = array ();
				$arr ['ItemId'] = BaseBonus::PIECE_ID;
				$arr ['ItemName'] = 'Piece';
				$arr ['Uuid'] = $user_piece->piece_id;
				$arr ['ItemNum'] = $num;
				$mapper ['UsrItemList'] [] = $arr;
			}
		}
		$mapper ['TotalPageNo'] = ceil ( $count / self::MAX_USRITEMLIST_NUM );
		return $mapper;
	}
}
