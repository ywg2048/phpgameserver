<?php
class AdminTencentQueryUserinfo extends AdminBaseAction
{
	/**
	 * (non-PHPdoc)
	 * @see AdminBaseAction::action()
	 */
	public function action($params)
	{
		$ptype	= $params['ptype'];
		$type	= $params['t'];
		$oid	= $params['oid'];
		
		$host	= $_SERVER['HTTP_HOST'];

		$request_url	= 'http://' . $host . '/api_tencent.php';
		$get_params		= null;
		$post_params	= array (
			'data_packet' => json_encode (
				array (
					'head' => array (
						'PacketLen'	=> 1,
						'Cmdid'		=> 0x1011,
						'Seqid'		=> 1,
						'ServiceName'	=> 'PADC',
						'SendTime'	=> 20110820,
						'Version'		=> 1011,
						'Authenticate'=> '',
						'Result'		=> 0,
						'RetErrMsg'	=> '' 
					),
					'body' => array (
						'AreaId' => $ptype % 2 + 1,
						'PlatId' => $type,
						'OpenId' => $oid
					) 
				) 
			) 
		);
		
		$response = $this->runTencentApi($request_url, $get_params, $post_params);
		$result = array (
			'res'		=> RespCode::SUCCESS,
			'response'	=> $response,
		);
		return json_encode ( $result );
	}
}
