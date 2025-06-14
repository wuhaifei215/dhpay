<?php
// +----------------------------------------------------------------------
// | 订单模型
// +----------------------------------------------------------------------
namespace Common\Model;
use Think\Model;

class OrderModel extends Model {
    protected $expire_date = 20241124;
    protected $tablePrefix = 'pay_'; // 分表前缀
    protected $tableName = 'order'; // 默认表名
    protected $orderTables=[];      //订单分表表名
    protected $timeoptions = ['pay_applydate','pay_successdate'];
    // 数据库表达式
    protected $exp = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN', 'not between' => 'NOT BETWEEN', 'notbetween' => 'NOT BETWEEN');
    
    public function __construct()
    {
        parent::__construct();
        //获取所有订单分表表名
        $orderTable_arr = $this->query("SHOW TABLES LIKE '". $this->tablePrefix . $this->tableName ."%'");
        $orderTable_array=[];
        foreach($orderTable_arr as $v){
            foreach ($v as $vv){
                $orderTable_array[] = $vv;
            }
        }
        krsort($orderTable_array);
        $this->orderTables = $orderTable_array;
        $this->createTable();
        // $this->setRealTableName();
    }
    protected function createTable(){
        $tableName = $this->getRealTableName(date('Ymd',time()));
        $isTable = $this->query("SHOW TABLES LIKE '". $tableName ."'");
        if(!$isTable){
            $lastId_array = $this->getLastIds();
            array_push($this->orderTables,$tableName);
            if($lastId_array){
                $maxValue = max($lastId_array);
                $newId = ($maxValue+1);
            }else{
                $newId = 1;
            }
            $creatSql = "CREATE TABLE `" . $tableName . "` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
                  `pay_orderid` varchar(100) NOT NULL COMMENT '系统订单号',
                  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
                  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
                  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
                  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
                  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
                  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
                  `pay_bankname` varchar(300) DEFAULT NULL,
                  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
                  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
                  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
                  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
                  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
                  `pay_tongdao` varchar(50) DEFAULT NULL,
                  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
                  `pay_tjurl` varchar(1000) DEFAULT NULL,
                  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
                  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
                  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
                  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
                  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
                  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
                  `ddlx` int(11) DEFAULT '0',
                  `pay_ytongdao` varchar(50) DEFAULT NULL,
                  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
                  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
                  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
                  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
                  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
                  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
                  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
                  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
                  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
                  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
                  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
                  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
                  `in_accountname` varchar(255) DEFAULT NULL,
                  `in_cardnumber` varchar(255) DEFAULT NULL,
                  `in_bankname` varchar(255) DEFAULT NULL,
                  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
                  `currency` varchar(30) NOT NULL COMMENT '国家货币',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
                  KEY `account_id` (`account_id`) USING BTREE,
                  KEY `channel_id` (`channel_id`) USING BTREE,
                  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
                  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
                  KEY `pay_status` (`pay_status`) USING BTREE,
                  KEY `applydate` (`pay_applydate`) USING BTREE,
                  KEY `successdate` (`pay_successdate`) USING BTREE,
                  KEY `lock_status` (`lock_status`) USING BTREE,
                  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
                ) ENGINE=InnoDB AUTO_INCREMENT=" . $newId . " DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;";
            $this->execute($creatSql);
        }
    }
        
    public function getTables($data=array()){
        if(!empty($data) && $data['pay_applydate']){
            //获取时间范围内的表
            list($startdate, $enddate) = $this->new_parseDate($data);
            $data_arr = $this->getDateRange($startdate, $enddate);
            foreach ($data_arr as $date) {
                $realTableName = $this->getRealTableName($date);
                if(in_array($realTableName,$this->orderTables)){
                    $tables[] = $realTableName;
                }
            }
        }else{
            $tables = $this->orderTables;
        }
        return $tables;
    }
    
    public function getLastIds($returnAllArray = false){
        foreach ($this->orderTables as $k => $v){
            $lastIds = $this->query("SELECT id FROM " . $v . ' ORDER BY id DESC LIMIT 1');
            $lastId_array[] = $lastIds[0]['id']+0;
            $lastAllArray[$k]['table']=$v;
            $lastAllArray[$k]['id']=$lastIds[0]['id']+0;
        }
        if(!$returnAllArray){
            return $lastId_array;
        }else{
            return $lastAllArray;
        }
    }
 
    // 获取实际表名的方法
    public function getRealTableName($date) {
        if(date('Ymd', strtotime($date)) < $this->expire_date){
            $date_md = '';
        }else{
            $date_md = date('md', strtotime($date));
        }
        return $this->tablePrefix . $this->tableName . $date_md;
    }

    
    // 获取实际表名的方法
    public function setRealTableName($date =array()) {
        $date = isset($data['pay_applydate']) ? $data['pay_applydate'] : date('Ymd',time());
        $realTableName = $this->getRealTableName($date);
        // 设置当前模型对应的数据表
        $this->table($realTableName);
        return $this;
    }
    
    public function getCount($options=array()){
        $field = 'count(id) as tp_count';
        $count_arr = $this->getOrderByDateRange($field, $options);
        $count = 0;
        foreach ($count_arr as $ck => $cv){
            $count+=$cv['tp_count'];
        }
        return $count;
    }
    
    public function getSum($field='', $options=array()){
        $field_arr = explode(',' , $field);
        $field_str='';
        foreach ($field_arr as $v){
            $field_str .= 'SUM(`' . $v . '`) ' . $v . ',';
        }
        $field_str = rtrim($field_str, ','); // 移除最后一个
        $sum_arr=[];
        $sum_arr = $this->getOrderByDateRange($field_str, $options);
        $sum=[];
        foreach ($sum_arr as $sk => $sv){
            foreach ($sv as $skk => $svv){
                $sum[$skk] += $svv;
            }
        }
        return $sum;
    }
    
    // 按时间范围查询数据表
    public function getOrderByDateRange($field=array(), $options=array(), $limit='', $orderby='' , $groupby='') {
        if(!$field){
            $field = '*';
        }else{
            $field = $this->new_parseField($field);
        }
        if($orderby){
            $orderby = ' ORDER BY ' . $orderby;
        }
        if($groupby){
            $groupby = ' GROUP BY ' . $groupby;
        }
        if($limit){
            $limit = ' LIMIT ' . $limit;
        }
        
        $where = '';
        $optionstr = $this->new_parseOptions($options);
        if($optionstr){
            $where .= " WHERE {$optionstr}";
        }
        $result = [];
        list($startdate, $enddate) = $this->new_parseDate($options);
        if($startdate && $enddate){
            //获取时间段内的每一天
            $data_arr = $this->getDateRange($startdate, $enddate);        // 构建查询语句
            $break = 0;
            $unionSql='';
            foreach ($data_arr as $date) {
                $realTableName = $this->getRealTableName($date);
                if(in_array($realTableName,$this->orderTables) && intval($date) >= $this->expire_date){
                    $unionSql .= "SELECT {$field} FROM `{$realTableName}`";
                    if($where){
                        $unionSql .= $where;
                    }
                    if($groupby){
                        $unionSql .= $groupby;
                    }
                    $unionSql .= " UNION All ";
                }elseif($break == 0 && intval($date) < $this->expire_date){
                    $unionSql .= "SELECT {$field} FROM `{$this->tablePrefix}{$this->tableName}`";
                    if($where){
                        $unionSql .= $where;
                    }
                    if($groupby){
                        $unionSql .= $groupby;
                    }
                    $unionSql .= " UNION All ";
                    $break = 1;
                }
                if($break===1 && intval($date) < $this->expire_date){
                    break;
                }
            }
            $unionSql = rtrim($unionSql, 'UNION  All '); // 移除最后一个UNION
            if($unionSql !=''){
                if($orderby){
                    $unionSql .= $orderby;
                }
                if($limit){
                    $unionSql .= $limit;
                }
                $do_unionSql = 'SELECT * FROM (' . $unionSql . ') AS subquery';
                // var_dump($do_unionSql);
                // echo "<br><br>";
                // 执行联合查询
                $result = $this->query($do_unionSql);
            }
        }else{
            $realTableName = $this->getRealTableName($startdate);
            if(in_array($realTableName,$this->orderTables)){
                $unionSql = "SELECT {$field} FROM `{$realTableName}`";
                if($where){
                    $unionSql .= $where;
                }
                if($groupby){
                        $unionSql .= $groupby;
                    }
                if($orderby){
                    $unionSql .= $orderby;
                }
                if($limit){
                    $unionSql .= $limit;
                }
                // 执行联合查询
                
                // var_dump($unionSql);
                $result = $this->query($unionSql);
            }
        }
        return $result;
    }

    // 批量新增数据
    public function addAllByDate($data, $options = array()){
        // 根据创建时间计算应该使用的分表
        $date = isset($data['pay_applydate']) ? $data['pay_applydate'] : date('Ymd',time());
        $tableName = $this->getRealTableName($date);
        // 切换到对应的分表进行插入操作
        $this->table($tableName);
        return $this->addAll($data, $options);
    }
    
    public function saveByDate($data, $options = array()){
        // 根据创建时间计算应该使用的分表
        $date = isset($options['pay_applydate']) ? $options['pay_applydate'] : date('Ymd',time());
        $tableName = $this->getRealTableName($date);
        // 切换到对应的分表进行更新操作
        $options['where'] = $options;
        $options['table'] = $tableName;
        return $this->db->update($data, $options);
    }
    
    public function new_parseDate($options){
        $startdate = $enddate = '';
        if(!isset($options['pay_applydate']) && !isset($options['pay_successdate'])){
            $date = date('Y-m-d',time());
            return [$date,$date];
        }
        if(isset($options['pay_applydate'])){
            if(is_array($options['pay_applydate'])){
                $startdate = $options['pay_applydate'][1][0];
                $enddate = $options['pay_applydate'][1][1];
            }else{
                $date = $options['pay_applydate']?$options['pay_applydate']:date('Y-m-d',time());
                list($startdate, $enddate) = explode('|', $date);
            }
        }elseif(isset($options['pay_successdate'])){
            if(is_array($options['pay_successdate'])){
                $startdate = $options['pay_successdate'][1][0];
                $enddate = $options['pay_successdate'][1][1];
            }else{
                $date = $options['pay_successdate']?$options['pay_successdate']:date('Y-m-d',time());
                list($startdate, $enddate) = explode('|', $date);
            }
        }
        return [$startdate,$enddate];
    }
    
    //where条件转换为语句
    public function new_parseOptions($options){
        if (is_array($options) && (count($options) > 0)) {
            foreach ($options as $key => $value){
                $where[] = $this->parseWhereItem($key, $value);
            }
            $options = implode(" AND ", $where);
        }
        return $options;
    } 
    
    public function new_parseField($field){
        if(is_array($field) && (count($field) > 0)) {
            $field = implode(",", $field);
        }
        return $field;
    }
    
    //获取时间段内的每一天
    public function getDateRange($startdate, $enddate) {
        $stimestamp = is_numeric($startdate)?$startdate:strtotime($startdate);
        $etimestamp = is_numeric($enddate)?$enddate:strtotime($enddate);
        // 计算日期段内有多少天
        $days = floor(($etimestamp - $stimestamp) / 86400);
        // 保存每天日期
        $date = array();
        for($i = $days; $i >= 0; $i--){
            $date[] = date('Ymd', $stimestamp + (86400 * $i));
        }
        return $date;
    }
    
    // where子单元分析
    public function parseWhereItem($key, $val)
    {
        $whereStr = '';
        if (is_array($val)) {
            if (is_string($val[0])) {
                $exp = strtolower($val[0]);
                if (preg_match('/^(eq|neq|gt|egt|lt|elt)$/', $exp)) {
                    // 比较运算
                    $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
                } elseif (preg_match('/^(notlike|like)$/', $exp)) {
                    // 模糊查找
                    if (is_array($val[1])) {
                        $likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
                        if (in_array($likeLogic, array('AND', 'OR', 'XOR'))) {
                            $like = array();
                            foreach ($val[1] as $item) {
                                $like[] = $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($item);
                            }
                            $whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
                        }
                    } else {
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
                    }
                } elseif ('bind' == $exp) {
                    // 使用表达式
                    $whereStr .= $key . ' = :' . $val[1];
                } elseif ('exp' == $exp) {
                    // 使用表达式
                    $whereStr .= $key . ' ' . $val[1];
                } elseif (preg_match('/^(notin|not in|in)$/', $exp)) {
                    // IN 运算
                    if (isset($val[2]) && 'exp' == $val[2]) {
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $val[1];
                    } else {
                        if (is_string($val[1])) {
                            $val[1] = explode(',', $val[1]);
                        }
                        $zone = implode(',', $this->parseValue($val[1]));
                        $whereStr .= $key . ' ' . $this->exp[$exp] . ' (' . $zone . ')';
                    }
                } elseif (preg_match('/^(notbetween|not between|between)$/', $exp)) {
                    // BETWEEN运算
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]);
                } else {
                    E(L('_EXPRESS_ERROR_') . ':' . $val[0]);
                }
            } else {
                $count = count($val);
                $rule  = isset($val[$count - 1]) ? (is_array($val[$count - 1]) ? strtoupper($val[$count - 1][0]) : strtoupper($val[$count - 1])) : '';
                if (in_array($rule, array('AND', 'OR', 'XOR'))) {
                    $count = $count - 1;
                } else {
                    $rule = 'AND';
                }
                for ($i = 0; $i < $count; $i++) {
                    $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                    if ('exp' == strtolower($val[$i][0])) {
                        $whereStr .= $key . ' ' . $data . ' ' . $rule . ' ';
                    } else {
                        $whereStr .= $this->parseWhereItem($key, $val[$i]) . ' ' . $rule . ' ';
                    }
                }
                $whereStr = '( ' . substr($whereStr, 0, -4) . ' )';
            }
        } else {
            //对字符串类型字段采用模糊匹配
            $likeFields = $this->config['db_like_fields'];
            if ($likeFields && preg_match('/^(' . $likeFields . ')$/i', $key)) {
                $whereStr .= $key . ' LIKE ' . $this->parseValue('%' . $val . '%');
            } else {
                $whereStr .= $key . ' = ' . $this->parseValue($val);
            }
        }
        return $whereStr;
    }
        /**
     * value分析
     * @access protected
     * @param mixed $value
     * @return string
     */
    public function parseValue($value)
    {
        if (is_string($value)) {
            $value = strpos($value, ':') === 0 && in_array($value, array_keys($this->bind)) ? $this->escapeString($value) : '\'' . $this->escapeString($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escapeString($value[1]);
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'parseValue'), $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }
        /**
     * SQL指令安全过滤
     * @access public
     * @param string $str  SQL字符串
     * @return string
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }
    
}

?>