<?php

namespace Log\Controller;

use \think\Controller;
use Think\Log;

/**
 * @author mapeijian
 * @date   2018-06-06
 */
class ApiController extends Controller
{
    protected $memberid;
    protected $order_id;
    protected $title;
    protected $out_trade_id;
    protected $business_type;
    protected $method;
    protected $request_method;
    protected $operator_type;
    protected $oper_url;
    protected $oper_param;
    protected $json_result;
    protected $status;
    protected $error_msg;
    protected $ReceiptLogmodel;
    protected $PaymentLogmodel;
    protected $NotifyLogmodel;
    protected $tableName;
    
    public function __construct()
    {
        parent::__construct();
        $this->firstCheckParams(); //初步验证参数
    }
    public function index()
    {
    }
    public function getOrders()
    {
        $this->memberid = I("post.memberid");
        if (empty($this->memberid) || $this->memberid <= 0) {
            $this->showmessage("请输入商户编号");
        }
        $this->order_id = I('post.order_id', '');
        if (!$this->order_id) {
            $this->showmessage('订单号异常！');
        }
        $this->type = I('post.type', '');
        if (!$this->type) {
            $this->showmessage('查询类型异常！');
        }
        $post_data = I('post.');
        if($this->type == 1){
            $order_data = [
                'getOperLog' => $this->getReceiptLog(),
                'getNotifyLog' => $this->getNotifyLog(),
            ];
        }elseif($this->type == 2){
            $order_data = [
                'getOperLog' => $this->getPaymentLog(),
                'getNotifyLog' => $this->getNotifyLog(),
            ];
        }
        echo json_encode($order_data, 320);
    }
    public function getAddLog()
    {
        $where=[];
        $this->memberid = I("post.memberid");
        if (!empty($this->memberid)) {
            $where['user_id'] = $this->memberid;
        }
        $this->order_id = I('post.order_id', '');
        if ($this->order_id) {
            $where['order_id'] = $this->order_id;
        }
        $this->business_type = I("request.business_type", '');
        if ($this->business_type) {
            $where['business_type'] = $this->business_type;
        }
        $this->oper_ip = I('post.oper_ip', '');
        if ($this->oper_ip) {
            $where['oper_ip'] = $this->oper_ip;
        }
        $this->create_time = I('post.create_time', '');
        $create_time = explode('|',$this->create_time);
        if (!$this->create_time) {
            $this->showmessage('请输入查询时间异常！');
        }
        if ($this->create_time) {
            $where['create_time'] = ['between',$create_time];
        }
        
        $this->getadd();
        $log_data = $this->AddLogmodel->table($this->tableName)->where($where)->limit('0,30')->order('id DESC')->select();
        $order_data = [
            'status' => 'success',
            'getAddLog' => $log_data,
        ];
        echo json_encode($order_data, 320);
    }
    
    protected function firstCheckParams()
    {
        // $post_data = I('post.');
        // log_place_order('log', "post提交", json_encode($post_data, 320));    //日志
        $this->memberid = I("request.memberid");
        if (empty($this->memberid) || $this->memberid <= 0) {
            $this->showmessage("请输入商户编号!");
        }
        $this->order_id = I('post.order_id', '');
        // if (!$this->order_id) {
        //     $this->showmessage('订单号异常！');
        // }
    }
        
    protected function secondCheckParams()
    {
        $this->title = I("request.title", '');
        if (!$this->title) {
            $this->showmessage('模块标题异常！');
        }
        $this->out_trade_id = I("request.out_trade_id", '');
        if (!$this->out_trade_id) {
            $this->showmessage('下游订单号异常！');
        }
        $this->business_type = I("request.business_type", '');
        if (!$this->business_type) {
            $this->showmessage('业务类型异常！');
        }
        $this->method = I("request.method", '');
        if (!$this->method) {
            $this->showmessage('程序方法名称异常！');
        }
        $this->request_method = I("request.request_method", '');
        if (!$this->request_method) {
            $this->showmessage('请求方式异常！');
        }
        $this->operator_type = I("request.operator_type", '');
        if (!$this->operator_type) {
            $this->showmessage('操作类别异常！');
        }
        $this->oper_url = I("request.oper_url", '');
        if (!$this->oper_url) {
            $this->showmessage('请求URL异常！');
        }
        $this->oper_param = I("request.oper_param", '');
        // if (!$this->oper_param) {
        //     $this->showmessage('请求参数异常！');
        // }
        $this->json_result = I("request.json_result");
        // if (!$this->json_result) {
        //     $this->showmessage('返回参数异常！');
        // }
        $this->status = I("request.status");
        // if (!$this->status) {
        //     $this->showmessage('操作状态异常！');
        // }
        $this->error_msg = I("request.error_msg");
        // if (!$this->error_msg) {
        //     $this->showmessage('错误消息异常！');
        // }
        
        $this->cost_time = I("request.cost_time");
        // if (!$this->cost_time) {
        //     $this->showmessage('执行时间异常！');
        // }
        $this->create_time = I("request.create_time");
        if (!$this->create_time) {
            $this->showmessage('创建时间异常！');
        }
        
    }
        
