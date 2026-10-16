<?php
namespace Payment\Controller;


class InrWakeDMPayDFController extends PaymentController
{
    private $code = '';

    public function __construct()
    {
        parent::__construct();
        $matches = [];
        preg_match('/([\da-zA-Z\_]+)Controller$/', __CLASS__, $matches);
        $this->code = $matches[1];
    }

    public function PaymentExec($data, $config)
    {
        if (!isset($data['type']) || !in_array($data['type'], ['Banktransfer', 'IMPS', 'NEFT', 'RTGS', 'UPI'], true)) {
            return ['status' => 0, 'msg' => '不支持该支付类型'];
        }
        $ext = isset($data['extends']) ? $data['extends'] : [];
        if (!is_array($ext)) {
            $decoded = json_decode($ext, true);
            $ext = is_array($decoded) ? $decoded : json_decode(base64_decode($ext, true), true);
        }
        $phone = isset($data['phone']) ? $data['phone'] : (isset($ext['phone']) ? $ext['phone'] : '');
        $phone = is_scalar($phone) ? trim((string)$phone) : '';
        if (preg_match('/^[0-9]{10}$/D', $phone)) {
            $phone = '+91' . $phone;
        } elseif (preg_match('/^91[0-9]{10}$/D', $phone)) {
            $phone = '+' . $phone;
        }
        if (!preg_match('/^\+91[0-9]{10}$/D', $phone)) {
            return ['status' => 0, 'msg' => '联系电话须为10位印度手机号或+91国际格式，例如+919203958784'];
        }
        $upi = $data['type'] === 'UPI';
        if (empty($data['banknumber']) || empty($data['bankfullname']) || !is_scalar($phone) || trim((string)$phone) === ''
            || (!$upi && empty($data['bankzhiname'])) || !isset($data['money']) || !is_numeric($data['money']) || $data['money'] <= 0) {
            return ['status' => 0, 'msg' => '缺少有效金额、收款账户、姓名、IFSC或extends.contactPhone'];
        }
        $request = [
            'merchantOrderNo' => $data['orderid'],
            'merchantNo' => $config['mch_id'],
            'orderAmount' => sprintf('%.2f', $data['money']),
            'bankAccount' => $data['banknumber'],
            'disbursementType' => $upi ? 'upi' : 'bank',
            'payeeName' => $data['bankfullname'],
            'contactPhone' => $phone,
            'notifyUrl' => 'https://' . C('NOTIFY_DOMAIN') . '/Payment_' . $this->code . '_notifyurl.html',
        ];
        if (!$upi) $request['bankCode'] = $data['bankzhiname'];
        $url = self::dmEndpoint($config['exec_gateway'], 'paymentOrder');
        if ($url === '' || empty($config['mch_id']) || empty($config['signkey'])) {
            return ['status' => 0, 'msg' => '代付配置不完整'];
        }
        $http = $this->request($url, $request, $config, $data['orderid']);
        $result = json_decode($http['body'], true);
        // Ambiguous submission must stay in processing to prevent duplicate payouts.
        if ($http['error'] !== '' || $http['http'] !== 200 || !is_array($result)) {
            return ['status' => 1, 'msg' => '下单结果未知，请查单或联系上游确认，禁止改派'];
        }
        if (isset($result['code']) && (string)$result['code'] === '200' && isset($result['success']) && $result['success'] === true
            && !empty($result['result']['orderNo'])) {
            return ['status' => 1, 'msg' => '申请成功'];
        }
        if (isset($result['success'], $result['code']) && $result['success'] === false && (string)$result['code'] !== '200') {
            return ['status' => 0, 'msg' => isset($result['message']) && is_string($result['message']) ? $result['message'] : '明确拒绝下单'];
        }
        return ['status' => 1, 'msg' => '下单结果未知，请查单确认，禁止改派'];
    }

    public function PaymentQuery($data, $config)
    {
        $http = $this->request(self::dmEndpoint($config['query_gateway'], 'queryDfOrder'),
            ['orderNo' => $data['orderid'], 'merchNo' => $config['mch_id']], $config, $data['orderid']);
        $response = json_decode($http['body'], true);
        $result = isset($response['result']) && is_array($response['result']) ? $response['result'] : [];
        if ($http['error'] !== '' || $http['http'] !== 200 || !isset($response['code']) || (string)$response['code'] !== '200'
            || !$this->validOrderData($result, $data, $config, 'orderNo')) {
            return ['status' => 7, 'msg' => '查单失败或验签/订单校验失败，保留原状态'];
        }
        return $this->mapState($result);
    }

