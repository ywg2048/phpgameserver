<?php
require_once ("../../app/config/autoload.php");

function parseTargets($targets)
{
	return array_map('trim', explode(',', $targets));
}

function incrementVersion($pdo, $master_data_name)
{
	$version = Version::findBy(array('name' => $master_data_name), $pdo, TRUE);
	if($version) {
		$version->version += 1;
		$version->update($pdo);
	} else {
		exit("Cannot find any version record with 'name=$master_data_name'\n");
	}
	/*
	else {
		$version = new Version();
		$version->name = $master_data_name;
		$version->version = 1;
		$version->create($pdo);
	}
	*/
}

function showHelp()
{
	echo "usage: ./increment_master_data_version.php --host=[host name/ip] -t=[target_list]\n";
	echo "    target_list format: a,b,c,d\n";
	echo "    --all: increment version for all master data\n";
	echo "    --clear_cache: clear cache immediately after update DB record\n\n";
}

$short_opts = "t::h";
$long_opts = array(
	"all",
	"clear_cache",
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

$is_clear_cache = false;
if (isset($options["clear_cache"])) {
	$is_clear_cache = true;
}

$is_target_all = false;
$target_list = array();
if (isset($options["all"])) {
	$is_target_all = true;
} else {
	if (empty($options["t"])) {
		showHelp();
		exit("Empty target list!\n");
	} else {
		$target_list = parseTargets($options["t"]);
	}
}

if ($is_target_all == false && empty($target_list)) {
	showHelp();
	exit("Empty target list! Please specify target with --all or -t option\n");
}

try {
	$pdo = Env::getDbConnectionForShare();
	$pdo->beginTransaction();

	Padc_Log_Log::debugToolEditDBLog("[Batch][Cli]incrementMasterDataVersion. host_name=$host_name, is_target_all=$is_target_all, targets=" . (isset($options["t"]) ? $options["t"] : ""));
	if ($is_target_all) {
		$result = Version::findAllBy(array(), null, null, $pdo);
		$versions = array();
		foreach ($result as $v) {
			echo "Increment version of " . $v->name . "\n";
			incrementVersion($pdo, $v->name);
		}
	} else {
		foreach($target_list as $target_name) {
			echo "Increment version of " . $target_name . "\n";
			incrementVersion($pdo, $target_name);
		}
	}
	$pdo->commit();

	if ($is_clear_cache) {
		Version::updateCache();
		echo "Cleared cache.\n";
	}
} catch (Exception $e) {
	echo "ERROR!" . $e->getMessage() . "\n";
	if ($pdo->inTransaction()) {
		$pdo->rollback();
	}
	throw $e;
}

Padc_Log_Log::debugToolEditDBLog("[Batch][Cli]incrementMasterDataVersion. End Success");
echo "\nIncrement all versions successfully.\n";

