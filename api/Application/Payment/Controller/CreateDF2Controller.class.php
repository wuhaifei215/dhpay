<?php
/*
 * 代付API
 */
namespace Payment\Controller;

use Think\Controller;

class CreateDF2Controller extends Controller
{
    //商家信息
    protected $merchants;
    //网站地址
    protected $_site;
    //用户代付通道信息
    protected $userPayForAnother;
    //商户编码
    protected $memberid;

    public function __construct()
    {
        parent::__construct();
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        
        if (empty($_POST)) {
            $this->showmessage('no data!');
        }
        $this->redis = $this->redis_connect();
        
        $siteconfig_redis = $this->redis->get('siteconfig');
        $siteconfig = json_decode($siteconfig_redis,true);
        if(!$siteconfig_redis || empty($siteconfig)){
            $siteconfig = M("Websiteconfig")->find();
            $this->redis->set('siteconfig', json_encode($siteconfig, JSON_UNESCAPED_UNICODE));
            $this->redis->expire('siteconfig', 60);
        }
        if (!$siteconfig['df_api']) {
            $this->showmessage('代付API未开启！');
        }

        $this->memberid = I("request.mchid", 0, 'intval');
        if ($this->memberid == 0) {
            $this->showmessage('不存在的商户编号!');
        }
    }
    
    public function index(){
        $this->showmessage("请使用正确的接口地址！");
    }
    
    //菲律宾代付接口
    public function payoutBRL(){
        $this->channelIsOpen(); //判断通道是否开启
        if(getPaytypeCurrency($this->userPayForAnother['paytype']) !=='PHP'){
            $this->showmessage("国家货币类型不对！");
        }
        $this->doNext();
    }
    //越南代付接口
    // public function payoutINR(){
    //     $this->channelIsOpen(); //判断通道是否开启
    //     if(getPaytypeCurrency($this->userPayForAnother['paytype']) !=='INR'){
    //         $this->showmessage("国家货币类型不对！");
    //     }
    //     $this->doNext();
    // }
    
    /**
     * [channelIsOpen 判断通道是否开启]
     * @return [type] [description]
     */
    protected function channelIsOpen()
    {
        //通道编码
        $this->bankcode = I('request.bankcode', 0, 'intval');
        if ($this->bankcode == 0) {
            $this->showmessage('代付通道编码不能为空！');
        }
        $userid = $this->memberid - 10000;
        $userPayForAnother_redis = $this->redis->get('UserPayForAnother_'. $this->bankcode . '_' . $userid);
        $userPayForAnother = json_decode($userPayForAnother_redis,true);
        if(!$userPayForAnother_redis || empty($userPayForAnother)){
            $userPayForAnother = M('UserPayForAnother')->where(['pid' => $this->bankcode, 'userid' => $userid, 'status' => 1])->find();
            $this->redis->set('UserPayForAnother_'. $this->bankcode . '_' . $userid, json_encode($userPayForAnother, JSON_UNESCAPED_UNICODE));
            $this->redis->expire('UserPayForAnother_'. $this->bankcode . '_' . $userid, 60);
        }
        $this->userPayForAnother = $userPayForAnother;
        //用户未分配
        if (!$this->userPayForAnother) {
            $this->showmessage('该代付通道已关闭!');
        }
        
        //进入支付
        if ($this->userPayForAnother['channel']) {
            $info_redis = $this->redis->get('PayForAnother_'. $this->userPayForAnother['channel']);
            $info = json_decode($info_redis,true);
            if(!$info_redis || empty($info)){
                $info = M('PayForAnother')->where(['id' => $this->userPayForAnother['channel'], 'status' => 1])->find();
                $this->redis->set('PayForAnother_'. $this->userPayForAnother['channel'], json_encode($info, JSON_UNESCAPED_UNICODE));
                $this->redis->expire('PayForAnother_'. $this->userPayForAnother['channel'] , 60);
            }
            //是否存在通道文件
            if (!is_file(APP_PATH . 'Payment/Controller/' . $info['code'] . 'Controller.class.php')) {
                $this->showmessage('代付通道不存在', ['pay_bankcode' => $this->userPayForAnother['pid']]);
            }
            $this->userPayForAnother['paytype'] = $info['paytype'];
        } else {
            $this->showmessage("抱歉......服务器飞去月球了");
        }
    }

