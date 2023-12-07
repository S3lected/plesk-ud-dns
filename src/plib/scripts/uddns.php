<?php

/**
 * ud domainreselling API Extension for Plesk
 * Written by Matthias Kiefer
 * 
 * API: https://api.domainreselling.de/api/
 * 
 * This script is added as --custom-backend for Plesk DNS in post-install.php and removed in pre-uninstall.php respectively.
 */

pm_Loader::registerAutoload();
pm_Context::init('united-domains-reselling-extension');

if (!pm_Settings::get('enabledCheckbox')) {
    pm_Log::debug("UDDNS not enabled.");
    exit(0);
}

// https://api.domainreselling.de/api/call.cgi?s_login=selected.work&s_pw=*****&s_format=jsonpretty&command=CheckDomain&domain=isthisdomainfree.com
// https://api.domainreselling.de/api/call.cgi?s_login=reseller.de&s_pw=secret&command=command& parameter1=value1&parameter2=value2&parameter3=value3...
function updateDNSZone($zoneName, $zone)
{
    $zoneNameTrimmed = rtrim($zoneName, '.');
    pm_Log::debug("Update DNS Zone {$zoneName} \n");

    $service_url = 'https://api.domainreselling.de/api/call.cgi';

    $fields = [
        's_login' => pm_Settings::get('loginNameText'),
        's_pw' => pm_Settings::getDecrypted('passwordText'),
        'command' => 'UpdateDNSZone',
        'dnszone' => $zoneNameTrimmed
    ];

    $i = 0;
    foreach ($zone->rr as $rr) {
        $value = $rr->host . ' IN ' . $rr->type . ' ' . $rr->opt . ' ' . $rr->value;
        $fields['rr'.$i] = $value;
        $i++;
    }

    $options = array(
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_RETURNTRANSFER => true  // return web page
    ); 

    $postvars = http_build_query($fields);
    $uri = $service_url.'?'.$postvars;  //. $login_url . $zone_url . $rr_url;
    $ch = curl_init($uri);
    curl_setopt_array($ch, $options);
    $content = curl_exec($ch);

    if (!$content){
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        die("cURL request failed, error = {$error}; info = " . print_r($info, true));
    }
    if(curl_errno($ch)){
        pm_Log::error(curl_error($ch));
    } else {
        pm_Log::debug(print_r($content, true));
    }

    curl_close($ch);
}


/**
 * Read zone script from stdin
 *
 *[
 * {
 *  "command": "(update|delete)",
 *  "zone": {
 *      "name": "domain.tld.",
 *      "displayName": "domain.tld.",
 *      "soa": {
 *          "email": "email@address",
 *          "status": 0,
 *          "type": "master",
 *          "ttl": 86400,
 *          "refresh": 10800,
 *          "retry": 3600,
 *          "expire": 604800,
 *          "minimum": 10800,
 *          "serial": 123123123,
 *          "serial_format": "UNIXTIMESTAMP"
 *      },
 *      "rr": [{
 *          "host": "www.domain.tld.",
 *          "displayHost": "www.domain.tld.",
 *          "type": "CNAME",
 *          "displayValue": "domain.tld.",
 *          "opt": "",
 *          "value": "domain.tld."
 *      }]
 * }, {
 *  "command": "(createPTRs|deletePTRs)",
 *  "ptr": {
 *      "ip_address": "1.2.3.4",
 *      "hostname": "domain.tld"}
 * }
 *]
 */
$data = json_decode(file_get_contents('php://stdin'));
//Example:
//[
//    {"command": "update", "zone": {"name": "domain.tld.", "displayName": "domain.tld.", "soa": {"email": "amihailov@parallels.com", "status": 0, "type": "master", "ttl": 86400, "refresh": 10800, "retry": 3600, "expire": 604800, "minimum": 10800, "serial": 1363228965, "serial_format": "UNIXTIMESTAMP"}, "rr": [
//        {"host": "www.domain.tld.", "displayHost": "www.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "1.2.3.4", "displayHost": "1.2.3.4", "type": "PTR", "displayValue": "domain.tld.", "opt": "24", "value": "domain.tld."},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "TXT", "displayValue": "v=spf1 +a +mx -all", "opt": "", "value": "v=spf1 +a +mx -all"},
//        {"host": "ftp.domain.tld.", "displayHost": "ftp.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "ipv4.domain.tld.", "displayHost": "ipv4.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "mail.domain.tld.", "displayHost": "mail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "MX", "displayValue": "mail.domain.tld.", "opt": "10", "value": "mail.domain.tld."},
//        {"host": "webmail.domain.tld.", "displayHost": "webmail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "ns.domain.tld.", "displayHost": "ns.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"}
//    ]}},
//    {"command": "createPTRs", "ptr": {"ip_address": "1.2.3.4", "hostname": "domain.tld"}},
//    {"command": "createPTRs", "ptr": {"ip_address": "2002:5bcc:18fd:000c:0001:0002:0003:0004", "hostname": "domain.tld"}}
//]

foreach ($data as $record) {
    $zoneName = $record->zone->name;
    $recordsTTL = $record->zone->soa->ttl;
    switch ($record->command) {
            /**
         * Zone created or updated
         */
        case 'create':
        case 'update':
            updateDNSZone($zoneName, $record->zone);

        case 'delete':
    }
}
