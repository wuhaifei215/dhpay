<?php

namespace Pay\Controller;

class QQPayIdrDanaController extends PayController
{
    private const API_BASE = 'https://apiidn.qqpay.shop';
    private const CREATE_PATH = '/api/Order/createPayOrder';
    private const QUERY_PATH = '/api/Order/queryPayOrder';
    private const TYPE_ID = '514';

    private $code = '';

    public function __construct()
    {
        parent::__construct();
        $matches = [];
        preg_match('/([\da-zA-Z_]+)Controller$/', __CLASS__, $matches);
        $this->code = isset($matches[1]) ? $matches[1] : 'IdrDana';
    }

    // DANA 代收
    public function Pay($array)
    {
        $downstreamOrderId = I('request.pay_orderid', '');
        $parameter = [
            'code' => $this->code,
            'title' => 'IdrDana-QQPay',
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
        $returnUrl = I('request.pay_callbackurl', '', 'string,strip_tags,htmlspecialchars');
        if ($returnUrl === '') {
            $returnUrl = $site . 'Pay_' . $this->code . '_callbackurl.html?orderid=' . rawurlencode($order['orderid']);
        }

        $request = [
            'version' => '1.0.0',
            'mch_id' => (string)$order['mch_id'],
            'type_id' => self::TYPE_ID,
            'currency' => 'IDR',
            'order_num' => (string)$order['orderid'],
            'total_fee' => $this->toCents($order['amount']),
            'notify_url' => (string)$order['notifyurl'],
            'return_url' => $returnUrl,
            'subject' => trim((string)$parameter['body']) !== '' ? (string)$parameter['body'] : 'all',
            'order_at' => date('Y-m-d H:i:s'),
        ];
        $request['sign'] = $this->sign($request, $order['signkey']);
        $gateway = $this->resolveEndpoint($order['gateway'], self::CREATE_PATH);
        log_place_order($this->code, $order['orderid'] . '----提交', json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        log_place_order($this->code, $order['orderid'] . '----提交地址', $gateway);

        $beginTime = microtime(true);
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code, $order['orderid'] . '----返回', $httpResult['body']);
        $response = json_decode($httpResult['body'], true);
        $result = $this->buildPayResult($response, $httpResult, $downstreamOrderId, $order);

        try {
            $duration = floor((microtime(true) - $beginTime) * 1000);
            logApiAddReceipt('订单提交上游' . $this->code, __METHOD__, $order['orderid'], $order['out_trade_id'], $gateway, $request, is_array($response) ? $response : $httpResult, $duration, '0', '1', '2');
        } catch (\Exception $exception) {
            log_place_order($this->code, $order['orderid'] . '----记录日志异常', $exception->getMessage());
        }

        if ((int)$array['userid'] === 2 && $result['status'] === 'success' && !empty($result['H5_url'])) {
            header('Location: ' . $result['H5_url']);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

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

    public function notifyurl()
    {
        $result = $this->readRequestData();
        $orderid = isset($result['order_num']) ? (string)$result['order_num'] : '';
        log_place_order($this->code . '_notifyurl', $orderid . '----异步回调', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $order = $this->findOrder($orderid);
        if (!$order) {
            $this->respond('FAIL', 404);
            return;
        }
        if ($this->verifyIpWhitelist($order['channel_id'], $orderid) !== true) {
            $this->writeNotifyLog($orderid, $result, 'IP异常:' . $this->getClientIp());
            $this->respond('FAIL', 403);
            return;
        }
        if (empty($result['sign']) || !hash_equals($this->sign($result, $order['key']), strtoupper((string)$result['sign']))) {
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

    // 代收订单查询，成功返回1，兼容现有补单调用约定。
    public function queryOrder($orderid, $memberid, $key)
    {
        $order = $this->findOrder($orderid);
        $merchantId = !empty($order['memberid']) ? $order['memberid'] : $memberid;
        $signKey = !empty($order['key']) ? $order['key'] : $key;
        $channel = !empty($order['channel_id'])
            ? M('Channel')->where(['id' => (int)$order['channel_id']])->find()
            : M('Channel')->where(['code' => $this->code])->find();
        $gateway = $this->resolveEndpoint(isset($channel['gateway']) ? $channel['gateway'] : '', self::QUERY_PATH);
        $request = [
            'version' => '1.0.0',
            'mch_id' => (string)$merchantId,
            'order_num' => (string)$orderid,
        ];
        $request['sign'] = $this->sign($request, $signKey);
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code . '_queryOrder', $orderid . '----查单返回', $httpResult['body']);
        $response = json_decode($httpResult['body'], true);
        $status = is_array($response) && isset($response['data']['status']) ? (string)$response['data']['status'] : '';
        return $httpResult['error'] === '' && (int)(isset($response['code']) ? $response['code'] : 0) === 200 && in_array($status, ['2', '3'], true)
            ? 1
            : ($httpResult['body'] !== '' ? $httpResult['body'] : $httpResult['error']);
    }

    private function buildPayResult($response, array $httpResult, $downstreamOrderId, array $order)
    {
        if ($httpResult['error'] !== '') {
            return ['status' => 'error', 'msg' => '连接失败，订单结果未知，请勿更换订单号重试'];
        }
        if (!is_array($response) || (int)(isset($response['code']) ? $response['code'] : 0) !== 200) {
            return ['status' => 'error', 'msg' => is_array($response) && isset($response['msg']) ? $response['msg'] : '响应异常'];
        }
        $payParams = isset($response['data']['pay_params']) && is_array($response['data']['pay_params']) ? $response['data']['pay_params'] : [];
        $payUrl = isset($payParams['pay_url']) ? (string)$payParams['pay_url'] : '';
        $qrcode = isset($payParams['qrcode']) ? (string)$payParams['qrcode'] : '';
        if ($payUrl === '' && $qrcode === '') {
            return ['status' => 'error', 'msg' => '未返回支付链接或二维码'];
        }
        return [
            'status' => 'success',
            'H5_url' => $payUrl,
            'QRcode' => $qrcode,
            'pay_orderid' => $downstreamOrderId,
            'out_trade_id' => $order['orderid'],
            'amount' => sprintf('%.2f', $order['amount']),
            'datetime' => date('Y-m-d'),
        ];
    }

    private function postJson($url, array $data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || trim((string)$url) === '') {
            return ['body' => '', 'httpStatus' => 0, 'error' => '请求参数或地址无效'];
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Content-Length: ' . strlen($json)],
            CURLOPT_POSTFIELDS => $json,
        ]);
        $body = curl_exec($curl);
        $error = $body === false ? curl_error($curl) : '';
        $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return ['body' => $body === false ? '' : $body, 'httpStatus' => $httpStatus, 'error' => $error];
    }

    private function sign(array $parameters, $key)
    {
        unset($parameters['sign']);
        $parameters = array_filter($parameters, function ($value) {
            return $value !== '' && $value !== null;
        });
        ksort($parameters, SORT_STRING);
        $pairs = [];
        foreach ($parameters as $name => $value) {
            $pairs[] = $name . '=' . (string)$value;
        }
        $pairs[] = 'key=' . (string)$key;
        return strtoupper(md5(implode('&', $pairs)));
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
