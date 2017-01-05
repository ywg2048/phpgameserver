<?php
/**
 * 39. おすすめヘルパーリスト取得
 */
class GetRecommendedHelpers extends BaseAction {

	// http://pad.localhost/api.php?action=get_recommended_helpers&pid=2&sid=1
	public function action($params){
		// #PADC# ----------begin----------
		// たまドラモード用の処理はカット
		$helpers = RecommendedHelperUtil::findHelpers($params['pid']);
		// #PADC# ----------end----------
		return json_encode(array('res' => RespCode::SUCCESS, 'helpers'=>$helpers));
	}

}
