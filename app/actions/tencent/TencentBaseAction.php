<?php
/**
 * アクションクラスのベースクラス.
 */
abstract class TencentBaseAction
{
	const ERR_NO_USER = 1;
	const ERR_INVALID_REQ = -101;
	const CMD_ERROR_API_GAME = -4000;
	
	public $post_params;
	public $decode_params;

	public function process($get_params, $request_uri, $post_params = null)
	{
		if(Env::ENV !== "production"){
			global $timer;
			$timer->setMarker('handling request');
		}
		if($this->isValidRequest($get_params, $request_uri) === TRUE || Env::CHECK_REQ_SUM === FALSE){
			$this->post_params = $post_params;
			$this->decode_params = array();
			if(isset($get_params['e'])){
				$this->decode_params = Cipher::decode($get_params['e']);
			}
			$api_revision = isset($get_params['r']) ? (int)$get_params['r'] : 0; 
			Env::setRev($api_revision);
			if(Env::ENV !== "production") {
				global $logger;
				$debug_print = "\n** REQUEST PARAM **\n";
				$debug_print .= "get_params :\n";
				foreach($get_params as $key => $val){
					$debug_print .= "   [$key] => ".print_r($val, true)."\n";
				}
				if(count($post_params) > 0){
					$debug_print .= "post_params :\n";
					foreach($post_params as $key => $val){
						$debug_print .= "   [$key] => ".print_r($val, true)."\n";
					}
				}
				if(count($this->decode_params) > 0){
					$debug_print .= "decode_params :\n";
					foreach($this->decode_params as $key => $val){
						$debug_print .= "   [$key] => $val\n";
					}
				}
				$logger->log($debug_print, Zend_Log::INFO);
			}
			$response = $this->action($get_params);
			
			//Tlog送信
			Padc_Log_Log::sendTlogHistory();
				
			if(Env::ENV !== "production"){
				global $timer;
				$timer->setMarker('called action');
			}
		}else{
			throw new PadException(RespCode::INVALID_REQUEST_CHECKSUM);
		}
		if(Env::ENV !== "production"){
			global $timer;
			$timer->stop();
			$profiling = $timer->getProfiling();
			global $logger;
			$action_name = '';//$_GET['action'];;
			foreach($profiling as $p){
//				$logger->log($action_name . ' ' . $p['name'] . ' ' . $p['time'] . ' ' . $p['diff'] . ' ' . $p['total'], Zend_Log::INFO);
				$logger->log($action_name . '  ' . substr($p['name'].str_repeat(' ',20),0,18) . '  ' . round($p['diff'], 4) . '  ' . round($p['total'], 4), Zend_Log::INFO);
			}
			// var_dump($profiling);
			if(substr($response, 0, 2) !== "\x1f\x8b"){
				$logger->log(mb_substr($response, 0, 4096), Zend_Log::INFO);
			}
		}
		return $response;
	}

	/**
	 * action base
	 * @param array $get_params
	 * @throws Exception
	 */
	public function action($get_params){
		throw new Exception('Unimplemented action');
	}

	/**
	 * request check
	 * @param array $get_params
	 * @param string $request_uri
	 * @return boolean
	 */
	private function isValidRequest($get_params, $request_uri){
		return true;//skip
		$result = FALSE;
		$key = $get_params['key'];
		$i = strpos($request_uri, '?') + 1;
		$e = strrpos($request_uri, '&');
		$args = (substr($request_uri, $i, $e - $i));
		$r = isset($get_params['r']) ? $get_params['r'] : 0;
		// APIリビジョン3から新暗号化ロジック.キーはリージョン別に.
		if(Env::REGION == "NA"){
			$pad_key_maker = new padKeyMakerVer2NA();
		}elseif(Env::REGION == "KR"){
			$pad_key_maker = new padKeyMakerVer2KR();
		}elseif(Env::REGION == "EU"){
			$pad_key_maker = new padKeyMakerVer2EU();
		}else{
			$pad_key_maker = new padKeyMakerVer2();
		}
		if(Env::ENABLED_OS == 'ios'){
			$result = $pad_key_maker->isKeyMatchedComplex($args, $key);
		}elseif(Env::ENABLED_OS == 'android'){
			$result = $pad_key_maker->isKeyMatchedAndroid($args, $key);
		}elseif(Env::ENABLED_OS == 'amazon'){//場合分けはしたが内部処理はAndroidと同一
			$result = $pad_key_maker->isKeyMatchedAndroid($args, $key);
		}else{
			$result = FALSE;
		}
		return $result;
	}

	public function createDummyDataForUser($user, $pdo) {
		// 何もしない. 子クラスごとに実装されていればそれを実行する.
	}
	
	public static function AreaId2PType($area_id){
		return $area_id % 2 + 1;
	}
	
	public static function checkItemList($items){
		foreach($items as $item){
			$item_id = $item ['ItemId'];
			$item_num = $item ['ItemNum'];
			$uuid = $item ['Uuid'];
			if($item_id != BaseBonus::COIN_ID 
					&& $item_id != BaseBonus::MAGIC_STONE_ID 
					&& $item_id != BaseBonus::FRIEND_POINT_ID 
					&& $item_id != BaseBonus::PIECE_ID 
					&& $item_id != BaseBonus::ROUND_ID
					// #PADC_DY# ----------begin----------
					&& $item_id != BaseBonus::USER_EXP
					&& $item_id != BaseBonus::USER_VIP_EXP
					&& $item_id != BaseBonus::STAMINA_RECOVER_ID
					// #PADC_DY# -----------end-----------
					|| $item_num <= 0){
				return false;
			}
			if($item_id == BaseBonus::PIECE_ID
					&& $uuid <= 0){
				return false;
			}
		}
		return true;
	}
	
	public static function convertArea($area){
		$ptype = 0;
		if($area == Env::IDIP_AREA_QQ){
			$ptype = UserDevice::PTYPE_QQ;
		}else if($area == Env::IDIP_AREA_WECHAT){
			$ptype = UserDevice::PTYPE_WECHAT;
		}else if($area == Env::IDIP_AREA_GUEST){
			$ptype = UserDevice::PTYPE_GUEST;
		}
		return $ptype;
	}
}

class padKeyMakerVer2 {
	public function isKeyMatchedComplex($args, $key) {
		return true;
	}
	public function isKeyMatchedAndroid($args, $key) {
		return true;
	}
}
