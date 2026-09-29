<?php

namespace Payment\Controller;

class GlobalPayDFController extends PaymentController
{
    private $code = '';
    private $bankCode = [
        'bpi' => 'BPI',
        'cimb' => 'CPI',
        'pdax' => 'PDAX',
        'unionbank' => 'UBP',
        'bdouni' => 'BDO',
        'gotyme' => 'GOT',
        'eastwest_rural' => 'KOMO',
        'aub' => 'AUB',
        'seabank' => 'SB',
        'mayabank' => 'MYA',
        'rcbc' => 'RCBC',
        'landbank' => 'LBOB',
        'security' => 'SBC',
        'chinabank' => 'CBC',
        'pnbk' => 'PNB',
        'psbank' => 'PSB',
        'maybank' => 'MBP',
        'palawanpay' => 'PPS',
        'cebuanal' => 'CLB',
        'robinsons' => 'RBB',
//        'DCPay/COINS.PH' => 'COINS',
//        'Metropolitan Bank and Trust Co' => 'MBT',
    ];

    public function __construct()
    {
        $matches = [];
        preg_match('/([\da-zA-Z\_]+)Controller$/', __CLASS__, $matches);
        $this->code = $matches[1];
    }

    //代付提交
    public function PaymentExec($data, $config)
    {
        if($data['type'] == 'Gcash'){
            $bankName = 'GCASH';
        } elseif ($data['type'] == 'Maya'){
            $bankName = 'PMP';
        } elseif ($data['type'] == 'Bank'){
            $bankName='';
            foreach ($this->bankCode as $k => $v) {
                if ($k == $data['bankname']) {
                    $bankName = $v;
                }
            }
            if($bankName == ''){
                $return = ['status' => 3, 'msg' => '该银行不支持！'];
                return $return;
            }
        } else {
            $return = ['status' => 3, 'msg' => '支付类型错误'];
            return $return;
        }
        $tradeType = '31';
        $productId = '310';
        $bankMark = $bankName;
        $attach = [
            "bankAccount" => $data['bankfullname'],  //户名
            "bankCard" => $data['banknumber'],    //账号
            "bankMark" => $bankMark,
            "bankName" => $bankName
        ];
        $post_data = array(
            "alliesNo" => $config['mch_id'], //商户号
            "tradeNo" => $data['orderid'], //订单号
            "orderAmount" =>sprintf('%.2f', $data['money']),  //提现金额
            "tradeType" =>$tradeType,
            "noticeUrl" => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",      //异步通知地址
            "productId" =>$productId,
            "attach" => base64_encode(json_encode($attach))
        );
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code, $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $data['orderid'] . "----提交地址", $config['exec_gateway']);    //日志

        // 记录初始执行时间
        $beginTime = microtime(TRUE);

        $returnContent = $this->http_post_json($config['exec_gateway'], $post_data);
        $result = json_decode($returnContent, true);
        log_place_order($this->code, $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志

        // log_place_order($this->code, $data['orderid'] . "----状态：", $result['status']);    //日志
        if($result['code'] === "10000"){
            $return = ['status' => 1, 'msg' => '申请正常'];
        }else{
            $return = ['status' => 0, 'msg' => $result['info']];
        }
        try{
            $redis = $this->redis_connect();
            $userdfpost = $redis->get('userdfpost_' . $data['out_trade_no']);
            $userdfData = json_decode($userdfpost,true);

            $forTime = floor((microtime(TRUE)-$userdfData['get_time'])*1000);
            logApiAddPayment('商户提交', __METHOD__, $data['orderid'], $data['out_trade_no'], '/', $userdfData, $return, $forTime, '0', '1', '2');

            // 结束并输出执行时间
            $endTime = microtime(TRUE);
            $doTime = floor(($endTime-$beginTime)*1000);
            logApiAddPayment('订单提交三方' . $this->code, __METHOD__, $data['orderid'], $data['out_trade_no'], $config['exec_gateway'], $post_data, $result, $doTime, '0', '1', '2');
        }catch (\Exception $e) {
            // var_dump($e);
        }
        return $return;
    }

    public function notifyurl()
    {
        $re_data = json_decode(file_get_contents('php://input'), true);
        //获取报文信息
        $orderid = $re_data['tradeNo'];
        // log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", json_encode($re_data, JSON_UNESCAPED_UNICODE));    //日志
        if (!$orderid) return;

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

        $sign = $this->get_sign($re_data, $config['signkey']);

        if ($sign == $re_data["sign"]) {
            if ($re_data['tradeStatus'] === "1" || $re_data['tradeStatus'] === "4") {     //订单状态
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
            } elseif ($re_data['tradeStatus'] === "2") {
                //代付失败
                $data = [
                    'memo' => '代付失败--'. $re_data['remark'],
                ];
                $this->changeStatus($Order['id'], 3, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
            }
            $json_result = 'success';
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误: ', $sign);
            $json_result = 'sign error';
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
            $config = M('pay_for_another')->where(['code' => $this->code])->find();
            $post_data = array(
                "alliesNo" => $config['mch_id'], //商户号
                "tradeType" =>"31",
            );
            $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
            log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
            log_place_order($this->code . '_queryBalance', "返回", $returnContent);    //日志
            $result = json_decode($returnContent,true);
            $balance_Gcash = $result['balance'];  //可用金额
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
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>Gcash 可用金额</td><td><b>$balance_Gcash </b></td></tr>
</table>
AAA;
            $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
        }
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = [
            "alliesNo" => $config['mch_id'], //商户号
            'outTradeNo' => $data['orderid'],
        ];
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_json($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['code'] === "10000") {
            switch ($result['tradeStatus']) {       //01:待结算06:清算中07:清算完成08:清算失败09:清算撤销
                case "0":
                case "3":
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case "1":
                case "4":
                    $return = ['status' => 2, 'msg' => '成功'];
                    break;
                case "2":
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
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
            if ("" != $v && "sign" != $k && "info" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $sign = strtoupper(md5(rtrim($signPars,'&') ));
//        log_place_order($this->code, "----签名", rtrim($signPars,'&'));    //日志
        return $sign; //最终的签名
    }
}
