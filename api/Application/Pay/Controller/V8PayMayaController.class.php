<?php

namespace Pay\Controller;

class V8PayMayaController extends PayController
{
    private const API_BASE = 'https://cdn-api.phpshanghu.org/phi-v2';
    private const CREATE_PATH = '/deposit/create';
    private const QUERY_PATH = '/deposit/transactions/';
    private const MAYA_WAKE_GATE = '004';

    private $code = '';

    public function __construct()
    {
        parent::__construct();
        $matches = [];
        preg_match('/([\da-zA-Z_]+)Controller$/', __CLASS__, $matches);
        $this->code = isset($matches[1]) ? $matches[1] : 'V8PayMaya';
    }

    // Maya 伪原生唤醒入款
    public function Pay($array)
    {
        $downstreamOrderId = I('request.pay_orderid', '');
        $parameter = [
            'code' => $this->code,
            'title' => 'Maya伪原生唤醒--V8Pay',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $downstreamOrderId,
            'channel' => $array,
            'body' => I('request.pay_productname', ''),
        ];
        $order = $this->orderadd($parameter);
        $order['status'] === 'error' && $this->showmessage($order['errorcontent']);

        $site = trim((string)$order['unlockdomain']) !== ''
            ? rtrim((string)$order['unlockdomain'], '/') . '/'
            : $this->_site;
        $callbackUrl = $site . 'Pay_' . $this->code . '_notifyurl.html';
        $user = trim((string)$order['appid']) !== '' ? (string)$order['appid'] : (string)$order['memberid'];
        $payload = [
            'gate' => self::MAYA_WAKE_GATE,
            'amount' => $this->numberValue($order['amount']),
            'referId' => (string)$order['orderid'],
            'user' => $user,
            'callbackUrl' => $callbackUrl,
        ];
        $json = $this->encodeJson($payload);
        $url = $this->resolveEndpoint($order['gateway'], self::CREATE_PATH);
        $token = trim((string)$order['mch_id']);
        $secret = trim((string)$order['signkey']);

        if ($json === false || $token === '' || $secret === '') {
            $result = ['status' => 'error', 'msg' => 'V8Pay入款Token、安全码或请求参数未配置'];
        } else {
            $checksum = $this->checksum($json, $secret);
            log_place_order($this->code, $order['orderid'] . '----提交', $json);
            log_place_order($this->code, $order['orderid'] . '----提交地址', $url);
            $beginTime = microtime(true);
            $httpResult = $this->postJson($url, $json, $token, $checksum);
            log_place_order($this->code, $order['orderid'] . '----返回', $httpResult['body']);
            $response = json_decode($httpResult['body'], true);
            $result = $this->buildPayResult($response, $httpResult, $downstreamOrderId, $order);

            try {
                $duration = floor((microtime(true) - $beginTime) * 1000);
                logApiAddReceipt(
                    '订单提交上游' . $this->code,
                    __METHOD__,
                    $order['orderid'],
                    $order['out_trade_id'],
                    $url,
                    $payload,
                    is_array($response) ? $response : $httpResult,
                    $duration,
                    '0',
                    '1',
                    '2'
                );
            } catch (\Exception $exception) {
                log_place_order($this->code, $order['orderid'] . '----记录日志异常', $exception->getMessage());
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // V8Pay 入款终态通知
    public function notifyurl()
    {
        $rawBody = file_get_contents('php://input');
        $notification = json_decode($rawBody, true);
        $payload = is_array($notification) && isset($notification['payload']) && is_array($notification['payload'])
            ? $notification['payload']
            : [];
        $orderid = !empty($payload['referId']) ? (string)$payload['referId'] : (isset($payload['searchId']) ? (string)$payload['searchId'] : '');
        log_place_order($this->code . '_notifyurl', $orderid . '----异步回调', $rawBody);

        $order = $this->findOrder($orderid);
        if (!$order) {
            $this->respond('Order FAIL', 404);
            return;
        }
        $ip = $this->getNotifyIp();
        if (check_IP($order['channel_id'], $ip, $orderid) !== true) {
            $this->writeNotifyLog($orderid, $notification, 'IP异常:' . $ip);
            $this->respond('IP FAIL', 403);
            return;
        }

        $account = !empty($order['account_id'])
            ? M('ChannelAccount')->where(['id' => (int)$order['account_id']])->find()
            : [];
        $channel = !empty($order['channel_id'])
            ? M('Channel')->where(['id' => (int)$order['channel_id']])->find()
            : [];
        // 入款 AES Secret Key 配置在子账号 appsecret，未配置子账号时回退通道 appsecret。
        $aesKey = !empty($account['appsecret']) ? (string)$account['appsecret'] : (isset($channel['appsecret']) ? (string)$channel['appsecret'] : '');
        if (!$this->verifyWebhook($notification, $aesKey)) {
            $this->writeNotifyLog($orderid, $notification, 'AES签名错误');
            $this->respond('Sign FAIL', 401);
            return;
        }
        if (!$this->amountsEqual(isset($payload['amount']) ? $payload['amount'] : null, $order['pay_amount'])) {
            $this->writeNotifyLog($orderid, $notification, '金额不一致');
            $this->respond('Amount FAIL', 400);
            return;
        }

        $state = strtolower((string)(isset($payload['state']) ? $payload['state'] : ''));
        if (!in_array($state, ['success', 'delete'], true)) {
            $this->writeNotifyLog($orderid, $notification, '未知回调状态-' . $state);
            $this->respond('State FAIL', 400);
            return;
        }
        if ($state === 'success') {
            $this->EditMoney($orderid, $this->code, 0);
        }
        $this->writeNotifyLog($orderid, $notification, 'SUCCESS-' . $state);
        $this->respond('SUCCESS');
    }

    // 入款订单查询，兼容现有补单接口。
    public function queryOrder($orderid, $memberid, $key)
    {
        $order = $this->findOrder($orderid);
        $token = !empty($order['memberid']) ? (string)$order['memberid'] : (string)$memberid;
        $channel = !empty($order['channel_id'])
            ? M('Channel')->where(['id' => (int)$order['channel_id']])->find()
            : M('Channel')->where(['code' => $this->code])->find();
        $url = $this->resolveEndpoint(isset($channel['gateway']) ? $channel['gateway'] : '', self::QUERY_PATH . rawurlencode((string)$orderid));
        $httpResult = $this->getJson($url, 'TransactionToken', $token);
        log_place_order($this->code . '_queryOrder', $orderid . '----查单返回', $httpResult['body']);
        $response = json_decode($httpResult['body'], true);
        $state = is_array($response) && isset($response['data']['state'])
            ? strtolower((string)$response['data']['state'])
            : '';
        if ($httpResult['error'] === '' && !empty($response['success']) && $state === 'success') {
            return 1;
        }
        return $httpResult['body'] !== '' ? $httpResult['body'] : $httpResult['error'];
    }

    private function buildPayResult($response, array $httpResult, $downstreamOrderId, array $order)
    {
        if ($httpResult['error'] !== '') {
            return ['status' => 'error', 'msg' => 'V8Pay连接失败，订单结果未知，请勿更换订单号重试'];
        }
        if (!is_array($response)) {
            return ['status' => 'error', 'msg' => 'V8Pay响应格式异常'];
        }
        if (empty($response['success'])) {
            return ['status' => 'error', 'msg' => isset($response['message']) ? (string)$response['message'] : 'V8Pay入款创建失败'];
        }
        $redirectUrl = isset($response['data']['redirectUrl']) ? trim((string)$response['data']['redirectUrl']) : '';
        if ($redirectUrl === '') {
            return ['status' => 'error', 'msg' => 'V8Pay未返回redirectUrl'];
        }
        return [
            'status' => 'success',
            'H5_url' => $redirectUrl,
            'pay_orderid' => $downstreamOrderId,
            'out_trade_id' => $order['orderid'],
            'amount' => $order['amount'],
            'datetime' => date('Y-m-d'),
        ];
    }

    private function checksum($json, $secret)
    {
        return base64_encode(hash_hmac('sha256', $json, $secret, true));
    }

    private function verifyWebhook(array $notification, $aesKey)
    {
        if (trim((string)$aesKey) === '' || empty($notification['checksum']) || empty($notification['md5'])) {
            return false;
        }
        if (empty($notification['algorithm']['iv']) || strtolower((string)$notification['algorithm']['type']) !== 'aes-256-cbc') {
            return false;
        }
        $decrypted = openssl_decrypt(
            (string)$notification['checksum'],
            'aes-256-cbc',
            (string)$aesKey,
            0,
            (string)$notification['algorithm']['iv']
        );
        return is_string($decrypted) && hash_equals((string)$notification['md5'], $decrypted);
    }

    private function postJson($url, $json, $token, $checksum)
    {
        return $this->httpRequest($url, 'POST', [
            'Content-Type: application/json',
            'TransactionToken: ' . $token,
            'Checksum: ' . $checksum,
        ], $json);
    }

    private function getJson($url, $headerName, $token)
    {
        return $this->httpRequest($url, 'GET', [$headerName . ': ' . $token]);
    }

    private function httpRequest($url, $method, array $headers, $body = null)
    {
        if (trim((string)$url) === '') {
            return ['body' => '', 'httpStatus' => 0, 'error' => '接口地址未配置'];
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }
        $responseBody = curl_exec($curl);
        $error = $responseBody === false ? curl_error($curl) : '';
        $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return ['body' => $responseBody === false ? '' : $responseBody, 'httpStatus' => $httpStatus, 'error' => $error];
    }

    private function findOrder($orderid)
    {
        $dateText = substr((string)$orderid, 0, 8);
        if (!preg_match('/^\d{8}$/', $dateText)) {
            return [];
        }
        try {
            $model = D('Order');
            return (array)$model->table($model->getRealTableName(date('Y-m-d', strtotime($dateText))))->where(['pay_orderid' => $orderid])->find();
        } catch (\Exception $exception) {
            return [];
        }
    }

    private function resolveEndpoint($configured, $path)
    {
        $configured = trim((string)$configured);
        if ($configured === '') {
            return self::API_BASE . $path;
        }
        if (preg_match('#/phi-v2(?:/.*)?$#i', $configured)) {
            return preg_replace('#/phi-v2(?:/.*)?$#i', '/phi-v2' . $path, $configured);
        }
        $configuredPath = parse_url($configured, PHP_URL_PATH);
        return $configuredPath === '' || $configuredPath === '/' ? rtrim($configured, '/') . '/phi-v2' . $path : $configured;
    }

    private function encodeJson(array $payload)
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? false : $json;
    }

    private function numberValue($value)
    {
        $number = (float)$value;
        return floor($number) == $number ? (int)$number : $number;
    }

    private function amountsEqual($left, $right)
    {
        return is_numeric($left) && is_numeric($right) && abs((float)$left - (float)$right) < 0.0001;
    }

    private function getNotifyIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : getRealIp();
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
