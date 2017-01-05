<?php
require_once ("../../app/config/autoload.php");
require_once ("../../app/actions/admin/AdminBaseAction.php");
require_once ("../../app/actions/admin/AdminSetMasterDownloadData.php");

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

$host_name = $options["host"];
setEnvironment($host_name);

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
	echo "update master data...\n";
	// adminSetDownloadMasterData
	$params = array(
			'action' => 'admin_set_master_download_data',
			'target_tables' => array(
					0 => '3',
					1 => '4',
					2 => '6',
					3 => '10',
					4 => '11',
					5 => '1001',
					6 => '1002',
					7 => '1003',
					8 => '1004',
					9 => '1005',
					10 => '1006',
					11 => '1007',
					12 => '1008'
			),
			'pass' => 'padc',
			'request_type' => '103',
			'backlink' => '1',
	);
	$adminSetMasterDownloadData = new AdminSetMasterDownloadData();
	$result = $adminSetMasterDownloadData->action($params);
	echo "update master data succeed...\n";

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

} catch (Exception $e) {
	echo "ERROR!" . $e->getMessage() . "\n";
	if ($pdo->inTransaction()) {
		$pdo->rollback();
	}
	throw $e;
}

Padc_Log_Log::debugToolEditDBLog("[Batch][Cli]incrementMasterDataVersion. End Success");
echo "\nIncrement versions successfully.\n";

