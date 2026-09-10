<?php

namespace Payment\Controller;

class V8PayMayaDFController extends PaymentController
{
    private const API_BASE = 'https://cdn-api.phpshanghu.org/phi-v2';
    private const CREATE_PATH = '/withdrawal/create';
    private const QUERY_PATH = '/withdrawal/transactions/';
    private const BALANCE_PATH = '/org/balance';
    private const GCASH_BANK_CODE = 8355;
    private const MAYA_BANK_CODE = 8392;
    private const AES_KEY = 'EpPt34Kc67SyyDZspl2KmfKeMHw5tfh7';

    private $code = '';

    // 银行标识沿用 EWPayDFGcashController 的 bankname，值为 V8Pay V2 bankCode。
    private $bankCode = [
        'binangonan' => 8323,
        'aub' => 8305,
        'bdouni' => 8308,
        'bankofchina' => 8315,
        'landbank' => 8371,
        'lazada' => 8301,
        'metrobank' => 8380,
        'bpi' => 8325,
        'cimb' => 8336,
        'cisbayad' => 8337,
        'ctbc' => 8342,
        'dungganon' => 8347,
        'eastwest_rural' => 8349,
        'eastwest' => 8348,
        'gotyme' => 8356,
        'gcash' => 8355,
        'infoserve' => 8364,
        'isla' => 8367,
        'seabank' => 8309,
        'malayan' => 8375,
        'mcb' => 8382,
        'mayabank' => 8377,
        'omnipay' => 8387,
        'allbank' => 8302,
        'palawanpay' => 8390,
        'partnerrb' => 8391,
        'producers' => 8402,
        'rcbc' => 8407,
        'camalig' => 8326,
        'shopeepay' => 8415,
        'starpay' => 8418,
        'tayocash' => 8422,
        'tonik' => 8424,
        'uno' => 8431,
        'uniondigital' => 8428,
        'ussc' => 8432,
        'zybi' => 8436,
        'cardbank' => 8328,
        'sme' => 8330,
        'dbp' => 8345,
        'unionbank' => 8427,
        'standard_chartered' => 8417,
        'traxionpay' => 8425,
        'sunsavings' => 8421,
        'sterling' => 8419,
        'maybank' => 8378,
        'pbcom' => 8395,
        'pnb' => 8396,
        'security' => 8413,
        'equicom' => 8352,
        'psbank' => 8397,
        'robinsons' => 8409,
        'ucpb' => 8429,
        'paymaya' => 8392,
        'veterans' => 8433,
        'quezonbank' => 8404,
        'bangkomabuhay' => 8311,
        'chinabanksavings' => 8334,
        'bankofcommerce' => 8317,
        'chinabank' => 8335,
        'cebuanal' => 8332,
        'bdo' => 8322,
        'dumaguetebankx' => 8346,
        'legazpi' => 8373,
        'queenbank' => 8403,
        'netbank' => 8386,
        'guinobatan' => 8304,
        'bpibanko' => 8320,
        'grabpay' => 8357,
        'philtrust' => 8399,
        'paymongo' => 8393,
        'cantilan' => 8327,
        'marcopay' => 8376,
        'citysavings' => 8339,
        'easypay' => 8350,
        'ownb' => 8388,
        'pdax' => 8394,
        'speedypay' => 8416,
        'coins' => 8343,
    ];

    public function __construct()
    {
        $matches = [];
        preg_match('/([\da-zA-Z_]+)Controller$/', __CLASS__, $matches);
        $this->code = isset($matches[1]) ? $matches[1] : 'V8PayMayaDF';
    }

