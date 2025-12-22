<?php

namespace Pay\Controller;

class IdrDanaInPayController extends PayController
{
    private $code = '';
    private $query_url='';

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
        $orderid = I("request.pay_orderid", '');
        $body = I('request.pay_productname', '');
        $pay_callbackurl = I('request.pay_callbackurl', '');
        $parameter = [
            'code' => $this->code,
            'title' => 'InrDana-InPay',
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
        
        //redis存储订单数据
        $token = randpw(5, 'CHAR') . nonceStr(32);
        $redis = $this->redis_connect();
        $redis->set($token, json_encode($return),600);
        
        $url = $site . 'Pay_' . $this->code . '_Orderpay?token=' . $token;
        if ($array['userid'] != 2) {
            $return_arr = [
                'status' => 'success',
                'H5_url' => $url,
                'pay_orderid' => $orderid,
                'out_trade_id' => $return['orderid'],
                'amount' => $return['amount'],
                'datetime' => date('Y-m-d')
            ];
            echo json_encode($return_arr);
        } else {
            echo '<script type="text/javascript">window.location.href="' . $url . '"</script>';
        }
    } 

    public function Orderpay()
    {
        $token = I('get.token', '');
        $redis = $this->redis_connect();
        $order = $redis->get($token);
        $return = json_decode($order,true);
        if(!$order || empty($return)){
            echo "订单已过期或不存在";
            return;
        }else{
            $redis->delete($token);
        }
        $real_name =[
            'real_name' => "John Doe",
            'phone' => "081234567890"
        ];
        $native = array(
            'mch_no' => $return['mch_id'],
            'out_trade_no' => $return['orderid'],
            'amount' => sprintf('%.2f', $return['amount']),
            'trade_type' =>'20',
            'currency' => 'IDR',
            'notify_url' => $return["notifyurl"],
            'return_url' => $return["callbackurl"],
            'client_ip' => '0.0.0.1',
            'attach' => json_encode($real_name),
        );
        $native['sign'] = $this->sign($native, $return['signkey']);
        log_place_order($this->code, $return['orderid'] . "----提交", json_encode($native, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $return['orderid'] . "----提交地址", $return['gateway']);    //日志

        // 记录初始执行时间
        $beginTime = microtime(TRUE);

        $returnContent = $this->http_post_json($return['gateway'], $native);
        log_place_order($this->code, $return['orderid'] . "----返回", $returnContent);    //日志
        $ans = json_decode($returnContent, true);
        
        try{
            $redis = $this->redis_connect();
            $userpost = $redis->get('userpost_' . $return['out_trade_id']);
            $userpost = json_decode($userpost,true);
            
            logApiAddReceipt('商户提交', __METHOD__, $return['orderid'], $return['out_trade_id'], '/', $userpost, [], '0', '0', '1', '2');
    
            // 结束并输出执行时间
            $endTime = microtime(TRUE);
            $doTime = floor(($endTime-$beginTime)*1000);
            logApiAddReceipt('订单提交三方', __METHOD__, $return['orderid'], $return['out_trade_id'], $return['gateway'], $native, $ans, $doTime, '0', '1', '2');
        }catch (\Exception $e) {
            // var_dump($e);
        }
        if($ans['code'] === 'success'){
            echo '<script type="text/javascript">window.location.href="' . $ans['pay_url'] . '"</script>';
        }else{
            die($ans['msg']);
        }
    }

    //异步通知
    public function notifyurl()
    {
        //获取报文信息
//        $json = file_get_contents("php://input");
        $re_data = $_REQUEST;
        $orderid = $re_data['out_trade_no'];
        //log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", json_encode($re_data, JSON_UNESCAPED_UNICODE));    //日志
        if (!$orderid) return;
        $result = $re_data;
        //过滤数据，防SQL注入
        // $check_data = sqlInj($result);
        // if ($check_data === false) return;
        $OrderModel = D('Order');
        $date = date('Ymd',strtotime(substr($orderid, 0, 8)));  //获取订单日期
        $tablename = $OrderModel->getRealTableName($date);

        $orderList = $OrderModel->table($tablename)->where(['pay_orderid' => $orderid])->find();
        if (!$orderList) return;

        //验证IP白名单
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = getRealIp();
        }

        $check_re = check_IP($orderList['channel_id'], $ip, $orderid);
        if ($check_re !== true) {
            log_place_order($this->code . '_notifyurl', $orderid . "----IP异常", $ip);    //日志
            $json_result = "IP异常:" . $ip;
            try{
                logApiAddNotify($orderid, 0, $_REQUEST, $json_result);
            }catch (\Exception $e) {
                // var_dump($e);
            }
            return;
        }

        $sign = $this->sign($result, $orderList['key']);
        if ($sign == $result["sign"]) {
            if($result['code'] == 'success'){     //状态 00成功 01失败
                $re = $this->EditMoney($orderList['pay_orderid'], $this->code, 0);
                if ($re !== false) {
                    log_place_order($this->code . '_notifyurl', $orderid . "----回调上游", "成功");    //日志
                }else{
                    log_place_order($this->code . '_notifyurl', $orderid . "----回调上游", "失败");    //日志
                }
            }else{
                log_place_order($this->code . '_notifyurl', $orderid . "----订单状态异常", $result['code']);    //日志
            }
            $json_result = "success";
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . "----签名错误，加密后", $sign);    //日志
            $json_result = "sign fail";
        }
        echo $json_result;
        try{
            logApiAddNotify($orderid, 0, $re_data, $json_result);
        }catch (\Exception $e) {
            // var_dump($e);
        }
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

    /**
     * 签名验证
     * $param 数据数组
     * $key 密钥
     */
    private function sign($param, $key)
    {
        $signPars = "";
        ksort($param);
        foreach ($param as $k => $v) {
            if ("sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $sign = hash_hmac('sha256', rtrim($signPars,'&'), $key);
        log_place_order($this->code, "----签名", rtrim($signPars,'&'));    //日志
        return $sign; //最终的签名
    }
}
