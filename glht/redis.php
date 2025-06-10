<?php
$redis = new Redis();
try {
    $redis->connect('127.0.0.1', 52940);
    $redis->auth('Yun!x@y#z99pay'); // 如果有密码
    $redis->set('test_key', 'Hello Redis');
    echo $redis->get('test_key');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>