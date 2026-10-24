<?php

namespace Payment\Controller;

class QQPayIdrDFController extends PaymentController
{
    private const API_BASE = 'https://apiidn.qqpay.shop';
    private const CREATE_PATH = '/api/Daifu/createOrder';
    private const QUERY_PATH = '/api/Daifu/queryOrder';
    private const BALANCE_PATH = '/api/Member/blance';

    private $code = '';

    public function __construct()
    {
        $matches = [];
        preg_match('/([\da-zA-Z_]+)Controller$/', __CLASS__, $matches);
        $this->code = isset($matches[1]) ? $matches[1] : 'IdrDF';
    }

    // QQPay 印尼代付
    public function PaymentExec($data, $config)
    {
        $channel = $this->resolvePayoutChannel($data);
        if (!$channel['success']) {
            return ['status' => 3, 'msg' => $channel['message']];
        }

        $request = [
            'version' => '1.0.0',
            'mch_id' => (string)$config['mch_id'],
            'type_id' => $channel['typeId'],
            'order_num' => (string)$data['orderid'],
            'currency' => 'IDR',
            'order_fee' => intval($data['money'] * 100),
            'account_name' => trim((string)$data['bankfullname']),
            'account_no' => trim((string)$data['banknumber']),
            'bank_number' => $channel['bankNumber'],
            'notify_url' => 'https://' . C('NOTIFY_DOMAIN') . "/Payment_" . $this->code . "_notifyurl.html",
            'order_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['out_trade_no'])) {
            $request['attach'] = substr((string)$data['out_trade_no'], 0, 100);
        }
        $request['sign'] = $this->sign($request, $config['signkey']);
        log_place_order($this->code, $data['orderid'] . '----提交', json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $gateway = $this->resolveEndpoint(isset($config['exec_gateway']) ? $config['exec_gateway'] : '', self::CREATE_PATH);
        log_place_order($this->code, $data['orderid'] . '----提交地址', $gateway);

        $beginTime = microtime(true);
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code, $data['orderid'] . '----返回', $httpResult['body']);
        $response = json_decode($httpResult['body'], true);
        try {
            $duration = floor((microtime(true) - $beginTime) * 1000);
            logApiAddPayment('订单提交上游' . $this->code, __METHOD__, $data['orderid'], $data['out_trade_no'], $gateway, $request, is_array($response) ? $response : $httpResult, $duration, '0', '1', '2');
        } catch (\Exception $exception) {
            log_place_order($this->code, $data['orderid'] . '----记录日志异常', $exception->getMessage());
        }

        if ($httpResult['error'] !== '') {
            return ['status' => 0, 'msg' => '连接失败，订单结果未知，请勿更换订单号重试'];
        }
        if (!is_array($response)) {
            return ['status' => 0, 'msg' => '响应格式异常，订单结果未知'];
        }
        $code = (int)(isset($response['code']) ? $response['code'] : 0);
        if ($code === 200) {
            return ['status' => 1, 'msg' => '申请正常'];
        }
        return [
            'status' => $code === 400 ? 3 : 0,
            'msg' => isset($response['msg']) ? (string)$response['msg'] : '代付创建失败',
        ];
    }

