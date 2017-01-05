<?php
require_once("../../app/config/autoload.php");
require_once ("../../app/actions/admin/AdminBaseAction.php");
require_once ("../../app/actions/admin/AdminSetMasterDownloadData.php");
$short_opts = "t::h";
$long_opts = array(
    "all",
    "clear_cache",
    "host::"
);

$options = getopt($short_opts, $long_opts);

$host_name = $options["host"];
setEnvironment($host_name);

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
        ),
        'pass' => 'padc',
        'request_type' => '103',
        'backlink' => '1',
    );
    $adminSetMasterDownloadData = new AdminSetMasterDownloadData();
    $result = $adminSetMasterDownloadData->action($params);
    var_dump($result);

    echo "update DB version...\n";
    //adminCheckDBVersion
    $pdo = Env::getDbConnectionForShare();
    $sql = 'UPDATE versions SET version = version + 1;';
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    var_dump($result);


} catch (Exception $e) {
    echo "ERROR!" . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    throw $e;
}

Padc_Log_Log::debugToolEditDBLog("[Batch][Cli]RefreshMasterDataAndVersion. End Success");
echo "Refresh Download Master Date and Versions successfully.\n";

