<?php
namespace Think\Session\Driver;

use Think\Session;

class Redis extends Session {
    protected $lifeTime     = 3600; // Session有效期
    protected $sessionName = '';
    protected $handle      = null;

    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct($config = array()) {
        if (!extension_loaded('redis')) {
            E('Redis 扩展未安装');
        }

        $this->sessionName = isset($config['session_name']) ? $config['session_name'] : ini_get('session.name');
        $this->lifeTime    = isset($config['expire']) ? $config['expire'] : ini_get('session.gc_maxlifetime');

        // 合并配置参数（包括 auth）
        $config = array_merge(array(
            'host'       => '127.0.0.1',
            'port'       => 6379,
            'auth'       => '', // Redis 密码
            'select'     => 0,  // 数据库编号
            'timeout'    => 0,  // 连接超时
            'persistent' => false,
        ), $config);

        // 连接 Redis
        $this->handle = new \Redis();
        $func = $config['persistent'] ? 'pconnect' : 'connect';
        $this->handle->$func($config['host'], $config['port'], $config['timeout']);

        // 认证
        if (!empty($config['auth'])) {
            $this->handle->auth($config['auth']);
        }

        // 选择数据库
        $this->handle->select($config['select']);
    }

    // 其他必要方法（open/close/read/write/destroy/gc）
    public function open($savePath, $sessName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($sessId) {
        $data = $this->handle->get($this->sessionName . $sessId);
        return $data ? $data : '';
    }

    public function write($sessId, $sessData) {
        return $this->handle->setex($this->sessionName . $sessId, $this->lifeTime, $sessData);
    }

    public function destroy($sessId) {
        return $this->handle->delete($this->sessionName . $sessId);
    }

    public function gc($maxlifetime) {
        return true;
    }
}
