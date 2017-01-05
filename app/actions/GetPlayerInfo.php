<?php
/**
 * 50. 簡易データ取得
 * ※アプリ側で呼んでいる箇所が無いとのことなので使用しません
 */
class GetPlayerInfo extends BaseAction {
	// http://pad.localhost/api.php?action=get_player_info&pid=1&sid=1
	public function action($params){
	$user = User::find($params["pid"]);
	$res = array();
	$res['res'] = RespCode::SUCCESS;
	$res['card'] = GetUserCards::getAllUserCards($user->id);
	$res['coin'] = (int)$user->coin;
	$res['gold'] = (int)($user->gold+$user->pgold);
	$res['lv'] = (int)$user->lv;
	$res['exp'] = (int)$user->exp;
	$res['camp'] = (int)$user->camp;
	$res['cost'] = (int)$user->cost_max;
	$res['sta'] = (int)$user->getStamina();
	$res['sta_max'] = (int)$user->stamina_max;
	$res['sta_time'] = strftime("%y%m%d%H%M%S", strtotime($user->stamina_recover_time));
	$res['deck'] = UserDeck::findBy(array('user_id'=>$user->id))->toCuidsCS();
	$res['fripnt'] = (int)$user->fripnt;
	$res['friendMax'] = (int)$user->friend_max;
	$res['friends'] = Friend::getFriends($user->id);
	// ユーザーランク部分はデータが無いのと、仕様変更予定のためコメントアウト
// 	$res['curLvExp'] = (int)LevelUp::get($user->lv)->required_experience;
// 	if($user->lv == 999){
// 		$next_level_exp = 0;
// 	}else{
// 		$nextLevel = LevelUp::get($user->lv+1);
// 		$next_level_exp = (int)$nextLevel->required_experience;
// 	}
// 	$res['nextLvExp'] = $next_level_exp;
		return json_encode($res);
	}

}
