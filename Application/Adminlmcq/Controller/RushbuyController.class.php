<?php
namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;
use Think\Controller;
class RushbuyController extends AdminlmcqBaseController
{

    /**
     * 初始化页面  抢购页面
     */
    public  function rob(){

        //查询数据库   stock_coupon
        $coupon=M('stock_coupon');
        //查询记录总数
        $count = $coupon->count();
        $p = getpage($count, 10);
        //查询数据
        $list = $coupon->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        //分配数据
        $this->assign('list', $list);
        $this->assign('page', $p->show());
        $this->display('Rushtobuy/rushtobuy');
    }
    //修改
    public  function  rushBuyEdit(){
        if (IS_POST) {
            //首先判断是不是 平均分配  若是 字段平均值   每次修改都要清空 idlist
            //获取提交的数据
            if ($_POST['isaverage']==1){
                //是平均 必须 数量/人数  是整数
                if (floor($_POST['couponnumber']/$_POST['robnumber'])==$_POST['couponnumber']/$_POST['robnumber']){
                    //是正整数
                    //构建数据
                    $news['couponnumber'] = $_POST['couponnumber'];
                    $news['robnumber'] = $_POST['robnumber'];
                    $news['isaverage'] = $_POST['isaverage'];
                    $news['aneragevalue'] =$_POST['couponnumber']/$_POST['robnumber'];
                    $news['idlist'] = null;
                    //更新数据
                    $result = D('stock_coupon')->where(array('id'=>$_POST['id']))->save($news);
                    if ($result) {
                        $this->success('修改成功！', '/Adminlmcq/Rushbuy/rob', 3);
                    } else {
                        $this->error('修改失败！');
                    }
                }else{
                    //不是正整数
                    $this->error('优惠券必须是允许抢购人数的整数倍！');
                }

            }else{
                //随机分配
                //构建数据
                $news['couponnumber'] = $_POST['couponnumber'];
                $news['robnumber'] = $_POST['robnumber'];
                $news['isaverage'] = $_POST['isaverage'];
                $news['aneragevalue'] =null;
                $news['idlist'] = null;
                //更新数据
                $result = D('stock_coupon')->where(array('id'=>$_POST['id']))->save($news);
                if ($result) {
                    $this->success('修改成功！', '/Adminlmcq/Rushbuy/rob', 3);
                } else {
                    $this->error('修改失败！');
                }
            }

        }else{
            //获取要修改的新闻id
            $id = I('get.id');
            //实例化对象
            $stock_coupon = D('stock_coupon');
            //查询新闻内容
            $data = $stock_coupon->where(array('id'=>$id))->find();
            //分配数据
            $this->assign('data', $data);
            //展示模板
            $this->display('Rushtobuy/rushbuy_edit');
        }
    }

}