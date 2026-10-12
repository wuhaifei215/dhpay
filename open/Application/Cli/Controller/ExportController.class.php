<?php

namespace Cli\Controller;

require_once dirname(__DIR__, 4) . '/shared/ExportTask.php';
use Dhpay2\Security\ExportTask;

use \think\Controller;
use Think\Log;

class ExportContinue extends \RuntimeException { }

/**
 * @author mapeijian
 * @date   2018-06-06
 */
class ExportController extends Controller
{
    public $_site='';
    protected $exportDeadline = 0;
    protected $exportTaskToken = '';
    protected $exportTempDir = '';
    protected $exportArchive = '';
    protected $exportMembers = array();
    protected $exportHandles = array();
    protected $exportJob = null;
    protected $exportRedis = null;
    protected $exportKey = null;
    protected $exportState = null;
    protected $exportBatchesThisRun = 0;
    public function __construct()
    {
        if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
        parent::__construct();
        //自动创建日志文件
        $filePath = './Runtime/Logs/Cli/';
        if(@mkdirs($filePath, 777)) {
            $destination = $filePath.'cli_export.log';
            if(!file_exists($destination)) {
                $handle = @fopen($destination,   'wb ');
                @fclose($handle);
            }
        }
        $this->_site= 'https://' . C("DOMAIN") . '/';
        ini_set('default_charset', 'UTF-8');
        ini_set('memory_limit', '512M');
    }
    public function index()
    {
        $lockPath = $this->exportLockPath();
        if (!is_dir(dirname($lockPath)) && !mkdir(dirname($lockPath), 0750, true) && !is_dir(dirname($lockPath))) {
            throw new \RuntimeException('无法创建导出锁目录');
        }
        $lock = fopen($lockPath, 'c');
        if (!$lock) { throw new \RuntimeException('无法创建导出执行锁'); }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            echo "已有导出任务执行中，跳过本次调度\n";
            return;
        }
        $redis = null;
        $downKey = null;
        $job = null;
        $keepPartialExport = false;
        $removeRunningMarker = true;
        try {
            $redis = $this->redis_connect();
            $this->recoverInterruptedJobs($redis);
            // Bound cleanup work: a poisoned backlog cannot monopolize one invocation.
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $downKey = $this->claimExportJob($redis);
                if (!$downKey) { return; }
                if (!ExportTask::validKey($downKey)) {
                    Log::record('Rejected malformed export key sha256=' . hash('sha256', $downKey), 'ERR');
                    $redis->lRem('downList_now', $downKey, 0);
                    continue;
                }
                $raw = $redis->get($downKey);
                if (!$raw) { $redis->lRem('downList_now', $downKey, 0); continue; }
                $candidate = json_decode($raw, true);
                try {
                    $candidate = ExportTask::validate($downKey, $candidate, ExportTask::secret());
                    if (!isset($candidate['status']) || $candidate['status'] !== 0) {
                        $redis->lRem('downList_now', $downKey, 0);
                        continue;
                    }
                } catch (\InvalidArgumentException $e) {
                    if (is_array($candidate)) {
                        $candidate['status'] = -1;
                        $candidate['error'] = $e->getMessage();
                        $this->saveExportJob($redis, $downKey, $candidate);
                    }
                    Log::record('Rejected export task sha256=' . hash('sha256', $downKey), 'ERR');
                    $redis->lRem('downList_now', $downKey, 0);
                    continue;
                }
                $job = $candidate;
                break;
            }
            if (!$job) { return; }
            $this->exportRedis = $redis;
            $this->exportKey = $downKey;
            $this->exportJob = $job;
            $this->exportState = isset($job['export_state']) ? $job['export_state'] : array(
                'table' => 0, 'cursor' => null, 'part' => 0, 'rows' => 0, 'bytes' => 0, 'tables_hash' => null);
            $job['export_state'] = $this->exportState;
            $job['status'] = 1;
            $job['started_at'] = time();
            $this->exportJob = $job;
            $this->saveExportJob($redis, $downKey, $job);
            $sliceSeconds = C('EXPORT_SLICE_SECONDS') ?: 60;
            $this->exportDeadline = time() + min(300, max(10, (int)$sliceSeconds));
            $this->exportTaskToken = substr(hash('sha256', $downKey), 0, 32);
            $this->exportTempDir = './Runtime/Temp/export_' . $this->exportTaskToken;
            if (is_link($this->exportTempDir) || (!is_dir($this->exportTempDir) &&
                !mkdir($this->exportTempDir, 0700, true) && !is_dir($this->exportTempDir))) {
                throw new \RuntimeException('无法创建导出临时目录');
            }
            if ($job['type'] === 1 || $job['type'] === 2) {
                $result = $this->exportorder($job['where'], $job['uid'], $job['type']);
            } elseif ($job['type'] === 3 || $job['type'] === 4) {
                $result = $this->exportweituo($job['where'], $job['uid'], $job['type']);
            } elseif ($job['type'] === 5 || $job['type'] === 6) {
                $result = $this->exceldownload($job['where'], $job['uid'], $job['type']);
            } else {
                $result = $this->exportUtrOrder($job['where'], $job['uid'], $job['type']);
            }
            $job = $this->exportJob;
            if ($result === false || $result === null || $result === '') {
                throw new \RuntimeException('导出未生成有效文件');
            }
            $job['status'] = 2;
            $job['url'] = $result === 'noData' ? 'noData' : $this->_site . $result;
            $job['finished_at'] = time();
            $this->saveExportJob($redis, $downKey, $job);
        } catch (ExportContinue $e) {
            $keepPartialExport = true;
            $removeRunningMarker = false;
            $job = $this->exportJob;
            $redis->lRem('downList', $downKey, 0);
            if ($redis->rPush('downList', $downKey) === false || !$redis->expire('downList', ExportTask::TTL)) {
                throw new \RuntimeException('无法重新排队导出任务');
            }
            $job['status'] = 0;
            $job['updated_at'] = time();
            $this->saveExportJob($redis, $downKey, $job);
            $removeRunningMarker = true;
        } catch (\Throwable $e) {
            $job = $this->exportJob ?: $job;
            $failureSaved = false;
            if ($redis && $job && $downKey) {
                $job['status'] = -1;
                $job['error'] = $e instanceof \RuntimeException && !($e instanceof \RedisException) ? $e->getMessage() : '导出失败，请缩小时间范围后重试';
                $job['finished_at'] = time();
                try { $this->saveExportJob($redis, $downKey, $job); $failureSaved = true; }
                catch (\Throwable $ignored) { $keepPartialExport = true; $removeRunningMarker = false; }
            }
            if ($failureSaved && $this->exportArchive && is_file($this->exportArchive)) { unlink($this->exportArchive); }
            Log::record('Export failed: ' . get_class($e), 'ERR');
        } finally {
            if ($removeRunningMarker && $redis && $job && $downKey) {
                try {
                    $redis->lRem('downList_now', $downKey, 0);
                } catch (\Throwable $ignored) { }
            }
            foreach ($this->exportHandles as $handle) { if (is_resource($handle)) { fclose($handle); } }
            $this->exportHandles = array();
            if (!$keepPartialExport && $this->exportTempDir && is_dir($this->exportTempDir)) {
                foreach (glob($this->exportTempDir . '/*') as $file) { if (is_file($file)) { unlink($file); } }
                rmdir($this->exportTempDir);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function recoverInterruptedJobs($redis)
    {
        // This runs only after the shared host lock is acquired: no upgraded local worker is running.
        foreach ($redis->lRange('downList_now', 0, 49) as $key) {
            if (ExportTask::validKey($key)) {
                $job = json_decode($redis->get($key), true);
                if (is_array($job) && isset($job['status']) && ($job['status'] === 1 || $job['status'] === 0)) {
                    if (isset($job['export_state']) && is_array($job['export_state'])) {
                        $job['status'] = 0;
                        $job['updated_at'] = time();
                        $redis->lRem('downList', $key, 0);
                        if ($redis->rPush('downList', $key) === false || !$redis->expire('downList', ExportTask::TTL)) {
                            throw new \RuntimeException('无法恢复导出任务');
                        }
                    } else {
                        $job['status'] = -1;
                        $job['error'] = '上次导出异常中断，请重新提交';
                        $job['finished_at'] = time();
                    }
                    $this->saveExportJob($redis, $key, $job);
                }
            }
            $redis->lRem('downList_now', $key, 0);
        }
    }

    protected function claimExportJob($redis)
    {
        $script = <<<'LUA'
local key = redis.call('LPOP', KEYS[1])
if key then
    redis.call('RPUSH', KEYS[2], key)
    redis.call('EXPIRE', KEYS[2], ARGV[1])
end
return key
LUA;
        return $redis->eval($script, array('downList', 'downList_now', ExportTask::TTL), 2);
    }

    protected function exportLockPath()
    {
        // All three applications share this lock, even when cron launches background children.
        return dirname(__DIR__, 4) . '/open/Runtime/export.lock';
    }

    protected function saveExportJob($redis, $key, $job)
    {
        $ttl = $redis->ttl($key);
        if ($ttl <= 0) { throw new \RuntimeException('导出任务已过期'); }
        $payload = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false || !$redis->setex($key, ExportTask::TTL, $payload)) {
            throw new \RuntimeException('无法保存导出任务状态');
        }
    }

    protected function reserveRows($count)
    {
        $maximum = C('EXPORT_MAX_ROWS') ?: 20000000;
        $maximum = min(50000000, max(1, (int)$maximum));
        if ($this->exportState['rows'] + (int)$count > $maximum) {
            throw new \RuntimeException('导出数据量超过单任务上限，请缩小时间范围');
        }
        if ($this->exportState['part'] >= 10000) {
            throw new \RuntimeException('导出分片数超过上限，请增大每批行数或缩小时间范围');
        }
    }

    protected function checkExportDeadline()
    {
        $maxBatches = C('EXPORT_SLICE_BATCHES') ?: 20;
        $maxBatches = min(100, max(1, (int)$maxBatches));
        if ($this->exportBatchesThisRun > 0 &&
            ($this->exportBatchesThisRun >= $maxBatches || time() >= $this->exportDeadline)) {
            throw new ExportContinue('继续下一批导出');
        }
    }

    protected function exportBatchSize()
    {
        return min(5000, max(1, (int)(C('EXPORT_BATCH_ROWS') ?: 5000)));
    }

    protected function saveExportProgress()
    {
        $this->exportJob['export_state'] = $this->exportState;
        $this->exportJob['processed_rows'] = $this->exportState['rows'];
        $this->exportJob['updated_at'] = time();
        $this->saveExportJob($this->exportRedis, $this->exportKey, $this->exportJob);
    }

    protected function prepareExportTables($tables)
    {
        $state = $this->exportState;
        if (!is_array($state)) { throw new \RuntimeException('导出进度异常'); }
        foreach (array('table', 'part', 'rows', 'bytes') as $field) {
            if (!isset($state[$field]) || !is_int($state[$field]) || $state[$field] < 0) {
                throw new \RuntimeException('导出进度异常');
            }
        }
        if ($state['table'] > count($tables) || $state['part'] > 10000 || $state['rows'] > 50000000 ||
            $state['bytes'] > 53687091200 ||
            (!array_key_exists('cursor', $state) || (!is_null($state['cursor']) &&
                (!is_string($state['cursor']) || !preg_match('/^[1-9][0-9]{0,19}$/D', $state['cursor']))))) {
            throw new \RuntimeException('导出进度异常');
        }
        $hash = hash('sha256', json_encode(array_values($tables)));
        if (!array_key_exists('tables_hash', $state)) { throw new \RuntimeException('导出进度异常'); }
        if ($state['tables_hash'] === null) {
            $this->exportState['tables_hash'] = $hash;
            $this->saveExportProgress();
        } elseif (!is_string($state['tables_hash']) || !hash_equals($hash, $state['tables_hash'])) {
            throw new \RuntimeException('导出数据表发生变化，请重新提交');
        }
    }

    protected function fetchExportBatch($model, $table, $where, $fields, $tableIndex)
    {
        if ($this->exportState['table'] !== $tableIndex) { return array(); }
        $this->checkExportDeadline();
        $queryWhere = $where;
        if ($this->exportState['cursor'] !== null) {
            $queryWhere['id'] = array('lt', $this->exportState['cursor']);
        }
        $query = $table === null ? $model : $model->table($table);
        $rows = $query->field($fields)->where($queryWhere)->order('id desc')->limit(0, $this->exportBatchSize())->select();
        if (!is_array($rows)) { throw new \RuntimeException('导出数据库查询失败'); }
        if (!$rows) {
            $this->exportState['table'] = $tableIndex + 1;
            $this->exportState['cursor'] = null;
            $this->saveExportProgress();
            return array();
        }
        $this->reserveRows(count($rows));
        $previous = $this->exportState['cursor'];
        foreach ($rows as $row) {
            if (!isset($row['id']) || !preg_match('/^[1-9][0-9]{0,19}$/D', (string)$row['id']) ||
                ($previous !== null && (strlen((string)$row['id']) > strlen($previous) ||
                    (strlen((string)$row['id']) === strlen($previous) && strcmp((string)$row['id'], $previous) >= 0)))) {
                throw new \RuntimeException('导出分页数据异常');
            }
            $previous = (string)$row['id'];
        }
        return $rows;
    }

    protected function exportPartPath()
    {
        return $this->exportTempDir . '/' . sprintf('part_%08d.csv', $this->exportState['part']);
    }

    protected function commitExportBatch($fp, $rows)
    {
        $path = $this->exportPartPath();
        if (!fflush($fp)) { throw new \RuntimeException('无法写入导出文件'); }
        if (!fclose($fp)) { throw new \RuntimeException('无法关闭导出分片'); }
        array_pop($this->exportHandles);
        $partial = $path . '.partial';
        if (is_file($path)) { unlink($path); }
        if (!rename($partial, $path)) { throw new \RuntimeException('无法保存导出分片'); }
        $bytes = filesize($path);
        if ($bytes === false) { throw new \RuntimeException('无法读取导出分片大小'); }
        $maximum = C('EXPORT_MAX_BYTES') ?: 10737418240;
        $maximum = min(53687091200, max(1, (int)$maximum));
        if ($this->exportState['bytes'] + $bytes > $maximum) {
            throw new \RuntimeException('导出文件超过磁盘配额，请缩小时间范围');
        }
        $last = end($rows);
        $this->exportState['cursor'] = (string)$last['id'];
        $this->exportState['part']++;
        $this->exportState['rows'] += count($rows);
        $this->exportState['bytes'] += $bytes;
        $this->exportBatchesThisRun++;
        $this->saveExportProgress();
    }

    protected function exportFiles()
    {
        $files = array();
        for ($part = 0; $part < $this->exportState['part']; $part++) {
            $path = $this->exportTempDir . '/' . sprintf('part_%08d.csv', $part);
            if (!is_file($path)) { throw new \RuntimeException('导出分片丢失，请重新提交'); }
            $files[] = $path;
        }
        return $files;
    }

    protected function finishExport($name)
    {
        if ($this->exportState['part'] === 0) { return 'noData'; }
        $archive = $name . '.zip';
        if (is_file($archive)) {
            $zip = new \ZipArchive();
            if ($zip->open($archive) === true) {
                $complete = $zip->numFiles === $this->exportState['part'];
                $zip->close();
                if ($complete) { return $archive; }
            }
        }
        return $this->packExport($name, $this->exportFiles());
    }

    protected function openExportCsv($path)
    {
        if (is_link($path) || (is_file($path) && !unlink($path))) {
            throw new \RuntimeException('无法清理未完成的导出分片');
        }
        $handle = fopen($path, 'x');
        if (!$handle) { throw new \RuntimeException('无法创建导出临时文件'); }
        $this->exportHandles[] = $handle;
        return $handle;
    }

    protected function writeExportCsv($handle, $row)
    {
        if (fputcsv($handle, $row) === false) { throw new \RuntimeException('无法写入导出数据'); }
    }

    protected function packExport($name, $files)
    {
        if (!$files) { return 'noData'; }
        $this->checkExportDeadline();
        $zip = new \ZipArchive();
        $this->exportArchive = $name . '.zip.partial';
        if (is_file($this->exportArchive)) { unlink($this->exportArchive); }
        if ($zip->open($this->exportArchive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('无法创建导出文件');
        }
        foreach ($files as $file) {
            if (!$zip->addFile($file, basename($file))) { $zip->close(); throw new \RuntimeException('无法写入导出文件'); }
        }
        if (!$zip->close()) { throw new \RuntimeException('无法完成导出文件'); }
        if (is_file($name . '.zip')) { unlink($name . '.zip'); }
        if (!rename($this->exportArchive, $name . '.zip')) { throw new \RuntimeException('无法完成导出文件'); }
        $this->exportArchive = $name . '.zip';
        foreach ($files as $file) { unlink($file); }
        return $this->exportArchive;
    }

    protected function loadExportMembers($rows, $field, $offset = 0)
    {
        if (count($this->exportMembers) > 10000) { $this->exportMembers = array(); }
        $ids = array();
        foreach ($rows as $row) {
            $id = (int)$row[$field] - $offset;
            if ($id > 0 && !array_key_exists($id, $this->exportMembers)) { $ids[$id] = $id; }
        }
        if (!$ids) { return; }
        $members = M('Member')->field('id,username,groupid')->where(array('id' => array('in', array_values($ids))))->select();
        foreach ($ids as $id) { $this->exportMembers[$id] = array('username' => '-', 'groupid' => 0); }
        foreach ($members ?: array() as $member) { $this->exportMembers[(int)$member['id']] = $member; }
    }

    protected function exportMemberName($id, $parentLabel = false)
    {
        $member = isset($this->exportMembers[$id]) ? $this->exportMembers[$id] : array('username' => '-', 'groupid' => 0);
        return $parentLabel && (int)$member['groupid'] === 1 ? '总管理员' : $member['username'];
    }

    public function redis_connect(){
        //创建一个redis对象
        $redis = new \Redis();
        //连接 Redis 服务
        $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
        //密码验证
        $redis->auth(C('REDIS_PWD'));
        //设置key-value
        // $redis->set('downloadList','111');
        // 设置键的过期时间为3600秒
        // $redis->expire('downloadList',3600);
        //获取value
        // $list = $redis->get('downloadList');

        // 在列表头部插入元素
        // $redis->lPush('list', 'value1');
        // 从列表左侧弹出元素
        // $redis->lPop('list');
        // 在列表尾部插入元素
        // $redis->rPush('list', 'value2');
        // 获取列表的长度
        // $length = $redis->lLen('list');
        // 获取列表的所有元素
        // $list = $redis->lRange('list', 0, -1);
        // 使用Redis的LRANGE命令获取分页数据
        // $list = $redis->lRange('list', $start, $end);
        // 使用SORT命令进行排序，这里以按value字典序升序为例
        // BY参数指定了使用哪个key的value作为sort的依据
        // GET参数指定了使用哪个key的value的哪部分作为排序的值
        // $redis->sort($key, ['by' => $key.*.*, 'get' => '#']));
        return $redis;
    }


    /**
     * 导出交易订单
     * */
    public function exportorder($where, $uid='', $type='')
    {
        if (isset($where['memberid'])) {
            $where['pay_memberid'] = $where['memberid'];
            unset($where['memberid']);
        }
        if (isset($where['status'])) {
            if ($where['status'] == '1or2') {
                $where['pay_status'] = array('between', array('1', '2'));
            }else{
                $where['pay_status'] = $where['status'];
            }
            unset($where['status']);
        }
        if (isset($where['orderid'])) {
            $where['pay_orderid'] = $where['orderid'];
            unset($where['orderid']);
        }
        if (isset($where['tongdao'])) {
            $where['channel_id'] = $where['tongdao'];
            unset($where['tongdao']);
        }
        if (isset($where['bank'])) {
            $where['pay_bankcode'] = $where['bank'];
            unset($where['bank']);
        }
        if (isset($where['createtime'])) {
            list($cstime, $cetime) = explode('|', $where['createtime']);
            $where['pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
            unset($where['createtime']);
        }
        if (isset($where['successtime'])) {
            list($sstime, $setime) = explode('|', $where['successtime']);
            $where['pay_successdate']   = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
            unset($where['successtime']);
        }

        $file_path = "./Uploads/download/". date('Ymd', $this->exportJob['time']) ."/" ;
        @mkdirs($file_path, 0750);
        @chmod($file_path, 0750);
        $fileName = $file_path . $uid . '_order' . $this->exportJob['time'] . '_' . $this->exportTaskToken;
        $title = ['外部订单号', '系统订单号', '商户编号', '商户用户名', '交易金额', '手续费', '实际金额', '提交时间', '成功时间', '状态', '通道'];
        foreach ($title as $tk => $tv){
            $title[$tk]=iconv('utf-8','gb2312//ignore',$tv);
        }
        $filed = "id,out_trade_id,pay_orderid,pay_memberid,pay_amount,pay_poundage,pay_actualamount,pay_applydate,pay_successdate,pay_bankname,pay_zh_tongdao,memberid,pay_status";

        $OrderModel = D('Order');
        $tables = array_values($OrderModel->getTables($where));
        $this->prepareExportTables($tables);
        foreach ($tables as $tableIndex => $table) {
            while ($this->exportState['table'] === $tableIndex) {
                $dataArr = $this->fetchExportBatch($OrderModel, $table, $where, $filed, $tableIndex);
                if (!$dataArr) { break; }
                $fp = $this->openExportCsv($this->exportPartPath() . '.partial');
                $this->writeExportCsv($fp, $title);
                $limit = 5000;
                $cnt = 0;
                $this->loadExportMembers($dataArr ?: array(), 'pay_memberid', 10000);
                foreach ($dataArr as $item) {
                    switch ($item['pay_status']) {
                        case 0:
                            $status = '未处理';
                            break;
                        case 1:
                            $status = '成功，未返回';
                            break;
                        case 2:
                            $status = '成功，已返回';
                            break;
                    }
                    if ($item['pay_successdate']) {
                        $pay_successdate = date('Y-m-d H:i:s', $item['pay_successdate']);
                    } else {
                        $pay_successdate = '---';
                    }
                    $list = [
                        'out_trade_id' => "\t" .$item['out_trade_id'],
                        'pay_orderid' =>"\t" .$item['pay_orderid'],
                        'pay_memberid' => "\t" . $item['pay_memberid'],
                        'username' => "\t" . iconv('utf-8','gb2312//ignore',$this->exportMemberName((int)$item['pay_memberid'] - 10000)),
                        'pay_amount' => $item['pay_amount'],
                        'pay_poundage' => $item['pay_poundage'],
                        'pay_actualamount' => $item['pay_actualamount'],
                        'pay_applydate' => "\t" .date('Y-m-d H:i:s', $item['pay_applydate']),
                        'pay_successdate' => "\t" . iconv('utf-8','gb2312//ignore',$pay_successdate),
                        'pay_status' => "\t" . iconv('utf-8','gb2312//ignore',$status),
                    ];
                    if($type==1){
                        $list['pay_zh_tongdao'] = "\t" . iconv('utf-8','gb2312//ignore',$item['pay_zh_tongdao']);
                    }else{
                        $list['pay_bankname'] = "\t" . iconv('utf-8','gb2312//ignore',$item['pay_bankname']);
                    }
                    $cnt++;
                    if ($limit == $cnt) {
                        //刷新一下输出buffer，防止由于数据过多造成问题
                        if (ob_get_level() > 0) { ob_flush(); }
                        flush();
                        $cnt = 0;
                    }
                    $this->writeExportCsv($fp, $list);
                }
                $this->commitExportBatch($fp, $dataArr);
            }
        }
        return $this->finishExport($fileName);
    }


    // /**
    //  * 导出交易订单
    //  * */
    // public function exportorder($where, $uid='', $type='')
    // {
    //     if($where['memberid']){
    //         $where['pay_order.pay_memberid'] = $where['memberid'];
    //         unset($where['memberid']);
    //     }
    //     if($where['status']){
    //         if ($where['status'] == '1or2') {
    //             $where['pay_order.pay_status'] = array('between', array('1', '2'));
    //         }else{
    //             $where['pay_order.pay_status'] = $where['status'];
    //         }
    //         unset($where['status']);
    //     }
    //     if($where['orderid']){
    //         $where['pay_order.pay_orderid'] = $where['orderid'];
    //         unset($where['orderid']);
    //     }
    //     if($where['tongdao']){
    //         $where['pay_order.channel_id'] = $where['tongdao'];
    //         unset($where['tongdao']);
    //     }
    //     if($where['bank']){
    //         $where['pay_order.pay_bankcode'] = $where['bank'];
    //         unset($where['bank']);
    //     }
    //     $table_where=[];
    //     if ($where['createtime']) {
    //         list($cstime, $cetime) = explode('|', $where['createtime']);
    //         $where['pay_order.pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
    //         $table_where['pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
    //         unset($where['createtime']);
    //     }
    //     if ($where['successtime']) {
    //         list($sstime, $setime) = explode('|', $where['successtime']);
    //         $where['pay_order.pay_successdate']   = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
    //         $table_where['pay_successdate'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
    //         unset($where['successtime']);
    //     }

    //     $file_path = "./Uploads/download/". date('Ymd') ."/" ;
    //     @mkdirs($file_path, 0777);
    //     @chmod($file_path, 0777);
    //     $fileName = $file_path . $uid . '_order' .time();
    //     $fileNameArr = array();
    //     $title = ['外部订单号', '系统订单号', '商户编号', '商户用户名', '交易金额', '手续费', '实际金额', '提交时间', '成功时间', '状态', '通道'];
    //     $filed = "pay_member.username,pay_order.id,pay_order.out_trade_id,pay_order.pay_orderid,pay_order.pay_memberid,pay_order.pay_amount,pay_order.pay_poundage,pay_order.pay_actualamount,pay_order.pay_applydate,pay_order.pay_successdate,pay_order.pay_bankname,pay_order.pay_zh_tongdao,pay_order.memberid,pay_order.pay_status";

    //     $OrderModel = D('Order');
    //     $tables = $OrderModel->getTables($table_where);
    //     foreach ($tables as $table) {
    //         $sqlCount = $OrderModel->table($table)
    //                 ->alias('as pay_order')
    //                 ->join('LEFT JOIN pay_member ON pay_member.id+10000 = pay_order.pay_memberid')
    //                 ->where($where)
    //                 ->count();
    //                 echo $OrderModel->table($table)->getLastSql();
    //                 // echo "<br><br>";
    //         if($sqlCount < 1){
    //             continue;
    //         }

    //         $mark = "order" . str_replace("pay_order", "", $table);
    //         $sqlLimit = 50000;//每次只从数据库取100000条以防变量缓存太大
    //         // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
    //         $limit = 50000;
    //         // buffer计数器
    //         $cnt = 0;
    //         for ($i = 0; $i < ceil($sqlCount / $sqlLimit); $i++) {
    //             $fp = $this->openExportCsv($mark . '_' . $i . '.csv'); //生成临时文件
    //             //     chmod('attack_ip_info_' . $i . '.csv',777);//修改可执行权限
    //             $fileNameArr[] = $mark . '_' .  $i . '.csv';
    //             // 将数据通过fputcsv写到文件句柄
    //             fputcsv($fp, $title);

    //             $dataArr = $OrderModel->table($table)
    //                 ->alias('as pay_order')
    //                 ->join('LEFT JOIN pay_member ON pay_member.id+10000 = pay_order.pay_memberid')
    //                 ->field($filed)->where($where)->order('id desc')
    //                 ->limit($i * $sqlLimit,$sqlLimit)->select();
    //                 // var_dump($dataArr);die;
    //             foreach ($dataArr as $item) {
    //                 switch ($item['pay_status']) {
    //                     case 0:
    //                         $status = '未处理';
    //                         break;
    //                     case 1:
    //                         $status = '成功，未返回';
    //                         break;
    //                     case 2:
    //                         $status = '成功，已返回';
    //                         break;
    //                 }
    //                 if ($item['pay_successdate']) {
    //                     $pay_successdate = date('Y-m-d H:i:s', $item['pay_successdate']);
    //                 } else {
    //                     $pay_successdate = '---';
    //                 }
    //                 $list = [
    //                     'out_trade_id' => "\t" .$item['out_trade_id'],
    //                     'pay_orderid' =>"\t" .$item['pay_orderid'],
    //                     'pay_memberid' => "\t" . $item['pay_memberid'],
    //                     'username' => "\t" .$item['username'],
    //                     'pay_amount' => $item['pay_amount'],
    //                     'pay_poundage' => $item['pay_poundage'],
    //                     'pay_actualamount' => $item['pay_actualamount'],
    //                     'pay_applydate' => "\t" .date('Y-m-d H:i:s', $item['pay_applydate']),
    //                     'pay_successdate' => "\t" .$pay_successdate,
    //                     'pay_status' => "\t" .$status,
    //                 ];
    //                 if($type==1){
    //                     $list['pay_zh_tongdao'] = "\t" .$item['pay_zh_tongdao'];
    //                 }else{
    //                     $list['pay_bankname'] = "\t" .$item['pay_bankname'];
    //                 }
    //                 $cnt++;
    //                 if ($limit == $cnt) {
    //                     //刷新一下输出buffer，防止由于数据过多造成问题
    //                     ob_flush();
    //                     flush();
    //                     $cnt = 0;
    //                 }
    //                 fputcsv($fp, $list);
    //             }
    //             fclose($fp);  //每生成一个文件关闭
    //         }
    //     }
    //     if(!empty($fileNameArr)){
    //         //进行多个文件压缩
    //         $zip = new \ZipArchive();
    //         $filename = $fileName . ".zip";
    //         // log_place_order('down', "文件地址", $filename);
    //         $zip->open($filename, \ZipArchive::CREATE);   //打开压缩包
    //         foreach ($fileNameArr as $file) {
    //             $zip->addFile($file, basename($file));   //向压缩包中添加文件
    //         }
    //         $zip->close();  //关闭压缩包
    //         foreach ($fileNameArr as $file) {
    //             unlink($file); //删除csv临时文件
    //         }
    //         return $filename;
    //     }else{
    //         return 'noData';
    //     }
    // }


    //导出委托提款记录
    public function exportweituo($where,$uid='', $type='')
    {
        if (isset($where['memberid'])) {
            $where['userid'] = $where['memberid']-10000;
            unset($where['memberid']);
        }
        if (isset($where['tongdao'])) {
            $where['df_id'] = $where['tongdao'];
            unset($where['tongdao']);
        }
        if (isset($where['memo'])) {
            $where['memo'] = ['like', "%" . $where['memo'] . "%"];
        }
        if (isset($where['status'])) {
            if ($where['status'] == '2or3') {
                $where['status'] = array('between', array('2', '3'));
            } elseif ($where['status'] == '4or5') {
                $where['status'] = array('between', array('4', '5'));
            }
        }
        if (isset($where['createtime'])) {
            list($cstime, $cetime) = explode('|', $where['createtime']);
            $where['sqdatetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d')]];
            unset($where['createtime']);
        }
        if (isset($where['successtime'])) {
            list($sstime, $setime) = explode('|', $where['successtime']);
            $where['cldatetime']   = ['between', [$sstime, $setime ? $setime : date('Y-m-d')]];
            unset($where['successtime']);
        }

        $file_path = "./Uploads/download/". date('Ymd', $this->exportJob['time']) ."/" ;
        @mkdirs($file_path, 0750);
        @chmod($file_path, 0750);
        $fileName = $file_path . $uid . '_dforder' . $this->exportJob['time'] . '_' . $this->exportTaskToken;

        if($type==3){
            $title = array('商户编号', '系统订单号', '外部订单号', '结算金额', '手续费', '到账金额', '银行名称', '支行名称', '银行卡号', '开户名', '申请时间', '处理时间', '状态', "备注","通道");
        }else{
            $title = array('商户编号', '系统订单号', '外部订单号', '结算金额', '手续费', '到账金额', '银行名称', '支行名称', '银行卡号', '开户名', '申请时间', '处理时间', '状态', "备注");
        }
        foreach ($title as $tk => $tv){
            $title[$tk]=iconv('utf-8','gb2312//ignore',$tv);
        }

        $filed = 'id,userid,df_name,channel_mch_id,orderid,out_trade_no,tkmoney,sxfmoney,money,bankname,bankzhiname,banknumber,bankfullname,sqdatetime,cldatetime,status,memo';
        $Wttklist = D('Wttklist');
        $tables = array_values($Wttklist->getTables($where));
        $this->prepareExportTables($tables);
        foreach ($tables as $tableIndex => $table) {
            while ($this->exportState['table'] === $tableIndex) {
                $data = $this->fetchExportBatch($Wttklist, $table, $where, $filed, $tableIndex);
                if (!$data) { break; }
                $fp = $this->openExportCsv($this->exportPartPath() . '.partial');
                $this->writeExportCsv($fp, $title);
                $limit = 5000;
                $cnt = 0;

                foreach ($data as $item) {
                    switch ($item['status']) {
                        case 0:
                            $status = '未处理';
                            break;
                        case 1:
                            $status = '处理中';
                            break;
                        case 2:
                            $status = '成功未返回';
                            break;
                        case 3:
                            $status = "成功已返回";
                            break;
                        case 4:
                            $status = "失败未返回";
                            break;
                        case 5:
                            $status = "失败已返回";
                            break;
                        case 6:
                            $status = "已驳回";
                            break;
                    }


                    if($type==3){
                        $list = array(
                            'memberid'       => "\t" . ($item['userid'] + 10000),
                            'orderid'        => "\t" .$item['orderid'],
                            'out_trade_no'   => "\t" .$item['out_trade_no'],
                            'tkmoney'        => $item['tkmoney'],
                            'sxfmoney'       => $item['sxfmoney'],
                            'money'          => $item['money'],
                            'bankname'       => "\t" .iconv('utf-8','gb2312//ignore',$item['bankname']),
                            'bankzhiname'    => "\t" .iconv('utf-8','gb2312//ignore',$item['bankzhiname']),
                            'banknumber'     => "\t" .$item['banknumber'],
                            'bankfullname'   => "\t" .iconv('utf-8','gb2312//ignore',$item['bankfullname']),
                            'sqdatetime'     => "\t" .$item['sqdatetime'],
                            'cldatetime'     => "\t" .$item['cldatetime'],
                            'status'         => "\t" .iconv('utf-8','gb2312//ignore',$status),
                            "memo"           => "\t" .iconv('utf-8','gb2312//ignore',$item["memo"]),
                            "df_name"        => "\t" .iconv('utf-8','gb2312//ignore',$item["df_name"])
                        );
                    }else{
                        $list = array(
                            'memberid'       => "\t" . ($item['userid'] + 10000),
                            'orderid'        => "\t" .$item['orderid'],
                            'out_trade_no'   => "\t" .$item['out_trade_no'],
                            'tkmoney'        => $item['tkmoney'],
                            'sxfmoney'       => $item['sxfmoney'],
                            'money'          => $item['money'],
                            'bankname'       => "\t" .iconv('utf-8','gb2312//ignore',$item['bankname']),
                            'bankzhiname'    => "\t" .iconv('utf-8','gb2312//ignore',$item['bankzhiname']),
                            'banknumber'     => "\t" .$item['banknumber'],
                            'bankfullname'   => "\t" .iconv('utf-8','gb2312//ignore',$item['bankfullname']),
                            'sqdatetime'     => "\t" .$item['sqdatetime'],
                            'cldatetime'     => "\t" .$item['cldatetime'],
                            'status'         => "\t" .iconv('utf-8','gb2312//ignore',$status),
                            "memo"           => "\t" .iconv('utf-8','gb2312//ignore',$item["memo"]),
                        );
                    }
                    $cnt++;
                    if ($limit == $cnt) {
                        //刷新一下输出buffer，防止由于数据过多造成问题
                        if (ob_get_level() > 0) { ob_flush(); }
                        flush();
                        $cnt = 0;
                    }
                    $this->writeExportCsv($fp, $list);
                }
                $this->commitExportBatch($fp, $data);
            }

        }
        return $this->finishExport($fileName);
    }


    /**
     * 资金变动记录导出
     */
    public function exceldownload($where, $uid='', $type='')
    {
        if (isset($where['memberid'])) {
            $where['userid'] = array('eq', $where['memberid']-10000);
            unset($where['memberid']);
        }
        if (isset($where['orderid'])) {
            $where['transid'] = array('eq', $where['orderid']);
            unset($where['orderid']);
        }
        if (isset($where['bank'])) {
            $where['lx'] = array('eq', $where['bank']);
            unset($where['bank']);
        }
        if (isset($where['createtime'])) {
            list($cstime, $cetime) = explode('|', $where['createtime']);
            $where['datetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d')]];
            unset($where['createtime']);
        }

        $file_path = "./Uploads/download/". date('Ymd', $this->exportJob['time']) ."/" ;
        @mkdirs($file_path, 0750);
        @chmod($file_path, 0750);
        $fileName = $file_path . $uid . '_change' . $this->exportJob['time'] . '_' . $this->exportTaskToken;

        $title = array('订单号', '外部订单号','用户名', '类型', '原金额', '变动金额', '变动后金额', '变动时间', '备注');
        foreach ($title as $tk => $tv){
            $title[$tk]=iconv('utf-8','gb2312//ignore',$tv);
        }
        $filed = 'id,transid,orderid,userid,lx,ymoney,money,gmoney,datetime,contentstr';

        $MoneychangeModel = D('Moneychange');
        $tables = array_values($MoneychangeModel->getTables($where));
        $this->prepareExportTables($tables);
        foreach ($tables as $tableIndex => $table) {
            while ($this->exportState['table'] === $tableIndex) {
                $data = $this->fetchExportBatch($MoneychangeModel, $table, $where, $filed, $tableIndex);
                if (!$data) { break; }
                $fp = $this->openExportCsv($this->exportPartPath() . '.partial');
                $this->writeExportCsv($fp, $title);
                $limit = 5000;
                $cnt = 0;
                $this->loadExportMembers($data ?: array(), 'userid');
                foreach ($data as $value) {
                    $list['transid'] = "\t" .$value["transid"];
                    $list['orderid'] = "\t" .$value["orderid"];
                    $list['parentname'] = "\t" .iconv('utf-8','gb2312//ignore',$this->exportMemberName((int)$value["userid"], true));
                    switch ($value["lx"]) {
                        case 1:
                            $list['lxstr'] = "付款";
                            break;
                        case 3:
                            $list['lxstr'] = "手动增加";
                            break;
                        case 4:
                            $list['lxstr'] = "手动减少";
                            break;
                        case 6:
                            $list['lxstr'] = "结算";
                            break;
                        case 7:
                            $list['lxstr'] = "冻结";
                            break;
                        case 8:
                            $list['lxstr'] = "解冻";
                            break;
                        case 9:
                            $list['lxstr'] = "提成";
                            break;
                        case 10:
                            $list['lxstr'] = "代付结算";
                            break;
                        case 11:
                            $list['lxstr'] = "提款驳回";
                            break;
                        case 12:
                            $list['lxstr'] = "代付驳回";
                            break;
                        case 13:
                            $list['lxstr'] = "投诉保证金解冻";
                            break;
                        case 14:
                            $list['lxstr'] = "扣除代付结算手续费";
                            break;
                        case 15:
                            $list['lxstr'] = "代付结算驳回退回手续费";
                            break;
                        case 16:
                            $list['lxstr'] = "扣除手动结算手续费";
                            break;
                        case 17:
                            $list['lxstr'] = "手动结算驳回退回手续费";
                            break;
                        case 18:
                            $list['lxstr'] = "代付失败退回";
                            break;
                        case 19:
                            $list['lxstr'] = "代付失败退回手续费";
                            break;
                        case 20:
                            $list['lxstr'] = "商户内转";
                            break;
                        case 21:
                            $list['lxstr'] = "冲正退款";
                            break;
                        default:
                            $list['lxstr'] = "未知";
                    }
                    $list['lxstr'] = iconv('utf-8','gb2312//ignore',$list['lxstr']);
                    // $list['tcuserid'] = "\t" .getParentName($value["tcuserid,"], 1);
                    // $list['tcdengji'] = "\t" .$value["tcdengji"];
                    $list['ymoney'] = $value["ymoney"];
                    $list['money'] = $value["money"];
                    $list['gmoney'] = $value["gmoney"];
                    $list['datetime'] = "\t" .$value["datetime"];
                    // $list['tongdao'] = "\t" .getProduct($value["tongdao"]);
                    $list['contentstr'] = "\t" .iconv('utf-8','gb2312//ignore',$value["contentstr"]);
                    $cnt++;
                    if ($limit == $cnt) {
                        //刷新一下输出buffer，防止由于数据过多造成问题
                        if (ob_get_level() > 0) { ob_flush(); }
                        flush();
                        $cnt = 0;
                    }
                    $this->writeExportCsv($fp, $list);
                }
                $this->commitExportBatch($fp, $data);
            }
        }
        return $this->finishExport($fileName);
    }

    /**
     * 导出Utr
     * */
    public function exportUtrOrder($where, $uid='', $type='')
    {
        if (isset($where['status'])) { $where['pay_status'] = $where['status']; unset($where['status']); }
        if (isset($where['memberid'])) {
            $where['pay_memberid'] = array('eq', $where['memberid']-10000);
            unset($where['memberid']);
        }
        if (isset($where['createtime'])) {
            list($cstime, $cetime) = explode('|', $where['createtime']);
            $where['datetime'] = ['between', [strtotime($cstime), strtotime($cetime)]];
            unset($where['createtime']);
        }

        $file_path = "./Uploads/download/". date('Ymd', $this->exportJob['time']) ."/" ;
        @mkdirs($file_path, 0750);
        @chmod($file_path, 0750);
        $fileName = $file_path . $uid . '_utrOrder' . $this->exportJob['time'] . '_' . $this->exportTaskToken;

        $utrModel = M('Utr');
        $title = [
            '商户编号',
            '系统订单号',
            '下游订单号',
            'UTR',
            '支付通道',
            '订单状态',
            '查询状态',
            '创建时间',
        ];
        foreach ($title as $tk => $tv){
            $title[$tk]=iconv('utf-8','gb2312//ignore',$tv);
        }
        $filed = '*';
        $this->prepareExportTables(array('utr'));
        while ($this->exportState['table'] === 0) {
            $data = $this->fetchExportBatch($utrModel, null, $where, $filed, 0);
            if (!$data) { break; }
            $fp = $this->openExportCsv($this->exportPartPath() . '.partial');
            $this->writeExportCsv($fp, $title);
            $limit = 5000;
            $cnt = 0;

            //        echo $utrModel->getLastSql();
            foreach ($data as $item) {

                switch ($item['pay_status']) {
                    case 0:
                        $status = '未处理';
                        break;
                    case 1:
                        $status = '成功，未返回';
                        break;
                    case 2:
                        $status = '成功，已返回';
                        break;
                }
                switch ($item['check_status']) {
                    case 0:
                        $check_status = '不存在';
                        break;
                    case 1:
                        $check_status = '不存在';
                        break;
                    case 2:
                        $check_status = '成功';
                        break;
                    case 3:
                        $check_status = '失败';
                        break;
                    case 4:
                        $check_status = '超时';
                        break;
                    case 5:
                        $check_status = '未查询';
                        break;
                }
                if ($item['datetime']) {
                    $datetime = date('Y-m-d H:i:s', $item['datetime']);
                } else {
                    $datetime = 0;
                }
                $list = [
                    'pay_memberid' => "\t" . ($item['pay_memberid'] + 10000),
                    'pay_orderid' => "\t" .$item['pay_orderid'],
                    'out_trade_id' => "\t" .$item['out_trade_id'],
                    'utr' => "\t" .$item['utr'],
                    'pay_tongdao' => "\t" .iconv('utf-8','gb2312//ignore',$item['pay_tongdao']),
                    'pay_status' => "\t" .iconv('utf-8','gb2312//ignore',$status),
                    'check_status' => "\t" .iconv('utf-8','gb2312//ignore',$check_status),
                    'datetime' => "\t" .$datetime,
                ];

                $cnt++;
                if ($limit == $cnt) {
                    //刷新一下输出buffer，防止由于数据过多造成问题
                    if (ob_get_level() > 0) { ob_flush(); }
                    flush();
                    $cnt = 0;
                }
                $this->writeExportCsv($fp, $list);
            }
            $this->commitExportBatch($fp, $data);
        }
        return $this->finishExport($fileName);
    }

}
