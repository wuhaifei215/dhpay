<?php

namespace Payment\Controller;

class CeShiDFController extends PaymentController
{
    //代付提交
    public function PaymentExec($data, $config)
    {
        
        return ['status' => 1, 'msg' => '测试通道，申请正常'];
        // return ['status' => 3, 'msg' => '测试通道，申请失败111'];
    }
    //代付订单查询
    public function PaymentQuery($data, $config)
    {
        $return = ['status' => 2, 'msg' => '成功','remark' => 'https://api.yunpay.com'];
        return $return;
    }
}
