<?php

namespace Payment\Controller;

class SarpagoPayBDDFController extends PaymentController
{
    private $code = '';

    const SIGN = 'sign';
    const KEY = 'key';
    const EXT = 'extra';

    public function __construct()
    {
        $matches = [];
        preg_match('/([\da-zA-Z\_]+)Controller$/', __CLASS__, $matches);
        $this->code = $matches[1];
    }

    //代付提交
    public function PaymentExec($data, $config)
    {
        if($data['bankname'] === 'BKASH'){
            $payMethod = "BD_BKASH";
        }elseif($data['bankname'] === 'NAGAD'){
            $payMethod = "BD_NAGAD";
        }else{
            $payMethod = '';
        }

        $extra = [
            "accountNo" => $data['banknumber'],    //账号
            "accountName" => $data['bankfullname'],  //户名
            'mobile' => '12345678909',
            'email' => 'email@mail.com',
        ];

        $post_data = array(
            'appId' => $config['appid'],
            "merOrderNo" => $data['orderid'], //订单号
            'currency' => 'BDT',
            "amount" =>$data['money'],  //提现金额
            "notifyUrl" => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",      //异步通知地址
            'payMethod' => $payMethod,
            'extra' => $extra,
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
        if($result['code'] === 0){
            //支付中 0 1 -4 ，支付成功 2 3 ，支付失败 -1 -2 -3
            if($result['data']['orderStatus'] === "0" || $result['data']['orderStatus'] === "1" || $result['data']['orderStatus'] === "-4"){
                $return = ['status' => 1, 'msg' => '申请正常'];
            }elseif($result['data']['orderStatus'] === "2" || $result['data']['orderStatus'] === "3"){
                $return = ['status' => 2, 'msg' => "支付成功！"];
            }else{
                $return = ['status' => 0, 'msg' => $result['data']['message']];
            }
        }else{
            $return = ['status' => 0, 'msg' => $result['msg']];
        }
        return $return;
    }

    public function notifyurl()
    {
        $re_data = json_decode(file_get_contents("php://input"), true);
        //获取报文信息
        $orderid = $re_data['merOrderNo'];
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", file_get_contents("php://input"));    //日志

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

        if ($sign === $re_data["sign"]) {
            if ($re_data['orderStatus'] === "2" || $re_data['orderStatus'] === "3") {     //订单状态（订单状态支付中 0 1 -4, 支付成功 2 3, 支付失败 -1 -2 -3 ）
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
            } elseif ($re_data['orderStatus'] === "-1" || $re_data['orderStatus'] === "-2" || $re_data['orderStatus'] === "-3") {
                //代付失败
                $data = [
                    'memo' => '代付失败-' . $re_data['message'],
                ];
                $this->changeStatus($Order['id'], 3, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
            }
            $json_result = "success";
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误: ', $sign);
            $json_result = "fail";
        }
        echo $json_result;
        try{
            logApiAddNotify($orderid, 1, $_REQUEST, $json_result);
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
                "appId" => $config['appid'], //商户号
            );
            $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
            log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            $returnContent = $this->http_get_json($config['serverreturn'], $post_data);
            log_place_order($this->code . '_queryBalance', "url", $config['serverreturn']);    //日志
            log_place_order($this->code . '_queryBalance', "返回", $returnContent);    //日志
            $result = json_decode($returnContent,true);

            if($result['code'] === 0){
                $balance = $result['data']['payinValue'];  //代收余额
                $payoutValue = $result['data']['payoutValue'];  //代付余额
                $disableValue = $result['data']['disableValue'];  //冻结金额
                $msg = <<<MSG
                <tr><th>说明</th><th>值</th></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>代收余额</td><td><b>$balance </b></td></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>代付余额</td><td><b>$payoutValue </b></td></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>冻结金额</td><td><b>$disableValue </b></td></tr>
MSG;
            }else{
                $msg = <<<MSG
                <tr><th>说明</th></tr>
                <tr><td>未查询出结果</td></tr>
MSG;
            }
            $html = <<<AAA
<!-- CSS goes in the document HEAD or added to your external stylesheet -->
<style type="text/css">
table.hovertable {width: 200px;font-family: verdana,arial,sans-serif;font-size:11px;color:#333333;border-width: 1px;border-color: #999999;border-collapse: collapse;}
table.hovertable th {background-color:#c3dde0;border-width: 1px;padding: 8px;border-style: solid;border-color: #a9c6c9;}
table.hovertable tr {background-color:#f5f5f5;}
table.hovertable td {border-width: 1px;padding: 8px;border-style: solid;border-color: #a9c6c9;}
</style>
<table class="hovertable">
$msg
</table>
AAA;
            $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
        }
    }

    //账户余额查询
    public function queryBalance2($config)
    {
        $post_data = array(
            "appId" => $config['appid'], //商户号
        );
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_queryBalance2', "提交", json_encode($post_data));    //日志
        $returnContent = $this->http_get_json($config['serverreturn'], $post_data);
        log_place_order($this->code . '_queryBalance2', "返回", $returnContent);    //日志
        $result = json_decode($returnContent,true);
        if($result['Success'] === true){
            $result_data['resultCode'] = "0";
            $result_data['balance'] = $result['data']['payoutValue'];
        }
        return $result_data;
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = [
            "appId" => $config['mch_id'], //商户号
            'merOrderNo' => $data['orderid'],
        ];
        $post_data["sign"] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $returnContent = $this->http_get_json($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['code'] === 0 ) {
            switch ($result['data']['orderStatus']) {       //订单状态（订单状态支付中 0 1 -4, 支付成功 2 3, 支付失败 -1 -2 -3 ）
                case "0":
                case "1":
                case "-4":
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case "2":
                case "3":
                    $return = ['status' => 2, 'msg' => '成功'];
                    break;
                case "-1":
                case "-2":
                case "-3":
                    $return = ['status' => 3, 'msg' => '失败','remark' => $result['data']['message']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['code']];
        }
        return $return;
    }

    public static function get_sign($map, $appSecret) {
        $signStr = self::createSignStr($appSecret, $map);

        return hash('sha256', $signStr);
    }

    public static function createSignStr($appSecret, $map) {
        $signStr = self::joinMap($map);
        $signStr .= '&'. self::KEY . '=' . $appSecret;

        return $signStr;
    }

    private static function prepareMap($map) {
        if (!is_array($map)) {
            return array();
        }

        if (array_key_exists(self::SIGN, $map)) {
            unset($map[self::SIGN]);
        }
        ksort($map);
        reset($map);

        return $map;
    }

    private static function joinMap($map) {
        if (!is_array($map)) {
            return '';
        }

        $map = self::prepareMap($map);
        $pair = array();
        foreach($map as $key => $value) {
            if (self::isIgnoredItem($key, $value)) {
                continue;
            }

            $tmp = $key . '=';
            if(0 === strcmp(self::EXT, $key)) {
                $tmp .= self::joinMap($value);
            } else {
                $tmp .= $value;
            }

            $pair[] = $tmp;
        }

        if (empty($pair)) {
            return '';
        }

        return join('&', $pair);
    }

    private static function isIgnoredItem($key, $value) {
        if (empty($key) || empty($value)) {
            return true;
        }

        if (0 === strcmp(self::SIGN, $key)) {
            return true;
        }

        if (0 === strcmp(self::EXT, $key)) {
            return false;
        }

        if (is_string($value)) {
            return false;
        }

        if (is_numeric($value)) {
            return false;
        }

        if (is_bool($value)) {
            return false;
        }

        return true;
    }

    //发送post请求，提交json字符串
    private function http_post_json($url, $postData)
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

    private function http_get_json($url, $headerArray)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $headerArray,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
