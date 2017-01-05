<?php
/**
 * 58. デッキリスト保存
 */
class SaveDecks extends BaseAction {
	
  // http://pad.localhost/api.php?action=save_decks&pid=2&sid=1&curdeck=1 (post)
  public function action($params){
  	// #PADC# decksの受取方を調整（$this->post_params['decks']→$params['decks']）
  	$decks = json_decode($params['decks'], true);
  	$totalPower = isset($params['total_power'])? $params['total_power'] : 0;
	UserDeck::setDecks($params['pid'], $params['curdeck'], $decks,$totalPower,$params);
	return json_encode(array('res'=>RespCode::SUCCESS));
  }

}
