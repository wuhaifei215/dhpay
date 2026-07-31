<?php

namespace Payment\Controller;

class INR169PayDFController extends PaymentController
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
            "mer_id" => $config['mch_id'], //商户号
            "mer_no" => $data['orderid']?$data['orderid']:$data['out_trade_no'], //订单号
            "amount" => intval($data['money']),  //提现金额
            "bank_name" => $data['bankname'],       //银行卡名称
            "accountname" => $data['bankfullname'],  //户名，仅支持英文，不支持越南语
            "cardnumber" => $data['banknumber'],    //账号
            "notifyurl" => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",      //异步通知地址
        );
        $post_data["pay_md5sign"] = $this->get_sign($post_data, $config['signkey']);
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
            logApiAddPayment('订单提交三方', __METHOD__, $data['orderid'], $data['out_trade_no'], $config['exec_gateway'], $post_data, $result, $doTime, '0', '1', '2');
        }catch (\Exception $e) {
            // var_dump($e);
        }

        // log_place_order($this->code, $data['orderid'] . "----状态：", $result['status']);    //日志
        switch ($result['code']) {
            case 0:
                $return = ['status' => 1, 'msg' => '申请正常'];
                break;
            default:
                $return = ['status' => 0, 'msg' => $result['msg']];
//                $return = ['status' => 3, 'msg' => '申请失败--'. $result['msg']];
                break;
        }
        return $return;
    }

    public function notifyurl()
    {
        $json = file_get_contents("php://input");
        $re_data = json_decode($json, true);
        //获取报文信息
        $orderid = $re_data['mer_no'];
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", $json);    //日志
        
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

        $sign = $this->get_sign($re_data, $config['signkey']);
        if ($sign === $re_data["sign"]) {
            if ($re_data['returncode'] === "00") {
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                // $this->handle($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
            } elseif ($re_data['returncode'] === "01") {
                $data['orderid'] = $orderid;
                $query_re = $this->PaymentQuery($data, $config);
                if($query_re['status'] ===3){
                    //代付失败
                    $data = [
                        'memo' => '代付失败-' . $query_re['remark'],
                    ];
                    $this->changeStatus($Order['id'], 3, $data, $tableName);
                    // $this->handle($Order['id'], 3, $data, $tableName);
                    log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
                }else{
                    log_place_order($this->code . '_notifyurl', $orderid, "----失败订单。查询状态异常" . $query_re['status']);    //日志
                }
            }
            $json_result =  "OK";
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误: ', $sign);
            $data = [
                'memo' => '签名错误',
            ];
            
            $this->changeStatus($Order['id'], 0, $data, $tableName);
            // $this->handle($Order['id'], 0, $data, $tableName);
            $json_result = "fail";
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
                "custId" => $config['mch_id'], //商户号
                "payCode" => 'VN',
            );
            $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
            log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            $returnContent = $this->http_post_json($config['serverreturn'], $post_data);
            log_place_order($this->code . '_queryBalance', "返回", $returnContent);    //日志
            $result = json_decode($returnContent, true);
            $currencyCode = $result['data']['currencyCode'];
            $balance = $result['data']['avlBalance'];  //可用金额
            $freezeBalance = $result['data']['freezeBalance'];  //冻结金额
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
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>可用金额</td><td><b>$balance $currencyCode</b></td></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>冻结金额</td><td><b>$freezeBalance $currencyCode</b></td></tr>
</table>
AAA;
            $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
        }
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = [
            'custId' => $config['mch_id'],
            'merchantOrderId' => $data['orderid'],
            'payCode' => 'VN',
        ];
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_json($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['status'] === "success") {
            switch ($result['data']['orderStatus']) {       //处理中状态：（1或者3或者21） ；成功状态=（8或者9或者10或者11）； 失败状态=（12或者13或者19或者20）；
                case 1:
                case 3:
                case 21:
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case 8:
                case 9:
                case 10:
                case 11:
                    $remark_arr = explode('/',$result['data']['remark']);
                    $remark_arr[0] = 'https:';
                    $remark_arr[1] = '';
                    $remark_arr[2] = 'down.sqpay77.com';
                    $remark = implode('/',$remark_arr);
                    $return = ['status' => 2, 'msg' => '成功','remark' => $remark];
                    break;
                case 12:
                case 13:
                case 19:
                case 20:
                    $return = ['status' => 3, 'msg' => '失败','remark' => $result['data']['remark']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['data']['remark']];
        }
        return $return;
    }
    
    //代付订单查询
    public function PaymentQuery2()
    {
        $config = M('pay_for_another')->where(['code' => $this->code])->find();
        $post_data = [
            'custId' => $config['mch_id'],
            'merchantOrderId' => 'O2024110523595922399578599',
            'payCode' => 'VN',
        ];
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery2', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_post_json($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery2', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['status'] === "success") {
            switch ($result['data']['orderStatus']) {       //处理中状态：（1或者3或者21） ；成功状态=（8或者9或者10或者11）； 失败状态=（12或者13或者19或者20）；
                case 1:
                case 3:
                case 21:
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case 8:
                case 9:
                case 10:
                case 11:
                    $return = ['status' => 2, 'msg' => '成功','remark' => $result['data']['remark']];
                    break;
                case 12:
                case 13:
                case 19:
                case 20:
                    $return = ['status' => 3, 'msg' => '失败','remark' => $result['data']['remark']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['data']['remark']];
        }
        
        $remark_arr = explode('/',$return['remark']);
        $remark_arr[0] = 'https:';
        $remark_arr[1] = '';
        $remark_arr[2] = 'down.sqpay77.com';
        $remark = implode('/',$remark_arr);
        var_dump($remark_arr);
        var_dump($remark);
        return $return;
    }

    //发送post请求，提交json字符串
    private function http_post_json($url, $postData, $options = array())
    {
        
        $json = json_encode($postData, JSON_UNESCAPED_UNICODE);
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
    private function get_sign($param, $key)
    {
        $signPars = "";
        ksort($param);
        $param['key'] = $key;
        foreach ($param as $k => $v) {
            if ("" != $v && "sign" != $k && "pay_md5sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $sign =strtoupper(md5(rtrim($signPars,'&')));
        log_place_order($this->code, $orderid . "----签名", rtrim($signPars,'&'));    //日志
        return $sign; //最终的签名

    }
}
