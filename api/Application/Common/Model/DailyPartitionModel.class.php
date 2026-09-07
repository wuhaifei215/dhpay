<?php
namespace Common\Model;

use Think\Model;

/** Daily table lifecycle shared by orders, payouts and balance logs. */
abstract class DailyPartitionModel extends Model
{
    protected $partitionDb;
    protected $checkedPartitionDate;

    abstract protected function buildPartitionSql($tableName, $newId);

    protected function partitionDate()
    {
        return date('Ymd');
    }

    protected function getPartitionDb()
    {
        if (!$this->partitionDb) {
            // A separate, non-persistent connection keeps DDL out of payment transactions.
            $config = array();
            foreach (array('TYPE', 'USER', 'PWD', 'HOST', 'PORT', 'NAME', 'DSN',
                'PARAMS', 'CHARSET', 'DEPLOY_TYPE', 'RW_SEPARATE', 'MASTER_NUM',
                'SLAVE_NO', 'DEBUG', 'LITE') as $key) {
                $config['DB_' . $key] = C('DB_' . $key);
            }
            $config['DB_PARAMS'] = (array) $config['DB_PARAMS'];
            $config['DB_PARAMS'][\PDO::ATTR_PERSISTENT] = false;
            // Db caches drivers by the entire config, including this discriminator.
            $config['DAILY_PARTITION_CONNECTION'] = true;
            $this->partitionDb = \Think\Db::getInstance($config);
        }
        return $this->partitionDb;
    }

    protected function partitionQuery($sql)
    {
        $db = $this->getPartitionDb();
        $rows = $db->query($sql, false, true);
        if ($rows === false) {
            throw new \RuntimeException('Partition query failed: ' . $db->getError());
        }
        return $rows;
    }

    protected function refreshPartitionTables()
    {
        $prefix = $this->tablePrefix . $this->tableName;
        $rows = $this->partitionQuery("SHOW TABLES LIKE '" . $prefix . "%'");
        $tables = array();
        foreach ($rows as $row) {
            $table = reset($row);
            // LIKE treats underscores as wildcards; accept only this model's tables.
            if (preg_match('/^' . preg_quote($prefix, '/') . '(?:[0-9]{4})?$/D', $table)) {
                $tables[] = $table;
            }
        }
        $this->orderTables = array_reverse($tables);
    }

    protected function createTable()
    {
        $date = $this->partitionDate();
        if ($this->checkedPartitionDate === $date) {
            return;
        }
        $tableName = $this->getRealTableName($date);
        try {
            $this->refreshPartitionTables();
            if (!in_array($tableName, $this->orderTables, true)) {
                $ids = $this->getLastIds();
                $newId = $ids ? max($ids) + 1 : 1;
                $db = $this->getPartitionDb();
                // Another request may create the table after our existence check.
                if ($db->execute($this->buildPartitionSql($tableName, $newId)) === false) {
                    throw new \RuntimeException('Partition creation failed: ' . $db->getError());
                }
                $this->refreshPartitionTables();
                if (!in_array($tableName, $this->orderTables, true)) {
                    throw new \RuntimeException('Created partition is not visible');
                }
            }
            // Never cache failures; a later call must be able to retry.
            $this->checkedPartitionDate = $date;
        } catch (\Throwable $error) {
            \Think\Log::write('Daily partition failed; table=' . $tableName
                . '; date=' . $date . '; error=' . $error->getMessage(), 'ERR');
            throw new \RuntimeException('Daily partition unavailable: ' . $tableName, 0, $error);
        }
    }

    public function getLastIds($returnAllArray = false)
    {
        $ids = array();
        $all = array();
        foreach ($this->orderTables as $key => $table) {
            $rows = $this->partitionQuery('SELECT id FROM `' . $table . '` ORDER BY id DESC LIMIT 1');
            $id = isset($rows[0]['id']) ? $rows[0]['id'] + 0 : 0;
            $ids[] = $id;
            $all[$key] = array('table' => $table, 'id' => $id);
        }
        return $returnAllArray ? $all : $ids;
    }

    public function table($table)
    {
        $this->createTable();
        return parent::table($table);
    }

    protected function _parseOptions($options = array())
    {
        // Also cover a model/table selected before midnight and executed afterwards.
        $this->createTable();
        return parent::_parseOptions($options);
    }
}
