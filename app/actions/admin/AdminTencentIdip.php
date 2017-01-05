<?php
class AdminTencentIdip extends AdminBaseAction {
	const ITEM_LIST_NAME = null;

	protected static $item_kyes = array ();
	/**
	 * (non-PHPdoc)
	 *
	 * @see AdminBaseAction::action()
	 */
	public function action($params) {
		$cmd_id = isset ( $params ['Cmdid'] ) ? $params ['Cmdid'] : null;
		if (! $cmd_id) {
			return json_encode ( array (
					'res' => RespCode::UNKNOWN_ERROR,
					'response' => '' 
			) );
		}
		$cmd_id = hexdec ( $cmd_id );
		
		$body = array ();
		
		$params = self::convertParams ( $cmd_id, $params );
		
		foreach ( $params as $key => $value ) {
			if ($key == 'Cmdid') {
				continue;
			}
			$body [$key] = $value;
		}
		
		$host	= $_SERVER['HTTP_HOST'];
		$parsed_url = parse_url($_SERVER['REQUEST_URI']);
		$tencent_endpoint = str_replace('admin', 'tencent', $parsed_url['path']); // */api_admin.php => */api_tencent.php
		$request_url = 'http://'. $host . $tencent_endpoint;
		$get_params = null;
		$post_params = array (
				'data_packet' => json_encode ( array (
						'head' => array (
								'PacketLen' => 1,
								'Cmdid' => $cmd_id,
								'Seqid' => 1,
								'ServiceName' => 'PADC',
								'SendTime' => 20150101,
								'Version' => 1,
								'Authenticate' => '',
								'Result' => 0,
								'RetErrMsg' => '' 
						),
						'body' => $body 
				) ) 
		);
		
		$response = $this->runTencentApi ( $request_url, $get_params, $post_params );
		$result = array (
				'res' => RespCode::SUCCESS,
				'response' => $response 
		);
		return json_encode ( $result );
	}
	
	/**
	 *
	 * @param unknown $url        	
	 * @param unknown $get_param        	
	 * @param unknown $post_param        	
	 * @return mixed
	 */
	private function runTencentApi($request_url, $get_params, $post_params) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		
		if (! isset ( $request_url )) {
			return;
		}
		
		$url = '';
		if (isset ( $get_params )) {
			$url = $this->combineURL ( $request_url, $get_params );
		} else {
			$url = $request_url;
		}
		
		curl_setopt ( $ch, CURLOPT_URL, $url );
		
		if (isset ( $post_params )) {
			curl_setopt ( $ch, CURLOPT_POST, TRUE );
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_params );
		}
		
		$response = curl_exec ( $ch );
		curl_close ( $ch );
		
		return $response;
	}
	
	/**
	 *
	 * @param string $baseURL        	
	 * @param array $keysArr        	
	 * @return string
	 */
	private static function combineURL($baseURL, $keysArr) {
		$combined = $baseURL . "?";
		$valueArr = array ();
		
		foreach ( $keysArr as $key => $val ) {
			$valueArr [] = "$key=$val";
		}
		
		$keyStr = implode ( "&", $valueArr );
		$combined .= ($keyStr);
		
		return $combined;
	}
	
	/**
	 *
	 * @param number $cmd_id        	
	 * @param array $params        	
	 * @return array
	 */
	private static function convertParams($cmd_id, $params) {
		if (static::ITEM_LIST_NAME) {
			return self::convertItemList ( static::ITEM_LIST_NAME, $params );
		} else {
			return $params;
		}
	}
	
	/**
	 *
	 * @param string $listname        	
	 * @param array $params        	
	 * @return array
	 */
	private static function convertItemList($listname, $params) {
		if (isset ( $params [$listname] )) {
			$itemlist = array ();
			$items = explode ( '|', $params [$listname] );
			foreach ( $items as $str_item ) {
				$item = self::convertItem ( $str_item );
				if ($item) {
					$itemlist [] = $item;
				}
			}
			$params [$listname] = $itemlist;
			$params [$listname . '_count'] = count ( $itemlist );
		}
		return $params;
	}
	
	/**
	 *
	 * @param unknown $str_item        	
	 */
	private static function convertItem($str_item) {
		if (empty ( $str_item ) || ! static::$item_kyes) {
			return null;
		}
		$item_values = explode ( ' ', $str_item );
		$value_num = count ( $item_values );
		if (count ( static::$item_kyes ) != $value_num) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'Key value not match!' );
		}
		$item = array ();
		for($i = 0; $i < $value_num; $i ++) {
			$keys = static::$item_kyes;
			$item [$keys [$i]] = $item_values [$i];
		}
		return $item;
	}
}
