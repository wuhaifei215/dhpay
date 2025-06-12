<?php
namespace Think\Session\Driver;

class Redis {
    private $redis;
    private $expire;

    public function __construct() {
        // 读取配置参数
        $host = C('REDIS_HOST') ?: '127.0.0.1';
        $port = C('REDIS_PORT') ?: 6379;
        $auth = C('REDIS_PWD') ?: '';
        $this->expire = C('SESSION_EXPIRE') ?: 3600;

        // 连接 Redis
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
        if ($auth) {
            $this->redis->auth($auth);
        }
    }

    public function open($path, $name) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
        return (string)$this->redis->get($id);
    }

    public function write($id, $data) {
        return $this->redis->setex($id, $this->expire, $data);
    }

    public function destroy($id) {
        return $this->redis->delete($id);
    }

    public function gc($maxLifeTime) {
        return true;
    }
}
