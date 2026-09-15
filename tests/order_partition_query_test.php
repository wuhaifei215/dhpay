<?php
// Run with: php -n tests/order_partition_query_test.php
// This test uses model stubs and never connects to MySQL or external services.
namespace Common\Model {
    abstract class DailyPartitionModel
    {
        public $capturedTable;
        public $capturedField;
        public $capturedWhere;
        public $findResult = array('id' => 1);

        public function __construct() {}
        protected function createTable() {}
        public function table($table) { $this->capturedTable = $table; return $this; }
        public function field($field) { $this->capturedField = $field; return $this; }
        public function where($where) { $this->capturedWhere = $where; return $this; }
        public function find() { return $this->findResult; }
    }
}

namespace {
    error_reporting(E_ALL);
    set_error_handler(function ($level, $message) {
        throw new \RuntimeException($message);
    });

    $checks = 0;

    function E($message)
    {
        throw new \RuntimeException($message);
    }

    function check($condition, $message)
    {
        $GLOBALS['checks']++;
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    require __DIR__ . '/../glht/Application/Common/Model/OrderModel.class.php';
    require __DIR__ . '/../glht/Application/Common/Model/WttklistModel.class.php';

    class TestOrderModel extends \Common\Model\OrderModel
    {
        public $capturedSql;
        public $queryResult = array();

        public function configure($tables, $expireDate)
        {
            $this->orderTables = $tables;
            $this->expire_date = $expireDate;
        }

        public function query($sql, $fetchSql = false, $master = false)
        {
            $this->capturedSql = $sql;
            return $this->queryResult;
        }
    }

    class TestWttklistModel extends \Common\Model\WttklistModel
    {
        public $capturedSql;
        public $queryResult = array();

        public function configure($tables, $expireDate)
        {
            $this->orderTables = $tables;
            $this->expire_date = $expireDate;
        }

        public function query($sql, $fetchSql = false, $master = false)
        {
            $this->capturedSql = $sql;
            return $this->queryResult;
        }
    }

    $orderModel = new TestOrderModel();
    $orderModel->configure(array(
        'pay_order',
        'pay_order0915',
        'pay_order0914',
        'pay_order0913',
        'pay_order0912',
        'pay_order0911',
        'pay_order0910',
        'pay_order0909',
        'pay_order0908',
    ), 20251016);

    check($orderModel->getDateFromOrderId('202609151234567890') === '20260915',
        'Valid collection order id date is extracted');
    check($orderModel->getDateFromOrderId('202602301234567890') === false,
        'Invalid collection calendar date is rejected');
    check($orderModel->getDateFromOrderId('bad-order-id') === false,
        'Invalid collection order id is rejected');
    check($orderModel->getDateFromOrderId(array('20260915')) === false,
        'Non-scalar collection order id is rejected');

    $orderModel->capturedSql = null;
    $order = $orderModel->findByOrderId(
        '202609151234567890',
        'id,pay_orderid',
        array('pay_memberid' => 10001, 'currency' => 'PHP')
    );
    check($order['id'] === 1, 'Collection single-table lookup returns model result');
    check($orderModel->capturedTable === 'pay_order0915',
        'Collection order id routes to one daily table');
    check($orderModel->capturedWhere['pay_memberid'] === 10001,
        'Collection lookup preserves merchant isolation');
    check($orderModel->capturedWhere['currency'] === 'PHP',
        'Collection lookup preserves dhpay2 currency isolation');
    check($orderModel->capturedWhere['pay_orderid'] === array('eq', '202609151234567890'),
        'Collection lookup adds exact system order id');
    check($orderModel->capturedSql === null,
        'Collection exact lookup bypasses UNION generation');

    $orderModel->findByOrderId('202510151234567890');
    check($orderModel->capturedTable === 'pay_order',
        'Collection order before partition cutover routes to legacy table');

    $twoDayOrderOptions = array(
        'pay_applydate' => array('between', array(
            strtotime('2026-09-14 00:00:00'),
            strtotime('2026-09-15 23:59:59'),
        )),
    );
    $orderModel->getOrderByDateRange(
        'id,pay_orderid',
        $twoDayOrderOptions,
        '0,50',
        'id desc',
        '',
        array('max_days' => 7, 'max_execution_time' => 30000)
    );
    check(strpos($orderModel->capturedSql, 'MAX_EXECUTION_TIME(30000)') !== false,
        'Collection online SQL has a 30-second timeout');
    check(substr_count($orderModel->capturedSql, ' UNION ALL ') === 1,
        'Collection two-day query has one UNION ALL');
    check(strpos($orderModel->capturedSql, 'ORDER BY id desc LIMIT 0,50') !== false,
        'Collection ordering and pagination are preserved');

    try {
        $orderModel->getOrderByDateRange(
            'id',
            array('pay_applydate' => array('between', array(
                strtotime('2026-09-08 00:00:00'),
                strtotime('2026-09-15 23:59:59'),
            ))),
            '',
            '',
            '',
            array('max_days' => 7, 'max_execution_time' => 30000)
        );
        check(false, 'Collection eight-day online query was accepted');
    } catch (\RuntimeException $error) {
        check(strpos($error->getMessage(), '最多支持7天') !== false,
            'Collection eight-day online query is rejected');
    }

    $orderModel->getOrderByDateRange('id', $twoDayOrderOptions);
    check(strpos($orderModel->capturedSql, 'MAX_EXECUTION_TIME') === false,
        'Legacy collection callers remain unchanged without query options');

    $withdrawalModel = new TestWttklistModel();
    $withdrawalModel->configure(array(
        'pay_wttklist',
        'pay_wttklist0915',
        'pay_wttklist0914',
        'pay_wttklist0913',
        'pay_wttklist0912',
        'pay_wttklist0911',
        'pay_wttklist0910',
        'pay_wttklist0909',
        'pay_wttklist0908',
    ), 20251016);

    check($withdrawalModel->getDateFromOrderId('Q202609151234567890') === '20260915',
        'Valid withdrawal order id date is extracted after its prefix');
    check($withdrawalModel->getDateFromOrderId('q202609151234567890') === false,
        'Lowercase withdrawal prefix is rejected');
    check($withdrawalModel->getDateFromOrderId('Q202602301234567890') === false,
        'Invalid withdrawal calendar date is rejected');

    $withdrawalModel->capturedSql = null;
    $withdrawal = $withdrawalModel->findByOrderId(
        'Q202609151234567890',
        'id,orderid,status,money',
        array('userid' => 1, 'currency' => 'PHP')
    );
    check($withdrawal['id'] === 1, 'Withdrawal single-table lookup returns model result');
    check($withdrawalModel->capturedTable === 'pay_wttklist0915',
        'Withdrawal order id routes to one daily table');
    check($withdrawalModel->capturedWhere['userid'] === 1,
        'Withdrawal lookup preserves user isolation');
    check($withdrawalModel->capturedWhere['currency'] === 'PHP',
        'Withdrawal lookup preserves dhpay2 currency isolation');
    check($withdrawalModel->capturedSql === null,
        'Withdrawal exact lookup bypasses UNION generation');
    check(count($withdrawalModel->getDateRange(
        '2026-09-09 00:00:00',
        '2026-09-15 23:59:59'
    )) === 7, 'Withdrawal seven-day range generates exactly seven dates');

    $withdrawalModel->getOrderByDateRange(
        'id,orderid',
        array('sqdatetime' => array('between', array(
            '2026-09-14 00:00:00',
            '2026-09-15 23:59:59',
        ))),
        '0,15',
        'id desc',
        '',
        array('max_days' => 7, 'max_execution_time' => 30000)
    );
    check(strpos($withdrawalModel->capturedSql, 'MAX_EXECUTION_TIME(30000)') !== false,
        'Withdrawal online SQL has a 30-second timeout');
    check(substr_count($withdrawalModel->capturedSql, ' UNION ALL ') === 1,
        'Withdrawal two-day query has one UNION ALL');

    foreach (array('glht', 'api', 'open', 'user') as $root) {
        $orderSource = file_get_contents(
            __DIR__ . '/../' . $root . '/Application/Common/Model/OrderModel.class.php'
        );
        $withdrawalSource = file_get_contents(
            __DIR__ . '/../' . $root . '/Application/Common/Model/WttklistModel.class.php'
        );
        check(strpos($orderSource, 'function findByOrderId(') !== false
            && strpos($orderSource, "'max_execution_time'") !== false,
            $root . ' collection model has single-table and timeout protection');
        check(strpos($orderSource, "rtrim(\$unionSql, 'UNION") === false,
            $root . ' collection model uses safe UNION construction');
        check(strpos($withdrawalSource, 'function findByOrderId(') !== false
            && strpos($withdrawalSource, "'max_execution_time'") !== false,
            $root . ' withdrawal model has single-table and timeout protection');
        check(strpos($withdrawalSource,
            'floor(($etimestamp - $stimestamp) / 86400) + 1') === false,
            $root . ' withdrawal model does not add an extra date');
    }

    $adminOrderController = file_get_contents(
        __DIR__ . '/../glht/Application/Admin/Controller/OrderController.class.php'
    );
    $adminWithdrawalController = file_get_contents(
        __DIR__ . '/../glht/Application/Admin/Controller/WithdrawalController.class.php'
    );
    foreach (array('glht', 'user') as $root) {
        $userOrderController = file_get_contents(
            __DIR__ . '/../' . $root . '/Application/User/Controller/OrderController.class.php'
        );
        $userWithdrawalController = file_get_contents(
            __DIR__ . '/../' . $root . '/Application/User/Controller/WithdrawalController.class.php'
        );
        check(strpos($userOrderController,
            "\$where['pay_memberid'] = 10000 + \$this->fans['uid'];") !== false,
            $root . ' user order controller retains merchant isolation');
        check(strpos($userOrderController, 'findByOrderId($orderid') !== false,
            $root . ' user order list uses single-table lookup');
        check(strpos($userWithdrawalController,
            "\$where['userid'] = \$this->fans['uid'];") !== false,
            $root . ' user withdrawal controller retains user isolation');
        check(strpos($userWithdrawalController, 'findByOrderId($orderid') !== false,
            $root . ' user withdrawal list uses single-table lookup');
    }
    check(substr_count($adminOrderController, 'findByOrderId(') >= 2,
        'Admin normal and frozen order lists use single-table lookup');
    check(strpos($adminOrderController, 'findByOrderId($payOrderid') !== false,
        'Admin frozen list routes by the dhpay2 system order id field');
    check(strpos($adminWithdrawalController, 'findByOrderId($orderid') !== false,
        'Admin withdrawal list uses single-table lookup');

    echo 'OK (' . $checks . " behavioral checks)\n";
}
