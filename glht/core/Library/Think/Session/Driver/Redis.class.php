<?php
namespace Think\Session\Driver;

class Redis {
    protected $lifeTime     = 3600; // Session有效期
    protected $sessionName = '';
    protected $handle      = null;

    /**
     * 打开方法
     */
    public function open($savePath, $sessName) {
        $this->lifeTime     = C('SESSION_EXPIRE') ?: $this->lifeTime;
        $this->sessionName = $sessName;
        $this->handle = new \Redis();
        return $this->handle->connect(
            C('SESSION_REDIS_HOST') ?: '127.0.0.1',
            C('SESSION_REDIS_PORT') ?: 6379,
            C('SESSION_REDIS_TIMEOUT') ?: 0
        );
    }

    /**
     * 读取Session
     */
    public function read($sessID) {
        return $this->handle->get($this->sessionName.$sessID);
    }

    /**
     * 写入Session
     */
    public function write($sessID, $sessData) {
        return $this->handle->setex($this->sessionName.$sessID, $this->lifeTime, $sessData);
    }

    /**
     * 删除Session
     */
    public function destroy($sessID) {
        return $this->handle->delete($this->sessionName.$sessID);
    }

    /**
     * 垃圾回收
     */
    public function gc($maxLifeTime) {
        return true;
    }

    /**
     * 关闭连接
     */
    public function close() {
        $this->handle->close();
        return true;
    }
}
