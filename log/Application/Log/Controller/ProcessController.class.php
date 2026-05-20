<?php
namespace Log\Controller;

use \think\Controller;
use Think\Log;

/**
 * @author mapeijian
 * @date   2018-06-06
 */
class ProcessController extends Controller
{
    protected $Processmodel;
    protected $tableName;
    protected $order_id;
    protected $memberid;

    public function __construct()
    {
        parent::__construct();
    }

    //订单查询
    public function index()
    {
        $from = I('request.from', '', 'string,strip_tags,htmlspecialchars');
        if(!$from){
            $this->ajaxReturn(['status' => 0, 'msg' => "from error"]);
        }
        $action = I('request.action', '', 'string,strip_tags,htmlspecialchars');
        if(!$action){
            $this->ajaxReturn(['status' => 0, 'msg' => "action error"]);
        }
        $this->order_id = I('request.cid', '', 'string,strip_tags,htmlspecialchars');
        if(!$this->order_id){
            $this->ajaxReturn(['status' => 0, 'msg' => "cid error"]);
        }
        $create_time = I('request.create_time', '', 'string,strip_tags,htmlspecialchars');
        if(!$create_time){
            $this->ajaxReturn(['status' => 0, 'msg' => "create_time error"]);
        }

        $log = [
            'from' => $from,
            'action' => $action,
            'order_id' => $this->order_id,
            'create_time' => $create_time,       //创建时间
        ];
        try {
            $this->getProcess();
            $res = $this->Processmodel->table($this->tableName)->add($log);
        } catch (\Exception $e) {
//            echo $e;
        }
        if(isset($res) && $res!=''){
            $this->ajaxReturn(['status' => 1, 'msg' => "success"]);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => "add error"]);
        }
    }

    protected function getProcessLog() {
        $this->memberid = I('request.memberid', '', 'string,strip_tags,htmlspecialchars');
        if(!$this->memberid){
            $this->ajaxReturn(['status' => 0, 'msg' => "cid error"]);
        }
        $this->order_id = I('request.order_id', '', 'string,strip_tags,htmlspecialchars');
        if(!$this->order_id){
            $this->ajaxReturn(['status' => 0, 'msg' => "order_id error"]);
        }
        $where = [
            'user_id' =>$this->memberid,
            'order_id' => $this->order_id
        ];
        $this->getProcess();
        $log_data = $this->Processmodel->table($this->tableName)->where($where)->select();
        if(!empty($log_data)){
            return $log_data;
        }
    }

    //获取出款 数据库连接信息
    protected function getProcess(){
        $date = date('Ymd',strtotime(substr($this->order_id, 1, 8)));  //获取订单日期
        $this->Processmodel = D('Process');
        $this->tableName = $this->Processmodel->getRealTableName($date);
    }

    /**
     * 错误返回
     * @param string $msg
     * @param array $fields
     */
    protected function ajaxReturn($re_data)
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => $re_data['status'], 'msg' => $re_data['msg']);
        echo json_encode($data, 320);
        exit;
    }
}