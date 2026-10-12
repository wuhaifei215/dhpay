<?php
namespace Dhpay2\Security;

/** Shared by the download endpoints and all CLI export entry points (PHP 7.2+). */
final class ExportTask
{
    const TTL = 604800;
    const MAX_RANGE_SECONDS = 604800;
    const MAX_QUEUE = 50;

    public static function positiveId($value)
    {
        if ((!is_int($value) && !is_string($value)) ||
            !preg_match('/^[1-9][0-9]{0,9}$/D', (string)$value) || (float)$value > 2147483647) {
            throw new \InvalidArgumentException('账号或商户编号不合法');
        }
        return (int)$value;
    }

    public static function validSession($auth, $signature)
    {
        if (!is_array($auth) || !is_string($signature) || empty($auth['uid']) ||
            empty($auth['expire_time']) || !is_numeric($auth['expire_time']) || $auth['expire_time'] <= time()) {
            return false;
        }
        try { self::positiveId($auth['uid']); } catch (\InvalidArgumentException $e) { return false; }
        ksort($auth);
        return hash_equals(sha1(http_build_query($auth)), $signature);
    }

    public static function csrfToken()
    {
        $token = session('export_csrf_token');
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            $token = bin2hex(random_bytes(32));
            session('export_csrf_token', $token);
        }
        return $token;
    }

    public static function checkCsrf($token)
    {
        $expected = session('export_csrf_token');
        if (!is_string($token) || !is_string($expected) || strlen($expected) !== 64 ||
            !hash_equals($expected, $token)) {
            throw new \InvalidArgumentException('页面已失效，请刷新页面后重新提交');
        }
    }

    public static function type($value, $ownerKind)
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[1-7]$/D', (string)$value)) {
            throw new \InvalidArgumentException('导出类型不合法');
        }
        $type = (int)$value;
        $allowed = $ownerKind === 'admin' ? array(1, 3, 5, 7) : array(2, 4, 6);
        if (!in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('没有该导出类型的权限');
        }
        return $type;
    }

    public static function subAccountCanExport($permissions, $type, $where)
    {
        if (!is_array($permissions) || (!is_string($type) && !is_int($type)) || !is_array($where)) { return false; }
        $pages = array(2 => 'Order/index', 4 => 'Withdrawal/payment', 6 => 'Account/changeRecord');
        if (!preg_match('/^[246]$/D', (string)$type)) { return false; }
        $page = $pages[(int)$type];
        if (isset($permissions['page:' . $page])) { return true; }
        $paymentType = isset($where['type']) ? $where['type'] : '';
        return (int)$type === 4 && (is_string($paymentType) || is_int($paymentType)) &&
            isset($permissions['page:' . $page . ':type=' . $paymentType]);
    }

    public static function filters($type, $input)
    {
        if (!is_array($input)) { throw new \InvalidArgumentException('导出筛选条件不合法'); }
        $fields = array(
            1 => array('memberid', 'orderid', 'out_trade_id', 'createtime', 'successtime', 'tongdao', 'bank', 'status', 'currency'),
            3 => array('memberid', 'orderid', 'out_trade_no', 'createtime', 'successtime', 'tongdao', 'bankcode', 'type', 'status', 'memo', 'currency'),
            5 => array('memberid', 'orderid', 'createtime', 'bank', 'currency'),
            7 => array('memberid', 'pay_orderid', 'out_trade_id', 'utr', 'createtime', 'status'),
        );
        $group = in_array((int)$type, array(1, 2), true) ? 1 : (in_array((int)$type, array(3, 4), true) ? 3 : (in_array((int)$type, array(5, 6), true) ? 5 : 7));
        $result = array();
        foreach ($input as $field => $value) {
            if (!in_array($field, $fields[$group], true) || (!is_string($value) && !is_int($value))) {
                throw new \InvalidArgumentException('包含不允许的筛选字段或查询表达式');
            }
            $value = trim((string)$value);
            if ($value === '') { continue; } // Keep "0": it is a valid order status.
            if (strlen($value) > 512 || preg_match('/[\x00-\x1f\x7f]/', $value)) {
                throw new \InvalidArgumentException('筛选值过长或含有非法字符');
            }
            if ($field === 'createtime' || $field === 'successtime') {
                $value = self::dateRange($value);
            } elseif ($field === 'memberid' || $field === 'tongdao') {
                $value = (string)self::positiveId($value);
                if ($field === 'memberid' && (int)$value <= 10000) {
                    throw new \InvalidArgumentException('商户编号不合法');
                }
            } elseif ($field === 'status') {
                $allowed = $group === 1 ? array('0', '1', '2', '1or2') : ($group === 3 ? array('0', '1', '2', '3', '4', '5', '6', '2or3', '4or5') : array('0', '1', '2'));
                if (!in_array($value, $allowed, true)) { throw new \InvalidArgumentException('状态不合法'); }
            } elseif ($field === 'currency') {
                if (!preg_match('/^[A-Z]{3}$/D', $value)) { throw new \InvalidArgumentException('币种不合法'); }
            } elseif ($field !== 'memo' && !preg_match('/^[a-zA-Z0-9_.:@+\-]{1,128}$/D', $value)) {
                throw new \InvalidArgumentException('订单号或筛选值格式不合法');
            }
            $result[$field] = $value;
        }
        if (empty($result['createtime']) && empty($result['successtime'])) {
            throw new \InvalidArgumentException('请选择不超过7天的导出时间范围');
        }
        ksort($result);
        return $result;
    }

    private static function dateRange($value)
    {
        $parts = explode('|', $value);
        if (count($parts) !== 2) { throw new \InvalidArgumentException('时间范围格式不合法'); }
        $dates = array();
        foreach ($parts as $part) {
            $part = trim($part);
            $format = strlen($part) === 10 ? 'Y-m-d' : 'Y-m-d H:i:s';
            $date = \DateTime::createFromFormat('!' . $format, $part);
            if (!$date || $date->format($format) !== $part) { throw new \InvalidArgumentException('日期不合法'); }
            $dates[] = $date;
        }
        $seconds = $dates[1]->getTimestamp() - $dates[0]->getTimestamp();
        if ($seconds < 0 || $seconds > self::MAX_RANGE_SECONDS) {
            throw new \InvalidArgumentException('只允许导出7天内的数据');
        }
        return $dates[0]->format('Y-m-d H:i:s') . '|' . $dates[1]->format('Y-m-d H:i:s');
    }

    public static function secret()
    {
        $secret = C('EXPORT_TASK_SECRET') ?: C('DATA_AUTH_KEY');
        if (!is_string($secret) || strlen($secret) < 16) { throw new \RuntimeException('导出任务签名配置不正确'); }
        return $secret;
    }

    private static function signature($key, $job, $secret)
    {
        $data = array();
        foreach (array('version', 'owner_kind', 'uid', 'scope_memberid', 'type', 'where', 'name', 'down_url', 'time') as $field) {
            if (!array_key_exists($field, $job)) { throw new \InvalidArgumentException('任务字段不完整'); }
            $data[$field] = $job[$field];
        }
        return hash_hmac('sha256', $key . "\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $secret);
    }

    public static function create($ownerKind, $uid, $type, $where, $secret)
    {
        if (!in_array($ownerKind, array('user', 'admin'), true)) { throw new \InvalidArgumentException('任务来源不合法'); }
        $uid = self::positiveId($uid);
        if ($ownerKind === 'user' && $uid <= 10000) { throw new \InvalidArgumentException('商户编号不合法'); }
        $type = self::type($type, $ownerKind);
        $where = self::filters($type, $where);
        if ($ownerKind === 'user' && empty($where['memberid'])) { throw new \InvalidArgumentException('缺少商户权限范围'); }
        $names = array(1 => '下载代收订单', 2 => '下载代收订单', 3 => '下载代付订单', 4 => '下载代付订单', 5 => '下载帐变记录', 6 => '下载帐变记录', 7 => '下载UTR查询订单');
        $actions = array(1 => 'Order/exportorder', 2 => 'Order/exportorder', 3 => 'Withdrawal/exportweituo', 4 => 'Withdrawal/exportweituo', 5 => 'Order/exceldownload', 6 => 'Account/exceldownload', 7 => 'Order/exportUtrOrder');
        $key = 'download_' . $ownerKind . '_' . $uid . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8));
        $job = array('version' => 2, 'owner_kind' => $ownerKind, 'uid' => $uid,
            'scope_memberid' => $ownerKind === 'user' ? (int)$where['memberid'] : 0,
            'type' => $type, 'name' => $names[$type], 'down_url' => ($ownerKind === 'admin' ? 'Admin/' : 'User/') . $actions[$type],
            'where' => $where, 'status' => 0, 'time' => time());
        $job['signature'] = self::signature($key, $job, $secret);
        return array($key, $job);
    }

    public static function validKey($key)
    {
        return is_string($key) && preg_match('/^download_(user|admin)_([1-9][0-9]{0,9})_[0-9]{14}(?:_[a-f0-9]{16})?$/D', $key);
    }

    public static function validate($key, $job, $secret)
    {
        if (!self::validKey($key) || !is_array($job)) { throw new \InvalidArgumentException('非法或失效的导出任务'); }
        preg_match('/^download_(user|admin)_([0-9]+)_/', $key, $matches);
        $kind = $matches[1];
        $uid = self::positiveId(isset($job['uid']) ? $job['uid'] : null);
        if ($kind === 'user' && $uid <= 10000) { throw new \InvalidArgumentException('商户编号不合法'); }
        if ((int)$matches[2] !== $uid) { throw new \InvalidArgumentException('任务所有者不匹配'); }
        $type = self::type(isset($job['type']) ? $job['type'] : null, $kind);
        if (!isset($job['time']) || !is_int($job['time']) || $job['time'] < time() - self::TTL || $job['time'] > time() + 300) {
            throw new \InvalidArgumentException('任务已过期或时间不合法');
        }
        $sealed = isset($job['version']) || isset($job['signature']) || preg_match('/_[a-f0-9]{16}$/D', $key);
        if ($sealed) {
            if (!isset($job['version'], $job['signature'], $job['owner_kind']) || $job['version'] !== 2 ||
                !is_string($job['signature']) || $job['owner_kind'] !== $kind ||
                !hash_equals(self::signature($key, $job, $secret), $job['signature'])) {
                throw new \InvalidArgumentException('任务签名不合法');
            }
        } elseif ($kind === 'user') {
            // Old anonymous user jobs have no proof of authorization. They must be resubmitted.
            throw new \InvalidArgumentException('旧版商户任务需登录后重新提交');
        }
        $where = self::filters($type, isset($job['where']) ? $job['where'] : null);
        if ($kind === 'user' && (empty($where['memberid']) || !isset($job['scope_memberid']) ||
            self::positiveId($job['scope_memberid']) !== (int)$where['memberid'])) {
            throw new \InvalidArgumentException('任务商户权限范围不匹配');
        }
        $job['uid'] = $uid;
        $job['type'] = $type;
        $job['where'] = $where;
        return $job;
    }

    public static function enqueue($redis, $key, $job)
    {
        $script = <<<'LUA'
for index = 2, 3 do
    local kind = redis.call('TYPE', KEYS[index]).ok
    if kind ~= 'none' and kind ~= 'list' then return -4 end
end
if redis.call('LLEN', KEYS[3]) >= tonumber(ARGV[3]) then return -3 end
redis.call('SET', KEYS[1], ARGV[1], 'EX', ARGV[2])
redis.call('RPUSH', KEYS[2], KEYS[1])
redis.call('EXPIRE', KEYS[2], ARGV[2])
redis.call('RPUSH', KEYS[3], KEYS[1])
return 1
LUA;
        $result = $redis->eval($script, array($key, 'downList_' . $job['owner_kind'] . '_' . $job['uid'], 'downList',
            json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::TTL, self::MAX_QUEUE), 3);
        if ((int)$result !== 1) {
            $messages = array(-3 => '导出队列已满，请稍后再试', -4 => '导出队列状态异常，请联系管理员');
            throw new \RuntimeException(isset($messages[$result]) ? $messages[$result] : '导出任务提交失败');
        }
    }
}
