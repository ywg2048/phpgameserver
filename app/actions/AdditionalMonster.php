<?php
/**
 * モンスター生成API
 * モンスターの重複所持条件を満たしていた場合に欠片を消費してモンスターを生成する。
 */
class AdditionalMonster extends BaseAction{
	public function action($params)
	{
		global $logger;
		$piece_id = $params['piece_id'];
		$user_id = $params['pid'];
		$cnt = isset($params['card_cnt']) ? $params['card_cnt'] : 1;
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$ret_piece = null;
		$ret_card = null;
		$ret_cards = array();

		$ret_array = array();
		$ret_array['res'] = RespCode::SUCCESS;
		try
		{
			$pdo->beginTransaction();
			$user_piece = UserPiece::getUserPiece($user_id,$piece_id,$pdo,TRUE);
			$piece_master = Piece::get($piece_id);

			if($user_piece)
			{
				if ($cnt > (int)($user_piece->num / $piece_master->gcnt)) {
					$logger->log("not enough pieces, need: $cnt * $piece_master->gcnt, have: $user_piece->num",Zend_Log::DEBUG);
					$ret_array['res'] = RespCode::UNKNOWN_ERROR;
					return json_encode($ret_array);
				}
				//$card = $user_piece->createCard($pdo,null,$user_piece->checkAdditionalMonster($pdo));
				// #PADC_DY# ----------begin----------
				// 不在限制玩家拥有的同类卡牌数量。之前的check直接变成TRUE，强制创建
				// 实现一键合成N个宠物，呵呵
				for ($i = 0; $i < $cnt; $i++) {
					$card = $user_piece->createCard($pdo,null,TRUE);
					if($card)
					{
						$ret_card = GetUserCards::arrangeColumn($card);
						$ret_cards[] = $ret_card;
					}
				}
				// #PADC_DY# ----------end----------
				$user_piece->update($pdo);
				$pdo->commit();

				$ret_piece = UserPiece::arrangeColumn($user_piece);
			}
		}
		catch(Exception $e)
		{
			if($pdo->inTransaction())
			{
				$pdo->rollback();	
			}
			throw $e;
		}

		if($ret_card)
		{
			$ret_array['card'] = $ret_card;
		}
		if($ret_piece)
		{
			$ret_array['pieces'] = $ret_piece;
		}
		if($ret_cards){
			$ret_array['cards'] = $ret_cards;
		}
		return json_encode($ret_array);
	}
}