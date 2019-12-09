<?php
pm_Loader::registerAutoload();
pm_Context::init('uddomainreselling');
try {
    $script = PRODUCT_ROOT . '/bin/extension --exec ' . pm_Context::getModuleId() . ' uddns.php';
    $result = pm_ApiCli::call('server_dns', array('--enable-custom-backend', $script));
} catch (pm_Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
exit(0);
