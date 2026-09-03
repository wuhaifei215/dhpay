<?php

namespace Payment\Controller;

class SSGPayMayaDFController extends PaymentController
{
    private $code = '';
    
    // private $bankCode = [
    //     'gcash' => 'gcash',
    //     'bpi' => 'bpi',
    //     'bdouni' => 'Unibank',
    //     'metrobank' => 'mbt',
    //     'landbank' => 'LBOB',
    //     'security' => 'SBC',
    //     'unionbank' => 'UBP',
    //     'pnb' => 'PNB',
    //     'chinabank' => 'CBC',
    //     'easypay' => 'EWBC',
    //     'rcbc' => 'RCBC',
    //     'producers' => 'PSB',
    //     'aub' => 'AUB',
    //     'dbp' => 'DBP',
    //     'allbank' => 'AB',
    //     'guinobatan' => 'Asenso',
    //     'bpibanko' => 'BK',
    //     'bankofcommerce' => 'BC',
    //     'cisbayad' => 'Bayad',
    //     'bdo' => 'BNB',
    //     'camalig' => 'CB',
    //     'cardbank' => 'CARD Bank',
    //     'cebuanal' => 'CLB',
    //     'chinabanksavings' => 'CBS',
    //     'ctbc' => 'CTBC',
    //     'dumaguetebankx' => 'DCDB',
    //     'dungganon' => 'DB',
    //     'equicom' => 'ESB',
    //     'grabpay' => 'GP',
    //     'isla' => 'ISLA',
    //     'zybi' => 'JC',
    //     'eastwest_rural' => 'Komo',
    //     'legazpi' => 'LSB',
    //     'malayan' => 'MBS',
    //     'mcb' => 'MCCB',
    //     'netbank' => 'NB',
    //     'omnipay' => 'OP',
    //     'partnerrb' => 'PRB',
    //     'paymaya' => 'PMP',
    //     'philtrust' => 'PTC',
    //     'producers' => 'PDB',
    //     'queenbank' => 'QB',
    //     'quezonbank' => 'QCRB',
    //     'robinsons' => 'RBB',
    //     'seabank' => 'SB',
    //     'shopeepay' => 'SP',
    //     'standard_chartered' => 'SCB',
    //     'starpay' => 'STP',
    //     'sterling' => 'SLB',
    //     'sunsavings' => 'SSB',
    //     'tayocash' => 'TC',
    //     'ucpb' => 'USB',
    //     'ussc' => 'USSC',
    //     'wealth' => 'WDB',
    //     'lazada' => 'ALIPAY',
    //     'bankofchina' => 'BCH',
    //     'binangonan' => 'BRB',
    //     'sme' => 'SME',
    //     'cimb' => 'ERB',
    //     'gotyme' => 'GOT',
    //     'iremit' => 'IRI',
    //     'infoserve' => 'IEM',
    //     'maybank' => 'MYA',
    //     'palawanpay' => 'PPS',
    //     'tonik' => 'TDB',
    //     'traxionpay' => 'TPI',
    //     'uniondigital' => 'UDB',
    //     'speedypay' => 'SPP',
    //     'uniondigital' => 'UNO',
    //     'ownb' => 'OWN',
    //     'pdax' => 'PDAX',
    // ];

    public function __construct()
    {
        $matches = [];
        preg_match('/([\da-zA-Z\_]+)Controller$/', __CLASS__, $matches);
        $this->code = $matches[1];
    }

    //代付提交
    public function PaymentExec($data, $config)
    {
        if ($data['type']=='Maya'){
            $bank_code = 'PMP';
        } else {
            $return = ['status' => 3, 'msg' => '支付类型错误'];
            return $return;
        }
        $post_data = array(
            "merchant" => $config['mch_id'], //商户号
            "total_amount" =>$data['money'],  //提现金额
            "callback_url" => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",      //异步通知地址
            "order_id" => $data['orderid'], //订单号
            "bank" => $bank_code,
            "bank_card_name" => $data['bankfullname'],  //户名
            "bank_card_account" => $data['banknumber'],    //账号
            'bank_card_remark' => 'no',
        );
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code, $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $data['orderid'] . "----提交地址", $config['exec_gateway']);    //日志
        
        // 记录初始执行时间
        $beginTime = microtime(TRUE);
        
