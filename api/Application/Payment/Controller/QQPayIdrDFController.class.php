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
        $bankNumber = strtoupper(trim((string)(isset($data['bankname']) ? $data['bankname'] : '')));
        if ($type === 'bank') {
            // 对外银行编号 => QQpay bank_number，按双方列表的银行名称对应。
            // 上游部分编码与名称不一致（如 BUMI 对应 JATENG），不可按编码字面匹配。
            $bankCodeMap = [
                '9164' => 'BOA', // Bank Of America
                '9163' => 'MEGA', // Credit Agricole Indosuez
                '9162' => 'JATIM', // Bangkok
                '9161' => 'SUMUT', // Sumitomo Mitsui
                '9160' => 'BII', // Resona Perdania
                '9159' => 'SINARMAS', // Mizuho
                '9158' => 'MUAMALAT', // ABN AMRO
                '9157' => 'BUKOPIN', // Keppel Tatlee Buana
                '9156' => 'MESTIKA', // Deutsche Bank
                '9155' => 'INDEX', // Antardaerah
                '9154' => 'VICTORIA2', // Haga
                '9153' => 'BANK072', // Nusantara Parahyangan
                '9152' => 'BANK073', // Swadesi
                '9151' => 'ICBC', // Metro Express
                '9150' => 'ABC', // Hagakita
                '9149' => 'BANK081', // Harmoni International
                '9148' => 'BANK089', // Bisnis Internasional
                '9147' => 'BANK092', // Yudha Bhakti
                '9146' => 'BANK153', // Raya / BRI Agroniaga
                '9145' => 'BANK097', // Syariah Mega
                '9144' => 'BANK099', // Prima Master
                '9143' => 'BANK100', // Persyarikatan
                '9142' => 'BANK101', // Akita
                '9141' => 'BANK106', // Purba Danarta
                '9140' => 'BANK107', // Multi Arta Sentosa
                '9139' => 'BANK111', // Eksekutif
                '9138' => 'BANK113', // Fama Internasional
                '9137' => 'BANK114', // Sinar Harapan Bali
                '9136' => 'BANK116', // Finconesia
                '9135' => 'BANK117', // Merincorp
                '9134' => 'BANK120', // China Trust Indonesia
                '9133' => 'BANK134', // Ekspor Indonesia
                '9132' => 'BANK179', // Neo Commerce
                '9131' => 'BANK154', // SBI Indonesia
                '9129' => 'BANK125', // Andara
                '9128' => 'BANK215', // Anglomas / Amar
                '9127' => 'JATIM', // Bangkok
                '9126' => 'BANK144', // BPD Banten
                '9125' => 'BANK097', // Mega Syariah
                '9123' => 'BANK181', // China Construction Bank
                '9122' => 'BANK178', // Aladin Syariah
                '9121' => 'BANK215', // Amar Indonesia
                '9120' => 'BANK151', // Syariah Indonesia
                '9119' => 'SHOPEEPAY',
                '9118' => 'LINKAJA',
                '9117' => 'GOPAY',
                '9116' => 'OVO',
                '9115' => 'DANA',
                '9111' => 'BANK121', // Commonwealth
                '9110' => 'BANK165', // CTBC
                '9109' => 'BANK164', // Agris / IBK
                '9101' => 'BANK163', // BPR KS
                '9099' => 'BANK115', // Harda
                '9098' => 'BANK110', // Victoria
                '9097' => 'BANK112', // Centratama Nasional
                '9096' => 'BANK111', // Pundi / Eksekutif
                '9095' => 'BANK109', // Index Selindo
                '9094' => 'BANK108', // Mayora
                '9093' => 'BANK107', // Multi Arta Sentosa
                '9092' => 'BANK159', // BTPN Syariah
                '9091' => 'BANK105', // Artos
                '9090' => 'BANK123', // BCA Syariah
                '9089' => 'BANK158', // SeaBank
                '9088' => 'BANK130', // Dinar
                '9086' => 'BANK157', // Sahabat Sampoerna
                '9085' => 'BANK156', // Syariah Bukopin
                '9084' => 'BANK155', // Panin Syariah
                '9083' => 'BANK098', // Ina Perdana
                '9082' => 'BANK097', // Bank Syariah Mega Indonesia
                '9081' => 'BANK103', // Nobu
                '9080' => 'BANK096', // Royal Indonesia
                '9079' => 'BANK095', // Indomonex
                '9078' => 'BANK094', // Agro Niaga
                '9077' => 'BANK092', // Yudha Bhakti
                '9076' => 'BANK152', // MNC
                '9075' => 'BANK131', // Hana
                '9074' => 'BANK090', // Jasa Jakarta
                '9073' => 'BSM', // Syariah Mandiri
                '9072' => 'BANK088', // Bukopin
                '9071' => 'BANK087', // Mega
                '9070' => 'BANK122', // BJB Syariah
                '9069' => 'BRIS', // BRI Syariah
                '9068' => 'BANK150', // Victoria Syariah
                '9066' => 'BANK149', // BTPN
                '9065' => 'BANK084', // Himpunan Saudara / Woori Saudara
                '9064' => 'BANK148', // BTN
                '9063' => 'BANK147', // QNB
                '9062' => 'BANK132', // ICBC
                '9061' => 'BOCOM', // Ganesha
                '9060' => 'CCB', // Maspion
                '9059' => 'BANK128', // Sinarmas
                '9058' => 'BOC', // Mestika
                '9057' => 'BANK074', // Muamalat
                '9056' => 'BANK145', // Bank of India
                '9055' => 'BRISS', // BNP Paribas
                '9054' => 'BANK071', // Sultra
                '9053' => 'BANK070', // Sulteng
                '9052' => 'BANK069', // Bengkulu
                '9051' => 'BANK068', // Papua
                '9050' => 'BANK067', // Maluku
                '9049' => 'BANK066', // NTT
                '9048' => 'BANK065', // Bali
                '9047' => 'BANK064', // NTB
                '9046' => 'BANK063', // Sulut
                '9045' => 'BANK141', // Sulselbar
                '9044' => 'BANK061', // Kalteng
                '9043' => 'BANK060', // Kaltim
                '9042' => 'BANK059', // Kalbar
                '9041' => 'BANK058', // Kalsel
                '9040' => 'BANK057', // Lampung
                '9039' => 'BANK056', // Sumsel
                '9038' => 'BANK055', // Riau
                '9037' => 'BANK054', // Nagari
                '9036' => 'BANK053', // Sumut
                '9035' => 'BANK052', // Aceh
                '9034' => 'BANK051', // Jambi
                '9033' => 'CAPITAL', // Jatim
                '9032' => 'BUMI', // Jateng
                '9031' => 'ARTHA', // BPD DIY
                '9030' => 'ANTAR', // DKI
                '9029' => 'BANK129', // Jabar Banten
                '9028' => 'PRIMA', // Mayapada
                '9027' => 'BANK133', // JTrust
                '9026' => 'FAMA', // IFI
                '9025' => 'VICTORIA', // Rabobank
                '9024' => 'INDEX', // Anda / Antardaerah
                '9023' => 'MULTI', // Ekonomi
                '9022' => 'PURBA', // Bumi Arta
                '9021' => 'ARTOS', // Bank of China
                '9020' => 'ANZ',
                '9019' => 'BCAS', // Capital Indonesia
                '9018' => 'STANDARD', // Standard Chartered
                '9017' => 'DBS',
                '9016' => 'JATENG', // Tokyo Mitsubishi UFJ
                '9015' => 'HSBC',
                '9014' => 'BTPN', // Artha Graha
                '9013' => 'CMB', // Windu Kentjana
                '9012' => 'CITIBANK',
                '9011' => 'NISP', // OCBC NISP
                '9010' => 'UOB',
                '9009' => 'CIMB',
                '9008' => 'PANIN',
                '9007' => 'MAYBANK',
                '9006' => 'BCA',
                '9005' => 'PERMATA',
                '9004' => 'DANAMON',
                '9003' => 'BNI',
                '9002' => 'MANDIRI',
                '9001' => 'BRI',
            ];
            if (!isset($bankCodeMap[$bankNumber])) {
                return ['success' => false, 'message' => '不支持的银行编号: ' . $bankNumber];
            }
            $bankNumber = $bankCodeMap[$bankNumber];
        }
        $types = [
            'qris' => ['601', 'QRIS'],
            'dana' => ['602', 'DANA'],
            'bank' => ['603', $bankNumber],
            'va' => ['604', $bankNumber],
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