    // 菲律宾电子钱包/银行出款。mch_id=Withdrawal Transaction Token，signkey=Checksum Secret Key，appid=requestedBy。
    public function PaymentExec($data, $config)
    {
        $type = strtolower(trim((string)$data['type']));
        if ($type === 'gcash') {
            $bankCode = self::GCASH_BANK_CODE;
        } elseif ($type === 'maya') {
            $bankCode = self::MAYA_BANK_CODE;
        } elseif ($type === 'bank') {
            $bankName = strtolower(trim((string)$data['bankname']));
            if (!isset($this->bankCode[$bankName])) {
                return ['status' => 3, 'msg' => '该银行不支持！'];
            }
            $bankCode = $this->bankCode[$bankName];
        } else {
            return ['status' => 3, 'msg' => '支付类型错误，仅支持Gcash、Maya或Bank'];
        }

        $token = trim((string)$config['mch_id']);
        $secret = trim((string)$config['signkey']);
        $requestedBy = trim((string)$config['appid']) !== ''
            ? (string)$config['appid']
            : (isset($data['userid']) ? trim((string)$data['userid']) : '');
        $payload = [
            'bankCode' => $bankCode,
            'accountNumber' => trim((string)$data['banknumber']),
            'accountName' => trim((string)$data['bankfullname']),
            'referId' => (string)$data['orderid'],
            'quantity' => $this->numberValue($data['money']),
            'requestedBy' => $requestedBy,
            'callbackUrl' => $this->buildNotifyUrl(),
        ];
        $json = $this->encodeJson($payload);
        if ($json === false || $token === '' || $secret === '' || $requestedBy === '') {
            return ['status' => 3, 'msg' => '出款Token、安全码、requestedBy或请求参数未配置'];
        }

        $url = $this->resolveEndpoint(isset($config['exec_gateway']) ? $config['exec_gateway'] : '', self::CREATE_PATH);
        $checksum = $this->checksum($json, $secret);
        log_place_order($this->code, $data['orderid'] . '----提交', $json);
        log_place_order($this->code, $data['orderid'] . '----提交地址', $url);
        $beginTime = microtime(true);
        $httpResult = $this->postJson($url, $json, $token, $checksum);
        log_place_order($this->code, $data['orderid'] . '----返回', $httpResult['body']);
        $response = json_decode($httpResult['body'], true);

        try {
            $duration = floor((microtime(true) - $beginTime) * 1000);
            logApiAddPayment(
                '订单提交上游' . $this->code,
                __METHOD__,
                $data['orderid'],
                $data['out_trade_no'],
                $url,
                $payload,
                is_array($response) ? $response : $httpResult,
                $duration,
                '0',
                '1',
                '2'
            );
        } catch (\Exception $exception) {
            log_place_order($this->code, $data['orderid'] . '----记录日志异常', $exception->getMessage());
        }

        if ($httpResult['error'] !== '') {
            return ['status' => 0, 'msg' => '连接失败，订单结果未知，请勿更换订单号重试'];
        }
        if (!is_array($response)) {
            return ['status' => 0, 'msg' => '响应格式异常，订单结果未知'];
        }
        if (empty($response['success'])) {
            $code = isset($response['code']) ? (int)$response['code'] : 0;
            return [
                'status' => in_array($code, [401001, 422001, 422002, 422003, 422004, 422005, 422006, 422007, 422008, 422100, 422101, 422102, 422103, 422104, 422105, 422106], true) ? 3 : 0,
                'msg' => isset($response['message']) ? (string)$response['message'] : '出款创建失败',
            ];
        }

        $state = strtolower((string)(isset($response['data']['state']) ? $response['data']['state'] : ''));
        if (in_array($state, ['queue', 'doing', 'done'], true)) {
            return ['status' => 1, 'msg' => '申请正常-' . $state];
        }
        if (in_array($state, ['delete', 'rejected'], true)) {
            return ['status' => 3, 'msg' => '申请失败-' . $state];
        }
        return ['status' => 0, 'msg' => '返回未知订单状态-' . $state];
    }