    /**
     * 创建代付申请
     * @param $parameter
     * @return array
     */
    protected function doNext()
    {
        //  PaymentLogs( 'DFpay_add',json_encode($_POST) );
        $out_trade_no = I("request.out_trade_no", '', 'string,strip_tags,htmlspecialchars');
        if (!$out_trade_no) {
            $this->showmessage('订单号不能为空！');
        }
        if($this->redis->get($out_trade_no)){
            $this->showmessage('重复订单号！');
        }
        
        $sign = I('request.pay_md5sign', '', 'string,strip_tags,htmlspecialchars');
        if (!$sign) {
            $this->showmessage("签名参数不能为空！");
        }

        
        $user_id = $this->memberid - 10000;
        
        
//        PaymentLogs( 'DFpay_add_server', $this->memberid.':'.json_encode($_SERVER) );
        //用户信息
        $this->merchants = D('Member')->where(array('id' => $user_id))->find();
        if (empty($this->merchants)) {
            $this->showmessage('商户不存在！');
        }
        if (!$this->merchants['df_api']) {
            $this->showmessage('商户未开启此功能！');
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = get_client_ip();
        }
        $referer = getHttpReferer();//请求来源URL

        if ($this->merchants['df_domain'] != '') {
            if (!checkDfDomain($referer, $this->merchants['df_domain'])) {
                $this->showmessage('请求来源域名与报备域名不一致！');
            }
        }
        if ($this->merchants['df_ip'] == '') {
            $this->showmessage('提交IP地址未报备！');
        } elseif ($this->merchants['df_ip'] != '') {
            if (!checkDfIp($ip, $this->merchants['df_ip'])) {
                
                PaymentLogs( 'DFpay_add_server', $user_id.':'.$ip.'=='. $this->merchants['df_ip']);
                $this->showmessage('IP地址与报备IP不一致！');
            }
        }
        
        $type = I("request.type", '');
        $bankname = I("request.bankname", '', 'string,strip_tags,htmlspecialchars');
        if ($type==2 && !$bankname) {
            $this->showmessage('银行名称不能为空！');
        }
        $ifsc = I("request.subbranch", '', 'string,strip_tags,htmlspecialchars');
        // if (!$ifsc) {
        //     $this->showmessage('银行IFSC不能为空！');
        // }

        //结算方式：
        $Tikuanconfig = M('Tikuanconfig');
        $defaultConfig = $Tikuanconfig->where(['issystem' => 1, 'tkzt' => 1])->find();      //平台规则
        //判断是否开启提款设置
        if (!$defaultConfig) {
            return ['status' => 0, 'msg' => '提款已关闭！'];
        }
        
        $tkConfig = $Tikuanconfig->where(['userid' => $user_id, 'tkzt' => 1])->find();       //个人规则
        
        $channel = M('pay_for_another')->alias('as A')->field('A.*')
        ->join('LEFT JOIN `pay_user_pay_for_another` AS B ON A.id = B.channel')
        ->where(['B.userid' => $user_id,'B.pid' => $this->bankcode])->find();
        
        $channelConfig = $Tikuanconfig->where(['channel'=> $channel['id'] , 'userid' => 1 , 'tkzt' => 1])->find();       //通道规则
        
        //判断是否设置个人规则
        if ($tkConfig && $tkConfig['tkzt'] == 1 && $tkConfig['systemxz'] == 1) {
            //个人规则，但是提现时间规则要按照系统规则
            $tkConfig['allowstart'] = $defaultConfig['allowstart'];
            $tkConfig['allowend'] = $defaultConfig['allowend'];
        }elseif ($channelConfig && $channelConfig['tkzt'] == 1 && $channelConfig['systemxz'] == 2) {
            $tkConfig = $channelConfig;
            //通道规则，但是提现时间规则要按照系统规则
            $tkConfig['allowstart'] = $defaultConfig['allowstart'];
            $tkConfig['allowend'] = $defaultConfig['allowend'];
        } else {
            $tkConfig = $defaultConfig;
        }
        //是否在许可的提现时间
        $hour = date('H');
        //判断提现时间是否合法
        if ($tkConfig['allowend'] != 0) {
            if ($tkConfig['allowstart'] > $hour || $tkConfig['allowend'] <= $hour) {
                $this->showmessage('不在提现时间，请换个时间再来!');
            }
        }
        $money = I("request.money", 0, 'intval');
        if (!is_numeric($money) || $money <= 0) {
            $this->showmessage('金额错误！');
        }
        //单笔最小提款金额
        if ($tkConfig['tkzxmoney'] > $money) {
            $this->showmessage('单笔最低提款额度：' . $tkConfig['tkzxmoney']);
        }
        //单笔最大提款金额
        if ($tkConfig['tkzdmoney'] < $money) {
            $this->showmessage('单笔最大提款额度：' . $tkConfig['tkzdmoney']);
        }

        $accountname = I("request.accountname", '', 'string,strip_tags,htmlspecialchars');
        if (!$accountname) {
            $this->showmessage('开户名不能为空！');
        }
        $cardnumber = I("request.cardnumber", '', 'string,strip_tags,htmlspecialchars');
        if (!$cardnumber) {
            $this->showmessage('银行卡号不能为空！');
        }
        
        $notifyurl = I("request.notifyurl", '');
        if (!$notifyurl) {
            $this->showmessage('回调地址不能为空！');
        }

        $Wttklist = D('Wttklist');
        $where = [
            'userid' => $user_id,
            'out_trade_no' => $out_trade_no,
            'sqdatetime'=>['between',[date('Y-m-d',time()-86399) . ' 00:00:00', date('Y-m-d',time()) . ' 23:59:59']],
        ];
        
        $where['sqdatetime'] = ['between', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59']];
        //判断是否超过当天次数
        $wttkNum = $Wttklist->getCount($where);
        $dayzdnum = $wttkNum + 1;
        if ($dayzdnum >= $tkConfig['dayzdnum']) {
            $this->showmessage('超出商户当日提款次数！');
        }
        //判断提款额度
        $dayzdmoney = $Wttklist->getSum('tkmoney',$where)['tkmoney'];
        if ($dayzdmoney >= $tkConfig['dayzdmoney']) {
            $this->showmessage('超出商户当日提款额度！');
        }
        if ($money < $tkConfig['tkzxmoney'] || $money > $tkConfig['tkzdmoney']) {
            $this->showmessage('提款金额不符合提款额度要求!');
        }
        $dayzdmoney = bcadd($money, $dayzdmoney, 4);
        if ($dayzdmoney >= $tkConfig['dayzdmoney']) {
            $this->showmessage('超出当日提款额度!');
        }
        //计算手续费
//       $sxfmoney = $tkConfig['tktype'] ? $tkConfig['sxffixed'] : bcdiv(bcmul($data['money'], $tkConfig['sxfrate'], 4), 100, 4);

        if ($tkConfig['tktype'] == 1) { //按比例计算
            $sxfmoney = $tkConfig['sxffixed'];
        } elseif ($tkConfig['tktype'] == 2) {   //按单笔加比例计算
            $sxfmoney = $tkConfig['sxffixed'] + bcdiv(bcmul($money, $tkConfig['sxfrate'], 4), 100, 4);
        } else {    //按单笔计算
            $sxfmoney = bcdiv(bcmul($money, $tkConfig['sxfrate'], 4), 100, 4);
        }

        if ($tkConfig['tk_charge_type']) {
            //实际提现的金额
            $money2 = $money;
        } else {
            //实际提现的金额
            $money2 = bcsub($money, $sxfmoney, 4);
        }
        
        $extends = I("request.extends", '');
        if (!$extends) {
            $this->showmessage('备注不能为空！');
        }
        //验签
        if ($this->verify($_POST)) {
            M()->startTrans();
            
            $info = M('Member')->where(['id' => $user_id])->lock(true)->find();
            if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='PHP'){        //菲律宾余额
                $balance = $info['balance_php'];
            }
            if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='INR'){        //菲律宾余额
                $balance = $info['balance_inr'];
            }
            
            if ($balance < $money2) {
                $this->showmessage('金额错误，可用余额不足!');
            }
            $time = date("Y-m-d H:i:s");
            $orderid = $this->getOrderId();
            
            //$cost = $channel['rate_type'] ? bcmul($data['money'], $channel['cost_rate'], 4) : $channel['cost_rate'];
            //计算成本
            if ($channel['rate_type'] == 1) { //按比例
                $cost = round($data['money']*($channel['cost_rate']/100),4);
            } elseif($channel['rate_type'] == 2) { //按单笔加比例计算
                $cost = $channel['dan_bi'] + bcdiv(bcmul($data['money'], $channel['cost_rate'], 4), 100, 4);
            } else { //按单笔
                $cost =  $channel['dan_bi'];
            }
            $wttkData = [
                "userid" => $user_id,
                'orderid' => $orderid,
                "out_trade_no" => $out_trade_no,
                "bankname" => trim($bankname),
                "bankzhiname" => trim($ifsc),
                "banknumber" => trim($cardnumber),
                "bankfullname" => trim($accountname),
                "sqdatetime" => $time,
                "status" => 0,
                'tkmoney' => $money,
                'sxfmoney' => $sxfmoney,
                "money" => $money2,
                'paytype' => $this->userPayForAnother['paytype'],
                "notifyurl" =>  $notifyurl,
                "type" => $type,
                "extends" => $extends,
                "bankcode" =>$this->bankcode,
                
                "df_charge_type" => $tkConfig['tk_charge_type'],
                'df_id' => $channel['id'],
                'code' => $channel['code'],
                'df_name' => $channel['title'],
                'channel_mch_id' => $channel['mch_id'],
                'cost_rate' => $channel['cost_rate'],
                'cost' => $cost,
                'rate_type' => $channel['rate_type'],
            ];
            
            $tkmoney = abs(floatval($money));
            $ymoney = $balance;
            $balance = bcsub($balance, $tkmoney, 4);
            $mcData = [
                "userid" => $user_id,
                'ymoney' => $ymoney,
                "money" => $money,
                'gmoney' => $balance,
                "datetime" => $time,
                "transid" => $orderid,
                "orderid" => $out_trade_no,
                'paytype' => $this->userPayForAnother['paytype'],
                "lx" => 10,
                'contentstr' => date("Y-m-d H:i:s") . '委托提现操作',
            ];
            $Member = M('Member');
            if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='PHP'){        //菲律宾余额
                $member_data['balance_php'] = ['exp', 'balance_php-' . $money]; //防止数据库并发脏读
            }
            if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='INR'){        //越南余额
                $member_data['balance_inr'] = ['exp', 'balance_inr-' . $money]; //防止数据库并发脏读
            }
            $res1 = $Member->where(['id' => $user_id])->save($member_data);
            
            //获取数据表名称
            $Wttklist = D('Wttklist');
            $table = $Wttklist->getRealTableName($time);
            // var_dump($table);die;
            $res2 = $Wttklist->table($table)->add($wttkData);
            $Moneychange = D('Moneychange');
            $Moneychangetable = $Moneychange->getRealTableName($time);
            $res3 = $Moneychange->table($Moneychangetable)->add($mcData);

            if ($tkConfig['tk_charge_type'] && $sxfmoney > 0) {
                $balance = bcsub($balance, $sxfmoney, 4);
                if ($balance < 0) {
                    M()->rollback();
                    $this->showmessage('余额不足以扣除手续费！');
                }
                $chargeData = [
                    "userid" => $user_id,
                    'ymoney' => $ymoney - $money,
                    "money" => $sxfmoney,
                    'gmoney' => $balance,
                    "datetime" => $time,
                    "transid" => $orderid,
                    "orderid" => $out_trade_no,
                    'paytype' => $this->userPayForAnother['paytype'],
                    "lx" => 14,
                    'contentstr' => date("Y-m-d H:i:s") . '委托提现扣除手续费',
                ];
                if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='PHP'){        //菲律宾余额
                    $member_sxf_data['balance_php'] = ['exp', 'balance_php-' . $sxfmoney]; //防止数据库并发脏读
                }
                if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='INR'){        //越南余额
                    $member_sxf_data['balance_inr'] = ['exp', 'balance_inr-' . $sxfmoney]; //防止数据库并发脏读
                }
                $Member->where(['id' => $user_id])->save($member_sxf_data);
                $Moneychange->table($Moneychangetable)->add($chargeData);
            }
            
            /**************************************2019年06月06日 增加的代付代理资金变动*************************************************/
