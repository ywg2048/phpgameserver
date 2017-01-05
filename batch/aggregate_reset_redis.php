<?php
require_once ("../app/config/autoload.php");
require_once ("../app/actions/admin/AdminBaseAction.php");
require_once ("../app/actions/admin/AdminSetMasterDownloadData.php");
require_once ("../app/actions/admin/AdminManageApcAndRedis.php");
echo "Environment:".$_SERVER['SERVER_NAME']."\n";
setEnvironment($_SERVER['SERVER_NAME']);
try
{
    echo "update master data...\n";
    // adminSetDownloadMasterData
    $params = array(
        'action' => 'admin_set_master_download_data',
        'target_tables' => array (
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

    echo "clear redis...\n";
    //adminManageAPCAndRedis
    $params = array(
        'pass' => 'padc',
        'action' => 'admin_manage_apc_and_redis',
        'request_type' => '100',
        'backlink' => '1'
     );
    $adminManageApcAndRedis = new AdminManageApcAndRedis();
    $result = $adminManageApcAndRedis->action($params);
    var_dump($result);
    echo strftime("%y/%m/%d %H:%M:%S",time())."\n";
}
catch(Exception $e)
{
    echo 'ERROR Message: ' . $e->getMessage() . "\n";
    echo "ERROR Dump: \n";
    var_dump($e);
    return;
}