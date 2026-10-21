<?php

namespace Pay\Controller;

class QQPayIdrQrisController extends PayController
{
    private const API_BASE = 'https://apiidn.qqpay.shop';
    private const CREATE_PATH = '/api/Order/createPayOrder';
    private const QUERY_PATH = '/api/Order/queryPayOrder';

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
        $orderid = I('request.pay_orderid', '');
        $parameter = [
            'code' => $this->code,
            'title' => 'IdrQris-QQPay',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'channel' => $array,
            'body' => I('request.pay_productname', ''),
        ];
        $return = $this->orderadd($parameter);
        $return['status'] === 'error' && $this->showmessage($return['errorcontent']);
        $site = trim((string)$return['unlockdomain']) !== ''
            ? rtrim((string)$return['unlockdomain'], '/') . '/'
            : $this->_site;
        $returnUrl = I('request.pay_callbackurl', '', 'string,strip_tags,htmlspecialchars');
        if ($returnUrl === '') {
            $returnUrl = $site . 'Pay_' . $this->code . '_callbackurl.html?orderid=' . rawurlencode($return['orderid']);
        }

        $native = [
            'version' => '1.0.0',
            'mch_id' => (string)$return['mch_id'],
            'type_id' => '513',
            'currency' => 'IDR',
            'order_num' => (string)$return['orderid'],
            'total_fee' => $this->toCents($return['amount']),
            'return_url' => $returnUrl,
            'notify_url' => (string)$return['notifyurl'],
            'order_at' => date('Y-m-d H:i:s'),
            'subject' => trim((string)$parameter['body']) !== '' ? (string)$parameter['body'] : 'all',
        ];
        $native['sign'] = $this->sign($native, $return['signkey']);
        $gateway = $this->resolveEndpoint($return['gateway'], self::CREATE_PATH);
        log_place_order($this->code, $return['orderid'] . '----提交', json_encode($native, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        log_place_order($this->code, $return['orderid'] . '----提交地址', $gateway);

        $beginTime = microtime(true);
        $httpResult = $this->postJson($gateway, $native);
        log_place_order($this->code, $return['orderid'] . '----返回', $httpResult['body']);
        $ans = json_decode($httpResult['body'], true);
        $payParams = is_array($ans) && isset($ans['data']['pay_params']) && is_array($ans['data']['pay_params'])
            ? $ans['data']['pay_params']
            : [];
        $payUrl = isset($payParams['pay_url']) ? (string)$payParams['pay_url'] : '';
        $qrcode = isset($payParams['qrcode']) ? (string)$payParams['qrcode'] : '';
        if ($httpResult['error'] !== '') {
            $return_arr = ['status' => 'error', 'msg' => 'QQPay连接失败，订单结果未知，请勿更换订单号重试'];
        } elseif (is_array($ans) && (int)(isset($ans['code']) ? $ans['code'] : 0) === 200 && ($payUrl !== '' || $qrcode !== '')) {
            $return_arr = [
                'status' => 'success',
                'H5_url' => $payUrl,
                'QRcode' => $qrcode,
                'pay_orderid' => $orderid,
                'out_trade_id' => $return['orderid'],
                'amount' => sprintf('%.2f', $return['amount']),
                'datetime' => date('Y-m-d'),
            ];
        } else {
            $return_arr = [
                'status' => 'error',
                'msg' => is_array($ans) && isset($ans['msg']) ? $ans['msg'] : 'QQPay响应异常',
            ];
        }
        if ((int)$array['userid'] === 2 && $return_arr['status'] === 'success' && $payUrl !== '') {
            header('Location: ' . $payUrl);
            exit;
        }

        try {
            $doTime = floor((microtime(true) - $beginTime) * 1000);
            logApiAddReceipt('订单提交上游' . $this->code, __METHOD__, $return['orderid'], $return['out_trade_id'], $gateway, $native, is_array($ans) ? $ans : $httpResult, $doTime, '0', '1', '2');
        } catch (\Exception $exception) {
            log_place_order($this->code, $return['orderid'] . '----记录日志异常', $exception->getMessage());
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($return_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // 同步通知仅用于页面跳转，不能把待支付订单直接置为成功。
    public function callbackurl()
    {
        $orderid = I('get.orderid', '', 'string,strip_tags,htmlspecialchars');
        if ($orderid === '') {
            exit('error');
        }
        $payStatus = M('Order')->where(['pay_orderid' => $orderid])->getField('pay_status');
        if ((int)$payStatus !== 0) {
            $this->EditMoney($orderid, $this->code, 1);
            return;
        }
        exit('error');
    }

    //异步通知
    public function notifyurl()
    {
        $result = $this->readRequestData();
        $orderid = isset($result['order_num']) ? (string)$result['order_num'] : '';
        log_place_order($this->code . '_notifyurl', $orderid . '----异步回调', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $orderList = $this->findOrder($orderid);
        if (!$orderList) {
            $this->respond('FAIL', 404);
            return;
        }
        if ($this->verifyIpWhitelist($orderList['channel_id'], $orderid) !== true) {
            $this->writeNotifyLog($orderid, $result, 'IP异常:' . $this->getClientIp());
            $this->respond('FAIL', 403);
            return;
        }
        if (empty($result['sign']) || !hash_equals($this->sign($result, $orderList['key']), strtoupper((string)$result['sign']))) {
            $this->writeNotifyLog($orderid, $result, '签名错误');
            $this->respond('FAIL', 401);
            return;
        }
        $status = (string)(isset($result['status']) ? $result['status'] : '');
        if (in_array($status, ['2', '3'], true)) {
            $this->EditMoney($orderid, $this->code, 0);
        }
        $this->writeNotifyLog($orderid, $result, 'OK-' . $status);
        $this->respond('OK');
    }
    
    //发送post请求，提交data字符串
    private function postJson($url, array $postData)
    {
        $json = json_encode($postData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || trim((string)$url) === '') {
            return ['body' => '', 'httpStatus' => 0, 'error' => '请求参数或地址无效'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($json)],
            CURLOPT_POSTFIELDS => $json,
        ]);
        $body = curl_exec($ch);
        $error = $body === false ? curl_error($ch) : '';
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['body' => $body === false ? '' : $body, 'httpStatus' => $httpStatus, 'error' => $error];
    }


    /**
     * 签名验证
     * $param 数据数组
     * $key 密钥
     */
    private function sign($param, $key)
    {
        unset($param['sign']);
        $param = array_filter($param, function ($value) {
            return $value !== '' && $value !== null;
        });
        ksort($param, SORT_STRING);
        $pairs = [];
        foreach ($param as $k => $v) {
            $pairs[] = $k . '=' . (string)$v;
        }
        $pairs[] = 'key=' . (string)$key;
        return strtoupper(md5(implode('&', $pairs)));
    }

    // 代收订单查询，成功返回1，兼容现有补单调用约定。
    public function queryOrder($orderid, $memberid, $key)
    {
        $order = $this->findOrder($orderid);
        $merchantId = !empty($order['memberid']) ? $order['memberid'] : $memberid;
        $signKey = !empty($order['key']) ? $order['key'] : $key;
        $channel = !empty($order['channel_id'])
            ? M('Channel')->where(['id' => (int)$order['channel_id']])->find()
            : M('Channel')->where(['code' => $this->code])->find();
        $request = [
            'version' => '1.0.0',
            'mch_id' => (string)$merchantId,
            'order_num' => (string)$orderid,
        ];
        $request['sign'] = $this->sign($request, $signKey);
        $gateway = $this->resolveEndpoint(isset($channel['gateway']) ? $channel['gateway'] : '', self::QUERY_PATH);
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code . '_queryOrder', $orderid . '----查单返回', $httpResult['body']);
        $response = json_decode($httpResult['body'], true);
        $status = is_array($response) && isset($response['data']['status']) ? (string)$response['data']['status'] : '';
        return $httpResult['error'] === '' && (int)(isset($response['code']) ? $response['code'] : 0) === 200 && in_array($status, ['2', '3'], true)
            ? 1
            : ($httpResult['body'] !== '' ? $httpResult['body'] : $httpResult['error']);
    }

    private function readRequestData()
    {
        $rawBody = file_get_contents('php://input');
        $json = json_decode($rawBody, true);
        return is_array($json) ? $json : $_REQUEST;
    }

    private function findOrder($orderid)
    {
        if (!preg_match('/^\d{8}/', (string)$orderid, $matches)) {
            return [];
        }
        try {
            $model = D('Order');
            return (array)$model->table($model->getRealTableName(date('Y-m-d', strtotime($matches[0]))))->where(['pay_orderid' => $orderid])->find();
        } catch (\Exception $exception) {
            return [];
        }
    }

    private function resolveEndpoint($gateway, $path)
    {
        $gateway = trim((string)$gateway);
        if ($gateway === '') {
            return self::API_BASE . $path;
        }
        if (preg_match('#/api/Order/(?:createPayOrder|queryPayOrder)/?(?:\?.*)?$#i', $gateway)) {
            return preg_replace('#/api/Order/(?:createPayOrder|queryPayOrder)/?(?:\?.*)?$#i', $path, $gateway);
        }
        $configuredPath = parse_url($gateway, PHP_URL_PATH);
        return $configuredPath === '' || $configuredPath === '/' ? rtrim($gateway, '/') . $path : $gateway;
    }

    private function toCents($amount)
    {
        return (int)round((float)$amount * 100);
    }

    private function writeNotifyLog($orderid, array $data, $message)
    {
        try {
            logApiAddNotify($orderid, 0, $data, $message);
        } catch (\Exception $exception) {
            log_place_order($this->code . '_notifyurl', $orderid . '----通知日志异常', $exception->getMessage());
        }
    }

    private function respond($body, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $body;
    }
}
