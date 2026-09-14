<?php

namespace Pay\Controller;

class HxPayUPIController extends PayController
{
    private const CREATE_PATH = '/pay/recharge/create';
    private const QUERY_PATH = '/pay/recharge/query';
    private const DEFAULT_PRODUCT_CODE = 'UPI_INTENT';

    private $code = '';

    public function __construct()
    {
        parent::__construct();

        $matches = [];
        preg_match('/([\da-zA-Z_]+)Controller$/', __CLASS__, $matches);
        $this->code = isset($matches[1]) ? $matches[1] : 'HxPayUPI';
    }

    // 代收提交
    public function Pay($array)
    {
        $orderid = I('request.pay_orderid', '');
        $body = I('request.pay_productname', '');
        $parameter = [
            'code' => $this->code,
            'title' => 'UPI--HxPay',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'channel' => $array,
            'body' => $body,
        ];

        $return = $this->orderadd($parameter);
        $return['status'] === 'error' && $this->showmessage($return['errorcontent']);

        $site = trim($return['unlockdomain'])
            ? rtrim($return['unlockdomain'], '/') . '/'
            : $this->_site;
        $notifyUrl = $site . 'Pay_' . $this->code . '_notifyurl.html';
        $productCode = $this->configuredValue($return, 'subappid', self::DEFAULT_PRODUCT_CODE);
        $gateway = $this->resolveEndpoint($return['gateway'], self::CREATE_PATH);

        $request = [
            'currency' => 'INR',
            'merchantId' => (string)$return['mch_id'],
            'merchantOrderNo' => (string)$return['orderid'],
            'nonce' => bin2hex(random_bytes(16)),
            'orderAmount' => sprintf('%.2f', $return['amount']),
            'productCode' => (string)$productCode,
            'timestamp' => (string)floor(microtime(true) * 1000),
            'notifyUrl' => $notifyUrl,
        ];
        $request['signVersion'] = '2';
        $request['sign'] = $this->signRequest(
            self::CREATE_PATH,
            $request,
            $return['signkey']
        );

        log_place_order(
            $this->code,
            $return['orderid'] . '----提交',
            json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        log_place_order($this->code, $return['orderid'] . '----提交地址', $gateway);

        $beginTime = microtime(true);
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code, $return['orderid'] . '----返回', $httpResult['body']);

        $response = json_decode($httpResult['body'], true);
        $returnArr = $this->buildPayinResult($response, $httpResult, $orderid, $return);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($returnArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $redis = $this->redis_connect();
            $userpost = json_decode($redis->get('userpost_' . $return['out_trade_id']), true);

            logApiAddReceipt('下游商户提交', __METHOD__, $return['orderid'], $return['out_trade_id'], '/', $userpost, $returnArr, '0', '0', '1', '2');

            $duration = floor((microtime(true) - $beginTime) * 1000);
            logApiAddReceipt('订单提交上游' . $this->code,__METHOD__,$return['orderid'],$return['out_trade_id'],$gateway,$request,is_array($response) ? $response : $httpResult,$duration,'0','1','2');
        } catch (\Exception $exception) {
            log_place_order($this->code, $return['orderid'] . '----记录日志异常', $exception->getMessage());
        }

        exit;
    }

    // 同步通知仅用于页面跳转，不能把待支付订单直接置为成功。
    public function callbackurl()
    {
        $outTradeNo = I('get.orderid', '', 'string,strip_tags,htmlspecialchars');
        if ($outTradeNo === '') {
            exit('error');
        }

        $Order = M('Order');
        $payStatus = $Order->where(['pay_orderid' => $outTradeNo])->getField('pay_status');
        if ((int)$payStatus !== 0) {
            $this->EditMoney($outTradeNo, $this->code, 1);
            return;
        }

        exit('error');
    }

