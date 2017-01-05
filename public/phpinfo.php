<?php
	// PHPファイル群をロード.
	require_once ("../app/config/autoload.php");

	if(isset($_SERVER["SERVER_NAME"]))
	{
		echo('server-name:'.$_SERVER["SERVER_NAME"]);
		setEnvironment($_SERVER["SERVER_NAME"],$_SERVER["SERVER_PORT"]);
	}
	
	if(isset($_SERVER))
	{
		foreach($_SERVER as $key => $value)
		{
			echo($key . ' => ' . $value . "<br />\n");
		}
	}
	
	echo('ENV:' . Env::ENV . "<br />\n");
	
	phpinfo();
	exit;
?>
