<?php
/**
 * Admin用：apcの操作
 */
class AdminManageApc extends AdminBaseAction
{
	public function action($params)
	{
		$type = isset($params['rt']) ? $params['rt'] : 0;
		$rKey = isset($params['rkey']) ? $params['rkey'] : '';
		$pass = isset($params['pass']) ? $params['pass'] : '';

		// 指定されたキーのデータを削除
		if($type == 1)
		{
			$result = 'no exist';
			if(apc_exists($rKey))
			{
				$result = 'exist and delete';
				apc_delete($rKey);
			}
		}
		// 全データ削除
		elseif($type == 2)
		{
			if($pass && $pass == 'padc')
			{
				apc_clear_cache();
				$result = 'clear cache';
			}
			else
			{
				$result = 'password is not correct!';
			}
		}
		// キー検索
		elseif($type == 3)
		{
			$result = array(
				'key'	=> $rKey,
				'size'	=> '-',
				'value'	=> '-no exist-',
			);
			if(apc_exists($rKey))
			{
				$data = apc_fetch($rKey);
				$size = strlen($data);
				$encoded_data = base64_encode($data);
				$result = array(
					'key'	=> $rKey,
					'size'	=> $size,
					'value(base64encode)'	=> $encoded_data,
				);
			}
			return json_encode($result);
		}
		// データのキー一覧を返す
		else
		{
			$result = apc_cache_info();
		}

		$result = array(
			'res' => RespCode::SUCCESS,
			'type' => $type,
			'result' => $result,
		);
		return json_encode($result);
	}
}