    public function notifyurl()
    {
        $raw = file_get_contents('php://input');
        $response = json_decode($raw, true);
        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
        $orderid = isset($data['merchOrderNo']) && is_string($data['merchOrderNo']) ? $data['merchOrderNo'] : '';
        log_place_order($this->code . '_notifyurl', $orderid, $raw);
        $reply = 'fail';
        $notifyMessage = '';
        if (preg_match('/^[a-zA-Z]\d{8}[a-zA-Z0-9_-]*$/D', $orderid)) {
            $model = D('Wttklist');
            $table = $model->getRealTableName(date('Ymd', strtotime(substr($orderid, 1, 8))));
            $order = $model->table($table)->where(['orderid' => $orderid])->find();
            $config = $order ? M('pay_for_another')->where(['code' => $this->code, 'id' => $order['df_id']])->find() : [];
            $ipAllowed = false;
            if ($config) {
                $ipAllowed = $this->verifyNotifyIp($config['notifyip'], $orderid) === true;
                if (!$ipAllowed) {
                    $notifyMessage = 'IP异常:' . $this->getClientIp();
                    log_place_order($this->code . '_notifyurl', $orderid . '----IP异常', $notifyMessage);
                }
            }
            if ($config && isset($response['code']) && (string)$response['code'] === '0'
                && $ipAllowed
                && $this->validOrderData($data, $order, $config, 'merchOrderNo')) {
                $mapped = $this->mapState($data);
                if ($mapped['status'] === 1) {
                    $reply = 'success';
                } elseif (in_array($mapped['status'], [2, 3], true)) {
                    // Do not overwrite an already settled order with a conflicting terminal state.
                    $expected = $mapped['status'] === 2 ? '2' : '4';
                    if (in_array((string)$order['status'], ['0', '1'], true)) {
                        $this->changeStatus($order['id'], $mapped['status'], ['memo' => $mapped['msg']], $table);
                        $order = $model->table($table)->where(['orderid' => $orderid])->find();
                    }
                    if ($order && (string)$order['status'] === $expected) $reply = 'success';
                    else log_place_order($this->code . '_notifyurl', $orderid . '----终态冲突或更新失败需核对', $raw);
                }
            }
        }
        echo $reply;
        try { logApiAddNotify($orderid, 1, $response, $notifyMessage !== '' ? $notifyMessage : $reply); } catch (\Exception $e) {}
    }

    private function validOrderData(array $result, array $order, array $config, $orderField)
    {
        return self::dmVerify($result, $config['signkey'])
            && isset($result[$orderField], $result['merchNo'], $result['amount'], $result['orderState'])
            && (string)$result[$orderField] === (string)$order['orderid']
            && (string)$result['merchNo'] === (string)$config['mch_id']
            && self::dmAmountEquals($result['amount'], $order['money']);
    }

    private function mapState(array $result)
    {
        switch ((string)$result['orderState']) {
            case '0': return ['status' => 2, 'msg' => '代付成功'];
            case '1': return ['status' => 3, 'msg' => '代付失败：' . (isset($result['msg']) && is_string($result['msg']) ? $result['msg'] : '')];
            case '2': return ['status' => 1, 'msg' => '代付处理中'];
            // Reversals after success need reconciliation; the base controller cannot refund a settled payout.
            case '3': return ['status' => 7, 'msg' => '代付冲正，请人工核对并处理账务'];
            default: return ['status' => 7, 'msg' => '未知状态，保留原状态'];
        }
    }

    public function queryBalance2($config)
    {
        $gateway = !empty($config['serverreturn']) ? $config['serverreturn'] : $config['exec_gateway'];
        $http = $this->request(self::dmEndpoint($gateway, 'balanceQuery'), ['merchNo' => $config['mch_id']], $config, 'balance');
        $result = json_decode($http['body'], true);
        if ($http['error'] === '' && $http['http'] === 200 && isset($result['code'], $result['result'])
            && (string)$result['code'] === '200' && is_numeric($result['result'])) {
            return ['resultCode' => '0', 'balance' => $result['result']];
        }
        return ['resultCode' => '1', 'message' => '余额查询失败'];
    }

    public function queryBalance()
    {
        if (!IS_AJAX) return;
        $config = M('pay_for_another')->where(['code' => $this->code])->find();
        if (!$config) {
            $this->ajaxReturn(['status' => 0, 'msg' => '通道未配置']);
            return;
        }
        $result = $this->queryBalance2($config);
        $this->ajaxReturn(['status' => $result['resultCode'] === '0' ? 1 : 0,
            'msg' => $result['resultCode'] === '0' ? '成功' : '余额查询失败',
            'data' => $result['resultCode'] === '0' ? '可用金额：' . htmlspecialchars((string)$result['balance'], ENT_QUOTES, 'UTF-8') : '']);
    }

    protected function request($url, array $request, array $config, $orderid)
    {
        $request['sign'] = self::dmSign($request, $config['signkey']);
        $begin = microtime(true);
        log_place_order($this->code, $orderid . '----提交地址', $url);
        log_place_order($this->code, $orderid . '----提交', json_encode($request, JSON_UNESCAPED_UNICODE));
        $http = self::dmPost($url, $request);
        log_place_order($this->code, $orderid . '----返回', json_encode($http, JSON_UNESCAPED_UNICODE));
        try {
            logApiAddPayment('接口请求', __METHOD__, $orderid, '', $url, $request,
                json_decode($http['body'], true), floor((microtime(true) - $begin) * 1000), '0', '1', '1');
        } catch (\Exception $e) {}
        return $http;
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
