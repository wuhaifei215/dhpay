<?php
$envFile = dirname(__FILE__) . '/../../../.env';
$env = array();
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

return array(
    'WEB_TITLE' => isset($env['WEB_TITLE']) ? $env['WEB_TITLE'] : '',
    'DOMAIN' => isset($env['DOMAIN']) ? $env['DOMAIN'] : '',
    'NOTIFY_DOMAIN' => isset($env['NOTIFY_DOMAIN']) ? $env['NOTIFY_DOMAIN'] : '',
    'MODULE_ALLOW_LIST'   => array('Home','User','Agent','Payment'),
    'URL_MODULE_MAP'  => array('agent'=>'user', 'user'=>'user'),
    'LOGINNAME' => isset($env['LOGINNAME']) ? $env['LOGINNAME'] : 'user',
    'API_DOMAIN' => isset($env['API_DOMAIN']) ? $env['API_DOMAIN'] : '',
    'LOG_API_URL' => isset($env['LOG_API_URL']) ? $env['LOG_API_URL'] : '',
);
