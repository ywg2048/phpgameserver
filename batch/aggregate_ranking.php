<?php
$host = FALSE;
if(count($argv) > 1)
{
	$host = $argv[1];	
}
if($host)
{

	require_once ("../app/config/autoload.php");
	setEnvironment($host);

	//Tlog初期化
	Tencent_Tlog::init(gethostname(), Env::TLOG_ZONEID);
	if (Env::TLOG_SERVER != null && Env::TLOG_PORT != null) {
		Tencent_Tlog::setServer(Env::TLOG_SERVER, Env::TLOG_PORT);
	}

	try
	{
		$ret = Ranking::reflectionAllRanking();
	}
	catch(Exception $e)
	{
		echo 'ERROR Message: ' . $e->getMessage() . "\n";
		echo "ERROR Dump: \n";
		var_dump($e);
		return;
	}
	echo strftime("%y/%m/%d %H:%M:%S",time()).' : '.$ret."\n";
}
else
{
	echo "no_host_name\n";
}
return;