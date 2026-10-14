<?php

namespace Telegram\Controller;

use \think\Controller;
use Think\Log;

/**
 * @author mapeijian
 * @date   2018-06-06
 */
class ApiController extends Controller
{
    protected $code = 'Tel_Api';
    protected $bot_name;
    protected $token;
    protected $title;
    protected $callback = '';
    protected $channel_url = '';
    protected $webhookSecret = '';

    public function __construct()
    {
        $this->guardHttpEntry();
        // 数据库配置
        $envFile = dirname(__FILE__) . '/../../../.env';
        $env = array();
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $env[trim($key)] = trim($value);
                }
            }
        }
        parent::__construct();
        $this->bot_name = isset($env['TELEGRAM_BOT_NAME']) ? $env['TELEGRAM_BOT_NAME'] : 'I8Pay_PHP_Bot';
        $this->token = isset($env['TELEGRAM_TOKEN']) ? trim($env['TELEGRAM_TOKEN']) : '';
        if ($this->token === '') {
            throw new \RuntimeException('缺少 TELEGRAM_TOKEN 配置');
        }
        $this->webhookSecret = isset($env['TELEGRAM_WEBHOOK_SECRET'])
            ? trim($env['TELEGRAM_WEBHOOK_SECRET']) : '';
        $this->title = isset($env['TELEGRAM_TITLE']) ? $env['TELEGRAM_TITLE'] : 'I8Pay';
        $this->channel_url = isset($env['TELEGRAM_CHANEL_URL']) ? $env['TELEGRAM_CHANEL_URL'] : '';
        $this->callback = 'https://' . C('DOMAIN') . '/Telegram_Api_receive.html';
    }

    public function index()
    {
    }

    // 只限制外部路由；其他模块通过 R() 的内部调用保留原有权限边界。
    private function guardHttpEntry()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        $directRequest = !defined('MODULE_NAME') || !defined('CONTROLLER_NAME')
            || (strcasecmp(MODULE_NAME, 'Telegram') === 0
                && strcasecmp(CONTROLLER_NAME, 'Api') === 0);
        if ($directRequest && (!defined('ACTION_NAME')
            || !in_array(strtolower(ACTION_NAME), ['receive', 'qunfa'], true))) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    // 群发分页使用独立用途的内部凭据，不接受 Telegram 回调头代替它。
    private function internalBroadcastSecret()
    {
        $this->requireWebhookSecret();
        return hash_hmac('sha256', 'telegram-internal-broadcast-v1', $this->webhookSecret);
    }

    private function requireInternalBroadcast()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            exit;
        }
        if (!preg_match('/\A[A-Za-z0-9_-]{32,256}\z/', $this->webhookSecret)) {
            http_response_code(503);
            exit;
        }
        $provided = $_SERVER['HTTP_X_TELEGRAM_INTERNAL_SECRET'] ?? '';
        if (!is_string($provided) || !hash_equals($this->internalBroadcastSecret(), $provided)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    // 管理操作仅允许服务器命令行执行，禁止通过公开路由调用。
    private function requireWebhookManagement()
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    private function requireWebhookSecret()
    {
        if (!preg_match('/\A[A-Za-z0-9_-]{32,256}\z/', $this->webhookSecret)) {
            throw new \RuntimeException('请配置 32 至 256 位随机 TELEGRAM_WEBHOOK_SECRET（仅字母、数字、下划线和连字符）');
        }
    }

    // 向 Telegram 注册固定回调地址，不丢弃积压消息。
    public function buildWebhook()
    {
        $this->requireWebhookManagement();
        $this->requireWebhookSecret();
        $url = 'https://api.telegram.org/bot' . $this->token . '/setWebhook';
        $data = [
            'url' => $this->callback,
            'secret_token' => $this->webhookSecret,
            'allowed_updates' => json_encode([
                'message', 'edited_message', 'channel_post',
                'edited_channel_post', 'callback_query',
            ]),
        ];
        $result = json_decode($this->curlPost($url, $data), true);
        if (!is_array($result) || ($result['ok'] ?? false) !== true
            || ($result['result'] ?? false) !== true) {
            throw new \RuntimeException('设置 webhook 失败，请检查网络、Token 和回调域名');
        }
        echo "域名绑定成功！\n";
    }

    // 仅命令行查看，避免将回调密钥写入日志或公开页面。
    public function getInfo()
    {
        $this->requireWebhookManagement();
        $url = 'https://api.telegram.org/bot' . $this->token . '/getWebhookInfo';
        $result = json_decode($this->curlPost($url, []), true);
        if (!is_array($result) || ($result['ok'] ?? false) !== true
            || !is_array($result['result'] ?? null)) {
            throw new \RuntimeException('查询 webhook 失败');
        }
        $info = $result['result'];
        $info['matches_expected_url'] = ($info['url'] ?? '') === $this->callback;
        // 旧配置可能含查询参数中的密钥，不输出它们。
        $info['url'] = preg_replace('/[?#].*$/', '', $info['url'] ?? '');
        unset($info['last_error_message']);
        echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }

    // 接收回调：先认证，再读取、记录及处理业务数据。
    public function receive()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            exit;
        }
        if (!preg_match('/\A[A-Za-z0-9_-]{32,256}\z/', $this->webhookSecret)) {
            http_response_code(503);
            exit;
        }
        $provided = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        if (!is_string($provided) || !hash_equals($this->webhookSecret, $provided)) {
            http_response_code(403);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['update_id']) || !is_int($input['update_id'])) {
            http_response_code(400);
            exit;
        }
        // 当前业务处理器只处理普通消息和按钮回调。
        if (isset($input['message']) || isset($input['callback_query'])) {
            log_place_order($this->code . '_receive', '回调信息', json_encode($input, JSON_UNESCAPED_UNICODE));
            $this->to_do($input);
        }
        http_response_code(200);
        echo 'OK';
    }

    public function send($chatId, $text,$message_id='',$parse_mode='',$markup=''){
        return $this->sendMessage($chatId, $text, $message_id, $parse_mode,$markup);
    }

    // 编辑消息的内联键盘（用于更新按钮状态）
    public function editMessageReplyMarkup($chatId, $messageId, $markup='')
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/editMessageReplyMarkup';
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];
        if($markup != ''){
            $data['reply_markup'] = json_encode($markup, JSON_UNESCAPED_UNICODE);
        }
        log_place_order($this->code . '_editMessageReplyMarkup', "更新按钮", json_encode($data, JSON_UNESCAPED_UNICODE));
        $result = $this->curlPost($url, $data);
        $result = json_decode($result, true);
        return $result;
    }

    // 发送处理完成后的新键盘（按钮变灰/显示已处理）
    private function sendProcessedKeyboard($chatId, $messageId, $action, $username)
    {
        if ($action === 'pass') {
            $btn_text = '已通过';
        } else {
            $btn_text = '已驳回';
        }
        $markup = [
            'inline_keyboard' => [[
                ['text' => $username . "：" . $btn_text, 'callback_data' => 'processed']
            ]]
        ];
        return $this->editMessageReplyMarkup($chatId, $messageId, $markup);
    }

    // 回复用户信息
    private function sendMessage($chatId, $text,$message_id='',$parse_mode='',$markup='')
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/sendMessage';
        // $param = "?chat_id=" . $chatId . "&text=" . $text;
        if($parse_mode!=''){
            $text = $this->sanitizeText($text, $parse_mode);
        }
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if($parse_mode!=''){
            $data['parse_mode'] = $parse_mode;
        }
        if($message_id!=''){
            $data['reply_parameters'] = json_encode([
                'message_id'=> $message_id,
                'chat_id'=>$chatId,
            ], JSON_UNESCAPED_UNICODE);
        }
        if($markup!=''){
            $data['reply_markup'] = json_encode($markup
                , JSON_UNESCAPED_UNICODE);
        }
        log_place_order($this->code . '_sendMessage', "发送", json_encode($data, JSON_UNESCAPED_UNICODE));    //日志
        $result = $this->curlPost($url, $data);

        log_place_order($this->code . '_sendMessage', "返回", $result);    //日志
        $result = json_decode($result, true);
        if ($result['ok'] == true) {
            return $result;
        } else {
            // 记录详细错误信息
            $errorInfo = [
                'error' => $result['description'] ?: 'Unknown error',
                'text' => $text,
                'parse_mode' => $parse_mode,
                'chat_id' => $chatId
            ];
            log_place_order($this->code . '_sendMessage_error', "发送", json_encode($data, JSON_UNESCAPED_UNICODE));    //日志
//            log_place_order($this->code . '_sendMessage_error', "错误", json_encode($errorInfo, JSON_UNESCAPED_UNICODE));

            // 尝试不带格式重新发送
//            if (isset($result['error_code']) && $result['error_code'] == 400) {
//                unset($data['parse_mode']);
//                $result = $this->curlPost($url, $data);
//                $result = json_decode($result, true);
//                return $result['ok'] ? $result : "sendError";
//            }
            //清理掉官方返回 不存在的群
            if ($result['error_code'] == 400 && $result['description'] == 'Bad Request: chat not found') {
                $where['telegram_id'] = $chatId;
                $re = M('Member')->where($where)->save(['telegram_id' => '']);
            }
            return "sendError";
        }
    }

    // 回复用户信息
    private function sendPhoto($chatId, $photo, $text='', $message_thread_id='', $parse_mode='')
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/sendPhoto';
        $data = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $text,
        ];
        if($message_thread_id!=''){
            $data['reply_parameters'] = json_encode([
                'message_id'=> $message_thread_id,
                'chat_id'=>$chatId,
            ], JSON_UNESCAPED_UNICODE);
        }
        // log_place_order($this->code . '_sendPhoto', "发送", json_encode($data, JSON_UNESCAPED_UNICODE));    //日志
        $result = $this->curlPost($url, $data);
        // log_place_order($this->code . '_sendPhoto', "返回", $result);    //日志
        $result = json_decode($result, true);
        if ($result['ok'] == true) {
            return $result;
        } else {
            // 记录详细错误信息
            $errorInfo = [
                'error' => $result['description'] ?: 'Unknown error',
                'text' => $text,
                'parse_mode' => $parse_mode,
                'chat_id' => $chatId
            ];
            log_place_order($this->code . '_sendPhoto_error', "发送", json_encode($data, JSON_UNESCAPED_UNICODE));    //日志
            log_place_order($this->code . '_sendPhoto_error', "错误", json_encode($errorInfo, JSON_UNESCAPED_UNICODE));

            // 尝试不带格式重新发送
//            if (isset($result['error_code']) && $result['error_code'] == 400) {
//                unset($data['parse_mode']);
//                $result = $this->curlPost($url, $data);
//                $result = json_decode($result, true);
//                return $result['ok'] ? $result : "sendError";
//            }

            return "sendError";
        }
    }

    // 删除消息
    private function deleteMessage($chatId, $messageId)
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/deleteMessage';
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];
        log_place_order($this->code . '_deleteMessage', "删除消息", json_encode($data, JSON_UNESCAPED_UNICODE));
        $result = $this->curlPost($url, $data);
        $result = json_decode($result, true);
        return $result;
    }

    private function to_do($input)
    {
        $callback_query = $input['callback_query'];
        if(!empty($callback_query)){
            $username = $callback_query['from']['username'];
            $chat_id = $callback_query['message']['chat']['id'];
            $message_id = $callback_query['message']['message_id'];

            $userInfo = self::get_user($chat_id);
            $operator_arr = explode("\r\n", $userInfo['info']['operators']);
            if (trim($userInfo['info']['operators']) =='' || empty($operator_arr)) {
                $message = '未设置审核人员！请联系值班客服';
                $this->sendMessage($chat_id, $message, $message_id);
                return;
            }
            foreach ($operator_arr as $k => $v) {
                if ($v) {
                    $operator_arr[$k] = trim($v);
                }
            }
            if (trim($username) == '' || !$username || !in_array($username, $operator_arr)) {
                $message = "\r\n用户：" . $username  . ' 为非审核人员，无权操作';
                $this->sendMessage($chat_id, $message, $message_id);
                return;
            }



            //存缓存。防止重复操作
            $redis = $this->redis_connect();
            if($redis->get('callback_query' . $chat_id . $message_id)){
                return;
            }
            $redis->set('callback_query' . $chat_id . $message_id ,'1',600);

            $text = $callback_query['data'];
            $str = explode(' ', $text);
            if($str[0] === 'pass'){       //通过
                $do = '通过';
                $callback = 1;
            }elseif($str[0] === 'reject'){     //驳回
                $do = '驳回';
                $callback = 2;
            }else{
                log_place_order($this->code . '_callback_query', "异常", json_encode($callback_query));    //日志
                return;
            }

            if($str[1]){
                $Wttklist = D('Wttklist');
                $date = date('Ymd', strtotime(substr($str[1], 1, 8)));  //获取订单日期
                $table = $Wttklist->getRealTableName($date);
                //确认该订单为未处理订单
                $wttkData = $Wttklist->table($table)->where(['orderid'=>$str[1], 'status'=>'0'])->find();
                if(empty($wttkData) || !$wttkData){
//                    log_place_order($this->code . '_callback_query', "订单状态异常", $str[1]);    //日志
//                    log_place_order($this->code . '_callback_query', "sql", $Wttklist->table($table)->getLastSql());    //日志
                    $message = '订单状态异常！请联系值班客服';
                    $this->sendMessage($chat_id, $message, $message_id);
                    return;
                }
            }else{
                return;
            }

            $add_Callback_data = [
                'orderid' => $str[1],
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'doUser' => $username,
                'callback' => $callback,
                'create_time'=>time(),
            ];
            try {
                $re = M('TelegramApiDforderCallback')->add($add_Callback_data);
            } catch (\Exception $e) {
                log_place_order($this->code . '_callback_error', "记录插入异常", json_encode($add_Callback_data, JSON_UNESCAPED_UNICODE));    //日志
            }
            try {
                $add_Autodf_data = [
                    'orderid' => $str[1],
                    'callback' => $callback,
                    'status' => 0,
                    'create_time'=>time(),
                ];
                $re2 = M('TelegramApiDforderAutodf')->add($add_Autodf_data);
            } catch (\Exception $e) {
                log_place_order($this->code . '_callback_query', "重复操作,程序错误", $e);    //日志
                return;
            }

            $message = '订单号：' . $str[1] . "\r\n用户：" . $username . '，' . $do . '该订单，执行成功';
            $this->sendMessage($chat_id, $message, $message_id);

            // 更新按钮状态为已处理
            $this->sendProcessedKeyboard($chat_id, $message_id, $str[0], $username);
            return;
        }

        $text = $input['message']['text'];
        $chat_id = $input['message']['chat']['id'];
        $message_id = $input['message']['message_id'];
        $photo = $input['message']['photo'];
        $caption = $input['message']['caption'];

        $document = $input['message']['document'];
        if($photo){
            $text = $caption;
        }

        // 检测并删除 exe 文件
        if($document){
            $file_name = isset($document['file_name']) ? $document['file_name'] : '';
            if(stripos($file_name, '.exe') !== false){
                $from_username = isset($input['message']['from']['username']) ? $input['message']['from']['username'] : $input['message']['from']['first_name'];
                $warning_msg = "请勿发送该类型文件 【" . $from_username . "】";
                $this->sendMessage($chat_id, $warning_msg, $message_id);
                $this->deleteMessage($chat_id, $message_id);
                return;
            }
        }
        $str = explode(' ', $text);

        if (isset($input['message']['new_chat_members'])) {
            $members = $input['message']['new_chat_members'];
            foreach ($members as $v) {
                $this->sendMessage($chat_id, "欢迎 " . $v['first_name'] . " 来到本群 ！！\r\n");
            }
        }

        //转发 回复机器人的信息
        if (isset($input['message']['reply_to_message'])) {
            $reply_to_message = $input['message']['reply_to_message'];
            if($reply_to_message['from']['is_bot'] === true){
                $user_check = M('TelegramApiDforder')->where(['message_id2' => $reply_to_message['message_id']])->find();
                if($user_check && !empty($user_check)){
                    if($photo){
                        $this->sendPhoto($user_check['chat_id'], $photo[0]['file_id'],$text, $user_check['message_id']);
                    }else{
                        $this->sendMessage($user_check['chat_id'], $text, $user_check['message_id']);
                    }
                }
            }
            return;
        }

        //查询显示群组ID
        if (strpos($text, '/show') !== false || strpos($text, '/show@' . $this->bot_name) !== false) {
            $message='';

            $message = "群组ID：" . $chat_id . "\r\n--------------------------------\r\n";
            $message .= $this->title . "，7*24小时服务\r\n 可以帮助您查询余额，收款订单详情、出款订单详情\r\n ";
            $message .= "输入 /show 可查看群组信息及提示，无需再添加其他参数\r\n ";
            $message .= "输入 /balance 可查询当前账户的查询余额，无需再添加其他参数\r\n ";
            $message .= "输入 /ds 可查询收款订单详情，格式：/ds 订单号1 订单号2 或 /ds 订单号1\n订单号2（最多10个）；例如:/ds 928271637212 928271637213\r\n ";
            $message .= "输入 /df 可查询出款订单详情，格式：/df 订单号1 订单号2 或 /df 订单号1\n订单号2（最多10个）；例如:/df 928271637212 928271637213\r\n ";
            // $message .= "输入 /pz 可查询出款凭证，后面+空格+订单号；例如:/pz 928271637212\r\n ";
            $this->sendMessage($chat_id, $message, $message_id);
            return;
        }

        //绑定用户
        if (strpos($text, '/bind') !== false) {
            if ($str[1]) {
                $user_check = M('Member')->where(['telegram_id' => $chat_id])->getField('id');
//                log_place_order($this->code . '_bind', "sql", M('Member')->getLastSql());    //日志
                if ($user_check) {
                    $uid = $user_check['id'] + 10000;
                    $message = "本群已绑定商户号：" . $uid . "\r\n--------------------------------\r\n";
                    $message .= $this->title . "，7*24小时服务\r\n 可以帮助您查询余额，收款订单详情、出款订单详情\r\n ";
                    $message .= "输入 /show 可查看群组信息及提示，无需再添加其他参数\r\n ";
                    $message .= "输入 /balance 可查询当前账户的查询余额，无需再添加其他参数\r\n ";
                    $message .= "输入 /ds 可查询收款订单详情，格式：/ds 订单号1 订单号2 或 /ds 订单号1\n订单号2（最多10个）；例如:/ds 928271637212 928271637213\r\n ";
                    $message .= "输入 /df 可查询出款订单详情，格式：/df 订单号1 订单号2 或 /df 订单号1\n订单号2（最多10个）；例如:/df 928271637212 928271637213\r\n ";
                    // $message .= "输入 /pz 可查询出款凭证，后面+空格+订单号；例如:/pz 928271637212\r\n ";
                    $this->sendMessage($chat_id, $message, $message_id);
                    return;
                } else {
                    $uid = $str[1] - 10000;
                    try {
                        $re = M('Member')->where(['id' => $uid])->save(['telegram_id' => $chat_id]);
                    } catch (\Exception $e) {
                        log_place_order($this->code . '_bind', "sql错误", $e);    //日志
                        return;
                    }

//                    log_place_order($this->code . '_bind', "uid", $uid);    //日志
//                    log_place_order($this->code . '_bind', "chat_id", $chat_id);    //日志
//                    log_place_order($this->code . '_bind', "sql", M('Member')->getLastSql());    //日志
                    if ($re) {
                        $message = "成功绑定商户号：" . $str[1] . "\r\n--------------------------------\r\n";
                        $message .= $this->title . "，7*24小时服务\r\n 可以帮助您查询余额，收款订单详情、出款订单详情\r\n ";
                        $message .= "输入 /show 可查看群组信息及提示，无需再添加其他参数\r\n ";
                        $message .= "输入 /balance 可查询当前账户的查询余额，无需再添加其他参数\r\n ";
                        $message .= "输入 /ds 可查询收款订单详情，格式：/ds 订单号1 订单号2 或 /ds 订单号1\n订单号2（最多10个）；例如:/ds 928271637212 928271637213\r\n ";
                        $message .= "输入 /df 可查询出款订单详情，格式：/df 订单号1 订单号2 或 /df 订单号1\n订单号2（最多10个）；例如:/df 928271637212 928271637213\r\n ";
                        // $message .= "输入 /pz 可查询出款凭证，后面+空格+订单号；例如:/pz 928271637212\r\n ";
                        $this->sendMessage($chat_id, $message, $message_id);
                    } else {
                        $this->sendMessage($chat_id, '绑定' . $str[1] . '失败', $message_id);
                    }
                }
            } else {
                $this->sendMessage($chat_id, '绑定失败', $message_id);
            }
            return;
        }

        //根据群ID 获取用户信息
        $userInfo = self::get_user($chat_id);
        if ($userInfo['status'] !== 1 || !$userInfo['info']['id']) {
            if($chat_id){
                $this->sendMessage($chat_id, '该群未绑定用户！请联系值班客服', $message_id);
            }
            return;
        }

        //查询usdt汇率
        if (strpos($text, '/usdt@' . $this->bot_name) !== false) {
            $where['currency'] = $userInfo['info']['currency'];
            $rate = M('URate')->where($where)->getField('rate');
            if($chat_id && $rate){
                $message = '当前汇率：' . $rate;
                $this->sendMessage($chat_id, $message, $message_id);
            }
            return;
        }

        //查询用户余额
        if (strpos($text, '/balance@' . $this->bot_name) !== false) {
            //获取集团账号下的用户余额信息
            if($userInfo['info']['groupid'] == 5 ){
                $this->getGroup5User($userInfo['info']['id'],$chat_id, $message_id);
            }else{
                $uid = $userInfo['info']['id'] + 10000;
                $statistics = $this->statistics($uid);
                $message = '';
                $message .= "商户数据播报\r\n--------------------------------\r\n";
                $message .= "商户号 ：`" . $uid . "`\r\n";
                $message .= "商户名称：`" . $userInfo['info']['username'] . "`\r\n";
                $message .= "可用余额：" . $userInfo['info']['balance'] . "\r\n";
                $message .= "冻结余额：" . $userInfo['info']['blockedbalance'] . "\r\n";
                $message .= "======代收数据======\r\n";
                $message .= "订单金额：" . $statistics['all_o_sum'] . "  ";
                $message .= "笔数：" . $statistics['all_o_num'] . "\r\n";
                $message .= "成功金额：" . $statistics['o_sum'] . "  ";
                $message .= "笔数：" . $statistics['o_num'] . "\r\n";
                $message .= "成功率：" . $statistics['o_success_rate'] . "%\r\n";
                $message .= "======代付数据======\r\n";
                $message .= "订单金额：" . $statistics['all_w_amount'] . "  ";
                $message .= "笔数：" . $statistics['all_w_i'] . "\r\n";
                $message .= "成功金额：" . $statistics['w_sum_done'] . "  ";
                $message .= "笔数：" . $statistics['wi_done'] . "\r\n";
                $message .= "代付中金额：" . $statistics['w_sum_do'] . "  ";
                $message .= "笔数：" . $statistics['wi_do'] . "\r\n";
                $message .= "--------------------------------\r\n";
                $message .= "查询时间：" . date('Y-m-d H:i:s', time()) . "\r\n";
                $message .= "当前余额及时核对以此为准，如有疑问联系值班客服\r\n";
                if($this->channel_url!=''){
                    $btn['text'] = $this->title . '官方频道，点击进入';
                    $btn['url'] = $this->channel_url;
                    $markup = [
                        'inline_keyboard'=>[[
                            $btn
                        ]],
                    ];
                }

                $this->sendMessage($chat_id, $message, $message_id, 'Markdown',$markup);
            }
            return;
        }

        //开启客服补单
        if (strpos($text, '/开启客服补单') !== false) {
            $title = $input['message']['chat']['title'];
            $title = mb_convert_encoding($title, 'UTF-8', mb_detect_encoding($title, "auto"));
            $where=[];
            $where['telegram_id'] = $chat_id;
            $is_re = M('TelegramApi')->where($where)->find();
            if($is_re && !is_null($is_re)){
                if($is_re['status'] === 1){
                    $this->sendMessage($chat_id, "此群已开启客服补单功能\r\n");
                    return;
                }else{
                    $re = M('TelegramApi')->where($where)->save(['telegram_name' => $title,'telegram_id' => $chat_id,'status'=>1]);
                }
            }else{
                $re = M('TelegramApi')->add(['telegram_name' => $title,'telegram_id' => $chat_id,'status'=>1]);
            }

            if($re){
                $this->sendMessage($chat_id, "SUCCESS，客服补单功能开启成功\r\n");
            }else{
                $this->sendMessage($chat_id, "FAIL，客服补单功能开启失败\r\n");
            }
        }

        //关闭客服补单
        if (strpos($text, '/关闭客服补单') !== false) {
            $where=[];
            $where['telegram_id'] = $chat_id;
            $is_re = M('TelegramApi')->where($where)->find();
            if($is_re){
                if($is_re['status'] === 0){
                    $this->sendMessage($chat_id, "此群已关闭客服补单功能\r\n");
                    return;
                }else{
                    $title = $input['message']['chat']['title'];
                    $re = M('TelegramApi')->where($where)->save(['telegram_name' => $title,'telegram_id' => $chat_id,'status'=>0]);
                }
            }else{
                $this->sendMessage($chat_id, "此群未开启客服补单功能\r\n");
                return;
            }
            if($re){
                $this->sendMessage($chat_id, "SUCCESS，客服补单功能关闭成功\r\n");
            }else{
                $this->sendMessage($chat_id, "FAIL，客服补单功能关闭失败\r\n");
            }
        }

        //开启客服补单
        if (strpos($text, '/开启群发') !== false) {
            $title = $input['message']['chat']['title'];
            $where=[];
            $where['telegram_id'] = $chat_id;
            $is_re = M('TelegramApi')->where($where)->find();
            // log_place_order($this->code . '_qunfaSql', "sql", M('TelegramApi')->getLastSql());    //日志
            // log_place_order($this->code . '_qunfaSql', "re", json_encode($is_re, JSON_UNESCAPED_UNICODE));    //日志
            if($is_re){
                if($is_re['is_open'] === 1){
                    $this->sendMessage($chat_id, "此群已开启群发功能\r\n");
                    return;
                }else{
                    $re = M('TelegramApi')->where($where)->save(['telegram_name' => $title,'telegram_id' => $chat_id,'is_open'=>1]);
                }
            }else{
                $re = M('TelegramApi')->add(['telegram_name' => $title,'telegram_id' => $chat_id,'is_open'=>1]);
            }

            if($re){
                $this->sendMessage($chat_id, "SUCCESS，群发开启成功\r\n");
            }else{
                $this->sendMessage($chat_id, "FAIL，群发开启失败\r\n");
            }
        }

        //关闭客服补单
        if (strpos($text, '/关闭群发') !== false) {
            $where=[];
            $where['telegram_id'] = $chat_id;
            $is_re = M('TelegramApi')->where($where)->find();
            if($is_re){
                if($is_re['is_open'] === 0){
                    $this->sendMessage($chat_id, "此群已关闭群发功能\r\n");
                    return;
                }else{
                    $title = $input['message']['chat']['title'];
                    $re = M('TelegramApi')->where($where)->save(['telegram_name' => $title,'telegram_id' => $chat_id,'is_open'=>0]);
                }
            }else{
                $this->sendMessage($chat_id, "此群未开启群发功能\r\n");
                return;
            }
            if($re){
                $this->sendMessage($chat_id, "SUCCESS，群发功能关闭成功\r\n");
            }else{
                $this->sendMessage($chat_id, "FAIL，群发功能关闭失败\r\n");
            }
            return;
        }

        if (strpos($text, '/qunfa') !== false) {
            $where=[];
            $where['telegram_id'] = $chat_id;
            $where['is_open'] = 1;
            $is_re = M('TelegramApi')->where($where)->find();

            // log_place_order($this->code . '_qunfaSql', "sql", M('TelegramApi')->getLastSql());    //日志
            // log_place_order($this->code . '_qunfaSql', "re", json_encode($is_re, JSON_UNESCAPED_UNICODE));    //日志
            if($is_re){
                $text = str_replace('/qunfa', "", $text);
                $data['text'] = $text;
                $url = 'https://' . C('DOMAIN') . '/Telegram_Api_qunfa.html';

                if($photo && !empty($photo)){
                    $data['photo'] = 1;
                    $data['file_id'] = $photo[0]['file_id'];
                }else{
                    $data['photo'] = 0;
                }
                $data['row']=0;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'X-Telegram-Internal-Secret: ' . $this->internalBroadcastSecret(),
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 验证 HTTPS 证书及主机名
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_HEADER, false);

                $mh = curl_multi_init();
                curl_multi_add_handle($mh, $ch);

                // 执行curl_multi
                do {
                    $status = curl_multi_exec($mh, $active);
                    if ($active) {
                        curl_multi_select($mh); // 等待所有句柄的活动
                    }
                } while ($active && $status == CURLM_OK);

                // 关闭句柄
                curl_multi_remove_handle($mh, $ch);
                curl_multi_close($mh);
                //获取错误编码
                $curlErrno = curl_errno($ch);
                if($curlErrno){
                    log_place_order($this->code . '_qunfaSql', "curlcode", $curlErrno);    //日志
                    log_place_order($this->code . '_qunfaSql', "curlErrno", str_replace($this->token, '[REDACTED]', curl_error($ch)));    //日志
                }
                curl_close($ch);


                // $file_contents = curl_exec($ch);
                // //获取错误编码
                // $curlErrno = curl_errno($ch);
                // if($curlErrno){
                //     log_place_order($this->code . '_qunfaSql', "curlcode", $curlErrno);    //日志
                //     log_place_order($this->code . '_qunfaSql', "curlErrno", str_replace($this->token, '[REDACTED]', curl_error($ch)));    //日志
                // }
                // curl_close($ch);

                // $result = $this->curlPost($url, $data);
                return true;
            }
            return true;
        }

        // //查询成功率
        // if(strpos($text, '/scr') !== false){

        //         $this->sendMessage($chat_id , '查询成功率', $message_id);
        //         return;
        // }
        // //查询用户今日使用的upi
        // if(strpos($text, '/upiall') !== false){

        //         $this->sendMessage($chat_id , '查询用户今日使用的upi', $message_id);
        //         return;
        // }

        // //查询代付凭证查询
        // if (strpos($text, '/pz') !== false) {
        //     if(isset($str[1])){
        //         $order_info = self:: get_dforder($str[1], $userInfo['info']['id']);
        //         $this->doHZ($order_info, $chat_id, $message, $message_id);
        //     }
        //     return;
        // }

        //查询代付订单（支持多订单查询，用换行或空格分隔）
        if (strpos($text, '/df') !== false) {
            $text = str_replace("\n", " ", $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $parts = explode(' ', $text);
            $order_ids = [];
            foreach ($parts as $part) {
                if ($part !== '/df' && $part !== ('/df@' . $this->bot_name) && !empty($part)) {
                    $order_ids[] = $part;
                }
            }
            if (empty($order_ids)) {
                $this->sendMessage($chat_id, '请输入订单号，格式：/df 订单号1 订单号2 或 /df 订单号1\n订单号2', $message_id);
                return;
            }
            $count = count($order_ids);
            if ($count > 10) {
                $this->sendMessage($chat_id, '最多支持同时查询10个订单', $message_id);
                return;
            }
            $all_messages = [];
            foreach ($order_ids as $orderid) {
                $order_info = self::get_dforder($orderid, $userInfo['info']['id']);
                $order_msg = $this->buildDFMessage($order_info, $orderid);
                $all_messages[] = $order_msg;
            }
            $final_message = implode("\r\n", $all_messages);
            $this->sendMessage($chat_id, $final_message, $message_id);
            return;
        }

        //查询订单（支持多订单查询，用换行或空格分隔）
        if (strpos($text, '/ds') !== false) {
            $text = str_replace("\n", " ", $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $parts = explode(' ', $text);
            $order_ids = [];
            foreach ($parts as $part) {
                if ($part !== '/ds' && $part !== ('/ds@' . $this->bot_name) && !empty($part)) {
                    $order_ids[] = $part;
                }
            }
            if (empty($order_ids)) {
                $this->sendMessage($chat_id, '请输入订单号，格式：/ds 订单号1 订单号2 或 /ds 订单号1\n订单号2', $message_id);
                return;
            }
            $count = count($order_ids);
            if ($count > 10) {
                $this->sendMessage($chat_id, '最多支持同时查询10个订单', $message_id);
                return;
            }
            $all_messages = [];
            foreach ($order_ids as $orderid) {
                $order_info = self::get_order($orderid, $userInfo['info']['id']);
                $order_msg = $this->buildDSMessage($order_info, $orderid);
                $all_messages[] = $order_msg;
            }
            $final_message = implode("\r\n", $all_messages);
            $this->sendMessage($chat_id, $final_message, $message_id);
            return;
        }

        //查询代收订单每小时成功率
        if (strpos($text, '/rate') !== false) {
            $uid = $userInfo['info']['id'] + 10000;
            $this->sendHourlySuccessRate($uid, $chat_id, $message_id);
            return;
        }

        //查询代收订单每小时成功率（简化版）
        if (strpos($text, '/r') !== false) {
            $uid = $userInfo['info']['id'] + 10000;
            $this->sendHourlySuccessRateSimple($uid, $chat_id, $message_id);
            return;
        }

        return;
    }

    //执行群发操作：同一批并发，避免逐群等待和递归 HTTP 调用。
    public function qunfa()
    {
        $this->requireInternalBroadcast();
        // HTTP 群发只使用已认证的 POST 数据，忽略查询参数和 Cookie。
        if (PHP_SAPI !== 'cli') {
            $_REQUEST = $_POST;
        }
        $text = isset($_REQUEST['text']) ? trim((string)$_REQUEST['text']) : '';
        if ($text === '') {
            return true;
        }
        $photo = !empty($_REQUEST['photo']);
        $fileId = isset($_REQUEST['file_id']) ? (string)$_REQUEST['file_id'] : '';
        if ($photo && $fileId === '') {
            log_place_order($this->code . '_qunfa', '群发失败', '缺少 file_id');
            return false;
        }
        // 兼容原有 row 参数（原来每页 10 个商户）。
        $row = isset($_REQUEST['row']) ? max(0, (int)$_REQUEST['row']) : 0;
        $members = M('Member')->field('telegram_id')->where(array(
            'telegram_id' => array('neq', '')
        ))->order('id asc')->select();
        $members = array_slice((array)$members, $row * 10);
        $pending = array();
        foreach ($members as $member) {
            $chatId = trim((string)$member['telegram_id']);
            if ($chatId !== '') {
                // 多个商户绑定同一群时只发送一次。
                $pending[$chatId] = array('chat_id' => $chatId, 'attempt' => 0);
            }
        }
        $pending = array_values($pending);
        ignore_user_abort(true);
        set_time_limit(0);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        // PHP-FPM 下先结束内部 HTTP 请求，再继续发送，避免 webhook 等待超时。
        if (function_exists('fastcgi_finish_request')) {
            echo 'OK';
            fastcgi_finish_request();
        }
        $started = microtime(true);
        $summary = array('total' => count($pending), 'success' => 0, 'failed' => 0, 'retries' => 0);
        while (!empty($pending)) {
            $batch = array_splice($pending, 0, 10);
            $batchStarted = microtime(true);
            $results = $this->sendBroadcastBatch($batch, $text, $photo, $fileId);
            $retryAfter = 0;
            foreach ($results as $item) {
                $job = $item['job'];
                $response = $item['response'];
                if (is_array($response) && !empty($response['ok'])) {
                    $summary['success']++;
                    continue;
                }
                if (is_array($response) && isset($response['error_code']) && (int)$response['error_code'] === 429) {
                    // 即使此群已用完重试次数，其他群也需要遵守限流等待。
                    $retryAfter = max($retryAfter, isset($response['parameters']['retry_after']) ? max(1, (int)$response['parameters']['retry_after']) : 1);
                    if ($job['attempt'] < 3) {
                        $job['attempt']++;
                        $summary['retries']++;
                        $pending[] = $job;
                        continue;
                    }
                }
                if (is_array($response) && isset($response['error_code'], $response['description'])
                    && (int)$response['error_code'] === 400 && $response['description'] === 'Bad Request: chat not found') {
                    // 保留原 sendMessage 对已失效群 ID 的清理行为。
                    M('Member')->where(array('telegram_id' => $job['chat_id']))->save(array('telegram_id' => ''));
                }
                // 网络超时可能已经送达，不自动重发，避免重复消息。
                $summary['failed']++;
                log_place_order($this->code . '_qunfa', '发送失败', json_encode(array(
                    'chat_id' => $job['chat_id'],
                    'response' => $response,
                    'curl_errno' => $item['curl_errno'],
                    'curl_error' => isset($item['curl_error']) ? $item['curl_error'] : ''
                ), JSON_UNESCAPED_UNICODE));
            }
            if (!empty($pending)) {
                // 每批最多 10 次请求；429 后暂停整个群发。
                $wait = max(1 - (microtime(true) - $batchStarted), $retryAfter > 0 ? $retryAfter + 1 : 0);
                if ($wait > 0) {
                    usleep((int)ceil($wait * 1000000));
                }
            }
        }
        $summary['seconds'] = round(microtime(true) - $started, 2);
        log_place_order($this->code . '_qunfa', '群发完成', json_encode($summary, JSON_UNESCAPED_UNICODE));
        return true;
    }

    protected function sendBroadcastBatch($batch, $text, $photo, $fileId)
    {
        $multi = curl_multi_init();
        $handles = array();
        foreach ($batch as $job) {
            $data = array('chat_id' => $job['chat_id']);
            if ($photo) {
                $data['photo'] = $fileId;
                $data['caption'] = $text;
            } else {
                $data['text'] = $text;
            }
            $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . ($photo ? 'sendPhoto' : 'sendMessage'));
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ));
            curl_multi_add_handle($multi, $ch);
            $handles[] = array('handle' => $ch, 'job' => $job);
        }
        do {
            $status = curl_multi_exec($multi, $running);
            if ($status === CURLM_CALL_MULTI_PERFORM) {
                continue;
            }
            if ($running && $status === CURLM_OK && curl_multi_select($multi, 1.0) === -1) {
                usleep(10000);
            }
        } while ($running && ($status === CURLM_OK || $status === CURLM_CALL_MULTI_PERFORM));
        $results = array();
        foreach ($handles as $item) {
            $ch = $item['handle'];
            $results[] = array(
                'job' => $item['job'],
                'response' => json_decode(curl_multi_getcontent($ch), true),
                'curl_errno' => curl_errno($ch),
                'curl_error' => str_replace($this->token, '[REDACTED]', curl_error($ch))
            );
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);
        return $results;
    }

    //代付回执查询
    public function doHZ($order_info, $chat_id, $message, $message_id, $parse_mode='Markdown'){
        if ($order_info && $order_info['status'] === 1) {
            $message = "请稍等，正在为您查询凭证...\r\n";
        } else {
            $message = "没有查询到此代付订单";
        }
        $result1 = $this->sendMessage($chat_id, $message, $message_id, $parse_mode);

        if($order_info['info']['df_name']){
            $where=[];
            $where['realname']=['eq',$order_info['info']['df_name']];
            $Member_info = M('Member')->field('id, telegram_id')->where($where)->find();
        }
        if(!isset($Member_info) || empty($Member_info)){
            $where=[];
            $where['id']=['eq',2];
            $Member_info = M('Member')->field('id, telegram_id')->where($where)->find();
        }
        if($order_info['status'] === 1 && isset($Member_info['telegram_id'])){
            $info = $order_info['info'];
            $zf_message = '';
            if($info['status'] == 0){
                $status_str = "未处理";
            } elseif($info['status'] == 1){
                $status_str = "处理中";
            } elseif ($info['status'] == 2) {
                $status_str = "成功未回调";
            } elseif ($info['status'] == 3) {
                $status_str = "成功已回调";
            } elseif ($info['status'] == 4) {
                $status_str = "失败未回调";
            } elseif ($info['status'] == 5) {
                $status_str = "失败已回调";
            } elseif ($info['status'] == 6) {
                $status_str = "已驳回";
            }

            $zf_message .= "最新代付订单详情\r\n--------------------------------\r\n";
            $zf_message .= "通道名称：" . $info['df_name'] . "\r\n";
            $zf_message .= "系统订单号：`" . $info['orderid'] . "`\r\n";
            if ($info['out_trade_no'] != '') {
                $zf_message .= "外部订单号：`" . $info['out_trade_no'] . "`\r\n";
            }
            $zf_message .= "订单金额：" . $info['tkmoney'] . "\r\n";
            if ($info['sqdatetime'] != '') {
                $zf_message .= "申请时间：" . $info['sqdatetime'] . "\r\n";
            }
            if ($info['cldatetime'] != '') {
                $zf_message .= "处理时间：" . $info['cldatetime'] . "\r\n";
            }
            $zf_message .= "订单状态：" . $status_str . "\r\n";
            $zf_message .= "备注 ：" . $info['memo'] . "\r\n";
            $zf_message .= "\r\n======银行信息======\r\n";
            $zf_message .= "银行名称：" . $info['bankname'] . "\r\n";
            $zf_message .= "开户名 ：`" . $info['bankfullname'] . "`\r\n";
            $zf_message .= "账号 ：`" . $info['banknumber'] . "`\r\n";

            $resule2 = $this->sendMessage($Member_info['telegram_id'], $zf_message, '', 'Markdown');
            if($resule2['ok'] === true && isset($resule2['result']['message_id'])){
                $add_data = [
                    'pay_orderid' => $info['orderid'],
                    'member_id' =>$info['userid'] + 10000,
                    'chat_id' => $chat_id,
                    'chat_id2' => $Member_info['telegram_id'],
                    'message_id' => $message_id,
                    'message_id2' => $resule2['result']['message_id'],
                    'create_time'=>time(),
                ];
                // log_place_order($this->code . '_get_dforder22', "信息", json_encode($add_data, JSON_UNESCAPED_UNICODE));    //日志
                $re = M('TelegramApiDforder')->add($add_data);
                // log_place_order($this->code . '_get_dforder22', "sql", M('TelegramApiDforder')->getLastSql());    //日志
            }
        }
        return true;
    }

    //代付处理
    public function doDF($order_info, $chat_id, $message_id, $parse_mode='Markdown'){
        if ($order_info && $order_info['status'] === 1) {
            $info = $order_info['info'];
            $message = '';
            // if($info['status'] > 1){
            if ($info['status'] == 0) {
                $status_str = "未处理";
            } elseif ($info['status'] == 1) {
                $status_str = "处理中";
            } elseif ($info['status'] == 2) {
                $status_str = "成功未回调";
            } elseif ($info['status'] == 3) {
                $status_str = "成功已回调";
            } elseif ($info['status'] == 4) {
                $status_str = "失败未回调";
            } elseif ($info['status'] == 5) {
                $status_str = "失败已回调";
            } elseif ($info['status'] == 6) {
                $status_str = "已驳回";
            }

            if($info['status'] == 2 || $info['status'] == 3){
//                $remark = 'https://' . C('DOMAIN') . '/Payment_Index_voucher.html?casOrdNo=' . $info['orderid'];

                // $where['id'] = $info['df_id'];
                // $pfa_list = M('PayForAnother')->where($where)->find();
                // $file = APP_PATH . 'Payment/Controller/' . $pfa_list['code'] . 'Controller.class.php';
                // if( is_file($file) ){
                //     $result = R('Payment/'.$pfa_list['code'].'/PaymentQuery', [$info, $pfa_list]);
                //         log_place_order($this->code . '_get_dforder', "PaymentQuery", json_encode($result, JSON_UNESCAPED_UNICODE));    //日志
                //     if($result!==FALSE && $result['status']===2 && $result['remark']!=''){
                //         $remark = $result['remark'];
                //     }
                // }
            }

            $message .= "\r\n最新代付订单详情\r\n---------------------------------------------\r\n";
            $message .= "系统订单号：`" . $info['orderid'] . "`\r\n";
            $message .= "外部订单号：`" . $info['out_trade_no'] . "`\r\n";
            $message .= "订单金额：" . $info['tkmoney'] . "\r\n";
            if ($info['sqdatetime'] != '') {
                $message .= "申请时间：" . $info['sqdatetime'] . "\r\n";
            }
            if ($info['cldatetime'] != '') {
                $message .= "处理时间：" . $info['cldatetime'] . "\r\n";
            }
            $message .= "订单状态：" . $status_str . "\r\n";
            $message .= "备注 ：" . $info['memo'] . "\r\n";
            // $message .= "异步通知地址：" . $info['notifyurl'] . "\r\n";
            $message .= "通知次数 ：" . $info['notifycount'] . "\r\n";
            $message .= "最后通知时间：" . date('Y-m-d H:i:s', $info['last_notify_time']) . "\r\n";
            $message .= "\r\n=========银行信息=========\r\n";
            $message .= "银行名称：" . $info['bankname'] . "\r\n";
            if($info['bankzhiname'] != '') {
                $message .= "支行名称：" . $info['bankzhiname'] . "\r\n";
            }
            $message .= "开户名 ：`" . $info['bankfullname'] . "`\r\n";
            $message .= "账号 ：`" . $info['banknumber'] . "`\r\n";
            if(isset($remark) && $remark!=''){
                $message .= "凭证地址 ：" . $remark . "\r\n";
            }


            // }else{

            //     $message .= "系统订单号：`" . $info['orderid'] . "`\r\n";
            //     $message .= "外部订单号：`" . $info['out_trade_no'] . "`\r\n";
            //     $message .= "订单金额：" . $info['tkmoney'] . "\r\n";
            //     $message .= "请稍等，正在为您查询...\r\n";
            //     $message .= "Please wait, we are querying for you ...";

            //     $add_data = [
            //         'pay_orderid' => $info['orderid'],
            //         'member_id' =>$info['userid'] + 10000,
            //         'chat_id' => $chat_id,
            //         'message_id' => $message_id,
            //         'create_time'=>time(),
            //     ];

            //     // log_place_order($this->code . '_get_dforder', "ApiDForder", json_encode($add_data, JSON_UNESCAPED_UNICODE));    //日志
            //     $re = M('TelegramApiDforder')->add($add_data);
            //     // log_place_order($this->code . '_get_dforder', "sql", M('TelegramApiDforder')->getLastSql());    //日志
            // }

        } else {
            $message = "没有查询到代付订单，三种情况\r\n--------------------------------\r\n1.单号输入不正确，请确认单号；\r\n2.不是我们的订单，请确认收付款方是不是我们；\r\n3.订单没有提交到我们这里，请联系群里运营人员。";
        }
        $this->sendMessage($chat_id, $message, $message_id, $parse_mode);
    }

    //构建代付消息内容
    public function buildDFMessage($order_info, $orderid = ''){
        if ($order_info && $order_info['status'] === 1) {
            $info = $order_info['info'];
            $message = '';
            if ($info['status'] == 0) {
                $status_str = "未处理";
            } elseif ($info['status'] == 1) {
                $status_str = "处理中";
            } elseif ($info['status'] == 2) {
                $status_str = "成功未回调";
            } elseif ($info['status'] == 3) {
                $status_str = "成功已回调";
            } elseif ($info['status'] == 4) {
                $status_str = "失败未回调";
            } elseif ($info['status'] == 5) {
                $status_str = "失败已回调";
            } elseif ($info['status'] == 6) {
                $status_str = "已驳回";
            }

            $message .= "\r\n最新代付订单详情\r\n---------------------------------------------\r\n";
            $message .= "系统订单号：`" . $info['orderid'] . "`\r\n";
            $message .= "外部订单号：`" . $info['out_trade_no'] . "`\r\n";
            $message .= "订单金额：" . $info['tkmoney'] . "\r\n";
            if ($info['sqdatetime'] != '') {
                $message .= "申请时间：" . $info['sqdatetime'] . "\r\n";
            }
            if ($info['cldatetime'] != '') {
                $message .= "处理时间：" . $info['cldatetime'] . "\r\n";
            }
            $message .= "订单状态：" . $status_str . "\r\n";
            $message .= "备注 ：" . $info['memo'] . "\r\n";
            $message .= "通知次数 ：" . $info['notifycount'] . "\r\n";
            $message .= "最后通知时间：" . date('Y-m-d H:i:s', $info['last_notify_time']) . "\r\n";
            $message .= "\r\n=========银行信息=========\r\n";
            $message .= "银行名称：" . $info['bankname'] . "\r\n";
            if($info['bankzhiname'] != '') {
                $message .= "支行名称：" . $info['bankzhiname'] . "\r\n";
            }
            $message .= "开户名 ：`" . $info['bankfullname'] . "`\r\n";
            $message .= "账号 ：`" . $info['banknumber'] . "`\r\n";

            if($info['status'] == 2 || $info['status'] == 3){
                $remark = 'https://' . C('DOMAIN') . '/Payment_Index_voucher.html?casOrdNo=' . $info['orderid'];
                $message .= "凭证地址 ：" . $remark . "\r\n";
            }
        } else {
            $message = "订单 " . ($orderid ?: '') . " 没有查询到代付订单，三种情况\r\n--------------------------------\r\n1.单号输入不正确，请确认单号；\r\n2.不是我们的订单，请确认收付款方是不是我们；\r\n3.订单没有提交到我们这里，请联系群里运营人员。";
        }
        return $message;
    }

    //构建代收消息内容
    public function buildDSMessage($order_info, $orderid = ''){
        if ($order_info && $order_info['status'] === 1) {
            $info = $order_info['info'];
            $message = '';

            $message .= "\r\n最新订单详情\r\n---------------------------------------------\r\n";
            $message .= "系统订单号：`" . $info['pay_orderid'] . "`\r\n";
            $message .= "外部订单号：`" . $info['out_trade_id'] . "`\r\n";
            $message .= "订单金额：" . $info['pay_amount'] . "\r\n";
            if ($info['pay_applydate'] != '') {
                $message .= "申请时间：" . date('Y-m-d H:i:s', $info['pay_applydate']) . "\r\n";
            }
            if ($info['pay_successdate'] != '0') {
                $message .= "成功时间：" . date('Y-m-d H:i:s', $info['pay_successdate']) . "\r\n";
            }
            if ($info['pay_status'] == 0) {
                $status_str = "未处理";
            } elseif ($info['pay_status'] == 1) {
                $status_str = "成功未回调";
            } elseif ($info['pay_status'] == 2) {
                $status_str = "成功已回调";
            }
            $message .= "订单状态：" . $status_str . "\r\n";

            //删除6小时前的记录
            M('TelegramApiOrder')->where(['create_time' => ['elt', (time() - 3600 * 72)]])->delete();
            // log_place_order($this->code . '_get_order', "sql", M('TelegramApiOrder')->getLastSql());    //日志
            // }
        } else {
            $message = "订单 " . ($orderid ?: '') . " 没有查询到订单，三种情况\r\n--------------------------------\r\n 1.单号输入不正确，请确认单号；\r\n 2.不是我们的订单，请确认收付款方是不是我们；\r\n 3.订单没有提交到我们这里，请联系群里运营人员。";
        }
        return $message;
    }

    //获取集团账号下的用户余额信息
    public function getGroup5User($uid,$chat_id, $message_id){
        $all_balance=0;
        $message='';
        $message .= "余额播报时间：" . date('Y-m-d H:i:s')  . "\r\n";
        $where=[];
        $where['parentid'] = $uid;
        $info = M('Member')->field('id, username, balance')->where($where)->select();
        if(!empty($info)){
            foreach($info as $v){
                $message .= "[" . $v['username'] . "]余额：" . $v['balance']  . "\r\n";
                $all_balance += $v['balance'];
            }
        }
        $message .= "合计余额：" . $all_balance  . "\r\n";
        return $this->sendMessage($chat_id, $message, $message_id);
    }


    //根据群ID 获取用户信息
    private function get_user($chat_id)
    {
        $info = [];
        if ($chat_id) {
            $where=[];
            $where['telegram_id'] = $chat_id;
            $info = M('Member')->field('id, username, balance, blockedbalance, df_api, status, df_ip, operators, currency, groupid')->where($where)->find();
            // log_place_order($this->code . '_get_user', "sql", M('Member')->getLastSql());    //日志
            if ($info) {
                $status = 1;
            } else {
                $status = 0;
            }
        } else {
            $status = 0;
        }
        $data = [
            'status' => $status,
            'info' => $info,
        ];
        // log_place_order($this->code . '_get_user', "返回", json_encode($data, JSON_UNESCAPED_UNICODE));    //日志
        return $data;
    }

    //获取订单信息
    private function get_order($orderid, $uid)
    {
        $info = [];
        if ($orderid && $uid) {
            $where=[];
            $map['pay_orderid']  = ['eq',$orderid];
            $map['out_trade_id']  = ['eq',$orderid];
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
            $where['pay_memberid'] = ['eq', $uid + 10000];

            $field = 'id,pay_memberid, pay_orderid, pay_amount, pay_poundage, pay_actualamount, pay_applydate ,pay_successdate, pay_bankname, pay_status, out_trade_id';
            $OrderModel = D('Order');
            $tables = $OrderModel->getTables();
            // log_place_order($this->code . '_get_order', "表", json_encode($tables, JSON_UNESCAPED_UNICODE));    //日志
            foreach ($tables as $v){
                $order = $OrderModel->table($v)
                    ->field($field)
                    ->where($where)
                    ->select();
                // log_place_order($this->code . '_get_order', "sql:".$v, $OrderModel->table($v)->getLastSql());    //日志
                if(!empty($order)) break;
            }
            $info = $order[0];
            // log_place_order($this->code . '_get_order', "sql", M('Order')->getLastSql());    //日志
            if ($info) {
                $status = 1;
            } else {
                $status = 0;
            }
        } else {
            $status = 0;
        }
        $data = [
            'status' => $status,
            'info' => $info,
        ];
        // log_place_order($this->code . '_get_order', "返回", json_encode($data, JSON_UNESCAPED_UNICODE));    //日志
        return $data;
    }

    //获取订单信息
    private function get_dforder($orderid, $uid)
    {
        $info = [];
        if ($orderid && $uid) {
            $where=[];
            $map['orderid']  = ['eq', $orderid];
            $map['out_trade_no']  = ['eq', $orderid];
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
            $where['userid'] =['eq', $uid];
            $field = 'id, userid, orderid, out_trade_no, tkmoney, sxfmoney, money, status, bankname, bankzhiname, banknumber, bankfullname, sqdatetime, cldatetime, memo, notifyurl, notifycount, last_notify_time,df_id, df_name, three_orderid';
            $Wttklist = D('Wttklist');
            $tables = $Wttklist->getTables();
            foreach ($tables as $v){
                $order = $Wttklist->table($v)
                    ->field($field)
                    ->where($where)
                    ->select();
                // log_place_order($this->code . '_get_dforder', "sql", $Wttklist->table($v)->getLastSql());    //日志
                if(!empty($order)) break;
            }
            $info = $order[0];
            if ($info) {
                $status = 1;
            } else {
                $status = 0;
            }
        } else {
            $status = 0;
        }
        $data = [
            'status' => $status,
            'info' => $info,
        ];
        // log_place_order($this->code . '_get_dforder', "返回", json_encode($data, JSON_UNESCAPED_UNICODE));    //日志
        return $data;
    }

    //用户当日统计
    private function statistics($uid){
        $data = [];
        $todayBegin = date('Y-m-d') . ' 00:00:00';
        $todyEnd = date('Y-m-d') . ' 23:59:59';
        $where=[];
        $where = [
            'pay_memberid' => $uid,
            'pay_applydate' => ['between', [strtotime($todayBegin), strtotime($todyEnd)]],
        ];
        //今日平台总入金
        $field = 'id, pay_amount, pay_status';
        $OrderModel = D('Order');
        $order_statistics = $OrderModel->getOrderByDateRange($field, $where);
        // $order_statistics = M('Order')->field('id, pay_amount, pay_status')->where($where)->select();
        $oi = $o_sum = $all_o_sum = $all_o_i = 0;
        foreach ($order_statistics as $ok => $ov) {
            $all_o_sum += $ov['pay_amount'];
            $all_o_i++;
            if($ov['pay_status']==1 || $ov['pay_status']==2){
                $o_sum += $ov['pay_amount'];
                $oi++;
            }
        }
        $data['all_o_sum'] = $all_o_sum;
        $data['all_o_num'] = $all_o_i;
        $data['o_sum'] = $o_sum;
        $data['o_num'] = $oi;
        $data['o_success_rate'] = sprintf("%.4f", $oi / $all_o_i) * 100;
        // if($data['o_success_rate'] > 10 && $data['o_success_rate'] < 90){
        //     $data['o_success_rate']+=5;
        // }

        $wi_do = $w_sum_do = $wi_done = $w_sum_done = $all_w_amount = $all_w_i = 0;
        $dfwhere = [
            'sqdatetime' => ['between', [$todayBegin, $todyEnd]],
            'userid' => $uid - 10000,
        ];
        $field = 'id, tkmoney, status';
        $Wttklist = D('Wttklist');

        $withdraw_statistics = $Wttklist->getOrderByDateRange($field, $dfwhere);
        foreach ($withdraw_statistics as $wk => $wv) {
            $all_w_amount += $wv['tkmoney'];
            $all_w_i++;
            if($wv['status'] == 1){
                $w_sum_do += $wv['tkmoney'];
                $wi_do++;
            }
            if($wv['status'] == 2 || $wv['status'] == 3){
                $w_sum_done += $wv['tkmoney'];
                $wi_done++;
            }
        }
        $data['all_w_amount'] = $all_w_amount;
        $data['all_w_i'] = $all_w_i;
        $data['w_sum_do'] = $w_sum_do;
        $data['wi_do'] = $wi_do;
        $data['w_sum_done'] = $w_sum_done;
        $data['wi_done'] = $wi_done;

        return $data;
    }


    /**
     * 用户代收订单每小时成功率统计
     * @param int $uid 用户ID（带10000前缀的商户号）
     * @return array 每小时统计数据
     */
    private function getHourlySuccessRate($uid){
        $data = [];
        $todayBegin = date('Y-m-d') . ' 00:00:00';
        $todyEnd = date('Y-m-d') . ' 23:59:59';

        // 获取今天的开始时间戳
        $todayStart = strtotime($todayBegin);

        // 初始化24小时数据
        for ($hour = 0; $hour < 24; $hour++) {
            $data[$hour] = [
                'hour' => $hour,
                'total_count' => 0,
                'success_count' => 0,
                'fail_count' => 0,
                'success_amount' => 0,
                'success_rate' => 0,
            ];
        }

        // 查询订单
        $where = [
            'pay_memberid' => $uid,
            'pay_applydate' => ['between', [$todayStart, strtotime($todyEnd)]],
        ];

        $OrderModel = D('Order');
        $orderList = $OrderModel->getOrderByDateRange('id, pay_amount, pay_status, pay_applydate', $where);

        // 按小时统计
        foreach ($orderList as $order) {
            $hour = date('H', $order['pay_applydate']); // 0-23
            $data[$hour]['total_count']++;

            if ($order['pay_status'] == 1 || $order['pay_status'] == 2) {
                $data[$hour]['success_count']++;
                $data[$hour]['success_amount'] += $order['pay_amount'];
            } else {
                $data[$hour]['fail_count']++;
            }
        }

        // 计算每小时成功率
        foreach ($data as $hour => &$item) {
            if ($item['total_count'] > 0) {
                $item['success_rate'] = sprintf("%.2f", ($item['success_count'] / $item['total_count']) * 100);
            }
            $item['success_amount'] = sprintf("%.2f", $item['success_amount']);
        }

        return $data;
    }

    /**
     * 格式化每小时成功率统计数据
     * @param int $uid 商户号
     * @return array 格式化后的统计数据
     */
    private function formatHourlySuccessRate($uid){
        $hourlyData = $this->getHourlySuccessRate($uid);

        $result = [
            'date' => date('Y-m-d'),
            'hours' => [],
            'summary' => [
                'total_count' => 0,
                'success_count' => 0,
                'fail_count' => 0,
                'total_amount' => 0,
                'success_rate' => 0,
            ],
        ];

        foreach ($hourlyData as $hour => $data) {
            $hourLabel = sprintf("%02d", $hour);
            $result['hours'][$hour] = [
                'label' => $hourLabel,
                'total_count' => $data['total_count'],
                'success_count' => $data['success_count'],
                'fail_count' => $data['fail_count'],
                'success_amount' => $data['success_amount'],
                'success_rate' => $data['success_rate'],
            ];

            // 累加汇总
            $result['summary']['total_count'] += $data['total_count'];
            $result['summary']['success_count'] += $data['success_count'];
            $result['summary']['fail_count'] += $data['fail_count'];
            $result['summary']['total_amount'] += $data['success_amount'];
        }

        // 计算总体成功率
        if ($result['summary']['total_count'] > 0) {
            $result['summary']['success_rate'] = sprintf("%.2f",
                ($result['summary']['success_count'] / $result['summary']['total_count']) * 100);
        }
        $result['summary']['total_amount'] = sprintf("%.2f", $result['summary']['total_amount']);

        return $result;
    }

    /**
     * 发送每小时成功率统计数据（详细版）
     * @param int $uid 商户号
     * @param int $chat_id 聊天ID
     * @param int $message_id 消息ID
     */
    private function sendHourlySuccessRate($uid, $chat_id, $message_id){
        $stats = $this->formatHourlySuccessRate($uid);

        $message = "";
        $message .= "代收实时数据\r\n\r\n";
        $message .= "小时 | 总笔数 | 成功笔数 | 成功金额 | 成功率\r\n";
        $message .= "---------------------------------------------\r\n";

        // 显示有数据的时段
        $hasData = false;
        foreach ($stats['hours'] as $hour => $data) {
            if ($data['total_count'] > 0) {
                $hasData = true;
                $message .= sprintf("%s | %d | %d | R$%s | %s%%\r\n",
                    str_pad($hour, 2, '0', STR_PAD_LEFT),
                    $data['total_count'],
                    $data['success_count'],
                    $data['success_amount'],
                    $data['success_rate']
                );
            }
        }

        if (!$hasData) {
            $message .= "今日暂无代收订单数据\r\n";
        }

        $message .= "---------------------------------------------\r\n";
        $message .= "查询时间：" . date('Y-m-d H:i:s') . "\r\n";

        $this->sendMessage($chat_id, $message, $message_id, 'Markdown');
    }

    /**
     * 发送每小时成功率统计数据（简化版，只显示非零数据）
     * @param int $uid 商户号
     * @param int $chat_id 聊天ID
     * @param int $message_id 消息ID
     */
    private function sendHourlySuccessRateSimple($uid, $chat_id, $message_id){
        $stats = $this->formatHourlySuccessRate($uid);

        $message = "";
        $message .= "代收统计 [" . $stats['date'] . "]\r\n\r\n";
        $message .= "小时 | 总笔数 | 成功笔数 | 成功率\r\n";
        $message .= "---------------------------------------------\r\n";

        // 显示有数据的时段
        $hasData = false;
        foreach ($stats['hours'] as $hour => $data) {
            if ($data['total_count'] > 0) {
                $hasData = true;
                $arrow = '';
                $rate = floatval($data['success_rate']);
                if ($rate >= 80) {
                    $arrow = '↑';
                } elseif ($rate >= 50) {
                    $arrow = '→';
                } else {
                    $arrow = '↓';
                }
                $message .= sprintf("%s %s | %d | %d | %s%%\r\n",
                    str_pad($hour, 2, '0', STR_PAD_LEFT),
                    $arrow,
                    $data['total_count'],
                    $data['success_count'],
                    $data['success_rate']
                );
            }
        }

        if (!$hasData) {
            $message .= "今日暂无订单数据\r\n";
        }

        $this->sendMessage($chat_id, $message, $message_id, 'Markdown');
    }

    private function sanitizeText($text, $parse_mode) {
        if ($parse_mode == 'Markdown') {
            // 确保Markdown标记成对出现
            $text = preg_replace('/(?<!\*)\*(?!\*)/', '', $text); // 移除单个*
            $text = preg_replace('/(?<!_)_(?!_)/', '', $text);   // 移除单个_
        } elseif ($parse_mode == 'HTML') {
            // 确保HTML标签闭合（简单示例，可能需要更复杂的处理）
            $text = strip_tags($text, '<b><i><u><code><a>'); // 只允许基本标签
        }
        return $text;
    }

    protected function redis_connect(){
        //创建一个redis对象
        $redis = new \Redis();
        //连接 Redis 服务
        $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
        //密码验证
        $redis->auth(C('REDIS_PWD'));
        return $redis;
    }
    protected function curlPost($url, $data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 验证 HTTPS 证书及主机名
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $file_contents = curl_exec($ch);
        //获取错误编码
        $curlErrno = curl_errno($ch);
        if($curlErrno){
            log_place_order($this->code . '_curlPostError', "curlcode", $curlErrno);    //日志
            log_place_order($this->code . '_curlPostError', "curlErrno", str_replace($this->token, '[REDACTED]', curl_error($ch)));    //日志
        }
        curl_close($ch);
        //这里解析
        return $file_contents;
    }
}