    protected function thirdCheckParams()
    {
        $this->title = I("request.title", '');
        if (!$this->title) {
            $this->showmessage('模块标题异常！');
        }
        $this->method = I("request.method", '');
        if (!$this->method) {
            $this->showmessage('程序方法名称异常！');
        }
        $this->business_type = I("request.business_type", '');
        if (!$this->business_type) {
            $this->showmessage('业务类型异常！');
        }
        $this->oper_param = I("request.oper_param", '');
        if (!$this->oper_param) {
            $this->showmessage('请求参数异常！');
        }
        $this->json_result = I("request.json_result", '');
        // if (!$this->json_result) {
        //     $this->showmessage('返回参数异常！');
        // }
        $this->oper_ip = I("request.oper_ip", '');
        if (!$this->oper_ip) {
            $this->showmessage('oper_ip异常！');
        }
        $this->create_time = I("request.create_time");
        if (!$this->create_time) {
            $this->showmessage('创建时间异常！');
        }
        
    }
    
    
    /*************************商户提交*************************/
    //获取商户提交 数据库连接信息
    protected function getadd(){
        $date = date('Ymd',strtotime(substr($this->order_id, 0, 8)));  //获取订单日期
        $this->AddLogmodel = D('AddLog');
        if($date != ''){
            $this->tableName = $this->AddLogmodel->getRealTableName($date);
        }else{
            $this->tableName = 'CustomAddLog';
        }
    }
    
    public function addLog() {
        $this->thirdCheckParams();
        $log = [
            'title' => $this->title,        //主题
            'method' => $this->method,      //程序方法名称
            'user_id' => $this->memberid,   //用户id
            'order_id' => $this->order_id,   //订单号
            'oper_param' => json_encode($this->oper_param, 320),      //请求参数
            // 'json_result' => json_encode($this->json_result, 320),    //返回参数
            'oper_ip' => $this->oper_ip,        //主机地址
            'create_time' => $this->create_time,       //创建时间
            'business_type' => $this->business_type,    //业务类型（1代收 2代付）
        ];
        try {
            $this->getadd();
            $res = $this->AddLogmodel->table($this->tableName)->add($log);
            var_dump($res);
            echo 'OJBK';
        } catch (\Exception $e) {
            echo $e;
        }
    }
    
    
    /*************************入款*************************/
    //获取入款 数据库连接信息
    protected function getReceipt(){
        $date = date('Ymd',strtotime(substr($this->order_id, 0, 8)));  //获取订单日期
        $this->ReceiptLogmodel = D('ReceiptLog');
        if($date != ''){
            $this->tableName = $this->ReceiptLogmodel->getRealTableName($date);
        }else{
            $this->tableName = 'CustomReceiptLog';
        }
    }
    
