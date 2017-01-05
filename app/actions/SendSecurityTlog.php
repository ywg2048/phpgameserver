<?php
class SendSecurityTlog extends BaseAction {
	const LOGIN_REQUIRED = FALSE;
	public function action($params) {
		$type = $params ['t'];
		$oid = $params ['ten_oid'];
		$ptype = $params ['pt'];
		$securitySDK = isset ( $this->decode_params ['sdkres'] ) ? $this->decode_params ['sdkres'] : null;
		$user_id = null;
		try{
			$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $oid );
		}catch(PadException $e){
			//ignore user not found
		}
		
		if(isset($user_id)){
			$sequence = Tencent_Tlog::getSequence ( $user_id );
			Padc_Log_Log::sendLogAntiData ( $type, $oid, $securitySDK, $ptype, $sequence );
		}
		
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
}