        $returnContent = $this->http_post_json($config['exec_gateway'], $post_data);
        $result = json_decode($returnContent, true);
        // if($data['userid'] == 2){
            try{
                
                $redis = $this->redis_connect();
                $userdfpost = $redis->get('userdfpost_' . $data['out_trade_no']);
                $userdfpost = json_decode($userdfpost,true);
                
                logApiAddPayment('商户提交', __METHOD__, $data['orderid'], $data['out_trade_no'], '/', $userdfpost, [], '0', '0', '1', '2');
                
                // 结束并输出执行时间
                $endTime = microtime(TRUE);
                $doTime = floor(($endTime-$beginTime)*1000);
                logApiAddPayment('订单提交三方', __METHOD__, $data['orderid'], $data['out_trade_no'], $config['exec_gateway'], $post_data, $result, $doTime, '0', '1', '1');
            }catch (\Exception $e) {
                // var_dump($e);
            }
        // }
        log_place_order($this->code, $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志

        // log_place_order($this->code, $data['orderid'] . "----状态：", $result['orderStatus']);    //日志
        if($result['status'] === '1'){
            //保存第三方订单号
            // $orderid = $data['orderid'];
            // $Wttklistmodel = D('Wttklist');
            // $date = date('Ymd',strtotime(substr($orderid, 1, 8)));  //获取订单日期
            // $tableName = $Wttklistmodel->getRealTableName($date);
            // $re_save = $Wttklistmodel->table($tableName)->where(['orderid' => $orderid])->save(['three_orderid'=>$result['TrackingNumber']]);
            $return = ['status' => 1, 'msg' => '申请正常'];
        }elseif($result['status'] === '0'){
            $return = ['status' => 3, 'msg' => $result['message']];
        }else{
            $return = ['status' => 0, 'msg' => $result['message']];
        }
        return $return;
    }

    public function notifyurl()
    {
        $re_data = $_REQUEST;
        //获取报文信息
        $orderid = $re_data['order_id'];
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
        if ($sign === $re_data["sign"]) {
            if ($re_data['status'] == '5') {     //订单状态 0审核中，1出款中，2出款成功，-1出款确认中，-2出款失败，-3冲正
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                // $this->handle($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
                $json_result = "success";
            } elseif ($re_data['status'] == "3") {
                //代付失败
                $data = [
                    'memo' => '代付失败-' . $re_data['remark'],
                ];
                $this->changeStatus($Order['id'], 3, $data, $tableName);
                // $this->handle($Order['id'], 3, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
                $json_result = "SUCCESS";
            }
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误: ', $sign);
            $json_result = "sign error";
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
                "merchant" => $config['mch_id'], //商户号
            );
            $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
            log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
            log_place_order($this->code . '_queryBalance', "url", $config['serverreturn']);    //日志
            log_place_order($this->code . '_queryBalance', "返回", $returnContent);    //日志
            $result = json_decode($returnContent,true);
            if($result['status'] != '0'){
                $balance = $result['balance'];  //可用金额
                $pending_balance = $result['pending_balance'];  //可用金额
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
    <tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>商户余额</td><td><b>$balance </b></td></tr>
    <tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>未完成订单金额</td><td><b>$pending_balance </b></td></tr>
    </table>
AAA;
            $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => $result['message']]);
            }
            
        }
    }
    
    //账户余额查询
    public function queryBalance2($config)
    {
        $post_data = array(
                "merchant" => $config['mch_id'], //商户号
            );
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_queryBalance2', "提交", json_encode($post_data));    //日志
        $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
        log_place_order($this->code . '_queryBalance2', "返回", $returnContent);    //日志
        $result = json_decode($returnContent,true);
        if($result['status'] != '0'){
            $result_data['resultCode'] = "0";
            $result_data['balance'] = $result['balance'];
        }
        return $result_data;
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = [
            "merchant" => $config['mch_id'], //商户号
            'order_id' => $data['orderid'],
        ];
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_json($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if($result['status'] === '1'){
            switch ($result['status']) {       //交易狀態：0-错误 1-等待中，2,6,10-进行中，3-失败，5-成功。
                case 1:
                case 2:
                case 6:
                case 10:
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case 5:
                    $return = ['status' => 2, 'msg' => '成功'];
                    break;
                case 3:
                    $return = ['status' => 3, 'msg' => '失败','remark' => $result['message']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['Success']];
        }
        return $return;
    }

    //发送post请求，提交json字符串
    private function http_post_json($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
            if ("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $sign =md5(rtrim($signPars,'&'));
        // log_place_order($this->code, "签名", rtrim($signPars,'&'));    //日志
        return $sign; //最终的签名
    }

}