    protected function getReceiptLog() {
        $where = [
            'user_id' =>$this->memberid,
            'order_id' => $this->order_id
        ];
        $this->getReceipt();
        $log_data = $this->ReceiptLogmodel->table($this->tableName)->where($where)->select();
        if(!empty($log_data)){
            return $log_data;
        }
    }
    public function addReceiptLog() {
        $this->secondCheckParams();
        $log = [
            'title' => $this->title,        //主题
            'user_id' => $this->memberid,   //用户id
            'order_id' => $this->order_id,   //订单号
            'out_trade_id' => $this->out_trade_id,  //下游订单号
            'business_type' => $this->business_type,    //业务类型（0其它 1下单 2回调）
            'method' => $this->method,      //程序方法名称
            'request_method' => $this->request_method,  //请求方式
            'operator_type' => $this->operator_type,  //操作类别（0其它 1后台 2Api）
            'oper_url' => $this->oper_url,      //请求URL
            'oper_ip' => $this->oper_ip,        //主机地址
            'oper_param' => json_encode($this->oper_param, 320),      //请求参数
            'json_result' => json_encode($this->json_result, 320),    //返回参数
            'status' => $this->status,      //操作状态（0正常 1异常）
            'error_msg' => $this->error_msg,    //错误消息
            'cost_time' => $this->cost_time,
            'create_time' => $this->create_time,       //创建时间
        ];
        $this->getReceipt();
        $res = $this->ReceiptLogmodel->table($this->tableName)->add($log);
        echo 'OJBK';
    }
    //记录日志
    public function editReceiptLog() {
        $this->secondCheckParams();
        $log = [
            'title' => $this->title,        //主题
            'user_id' => $this->memberid,   //用户id
            'order_id' => $this->order_id,   //订单号
            'out_trade_id' => $this->out_trade_id,  //下游订单号
            'business_type' => $this->business_type,    //业务类型（0其它 1下单 2回调）
            'method' => $this->method,      //程序方法名称
            'request_method' => $this->request_method,  //请求方式
            'operator_type' => $this->operator_type,  //操作类别（0其它 1后台 2Api）
            'oper_url' => $this->oper_url,      //请求URL
            'oper_ip' => $this->oper_ip,        //主机地址
            'oper_param' => json_encode($this->oper_param, 320),      //请求参数
            'json_result' => json_encode($this->json_result, 320),    //返回参数
            'status' => $this->status,      //操作状态（0正常 1异常）
            'error_msg' => json_encode($this->error_msg, 320),    //错误消息
            'create_time' => date('Y-m-d H:i:s'),       //创建时间
        ];
        $where = [
            'order_id' => $this->order_id
        ];
        $this->getReceipt();
        $res = $this->ReceiptLogmodel->table($this->tableName)->where($where)->save($log);
        echo 'OJBK';
    }
    
    /*************************出款*************************/
    //获取出款 数据库连接信息
    protected function getPayment(){
        $date = date('Ymd',strtotime(substr($this->order_id, 1, 8)));  //获取订单日期
        $this->PaymentLogmodel = D('PaymentLog');
        $this->tableName = $this->PaymentLogmodel->getRealTableName($date);
    }
    
    protected function getPaymentLog() {
        $where = [
            'user_id' =>$this->memberid,
            'order_id' => $this->order_id
        ];
        $this->getPayment();
        $log_data = $this->PaymentLogmodel->table($this->tableName)->where($where)->select();
        if(!empty($log_data)){
            return $log_data;
        }
    }
    public function addPaymentLog() {
        $this->secondCheckParams();
        $log = [
            'title' => $this->title,        //主题
            'user_id' => $this->memberid,   //用户id
            'order_id' => $this->order_id,   //订单号
            'out_trade_id' => $this->out_trade_id,  //下游订单号
            'business_type' => $this->business_type,    //业务类型（0其它 1下单 2回调）
            'method' => $this->method,      //程序方法名称
            'request_method' => $this->request_method,  //请求方式
            'operator_type' => $this->operator_type,  //操作类别（0其它 1后台 2Api）
            'oper_url' => $this->oper_url,      //请求URL
            'oper_ip' => $this->oper_ip,        //主机地址
            'oper_param' => json_encode($this->oper_param, 320),      //请求参数
            'json_result' => json_encode($this->json_result, 320),    //返回参数
            'status' => $this->status,      //操作状态（0正常 1异常）
            'error_msg' => $this->error_msg,    //错误消息
            'cost_time' => $this->cost_time,
            'create_time' => $this->create_time,       //创建时间
        ];
        $this->getPayment();
        $res = $this->PaymentLogmodel->table($this->tableName)->add($log);
        echo 'OJBK';
    }
    //记录日志
    public function editPaymentLog() {
        $this->secondCheckParams();
        $log = [
            'title' => $this->title,        //主题
            'user_id' => $this->memberid,   //用户id
            'order_id' => $this->order_id,   //订单号
            'out_trade_id' => $this->out_trade_id,  //下游订单号
            'business_type' => $this->business_type,    //业务类型（0其它 1下单 2回调）
            'method' => $this->method,      //程序方法名称
            'request_method' => $this->request_method,  //请求方式
            'operator_type' => $this->operator_type,  //操作类别（0其它 1后台 2Api）
            'oper_url' => $this->oper_url,      //请求URL
            'oper_ip' => $this->oper_ip,        //主机地址
            'oper_param' => json_encode($this->oper_param, 320),      //请求参数
            'json_result' => json_encode($this->json_result, 320),    //返回参数
            'status' => $this->status,      //操作状态（0正常 1异常）
            'error_msg' => json_encode($this->error_msg, 320),    //错误消息
            'create_time' => date('Y-m-d H:i:s'),       //创建时间
        ];
        $where = [
            'order_id' => $this->order_id
        ];
        $this->getPayment();
        $res = $this->PaymentLogmodel->table($this->tableName)->where($where)->save($log);
        echo 'OJBK';
    }
    
    
    /*************************入款回调*************************/
    //获取入款回调 数据库连接信息
    protected function getNotify(){
        $date = date('Ymd',strtotime(substr($this->order_id, 0, 8)));  //获取订单日期
        $this->NotifyLogmodel = D('NotifyLog');
        $this->tableName = $this->NotifyLogmodel->getRealTableName($date);
    }
    //获取出款回调 数据库连接信息
    protected function getDFNotify(){
        $date = date('Ymd',strtotime(substr($this->order_id, 1, 8)));  //获取订单日期
        $this->NotifyLogmodel = D('NotifyLog');
        $this->tableName = $this->NotifyLogmodel->getRealTableName($date);
    }
    
