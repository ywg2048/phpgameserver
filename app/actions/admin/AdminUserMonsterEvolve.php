<?php
class AdminUserMonsterEvolve extends AdminBaseAction{
	public function action($params)
	{
		$pdo = Env::getDbConnectionForUserWrite($params['pid']);
		$pdo->beginTransaction();
		try
		{
			$user = User::find($params['pid'],$pdo,TRUE);
			$coin = $user->coin;
			
			if($params['all'] || $params['cuid'] == 0)
			{
				$user_cards = UserCard::findAllBy(array('user_id' => $user->id),null,null,$pdo,TRUE);
			}
			else
			{
				$user_cards = array();
				$user_cards[] = UserCard::findBy(array('user_id' => $user->id,'cuid' => $params['cuid']),null,null,$pdo,TRUE);	
			}

			$evolve_cards = array();
			$share_pdo = Env::getDbConnectionForShareRead();
			foreach($user_cards as $user_card)
			{

				// 自分のcuid,進化用かけらID、かけら使用数,進化先ID
				// LVを最大にしてしまう。
				$card = $user_card->getMaster();
				// SQLの組み立て.
				$conditions_key = array('gupc','gupc2','gupc3','gupc4','gupc5');
				$conditions = array();
				$values = array($card->id,$card->id,$card->id,$card->id,$card->id);
				foreach($conditions_key as $k => $v) {
					$conditions[] = $v . '=?';
				}

				
				$sql = "SELECT * FROM " . Card::TABLE_NAME . " WHERE " . join(' OR ', $conditions);

				$stmt = $share_pdo->prepare($sql);
				$stmt->setFetchMode(PDO::FETCH_CLASS, 'Card');
				$stmt->execute($values);

				$target_card = $stmt->fetch(PDO::FETCH_CLASS);
				// 進化先が見つかった場合のみ、進化用パラメータを生成。
				if($target_card)
				{
					$evolve_param = array();
					if($user_card->lv < $card->mlv)
					{
						$user_card->lv = $card->mlv;
						$user_card->update($pdo);
						
					}

					$gup_index = $target_card->getGupcIndex($user_card->card_id);

					$required_evolve_exp = $target_card->getRequiredEvolveExp($gup_index);
					$evolve_price = $user_card->getPieceEvolvePrice($required_evolve_exp);
					$piece = Piece::find($target_card->gup_piece_id);
					$num = ceil($required_evolve_exp/$piece->eexp);

					$evolve_param['user_id'] = $user->id;
					$evolve_param['base_cuid'] = $user_card->cuid;
					$evolve_param['base_card_id'] = $card->id;
					$evolve_param['base_name'] = $card->name;
					$evolve_param['target_name'] = $target_card->name;
					$evolve_param['target_card_id'] = $target_card->id;
					$evolve_param['price'] = $evolve_price;
					$add_piece = array();
					$add_piece[$target_card->gup_piece_id] = array('id' => $target_card->gup_piece_id, 'num' => $num);
					$evolve_param['add_piece_datas'] = $add_piece;
					$evolve_cards[] = $evolve_param;
				}
			}
			$pdo->commit();
		}
		catch(Exception $e)
		{
			if($pdo->inTransaction())
			{
				$pdo->rollback();
				throw $e;
			}
		}
		$evolve_info = array();
		foreach($evolve_cards as $evolve_card)
		{
			$pdo->beginTransaction();
			try
			{
				// 進化の費用加算
				$user->coin += $evolve_card['price'];
				$user->update($pdo);
				$before_pieces = array();
				foreach($evolve_card['add_piece_datas'] as $add_piece)
				{
					$user_piece = UserPiece::getUserPiece($evolve_card['user_id'],$add_piece['id'],$pdo,TRUE);
					// MY モンスターは持っているはずなので、生成は気にしない。
					$user_piece->addPiece($add_piece['num'],$pdo);
					$user_piece->update($pdo);
				}
				$pdo->commit();
			}
			catch(Exception $e)
			{
				if($pdo->inTransaction())
				{
					$pdo->rollBack();
					throw $e;
				}
			}
			list($succeed, $coin, $result_pieces) = UserCard::pieceEvolve($evolve_card['user_id'], $evolve_card['base_cuid'], $evolve_card['target_card_id'], $evolve_card['add_piece_datas']);
			$evolve_info[$evolve_card['base_cuid']] = array(
				'evolve_succeed' => $succeed,
				'cuid' => $evolve_card['base_cuid'],
				'base_card_id' => $evolve_card['base_card_id'],
				'base_card_name' => $evolve_card['base_name'],
				'target_card_id' => $evolve_card['target_card_id'],
				'target_card_name' => $evolve_card['target_name'],
				);
		}
		if(count($evolve_info) == 0)
		{
			$evolve_info[] = array('evolve_monster_none');
		}
		return json_encode($evolve_info);
	}
	
}