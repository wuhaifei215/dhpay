<?php

/**
 * Standalone broadcast regression test; no framework, database, token or network.
 * Run separately for each controller copy:
 *   php api/Tests/TelegramBroadcastTest.php api
 *   php api/Tests/TelegramBroadcastTest.php open
 *   php api/Tests/TelegramBroadcastTest.php user
 *   php api/Tests/TelegramBroadcastTest.php glht
 *
 * Namespace fakes intercept the controller's database, clock and cURL calls.
 * The parent constructor is deliberately skipped so no .env is loaded.
 */

namespace Think {
    class Controller {}
}

namespace Telegram\Controller {
    class BroadcastFixture
    {
        public static $members = array();
        public static $logs = array();
        public static $saved = array();
        public static $selects = 0;
        public static $clock = 0.0;
        public static $sleeps = array();
        public static $handles = array();
        public static $multi = null;
        public static $wireResponses = array();

        public static function reset($chatIds = array())
        {
            self::$members = array();
            foreach ($chatIds as $i => $chatId) {
                self::$members[] = array('id' => $i + 1, 'telegram_id' => $chatId);
            }
            self::$logs = self::$saved = self::$sleeps = self::$handles = array();
            self::$wireResponses = array();
            self::$selects = 0;
            self::$clock = 0.0;
            self::$multi = null;
            $_REQUEST = $_POST = array();
        }
    }

    class FakeMemberModel
    {
        private $where = array();
        public function field($fields) { return $this; }
        public function where($where) { $this->where = $where; return $this; }
        public function order($order) { return $this; }
        public function select()
        {
            BroadcastFixture::$selects++;
            return array_values(array_filter(BroadcastFixture::$members, function ($member) {
                return $member['telegram_id'] !== '';
            }));
        }
        public function save($data)
        {
            BroadcastFixture::$saved[] = array('where' => $this->where, 'data' => $data);
            $changed = 0;
            foreach (BroadcastFixture::$members as &$member) {
                if ($member['telegram_id'] === $this->where['telegram_id']) {
                    $member = array_merge($member, $data);
                    $changed++;
                }
            }
            return $changed;
        }
    }

    function M($name)
    {
        if ($name !== 'Member') {
            throw new \RuntimeException('Unexpected database table: ' . $name);
        }
        return new FakeMemberModel();
    }
    function log_place_order($category, $message, $data)
    {
        BroadcastFixture::$logs[] = array($category, $message, $data);
    }
    function microtime($asFloat = false)
    {
        if (!$asFloat) {
            throw new \RuntimeException('Unexpected nonnumeric clock request');
        }
        return BroadcastFixture::$clock;
    }
    function usleep($microseconds)
    {
        BroadcastFixture::$sleeps[] = $microseconds;
        BroadcastFixture::$clock += $microseconds / 1000000;
    }

    // The real batch helper is exercised using asynchronous cURL handle fakes.
    function curl_multi_init()
    {
        BroadcastFixture::$multi = (object) array(
            'handles' => array(), 'executions' => 0, 'activeCounts' => array(),
            'removed' => 0, 'closed' => false
        );
        return BroadcastFixture::$multi;
    }
    function curl_init($url)
    {
        $handle = (object) array('url' => $url, 'options' => array(), 'closed' => false);
        BroadcastFixture::$handles[] = $handle;
        return $handle;
    }
    function curl_setopt_array($handle, $options) { $handle->options = $options; return true; }
    function curl_multi_add_handle($multi, $handle) { $multi->handles[] = $handle; return CURLM_OK; }
    function curl_multi_exec($multi, &$running)
    {
        $multi->executions++;
        $multi->activeCounts[] = count($multi->handles);
        $running = $multi->executions < 3 ? count($multi->handles) : 0;
        return $multi->executions === 1 ? CURLM_CALL_MULTI_PERFORM : CURLM_OK;
    }
    function curl_multi_select($multi, $timeout) { return -1; }
    function wireResult($handle)
    {
        parse_str($handle->options[CURLOPT_POSTFIELDS], $data);
        return isset(BroadcastFixture::$wireResponses[$data['chat_id']])
            ? BroadcastFixture::$wireResponses[$data['chat_id']]
            : array('raw' => '{"ok":true}', 'errno' => 0, 'error' => '');
    }
    function curl_multi_getcontent($handle) { return wireResult($handle)['raw']; }
    function curl_errno($handle) { return wireResult($handle)['errno']; }
    function curl_error($handle) { return wireResult($handle)['error']; }
    function curl_multi_remove_handle($multi, $handle) { $multi->removed++; return CURLM_OK; }
    function curl_close($handle) { $handle->closed = true; }
    function curl_multi_close($multi) { $multi->closed = true; }
}