    protected function getNotifyLog() {
        $type = I("request.type", '');
        if($type ==1){
            $this->getNotify();
        }else{
            $this->getDFNotify();
        }
        $where = [
            'user_id' =>$this->memberid,
            'order_id' => $this->order_id
        ];
        $log_data = $this->NotifyLogmodel->table($this->tableName)->where($where)->select();
        if(!empty($log_data)){
            return $log_data;
        }
    }
    public function addNotifyLog() {
        $this->out_trade_id = I("request.out_trade_id", '');
        // if (!$this->out_trade_id) {
        //     $this->showmessage('下游订单号异常！');
        // }
        $this->type = I("request.type", '');
        $this->oper_param = I("request.oper_param", '');
        // if (!$this->oper_param) {
        //     $this->showmessage('请求参数异常！');
        // }
        $this->json_result = I("request.json_result");
        // if (!$this->json_result) {
        //     $this->showmessage('返回参数异常！');
        // }
        $this->status = I("request.status");
        // if (!$this->status) {
        //     $this->showmessage('操作状态异常！');
        // }
        $this->create_time = I("request.create_time");
        if (!$this->create_time) {
            $this->showmessage('创建时间异常！');
        }
        
        $log = [
            'user_id' => $this->memberid,   //用户id
            'order_id' => $this->order_id,   //订单号
            'out_trade_id' => $this->out_trade_id,  //下游订单号
            'type' => $this->type,    //业务类型（0其它 1下单 2回调）
            'oper_param' => json_encode($this->oper_param, 320),      //请求参数
            'json_result' => json_encode($this->json_result, 320),    //返回参数
            'status' => $this->status,      //操作状态（0正常 1异常）
            'create_time' => $this->create_time,       //创建时间
        ];
        // log_place_order('addNotifyLog', "log", json_encode($log, 320));    //日志
        $type = I("request.type", '');
        if($type ==1){
            $this->getDFNotify();
        }else{
            $this->getNotify();
        }
        $res = $this->NotifyLogmodel->table($this->tableName)->add($log);
        echo 'OJBK';
    }
    //记录日志
    public function editNotifyLog() {
        $this->secondCheckParams();
        $log = [
            'title' => $this->title,        //主题
            'user_id' => $this->memberid,   //用户id
            'order_id' => $this->order_id,   //订单号
            'out_trade_id' => $this->out_trade_id,  //下游订单号
            'business_type' => $this->business_type,    //业务类型（0其它 1下单 2回调）
            'method' => $this->method,      //程序方法名称
            'request_method' => $this->request_method,  //请求方式
            'operator_type' => $this->operator_type,  //操作类别（0其它 1后台 2Api）
            'oper_url' => $this->oper_url,      //请求URL
            'oper_ip' => $this->oper_ip,        //主机地址
            'oper_param' => json_encode($this->oper_param, 320),      //请求参数
            'json_result' => json_encode($this->json_result, 320),    //返回参数
            'status' => $this->status,      //操作状态（0正常 1异常）
            'error_msg' => json_encode($this->error_msg, 320),    //错误消息
            'create_time' => date('Y-m-d H:i:s'),       //创建时间
        ];
        $where = [
            'order_id' => $this->order_id
        ];
        $type = I("request.type", '');
        if($type ==1){
            $this->getDFNotify();
        }else{
            $this->getNotify();
        }
        $res = $this->NotifyLogmodel->table($this->tableName)->where($where)->save($log);
        echo 'OJBK';
    }


    /*************************辅助方法*************************/
        /**
     * 错误返回
     * @param string $msg
     * @param array $fields
     */
    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => 'error', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }
    
    protected function redis_connect(){
        //创建一个redis对象
        $redis = new \Redis();
        //连接 Redis 服务
        $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
        //密码验证
        $redis->auth(C('REDIS_PWD'));
        return $redis;
    }
}