    public function notifyurl()
    {
        $rawBody = file_get_contents('php://input');
        $notification = json_decode($rawBody, true);
        $payload = is_array($notification) && isset($notification['payload']) && is_array($notification['payload'])
            ? $notification['payload']
            : [];
        $orderid = !empty($payload['referId']) ? (string)$payload['referId'] : (isset($payload['searchId']) ? (string)$payload['searchId'] : '');
        log_place_order($this->code . '_notifyurl', $orderid . '----异步回调', $rawBody);

        $orderInfo = $this->findOrder($orderid);
        if (!$orderInfo['order']) {
            $this->respond('Order FAIL', 404);
            return;
        }
        $order = $orderInfo['order'];
        $config = M('pay_for_another')->where(['id' => $order['df_id'], 'code' => $this->code])->find();
        if (!$config) {
            $this->respond('Config FAIL', 503);
            return;
        }
        $ip = $this->getNotifyIp();
        if (checkNotifyurlIp($ip, $config['notifyip']) !== true) {
            $this->writeNotifyLog($orderid, $notification, 'IP异常:' . $ip);
            $this->respond('IP FAIL', 403);
            return;
        }

        if (!$this->verifyWebhook($notification, self::AES_KEY)) {
            $this->writeNotifyLog($orderid, $notification, 'AES签名错误');
            $this->respond('Sign FAIL', 401);
            return;
        }
        if (!$this->amountsEqual(isset($payload['quantity']) ? $payload['quantity'] : null, isset($order['money']) ? $order['money'] : $order['tkmoney'])) {
            $this->writeNotifyLog($orderid, $notification, '金额不一致');
            $this->respond('Amount FAIL', 400);
            return;
        }

        $state = strtolower((string)(isset($payload['state']) ? $payload['state'] : ''));
        if (!in_array($state, ['done', 'delete', 'rejected'], true)) {
            $this->writeNotifyLog($orderid, $notification, '未知回调状态-' . $state);
            $this->respond('State FAIL', 400);
            return;
        }
        if ($state === 'done') {
            $this->changeStatus($order['id'], 2, ['memo' => '代付成功'], $orderInfo['tableName']);
        } elseif (in_array($state, ['delete', 'rejected'], true)) {
            $reason = !empty($payload['deleteReason']) ? (string)$payload['deleteReason'] : $state;
            $this->changeStatus($order['id'], 3, ['memo' => '代付失败-' . $reason], $orderInfo['tableName']);
        }
        $this->writeNotifyLog($orderid, $notification, 'SUCCESS-' . $state);
        $this->respond('SUCCESS');
    }

    public function PaymentQuery($data, $config)
    {
        $orderid = isset($data['orderid']) ? (string)$data['orderid'] : '';
        $base = isset($config['query_gateway']) && trim((string)$config['query_gateway']) !== ''
            ? $config['query_gateway']
            : (isset($config['exec_gateway']) ? $config['exec_gateway'] : '');
        $url = $this->resolveEndpoint($base, self::QUERY_PATH . rawurlencode($orderid));
        $httpResult = $this->getJson($url, 'TransactionToken', (string)$config['mch_id']);
        log_place_order($this->code . '_PaymentQuery', $orderid . '----返回', $httpResult['body']);
        if ($httpResult['error'] !== '') {
            return ['status' => 7, 'msg' => '查询接口连接失败-' . $httpResult['error']];
        }
        $response = json_decode($httpResult['body'], true);
        if (!is_array($response) || empty($response['success'])) {
            return ['status' => 7, 'msg' => isset($response['message']) ? $response['message'] : '查询接口响应异常'];
        }
        $state = strtolower((string)(isset($response['data']['state']) ? $response['data']['state'] : ''));
        if (in_array($state, ['queue', 'doing'], true)) {
            return ['status' => 1, 'msg' => '处理中-' . $state];
        }
        if ($state === 'done') {
            return ['status' => 2, 'msg' => '成功'];
        }
        if (in_array($state, ['delete', 'rejected'], true)) {
            $reason = !empty($response['data']['deleteReason']) ? $response['data']['deleteReason'] : $state;
            return ['status' => 3, 'msg' => '失败-' . $reason, 'remark' => $reason];
        }
        return ['status' => 7, 'msg' => '未知订单状态-' . $state];
    }