namespace {
    use Telegram\Controller\BroadcastFixture as Fixture;

    // Keep these tests runnable even when the local CLI has no cURL extension.
    foreach (array(
        'CURLOPT_POST', 'CURLOPT_POSTFIELDS', 'CURLOPT_RETURNTRANSFER',
        'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT', 'CURLOPT_SSL_VERIFYPEER',
        'CURLOPT_SSL_VERIFYHOST', 'CURLM_CALL_MULTI_PERFORM', 'CURLM_OK'
    ) as $i => $constant) {
        if (!defined($constant)) {
            define($constant, $constant === 'CURLM_OK' ? 0 : 1000 + $i);
        }
    }
    $module = isset($argv[1]) ? $argv[1] : 'api';
    if (!in_array($module, array('api', 'open', 'user', 'glht'), true)) {
        fwrite(STDERR, "Usage: php api/Tests/TelegramBroadcastTest.php [api|open|user|glht]\n");
        exit(2);
    }
    require dirname(__DIR__, 2) . '/' . $module . '/Application/Telegram/Controller/ApiController.class.php';

    class TestBroadcastController extends \Telegram\Controller\ApiController
    {
        public $batches = array();
        public $respond;
        public function __construct() { $this->token = 'fixture-token'; }
        protected function sendBroadcastBatch($batch, $text, $photo, $fileId)
        {
            $this->batches[] = array(
                'jobs' => $batch, 'text' => $text, 'photo' => $photo,
                'file_id' => $fileId, 'started' => Fixture::$clock
            );
            $results = array();
            foreach ($batch as $job) {
                $result = $this->respond
                    ? call_user_func($this->respond, $job)
                    : array('response' => array('ok' => true), 'curl_errno' => 0);
                $results[] = array_merge(array('job' => $job, 'curl_errno' => 0), $result);
            }
            return $results;
        }
        public function transport($batch, $text, $photo = false, $fileId = '')
        {
            return parent::sendBroadcastBatch($batch, $text, $photo, $fileId);
        }
    }

