<?php
// Offline regression test: php api/Tests/DailyPartitionLifecycleTest.php [api|open|user|glht]
namespace Think {
    class Model {
        protected $db;
        protected $options = array();
        public function __construct() { $this->db = new \PartitionFakeDb(); }
        public function table($table) { $this->options['table'] = $table; return $this; }
        protected function _parseOptions($options = array()) { return array_merge($this->options, $options); }
        public function query($sql) { return array(); }
    }
    class Log {
        public static $messages = array();
        public static function write($message, $level) { self::$messages[] = $message; }
    }
    class Db {
        public static $config;
        public static $driver;
        public static function getInstance($config) {
            self::$config = $config;
            return self::$driver;
        }
    }
}
namespace {
    function C($key) { return $key === 'DB_PARAMS' ? array(\PDO::ATTR_PERSISTENT => true) : null; }
    function check($condition, $message) {
        if (!$condition) { throw new \RuntimeException($message); }
    }
    class PartitionFakeDb {
        public $tables = array();
        public $sql = array();
        public $queries = 0;
        public $failQuery = false;
        public $failCreate = false;
        public $throwCreate = false;
        public $race = false;
        public function query($sql, $fetchSql = false, $master = false) {
            check($master, 'Partition metadata and ID reads must use the master');
            $this->queries++;
            if ($this->failQuery) { return false; }
            if (strpos($sql, 'SHOW TABLES') === 0) {
                return array_map(function ($table) { return array('table' => $table); }, array_keys($this->tables));
            }
            preg_match('/FROM `([^`]+)`/', $sql, $match);
            check(isset($this->tables[$match[1]]), 'ID scan queried a missing table');
            return $this->tables[$match[1]] ? array(array('id' => $this->tables[$match[1]])) : array();
        }
        public function execute($sql) {
            $this->sql[] = $sql;
            if ($this->throwCreate) { throw new \RuntimeException('simulated DDL exception'); }
            if ($this->failCreate) { return false; }
            check(strpos($sql, 'CREATE TABLE IF NOT EXISTS') === 0, 'DDL must be idempotent');
            preg_match('/EXISTS `([^`]+)`/', $sql, $match);
            if ($this->race) { $this->tables[$match[1]] = 777; }
            if (!isset($this->tables[$match[1]])) { $this->tables[$match[1]] = 0; }
            return 0; // Successful MySQL DDL returns zero, not true.
        }
        public function getError() { return 'simulated database failure'; }
        public function update($data, $options) { return $options; }
    }
    $app = isset($argv[1]) ? $argv[1] : 'api';
    check(in_array($app, array('api', 'open', 'user', 'glht'), true), 'Unknown app');
    $root = dirname(__DIR__, 2) . '/' . $app . '/Application/Common/Model/';
    require $root . 'DailyPartitionModel.class.php';
    foreach (array('Order', 'Wttklist', 'Moneychange') as $modelName) {
        require $root . $modelName . 'Model.class.php';
        $class = 'Test' . $modelName;
        eval('class ' . $class . ' extends \\Common\\Model\\' . $modelName . 'Model {
            public $today = "20260907";
            protected function partitionDate() { return $this->today; }
            protected function loadEnv() { return 20251016; }
            public function cachedTables() { return $this->orderTables; }
            public function parseNow() { return $this->_parseOptions(); }
            public function businessDb() { return $this->db; }
        }');
        $prefix = 'pay_' . strtolower($modelName);
        $db = new PartitionFakeDb();
        $db->tables = array($prefix => 12, $prefix . '0906' => 100,
            $prefix . '0905' => 0, $prefix . '_backup' => 9999);
        \Think\Db::$driver = $db;
        $model = new $class();
        check(count($db->sql) === 1 && strpos($db->sql[0], 'AUTO_INCREMENT=101 ') !== false,
            'Retain max historical ID + 1, safely handle empty tables and exclude backups');
        check($model->businessDb() !== $db, 'DDL must not use the business driver');
        check(\Think\Db::$config['DB_PARAMS'][\PDO::ATTR_PERSISTENT] === false,
            'DDL connection must disable persistent PDO reuse');
        check(!empty(\Think\Db::$config['DAILY_PARTITION_CONNECTION']), 'Separate driver cache key required');
        check(!isset($db->tables[$prefix . '0908']), 'Must not precreate tomorrow');
        $queries = $db->queries;
        $model->table($prefix . '0907');
        $model->getTables();
        check($queries === $db->queries, 'Same-day checks should use the success cache');

        $model->today = '20260908';
        $db->tables[$prefix . '0907'] = 150;
        $model->parseNow(); // Selected before midnight; execute after midnight.
        check(isset($db->tables[$prefix . '0908']), 'Execution must recheck after midnight');
        check(strpos($db->sql[1], 'AUTO_INCREMENT=151 ') !== false, 'Rollover must rescan current IDs');
        $model->table($prefix . '0907');
        check($model->parseNow()['table'] === $prefix . '0907', 'Historical callbacks must keep their target');

        $model->today = '20260909';
        $db->race = true;
        $model->getTables();
        check($db->tables[$prefix . '0909'] === 777, 'Concurrent winner data must be preserved');
        check(in_array($prefix . '0909', $model->cachedTables(), true), 'Concurrent creation must refresh cache');
        $db->race = false;

        $model->today = '20260910';
        $db->failCreate = true;
        try { $model->table($prefix . '0910'); throw new \LogicException('DDL failure was ignored'); }
        catch (\RuntimeException $error) { check(strpos($error->getMessage(), 'unavailable') !== false, 'Failure context missing'); }
        check(!in_array($prefix . '0910', $model->cachedTables(), true), 'Failed creation must not poison cache');
        check(strpos(end(\Think\Log::$messages), 'date=20260910') !== false, 'Failure must log date');
        $db->failCreate = false;
        $model->table($prefix . '0910');
        check(isset($db->tables[$prefix . '0910']), 'Failed creation must be retryable');

        $model->today = '20260911';
        $db->failQuery = true;
        $ddlCount = count($db->sql);
        try { $model->getTables(); throw new \LogicException('Metadata failure was ignored'); }
        catch (\RuntimeException $error) {}
        check(count($db->sql) === $ddlCount, 'Metadata failure must not be interpreted as absent table');
        $db->failQuery = false;
        $db->throwCreate = true;
        try { $model->getTables(); throw new \LogicException('DDL exception was ignored'); }
        catch (\RuntimeException $error) { check($error->getPrevious() !== null, 'Keep underlying error'); }
        $db->throwCreate = false;
        $model->getTables();
        check(isset($db->tables[$prefix . '0911']), 'Exception must not prevent retry');
        echo "PASS $app/$modelName lifecycle\n";
    }
}