    // HxPay 终态异步通知
    public function notifyurl()
    {
        $rawBody = file_get_contents('php://input');
        $result = json_decode($rawBody, true);
        $orderid = is_array($result) && isset($result['merchantOrderNo']) ? (string)$result['merchantOrderNo'] : '';

        log_place_order(
            $this->code . '_notifyurl',
            $orderid . '----异步回调',
            json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $OrderModel = D('Order');
        $date = date('Ymd', strtotime(substr($orderid, 0, 8)));
        $tableName = $OrderModel->getRealTableName($date);
        $order = $OrderModel->table($tableName)->where(['pay_orderid' => $orderid])->find();
        if (!$order) {
            log_place_order($this->code . '_notifyurl', $orderid . '----订单不存在', $orderid);
            $this->respondNotification('Order FAIL', 404);
            return;
        }

        $ip = $this->getNotifyIp();
        if (check_IP($order['channel_id'], $ip, $orderid) !== true) {
            $message = 'IP异常:' . $ip;
            log_place_order($this->code . '_notifyurl', $orderid . '----IP异常', $ip);
            $this->writeNotifyLog($orderid, $result, $message);
            $this->respondNotification('IP FAIL', 403);
            return;
        }

        $callbackPath = $this->currentRequestPath('/Pay_' . $this->code . '_notifyurl.html');
        if (!$this->verifyNotificationSignature(
            $callbackPath,
            (string)$order['memberid'],
            $result,
            $order['key']
        )) {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误', 'signature mismatch');
            $this->writeNotifyLog($orderid, $result, '签名错误');
            $this->respondNotification('sign FAIL', 401);
            return;
        }

        if (!$this->amountsEqual($result['amount'], $order['pay_amount'])) {
            log_place_order($this->code . '_notifyurl', $orderid . '----金额不一致', (string)$result['amount']);
            $this->writeNotifyLog($orderid, $result, '金额不一致');
            $this->respondNotification('amount FAIL', 400);
            return;
        }

        $status = strtoupper((string)$result['status']);
        if ($status === 'SUCCESS') {
            $updated = $this->EditMoney($orderid, $this->code, 0);
            if ($updated === false) {
                log_place_order($this->code . '_notifyurl', $orderid . '----订单更新', '幂等或更新未执行');
            } else {
                log_place_order($this->code . '_notifyurl', $orderid . '----订单更新', '代收成功');
            }
        } else {
            log_place_order($this->code . '_notifyurl', $orderid . '----订单终态', $status);
        }

        $this->writeNotifyLog($orderid, $result, 'SUCCESS');
        $this->respondNotification('SUCCESS');
    }

    // 代收订单查询；成功订单返回 1，其他情况返回上游原始响应，兼容现有补单调用约定。
    public function queryOrder($orderid, $memberid, $key)
    {
        $order = $this->findLocalOrder($orderid);
        $merchantId = !empty($order['memberid']) ? (string)$order['memberid'] : (string)$memberid;
        $signKey = !empty($order['key']) ? (string)$order['key'] : (string)$key;
        $channelId = isset($order['channel_id']) ? (int)$order['channel_id'] : 0;
        $channel = $channelId > 0
            ? M('Channel')->where(['id' => $channelId])->find()
            : M('Channel')->where(['code' => $this->code])->find();

        $account = [];
        if (!empty($order['account_id'])) {
            $account = M('ChannelAccount')->where(['id' => (int)$order['account_id']])->find();
        } elseif (!empty($channel['id'])) {
            $account = M('ChannelAccount')->where([
                'channel_id' => (int)$channel['id'],
                'mch_id' => $merchantId,
            ])->find();
        }

        $gateway = $this->resolveEndpoint(isset($channel['gateway']) ? $channel['gateway'] : '', self::QUERY_PATH);
        if ($gateway === '') {
            $message = 'HxPay代收查询地址未配置';
            log_place_order($this->code . '_queryOrder', $orderid . '----查询失败', $message);
            return $message;
        }

        $request = [
            'merchantId' => $merchantId,
            'merchantOrderNo' => (string)$orderid,
            'nonce' => bin2hex(random_bytes(16)),
            'timestamp' => (string)floor(microtime(true) * 1000),
        ];
        $request['signVersion'] = '2';
        $request['sign'] = $this->signRequest(self::QUERY_PATH, $request, $signKey);

        log_place_order(
            $this->code . '_queryOrder',
            $orderid . '----查单提交',
            json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code . '_queryOrder', $orderid . '----查单返回', $httpResult['body']);

        $response = json_decode($httpResult['body'], true);
        if (
            $httpResult['error'] === ''
            && is_array($response)
            && (int)(isset($response['code']) ? $response['code'] : 0) === 200
            && strtoupper((string)(isset($response['data']['orderStatus']) ? $response['data']['orderStatus'] : '')) === 'SUCCESS'
        ) {
            return 1;
        }

        return $httpResult['body'] !== '' ? $httpResult['body'] : $httpResult['error'];
    }

    private function findLocalOrder($orderid)
    {
        $dateText = substr((string)$orderid, 0, 8);
        if (!preg_match('/^\d{8}$/', $dateText)) {
            return [];
        }

        try {
            $OrderModel = D('Order');
            return (array)$OrderModel
                ->table($OrderModel->getRealTableName(date('Y-m-d', strtotime($dateText))))
                ->where(['pay_orderid' => (string)$orderid])
                ->find();
        } catch (\Exception $exception) {
            log_place_order($this->code . '_queryOrder', $orderid . '----本地订单读取异常', $exception->getMessage());
            return [];
        }
    }

    private function buildPayinResult($response, array $httpResult, $downstreamOrderId, array $order)
    {
        if ($httpResult['error'] !== '') {
            return ['status' => 'error', 'msg' => '上游连接失败，订单结果未知，请勿更换订单号重试'];
        }
        if (!is_array($response)) {
            return ['status' => 'error', 'msg' => '上游响应格式异常，订单结果未知'];
        }
        if ((int)(isset($response['code']) ? $response['code'] : 0) !== 200) {
            return [
                'status' => 'error',
                'msg' => isset($response['msg']) ? (string)$response['msg'] : '上游下单失败',
            ];
        }

        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
        $orderStatus = strtoupper(isset($data['orderStatus']) ? (string)$data['orderStatus'] : '');
        if (!in_array($orderStatus, ['CREATED', 'PENDING', 'SUCCESS'], true)) {
            return [
                'status' => 'error',
                'msg' => isset($data['errorMsg']) && $data['errorMsg'] !== null
                    ? (string)$data['errorMsg']
                    : (isset($response['msg']) ? (string)$response['msg'] : '上游未受理订单'),
            ];
        }

        $result = [
            'status' => 'success',
            'pay_orderid' => $downstreamOrderId,
            'out_trade_id' => $order['orderid'],
            'amount' => sprintf('%.2f', $order['amount']),
            'datetime' => date('Y-m-d'),
        ];
        if (!empty($data['payUrl'])) {
            $result['H5_url'] = (string)$data['payUrl'];
        }
        if (!empty($data['payContent'])) {
            $result['QRcode'] = (string)$data['payContent'];
        }
        if (!isset($result['H5_url']) && !isset($result['QRcode']) && $orderStatus !== 'SUCCESS') {
            return ['status' => 'error', 'msg' => '上游已受理但未返回支付信息，请核对原订单'];
        }

        return $result;
    }

    private function postJson($url, array $payload)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['body' => '', 'httpStatus' => 0, 'requestId' => '', 'error' => 'JSON encode failed'];
        }

