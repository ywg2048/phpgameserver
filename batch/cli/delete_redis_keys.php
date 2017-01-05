<?php
require_once ("../../app/config/autoload.php");

function parseTargets($targets)
{
	return array_map('trim', explode(',', $targets));
}

function showHelp()
{
	echo "usage: ./delete_redis_keys.php --host=[host name/ip] -k=[target_keys]\n";
	echo "    target_keys format: a,b,c,d\n";
	echo "    --dry_run: Dry run mode for checking keys exists\n";
}

function deleteRedisKey($key, $is_dry_run_mode)
{
	$redis = Env::getRedisForShare();
	if($redis->exists($key)) {
		if ($is_dry_run_mode) {
			echo "(Dry Run Test) Found and deleted key: $key\n";
		} else {
			$redis->del($key);
			echo "Found and deleted key: $key\n";
		}
	} else {
		echo "Not found key: $key\n";
	}
}

$short_opts = "k::h";
$long_opts = array(
	"dry_run",
	"host::"
);

$options = getopt($short_opts, $long_opts);
if (isset($options["h"])) {
	showHelp();
	exit(0);
}

if (empty($options["host"])) {
	showHelp();
	exit("Empty host name (game server)! Please specify host name with option '--host=[host name/ip]'\n");
}
$host_name = $options["host"];
setEnvironment($host_name);

$is_dry_run_mode = false;
if (isset($options["dry_run"])) {
	$is_dry_run_mode = true;
}

$target_keys = array();
if (isset($options["k"])) {
	$target_keys = parseTargets($options["k"]);
}

if ($is_dry_run_mode) {
	echo "-----------Dry Run Mode-------------\n";
}

if (empty($target_keys)) {
	while (false !== ($key = fgets(STDIN))) {
		if (! empty(trim($key))) {
			deleteRedisKey(trim($key), $is_dry_run_mode);
		}
	}
} else {
	foreach($target_keys as $key) {
		if (! empty(trim($key))) {
			deleteRedisKey(trim($key), $is_dry_run_mode);
		}
	}
}


