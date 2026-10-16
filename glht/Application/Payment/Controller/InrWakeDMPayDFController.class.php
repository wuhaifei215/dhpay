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

    //代付提交
    public function PaymentExec($data, $config)
    {
        if (!isset($data['type']) || !in_array($data['type'], ['Banktransfer', 'IMPS', 'NEFT', 'RTGS', 'UPI'], true)) {
            return ['status' => 0, 'msg' => '不支持该支付类型'];
        }
        // Generate +91 followed by a ten-digit mobile number.
        $phone = '+91' . random_int(6, 9);
        for ($i = 0; $i < 9; $i++) {
            $phone .= random_int(0, 9);
        }
        $upi = $data['type'] === 'UPI';
        if (empty($data['banknumber']) || empty($data['bankfullname'])
            || (!$upi && empty($data['bankzhiname'])) || !isset($data['money']) || !is_numeric($data['money']) || $data['money'] <= 0) {
            return ['status' => 0, 'msg' => '缺少有效金额、收款账户、姓名或IFSC'];
        }
        $post_data = [
            'merchantOrderNo' => $data['orderid'],
            'merchantNo' => $config['mch_id'],
            'orderAmount' => sprintf('%.2f', $data['money']),
            'bankAccount' => $data['banknumber'],
            'payeeName' => $data['bankfullname'],
            'contactPhone' => $phone,
            'notifyUrl' => 'https://' . C('NOTIFY_DOMAIN') . '/Payment_' . $this->code . '_notifyurl.html',
        ];
        if (!$upi) $post_data['bankCode'] = $data['bankzhiname'];
        $url = trim((string)$config['exec_gateway']);
        if ($url === '' || empty($config['mch_id']) || empty($config['signkey'])) {
            return ['status' => 0, 'msg' => '代付配置不完整'];
        }
        $post_data['sign'] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code, $data['orderid'] . '----提交', json_encode($post_data, JSON_UNESCAPED_UNICODE));
        log_place_order($this->code, $data['orderid'] . '----提交地址', $url);
        $beginTime = microtime(true);
        $http = $this->http_post_json($url, $post_data);
        $result = json_decode($http['body'], true);
        log_place_order($this->code, $data['orderid'] . '----返回', json_encode($http, JSON_UNESCAPED_UNICODE));
        try {
            $doTime = floor((microtime(true) - $beginTime) * 1000);
            logApiAddPayment('订单提交三方', __METHOD__, $data['orderid'], isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
                $url, $post_data, $result, $doTime, '0', '1', '2');
        } catch (\Exception $e) {
            log_place_order($this->code, $data['orderid'] . '----提交日志写入异常', $e->getMessage());
        }
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
            if (!$order) {
                $notifyMessage = '本地代付订单不存在：' . $orderid;
            } elseif (!$config) {
                $notifyMessage = '代付配置不存在或通道不匹配：df_id=' . $order['df_id'] . '，通道=' . $this->code;
            } elseif ($this->verifyNotifyIp($config['notifyip'], $orderid) !== true) {
                $notifyMessage = 'IP异常:' . $this->getClientIp();
            } elseif (!isset($response['code']) || (string)$response['code'] !== '0') {
                $notifyMessage = '回调code异常：' . json_encode(isset($response['code']) ? $response['code'] : null);
            } elseif (!$this->verifySign($data, $config['signkey'])) {
                $notifyMessage = '签名校验失败：请核对该代付通道的签名密钥';
            } elseif (!isset($data['merchNo']) || (string)$data['merchNo'] !== (string)$config['mch_id']) {
                $notifyMessage = '商户号不匹配：回调=' . json_encode(isset($data['merchNo']) ? $data['merchNo'] : null)
                    . '，配置=' . $config['mch_id'];
            } elseif (!isset($data['orderState'])) {
                $notifyMessage = '回调缺少orderState';
            } else {
                $mapped = $this->mapState($data);
                if ($mapped['status'] === 1) {
                    $reply = 'success';
                } elseif (in_array($mapped['status'], [2, 3], true)) {
                    // Do not overwrite an already settled order with a conflicting terminal state.
                    $expected = $mapped['status'] === 2 ? '2' : '4';
                    if (in_array((string)$order['status'], ['0', '1'], true)) {
                        $this->changeStatus($order['id'], $mapped['status'], ['memo' => $mapped['msg'] . (isset($mapped['remark']) ? '-' . $mapped['remark'] : '')], $table);
                        $order = $model->table($table)->where(['orderid' => $orderid])->find();
                    }
                    if ($order && (string)$order['status'] === $expected) $reply = 'success';
                    else $notifyMessage = '终态冲突或更新失败：期望本地状态=' . $expected
                        . '，实际状态=' . ($order ? $order['status'] : '订单不存在');
                } else {
                    $notifyMessage = $mapped['msg'];
                }
            }
        } else {
            $notifyMessage = '商户订单号缺失或格式错误：' . $orderid;
        }
        if ($notifyMessage !== '') {
            log_place_order($this->code . '_notifyurl', $orderid . '----回调失败原因', $notifyMessage);
        }
        echo $reply;
        try { logApiAddNotify($orderid, 1, $response, $notifyMessage !== '' ? $notifyMessage : $reply); } catch (\Exception $e) {}
    }

    private function validOrderData(array $result, array $order, array $config, $orderField)
    {
        return $this->verifySign($result, $config['signkey'])
            && isset($result[$orderField], $result['merchNo'], $result['amount'], $result['orderState'])
            && (string)$result[$orderField] === (string)$order['orderid']
            && (string)$result['merchNo'] === (string)$config['mch_id']
            && self::dmAmountEquals($result['amount'], $order['money']);
    }

    private function mapState(array $result)
    {
        switch ((string)$result['orderState']) {
            case '0': return ['status' => 2, 'msg' => '代付成功'];
            case '1': return ['status' => 3, 'msg' => '代付失败', 'remark' => isset($result['msg']) && is_string($result['msg']) ? $result['msg'] : ''];
            case '2': return ['status' => 1, 'msg' => '代付处理中'];
            // Reversals after success need reconciliation; the base controller cannot refund a settled payout.
            case '3': return ['status' => 7, 'msg' => '代付冲正，请人工核对并处理账务'];
            default: return ['status' => 7, 'msg' => '未知状态，保留原状态'];
        }
    }

    public function queryBalance2($config)
    {
        $gateway = !empty($config['serverreturn']) ? $config['serverreturn'] : $config['exec_gateway'];
        $post_data = ['merchNo' => $config['mch_id']];
        $post_data['sign'] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_queryBalance', '提交', json_encode($post_data, JSON_UNESCAPED_UNICODE));
        $http = $this->http_post_json($gateway, $post_data);
        log_place_order($this->code . '_queryBalance', '返回', json_encode($http, JSON_UNESCAPED_UNICODE));
        $result = json_decode($http['body'], true);
        if ($http['error'] === '' && $http['http'] === 200 && isset($result['code'], $result['result'])
            && (string)$result['code'] === '200' && is_numeric($result['result'])) {
            return ['resultCode' => '0', 'balance' => $result['result']];
        }
        return ['resultCode' => '1', 'message' => '余额查询失败'];
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
        $result = $this->queryBalance2($config);
        if ($result['resultCode'] !== '0') {
            $this->ajaxReturn(['status' => 0, 'msg' => isset($result['message']) ? $result['message'] : '余额查询失败']);
            return;
        }
        $availableBalance = htmlspecialchars((string)$result['balance'], ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<style type="text/css">
table.hovertable {width:300px;font-family:verdana,arial,sans-serif;font-size:11px;color:#333333;border-width:1px;border-color:#999999;border-collapse:collapse;}
table.hovertable th {background-color:#c3dde0;border-width:1px;padding:8px;border-style:solid;border-color:#a9c6c9;}
table.hovertable tr {background-color:#f5f5f5;}
table.hovertable td {border-width:1px;padding:8px;border-style:solid;border-color:#a9c6c9;}
</style>
<table class="hovertable">
<tr><th>说明</th><th>值</th></tr>
<tr onmouseout="this.style.backgroundColor='#f5f5f5';" onmouseover="this.style.backgroundColor='#009688';"><td>可用金额</td><td><b>{$availableBalance} INR</b></td></tr>
</table>
HTML;
        $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
    }

    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $post_data = ['orderNo' => $data['orderid'], 'merchNo' => $config['mch_id']];
        $post_data['sign'] = $this->get_sign($post_data, $config['signkey']);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . '----提交', json_encode($post_data, JSON_UNESCAPED_UNICODE));
        $http = $this->http_post_json($config['query_gateway'], $post_data);
        log_place_order($this->code . '_PaymentQuery', $data['orderid'] . '----返回', json_encode($http, JSON_UNESCAPED_UNICODE));
        $response = json_decode($http['body'], true);
        $result = isset($response['result']) && is_array($response['result']) ? $response['result'] : [];
        if ($http['error'] !== '' || $http['http'] !== 200 || !isset($response['code']) || (string)$response['code'] !== '200'
            || !$this->validOrderData($result, $data, $config, 'orderNo')) {
            return ['status' => 7, 'msg' => '查单失败或验签/订单校验失败，保留原状态'];
        }
        return $this->mapState($result);
    }

    private function get_sign(array $data, $key)
    {
        unset($data['sign']);
        ksort($data, SORT_STRING);
        $parts = [];
        foreach ($data as $name => $value) {
            if ($value !== '' && $value !== null && is_scalar($value)) {
                $parts[] = $name . '=' . $value;
            }
        }
        log_place_order($this->code, "----签名", implode('&', $parts) . $key);    //日志
        return strtoupper(md5(implode('&', $parts) . $key));
    }

    private function verifySign(array $data, $key)
    {
        return isset($data['sign']) && is_string($data['sign'])
            && preg_match('/^[a-fA-F0-9]{32}$/D', $data['sign'])
            && hash_equals($this->get_sign($data, $key), strtoupper($data['sign']));
    }

    private static function dmAmountEquals($left, $right)
    {
        return is_scalar($left) && is_scalar($right)
            && preg_match('/^\d+(\.\d{1,2})?$/D', (string)$left)
            && preg_match('/^\d+(\.\d{1,2})?$/D', (string)$right)
            && bccomp((string)$left, (string)$right, 2) === 0;
    }

    //发送POST JSON请求，保留HTTP状态用于识别结果未知的订单
    protected function http_post_json($url, array $data)
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