    public function notifyurl()
    {
        $result = $this->readRequestData();
        $orderid = isset($result['order_num']) ? (string)$result['order_num'] : '';
        log_place_order($this->code . '_notifyurl', $orderid . '----异步回调', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $orderInfo = $this->findOrder($orderid);
        if (!$orderInfo['order']) {
            $this->respond('FAIL', 404);
            return;
        }
        $order = $orderInfo['order'];
        $config = M('pay_for_another')->where(['code' => $this->code, 'id' => $order['df_id']])->find();
        if (!$config) {
            $this->respond('FAIL', 503);
            return;
        }
        if ($this->verifyNotifyIp($config['notifyip'], $orderid) !== true) {
            $this->writeNotifyLog($orderid, $result, 'IP异常:' . $this->getClientIp());
            $this->respond('FAIL', 403);
            return;
        }
        if (empty($result['sign']) || !hash_equals($this->sign($result, $config['signkey']), strtoupper((string)$result['sign']))) {
            $this->writeNotifyLog($orderid, $result, '签名错误');
            $this->respond('FAIL', 401);
            return;
        }

        $status = (string)(isset($result['status']) ? $result['status'] : '');
        if (in_array($status, ['2', '3'], true)) {
            $this->changeStatus($order['id'], 2, ['memo' => '代付成功'], $orderInfo['tableName']);
        } elseif (in_array($status, ['-1', '-2'], true)) {
            $this->changeStatus($order['id'], 3, ['memo' => '代付失败-' . $status], $orderInfo['tableName']);
        }
        $this->writeNotifyLog($orderid, $result, 'OK-' . $status);
        $this->respond('OK');
    }

    public function PaymentQuery($data, $config)
    {
        $orderid = isset($data['orderid']) ? (string)$data['orderid'] : '';
        $request = [
            'version' => '1.0.0',
            'mch_id' => (string)$config['mch_id'],
            'order_num' => $orderid,
        ];
        $request['sign'] = $this->sign($request, $config['signkey']);
        $base = isset($config['query_gateway']) && trim((string)$config['query_gateway']) !== ''
            ? $config['query_gateway']
            : (isset($config['exec_gateway']) ? $config['exec_gateway'] : '');
        $gateway = $this->resolveEndpoint($base, self::QUERY_PATH);
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code . '_PaymentQuery', $orderid . '----返回', $httpResult['body']);
        if ($httpResult['error'] !== '') {
            return ['status' => 7, 'msg' => '查询接口连接失败-' . $httpResult['error']];
        }
        $response = json_decode($httpResult['body'], true);
        if (!is_array($response) || (int)(isset($response['code']) ? $response['code'] : 0) !== 200) {
            return ['status' => 7, 'msg' => isset($response['msg']) ? $response['msg'] : '查询接口响应异常'];
        }
        $status = (string)(isset($response['data']['status']) ? $response['data']['status'] : '');
        if (in_array($status, ['0', '1'], true)) {
            return ['status' => 1, 'msg' => '处理中'];
        }
        if (in_array($status, ['2', '3'], true)) {
            return ['status' => 2, 'msg' => '成功'];
        }
        if (in_array($status, ['-1', '-2'], true)) {
            $message = isset($response['data']['status_name']) ? $response['data']['status_name'] : '失败';
            return ['status' => 3, 'msg' => '失败-' . $message, 'remark' => $message];
        }
        return ['status' => 7, 'msg' => '未知订单状态-' . $status];
    }

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
        $result = $this->requestBalance($config);
        if (!$result['success']) {
            $this->ajaxReturn(['status' => 0, 'msg' => $result['message']]);
            return;
        }
        $balance = htmlspecialchars((string)$result['balance'], ENT_QUOTES, 'UTF-8');
        $html = '<table class="hovertable" style="width:240px;border-collapse:collapse">'
            . '<tr><th style="padding:8px;border:1px solid #a9c6c9">说明</th><th style="padding:8px;border:1px solid #a9c6c9">值</th></tr>'
            . '<tr><td style="padding:8px;border:1px solid #a9c6c9">可用余额</td><td style="padding:8px;border:1px solid #a9c6c9"><b>' . $balance . ' IDR</b></td></tr>'
            . '</table>';
        $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
    }

    public function queryBalance2($config)
    {
        $result = $this->requestBalance($config);
        return $result['success']
            ? ['resultCode' => '0', 'balance' => $result['balance']]
            : ['resultCode' => '1', 'msg' => $result['message']];
    }

    private function requestBalance(array $config)
    {
        $request = [
            'version' => '1.0.0',
            'mch_id' => (string)$config['mch_id'],
            'timestamp' => (string)floor(microtime(true) * 1000),
        ];
        $request['sign'] = $this->sign($request, $config['signkey']);
        $base = isset($config['serverreturn']) && trim((string)$config['serverreturn']) !== ''
            ? $config['serverreturn']
            : (isset($config['exec_gateway']) ? $config['exec_gateway'] : '');
        $gateway = $this->resolveEndpoint($base, self::BALANCE_PATH);
        $httpResult = $this->postJson($gateway, $request);
        log_place_order($this->code . '_queryBalance', '返回', $httpResult['body']);
        if ($httpResult['error'] !== '') {
            return ['success' => false, 'message' => '余额查询连接失败-' . $httpResult['error'], 'balance' => 0];
        }
        $response = json_decode($httpResult['body'], true);
        if (!is_array($response) || (int)(isset($response['code']) ? $response['code'] : 0) !== 200 || !isset($response['data']['money'])) {
            return ['success' => false, 'message' => isset($response['msg']) ? $response['msg'] : '余额查询响应异常', 'balance' => 0];
        }
        return ['success' => true, 'message' => '成功', 'balance' => ((float)$response['data']['money']) / 100];
    }

    private function resolvePayoutChannel(array $data)
    {
        $type = strtolower(trim((string)(isset($data['type']) ? $data['type'] : 'Bank')));
        $types = [
            'qris' => ['601', 'QRIS'],
            'dana' => ['602', 'DANA'],
            'bank' => ['603', strtoupper(trim((string)$data['bankname']))],
            'va' => ['604', strtoupper(trim((string)$data['bankname']))],
            'ovo' => ['605', 'OVO'],
            'gopay' => ['606', 'GOPAY'],
            'linkaja' => ['607', 'LINKAJA'],
            'shopeepay' => ['608', 'SHOPEEPAY'],
        ];
        if (!isset($types[$type]) || $types[$type][1] === '') {
            return ['success' => false, 'message' => '支付类型或银行编码不支持'];
        }
        return ['success' => true, 'message' => '成功', 'typeId' => $types[$type][0], 'bankNumber' => $types[$type][1]];
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
        $dateText = substr((string)$orderid, 1, 8);
        if (!preg_match('/^\d{8}$/', $dateText)) {
            return ['order' => [], 'tableName' => ''];
        }
        try {
            $model = D('Wttklist');
            $tableName = $model->getRealTableName(date('Y-m-d', strtotime($dateText)));
            return ['order' => (array)$model->table($tableName)->where(['orderid' => $orderid])->find(), 'tableName' => $tableName];
        } catch (\Exception $exception) {
            return ['order' => [], 'tableName' => ''];
        }
    }

    private function resolveEndpoint($gateway, $path)
    {
        $gateway = trim((string)$gateway);
        if ($gateway === '') {
            return self::API_BASE . $path;
        }
        if (preg_match('#/api/(?:Daifu/(?:createOrder|queryOrder)|Member/blance)/?(?:\?.*)?$#i', $gateway)) {
            return preg_replace('#/api/(?:Daifu/(?:createOrder|queryOrder)|Member/blance)/?(?:\?.*)?$#i', $path, $gateway);
        }
        $configuredPath = parse_url($gateway, PHP_URL_PATH);
        return $configuredPath === '' || $configuredPath === '/' ? rtrim($gateway, '/') . $path : $gateway;
    }

    private function writeNotifyLog($orderid, array $data, $message)
    {
        try {
            logApiAddNotify($orderid, 1, $data, $message);
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
