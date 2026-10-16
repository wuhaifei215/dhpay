<?php

namespace Pay\Controller;


class InrWakeDMPayController extends PayController
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
            'title' => 'InrWake-DMPay',
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
            'merchantOrderNo' => $return['orderid'],
            'merchantNo' => $return['mch_id'],
            'orderAmount' => sprintf('%.2f', $return['amount']),
            'notifyUrl' => $return["notifyurl"],
        );
        $native['sign'] = $this->get_sign($native, $return['signkey']);
        log_place_order($this->code, $return['orderid'] . "----提交", json_encode($native, JSON_UNESCAPED_UNICODE));    //日志
        log_place_order($this->code, $return['orderid'] . "----提交地址", $return['gateway']);    //日志
                
        // 记录初始执行时间
        $beginTime = microtime(TRUE);
        
        $returnContent = $this->http_post_json($return['gateway'], $native);
        log_place_order($this->code, $return['orderid'] . "----返回", $returnContent);    //日志
        $ans = json_decode($returnContent, true);
        if(is_array($ans) && isset($ans['code']) && (string)$ans['code'] === '200' && !empty($ans['success']) && !empty($ans['result']['codeUrl'])){
            $return_arr = [
                'status' => 'success',
                'H5_url' => $ans['result']['codeUrl'],
                'pay_orderid' => $orderid,
                'out_trade_id' => $return['orderid'],
                'amount' => $return['amount'],
                'datetime' => date('Y-m-d')
            ];
        }else{
            if(!isset($ans['message']) || $ans['message'] == ''){
                $msg = 'channel error';
            } else {
                $msg = $ans['message'];
            }
            $return_arr = [
                'status' => 'error',
                'msg' => $msg,
            ];
        }
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
        // Record both submission logs before responding or redirecting.
        if ($return_arr['status'] === 'success' && $array['userid'] == 2) {
            echo '<script type="text/javascript">window.location.href=' . json_encode($return_arr['H5_url'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
        } else {
            echo json_encode($return_arr);
        }
        exit;
    }

    // DM only signs the nonempty fields in data, including the numeric state 0.
    public function notifyurl()
    {
        $raw = file_get_contents('php://input');
        $response = json_decode($raw, true);
        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
        $orderid = isset($data['merchOrderNo']) && is_string($data['merchOrderNo']) ? $data['merchOrderNo'] : '';
        log_place_order($this->code . '_notifyurl', $orderid . '----异步回调', $raw);
        $reply = 'fail';
        $notifyMessage = '';
        $order = $this->findDmOrder($orderid);
        if (!is_array($response) || !isset($response['data']) || !is_array($response['data'])) {
            $notifyMessage = '回调报文格式错误：必须为JSON且包含data对象';
        } elseif ($orderid === '' || !preg_match('/^\d{8}[a-zA-Z0-9_-]*$/D', $orderid)) {
            $notifyMessage = '商户订单号缺失或格式错误';
        } elseif (!$order) {
            $notifyMessage = '本地订单不存在或通道不匹配：' . $orderid . '，通道：' . $this->code;
        } elseif ($this->verifyIpWhitelist($order['channel_id'], $orderid) !== true) {
            $notifyMessage = 'IP异常:' . $this->getClientIp();
        } elseif (!isset($response['code']) || (string)$response['code'] !== '0') {
            $notifyMessage = '回调code异常：' . json_encode(isset($response['code']) ? $response['code'] : null);
        } elseif (!self::dmVerify($data, $order['key'])) {
            $notifyMessage = '签名校验失败：请核对上游签名与订单保存的密钥';
        } elseif (!isset($data['merchNo']) || (string)$data['merchNo'] !== (string)$order['memberid']) {
            $notifyMessage = '商户号不匹配：回调=' . json_encode(isset($data['merchNo']) ? $data['merchNo'] : null)
                . '，订单=' . $order['memberid'];
        } elseif (!isset($data['amount']) || !self::dmAmountEquals($data['amount'], $order['pay_amount'])) {
            $notifyMessage = '订单金额不匹配或格式错误：回调=' . json_encode(isset($data['amount']) ? $data['amount'] : null)
                . '，订单=' . $order['pay_amount'];
        } else {
            $state = isset($data['orderState']) ? (string)$data['orderState'] : '';
            if ($state === '0' || ($state === '8' && isset($data['realPrice'])
                && self::dmAmountEquals($data['realPrice'], $order['pay_amount']))) {
                if (in_array((string)$order['pay_status'], ['1', '2'], true)) {
                    $reply = 'success';
                } else {
                    $updated = $this->EditMoney($orderid, $this->code, 0, isset($data['refNo']) ? $data['refNo'] : '');
                    $latest = $this->findDmOrder($orderid);
                    if ($updated !== false || ($latest && in_array((string)$latest['pay_status'], ['1', '2'], true))) {
                        $reply = 'success';
                    } else {
                        $notifyMessage = '入账未完成：EditMoney返回false且订单未成功，可能正在处理中或账务更新失败，请核对EditMoney日志';
                    }
                }
            } elseif ($state === '1') {
                $reply = 'success';
            } elseif ($state === '8') {
                $notifyMessage = '金额变动需人工核对：realPrice=' . json_encode(isset($data['realPrice']) ? $data['realPrice'] : null)
                    . '，订单=' . $order['pay_amount'];
            } else {
                $notifyMessage = '订单状态不支持：' . json_encode(isset($data['orderState']) ? $data['orderState'] : null);
            }
        }
        if ($notifyMessage !== '') {
            log_place_order($this->code . '_notifyurl', $orderid . '----回调失败原因', $notifyMessage);
        }
        echo $reply;
        try { logApiAddNotify($orderid, 0, $response, $notifyMessage !== '' ? $notifyMessage : $reply); } catch (\Exception $e) {}
    }

    private function findDmOrder($orderid)
    {
        if (!preg_match('/^\d{8}[a-zA-Z0-9_-]*$/D', $orderid)) return [];
        $model = D('Order');
        return $model->table($model->getRealTableName(date('Ymd', strtotime(substr($orderid, 0, 8)))))
            ->where(['pay_orderid' => $orderid, 'pay_tongdao' => $this->code])->find();
    }

    // Compatible with the existing collection reconciliation entry point.
    public function queryOrder($orderid, $memberid, $key)
    {
        $order = $this->findDmOrder($orderid);
        if (!$order) return '本地订单不存在';
        $channel = M('Channel')->where(['id' => $order['channel_id']])->find();
        $request = ['orderNo' => $orderid, 'merchNo' => $order['memberid']];
        $request['sign'] = self::dmSign($request, $order['key']);
        $http = self::dmPost(self::dmEndpoint(isset($channel['gateway']) ? $channel['gateway'] : '', 'queryDsOrder'), $request);
        log_place_order($this->code . '_queryOrder', $orderid, $http['body']);
        $response = json_decode($http['body'], true);
        $data = isset($response['result']) && is_array($response['result']) ? $response['result'] : [];
        if ($http['error'] === '' && $http['http'] === 200 && isset($response['code']) && (string)$response['code'] === '200'
            && self::dmVerify($data, $order['key'])
            && isset($data['orderNo'], $data['merchNo'], $data['amount'], $data['orderState'])
            && (string)$data['orderNo'] === (string)$orderid && (string)$data['merchNo'] === (string)$order['memberid']
            && self::dmAmountEquals($data['amount'], $order['pay_amount'])
            && ((string)$data['orderState'] === '0' || ((string)$data['orderState'] === '8'
                && isset($data['realPrice']) && self::dmAmountEquals($data['realPrice'], $order['pay_amount'])))) {
            return 1;
        }
        return $http['body'] !== '' ? $http['body'] : '查询失败，订单状态未知';
    }

    private function http_post_json($url, $postData)
    {
        $http = self::dmPost(self::dmEndpoint($url, 'collectionOrder'), $postData);
        return $http['error'] === '' && $http['http'] === 200 ? $http['body'] : '';
    }

    private function get_sign($param, $secretKey)
    {
        return self::dmSign($param, $secretKey);
    }

    private static function dmSign(array $data, $key)
    {
        unset($data['sign']);
        ksort($data, SORT_STRING);
        $parts = [];
        foreach ($data as $name => $value) {
            if ($value !== '' && $value !== null && is_scalar($value)) {
                $parts[] = $name . '=' . $value;
            }
        }
        return strtoupper(md5(implode('&', $parts) . $key));
    }

    private static function dmVerify(array $data, $key)
    {
        return isset($data['sign']) && is_string($data['sign'])
            && preg_match('/^[a-fA-F0-9]{32}$/D', $data['sign'])
            && hash_equals(self::dmSign($data, $key), strtoupper($data['sign']));
    }

    private static function dmAmountEquals($left, $right)
    {
        return is_scalar($left) && is_scalar($right)
            && preg_match('/^\d+(\.\d{1,2})?$/D', (string)$left)
            && preg_match('/^\d+(\.\d{1,2})?$/D', (string)$right)
            && bccomp((string)$left, (string)$right, 2) === 0;
    }

    // Accept either a host or a full DM endpoint from channel configuration.
    private static function dmEndpoint($gateway, $action)
    {
        $parts = parse_url(trim((string)$gateway));
        if (!$parts || empty($parts['host']) || !isset($parts['scheme'])
            || !in_array($parts['scheme'], ['https', 'http'], true)) {
            return '';
        }
        return $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '') . '/api/xd/' . $action;
    }

    private static function dmPost($url, array $data)
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($url === '' || $body === false) {
            return ['body' => '', 'error' => '请求地址或参数无效', 'http' => 0];
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $response = curl_exec($curl);
        $result = ['body' => $response === false ? '' : $response,
            'error' => curl_error($curl), 'http' => (int)curl_getinfo($curl, CURLINFO_HTTP_CODE)];
        curl_close($curl);
        return $result;
    }
}
