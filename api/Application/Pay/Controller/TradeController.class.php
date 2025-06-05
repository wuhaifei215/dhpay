<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-10-30
 * Time: 21:24
 */
namespace Pay\Controller;

class TradeController extends PayController
{
    private $userid;
    private $apikey;
    public function __construct()
    {
        parent::__construct();
        $memberid = I("request.pay_memberid",0,'intval') - 10000;
        if (empty($memberid) || $memberid<=0) {
            $this->showmessage("不存在的商户编号!");
        }
        $this->userid = $memberid;
        $fans = M('Member')->where(['id'=>$this->userid])->find();
        if(!$fans){
            $this->showmessage('商户不存在');
        }
        $this->apikey = $fans['apikey'];
    }

    //订单查询
    public function query()
    {
        $out_trade_id = I('request.pay_orderid', '', 'string,strip_tags,htmlspecialchars');
        if(!$out_trade_id){
            $this->showmessage("交易订单号不能为空！");
        }
        $pay_applydate = I('request.pay_applydate', '', 'string,strip_tags,htmlspecialchars');
        if(!$pay_applydate){
            $this->showmessage("订单提交日期不能为空！");
        }
        $pay_memberid = I("request.pay_memberid", 0, 'intval');
        if(!$pay_memberid) {
            $this->showmessage("商户号不能为空！");
        }
        $request = [
            'pay_memberid'=>$pay_memberid,
            'pay_orderid'=>$out_trade_id,
            'pay_applydate'=>$pay_applydate,
        ];
        $signature = $this->createSign($this->apikey,$request);
        // echo $signature;
        $sign = I('request.pay_md5sign');
        if($signature != $sign){
            ksort($_POST);
            $md5str = "";
            foreach ($_POST as $key => $val) {
                if (!empty($val) && $key != 'pay_md5sign') {
                    $md5str = $md5str . $key . "=" . $val . "&";
                }
            }
            $sign = strtoupper(md5($md5str . "key=" . $this->apikey));
            $result = [
                '提醒' => '请比对拼接顺序及签名',
                'POST数据' => $_POST,
                '拼接顺序' => substr($md5str, 0, strlen($md5str) - 1),
                '签名' => $sign,
            ];
            $this->showmessage('签名验证失败', $result);
        }
        //获取redis里的订单信息
        // $redis = $this->redis_connect();
        // $order = $redis->get($out_trade_id);
        // if(!$order || empty($order)){
            $where = [
                'pay_memberid'=>$pay_memberid,
                'out_trade_id'=>$out_trade_id,
                'pay_applydate'=>['between',[strtotime($pay_applydate . ' 00:00:00'),strtotime($pay_applydate . ' 23:59:59')]],
            ];
            $OrderModel = D('Order'); 
            $order = $OrderModel->table($OrderModel->getRealTableName($pay_applydate))->where($where)->find();
            // echo $OrderModel->table($OrderModel->getRealTableName($pay_applydate))->getLastSql();
            if(!$order){
                $where = [
                    'pay_memberid'=>$pay_memberid,
                    'out_trade_id'=>$out_trade_id,
                    'pay_applydate'=>['between',[strtotime($pay_applydate . ' 00:00:00'),strtotime($pay_applydate . ' 23:59:59') + 86400]],
                ];
                $t_date = date('Y-m-d',strtotime($pay_applydate . ' 23:59:59') + 86400);
                $OrderModel = D('Order'); 
                try {
                    $order = $OrderModel->table($OrderModel->getRealTableName($t_date))->where($where)->find();
                } catch (\Exception $e) {
                    
                }
                
            }
        // }
            
        if(!$order){
            $this->showmessage('不存在的交易订单.');
        }
        if($order['pay_status']==0){
            $refCode = '3';
            $refMsg = "处理中";
        }elseif ($order['pay_status'] ==1 || $order['pay_status'] == 2){
            $refCode = '1';
            $refMsg = "成功";
        }else{
            $refCode = '8';
            $refMsg = "未知状态";
        }
        
        $return = [
            'status' => 'success',
            'msg' => '请求成功',
            'orderType' => 'ds',
            'mchid' => $pay_memberid,
            'out_trade_no' => $order['out_trade_id'],
            'amount' => $order['pay_amount'],
            'transaction_id' => $order['pay_orderid'],
            'refCode' => $refCode,
            'refMsg' => $refMsg,
        ];

        $return['sign'] = $this->createSign($this->apikey,$return);
        echo json_encode($return);
    }
}