<?php

namespace Payment\Controller;

class InPayPakDFController extends PaymentController
{
    private $code = '';

    public function __construct()
    {
        $matches = [];
        preg_match('/([\da-zA-Z\_]+)Controller$/', __CLASS__, $matches);
        $this->code = $matches[1];
    }

    //代付提交
    public function PaymentExec($data, $config)
    {
        if($data['bankname']=='JAZZCASH'){
            $bankCode = '097';
        }elseif($data['bankname']=='EASYPAISA'){
            $bankCode = '096';
        }else{
            $return = ['status' => 3, 'msg' => '支付类型错误'];
            return $return;
        }
        $post_data = array(
            "mch_id" => $config['mch_id'], //商户号
            "out_trade_no" => $data['orderid'], //订单号
            "total_fee" => intval(sprintf("%.2f", $data['money']) * 100),  //提现金额
            "pay_type" => '869201',
            "body" => 'pk',
            "bank_account" => $data['banknumber'],    //账号
            "account_holder" => $data['bankfullname'],  //户名
            "deposit_bank_code" => $bankCode,
            "deposit_bank" => $bankCode,
            "acct_type" => 'bank',
            "province" => 'pk',
            "city" => 'pk',
            "sub_branch" => 'pk',
            "account_holder_id" => 'pk',
            "notify_url" => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",      //异步通知地址
        );
        $attach = json_decode($data['extends'],true);
        $post_data['account_holder_mobile'] = $attach['phone'];
        $signPars = $post_data['out_trade_no'] . $post_data['total_fee'] . $post_data['bank_account'] . $post_data['account_holder'] . $post_data['deposit_bank'] . $post_data['account_holder_mobile'] . $post_data['mch_id'] . $config['signkey'];
        $post_data['sign'] =strtolower(md5($signPars));
        log_place_order($this->code, $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $data['orderid'] . "----提交地址", $config['exec_gateway']);    //日志

        // 记录初始执行时间
        $beginTime = microtime(TRUE);

        $returnContent = $this->http_post_json($config['exec_gateway'], $post_data);
        $result = json_decode($returnContent, true);
        log_place_order($this->code, $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志

        try{

//            $redis = $this->redis_connect();
//            $userdfpost = $redis->get('userdfpost_' . $data['out_trade_no']);
//            $userdfpost = json_decode($userdfpost,true);
//
//            logApiAddPayment('商户提交', __METHOD__, $data['orderid'], $data['out_trade_no'], '/', $userdfpost, [], '0', '0', '1', '2');

            // 结束并输出执行时间
            $endTime = microtime(TRUE);
            $doTime = floor(($endTime-$beginTime)*1000);
            logApiAddPayment('订单提交三方' . $this->code . $this->code, __METHOD__, $data['orderid'], $data['out_trade_no'], $config['exec_gateway'], $post_data, $result, $doTime, '0', '1', '2');
        }catch (\Exception $e) {
            // var_dump($e);
        }

        // log_place_order($this->code, $data['orderid'] . "----状态：", $result['status']);    //日志
        if ($result['result_code'] === 0) {
            $return = ['status' => 1, 'msg' => '申请正常'];
        }else{
            $return = ['status' => 3, 'msg' => '申请失败--'. $result['err_msg']];
        }
        return $return;
    }

    public function notifyurl()
    {
        $re_data = $_REQUEST;
        //获取报文信息
        $orderid = $re_data['out_trade_no'];
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", json_encode($re_data, JSON_UNESCAPED_UNICODE));    //日志

        $tableName ='';
        $Wttklistmodel = D('Wttklist');
        $date = date('Ymd',strtotime(substr($orderid, 1, 8)));  //获取订单日期
        $tableName = $Wttklistmodel->getRealTableName($date);
        $Order = $Wttklistmodel->table($tableName)->where(['orderid' => $orderid])->find();

        if (!$Order) {
            log_place_order($this->code . '_notifyurl', $orderid . '----没有查询到Order！ ', $orderid);
            exit;
        }

        $config = M('pay_for_another')->where(['code' => $this->code,'id'=>$Order['df_id']])->find();
        //验证IP白名单
        if ($this->verifyNotifyIp($config['notifyip'], $orderid) !== true) {
            log_place_order($this->code . '_notifyurl', $orderid . "----IP异常", $this->getClientIp().'==='.$config['notifyip']);    //日志
            $json_result = "IP异常:" . $this->getClientIp().'==='.$config['notifyip'];
            try{
                logApiAddNotify($orderid, 1, $re_data, $json_result);
            }catch (\Exception $e) {
                // var_dump($e);
            }
            return;
        }

        $signPars = $re_data['out_trade_no'] . $re_data['transaction_id'] . $re_data['time_end'] . $re_data['mch_id'] . $config['signkey'];
        $sign =strtolower(md5($signPars));
        if ($sign === $re_data["sign"]) {
            if ($re_data['status'] === "0") {
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                // $this->handle($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
            } elseif ($re_data['status'] === "1") {
                $data = [
                    'memo' => '代付失败',
                ];
                $this->changeStatus($Order['id'], 3, $data, $tableName);
                // $this->handle($Order['id'], 3, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
            }
            $json_result =  "success";
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误: ', $sign);
            $json_result = "sign fail";
        }
        echo $json_result;
        try{
            logApiAddNotify($orderid, 1, $re_data, $json_result);
        }catch (\Exception $e) {
            // var_dump($e);
        }
    }

    //账户余额查询
    public function queryBalance()
    {
        if (IS_AJAX) {
            $id = I('post.id', 1);
            $config = M('pay_for_another')->where(['id' => $id,'code' => $this->code])->find();
            $post_data = array(
                "mch_id" => $config['mch_id'], //商户号
            );
            $signPars = $post_data['mch_id'] . $config['signkey'];
            $post_data['sign'] =strtolower(md5($signPars));
            log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
            log_place_order($this->code . '_queryBalance', "返回", $returnContent);    //日志
            $result = json_decode($returnContent, true);

            $account_amount = $result['account_amount'] / 100;  //可用金额
            $total_amount = $result['total_amount'] / 100;  //总余额
            $frozen_amount = $result['frozen_amount'] / 100;  //冻结余额
            $html = <<<AAA
<!-- CSS goes in the document HEAD or added to your external stylesheet -->
<style type="text/css">
table.hovertable {width: 300px;font-family: verdana,arial,sans-serif;font-size:11px;color:#333333;border-width: 1px;border-color: #999999;border-collapse: collapse;}
table.hovertable th {background-color:#c3dde0;border-width: 1px;padding: 8px;border-style: solid;border-color: #a9c6c9;}
table.hovertable tr {background-color:#f5f5f5;}
table.hovertable td {border-width: 1px;padding: 8px;border-style: solid;border-color: #a9c6c9;}
</style>
<table class="hovertable">
<tr><th>说明</th><th>值</th></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>可用金额</td><td><b>$account_amount</b></td></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>总余额</td><td><b>$total_amount</b></td></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>冻结余额</td><td><b>$frozen_amount</b></td></tr>
</table>
AAA;
            $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
        }
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = array(
            "mch_id" => $config['mch_id'], //商户号
            "out_trade_no" => $data['orderid'],
        );
        $signPars = $post_data['out_trade_no'] . $config['mch_id'] . $config['signkey'];
        $post_data['sign'] =strtolower(md5($signPars));
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
        $result = json_decode($returnContent, true);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志
        if ($result['result_code']) {
            switch ($result['result_code']) {
                case 0:
                case 1:
                case 2:
                case -2:
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case 3:
                    $return = ['status' => 2, 'msg' => '成功'];
                    break;
                case 4:
                case 5:
                    $return = ['status' => 3, 'msg' => '失败','remark' => $result['msg']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['code']];
        }
        return $return;
    }

    //发送post请求，提交json字符串
    private function http_post_json($url, $postData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8'));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
