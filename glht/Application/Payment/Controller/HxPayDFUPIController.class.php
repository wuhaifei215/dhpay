<?php

namespace Payment\Controller;

class HxPayDFUPIController extends PaymentController
{
    private const CREATE_PATH = '/pay/withdraw/create';
    private const QUERY_PATH = '/pay/withdraw/query';
    private const BALANCE_PATH = '/pay/account/balance';
    private const DEFAULT_PRODUCT_CODE = 'UPI_PAYOUT';

    private $code = '';

    public function __construct()
    {
        $matches = [];
        preg_match('/([\da-zA-Z_]+)Controller$/', __CLASS__, $matches);
        $this->code = isset($matches[1]) ? $matches[1] : 'HxPayDFUPI';
    }

    // 代付提交
    public function PaymentExec($data, $config)
    {
        $productCode = $this->configuredValue($config, 'appsecret', self::DEFAULT_PRODUCT_CODE);
        $gateway = $this->resolveEndpoint($config['exec_gateway'], self::CREATE_PATH);
        $notifyUrl = $this->buildNotifyUrl();

        $request = [
            'currency' => 'INR',
            'merchantId' => (string) $config['mch_id'],
            'merchantOrderNo' => (string) $data['orderid'],
            'nonce' => bin2hex(random_bytes(16)),
            'orderAmount' => sprintf('%.2f', $data['money']),
            'payeeAccount' => trim((string) $data['banknumber']),
            'productCode' => (string) $productCode,
            'timestamp' => (string) floor(microtime(true) * 1000),
            'payeeName' => trim((string) $data['bankfullname']),
            'payeeBankCode' => 'UPI',
            'payee_extra' => json_encode(['IFSC' => $data['bankzhiname']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'notifyUrl' => $notifyUrl,
        ];
        $request['signVersion'] = '2';
        $request['sign'] = $this->signRequest(
            self::CREATE_PATH,
            $request,
            $config['signkey']
        );

        log_place_order(
            $this->code,
            $data['orderid'] . '----提交',
            json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        log_place_order($this->code, $data['orderid'] . '----提交地址', $gateway);

        $beginTime = microtime(true);
        $httpResult = $this->postJson($gateway, $request);
        $response = json_decode($httpResult['body'], true);
        log_place_order($this->code, $data['orderid'] . '----返回', $httpResult['body']);

        try {
            $duration = floor((microtime(true) - $beginTime) * 1000);
            logApiAddPayment(
                '订单提交上游' . $this->code,
                __METHOD__,
                $data['orderid'],
                $data['out_trade_no'],
                $gateway,
                $request,
                is_array($response) ? $response : $httpResult,
                $duration,
                '0',
                '1',
                '2'
            );
        } catch (\Exception $exception) {
            log_place_order($this->code, $data['orderid'] . '----记录日志异常', $exception->getMessage());
        }

        return $this->buildPayoutResult($response, $httpResult);
    }

    // HxPay 终态异步通知
    public function notifyurl()
    {
        $rawBody = file_get_contents('php://input');
        $result = json_decode($rawBody, true);
        $orderid = is_array($result) && isset($result['merchantOrderNo'])
            ? (string) $result['merchantOrderNo']
            : '';

        log_place_order( $this->code . '_notifyurl', $orderid . '----异步回调', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) );

        $WttklistModel = D('Wttklist');
        $date = date('Ymd', strtotime(substr($orderid, 1, 8)));
        $tableName = $WttklistModel->getRealTableName($date);
        $order = $WttklistModel->table($tableName)->where(['orderid' => $orderid])->find();
        if (!$order) {
            log_place_order($this->code . '_notifyurl', $orderid . '----订单不存在', $orderid);
            $this->respondNotification('Order FAIL', 404);
            return;
        }

        $config = M('pay_for_another')->where(['code' => $this->code,'id' => $order['df_id']])->find();
        if (!$config) {
            log_place_order($this->code . '_notifyurl', $orderid . '----代付配置不存在', (string) $order['df_id']);
            $this->respondNotification('RETRY', 503);
            return;
        }

        $ip = $this->getNotifyIp();
        if (checkNotifyurlIp($ip, $config['notifyip']) !== true) {
            $message = 'IP异常:' . $ip;
            log_place_order($this->code . '_notifyurl', $orderid . '----IP异常', $ip . '===' . $config['notifyip']);
            $this->writeNotifyLog($orderid, $result, $message);
            $this->respondNotification('IP FAIL', 403);
            return;
        }

        $callbackPath = $this->currentRequestPath('/Payment_' . $this->code . '_notifyurl.html');
        if (!$this->verifyNotificationSignature(
            $callbackPath,
            (string) $config['mch_id'],
            $result,
            $config['signkey']
        )) {
            log_place_order($this->code . '_notifyurl', $orderid . '----签名错误', 'signature mismatch');
            $this->writeNotifyLog($orderid, $result, '签名错误');
            $this->respondNotification('Sign FAIL', 401);
            return;
        }

        $localAmount = isset($order['money']) ? $order['money'] : $order['tkmoney'];
        if (!$this->amountsEqual($result['amount'], $localAmount)) {
            log_place_order($this->code . '_notifyurl', $orderid . '----金额不一致', (string) $result['amount']);
            $this->writeNotifyLog($orderid, $result, '金额不一致');
            $this->respondNotification('Amount FAIL', 400);
            return;
        }

        $status = strtoupper((string) $result['status']);
        if ($status === 'SUCCESS') {
            $this->changeStatus($order['id'], 2, ['memo' => '代付成功'], $tableName);
            log_place_order($this->code . '_notifyurl', $orderid . '----订单更新', '代付成功');
        } elseif (in_array($status, ['FAILED', 'REJECT', 'REFUND', 'CLOSED'], true)) {
            $error = !empty($result['errorMsg'])
                ? (string) $result['errorMsg']
                : (isset($response['msg']) ? (string) $response['msg'] : $status);
            $this->changeStatus($order['id'],3,['memo' => '代付失败-' . $error],$tableName);

            log_place_order($this->code . '_notifyurl', $orderid . '----订单更新', '代付失败-' . $error);
        }

        $this->writeNotifyLog($orderid, $result, 'SUCCESS');
        $this->respondNotification('SUCCESS');
    }

    // 代付订单查询
    public function PaymentQuery($data, $config)
    {
        $orderid = isset($data['orderid']) ? (string) $data['orderid'] : '';
        $configuredGateway = isset($config['query_gateway']) && trim((string) $config['query_gateway']) !== ''
            ? $config['query_gateway']
            : (isset($config['exec_gateway']) ? $config['exec_gateway'] : '');
        $gateway = $this->resolveEndpoint($configuredGateway, self::QUERY_PATH);
        if ($gateway === '') {
            return ['status' => 7, 'msg' => '代付查询地址未配置'];
        }

        $request = [
            'merchantId' => (string) $config['mch_id'],
            'merchantOrderNo' => $orderid,
            'nonce' => bin2hex(random_bytes(16)),
            'timestamp' => (string) floor(microtime(true) * 1000),
        ];
        $request['signVersion'] = '2';
        $request['sign'] = $this->signRequest(self::QUERY_PATH, $request, $config['signkey']);

        log_place_order(
            $this->code . '_PaymentQuery',
            $orderid . '----提交',
            json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code . '_PaymentQuery', $orderid . '----返回', $httpResult['body']);

        if ($httpResult['error'] !== '') {
            return ['status' => 7, 'msg' => '查询接口连接失败：' . $httpResult['error']];
        }

        $response = json_decode($httpResult['body'], true);
        if (!is_array($response)) {
            return ['status' => 7, 'msg' => '查询接口响应格式异常'];
        }
        $responseCode = (int) (isset($response['code']) ? $response['code'] : 0);
        if ($responseCode !== 200) {
            return [
                'status' => 7,
                'msg' => '查询接口失败：' . (isset($response['msg']) ? $response['msg'] : $responseCode),
            ];
        }

        $result = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
        $orderStatus = strtoupper((string) (isset($result['orderStatus']) ? $result['orderStatus'] : ''));
        if (in_array($orderStatus, ['CREATED', 'PENDING'], true)) {
            return ['status' => 1, 'msg' => '处理中-' . $orderStatus];
        }
        if ($orderStatus === 'SUCCESS') {
            return ['status' => 2, 'msg' => '成功'];
        }
        if (in_array($orderStatus, ['FAILED', 'REJECT', 'REFUND', 'CLOSED'], true)) {
            $error = !empty($result['errorMsg'])
                ? (string) $result['errorMsg']
                : (isset($response['msg']) ? (string) $response['msg'] : $orderStatus);
            return ['status' => 3, 'msg' => '失败-' . $error, 'remark' => $error];
        }

        return ['status' => 7, 'msg' => '查询接口返回未知订单状态：' . $orderStatus];
    }

    // 后台账户余额查询
    public function queryBalance()
    {
        if (!IS_AJAX) {
            return;
        }

        $id = I('post.id', 1, 'intval');
        $config = M('pay_for_another')->where(['id' => $id, 'code' => $this->code])->find();
        if (!$config) {
            $this->ajaxReturn(['status' => 0, 'msg' => '代付配置不存在']);
            return;
        }

        $balanceResult = $this->requestBalance($config);
        if (!$balanceResult['success']) {
            $this->ajaxReturn(['status' => 0, 'msg' => $balanceResult['message']]);
            return;
        }

        $data = $balanceResult['data'];
        $availableBalance = htmlspecialchars((string) $data['availableBalance'], ENT_QUOTES, 'UTF-8');
        $blockedBalance = htmlspecialchars((string) $data['blockedBalance'], ENT_QUOTES, 'UTF-8');
        $totalBalance = htmlspecialchars((string) $data['totalBalance'], ENT_QUOTES, 'UTF-8');
        $currency = htmlspecialchars((string) $data['currency'], ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<style type="text/css">
table.hovertable {width:260px;font-family:verdana,arial,sans-serif;font-size:11px;color:#333333;border-width:1px;border-color:#999999;border-collapse:collapse;}
table.hovertable th {background-color:#c3dde0;border-width:1px;padding:8px;border-style:solid;border-color:#a9c6c9;}
table.hovertable tr {background-color:#f5f5f5;}
table.hovertable td {border-width:1px;padding:8px;border-style:solid;border-color:#a9c6c9;}
</style>
<table class="hovertable">
<tr><th>说明</th><th>值</th></tr>
<tr><td>币种</td><td><b>{$currency}</b></td></tr>
<tr><td>可用余额</td><td><b>{$availableBalance}</b></td></tr>
<tr><td>冻结余额</td><td><b>{$blockedBalance}</b></td></tr>
<tr><td>总余额</td><td><b>{$totalBalance}</b></td></tr>
</table>
HTML;
        $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
    }

    // 程序化账户余额查询，兼容现有代付通道约定。
    public function queryBalance2($config)
    {
        $balanceResult = $this->requestBalance($config);
        if (!$balanceResult['success']) {
            return ['resultCode' => '1', 'msg' => $balanceResult['message']];
        }

        return [
            'resultCode' => '0',
            'balance' => $balanceResult['data']['availableBalance'],
            'availableBalance' => $balanceResult['data']['availableBalance'],
            'blockedBalance' => $balanceResult['data']['blockedBalance'],
            'totalBalance' => $balanceResult['data']['totalBalance'],
            'currency' => $balanceResult['data']['currency'],
        ];
    }

    private function requestBalance(array $config)
    {
        if (isset($config['serverreturn']) && trim((string) $config['serverreturn']) !== '') {
            $configuredGateway = $config['serverreturn'];
        } elseif (isset($config['exec_gateway']) && trim((string) $config['exec_gateway']) !== '') {
            $configuredGateway = $config['exec_gateway'];
        } else {
            $configuredGateway = isset($config['query_gateway']) ? $config['query_gateway'] : '';
        }
        $gateway = $this->resolveEndpoint($configuredGateway, self::BALANCE_PATH);
        if ($gateway === '') {
            return ['success' => false, 'message' => '余额查询地址未配置', 'data' => []];
        }

        $request = [
            'merchantId' => (string) $config['mch_id'],
            'nonce' => bin2hex(random_bytes(16)),
            'timestamp' => (string) floor(microtime(true) * 1000),
        ];
        $request['signVersion'] = '2';
        $request['sign'] = $this->signRequest(self::BALANCE_PATH, $request, $config['signkey']);

        log_place_order(
            $this->code . '_queryBalance',
            '提交',
            json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code . '_queryBalance', '返回', $httpResult['body']);

        if ($httpResult['error'] !== '') {
            return ['success' => false, 'message' => '余额查询连接失败：' . $httpResult['error'], 'data' => []];
        }

        $response = json_decode($httpResult['body'], true);
        if (!is_array($response)) {
            return ['success' => false, 'message' => '余额查询响应格式异常', 'data' => []];
        }
        if ((int) (isset($response['code']) ? $response['code'] : 0) !== 200) {
            return [
                'success' => false,
                'message' => isset($response['msg']) ? (string) $response['msg'] : '余额查询失败',
                'data' => [],
            ];
        }

        $result = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
        foreach (['availableBalance', 'blockedBalance', 'totalBalance', 'currency'] as $field) {
            if (!array_key_exists($field, $result)) {
                return ['success' => false, 'message' => '余额查询响应缺少字段：' . $field, 'data' => []];
            }
        }

        return ['success' => true, 'message' => '成功', 'data' => $result];
    }

    private function buildPayoutResult($response, array $httpResult)
    {
        if ($httpResult['error'] !== '') {
            return ['status' => 0, 'msg' => '上游连接失败，订单结果未知，请勿更换订单号重试'];
        }
        if (!is_array($response)) {
            return ['status' => 0, 'msg' => '上游响应格式异常，订单结果未知'];
        }

        $code = (int) (isset($response['code']) ? $response['code'] : 0);
        $message = isset($response['msg']) ? (string) $response['msg'] : '上游代付响应异常';
        if ($code !== 200) {
            $definiteRejections = [
                40001, 40002, 40003, 40004, 40006, 40007, 40008, 40009, 40010,
                40012, 40013, 40014, 40015, 40018, 40019, 40500, 41500, 41210,
            ];
            return [
                'status' => in_array($code, $definiteRejections, true) ? 3 : 0,
                'msg' => $message,
            ];
        }

        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
        $orderStatus = strtoupper(isset($data['orderStatus']) ? (string) $data['orderStatus'] : '');
        if (in_array($orderStatus, ['CREATED', 'PENDING', 'SUCCESS'], true)) {
            return ['status' => 1, 'msg' => '申请正常-' . $orderStatus];
        }
        if (in_array($orderStatus, ['FAILED', 'REJECT', 'REFUND', 'CLOSED'], true)) {
            $error = !empty($data['errorMsg']) ? (string) $data['errorMsg'] : $message;
            return ['status' => 3, 'msg' => '申请失败-' . $error];
        }

        return ['status' => 0, 'msg' => '上游已响应但订单状态未知，请核对原订单'];
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
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
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
            (string) $parameters['merchantId'],
            $parameters,
            $key
        );
    }

    private function verifyNotificationSignature($path, $merchantId, array $parameters, $key)
    {
        $version = isset($parameters['signVersion']) ? (string) $parameters['signVersion'] : '';
        $signature = strpos($version, '2') !== false && !empty($parameters['signV2'])
            ? (string) $parameters['signV2']
            : (isset($parameters['sign']) ? (string) $parameters['sign'] : '');
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
            $merchantId,
            (string) $parameters['timestamp'],
            (string) $parameters['nonce'],
            $parameters
        );
        return hash_hmac('sha256', $canonical, (string) $key);
    }

    private function canonicalRequest(
        $method,
        $path,
        $merchantId,
        $timestamp,
        $nonce,
        array $parameters
    ) {
        unset($parameters['sign'], $parameters['signV2']);
        ksort($parameters, SORT_STRING);

        $pairs = [];
        foreach ($parameters as $name => $value) {
            if ($value === null || (string) $value === '') {
                continue;
            }
            $pairs[] = $name . '=' . $this->base64Url((string) $value);
        }

        return implode("\n", [
            strtoupper((string) $method),
            (string) $path,
            (string) $merchantId,
            (string) $timestamp,
            (string) $nonce,
            implode('&', $pairs),
        ]);
    }

    private function base64Url($value)
    {
        return rtrim(strtr(base64_encode((string) $value), '+/', '-_'), '=');
    }

    private function amountsEqual($left, $right)
    {
        $left = $this->normalizeAmount($left);
        $right = $this->normalizeAmount($right);
        return $left !== null && $left === $right;
    }

    private function normalizeAmount($value)
    {
        $text = trim((string) $value);
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
        return isset($config[$field]) && trim((string) $config[$field]) !== ''
            ? trim((string) $config[$field])
            : $default;
    }

    private function resolveEndpoint($gateway, $path)
    {
        $gateway = trim((string) $gateway);
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

    private function buildNotifyUrl()
    {
        $domain = trim((string) C('NOTIFY_DOMAIN'));
        $baseUrl = preg_match('#^https?://#i', $domain) ? $domain : 'https://' . $domain;
        return rtrim($baseUrl, '/') . '/Payment_' . $this->code . '_notifyurl.html';
    }

    private function currentRequestPath($fallback)
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
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
            logApiAddNotify($orderid, 1, $result, $message);
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