    function same($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($message . ': expected ' . var_export($expected, true)
                . ', got ' . var_export($actual, true));
        }
    }
    function summary()
    {
        $found = array();
        foreach (Fixture::$logs as $entry) {
            if ($entry[1] === '群发完成') {
                $found[] = json_decode($entry[2], true);
            }
        }
        same(1, count($found), 'one completion summary');
        return $found[0];
    }
    function counts($total, $success, $failed, $retries)
    {
        $summary = summary();
        foreach (array('total' => $total, 'success' => $success, 'failed' => $failed, 'retries' => $retries) as $key => $value) {
            same($value, $summary[$key], 'summary ' . $key);
        }
        same(Fixture::$clock, (float) $summary['seconds'], 'elapsed time includes rate pauses');
    }
    function ids($count)
    {
        return array_map(function ($id) { return '-' . $id; }, range(1, $count));
    }
    function attempts($controller)
    {
        $jobs = array();
        foreach ($controller->batches as $batch) {
            foreach ($batch['jobs'] as $job) { $jobs[] = $job; }
        }
        return $jobs;
    }
    function rateLimit($seconds = 1)
    {
        return array('response' => array(
            'ok' => false, 'error_code' => 429,
            'parameters' => array('retry_after' => $seconds)
        ));
    }
    function broadcast($chatIds, $request = array('text' => 'notice'), $respond = null)
    {
        Fixture::reset($chatIds);
        $_REQUEST = $request;
        $controller = new TestBroadcastController();
        $controller->respond = $respond;
        $result = $controller->qunfa();
        return array($controller, $result);
    }

    $tests = array();
    $tests['1001 unique groups, deduplication and ten requests per second'] = function () {
        list($controller, $result) = broadcast(array_merge(ids(1001), array(' -1 ', '-1001', '', '   ')));
        same(true, $result, 'broadcast completes');
        counts(1001, 1001, 0, 0);
        same(101, count($controller->batches), 'all recipient batches');
        same(ids(1001), array_column(attempts($controller), 'chat_id'), 'each group sent once in order');
        foreach ($controller->batches as $i => $batch) {
            same($i === 100 ? 1 : 10, count($batch['jobs']), 'batch size');
            same((float) $i, $batch['started'], 'one second minimum between batch starts');
        }
        same(1, Fixture::$selects, 'recipient query runs once');
    };
    $tests['row offset, negative row and offset beyond recipient list'] = function () {
        list($controller) = broadcast(ids(25), array('text' => 'notice', 'row' => 1));
        same(array_slice(ids(25), 10), array_column(attempts($controller), 'chat_id'), 'row skips ten members');
        counts(15, 15, 0, 0);
        list($controller) = broadcast(ids(25), array('text' => 'notice', 'row' => -2));
        counts(25, 25, 0, 0);
        list($controller) = broadcast(ids(25), array('text' => 'notice', 'row' => 3));
        same(array(), $controller->batches, 'out of range row sends nothing');
        counts(0, 0, 0, 0);
    };
    $tests['photo metadata, blank text and missing photo file'] = function () {
        list($controller, $result) = broadcast(array('-1'), array('text' => '  通知 & hello  ', 'photo' => 1, 'file_id' => 'cached-file'));
        same(true, $result, 'valid photo accepted');
        same('通知 & hello', $controller->batches[0]['text'], 'caption trimmed');
        same(true, $controller->batches[0]['photo'], 'photo mode forwarded');
        same('cached-file', $controller->batches[0]['file_id'], 'cached Telegram file forwarded');
        counts(1, 1, 0, 0);
        list($controller, $result) = broadcast(array('-1'), array('text' => '  '));
        same(true, $result, 'empty text is a no-op');
        same(array(), $controller->batches, 'empty text sends nothing');
        same(0, Fixture::$selects, 'empty text does not query members');
        list($controller, $result) = broadcast(array('-1'), array('text' => 'notice', 'photo' => 1));
        same(false, $result, 'missing file rejected');
        same(0, Fixture::$selects, 'invalid photo does not query members');
        same('缺少 file_id', Fixture::$logs[0][2], 'invalid photo logged');
        list($controller) = broadcast(array());
        counts(0, 0, 0, 0);
    };
    $tests['transient 429 pauses the entire broadcast then succeeds'] = function () {
        list($controller) = broadcast(ids(11), array('text' => 'notice'), function ($job) {
            if ($job['chat_id'] === '-1' && $job['attempt'] < 2) {
                return rateLimit($job['attempt'] === 0 ? 4 : 2);
            }
            return array('response' => array('ok' => true));
        });
        counts(11, 11, 0, 2);
        same(array(0.0, 5.0, 8.0), array_column($controller->batches, 'started'), 'all later work observes retry_after plus one');
        same(array(0, 1, 2), array_column(array_values(array_filter(attempts($controller), function ($job) {
            return $job['chat_id'] === '-1';
        })), 'attempt'), 'only rate limited recipient retries');
    };
    $tests['permanent 429 stops after three retries'] = function () {
        list($controller) = broadcast(array('-1'), array('text' => 'notice'), function ($job) { return rateLimit(2); });
        counts(1, 0, 1, 3);
        same(array(0, 1, 2, 3), array_column(attempts($controller), 'attempt'), 'four attempts maximum');
        same(array(3000000, 3000000, 3000000), Fixture::$sleeps, 'no sleep after final failure');
    };
    $tests['exhausted rate limited recipient still pauses remaining groups'] = function () {
        list($controller) = broadcast(ids(11), array('text' => 'notice'), function ($job) {
            if ($job['chat_id'] === '-1') { return rateLimit(7); }
            if ($job['chat_id'] === '-11' && $job['attempt'] < 3) { return rateLimit(1); }
            return array('response' => array('ok' => true));
        });
        counts(11, 10, 1, 6);
        same(array(0.0, 8.0, 16.0, 24.0, 32.0), array_column($controller->batches, 'started'), 'global cooldown includes exhausted chat');
    };
    $tests['invalid chat cleanup and permanent or ambiguous errors never retry'] = function () {
        $responses = array(
            '-1' => array('ok' => false, 'error_code' => 400, 'description' => 'Bad Request: chat not found'),
            '-2' => array('ok' => false, 'error_code' => 403, 'description' => 'Forbidden: bot was kicked from the group chat'),
            '-3' => array('ok' => false, 'error_code' => 500, 'description' => 'Internal Server Error'),
            '-4' => null,
            '-5' => array('ok' => false, 'error_code' => 400, 'description' => 'Bad Request: message is too long')
        );
        list($controller) = broadcast(array_merge(ids(5), array('-1')), array('text' => 'notice'), function ($job) use ($responses) {
            return array('response' => $responses[$job['chat_id']], 'curl_errno' => $job['chat_id'] === '-4' ? 28 : 0);
        });
        counts(5, 0, 5, 0);
        same(5, count(attempts($controller)), 'all non429 errors attempt once');
        same(array(array('where' => array('telegram_id' => '-1'), 'data' => array('telegram_id' => ''))), Fixture::$saved, 'only exact invalid chat binding cleared');
        same('', Fixture::$members[0]['telegram_id'], 'first invalid binding cleared');
        same('', Fixture::$members[5]['telegram_id'], 'duplicate invalid binding cleared');
        same('-2', Fixture::$members[1]['telegram_id'], 'forbidden group retained');
        $failures = array_values(array_filter(Fixture::$logs, function ($entry) { return $entry[1] === '发送失败'; }));
        same(5, count($failures), 'one log per failed recipient');
        same(28, json_decode($failures[3][2], true)['curl_errno'], 'transport failure diagnostic recorded');
    };
    $tests['actual cURL batch helper runs concurrently and encodes text safely'] = function () {
        Fixture::reset();
        Fixture::$wireResponses['-2'] = array('raw' => 'invalid JSON', 'errno' => 28, 'error' => 'timeout at botfixture-token/sendMessage');
        $controller = new TestBroadcastController();
        $jobs = array_map(function ($chatId) { return array('chat_id' => $chatId, 'attempt' => 2); }, ids(10));
        $results = $controller->transport($jobs, '通知 & a=b+hello');
        same(10, count($results), 'each transport result returned');
        same(array(10, 10, 10), Fixture::$multi->activeCounts, 'all handles added before multi execution');
        same(array(10000), Fixture::$sleeps, 'select minus one avoids busy loop');
        same(10, Fixture::$multi->removed, 'all handles removed');
        same(true, Fixture::$multi->closed, 'multi handle closed');
        foreach (Fixture::$handles as $i => $handle) {
            same('https://api.telegram.org/botfixture-token/sendMessage', $handle->url, 'text endpoint');
            parse_str($handle->options[CURLOPT_POSTFIELDS], $data);
            same(array('chat_id' => '-' . ($i + 1), 'text' => '通知 & a=b+hello'), $data, 'form payload round trip');
            same(true, $handle->options[CURLOPT_SSL_VERIFYPEER], 'certificate verification enabled');
            same(2, $handle->options[CURLOPT_SSL_VERIFYHOST], 'hostname verification enabled');
            same(5, $handle->options[CURLOPT_CONNECTTIMEOUT], 'connect timeout bounded');
            same(15, $handle->options[CURLOPT_TIMEOUT], 'request timeout bounded');
            same(true, $handle->closed, 'request handle closed');
            same($jobs[$i], $results[$i]['job'], 'retry metadata preserved');
        }
        same(array('ok' => true), $results[0]['response'], 'JSON decoded');
        same(null, $results[1]['response'], 'invalid JSON remains an ambiguous failure');
        same(28, $results[1]['curl_errno'], 'curl errno returned');
        same('timeout at bot[REDACTED]/sendMessage', $results[1]['curl_error'], 'token redacted from curl error');
    };
    $tests['actual photo transport sends file id and caption'] = function () {
        Fixture::reset();
        $controller = new TestBroadcastController();
        $controller->transport(array(array('chat_id' => '-1001234567890', 'attempt' => 0)), '图片通知 & hi', true, 'file+id=abc');
        $handle = Fixture::$handles[0];
        same('https://api.telegram.org/botfixture-token/sendPhoto', $handle->url, 'photo endpoint');
        parse_str($handle->options[CURLOPT_POSTFIELDS], $data);
        same(array('chat_id' => '-1001234567890', 'photo' => 'file+id=abc', 'caption' => '图片通知 & hi'), $data, 'photo payload round trip');
    };

    $failed = 0;
    foreach ($tests as $name => $test) {
        try {
            $test();
            echo "PASS [$module] $name\n";
        } catch (\Throwable $error) {
            $failed++;
            fwrite(STDERR, "FAIL [$module] $name: " . $error->getMessage() . "\n");
        }
    }
    echo ($failed ? 'FAILED' : 'OK') . " [$module] (" . count($tests) . " tests)\n";
    exit($failed ? 1 : 0);
}