        $requestId = bin2hex(random_bytes(16));
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=UTF-8',
                'Accept: application/json',
                'Content-Length: ' . strlen($json),
                'X-Request-Id: ' . $requestId,
            ],
            CURLOPT_POSTFIELDS => $json,
        ]);

        $body = curl_exec($curl);
        $error = $body === false ? curl_error($curl) : '';
        $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'body' => $body === false ? '' : $body,
            'httpStatus' => $httpStatus,
            'requestId' => $requestId,
            'error' => $error,
        ];
    }

    private function signRequest($path, array $parameters, $key)
    {
        unset($parameters['signVersion'], $parameters['payPassword'], $parameters['remark']);
        return $this->hmacSignature(
            $path,
            (string)$parameters['merchantId'],
            $parameters,
            $key
        );
    }

    private function verifyNotificationSignature($path, $merchantId, array $parameters, $key)
    {
        $version = isset($parameters['signVersion']) ? (string)$parameters['signVersion'] : '';
        $signature = strpos($version, '2') !== false && !empty($parameters['signV2'])
            ? (string)$parameters['signV2']
            : (isset($parameters['sign']) ? (string)$parameters['sign'] : '');
        if (strpos($version, '2') === false || !preg_match('/^[a-fA-F0-9]{64}$/', $signature)) {
            return false;
        }

        $expected = $this->hmacSignature($path, $merchantId, $parameters, $key);
        return hash_equals(strtolower($expected), strtolower($signature));
    }

    private function hmacSignature($path, $merchantId, array $parameters, $key)
    {
        $canonical = $this->canonicalRequest(
            'POST',
            $path,
            (string)$merchantId,
            (string)$parameters['timestamp'],
            (string)$parameters['nonce'],
            $parameters
        );

        return hash_hmac('sha256', $canonical, (string)$key);
    }

    private function canonicalRequest(
        $method,
        $path,
        $merchantId,
        $timestamp,
        $nonce,
        array $parameters
    )
    {
        unset($parameters['sign'], $parameters['signV2']);
        ksort($parameters, SORT_STRING);

        $pairs = [];
        foreach ($parameters as $name => $value) {
            if ($value === null || (string)$value === '') {
                continue;
            }
            $pairs[] = $name . '=' . $this->base64Url((string)$value);
        }

        return implode("\n", [
            strtoupper((string)$method),
            (string)$path,
            (string)$merchantId,
            (string)$timestamp,
            (string)$nonce,
            implode('&', $pairs),
        ]);
    }

    private function base64Url($value)
    {
        return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '=');
    }

    private function amountsEqual($left, $right)
    {
        $left = $this->normalizeAmount($left);
        $right = $this->normalizeAmount($right);
        return $left !== null && $left === $right;
    }

    private function normalizeAmount($value)
    {
        $text = trim((string)$value);
        if (!preg_match('/^(\d+)(?:\.(\d{1,4}))?$/', $text, $matches)) {
            return null;
        }
        $integer = ltrim($matches[1], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = isset($matches[2]) ? $matches[2] : '';
        return $integer . '.' . str_pad($fraction, 4, '0');
    }

    private function configuredValue(array $config, $field, $default)
    {
        return isset($config[$field]) && trim((string)$config[$field]) !== ''
            ? trim((string)$config[$field])
            : $default;
    }

    private function resolveEndpoint($gateway, $path)
    {
        $gateway = trim((string)$gateway);
        if ($gateway === '') {
            return '';
        }
        if (preg_match('#/pay/(?:recharge|withdraw)/(?:create|query)/?(?:\?.*)?$#i', $gateway)) {
            return preg_replace('#/pay/(?:recharge|withdraw)/(?:create|query)/?(?:\?.*)?$#i', $path, $gateway);
        }
        if (preg_match('#/pay/account/balance/?(?:\?.*)?$#i', $gateway)) {
            return preg_replace('#/pay/account/balance/?(?:\?.*)?$#i', $path, $gateway);
        }
        $configuredPath = parse_url($gateway, PHP_URL_PATH);
        if ($gateway !== '' && ($configuredPath === null || $configuredPath === '' || $configuredPath === '/')) {
            return rtrim($gateway, '/') . $path;
        }
        return $gateway;
    }

    private function currentRequestPath($fallback)
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
        $path = parse_url($requestUri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : $fallback;
    }

    private function getNotifyIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return getRealIp();
    }

    private function writeNotifyLog($orderid, array $result, $message)
    {
        try {
            logApiAddNotify($orderid, 0, $result, $message);
        } catch (\Exception $exception) {
            log_place_order($this->code . '_notifyurl', $orderid . '----通知日志异常', $exception->getMessage());
        }
    }

    private function respondNotification($body, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $body;
    }
}
