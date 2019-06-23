<?php

namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;

/*
 * 新闻控制器
 */
class MerchantController extends AdminlmcqBaseController
{
    /**
     * 商家列表
     */
    public function merchantList()
    {   
        //实例化对象
        $Merchant= M('merchant');
            // 查询总记录数
            $count = $Merchant->count();
            $p = getpage($count, 10);
            //查询数据
            $list = $Merchant->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        //分配数据
        $this->assign('list', $list);
        $this->assign('page', $p->show());
        $this->display('MerchantManger/Merchant_list');
    }

    /**
     * 通过商家审核
     */
    public function tongGuo()
    {
        //通过审核的商家 需要更改merchant表中的 状态
        $merchant_id=I('merchant_id');
            $news['merchant_status'] = 1;
            //更新数据
            $result = D('merchant')->where(array('merchant_id'=>$merchant_id))->save($news);
            if ($result){
                //修改成功  返回
                $this->success('修改成功!');
            }else{
            //修改失败
                $this->error('修改失败!');
        }
    }
    /**
     * 驳回审核
     */
    public function boHui()
    {
        //通过审核的商家 需要更改merchant表中的 状态
        $merchant_id=I('merchant_id');
        $news['merchant_status'] = 2;
        //更新数据
        $result = D('merchant')->where(array('merchant_id'=>$merchant_id))->save($news);
        if ($result){
            //修改成功  返回
            $this->success('修改成功!');
        }else{
            //修改失败
            $this->error('修改失败!');
        }
    }

    /**
     * 删除
     */
    public function merchantDel()
    {
        //删除需要更改user表中的是否是商家的状态
        //获取新闻id
        $merchant_id = I('get.merchant_id');
        $merchant=M('merchant')->where(array('merchant_id' => $merchant_id))->find();
        //删除数据
        $result1= M('merchant')->where(array('merchant_id' => $merchant_id))->delete();
        //修改user状态
        $news['user_ismerchant'] = 0;
        $result2 = D('user')->where(array('user_id'=>$merchant['user_id']))->save($news);
        if($result1&&$result2){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败!');
        }
    }



}