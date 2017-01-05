<?php

/**
 * #PADC_DY#
 * 查看玩家信息
 */
class GetRankingUserInfo extends BaseAction {
	public function action($params){
		
		$user_id = $params['pid'];
		$ranking_id = $params['ranking_id'];
		$user = User::find($user_id);
		$pdo = Env::getDbConnectionForUserRead($user_id);
		$ranking_record = UserRankingRecord::findbyUserId(array('user_id'=>$user_id,'ranking_id'=>$ranking_id),$pdo);
		if($user){
			if(!$ranking_record){
				throw new PadException(RespCode::UNKNOWN_ERROR, "用户没有打过本次排名关卡");
			}
			$openids = UserDevice::getOpenids(array($user_id));
			$user_deck =UserDeck::findBy(array('user_id'=>$user_id));
			$user_decks = json_decode($user_deck->decks,1);
			foreach ($user_decks as $deck) {

				if(key($deck) == sprintf("set_%02s", $user_deck->deck_num)){
					$current_deck = $deck;
				}
			}
			$deck_param_data = new DeckParamData();
			list($deck_total_power,$deck_hp,$deck_atk,$deck_rec) = $deck_param_data->getDecksInfo($user,$user_deck->deck_num,$pdo);
			$res = array(
				'res' =>RespCode::SUCCESS,
				'name' => $user->name,
				'decks' => $user_deck->decks,
				'current_deck' => $current_deck,
				'deck_total_power' => $deck_total_power,
				'deck_hp' =>$deck_hp,
				'deck_atk' => $deck_atk,
				'deck_rec' => $deck_rec,
				'openid' =>$openids[$user_id],
				'turns' => $ranking_record->turns,
				'waves' => $ranking_record->waves,
				'combos' => $ranking_record->combos,
				'rare' => $ranking_record->rare,
				'score' => $ranking_record->score
			);
		}else{
			throw new PadException(RespCode::USER_NOT_FOUND, "user not find");
		}	
		return json_encode($res);
	}
}