    // public_key 配置 V8Pay OrgToken。
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
        $balance = htmlspecialchars((string)$balanceResult['balance'], ENT_QUOTES, 'UTF-8');
        $html = '<table class="hovertable" style="width:220px;border-collapse:collapse">'
            . '<tr><th style="padding:8px;border:1px solid #a9c6c9">说明</th><th style="padding:8px;border:1px solid #a9c6c9">值</th></tr>'
            . '<tr><td style="padding:8px;border:1px solid #a9c6c9">商户余额</td><td style="padding:8px;border:1px solid #a9c6c9"><b>' . $balance . '</b></td></tr>'
            . '</table>';
        $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);
    }

    public function queryBalance2($config)
    {
        $balanceResult = $this->requestBalance($config);
        return $balanceResult['success']
            ? ['resultCode' => '0', 'balance' => $balanceResult['balance']]
            : ['resultCode' => '1', 'msg' => $balanceResult['message']];
    }

    private function requestBalance(array $config)
    {
        $orgToken = trim((string)$config['public_key']);
        if ($orgToken === '') {
            return ['success' => false, 'message' => 'OrgToken未配置，请填写public_key', 'balance' => 0];
        }
        $base = isset($config['serverreturn']) && trim((string)$config['serverreturn']) !== ''
            ? $config['serverreturn']
            : (isset($config['exec_gateway']) ? $config['exec_gateway'] : '');
        $url = $this->resolveEndpoint($base, self::BALANCE_PATH);
        $httpResult = $this->getJson($url, 'OrgToken', $orgToken);
        log_place_order($this->code . '_queryBalance', '返回', $httpResult['body']);
        if ($httpResult['error'] !== '') {
            return ['success' => false, 'message' => '余额查询连接失败-' . $httpResult['error'], 'balance' => 0];
        }
        $response = json_decode($httpResult['body'], true);
        if (!is_array($response) || empty($response['success']) || !isset($response['data']['balance'])) {
            return ['success' => false, 'message' => isset($response['message']) ? $response['message'] : '余额查询响应异常', 'balance' => 0];
        }
        return ['success' => true, 'message' => '成功', 'balance' => $response['data']['balance']];
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

        $bin = hex2bin($notification['checksum']);
        $b64 = base64_encode($bin);
        $decryptedData = self::decryptRJ256($aesKey, $notification['algorithm']['iv'], $b64);
        return $decryptedData;
    }
    private function decryptRJ256($key, $iv, $string_to_decrypt)
    {
        $rtn = openssl_decrypt($string_to_decrypt, "aes-256-cbc", $key, 0, $iv);
        $rtn = rtrim($rtn, "\0\4");
        return ($rtn);
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
        $dateText = substr((string)$orderid, 1, 8);
        if (!preg_match('/^\d{8}$/', $dateText)) {
            return ['order' => [], 'tableName' => ''];
        }
        try {
            $model = D('Wttklist');
            $tableName = $model->getRealTableName(date('Y-m-d', strtotime($dateText)));
            $order = $model->table($tableName)->where(['orderid' => $orderid])->find();
            return ['order' => (array)$order, 'tableName' => $tableName];
        } catch (\Exception $exception) {
            return ['order' => [], 'tableName' => ''];
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

    private function buildNotifyUrl()
    {
        $domain = trim((string)C('NOTIFY_DOMAIN'));
        $baseUrl = preg_match('#^https?://#i', $domain) ? $domain : 'https://' . $domain;
        return rtrim($baseUrl, '/') . '/Payment_' . $this->code . '_notifyurl.html';
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
