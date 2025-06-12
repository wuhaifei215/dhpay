<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        // 获取服务器IP
        $serverIP = $_SERVER['SERVER_ADDR'];
        echo $serverIP;
        $this->show('请使用正确的接口地址！','utf-8');
    }
}