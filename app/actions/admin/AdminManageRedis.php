<?php
/**
 * Admin用：redisの操作
 */
class AdminManageRedis extends AdminBaseAction
{
	public function action($params)
	{
		$type = isset($params['rt']) ? $params['rt'] : 0;
		$rKey = isset($params['rkey']) ? $params['rkey'] : '';
		$pass = isset($params['pass']) ? $params['pass'] : '';

		$redis = Env::getRedisForShare();

		// 指定されたキーのデータを削除
		if($type == 1)
		{
			$result = 'no exist';
			if($redis->exists($rKey))
			{
				$result = 'exist and del';
				$redis->del($rKey);
			}
		}
		// 全データ削除
		elseif($type == 2)
		{
			if($pass && $pass == 'padc')
			{
				$redis->flushAll();
				$result = 'flush all';
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
			if($redis->exists($rKey))
			{
				$data = $redis->get($rKey);
				if(is_array($data))
				{
					$str	= print_r($data,true);
				}
				else
				{
					$str	= $data;
				}
				$size	= strlen($str);
				$result = array(
					'key'	=> $rKey,
					'size'	=> $size,
					'value'	=> $str,
				);
			}
			return json_encode($result);
		}
		elseif($type == 4)
		{
			$result = 'no exist';
			$user_key = CacheKey::getUserSessionKey($rKey);
			if($redis->exists($user_key))
			{
				$result = 'exist and del => '.$user_key;
				$redis->del($user_key);
			}
		}
		// データのキー一覧を返す
		else
		{
			$result = $redis->keys('*');
		}

		$result = array(
			'res' => RespCode::SUCCESS,
			'type' => $type,
			'result' => $result,
		);
		return json_encode($result);
	}
}
