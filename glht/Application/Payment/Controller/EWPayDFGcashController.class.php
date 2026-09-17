<?php

namespace Payment\Controller;

class EWPayDFGcashController extends PaymentController
{
    private $code = '';

    private $bankCode = [
        'binangonan' => 'PH_BRB',
        'aub' => 'PH_AUB',
        'bdouni' => 'PH_BDO',
        'bankofchina' => 'PH_BCH',
        'landbank' => 'PH_LBP',
        'lazada' => 'PH_APY',
        'metrobank' => 'PH_MET',
        'bpi' => 'PH_BPI',
        'cimb' => 'PH_CPI',
        'cisbayad' => 'PH_CIS',
        'ctbc' => 'PH_CCB',
        'dungganon' => 'PH_DBI',
        'eastwest_rural' => 'PH_EWR',
        'eastwest' => 'PH_EWB',
        'gotyme' => 'PH_GOT',
        'gcash' => 'PH_GCASH',
        'infoserve' => 'PH_IEM',
        'isla' => 'PH_ISL',
        'seabank' => 'PH_SEA',
        'malayan' => 'PH_MSB',
        'mcb' => 'PH_MCB',
        'mayabank' => 'PH_MYA',
        'omnipay' => 'PH_OPI',
        'allbank' => 'PH_ALB',
        'palawanpay' => 'PH_PPS',
        'partnerrb' => 'PH_PAR',
        'producers' => 'PH_PRB',
        'rcbc' => 'PH_RCI',
        'camalig' => 'PH_CMG',
        'shopeepay' => 'PH_SPP',
        'starpay' => 'PH_SPY',
        'tayocash' => 'PH_TCI',
        'tonik' => 'PH_TDB',
        'UNO' => 'PH_UNO',
        'uniondigital' => 'PH_UDB',
        'ussc' => 'PH_UMS',
        'zybi' => 'PH_ZTI',
        'cardbank' => 'PH_CRD',
        'sme' => 'PH_SME',
        'dbp' => 'PH_DBP',
        'unionbank' => 'PH_UBP',
        'standard_chartered' => 'PH_SCB',
        'traxionpay' => 'PH_TPI',
        'sunsavings' => 'PH_SSB',
        'sterling' => 'PH_SBA',
        'maybank' => 'PH_MPI',
        'pbcom' => 'PH_PBC',
        'pnb' => 'PH_PNB',
        'security' => 'PH_SEC',
        'equicom' => 'PH_EQB',
        'psbank' => 'PH_PSB',
        'robinsons' => 'PH_RBN',
        'ucpb' => 'PH_USB',
        'paymaya' => 'PH_PPI',
        'veterans' => 'PH_PVB',
        'quezonbank' => 'PH_QRB',
        'bangkomabuhay' => 'PH_BMB',
        'chinabanksavings' => 'PH_CBS',
        'bankofcommerce' => 'PH_BOC',
        'chinabank' => 'PH_CBC',
        'cebuanal' => 'PH_CLB',
        'bdo' => 'PH_ONB',
        'dumaguetebankx' => 'PH_DCB',
        'legazpi' => 'PH_LSB',
        'queenbank' => 'PH_QCB',
        'netbank' => 'PH_NBK',
        'guinobatan' => 'PH_RBG',
        'bpibanko' => 'PH_BGB',
        'grabpay' => 'PH_GBY',
        'philtrust' => 'PH_PTC',
        'PayMongo' => 'PH_PMG',
        'cantilan' => 'PH_CBI',
        'marcopay' => 'PH_MCP',
        'citysavings' => 'PH_CSV',
        'easypay' => 'PH_EPG',
        'ownb' => 'PH_OWB',
        'pdax' => 'PH_PDX',
        'speedypay' => 'PH_SPD',
        'coins' => 'PH_DCP',
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
            $bank_code = 'PH_GCASH';
        } elseif ($data['type'] == 'Maya'){
            $bank_code = 'PH_PPI';
        } elseif ($data['type'] == 'Bank'){
            foreach ($this->bankCode as $k => $v) {
                if ($k == $data['bankname']) {
                    $bank_code = $v;
                }
            }
            if (!$bank_code) {
                return ['status' => 3, 'msg' => '该银行不支持！'];
            }
        } else {
            $return = ['status' => 0, 'msg' => '支付类型错误'];
            return $return;
        }
        $post_data = array(
            "merchantNo" => $config['mch_id'], //商户号
            "merchantOrder" => $data['orderid'], //订单号
            'channel' => 'BANK_CARD',
            "amount" => sprintf('%.2f', $data['money']),  //提现金额
            "currency" =>"PHP",
            "bankCode" => $bank_code,    //银行代码
            "bankAccountName" => $data['bankfullname'],
            "bankAccountNo" => $data['banknumber'],    //账号
            'dateTime' => date('ymdHis'),
            "callbackUrl" => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",      //异步通知地址
        );
        $post_data["signature"] = $this->get_sign($post_data, $config['signkey']);
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
            logApiAddPayment('订单提交三方' . $this->code, __METHOD__, $data['orderid'], $data['out_trade_no'], $config['exec_gateway'], $post_data, $result, $doTime, '0', '1', '1');
        }catch (\Exception $e) {
            // var_dump($e);
        }
        // }
        log_place_order($this->code, $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志

        // log_place_order($this->code, $data['orderid'] . "----状态：", $result['status']);    //日志
        if($result['code'] === "0"){
            //保存第三方订单号
            // $orderid = $data['orderid'];
            // $Wttklistmodel = D('Wttklist');
            // $date = date('Ymd',strtotime(substr($orderid, 1, 8)));  //获取订单日期
            // $tableName = $Wttklistmodel->getRealTableName($date);
            // $re_save = $Wttklistmodel->table($tableName)->where(['orderid' => $orderid])->save(['three_orderid'=>$result['order']]);
            switch ($result['data']['order_status']) {      //訂單狀態：0=未處理, 1=處理中, 3=失敗, 4=成功
                case 0:
                case 1:
                    $return = ['status' => 1, 'msg' => '申请正常'];
                    break;
            }
        }elseif($result['code'] === "-1"){
            $return = ['status' => 3, 'msg' => '申请失败--'.$result['message']];
        }else{
            $return = ['status' => 0, 'msg' => $result['message']];
        }
        return $return;
    }

    public function notifyurl()
    {
        $re_data = json_decode(file_get_contents("php://input"), true);
        //获取报文信息
        $orderid = $re_data['merchantOrder'];
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", json_encode($re_data, JSON_UNESCAPED_UNICODE));    //日志

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

        $sign = $this->get_sign($re_data,$config['signkey']);
        if ($sign === $re_data["signature"]) {
            if ($re_data['status'] === 4) {     //訂單狀態：0=未處理中, 1=處理中, 3=失敗, 4=成功
//                if($re_data['invoiceNo']){
//                    $re_save = $Wttklistmodel->table($tableName)->where(['orderid' => $orderid])->save(['billno'=>$re_data['invoiceNo'],'additional'=>$re_data['channelName']]);
//                }
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                // $this->handle($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
                $json_result = "success";
            } elseif ($re_data['status'] === 3) {
                //代付失败
                $data = [
                    'memo' => '代付失败--'.$re_data['errMsg'],
                ];
                $this->changeStatus($Order['id'], 3, $data, $tableName);
                // $this->handle($Order['id'], 3, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
                $json_result = "success";
            }else{
                log_place_order($this->code . '_notifyurl', $orderid, "----状态异常：" . $re_data['status']);    //日志
            }
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
            $config = M('pay_for_another')->where(['code' => $this->code])->find();
            $post_data = array(
                "merchantNo" => $config['mch_id'], //商户号
                'dateTime' => date('ymdHis'),
            );
            $post_data["signature"] = $this->get_sign($post_data, $config['signkey']);
            log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            log_place_order($this->code . '_queryBalance', "url", $config['serverreturn']);    //日志
            $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
            log_place_order($this->code . '_queryBalance', "返回", $returnContent);    //日志
            $return = json_decode($returnContent,true);
            if($return['code'] === "0"){
                $result = $return['data'];

                $balance = $result['available_balance'];  //可用金额
                $creditAmount = $result['hold_balance'];  //冻结金额
                $totalAmount = $result['total_balance'];  //总金额
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
    <tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>可用金额</td><td><b>$balance </b></td></tr>
    <tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>冻结金额</td><td><b>$creditAmount </b></td></tr>
    <tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>总金额</td><td><b>$totalAmount </b></td></tr>
    </table>
AAA;
            }else{
                $html = $return['msg'];
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
        }
    }

    //账户余额查询
    public function queryBalance2($config)
    {
        $post_data = array(
            "merchantNo" => $config['mch_id'], //商户号
            'dateTime' => date('ymdHis'),
        );
        $post_data["signature"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_queryBalance2', "提交", json_encode($post_data));    //日志
        $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
        log_place_order($this->code . '_queryBalance2', "返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['code'] === "0") {
            $result_data['resultCode'] = "0";
            $result_data['balance'] = $result['available_balance'];
        }
        return $result_data;
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = [
            "merchantNo" => $config['mch_id'], //商户号
            'merchantOrder' => $data['orderid'],
            'dateTime' => date('ymdHis'),
        ];
        $post_data["signature"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_json($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['code'] === "0") {
            switch ($result['data']['status']) {       //訂單狀態：0=未處理, 1=處理中, 3=失敗, 4=成功
                case 0:
                case 1:
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case 4:
                    $return = ['status' => 2, 'msg' => '成功'];
                    break;
                case 3:
                    $return = ['status' => 3, 'msg' => '失败--'. $result['data']['message']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['code']];
        }
        return $return;
    }

    public function PaymentVoucher($data, $config){
        $post_data = [
            'merchantNo' => $config['mch_id'],
            'merchantOrder' => $data['orderid'],
            'dateTime' => date('ymdHis'),
        ];
        $post_data["signature"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentVoucher', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_json('https://api.ewpay.site/withdraw/receipt/query', $post_data);
        log_place_order($this->code . '_PaymentVoucher', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);

        if(!empty($result) && $result['code'] == 0){
            $voucherrData = [
                'status'=>'Successful',
                'amount'=>sprintf('%.2f', $data['tkmoney']),
                'channelName'=> "InstaPay",
                'accountName'=>$data['bankfullname'],
                'accountNumber'=>$data['banknumber'],
                'type'=>$data['type'],
                'referenceNo'=>$result['data']['providerReferenceNo'],
                'cldatetime'=>$data['cldatetime'],
            ];
            return  $voucherrData;
        }else{
            return false;
        }
    }

    //发送post请求，提交json字符串
    private function http_post_json($url, $data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length:'.strlen($json)
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    /**
     * 签名算法
     * @param $data         请求数据
     * @param $md5Key       md5秘钥
     */
    private function get_sign($params, $key)
    {
        // 移除不需要參與簽名的字段
        unset($params['extend']);
        unset($params['callbackUrl']);
        unset($params['signature']);

        ksort($params); // 依照键值 A-Z 排序
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = $k . '=' . urlencode((string)$v); // 注意: value 都需要經過 urlencode 轉譯
        }
        $beforeGenSignStr = implode('&', $parts); // 組合產出簽名前字串
        $beforeGenSignStr .= $key; // 串接 API Key
        log_place_order($this->code, "----签名", $beforeGenSignStr);    //日志
        return md5($beforeGenSignStr); // 回傳 md5 後的簽名字串
    }
}
