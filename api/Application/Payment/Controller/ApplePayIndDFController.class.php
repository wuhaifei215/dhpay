<?php

namespace Payment\Controller;

class ApplePayIndDFController extends PaymentController
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
        
        $post_data = array(
            "app_id" => $config['appid'], //商户号
            "merchant_order_id" => $data['orderid'], //订单号
            "amount" => sprintf('%.2f', $data['money']),  //提现金额
            "notify_url" => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",      //异步通知地址
            "merchant_id" => $config['mch_id'], //商户号
            "payout_mode" => "INDIA_IMPS",
            "customer_account_type" => $data['bankzhiname'],
            "customer_account_no" => $data['banknumber'],
        );
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code, $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $data['orderid'] . "----提交地址", $config['exec_gateway']);    //日志
        
        // 记录初始执行时间
        $beginTime = microtime(TRUE);
        
        $returnContent = $this->http_post_data($config['exec_gateway'], $post_data);
        $result = json_decode($returnContent, true);
        // if($data['userid'] == 2){
            try{
                // 结束并输出执行时间
                $endTime = microtime(TRUE);
                $doTime = floor(($endTime-$beginTime)*1000);
                logApiAddPayment('订单提交三方' . $this->code, __METHOD__, $data['orderid'], $data['out_trade_no'], $config['exec_gateway'], $post_data, $result, $doTime, '0', '1', '2');
            }catch (\Exception $e) {
                // var_dump($e);
            }
        // }
        log_place_order($this->code, $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志

        // log_place_order($this->code, $data['orderid'] . "----状态：", $result['status']);    //日志
        //WAIT_CONFIRM	等待确认,WAIT_PAY	等待支付,PAY_ING	正在支付中,PAY_FAIL	支付失败,PAY_SUCCESS	支付成功
        if($result['data']['order_status'] === "WAIT_PAY" || $result['data']['order_status'] === "PAY_ING" || $result['data']['order_status'] === "WAIT_CONFIRM"){
            $return = ['status' => 1, 'msg' => '申请正常'];
        }elseif($result['data']['order_status'] === "PAY_FAIL"){
            $return = ['status' => 3, 'msg' => $result['message']];
        }else{
            $return = ['status' => 0, 'msg' => $result['message']];
        }
        return $return;
    }

    public function notifyurl()
    {
        $re_data = json_decode(file_get_contents("php://input"), true);
        //获取报文信息
        $orderid = $re_data['merchant_order_id'];
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));    //日志
        
        $tableName ='';
        $Wttklistmodel = D('Wttklist');
        $date = date('Ymd',strtotime(substr($orderid, 1, 8)));  //获取订单日期
        $tableName = $Wttklistmodel->getRealTableName($date);
        $Order = $Wttklistmodel->table($tableName)->where(['orderid' => $orderid])->find();
        
        // $Order = $this->selectOrder(['orderid' => $orderid]);
        if (!$Order) {
            log_place_order($this->code . '_notifyurl', $orderid . '----没有查询到Order！ ', $orderid);
            exit;
        }
        
        $config = M('pay_for_another')->where(['code' => $this->code,'id'=>$Order['df_id']])->find();

        //验证IP白名单
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = getRealIp();
        }
        $check_re = checkNotifyurlIp($ip, $config['notifyip']);
        if ($check_re !== true) {
            log_place_order($this->code . '_notifyurl', $orderid . "----IP异常", $ip.'==='.$config['notifyip']);    //日志
            $json_result = "IP异常:" . $ip.'==='.$config['notifyip'];
            try{
                logApiAddNotify($orderid, 1, $_REQUEST, $json_result);
            }catch (\Exception $e) {
                // var_dump($e);
            }
            return;
        }

        $sign = $this->get_sign($re_data, $config['signkey']);
        if ($sign === $re_data["sign"]) {
            if ($re_data['order_status'] == "PAY_SUCCESS") {     //WAIT_CONFIRM	等待确认,WAIT_PAY	等待支付,PAY_ING	正在支付中,PAY_FAIL	支付失败,PAY_SUCCESS	支付成功
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                // $this->handle($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
                $json_result = "success";
            } elseif ($re_data['order_status'] == "PAY_FAIL") {
                //代付失败
                $data = [
                    'memo' => '代付失败-' . $re_data['remark'],
                ];
                $this->changeStatus($Order['id'], 3, $data, $tableName);
                // $this->handle($Order['id'], 3, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
                $json_result = "success";
            }
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误: ', $sign);
            $json_result = "签名错误";
        }
        echo $json_result;
        try{
            logApiAddNotify($orderid, 1, $re_data, $json_result);
        }catch (\Exception $e) {
            // var_dump($e);
        }
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = [
            'app_id' => $config['appid'],
            'merchant_order_id' => $data['orderid'],
        ];
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_data($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['code'] === 200) {
            switch ($result['data']['order_status']) {     //WAIT_CONFIRM	等待确认,WAIT_PAY	等待支付,PAY_ING	正在支付中,PAY_FAIL	支付失败,PAY_SUCCESS	支付成功
                case "WAIT_CONFIRM":
                case "WAIT_PAY":
                case "PAY_ING":
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case "PAY_SUCCESS":
                    $return = ['status' => 2, 'msg' => '成功'];
                    break;
                case "PAY_FAIL":
                    $return = ['status' => 3, 'msg' => '失败','remark' => $result['message']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['message']];
        }
        return $return;
    }
    
    //账户余额查询
    public function queryBalance()
    {
        if (IS_AJAX) {
            $id = I('post.id', 1);
            $config = M('pay_for_another')->where(['id' => $id,'code' => $this->code])->find();
            $post_data = array(
                "app_id" => $config['appid'], //商户号
            );
            $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
            log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            $returnContent = $this->http_post_data($config['serverreturn'], $post_data);
            log_place_order($this->code . '_queryBalance', "提交地址", $config['serverreturn']);    //日志
            log_place_order($this->code . '_queryBalance', "返回", $returnContent);    //日志
            $result = json_decode($returnContent, true);
            if($result['code'] === 200){
                $balance = $result['data']['balance'];  //总金额
                $payout_balance = $result['data']['payout_balance'];  //总金额
                $html = <<<AAA
    <!-- CSS goes in the document HEAD or added to your external stylesheet -->
    <style type="text/css">
    table.hovertable {width: 200px;font-family: verdana,arial,sans-serif;font-size:11px;color:#333333;border-width: 1px;border-color: #999999;border-collapse: collapse;}
    table.hovertable th {background-color:#c3dde0;border-width: 1px;padding: 8px;border-style: solid;border-color: #a9c6c9;}
    table.hovertable tr {background-color:#f5f5f5;}
    table.hovertable td {border-width: 1px;padding: 8px;border-style: solid;border-color: #a9c6c9;}
    </style>
    <table class="hovertable">
    <tr><th>说明</th><th>值</th></tr>
    <tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>余额</td><td><b>$balance </b></td></tr>
    <tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>可用金额</td><td><b>$payout_balance </b></td></tr>
    </table>
AAA;
                $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
            }
            
        }
    }
    
    //账户余额查询
    public function queryBalance2($config)
    {
        $post_data = array(
            "app_id" => $config['app_id'], //商户号
            "time" => time(),
        );
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_queryBalance2', "提交", json_encode($post_data));    //日志
        $returnContent = $this->http_post_data($config['serverreturn'], $post_data);
        log_place_order($this->code . '_queryBalance2', "返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if($result['code'] === 200){
            $result_data['resultCode'] = "0";
            $result_data['balance'] = $result['data']['payout_balance'];
        }
        return $result_data;
    }

    //发送post请求，提交data字符串
    private function http_post_data($url, $postData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $response = curl_exec($ch);
//        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }

    /**
     * 签名算法
     * @param $data         请求数据
     * @param $md5Key       md5秘钥
     */
    private function get_sign($param, $key)
    {
        $signPars = "";
        ksort($param);
        $param['key'] = $key;
        foreach ($param as $k => $v) {
            if ("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $sign = strtolower(md5(rtrim($signPars,'&')));
        // log_place_order($this->code, $orderid . "----签名", rtrim($signPars,'&'));    //日志
        return $sign; //最终的签名

    }
}
