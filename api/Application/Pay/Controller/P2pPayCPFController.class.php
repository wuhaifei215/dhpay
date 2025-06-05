<?php

namespace Pay\Controller;

class P2pPayCPFController extends PayController
{
    private $code = '';
    private $query_url='https://api.p2ppay.vip/collections/query';

    public function __construct()
    {
        parent::__construct();
        $matches = [];
        preg_match('/([\da-zA-Z\_]+)Controller$/', __CLASS__, $matches);
        $this->code = $matches[1];
    }

    //支付
    public function Pay($array)
    {
        $pay_cpf = I('request.pay_cpf', '');
        if($pay_cpf==''){
            die("cpf不能为空");
        }
        $pay_name = I('request.pay_name', '');
        
        $orderid = I("request.pay_orderid", '');
        $body = I('request.pay_productname', '');
        $pay_callbackurl = I('request.pay_callbackurl', '');
        $parameter = [
            'code' => $this->code,
            'title' => 'P2Pcpf',
            'exchange' => 1, // 金额比例
            'gateway' => "",
            'orderid' => '',
            'out_trade_id' => $orderid, //外部订单号
            'channel' => $array,
            'body' => $body,
        ];
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        //跳转页面，优先取数据库中的跳转页面
        $_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        $site = trim($return['unlockdomain']) ? $return['unlockdomain'] . '/' : $_site;

        $params = [
            "reference"=>$return['orderid'],//代收单号
            "amount"=>intval($return['amount'] * 100),//代收金额分 真实金额*100
            "callbackUrl"=>$return["notifyurl"],
            'cpf'=>$pay_cpf,
            'name'=>$pay_name,
        ];
        $md5p = md5(json_encode($params));
        $authorization = sha1($md5p . $return['signkey']);
        $header = [
            'AppId: '.$return['mch_id'],
            'Authorization:'.$authorization,
            'Content-Type: application/json'
        ];
        
        log_place_order($this->code, $return['orderid'] . "----提交", json_encode($params, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $return['orderid'] . "----提交地址", $return['gateway']);    //日志
                
        // 记录初始执行时间
        $beginTime = microtime(TRUE);
        
        $ans = $this->request($return['gateway'], $params, $header);
        log_place_order($this->code, $return['orderid'] . "----返回", json_encode($ans, JSON_UNESCAPED_UNICODE));    //日志

        if($ans['code'] === '0' && $ans['data']['status'] ==='PENDING'){
            $payurl = $site . 'PayPage.html?sid=' . $return['orderid'] . '&amount=' . $return['amount']. '&qrcode=' .$ans['data']['paymentUrl'];
            $return_arr = [
                'status' => 'success',
                'H5_url' => $payurl,
                'QRcode' => $ans['data']['paymentUrl'],
                'pay_orderid' => $orderid,
                'out_trade_id' => $return['orderid'],
                'amount' => $return['amount'],
                'datetime' => date('Y-m-d')
            ];
        }else{
            $return_arr = [
                'status' => 'error',
                'msg' => $ans['msg']?$ans['msg']:'fail', 
            ];
        }
        echo json_encode($return_arr);
        
        // if($array['userid'] == 2){
            try{
                $redis = $this->redis_connect();
                $userpost = $redis->get('userpost_' . $return['out_trade_id']);
                $userpost = json_decode($userpost,true);
                
                logApiAddReceipt('下游商户提交YunPay', __METHOD__, $return['orderid'], $return['out_trade_id'], '/', $userpost, $return_arr, '0', '0', '1', '2');
                
                // 结束并输出执行时间
                $endTime = microtime(TRUE);
                $doTime = floor(($endTime-$beginTime)*1000);
                logApiAddReceipt('YunPay订单提交上游P2pPay', __METHOD__, $return['orderid'], $return['out_trade_id'], $return['gateway'], $params, $ans, $doTime, '0', '1', '2');
            }catch (\Exception $e) {
                // var_dump($e);
            }
        // }
        exit;
    }

    //异步通知
    public function notifyurl()
    {
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", file_get_contents('php://input'));    //日志
        //获取报文信息
        $array = json_decode(file_get_contents('php://input'), true);
        $arrayData = json_decode($array['data'], true);
        $orderid = $arrayData['reference'];
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", file_get_contents('php://input'));    //日志
        if (!$orderid) return;

        //过滤数据，防SQL注入
        // $check_data = sqlInj($arrayData);
        // if ($check_data === false) return;
        $OrderModel = D('Order');
        $date = date('Ymd',strtotime(substr($orderid, 0, 8)));  //获取订单日期
        $tablename = $OrderModel->getRealTableName($date);

        $orderList = $OrderModel->table($tablename)->where(['pay_orderid' => $orderid])->find();
        if (!$orderList) return;

        //验证IP白名单
        // $check_re = check_IP($orderList['channel_id'], getIP(), $orderid);
        // if ($check_re !== true) return;

        $sign = sha1(md5($array['data'] . $array['timestamp']) . $orderList['key']);
        if ($sign == $array["sign"]) {
            if($arrayData['status'] === 'SUCCESS' || $arrayData['status'] === 'SETTLED'){     //订单状态 PENDING,SUCCESS,FAIL（进行中，成功，失败）SETTLED 结算的成功   
                $re = $this->EditMoney($orderList['pay_orderid'], $this->code, 0);
                if ($re !== false) {
                    log_place_order($this->code . '_notifyurl', $orderid . "----回调上游", "成功");    //日志
                }else{
                    log_place_order($this->code . '_notifyurl', $orderid . "----回调上游", "失败");    //日志
                }
            }else{
                log_place_order($this->code . '_notifyurl', $orderid . "----订单状态异常", $arrayData['status']);    //日志
            }
            $json_result = "SUCCESS";
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . "----签名错误，加密后", $sign);    //日志
        }
        echo $json_result;
        try{
            logApiAddNotify($orderid, 0, $array, $json_result);
        }catch (\Exception $e) {
            // var_dump($e);
        }
    }

    //订单查询
    public function queryOrder($orderid, $memberid, $key)
    {
        $params = [
            "reference"=>$orderid,//代收单号
        ];
        $md5p = md5(json_encode($params));
        $authorization = sha1($md5p . $key);
        $header = [
            'AppId: ' . $memberid,
            'Authorization:' . $authorization,
            'Content-Type: application/json'
        ];
        
        $returnContent = $this->request($this->query_url, $params, $header);
        log_place_order($this->code. '_queryOrder', $orderid . "----查单返回", $returnContent);    //日志
        $result = json_decode($returnContent, true);
        if ($result['code'] === "0" && $result['data']['status'] === 'SUCCESS') {
            return 1;
        } else {
            return $returnContent;
        }
    }

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
}
