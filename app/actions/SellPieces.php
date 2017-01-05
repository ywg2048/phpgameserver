<?php
/**
　* #PADC#
 * 欠片売却.
 * http://127.0.0.1:8081/api.php?action=sell_pieces&pid=123795846&sid=12VUu3uCCv9ITUUDlzgOVyShjIOHKu1lZ89IOhuB&key=jset
 */
class SellPieces extends BaseAction {
	// http://127.0.0.1:8081/api.php?action=sell_pieces&pid=123795846&sid=12VUu3uCCv9ITUUDlzgOVyShjIOHKu1lZ89IOhuB&key=jset
	public function action($params)
	{
		$user_id = $params['pid'];
		$piece_datas = array();
		$i = 1;
		$piece_key = 'piece'.$i;
		$piece_num_key = 'piecenum'.$i;
		while(array_key_exists($piece_key, $params))
		{
			$piece_datas[$params[$piece_key]] = array('id' => $params[$piece_key], 'num' => $params[$piece_num_key]);

			$i = $i + 1;			
			$piece_key = "piece".$i;
			$piece_num_key = "piecenum".$i;
		}
		list($res,$before_coin,$coin,$after_pieces) = UserPiece::sellPieces($user_id, $piece_datas);

		return json_encode(array('res' => $res, "before_coin" => $before_coin,"coin" => $coin,"rpieces"=>$after_pieces));

//		list($res,$before_exchange_point,$exchange_point,$after_pieces) = UserPiece::sellPiecesForExchangePoint($user_id, $piece_datas);
//		return json_encode(array('res' => $res, "before_coin" => 0,"coin" => 0,"rpieces" => $after_pieces,"before_exchange_point" => $before_exchange_point,"exchange_point" => $exchange_point));
	}
}