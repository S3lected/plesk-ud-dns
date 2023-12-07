<?php
pm_Loader::registerAutoload();
//pm_Context::init('united-domains-reselling-extension');

try {
    $result = pm_ApiCli::call('server_dns', array('--disable-custom-backend'));
} catch (pm_Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
exit(0);
