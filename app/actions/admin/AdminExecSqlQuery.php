<?php
/**
 * Admin用:SQL実行
 */
class AdminExecSqlQuery extends AdminBaseAction
{
	public function action($params)
	{
		$sqlquery	= isset($params['sqlquery']) ? $params['sqlquery'] : '';
		$pass		= isset($params['pass']) ? $params['pass'] : '';

		if($pass && $pass == 'padc')
		{
			$tmpQueries = array();
			if(preg_match("/\n/",$sqlquery))
			{
				$tmpQueries = explode("\n", $sqlquery);
			}
			else
			{
				$tmpQueries[] = $sqlquery;
			}

			$execQuery = '';
			$pdo = Env::getDbConnectionForShare();
			foreach($tmpQueries as $_query)
			{
				if($_query)
				{
					$stmt = $pdo->prepare($_query);
					$stmt->execute();

					$execQuery .= $_query . "<br />\n";
				}
			}

			$result = $execQuery;
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