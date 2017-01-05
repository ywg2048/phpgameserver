<?php
/**
 * Admin用：apc/redisのキャッシュまとめて削除
 */
class AdminManageApcAndRedis extends AdminBaseAction
{
	public function action($params)
	{
		$pass = isset($params['pass']) ? $params['pass'] : '';

		if($pass && $pass == 'padc')
		{
			apc_clear_cache();

			$sRedis = Env::getRedisForShare();
			$sRedis->flushAll();

			$uRedis = Env::getRedisForUser();
			$uRedis->flushAll();

			$result = 'clear all cache';
		}
		else
		{
			$result = 'password is not correct!';
		}

		$result = array(
			'res' => RespCode::SUCCESS,
			'result' => $result,
		);
		return json_encode($result);
	}
}