//            //上级代理
//            $parentid = $Member->where(["id" => $user_id])->getField("parentid");
//            //如果存在上级代理的情况
//            if ($parentid > 1) {
//                $dl_rate = $Tikuanconfig->where(['userid' => $parentid])->getField("sxfrate");
//                $dl_sxfrate = abs(bcsub($dl_rate, $tkConfig['sxfrate'], 4));
//                $dl_info = $Member->where(['id' => $parentid])->lock(true)->find();
//                $dl_ymoney = $dl_info['balance'];
//                $dlyj_money = bcdiv(bcmul($money, $dl_sxfrate, 4), 100, 4);
//                $dl_balance = bcadd($dl_ymoney, $dlyj_money, 4);
//                $dl_mcData = [
//                    "userid" => $parentid,
//                    'ymoney' => $dl_ymoney,
//                    "money" => $dlyj_money,
//                    'gmoney' => $dl_balance,
//                    "datetime" => $time,
//                    "transid" => $orderid,
//                    "orderid" => $out_trade_no,
//                    "lx" => 6,
//                    'contentstr' => date("Y-m-d H:i:s") . '下级商户代付提现佣金',
//                ];
//            }
            /**************************************2019年06月06日 增加的代付代理资金变动*************************************************/
            
            if ($res1 && $res2 && $res3) {
                
                //代付代理佣金变动
//                if($parentid > 1){
                    // if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='PHP'){        //菲律宾余额
                    //     $dl_member_data['balance_php'] = ['exp', 'balance_php+' . $dl_balance]; //防止数据库并发脏读
                    // }
                    // if(getPaytypeCurrency($this->userPayForAnother['paytype']) ==='INR'){        //越南余额
                    //     $dl_member_data['balance_inr'] = ['exp', 'balance_inr+' . $dl_balance]; //防止数据库并发脏读
                    // }
                    // $res6 = $Member->where(['id' => $parentid])->save($dl_member_data);
                    // $res7 = M('Moneychange')->add($dl_mcData);
//                }
                M()->commit();
                
                //订单信息存入缓存
                // $this->redis->set($orderid,json_encode($wttkData),3600 * 2);
                $this->redis->set($out_trade_no,$orderid,3600 * 2);
            }else{
                M()->rollback();
                $this->showmessage('系统错误');
            }

            /**************************************2024年09月10日 自动向上游提交*************************************************/
            sleep(1);
                // log_place_order( 'DFadd_NoSubmit', $orderid,   'id' . $res2);    //日志
            if($res2){
                if($user_id == 3){
                    $message = '';
                    $message .= "\r\n代付风控提示\r\n\r\n";
                    $message .= "系统订单号：`" . $wttkData['orderid'] . "`\r\n";
                    $message .= "外部订单号：`" . $wttkData['out_trade_no'] . "`\r\n";
                    $message .= "订单金额：" . $wttkData['tkmoney'] . "\r\n";
                    // $message .= "银行名称：" . $wttkData['bankname'] . "\r\n";
                    $message .= "开户名 ：" . $wttkData['bankfullname'] . "\r\n";
                    $message .= "账号 ：" . $wttkData['banknumber'] . "\r\n";
                    $message .= "申请时间：" . $wttkData['sqdatetime'] . "\r\n";
                    
                    $btn['text'] = '驳回(reject)';
                    $btn['callback_data'] = 'reject '.$wttkData['orderid'];
                    $btn2['text'] = '通过(pass)';
                    $btn2['callback_data'] = 'pass '.$wttkData['orderid'];
                    $markup = [
                        'inline_keyboard'=>[[
                            $btn,
                            $btn2
                        ]],
                     ];
    
                    $member_list = M('Member')->field('telegram_id')->where(['id'=>$user_id])->find();
                    $result = R('Telegram/Api2/send', [$member_list['telegram_id'], $message, '', 'Markdown', $markup]);
                    
                }elseif($wttkData['money'] < 5000){
                    $id = $res2;
    
                    //加锁防止重复提交
                    $lock_res = $Wttklist->table($table)->where(['id'=>$id, 'df_lock'=>0])->save(['df_lock' => 1, 'lock_time' => time()]);
                    if(!$lock_res) {
        //                return ['status' => 0, 'msg' => '系统出错，请您联系管理员处理!'];
                    }
                    try {
                        $wttkData['money'] = round($wttkData['money'],2);
                        $result = R('Payment/' . $channel['code'] . '/PaymentExec', [$wttkData, $channel]);
                        if (FALSE === $result) {
                            $Wttklist->table($table)->where(['id' => $id])->save(['last_submit_time' => time(), 'auto_submit_try' => ['exp', 'auto_submit_try+1'], 'df_lock' => 0]);
                        } else {
                            if (is_array($result)) {
                                $data = [
                                    'memo' => $result['msg'],
                                ];
                                // if($user_id==2){
                                    $this->changeStatus($id, $result['status'], $data, $table);
                                // }else{
                                //     $this->handle($id, $result['status'], $data, $table, 0);    //处理订单状态，不异步回调
                                // }
                                $Wttklist->table($table)->where(['id' => $id])->save(['is_auto' => 1, 'last_submit_time' => time(), 'auto_submit_try' => ['exp', 'auto_submit_try+1'], 'df_lock' => 0]);
                            }
                        }
                        if($result['status'] === 3){
                            $this->showmessage($result['msg']);
                        }
                    } catch (\Exception $e) {
                        $Wttklist->table($table)->where(['id' => $id])->setField('df_lock', 0);
                    }
                }else{
                    
                }
            }else{
                // log_place_order( 'DFadd_NoSubmit', $orderid,   '表' . $table);    //日志
                // log_place_order( 'DFadd_NoSubmit', $orderid,   $Wttklist->table($table)->getLastSql());    //日志
                // log_place_order( 'DFadd_NoSubmit', $orderid,   '未找到订单信息，没有向上提交');    //日志
            }
            /**************************************2024年09月10日 自动向上游提交*************************************************/

            header('Content-Type:application/json; charset=utf-8');
            $data = array('status' => 'success', 'msg' => '代付申请成功', 'transaction_id' => $orderid);
            echo json_encode($data);
            exit;
        } else {
            ksort($_POST);
            $md5str = "";
            foreach ($_POST as $key => $val) {
                if (!empty($val) && $key != 'pay_md5sign') {
                    $md5str = $md5str . $key . "=" . $val . "&";
                }
            }
            $sign = strtoupper(md5($md5str . "key=" . $this->merchants['apikey']));
            $result = [
                '提醒' => '请比对拼接顺序及签名',
                'POST数据' => $_POST,
                '拼接顺序' => substr($md5str, 0, strlen($md5str) - 1),
                '签名' => $sign,
            ];
            $this->showmessage('签名验证失败', $result);
        }
    }

    /**
     * 获得订单号
     *
     * @return string
     */
    public function getOrderId()
    {
        $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $i = intval(date('Y')) - 2010;

        return $year_code[$i] . date('YmdHis') . substr(time(), -5) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT) . rand(1000,9999);
    }

    /**
     *  验证签名
     * @return bool
     */
    protected function verify($param)
    {
        $md5key = $this->merchants['apikey'];
        $md5keysignstr = $this->createSign($md5key, $param);
        $pay_md5sign = I('request.pay_md5sign');
        if ($pay_md5sign == $md5keysignstr) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    protected function createSign($Md5key, $list)
    {
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val) && $key != 'pay_md5sign') {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
    }

    /**
     * 错误返回
     * @param string $msg
     * @param array $fields
     */
    protected function showmessage($msg = '', $fields = array())
    {
        // PaymentLogs( 'DFpay_add', $msg.':'.json_encode($fields,JSON_UNESCAPED_UNICODE) );
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => 'error', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }
    
    protected function changeStatus($id, $status = 1, $return,$table='Wttklist'){
        if($this->redis->get('changestatus' . $status . $id)){
            return;
        }
        $this->redis->set('changestatus' . $status . $id,'1',120);
	    //处理成功返回的数据
        $Wttklist = D('Wttklist');
        $withdraw = $Wttklist->table($table)->where(['id' => $id])->find();
        $memo = $withdraw['memo'];
        $data = array();
		switch($status){
			case 1:
				//提交代付成功
			   $data['status'] = 1;
			   $data['memo']  = '申请成功！ - '. $return['memo']. date('Y-m-d H:i:s').'<br>'.$memo;
			   break;
			case 2:
				 //支付成功
			   $data['status'] = 2;
			   $data['cldatetime'] = date('Y-m-d H:i:s', time());
			   $data['memo']  = '代付成功！ - '. $return['memo']. date('Y-m-d H:i:s').'<br>'.$memo;
			   break;
			case 3:
                    // log_place_order( 'DFadd_error', $withdraw['orderid'],  json_encode($withdraw, JSON_UNESCAPED_UNICODE));    //日志
			     try {
			        $contentstr = '申请失败';
                    M()->startTrans();
    				 //各种失败未返回 并退回金额
                    $Member     = M('Member');
                    $memberInfo = $Member->where(['id' => $withdraw['userid']])->lock(true)->find();
                    if(getPaytypeCurrency($withdraw['paytype']) ==='PHP'){        //菲律宾余额
                        $res1 = $Member->where(['id' => $withdraw['userid']])->save(['balance_php' => array('exp', "balance_php+{$withdraw['tkmoney']}")]);
                        $ymoney = $memberInfo['balance_php'];
                    }
                    if(getPaytypeCurrency($withdraw['paytype']) ==='INR'){        //菲律宾余额
                        $res1 = $Member->where(['id' => $withdraw['userid']])->save(['balance_inr' => array('exp', "balance_inr+{$withdraw['tkmoney']}")]);
                        $ymoney = $memberInfo['balance_inr'];
                    }
                    //2,记录流水订单号
                    $arrayField = array(
                        "userid"     => $withdraw['userid'],
                        "ymoney"     => $ymoney,
                        "money"      => $withdraw['tkmoney'],
                        "gmoney"     => $ymoney + $withdraw['tkmoney'],
                        "datetime"   => date("Y-m-d H:i:s"),
                        "tongdao"    => 0,
                        "transid"    => $withdraw['orderid'],
                        "orderid"    => $withdraw['out_trade_no'],
                        "paytype"    => $withdraw['paytype'],
                        "lx"         => 18,
                        'contentstr' => $contentstr.': '.$withdraw['orderid'],
                    );
                    $Moneychange = D("Moneychange");
                    $tablename = $Moneychange -> getRealTableName($arrayField['datetime']);
                    $res2 = $Moneychange->table($tablename)->add($arrayField);
                    // log_place_order( 'DFadd_error', $withdraw['orderid'],   $Moneychange->table($tablename)->getLastSql());    //日志
                    $sxf_re = true;
                    //代付驳回退回手续费
                    if ($withdraw['df_charge_type'] && $withdraw['sxfmoney']>0) {
                        if(getPaytypeCurrency($withdraw['paytype']) ==='PHP'){        //菲律宾余额
                            $res3 = $Member->where(['id' => $withdraw['userid']])->save(['balance_php' => array('exp', "balance_php+{$withdraw['sxfmoney']}")]);
                        }
                        if(getPaytypeCurrency($withdraw['paytype']) ==='INR'){        //菲律宾余额
                            $res3 = $Member->where(['id' => $withdraw['userid']])->save(['balance_inr' => array('exp', "balance_inr+{$withdraw['sxfmoney']}")]);
                        }
                        if (!$res3) {
                            $sxf_re = false;
                        }
                        $chargeField = array(
                            "userid"     => $withdraw['userid'],
                            "ymoney"     => $ymoney + $withdraw['tkmoney'],
                            "money"      => $withdraw['sxfmoney'],
                            "gmoney"     => $ymoney + $withdraw['tkmoney'] + $withdraw['sxfmoney'],
                            "datetime"   => date("Y-m-d H:i:s"),
                            "tongdao"    => 0,
                            "transid"    => $withdraw['orderid'],
                            "orderid"    => $withdraw['out_trade_no'],
                            "paytype"    => $withdraw['paytype'],
                            "lx"         => 19,
                            'contentstr' => $contentstr.' 结算退回手续费',
                        );
                        $res4 = $Moneychange->table($tablename)->add($chargeField);
                        // $res = M('Moneychange')->add($chargeField);
                        if (!$res4) {
                            $sxf_re = false;
                        }
                    }
    				$message = isset($return['memo'])?$return['memo']:'代付失败！';
    				$message = $message .' - '.date('Y-m-d H:i:s').'<br>'.$memo;
    				
    			    $data['status'] = 4;
    			    $data['cldatetime'] = date('Y-m-d H:i:s', time());
    			    $data['memo']  = $message;
    			 //   log_place_order( 'DFadd_error', $withdraw['orderid'], '$res1:' . $res1);    //日志
    			 //   log_place_order( 'DFadd_error', $withdraw['orderid'], '$res2:' . $res2);    //日志
    			 //   log_place_order( 'DFadd_error', $withdraw['orderid'], '$sxf_re:' . $sxf_re);    //日志
    			    
                    if($res1 && $res2 && $sxf_re === true){
                    // log_place_order( 'DFadd_error', $withdraw['orderid'],   11111);    //日志
            		    M()->commit();
            		}else{
                        log_place_order( 'DFadd_error', $withdraw['orderid'],   '数据回滚了');    //日志
            		    M()->rollback();
            		}
			    } catch (\Exception $e) {
                    log_place_order( 'DFadd_error', $withdraw['orderid'] . '$e',  $e);    //日志
                }
			    break;
			default:
				 //订单状态不改变
				 $sta = $Wttklist->table($table)->where(['id' => $id])->getField('status');
				 $data['status'] = $sta;
				 $data['memo']  = '状态不改变！ - '. $return['memo']. date('Y-m-d H:i:s').'<br>'.$memo;
				 break;
		}
        $where = ['id'=>$id, 'status'=>['in', '0,1']];
        $ad = $Wttklist->table($table)->where($where)->save($data);
        if($status == 2 || $status == 3){
            $withdraw['status'] = $data['status'];
            $this->redis->set($withdraw['orderid'],json_encode($withdraw),3600 * 2);
            $this->redis->rPush('notifyList_DF', $withdraw['orderid']);
            Automatic_Notify($withdraw['orderid']);
        }
        return;
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
}