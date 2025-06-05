<?php

namespace Payment\Controller;

class P2pPayDFController extends PaymentController
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
        $post_data = [
            "amount"=>intval($data['money'] * 100),//放款金额分 真实金额*100
            "reference"=>$data['orderid'],//订单号
            "payoutDescription"=>"PAY",
            "type"=> $data['type'], //类型
            "pixKey"=>$data['banknumber'],//放款号
            "callbackUrl"=>'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",//回调地址
        ];
        $md5p = md5(json_encode($post_data));
        $authorization = sha1($md5p . $config['signkey']);
        $header = [
            'AppId: ' . $config['mch_id'],
            'Authorization:' . $authorization,
            'Content-Type: application/json'
        ];

        log_place_order($this->code, $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $data['orderid'] . "----提交地址", $config['exec_gateway']);    //日志
        
        // 记录初始执行时间
        $beginTime = microtime(TRUE);
        
        $result = $this->request($config['exec_gateway'], $post_data, $header);

        // if($data['userid'] == 2){
            try{
                
                $redis = $this->redis_connect();
                $userdfpost = $redis->get('userdfpost_' . $data['out_trade_no']);
                $userdfpost = json_decode($userdfpost,true);
                
                logApiAddPayment('下游商户提交YunPay', __METHOD__, $data['orderid'], $data['out_trade_no'], '/', $userdfpost, [], '0', '0', '1', '2');
                
                // 结束并输出执行时间
                $endTime = microtime(TRUE);
                $doTime = floor(($endTime-$beginTime)*1000);
                logApiAddPayment('YunPay订单提交上游' . $this->code, __METHOD__, $data['orderid'], $data['out_trade_no'], $config['exec_gateway'], $post_data, $result, $doTime, '0', '1', '2');
            }catch (\Exception $e) {
                // var_dump($e);
            }
        // }

        log_place_order($this->code, $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志
        // log_place_order($this->code, $data['orderid'] . "----状态：", $result['status']);    //日志
        if($result['code'] === '0'){
            //保存第三方订单号
            // $orderid = $data['orderid'];
            // $Wttklistmodel = D('Wttklist');
            // $date = date('Ymd',strtotime(substr($orderid, 1, 8)));  //获取订单日期
            // $tableName = $Wttklistmodel->getRealTableName($date);
            // $re_save = $Wttklistmodel->table($tableName)->where(['orderid' => $orderid])->save(['three_orderid'=>$result['order']]);
            
            switch ($result['data']['status']) {      //订单状态  PENDING,SUCCESS,FAIL（进行中，成功，失败）
                case 'PENDING':
                    $return = ['status' => 1, 'msg' => '申请正常'];
                    break;
            }
        }elseif($result['code'] === '1'){
            $return = ['status' => 3, 'msg' => $result['msg']];
        }else{
            $return = ['status' => 0, 'msg' => $result['msg']];
        }
        return $return;
    }

    public function notifyurl()
    {
        $array = json_decode(file_get_contents('php://input'), true);
        $arrayData = json_decode($array['data'], true);
        $orderid = $arrayData['reference'];
        
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", file_get_contents('php://input'));    //日志
        
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

        $sign = sha1(md5($array['data'] . $array['timestamp']) . $config['signkey']);
        if ($sign == $array["sign"]) {
            if($arrayData['status'] === 'SUCCESS'){     //订单状态 PENDING,SUCCESS,FAIL（进行中，成功，失败）
                //代付成功 更改代付状态 完善代付逻辑
                $data = [
                    'memo' => '代付成功',
                ];
                $this->changeStatus($Order['id'], 2, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付成功");    //日志
            } elseif ($arrayData['status'] === 'FAIL') {
                //代付失败
                $data = [
                    'memo' => '代付失败-' . $arrayData['message'],
                ];
                $this->changeStatus($Order['id'], 3, $data, $tableName);
                log_place_order($this->code . '_notifyurl', $orderid, "----代付失败");    //日志
            }
            $json_result = "SUCCESS";
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误: ', $sign);
            $json_result = "fail";
        }
        echo $json_result;
        try{
            logApiAddNotify($orderid, 1, $array, $json_result);
        }catch (\Exception $e) {
            // var_dump($e);
        }
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = [
            "reference"=>$data['orderid']
        ];
        $md5p = md5(json_encode($post_data));
        $authorization = sha1($md5p . $config['signkey']);
        $header = [
            'AppId: ' . $config['mch_id'],
            'Authorization:' . $authorization,
            'Content-Type: application/json'
        ];

        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志

        $result = $this->request($config['query_gateway'], $post_data, $header);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志
        if ($result['code'] === "0") {
            switch ($result['data']['orderStatus']) {       //PENDING,SUCCESS,FAIL（状态对应付款中，付款成功，付款失败）
                case 'PENDING':
                    $return = ['status' => 1, 'msg' => '处理中'];
                    break;
                case 'SUCCESS':
                    $return = ['status' => 2, 'msg' => '成功'];
                    break;
                case 'FAIL':
                    $return = ['status' => 3, 'msg' => '失败','remark' => $result['msg']];
                    break;
            }
        } else {
            $return = ['status' => 7, 'msg' => "查询接口失败:".$result['code']];
        }
        return $return;
    }
    
    // public function PaymentVoucher(){
        // $config = M('pay_for_another')->where(['code' => $this->code])->find();
    public function PaymentVoucher($data, $config){
        $post_data = [
            "reference"=>$data['orderid']
        ];
        $md5p = md5(json_encode($post_data));
        $authorization = sha1($md5p . $config['signkey']);
        $header = [
            'AppId: ' . $config['mch_id'],
            'Authorization:' . $authorization,
            'Content-Type: application/json'
        ];
        log_place_order($this->code . '_PaymentVoucher', $data['orderid'] . "----提交", json_encode($post_data, JSON_UNESCAPED_UNICODE));    //日志
        $result = $this->request('https://api.p2ppay.vip/disbursements/queryvoucher', $post_data, $header);
        log_place_order($this->code . '_PaymentVoucher', $data['orderid'] . "----返回", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志

        if($result['code'] === '000000'){
            return  $result;
        }else{
            return false;
        }
    }
    
    //账户余额查询
    public function queryBalance()
    {
        if (IS_AJAX) {
            $config = M('pay_for_another')->where(['code' => $this->code])->find();
            $header = [
                'AppId: ' . $config['mch_id'],
            ];
            
            // log_place_order($this->code . '_queryBalance', "提交", json_encode($post_data));    //日志
            $returnContent = $this->http_get_json($config['serverreturn'], $header);
            $result = json_decode($returnContent, true);
            log_place_order($this->code . '_queryBalance', "返回", json_encode($result));    //日志
            if($result['code']==="0"){
                $balance = $result['data']['balance'] / 100;
                $availableBalance = $result['data']['availableBalance'] / 100;
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
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>可用余额</td><td><b>$availableBalance </b></td></tr>
</table>
AAA;
                $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
            }
        }
    }
    
    //账户余额查询
    public function queryBalance2($config)
    {
        $result_data=[];
        $header = [
            'AppId: ' . $config['mch_id'],
        ];
        
        log_place_order($this->code . '_queryBalance2', "提交", json_encode($header));    //日志
        $returnContent = $this->http_get_json($config['serverreturn'], $header);
        $result = json_decode($returnContent, true);
        log_place_order($this->code . '_queryBalance2', "返回", json_encode($result));    //日志
        if($result['code']==="0"){
            $result_data['resultCode'] = "0";
            $result_data['balance'] = $result['data']['balance'] / 100;
        }
        return $result_data;
    }
    
    
    /*-----------------------------------辅助方法---------------------------------------------*/
    /**
     * 执行请求，http header验证
     *
     * @param string $url
     * @param array $params
     * @return Ambigous <mixed, multitype:NULL >
     */
    private function request($url, $params, $header)
    {
        try {
            $ch = curl_init();
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
    
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($params),
    
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0
            );
            curl_setopt_array($ch, $options);
            $output = curl_exec($ch);
    
            $result = [];
            if ($output === false) {
                $result['code'] = curl_errno($ch);
                $result['message'] = curl_error($ch);
            } else {
                $result = json_decode($output, true);
            }
            curl_close($ch);
            return $result;
        } catch (\Exception $e) {
            log_place_order($this->code. '_request', $params["reference"] . "----提交错误", $e->getMessage());    //日志
        }
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
