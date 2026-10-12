<?php
namespace User\Controller;

require_once dirname(__DIR__, 4) . '/shared/ExportTask.php';
use Dhpay2\Security\ExportTask;
use Think\Page;

class DownloadController extends UserController
{
    public $expire_time = 3600 * 8; //1天的秒数
    public function __construct()
    {
        parent::__construct();
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

    //首页
    public function index()
    {
        $redis = $this->redis_connect();
        $uid = ExportTask::positiveId($this->fans['memberid']);
        $key = 'downList_user_' . $uid;
        //获取列表的所有元素
        $downList = $redis->lRange($key, 0, -1);
        
        //删除列表
        // $redis->delete($key);
        $list = [];
        foreach ($downList as $v){
            $list_msg = $redis->get($v);
            if(!empty($list_msg)){
                $list[] =  json_decode($list_msg,true);
                // $redis->rPush($key, $v);
            }
        }
        $list = array_reverse($list);
        
        $this->assign('uid', session("user_auth.uid"));
        $this->assign("list", $list);
        C('TOKEN_ON', false);
        $this->display();
    }
    public function setlist()
    {
        try {
            if (!IS_POST) { throw new \InvalidArgumentException('导出任务仅支持POST提交'); }
            ExportTask::checkCsrf(I('post.export_token', '', false));
            $requestedUid = I('post.uid', null, false);
            if ($requestedUid !== null) { ExportTask::positiveId($requestedUid); }
            $ownerKind = 'user';
            $uid = ExportTask::positiveId($this->fans['memberid']);
            $type = ExportTask::type(I('post.type', '', false), $ownerKind);
            $where = ExportTask::filters($type, I('post.where', array(), false));
            $target = isset($where['memberid']) ? ExportTask::positiveId($where['memberid']) : $uid;
            $currency = $this->fans['currency'];
            if ($target !== $uid) {
                $member = M('Member')->where(array('id' => $target - 10000, 'parentid' => $this->fans['uid'], 'status' => 1))->field('id,currency')->find();
                if (!$member) { throw new \InvalidArgumentException('没有该商户的导出权限'); }
                $currency = $member['currency'];
            }
            if (!is_string($currency) || !preg_match('/^[A-Z]{3}$/D', $currency)) {
                throw new \RuntimeException('商户币种配置不正确');
            }
            $where['memberid'] = (string)$target;
            $where['currency'] = $currency;
            list($key, $job) = ExportTask::create($ownerKind, $uid, $type, $where, ExportTask::secret());
            ExportTask::enqueue($this->redis_connect(), $key, $job);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return;
        } catch (\RedisException $e) {
            \Think\Log::record('Export Redis unavailable', 'ERR');
            $this->error('导出服务暂不可用，请稍后再试');
            return;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return;
        } catch (\Exception $e) {
            \Think\Log::record('Export enqueue failed: ' . get_class($e), 'ERR');
            $this->error('导出服务暂不可用，请稍后再试');
            return;
        }
        $this->success('添加成功');
    }

}
