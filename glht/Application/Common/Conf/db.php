<?php
// 数据库配置
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
    'DB_TYPE'   => isset($env['DB_TYPE']) ? $env['DB_TYPE'] : 'mysql',
    'DB_HOST'   => isset($env['DB_HOST']) ? $env['DB_HOST'] : '',
    'DB_NAME'   => isset($env['DB_NAME']) ? $env['DB_NAME'] : '',
    'DB_USER'   => isset($env['DB_USER']) ? $env['DB_USER'] : '',
    'DB_PWD'    => isset($env['DB_PWD']) ? $env['DB_PWD'] : '',
    'DB_PORT'   => isset($env['DB_PORT']) ? $env['DB_PORT'] : '3306',
    'DB_PREFIX' => isset($env['DB_PREFIX']) ? $env['DB_PREFIX'] : 'pay_',

    'REDIS_HOST'   => isset($env['REDIS_HOST']) ? $env['REDIS_HOST'] : '127.0.0.1',
    'REDIS_PORT'   => isset($env['REDIS_PORT']) ? $env['REDIS_PORT'] : '6379',
    'REDIS_PWD'    => isset($env['REDIS_PWD']) ? $env['REDIS_PWD'] : '',
    'REDIS_SELECT' => isset($env['REDIS_SELECT']) ? $env['REDIS_SELECT'] : '0',
);
