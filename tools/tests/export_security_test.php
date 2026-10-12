<?php
// Isolated regression tests: no application credentials, network connections or production data.
namespace Think {
    class Controller {
        public $assigned = array();
        public function __construct() { }
        protected function assign($key, $value) { $this->assigned[$key] = $value; }
        protected function redirect($url) { throw new \TestResponse(false, 'redirect'); }
        protected function error($message, $url = '', $ajax = false) { throw new \TestResponse(false, $message); }
        protected function success($message) { throw new \TestResponse(true, $message); }
    }
    class Log { public static function record($message, $level = '') { $GLOBALS['test_logs'][] = $message; } }
}

namespace {
    error_reporting(E_ALL);
    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) { return false; }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    require dirname(__DIR__, 2) . '/shared/ExportTask.php';
    use Dhpay2\Security\ExportTask;

    class TestResponse extends \RuntimeException {
        public $ok;
        public function __construct($ok, $message) { $this->ok = $ok; parent::__construct($message); }
    }
    $GLOBALS['test_config'] = array('DATA_AUTH_KEY' => 'unit-test-signing-key-not-used-in-production', 'GROUP_ID' => array(), 'DOMAIN' => 'unit.test');
    $GLOBALS['test_session'] = array();
    $GLOBALS['test_request'] = array();
    $GLOBALS['test_logs'] = array();
    $GLOBALS['test_permissions'] = array('page:Order/index' => array());
    $GLOBALS['test_rows'] = array();
    $GLOBALS['test_model_calls'] = array();
    $checks = 0;
    function C($key) { return isset($GLOBALS['test_config'][$key]) ? $GLOBALS['test_config'][$key] : null; }
    function session($key, $value = '__READ__') {
        if ($value !== '__READ__') { $GLOBALS['test_session'][$key] = $value; return; }
        if (array_key_exists($key, $GLOBALS['test_session'])) { return $GLOBALS['test_session'][$key]; }
        $parts = explode('.', $key); $result = $GLOBALS['test_session'];
        foreach ($parts as $part) { if (!is_array($result) || !array_key_exists($part, $result)) { return null; } $result = $result[$part]; }
        return $result;
    }
    function I($key, $default = null, $filter = null) {
        $parts = explode('.', $key); array_shift($parts); $result = $GLOBALS['test_request'];
        foreach ($parts as $part) { if (!is_array($result) || !array_key_exists($part, $result)) { return $default; } $result = $result[$part]; }
        return $result;
    }
    function is_https() { return true; }
    function U($route) { return '/' . $route; }
    function is_login() { return 42; }
    function is_rootAdministrator() { return true; }
    function mkdirs($path, $mode = 0750) { return is_dir($path) || mkdir($path, $mode, true); }
    function M($name) { return new TestModel($name); }
    function D($name) { return new TestModel($name); }
    function check($condition, $message) { global $checks; $checks++; if (!$condition) { throw new \RuntimeException('FAIL: ' . $message); } }
    function rejects($callable, $message) { try { $callable(); } catch (\InvalidArgumentException $e) { check(true, $message); return; } check(false, $message); }
    function authenticated($sub = false) {
        $auth = array('uid' => $sub ? 43 : 42, 'expire_time' => time() + 1800, 'session_random' => 'test-session',
            'username' => 'fixture-user', 'groupid' => 4, 'password' => 'fixture-only');
        if ($sub) { $auth += array('is_sub_account' => 1, 'parent_id' => 42, 'username' => 'sub-test', 'realname' => 'sub-test'); }
        ksort($auth);
        session('user_auth', $auth); session('user_auth_sign', sha1(http_build_query($auth)));
        session('admin_auth', $auth); session('admin_auth_sign', sha1(http_build_query($auth)));
    }
    function filters() { return array('memberid' => '10042', 'createtime' => '2026-10-10 00:00:00|2026-10-11 00:00:00', 'status' => '0'); }

    class TestModel {
        public $name; public $conditions = array(); public $offset = 0; public $size = 5000;
        public function __construct($name) { $this->name = $name; }
        public function where($conditions) { $this->conditions = $conditions; return $this; }
        public function field($fields) { return $this; }
        public function table($name) { return $this; }
        public function alias($alias) { return $this; }
        public function order($order) { return $this; }
        public function limit($offset, $size = null) { $this->offset = (int)$offset; if ($size !== null) { $this->size = $size; } return $this; }
        public function getTables($where) { return array('pay_order1011'); }
        public function getLastSql() { return 'fixture-query'; }
        public function getPermissionMap($id) { return $GLOBALS['test_permissions']; }
        public function getUserRules($id) { return array(); }
        public function count() {
            $GLOBALS['test_model_calls'][] = array($this->name, 'count', $this->conditions);
            return $this->name === 'Member' ? ((int)($this->conditions['id'] ?? 0) === 555 ? 1 : 0) : count($GLOBALS['test_rows']);
        }
        public function find() {
            if ($this->name === 'Websiteconfig') { return array('google_auth' => 0, 'admin_alone_login' => 0); }
            if ($this->name === 'SubAccount') { return array('status' => 1, 'parent_id' => 42, 'session_random' => 'test-session'); }
            if ($this->name === 'Member' && isset($this->conditions['parentid'])) {
                return (int)$this->conditions['id'] === 555 && (int)$this->conditions['parentid'] === 42
                    ? array('id' => 555, 'currency' => 'BDT') : false;
            }
            return array('id' => 42, 'uid' => 42, 'username' => 'fixture-user', 'groupid' => 4, 'status' => 1,
                'google_secret_key' => 'fixture', 'google_auth' => 0, 'session_random' => 'test-session', 'memberid' => 10042, 'currency' => 'BRL');
        }
        public function select() {
            $GLOBALS['test_model_calls'][] = array($this->name, 'select', $this->conditions, $this->size);
            if (!empty($GLOBALS['test_select_failure']) && $this->name === 'Order') { return false; }
            if ($this->name === 'Member') { return array(array('id' => 42, 'username' => 'fixture-user', 'groupid' => 4)); }
            if ($this->name === 'Article' || $this->name === 'MemberAgentCate') { return array(); }
            $rows = $GLOBALS['test_rows'];
            if (isset($this->conditions['id']) && is_array($this->conditions['id']) && $this->conditions['id'][0] === 'lt') {
                $cursor = (int)$this->conditions['id'][1];
                $rows = array_values(array_filter($rows, function ($row) use ($cursor) { return $row['id'] < $cursor; }));
            }
            usort($rows, function ($left, $right) { return $right['id'] <=> $left['id']; });
            return array_slice($rows, $this->offset, $this->size);
        }
    }
    class TestRedis {
        public $records = array(); public $lists = array(); public $evaluations = array(); public $evalResult = 1; public $pops = 0;
        public function eval($script, $args, $keys) {
            $this->evaluations[] = array($script, $args, $keys);
            if ($keys === 2 && strpos($script, "redis.call('LPOP'") !== false) {
                $this->pops++;
                $key = isset($this->lists[$args[0]]) ? array_shift($this->lists[$args[0]]) : false;
                if ($key) { $this->lists[$args[1]][] = $key; }
                return $key;
            }
            return $this->evalResult;
        }
        public function lPop($key) { $this->pops++; return isset($this->lists[$key]) ? array_shift($this->lists[$key]) : false; }
        public function lRange($key, $start, $end) { return array_slice($this->lists[$key] ?? array(), $start, $end - $start + 1); }
        public function get($key) { return isset($this->records[$key]) ? $this->records[$key] : false; }
        public function ttl($key) { return isset($this->records[$key]) ? 3600 : -2; }
        public function setex($key, $ttl, $value) { $this->records[$key] = $value; return true; }
        public function rPush($key, $value) { $this->lists[$key][] = $value; return count($this->lists[$key]); }
        public function expire($key, $ttl) { return true; }
        public function lRem($key, $value, $count) { $this->lists[$key] = array_values(array_filter($this->lists[$key] ?? array(), function ($item) use ($value) { return $item !== $value; })); }
    }

    $mode = $argv[1] ?? 'policy';
    $app = $argv[2] ?? 'user';
    $root = dirname(__DIR__, 2);
    if ($mode === 'policy') {
        check(ExportTask::filters(2, filters())['status'] === '0', 'status zero retained');
        foreach (array(1, 3, 5) as $currencyType) {
            foreach (array('BRL', 'BDT', 'PHP', 'INR') as $currency) {
                $where = filters(); if ($currencyType === 5) { unset($where['status']); }
                $where['currency'] = $currency;
                check(ExportTask::filters($currencyType, $where)['currency'] === $currency, 'dhpay2 currency retained for export type ' . $currencyType);
            }
        }
        foreach (array('brl', 'BRL UNION SELECT', array('exp', '1=1')) as $currency) {
            rejects(function () use ($currency) { ExportTask::filters(2, array_merge(filters(), array('currency' => $currency))); }, 'unsafe currency rejected');
        }
        foreach (array('', '../Runtime/test.php', '../../../../etc/passwd', '1&#39; UNION SELECT INTO OUTFILE shell.php', array('exp', '1')) as $uid) {
            rejects(function () use ($uid) { ExportTask::positiveId($uid); }, 'malicious identity rejected');
        }
        foreach (array(array('_string' => '1=1'), array('memberid' => array('exp', '1')), array('createtime' => '2026-10-01|2026-10-10'), array('createtime' => '2026-02-30|2026-03-01'), array('createtime' => '2026-10-11|2026-10-10'), array('createtime' => '2026-10-10|')) as $bad) {
            rejects(function () use ($bad) { ExportTask::filters(2, array_merge(filters(), $bad)); }, 'unsafe filters rejected');
        }
        rejects(function () { ExportTask::filters(2, array()); }, 'unbounded export rejected');
        rejects(function () { ExportTask::type(1, 'user'); }, 'merchant cannot request admin columns');
        rejects(function () { ExportTask::type(array(2), 'user'); }, 'array type rejected');
        check(ExportTask::subAccountCanExport(array('page:Order/index' => array()), 2, filters()), 'subaccount order permission recognized');
        check(!ExportTask::subAccountCanExport(array('page:Order/index' => array()), 6, filters()), 'order permission cannot authorize account records');
        check(ExportTask::subAccountCanExport(array('page:Withdrawal/payment:type=CPF' => array()), 4, array('type' => 'CPF')), 'scoped payout permission recognized');
        check(!ExportTask::subAccountCanExport(array('page:Withdrawal/payment:type=CPF' => array()), 4, array('type' => 'EMAIL')), 'scoped payout permission cannot expand');
        authenticated();
        check(ExportTask::validSession(session('user_auth'), session('user_auth_sign')), 'valid session accepted');
        check(!ExportTask::validSession(null, ''), 'missing session rejected');
        $expired = session('user_auth'); $expired['expire_time'] = time() - 1;
        check(!ExportTask::validSession($expired, session('user_auth_sign')), 'expired session rejected');
        $csrf = ExportTask::csrfToken(); ExportTask::checkCsrf($csrf);
        rejects(function () { ExportTask::checkCsrf('forged'); }, 'CSRF forgery rejected');
        list($key, $job) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        check(ExportTask::validate($key, $job, ExportTask::secret())['uid'] === 10042, 'signed job accepted');
        $tampered = $job; $tampered['where']['memberid'] = '10555';
        rejects(function () use ($key, $tampered) { ExportTask::validate($key, $tampered, ExportTask::secret()); }, 'scope tampering rejected');
        rejects(function () use ($job) { ExportTask::validate('download_user_../../etc/passwd_20261011185453', $job, ExportTask::secret()); }, 'malformed queue key rejected');
        $legacy = $job; unset($legacy['version'], $legacy['signature']);
        rejects(function () use ($key, $legacy) { ExportTask::validate($key, $legacy, ExportTask::secret()); }, 'unsigned merchant backlog rejected');
        list($adminKey, $adminJob) = ExportTask::create('admin', 42, 1, filters(), ExportTask::secret());
        $downgraded = $adminJob; unset($downgraded['version'], $downgraded['signature']);
        rejects(function () use ($adminKey, $downgraded) { ExportTask::validate($adminKey, $downgraded, ExportTask::secret()); }, 'sealed admin task cannot downgrade to legacy format');
        $adminKey = preg_replace('/_[a-f0-9]{16}$/D', '', $adminKey);
        unset($adminJob['version'], $adminJob['signature']);
        check(ExportTask::validate($adminKey, $adminJob, ExportTask::secret())['type'] === 1, 'filtered legacy admin job retained');
        $r = new TestRedis();
        list($secondKey, $secondJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        ExportTask::enqueue($r, $key, $job);
        ExportTask::enqueue($r, $secondKey, $secondJob);
        check($key !== $secondKey && count($r->evaluations) === 2, 'same account can submit consecutive distinct tasks');
        foreach ($r->evaluations as $evaluation) {
            check($evaluation[2] === 3 && count($evaluation[1]) === 6, 'enqueue uses only task, history and global queue keys');
            check($evaluation[1][1] === 'downList_user_10042' && $evaluation[1][2] === 'downList' &&
                $evaluation[1][5] === ExportTask::MAX_QUEUE, 'same account still subject to global queue cap');
            check(strpos($evaluation[0], 'KEYS[4]') === false && strpos($evaluation[0], 'KEYS[5]') === false &&
                strpos($evaluation[0], 'ARGV[4]') === false, 'enqueue script has no account lock or submit interval');
        }
        foreach (array(-3, -4, false) as $result) {
            $r = new TestRedis(); $r->evalResult = $result;
            try { ExportTask::enqueue($r, $key, $job); check(false, 'queue rejection surfaced'); }
            catch (\RuntimeException $e) { check(true, 'queue rejection surfaced'); }
        }
    } elseif ($mode === 'templates') {
        require $root . '/user/core/Library/Think/Template/TagLib.class.php';
        require $root . '/user/core/Library/Think/Template/TagLib/Cx.class.php';
        $compiler = (new \ReflectionClass('Think\\Template\\TagLib\\Cx'))->newInstanceWithoutConstructor();
        $paths = array('user/Application/User/View/New/Download/index.html',
            'glht/Application/User/View/New/Download/index.html', 'glht/Application/Admin/View/Download/index.html');
        foreach ($paths as $path) {
            $text = file_get_contents($root . '/' . $path);
            $start = strpos($text, '<if condition="$vo[\'status\']');
            $fragment = substr($text, $start, strpos($text, '</if>', $start) - $start);
            preg_match_all('/<(?:if|elseif) condition="([^"]+)"/', $fragment, $matches);
            $conditions = array_map(array($compiler, 'parseCondition'), $matches[1]);
            foreach (array(array(array('status' => 0), 4), array(array('status' => 1), 4),
                array(array('status' => 0, 'processed_rows' => 5000), 3),
                array(array('status' => 1, 'url' => 'https://unit.test/file.zip'), 1),
                array(array('status' => 2, 'url' => 'https://unit.test/file.zip'), 1),
                array(array('status' => -1, 'error' => 'failure'), 0), array(array('status' => 2, 'url' => 'noData'), 2)) as $case) {
                $vo = $case[0]; $branch = 4;
                foreach ($conditions as $index => $condition) { if (eval('return ' . $condition . ';')) { $branch = $index; break; } }
                check($branch === $case[1], 'compiled download template handles state without empty download links');
            }
        }
        $forms = 0;
        foreach (array('user/Application/User/View', 'glht/Application/User/View', 'glht/Application/Admin/View') as $directory) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/' . $directory, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->getExtension() !== 'html') { continue; }
                $body = file_get_contents($file->getPathname());
                if (strpos($body, "U('Download/setlist')") === false) { continue; }
                $forms++;
                check(strpos($body, "'export_token': '<{\$exportCsrfToken}>'") !== false, 'export form has CSRF token: ' . $file->getPathname());
            }
        }
        check($forms === 21, 'all dhpay2 export forms covered');
    } elseif ($mode === 'controller' || $mode === 'get' || $mode === 'sub') {
        define('IS_POST', $mode !== 'get'); define('IS_AJAX', true);
        define('CONTROLLER_NAME', 'Download'); define('ACTION_NAME', 'setlist'); define('__MODULE__', 'User');
        define('MODULE_NAME', $app === 'admin' ? 'Admin' : 'User');
        $module = $app === 'admin' ? 'Admin' : 'User'; $folder = $app === 'admin' ? 'glht' : $app;
        require $root . '/' . $folder . '/Application/' . $module . '/Controller/BaseController.class.php';
        if ($module === 'User') { require $root . '/' . $folder . '/Application/User/Controller/UserController.class.php'; }
        require $root . '/' . $folder . '/Application/' . $module . '/Controller/DownloadController.class.php';
        $parent = $module . '\\Controller\\DownloadController';
        eval('class TestDownload extends \\' . $parent . ' { public function redis_connect() { return $GLOBALS["test_redis"]; } }');
        authenticated($mode === 'sub');
        $token = ExportTask::csrfToken();
        $GLOBALS['test_request'] = array('uid' => $app === 'admin' ? '42' : '10042', 'type' => $app === 'admin' ? '1' : '2', 'where' => filters(), 'export_token' => $token);
        $r = $GLOBALS['test_redis'] = new TestRedis();
        function submit() { try { $controller = new TestDownload(); $controller->setlist(); } catch (TestResponse $e) { return $e; } throw new \RuntimeException('No endpoint response'); }
        $response = submit();
        check($response->ok === ($mode !== 'get'), 'POST-only endpoint');
        if ($mode !== 'get') {
            $args = $r->evaluations[0][1]; $job = json_decode($args[3], true);
            check($job['uid'] === ($app === 'admin' ? 42 : 10042), 'owner from authenticated identity');
            check($job['where']['status'] === '0', 'endpoint retains zero status');
            if ($app !== 'admin') {
                check($job['where']['currency'] === 'BRL', 'merchant currency comes from the authenticated account');
                $GLOBALS['test_request']['where']['currency'] = 'USD';
                check(submit()->ok, 'same account can submit another task immediately');
                $last = end($r->evaluations); $currencyJob = json_decode($last[1][3], true);
                check($currencyJob['where']['currency'] === 'BRL', 'client currency cannot override merchant currency');
                unset($GLOBALS['test_request']['where']['currency']);
            }
            foreach (array(array('uid' => '../../etc/passwd'), array('export_token' => 'forged'), array('type' => array('1')), array('where' => array_merge(filters(), array('_string' => '1=1')))) as $bad) {
                $before = count($r->evaluations); $old = $GLOBALS['test_request']; $GLOBALS['test_request'] = array_merge($old, $bad);
                check(!submit()->ok, 'malicious request denied');
                check(count($r->evaluations) === $before, 'rejection causes no queue write');
                $GLOBALS['test_request'] = $old;
            }
            if ($app !== 'admin') {
                $GLOBALS['test_request']['where']['memberid'] = '10555';
                $response = submit();
                if ($mode !== 'sub') {
                    check($response->ok, 'authorized direct child export preserved');
                    $last = end($r->evaluations); $job = json_decode($last[1][3], true);
                    check($job['uid'] === 10042 && $job['scope_memberid'] === 10555, 'owner separate from authorized target');
                    check($job['where']['currency'] === 'BDT', 'child currency is derived from the authorized account');
                    $GLOBALS['test_request']['where']['memberid'] = '10556';
                    $before = count($r->evaluations);
                    check(!submit()->ok && count($r->evaluations) === $before, 'unrelated merchant cannot be exported');
                } elseif ($mode === 'sub') {
                    check(!$response->ok, 'subaccount cannot select another merchant');
                } else {
                    check($response->ok, 'merchant scope forced to self');
                    $last = end($r->evaluations); $job = json_decode($last[1][3], true);
                    check($job['where']['memberid'] === '10042', 'forged target cannot change data scope');
                }
                session('user_auth', null); session('user_auth_sign', null);
                $before = count($r->evaluations);
                check(!submit()->ok && count($r->evaluations) === $before, 'anonymous submission denied before Redis');
                authenticated();
                $auth = session('user_auth'); $auth['expire_time'] = time() - 1; ksort($auth);
                session('user_auth', $auth); session('user_auth_sign', sha1(http_build_query($auth)));
                check(!submit()->ok && count($r->evaluations) === $before, 'expired session denied before Redis');
                authenticated(); session('user_auth_sign', 'forged');
                check(!submit()->ok && count($r->evaluations) === $before, 'forged session denied before Redis');
            }
        }
    } elseif ($mode === 'worker') {
        require $root . '/' . $app . '/Application/Cli/Controller/ExportController.class.php';
        class TestWorker extends \Cli\Controller\ExportController {
            public $redis; public $fail = false; public $lockFile;
            public function __construct($redis, $lockFile) { $this->redis = $redis; $this->lockFile = $lockFile; $this->_site = 'https://unit.test/'; }
            protected function exportLockPath() { return $this->lockFile; }
            public function redis_connect() { return $this->redis; }
            public function exportorder($where, $uid = '', $type = '') {
                if ($this->fail) { file_put_contents($this->exportTempDir . '/partial.csv', 'partial'); throw new \RuntimeException('fixture failure'); }
                return parent::exportorder($where, $uid, $type);
            }
        }
        $dir = sys_get_temp_dir() . '/r97-export-test-' . bin2hex(random_bytes(8)); mkdir($dir, 0700); chdir($dir);
        $r = new TestRedis(); $worker = new TestWorker($r, $dir . '/export.lock');
        $held = fopen($dir . '/export.lock', 'c'); flock($held, LOCK_EX);
        ob_start(); $worker->index(); ob_end_clean();
        check($r->pops === 0, 'concurrent invocation cannot dequeue'); flock($held, LOCK_UN); fclose($held);
        list($key, $job) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $r->records[$key] = json_encode($job); $r->lists['downList'] = array('download_user_../../etc/passwd_20261011185453', $key);
        $GLOBALS['test_rows'] = array(array('id' => 1, 'out_trade_id' => 'fixture-outer', 'pay_orderid' => 'fixture-order', 'pay_memberid' => 10042,
            'pay_amount' => 10, 'pay_poundage' => 1, 'pay_actualamount' => 9, 'pay_applydate' => time(), 'pay_successdate' => 0,
            'pay_bankname' => 'fixture-bank', 'pay_zh_tongdao' => 'fixture-channel', 'memberid' => 42, 'pay_status' => 0));
        ob_start(); $worker->index(); ob_end_clean();
        $done = json_decode($r->records[$key], true);
        check($done['status'] === 2 && !empty($done['url']), 'real CSV and ZIP export completes');
        check(empty($r->lists['downList_now']), 'running queue cleaned');
        check(count(glob('./Runtime/Temp/export_*')) === 0, 'private temp files cleaned');
        $files = glob('./Uploads/download/*/*.zip'); check(count($files) === 1, 'one archive produced');
        $zip = new \ZipArchive(); $zip->open($files[0]); $csv = $zip->getFromIndex(0); $zip->close();
        check(strpos($csv, 'fixture-user') !== false && strpos($csv, 'fixture-outer') !== false, 'exported rows preserved');
        $calls = $GLOBALS['test_model_calls'];
        $selects = array_values(array_filter($calls, function ($call) { return $call[0] === 'Order' && $call[1] === 'select'; }));
        check($selects[0][2]['pay_memberid'] === '10042' && $selects[0][2]['pay_status'] === '0', 'paged select uses merchant and zero-status filters');
        check($selects[0][3] === 5000, 'database batch bounded');
        // Exercise every remaining export type with multiple rows, including the parent-name cache.
        foreach (array(3, 4, 5, 6, 7) as $type) {
            $kind = in_array($type, array(3, 5, 7), true) ? 'admin' : 'user';
            $where = filters();
            if ($type !== 7) { $where['currency'] = 'BRL'; }
            if ($type === 5 || $type === 6) { unset($where['status']); }
            list($variantKey, $variantJob) = ExportTask::create($kind, $kind === 'admin' ? 42 : 10042, $type, $where, ExportTask::secret());
            $row = $GLOBALS['test_rows'][0] + array('userid' => 42, 'orderid' => 'fixture-order', 'out_trade_no' => 'fixture-outer',
                'tkmoney' => 10, 'sxfmoney' => 1, 'money' => 9, 'bankname' => 'bank', 'bankzhiname' => 'branch',
                'banknumber' => '123456', 'bankfullname' => 'name', 'type' => 'CPF', 'sqdatetime' => '2026-10-10 01:00:00',
                'cldatetime' => '', 'status' => 0, 'memo' => 'fixture', 'df_name' => 'channel', 'channel_mch_id' => 1,
                'transid' => 'fixture-trans', 'lx' => 1, 'ymoney' => 10, 'gmoney' => 9, 'datetime' => '2026-10-10 01:00:00',
                'contentstr' => 'fixture-content', 'utr' => 'fixture-utr', 'pay_tongdao' => 'channel', 'check_status' => 5);
            if ($type === 7) { $row['datetime'] = time(); $row['pay_memberid'] = 42; }
            $row['id'] = 1;
            $firstRow = $row; $firstRow['id'] = 2;
            $GLOBALS['test_rows'] = array($firstRow, $row);
            $GLOBALS['test_model_calls'] = array();
            $r->records[$variantKey] = json_encode($variantJob); $r->lists['downList'] = array($variantKey);
            $worker = new TestWorker($r, $dir . '/export.lock');
            ob_start(); $worker->index(); ob_end_clean();
            $variantDone = json_decode($r->records[$variantKey], true);
            check($variantDone['status'] === 2, 'export type ' . $type . ' completes with multiple rows: ' . ($variantDone['error'] ?? 'no error'));
            if ($type !== 7) {
                $model = $type === 3 || $type === 4 ? 'Wttklist' : 'Moneychange';
                $queries = array_values(array_filter($GLOBALS['test_model_calls'], function ($call) use ($model) { return $call[0] === $model && $call[1] === 'select'; }));
                check($queries[0][2]['currency'] === 'BRL', 'dhpay2 queries retain currency for type ' . $type);
                check(!isset($queries[0][2]['paytype']), 'currency is not remapped to r97 channel types');
            }
            if ($type === 3 || $type === 4) {
                $archivePath = substr($variantDone['url'], strlen('https://unit.test/'));
                $zip = new \ZipArchive(); check($zip->open($archivePath) === true, 'payout archive opens');
                $lines = explode("\n", $zip->getFromIndex(0)); $zip->close();
                $columns = $type === 3 ? 15 : 14;
                check(count(str_getcsv($lines[0])) === $columns && count(str_getcsv($lines[1])) === $columns,
                    'dhpay2 payout header and data retain original column count');
            }
            if ($type === 5 || $type === 6) {
                $members = array_filter($GLOBALS['test_model_calls'], function ($call) { return $call[0] === 'Member' && $call[1] === 'select'; });
                check(count($members) === 1, 'member lookup batched instead of repeated per row');
            }
        }
        // Force one 2-row batch per invocation to verify resumable cursor paging for every export type.
        $GLOBALS['test_config']['EXPORT_BATCH_ROWS'] = 2;
        $GLOBALS['test_config']['EXPORT_SLICE_BATCHES'] = 1;
        foreach (array(1, 2, 3, 4, 5, 6, 7) as $type) {
            $kind = in_array($type, array(1, 3, 5, 7), true) ? 'admin' : 'user';
            $where = filters();
            if ($type !== 7) { $where['currency'] = 'BRL'; }
            if ($type === 5 || $type === 6) { unset($where['status']); }
            list($pageKey, $pageJob) = ExportTask::create($kind, $kind === 'admin' ? 42 : 10042, $type, $where, ExportTask::secret());
            $rows = array();
            for ($id = 1; $id <= 5; $id++) {
                $item = $row;
                $item['id'] = $id;
                $item['out_trade_id'] = 'fixture-outer';
                $item['out_trade_no'] = 'fixture-outer';
                $item['orderid'] = 'fixture-order';
                if ($type === 1 || $type === 2 || $type === 7) { $item['out_trade_id'] = 'fixture-outer-' . $id; }
                elseif ($type === 3 || $type === 4) { $item['out_trade_no'] = 'fixture-outer-' . $id; }
                else { $item['orderid'] = 'fixture-outer-' . $id; }
                $rows[] = $item;
            }
            $GLOBALS['test_rows'] = $rows;
            $GLOBALS['test_model_calls'] = array();
            $r->records[$pageKey] = json_encode($pageJob);
            $r->lists['downList'] = array($pageKey);
            for ($run = 1; $run <= 4; $run++) {
                $worker = new TestWorker($r, $dir . '/export.lock');
                ob_start(); $worker->index(); ob_end_clean();
                $state = json_decode($r->records[$pageKey], true);
                if ($run < 4) {
                    check($state['status'] === 0 && $state['processed_rows'] === min(5, $run * 2),
                        'type ' . $type . ' saves progress and requeues after batch ' . $run);
                    check($r->lists['downList'] === array($pageKey), 'paused task queued exactly once');
                }
            }
            check($state['status'] === 2 && $state['processed_rows'] === 5, 'type ' . $type . ' resumes through completion');
            $archivePath = str_replace('https://unit.test/', '', $state['url']);
            $archive = new \ZipArchive();
            check($archive->open($archivePath) === true && $archive->numFiles === 3, 'type ' . $type . ' keeps one three-part ZIP');
            $contents = '';
            for ($part = 0; $part < $archive->numFiles; $part++) { $contents .= $archive->getFromIndex($part); }
            $archive->close();
            for ($id = 1; $id <= 5; $id++) {
                check(substr_count($contents, 'fixture-outer-' . $id) === 1, 'type ' . $type . ' exports row ' . $id . ' once');
            }
            check(count(glob('./Runtime/Temp/export_*')) === 0, 'finished export removes temporary CSV parts');
            if ($type === 1) {
                // An interrupted status write after ZIP creation must reuse the valid archive.
                $state['status'] = 1;
                $r->records[$pageKey] = json_encode($state);
                $r->lists['downList_now'] = array($pageKey);
                $worker = new TestWorker($r, $dir . '/export.lock');
                ob_start(); $worker->index(); ob_end_clean();
                check(json_decode($r->records[$pageKey], true)['status'] === 2,
                    'completed archive survives interruption before status write');
            }
        }
        list($crashKey, $crashJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $crashRows = array();
        for ($id = 1; $id <= 3; $id++) {
            $item = $row; $item['id'] = $id; $item['out_trade_id'] = 'crash-row-' . $id;
            $crashRows[] = $item;
        }
        $GLOBALS['test_rows'] = $crashRows;
        $r->records[$crashKey] = json_encode($crashJob);
        $r->lists['downList'] = array($crashKey);
        $worker = new TestWorker($r, $dir . '/export.lock'); ob_start(); $worker->index(); ob_end_clean();
        $crashed = json_decode($r->records[$crashKey], true);
        check($crashed['processed_rows'] === 2 && $crashed['status'] === 0, 'first batch checkpoint saved');
        $crashed['status'] = 1;
        $r->records[$crashKey] = json_encode($crashed);
        $r->lists['downList_now'] = array($crashKey);
        $tempDir = './Runtime/Temp/export_' . substr(hash('sha256', $crashKey), 0, 32);
        file_put_contents($tempDir . '/part_00000001.csv.partial', 'stale unfinished batch');
        $worker = new TestWorker($r, $dir . '/export.lock'); ob_start(); $worker->index(); ob_end_clean();
        $recovered = json_decode($r->records[$crashKey], true);
        check($recovered['processed_rows'] === 3 && $recovered['status'] === 0 &&
            $r->lists['downList'] === array($crashKey), 'interrupted task resumes once from saved cursor');
        $worker = new TestWorker($r, $dir . '/export.lock'); ob_start(); $worker->index(); ob_end_clean();
        $recovered = json_decode($r->records[$crashKey], true);
        $archive = new \ZipArchive();
        check($recovered['status'] === 2 && $archive->open(str_replace('https://unit.test/', '', $recovered['url'])) === true,
            'recovered task completes archive');
        $contents = '';
        for ($part = 0; $part < $archive->numFiles; $part++) { $contents .= $archive->getFromIndex($part); }
        $archive->close();
        for ($id = 1; $id <= 3; $id++) {
            check(substr_count($contents, 'crash-row-' . $id) === 1, 'recovery exports row ' . $id . ' exactly once');
        }
        check(strpos($contents, 'stale unfinished batch') === false && count(glob('./Runtime/Temp/export_*')) === 0,
            'recovery overwrites partial CSV and cleans temporary files');
        unset($GLOBALS['test_config']['EXPORT_BATCH_ROWS'], $GLOBALS['test_config']['EXPORT_SLICE_BATCHES']);
        list($limitKey, $limitJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $GLOBALS['test_config']['EXPORT_MAX_ROWS'] = 1;
        $r->records[$limitKey] = json_encode($limitJob); $r->lists['downList'] = array($limitKey);
        $worker = new TestWorker($r, $dir . '/export.lock'); ob_start(); $worker->index(); ob_end_clean();
        check(json_decode($r->records[$limitKey], true)['status'] === -1, 'oversized export fails before writing rows');
        unset($GLOBALS['test_config']['EXPORT_MAX_ROWS']);
        list($bytesKey, $bytesJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $GLOBALS['test_config']['EXPORT_MAX_BYTES'] = 1;
        $r->records[$bytesKey] = json_encode($bytesJob); $r->lists['downList'] = array($bytesKey);
        $worker = new TestWorker($r, $dir . '/export.lock'); ob_start(); $worker->index(); ob_end_clean();
        check(json_decode($r->records[$bytesKey], true)['status'] === -1 && count(glob('./Runtime/Temp/export_*')) === 0,
            'CSV byte quota rejects oversized export and cleans files');
        unset($GLOBALS['test_config']['EXPORT_MAX_BYTES']);
        list($queryKey, $queryJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $GLOBALS['test_select_failure'] = true;
        $r->records[$queryKey] = json_encode($queryJob); $r->lists['downList'] = array($queryKey);
        $worker = new TestWorker($r, $dir . '/export.lock'); ob_start(); $worker->index(); ob_end_clean();
        check(json_decode($r->records[$queryKey], true)['status'] === -1, 'database failure cannot become empty successful export');
        unset($GLOBALS['test_select_failure']);
        $GLOBALS['test_rows'] = array();
        list($emptyKey, $emptyJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $r->records[$emptyKey] = json_encode($emptyJob); $r->lists['downList'] = array($emptyKey);
        $worker = new TestWorker($r, $dir . '/export.lock'); ob_start(); $worker->index(); ob_end_clean();
        check(json_decode($r->records[$emptyKey], true)['url'] === 'noData', 'empty export reported without a download link');
        list($badKey, $badJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret()); $badJob['where']['memberid'] = '10555';
        $r->records[$badKey] = json_encode($badJob); $r->lists['downList'] = array($badKey); $before = count($GLOBALS['test_model_calls']);
        $worker = new TestWorker($r, $dir . '/export.lock'); $worker->index();
        check(json_decode($r->records[$badKey], true)['status'] === -1 && count($GLOBALS['test_model_calls']) === $before, 'tampered job rejected before database');
        list($failKey, $failJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $r->records[$failKey] = json_encode($failJob); $r->lists['downList'] = array($failKey);
        $worker = new TestWorker($r, $dir . '/export.lock'); $worker->fail = true; $worker->index();
        check(json_decode($r->records[$failKey], true)['status'] === -1 && empty($r->lists['downList_now']), 'failure state and running queue cleaned');
        check(count(glob('./Runtime/Temp/export_*')) === 0, 'partial CSV cleaned on exception');
        $lock = fopen($dir . '/export.lock', 'c'); check(flock($lock, LOCK_EX | LOCK_NB), 'execution lock released after failure'); fclose($lock);
        list($orphanKey, $orphanJob) = ExportTask::create('user', 10042, 2, filters(), ExportTask::secret());
        $orphanJob['status'] = 1; $r->records[$orphanKey] = json_encode($orphanJob); $r->lists['downList_now'] = array($orphanKey);
        $worker = new TestWorker($r, $dir . '/export.lock'); $worker->index();
        check(json_decode($r->records[$orphanKey], true)['status'] === -1 && empty($r->lists['downList_now']), 'orphaned running task recovered once host lock acquired');
        // Delete only this test's known files and directories, without recursive shell operations.
        foreach (glob('./Uploads/download/*/*.zip') as $file) { unlink($file); }
        foreach (glob('./Uploads/download/*') as $child) { rmdir($child); }
        rmdir('./Uploads/download'); rmdir('./Uploads'); rmdir('./Runtime/Temp'); rmdir('./Runtime'); unlink('./export.lock'); chdir($root); rmdir($dir);
    }
    echo 'PASS ' . $mode . '/' . $app . ': ' . $checks . " checks\n";
}
