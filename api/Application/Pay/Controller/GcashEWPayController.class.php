<?php

namespace Pay\Controller;

class GcashEWPayController extends PayController
{
    private $code = '';

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
            'title' => 'Gcash唤醒-EWPay',
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
        
        $native = array(
            'merchantNo' => $return['mch_id'],
            'merchantOrder' => $return['orderid'],
            'channel' => 'QRPH_GCASH',
            'amount' => sprintf('%.2f', $return['amount']),
            'currency' => 'PHP',
            'dateTime' => date('ymdHis'),
            'callbackUrl' => $return["notifyurl"],
        );
        $native['signature'] = $this->get_sign($native, $return['signkey']);
        log_place_order($this->code, $return['orderid'] . "----提交", json_encode($native, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $return['orderid'] . "----提交地址", $return['gateway']);    //日志
                
        // 记录初始执行时间
        $beginTime = microtime(TRUE);
        
        $returnContent = $this->http_post_json($return['gateway'], $native);
        log_place_order($this->code, $return['orderid'] . "----返回", $returnContent);    //日志
        $ans = json_decode($returnContent, true);
        if($ans['code'] === "0"){
            if($array['userid'] == 2){
                echo '<script type="text/javascript">window.location.href="' . $ans['data']['url'] . '"</script>';
            }else{
                $return_arr = [
                    'status' => 'success',
                    'H5_url' => $ans['data']['url'],
                    'QRcode' => $ans['data']['url'],
                    'pay_orderid' => $orderid,
                    'out_trade_id' => $return['orderid'],
                    'amount' => $return['amount'],
                    'datetime' => date('Y-m-d')
                ];
            }
        }else{
            $return_arr = [
                'status' => 'error',
                'msg' => $ans['message'], 
            ];
        }
        echo json_encode($return_arr);

        try{
            $redis = $this->redis_connect();
            $userpost = $redis->get('userpost_' . $return['out_trade_id']);
            $userpost = json_decode($userpost,true);
            $userEndTime = microtime(TRUE);
            $userDoTime = floor(($userEndTime-$userpost['get_time'])*1000);

            logApiAddReceipt('下游商户提交', __METHOD__, $return['orderid'], $return['out_trade_id'], '/', $userpost, $return_arr, $userDoTime, '0', '1', '2');

            // 结束并输出执行时间
            $endTime = microtime(TRUE);
            $doTime = floor(($endTime-$beginTime)*1000);
            logApiAddReceipt('订单提交上游' . $this->code, __METHOD__, $return['orderid'], $return['out_trade_id'], $return['gateway'], $native, $ans, $doTime, '0', '1', '2');
        }catch (\Exception $e) {
            // var_dump($e);
        }
        exit;
    }

    //异步通知
    public function notifyurl()
    {
        $res_data = json_decode(file_get_contents("php://input"), true);
        //获取报文信息
        $orderid = $res_data['merchantOrder'];
        // log_place_order($this->code . '_notifyserver', $orderid . "----异步回调报文头", json_encode($_SERVER));    //日志
        log_place_order($this->code . '_notifyurl', $orderid . "----异步回调", json_encode($res_data, JSON_UNESCAPED_UNICODE));    //日志
        if (!$orderid) return;
        //过滤数据，防SQL注入
        // $check_data = sqlInj($res_data);
        // if ($check_data === false) return;
        $OrderModel = D('Order');
        $date = date('Ymd',strtotime(substr($orderid, 0, 8)));  //获取订单日期
        $tablename = $OrderModel->getRealTableName($date);

        $orderList = $OrderModel->table($tablename)->where(['pay_orderid' => $orderid])->find();
        if (!$orderList) {
            log_place_order($this->code . '_notifyurl', $orderid, '未查询到');    //日志
            return;
        }

        //验证IP白名单
        if ($this->verifyIpWhitelist($orderList['channel_id'], $orderid) !== true) {
            log_place_order($this->code . '_notifyurl', $orderid . "----IP异常", $this->getClientIp());    //日志
            $json_result = "IP异常:" . $this->getClientIp();
            try{
                logApiAddNotify($orderid, 0, $res_data, $json_result);
            }catch (\Exception $e) {
                // var_dump($e);
            }
            return;
        }

        $sign = $this->get_sign($res_data, $orderList['key']);
        if ($sign == $res_data["signature"]) {
            if($res_data['status'] === 4){     //訂單狀態：0=未處理, 1=處理中, 3=失敗, 4=成功
                $re = $this->EditMoney($orderList['pay_orderid'], $this->code, 0);
                if ($re !== false) {
                    log_place_order($this->code . '_notifyurl', $orderid . "----回调上游", "成功");    //日志
                }else{
                    log_place_order($this->code . '_notifyurl', $orderid . "----回调上游", "失败");    //日志
                }
                $json_result = "success";
            }else{
                log_place_order($this->code . '_notifyurl', $orderid . "----订单状态异常", $res_data['status']);    //日志
                $json_result = "status error:" . $res_data['status'];
            }
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . "----签名错误，加密后", $sign);    //日志
            $json_result = "sign fail";
        }
        echo $json_result;
        try{
            logApiAddNotify($orderid, 0, $res_data, $json_result);
        }catch (\Exception $e) {
            // var_dump($e);
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
