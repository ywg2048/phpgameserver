<?php
/**
 * #PADC#
 */
class QueryQualify extends BaseAction {
	/**
	 *
	 * @see BaseAction::action()
	 */
	public function action($params) {
		$user_id = $params ["pid"];
		
		$token = Tencent_MsdkApi::checkToken($params);
		if(!$token){
			return json_encode(array('res' => RespCode::TENCENT_TOKEN_ERROR));
		}
		
		$result = User::queryQualify ( $user_id, $token );
		return json_encode ( $result );
	}
}