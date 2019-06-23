<?php

namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;

/*
 * 财富管理
 * Author:chenmengchen
 * Date:2017/03/31
 */

class WealthController extends AdminlmcqBaseController
{

    //财务明细
    public function wallet()
    {
        //实例化对象
        $wallet = M('wallet_log');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data)) {
                $this->error('请输入用户账号查询');
            }
            $reg = '/^1[3|4|5|6|7|8|9][0-9]{9}$/';
//            if(preg_match($reg,$data['user_phone'])==0 && !is_numeric($data['user_phone'])){
//                $this->error('请输入正确用户账号查询');
//            }
            $map['user_phone'] = $data['user_phone'];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('change_date desc')->select();

        } else {
            //全部列表
            $count = $wallet->count();
            $p = getpage($count, 15);
            $list = $wallet->limit($p->firstRow, $p->listRows)->order('change_date desc')->select();
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/wallet');
    }

    /**
     * 交易明细
     */
    public function payInfo()
    {
        //实例化对象
        $payorder = M('PayedOrder');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_phone'])) {
                //全部列表
                $count = $payorder->where(array('status' => '2'))->count();
                $p = getpage($count, 15);
                $list = $payorder->where(array('status' => '2'))->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
            } else {

                $where['user_name'] = array('like', "%" . $data['user_phone'] . "%");
                $where['gain_user_name'] = array('like', "%" . $data['user_phone'] . "%");
                $where['_logic'] = 'or';
                $map['_complex'] = $where;
                $count = $payorder->where(array('status' => '2'))->where($map)->count();
                $p = getpage($count, 15);
                $list = $payorder->where(array('status' => '2'))->where($map)->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
            }
        } else {
            //全部列表
            $count = $payorder->where(array('status' => '2'))->count();
            $p = getpage($count, 15);
            $list = $payorder->where(array('status' => '2'))->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/payed_order');
    }

    //买入订单  只查询待匹配预付款订单
    public function buyOrderone()
    {
        //实例化对象
        $wallet = M('HelpOrder');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_name'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'matching' => array('eq', 0),
                    'order_type' => '1',//预付款类
                ];
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            } else {
                $where['user_phone'] = array('like', $data['user_name']);
                $where['order_number'] = array('like', $data['user_name']);
                $where['_logic'] = 'or';
                $map['_complex'] = $where;
                // $map=[
                //     'user_name'=>$data['user_name'],
                //     'matching'=>array('eq',0),
                //     'order_type'=>'1',//预付款类
                // ];
                //dump($map);die;
                $count = $wallet->where($map)->where(array('matching' => '0', 'order_type' => '1'))->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->where(array('matching' => '0', 'order_type' => '1'))->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            }
        } else {
            //全部未匹配列表
            $map = [
                'matching' => array('eq', 0),
                'order_type' => '1',//预付款类
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
        }
        //计算总金额/待匹配/交易中金额/交易成功金额
        $allmoney = M('help_order')->where(array('id!=parent_id'))->sum('amount');
        $waitmoney = M('help_order')->where(array('id!=parent_id'))->where('matching=0')->sum('amount');
        $moneying = M('help_order')->where(array('id!=parent_id'))->where('matching=1')->sum('amount');
        $successmoney = M('help_order')->where(array('id!=parent_id'))->where('matching=2')->sum('amount');
        if (!$allmoney) {
            $allmoney = 0;
        }
        if (!$waitmoney) {
            $waitmoney = 0;
        }
        if (!$moneying) {
            $moneying = 0;
        }
        if (!$successmoney) {
            $successmoney = 0;
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('allmoney', $allmoney);
        $this->assign('waitmoney', $waitmoney);
        $this->assign('moneying', $moneying);
        $this->assign('successmoney', $successmoney);
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/buy_order1');
    }

    //买入订单  只查询待匹配非预付款订单
    public function buyOrdertow()
    {
        //实例化对象
        $wallet = M('HelpOrder');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_name'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'matching' => array('eq', 0),
                    'order_type' => '2',//预付款类
                ];
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            } else {
                $where['user_phone'] = array('like', $data['user_name']);
                $where['order_number'] = array('like', $data['user_name']);
                $where['_logic'] = 'or';
                $map['_complex'] = $where;
                // $map=[
                //     'user_name'=>$data['user_name'],
                //     'matching'=>array('eq',0),
                //     'order_type'=>'2',//预付款类
                // ];
                //dump($map);die;
                $count = $wallet->where($map)->where(array('matching' => '0', 'order_type' => '2'))->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->where(array('matching' => '0', 'order_type' => '2'))->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            }
        } else {
            //全部未匹配列表
            $map = [
                'matching' => array('eq', 0),
                'order_type' => '2',//预付款类
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
        }
        //计算总金额/待匹配/交易中金额/交易成功金额
        $allmoney = M('help_order')->where(array('id!=parent_id'))->sum('amount');
        $waitmoney = M('help_order')->where(array('id!=parent_id'))->where('matching=0')->sum('amount');
        $moneying = M('help_order')->where(array('id!=parent_id'))->where('matching=1')->sum('amount');
        $successmoney = M('help_order')->where(array('id!=parent_id'))->where('matching=2')->sum('amount');
        if (!$allmoney) {
            $allmoney = 0;
        }
        if (!$waitmoney) {
            $waitmoney = 0;
        }
        if (!$moneying) {
            $moneying = 0;
        }
        if (!$successmoney) {
            $successmoney = 0;
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('allmoney', $allmoney);
        $this->assign('waitmoney', $waitmoney);
        $this->assign('moneying', $moneying);
        $this->assign('successmoney', $successmoney);
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/buy_order2');
    }

    //买入订单  只查询待匹配非预付款订单
    public function buyOrderthree()
    {
        //实例化对象
        $wallet = M('HelpOrder');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_name'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'matching' => array('eq', 0),
                ];
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            } else {
                $where['user_phone'] = array('like', $data['user_name']);
                $where['order_number'] = array('like', $data['user_name']);
                $where['_logic'] = 'or';
                $map['_complex'] = $where;
                $count = $wallet->where($map)->where(array('matching' => '0', 'order_type' => '2'))->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->where(array('matching' => '0', 'order_type' => '2'))->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            }
        } else {
            //全部未匹配列表
            $map = [
                'matching' => array('eq', 0),
                'buy' => '1',//预约购买
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
        }
        //计算总金额/待匹配/交易中金额/交易成功金额
        $allmoney = M('help_order')->where(array('id!=parent_id'))->sum('amount');
        $waitmoney = M('help_order')->where(array('id!=parent_id'))->where('matching=0')->sum('amount');
        $moneying = M('help_order')->where(array('id!=parent_id'))->where('matching=1')->sum('amount');
        $successmoney = M('help_order')->where(array('id!=parent_id'))->where('matching=2')->sum('amount');
        if (!$allmoney) {
            $allmoney = 0;
        }
        if (!$waitmoney) {
            $waitmoney = 0;
        }
        if (!$moneying) {
            $moneying = 0;
        }
        if (!$successmoney) {
            $successmoney = 0;
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('allmoney', $allmoney);
        $this->assign('waitmoney', $waitmoney);
        $this->assign('moneying', $moneying);
        $this->assign('successmoney', $successmoney);
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/buy_order3');
    }

    //买入订单删除
    public function orderdelete()
    {
        $theid = I('request.id');
        $orderrel = M('help_order')->where(array('id' => $theid))->find();
        if ($theid <> '' && $orderrel['id'] <> '') {
            M('help_order')->where(array('id' => $orderrel['id']))->delete();
            $this->success('删除成功!');
        } else {
            $this->error('订单不存在');
        }
    }

    //买入订单预付款手动匹配---->展示卖出未匹配的订单列表
    public function inputlistone()
    {
        $theid = I('get.id');//买入列表中的序号值
        $tgbzuser = M('help_order')->where(array('id' => $theid))->find();
        $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
        $pagecount = M('askhelp_order')->where(array('matching' => 0))->where($map)->count();
        $p = getpage($pagecount, 20);
        $list = M('askhelp_order')->where(array('matching' => 0))->where($map)->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
        foreach ($list as &$val) {
            $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
            if ($val['user_parent']) {//有值时
                $theparent = explode(',', $val['user_parent']);
                $count = count($theparent);
                $count = $count - 1;
                $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
            } else {
                $val['user_parents'] = "--";
            }
        }
        $show = $p->show();
        if (!stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值
            $_SESSION['check_p']['check_id'] = ",";
            $_SESSION['check_p']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p']['check_money'])) {
            $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
        }
        if (IS_POST) {
            $data = I('post.');
            if ($data['user2']) {
                if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
                    $_SESSION['check_p']['check_id'] = ",";
                    $_SESSION['check_p']['check_money'] = 0;
                }
                //已选择的数据
                if (!empty($_SESSION['check_p']['check_money'])) {
                    $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
                }
                $tgbzuser = M('help_order')->where(array('id' => $data['pid']))->find();//根据id查找
                $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
                $pagecount = M('askhelp_order')->where(array('matching' => 0, 'user_name' => $data['user2']))->where($map)->count();
                $p = getpage($pagecount, 20);
                $list = M('askhelp_order')->where(array('matching' => 0, 'user_name' => $data['user2']))->where($map)->order('addtime DESC')->limit($p->firstRow, $p->listRows)->select();
                foreach ($list as &$val) {
                    $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
                    if ($val['user_parent']) {//有值时
                        $theparent = explode(',', $val['user_parent']);
                        $count = count($theparent);
                        $count = $count - 1;
                        $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
                    } else {
                        $val['user_parents'] = "--";
                    }
                }
                $show = $p->show();
            } else {
                if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
                    $_SESSION['check_p']['check_id'] = ",";
                    $_SESSION['check_p']['check_money'] = 0;
                }
                //已选择的数据
                if (!empty($_SESSION['check_p']['check_money'])) {
                    $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
                }
                $tgbzuser = M('help_order')->where(array('id' => $data['pid']))->find();//根据id查找
                $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
                $pagecount = M('askhelp_order')->where(array('matching' => 0))->where($map)->count();
                $p = getpage($pagecount, 20);
                $list = M('askhelp_order')->where(array('matching' => 0))->where($map)->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
                foreach ($list as &$val) {
                    $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
                    if ($val['user_parent']) {//有值时
                        $theparent = explode(',', $val['user_parent']);
                        $count = count($theparent);
                        $count = $count - 1;
                        $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
                    } else {
                        $val['user_parents'] = "--";
                    }
                }
                $show = $p->show();
            }
        }
        $this->assign('list', $list);
        $this->assign('tgbzuser', $tgbzuser);
        $this->assign('page', $show);
        $this->assign('check_id', $_SESSION['check_p']['check_id']);
        $this->assign('check_money', $_SESSION['check_p']['check_money']);
        $this->display('Wealth/input_list1');
    }

    //买入订单非预付款手动匹配---->展示卖出未匹配的订单列表
    public function inputlisttow()
    {
        $theid = I('get.id');//买入列表中的序号值
        $tgbzuser = M('help_order')->where(array('id' => $theid))->find();
        $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
        $pagecount = M('askhelp_order')->where(array('matching' => 0))->where($map)->count();
        $p = getpage($pagecount, 20);
        $list = M('askhelp_order')->where(array('matching' => 0))->where($map)->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
        foreach ($list as &$val) {
            $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
            if ($val['user_parent']) {//有值时
                $theparent = explode(',', $val['user_parent']);
                $count = count($theparent);
                $count = $count - 1;
                $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
            } else {
                $val['user_parents'] = "--";
            }
        }
        $show = $p->show();
        if (!stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值
            $_SESSION['check_p']['check_id'] = ",";
            $_SESSION['check_p']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p']['check_money'])) {
            $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
        }
        if (IS_POST) {
            $data = I('post.');
            if ($data['user2']) {
                if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
                    $_SESSION['check_p']['check_id'] = ",";
                    $_SESSION['check_p']['check_money'] = 0;
                }
                //已选择的数据
                if (!empty($_SESSION['check_p']['check_money'])) {
                    $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
                }
                $tgbzuser = M('help_order')->where(array('id' => $data['pid']))->find();//根据id查找
                $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
                $pagecount = M('askhelp_order')->where(array('matching' => 0, 'user_name' => $data['user2']))->where($map)->count();
                $p = getpage($pagecount, 20);
                $list = M('askhelp_order')->where(array('matching' => 0, 'user_name' => $data['user2']))->where($map)->order('addtime DESC')->limit($p->firstRow, $p->listRows)->select();
                foreach ($list as &$val) {
                    $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
                    if ($val['user_parent']) {//有值时
                        $theparent = explode(',', $val['user_parent']);
                        $count = count($theparent);
                        $count = $count - 1;
                        $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
                    } else {
                        $val['user_parents'] = "--";
                    }
                }
                $show = $p->show();
            } else {
                if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
                    $_SESSION['check_p']['check_id'] = ",";
                    $_SESSION['check_p']['check_money'] = 0;
                }
                //已选择的数据
                if (!empty($_SESSION['check_p']['check_money'])) {
                    $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
                }
                $tgbzuser = M('help_order')->where(array('id' => $data['pid']))->find();//根据id查找
                $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
                $pagecount = M('askhelp_order')->where(array('matching' => 0))->where($map)->count();
                $p = getpage($pagecount, 20);
                $list = M('askhelp_order')->where(array('matching' => 0))->where($map)->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
                foreach ($list as &$val) {
                    $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
                    if ($val['user_parent']) {//有值时
                        $theparent = explode(',', $val['user_parent']);
                        $count = count($theparent);
                        $count = $count - 1;
                        $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
                    } else {
                        $val['user_parents'] = "--";
                    }
                }
                $show = $p->show();
            }
        }
        $this->assign('list', $list);
        $this->assign('tgbzuser', $tgbzuser);
        $this->assign('page', $show);
        $this->assign('check_id', $_SESSION['check_p']['check_id']);
        $this->assign('check_money', $_SESSION['check_p']['check_money']);
        $this->display('Wealth/input_list2');
    }

    /*
    记录已选中的匹配信息
    */
    public function set_cookie()
    {
        $_SESSION['check_p']['check_id'] = I('get.id');
        $_SESSION['check_p']['check_money'] = I('get.money');
        //dump($_SESSION['check_p']['check_id']);die;
    }

    public function set_cookie2()
    {
        $_SESSION['check_p2']['check_id'] = I('get.id');
        $_SESSION['check_p2']['check_money'] = I('get.money');
    }

    /*
    买入预付款订单匹配
    */
    public function matchingone()
    {
        $data = I('post.');
        $arr = explode(',', I('post.arrid'));
        $arr = array_filter($arr);//检测数组中值是否为空,为空就舍弃,键名不变
        rsort($arr);//对数组中的元素进行将序排列,键名改变
        if ($data['arrzs'] < $data['amount']) {
            $this->error('卖出金额不能低于买入金额', U('/Adminlmcq/Wealth/buyOrderone'));
        } elseif (count($arr) > '1') {
            $this->error('预付款不可拆分,您只可选择一条卖出订单进行匹配', U('/Adminlmcq/Wealth/buyOrderone'));
        } else {
            $output = M('askhelp_order')->where(['id' => ['in', $arr]])->select();//依次查询选中的卖出订单信息
            $input = M('help_order')->where(['id' => $data['pid']])->select();//查询买入订单信息
            //dump($output);dump($input);die;
            $num = auto_match_r($input, $output);
            $this->success('订单匹配成功', U('/Adminlmcq/Wealth/buyOrderone'));//匹配成功后跳转到买入订单列表页
        }
    }

    /*
    买入非预付款订单匹配
    */
    public function matchingtow()
    {
        $data = I('post.');
        $arr = explode(',', I('post.arrid'));
        $arr = array_filter($arr);//检测数组中值是否为空,为空就舍弃,键名不变
        rsort($arr);//对数组中的元素进行将序排列,键名改变
        // if($data['arrzs']<$data['amount']){
        //     $this->error('卖出金额不能低于买入金额',U('/Adminlmcq/Wealth/buyOrdertow'));
        // }else{
        $output = M('askhelp_order')->where(['id' => ['in', $arr]])->select();//依次查询选中的卖出订单信息
        $input = M('help_order')->where(['id' => $data['pid']])->select();//查询买入订单信息
        //dump($output);dump($input);die;
        $num = auto_match_r($input, $output);
        $this->success('订单匹配成功', U('/Adminlmcq/Wealth/buyOrdertow'));//匹配成功后跳转到买入订单列表页
        // }
    }

    //卖出订单  只查询待匹配订单
    public function saleOrder()
    {
        //实例化对象
        $wallet = M('askhelp_order');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_name'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'matching' => array('eq', 0),
                ];
                //$map['type'] = 2;
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            } else {
                // $reg = '/^1[3|4|5|6|7|8|9][0-9]{9}$/';
                // if(preg_match($reg,$data['user_phone'])==0 && !is_numeric($data['user_phone'])){
                //     $this->error('请输入正确用户账号查询');
                // }
                $where['user_phone'] = array('like', $data['user_name']);
                $where['order_number'] = array('like', $data['user_name']);
                $where['_logic'] = 'or';
                $map['_complex'] = $where;
                // $map=[
                //     'user_name'=>$data['user_name'],
                //     'matching'=>array('eq',0),
                // ];
                $count = $wallet->where($map)->where(array('matching' => '0'))->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->where(array('matching' => '0'))->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            }
        } else {
            //全部列表
            $map = [
                'matching' => array('eq', 0),
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
        }
        //计算总金额/待匹配/交易中金额/交易成功金额
        //卖出订单最开始未拆分,也要算进去
        $parentlist = M('askhelp_order')->where('id=parent_id and matching=0')->getField('id', true);
        $parentmoney = 0;
        for ($i = 0; $i < count($parentlist); $i++) {
            if (!M('AskhelpOrder')->where(array('parent_id' => $parentlist[$i], 'order_type' => '2'))->find()) {
                $parentmoney = $parentmoney + M('askhelp_order')->where(array('id' => $parentlist[$i]))->getField('amount');
            }
        }
        $allmoney = M('askhelp_order')->where('id!=parent_id')->sum('amount');
        $allmoney = $allmoney + $parentmoney;
        $waitmoney = M('askhelp_order')->where('id!=parent_id and matching=0')->sum('amount');
        $waitmoney = $waitmoney + $parentmoney;
        $moneying = M('askhelp_order')->where('id!=parent_id and matching=1')->sum('amount');
        $successmoney = M('askhelp_order')->where('id!=parent_id and matching=2')->sum('amount');
        if (!$allmoney) {
            $allmoney = 0;
        }
        if (!$waitmoney) {
            $waitmoney = 0;
        }
        if (!$moneying) {
            $moneying = 0;
        }
        if (!$successmoney) {
            $successmoney = 0;
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('allmoney', $allmoney);
        $this->assign('waitmoney', $waitmoney);
        $this->assign('moneying', $moneying);
        $this->assign('successmoney', $successmoney);
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/sale_order');
    }

    //卖出订单删除
    public function outorderdelete()
    {
        $theid = I('request.id');
        $orderrel = M('askhelp_order')->where(array('id' => $theid))->find();
        if ($theid <> '' && $orderrel['id'] <> '') {
            M('askhelp_order')->where(array('id' => $orderrel['id']))->delete();
            $this->success('删除成功!');
        } else {
            $this->error('订单不存在');
        }
    }

    //卖出订单手动匹配---->展示买入未匹配的订单列表
    public function outputlist()
    {
        $theid = I('get.id');//买入列表中的序号值
        $tgbzuser = M('askhelp_order')->where(array('id' => $theid))->find();
        $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
        $pagecount = M('help_order')->where('id!=parent_id')->where(array('matching' => 0))->where($map)->count();
        $p = getpage($pagecount, 20);
        $list = M('help_order')->where('id!=parent_id')->where(array('matching' => 0))->where($map)->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
        foreach ($list as &$val) {
            $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
            if ($val['user_parent']) {//有值时
                $theparent = explode(',', $val['user_parent']);
                $count = count($theparent);
                $count = $count - 1;
                $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
            } else {
                $val['user_parents'] = "--";
            }
        }
        $show = $p->show();
        if (!stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值
            $_SESSION['check_p']['check_id'] = ",";
            $_SESSION['check_p']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p']['check_money'])) {
            $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
        }
        if (IS_POST) {
            $data = I('post.');
            if ($data['user2']) {
                if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
                    $_SESSION['check_p']['check_id'] = ",";
                    $_SESSION['check_p']['check_money'] = 0;
                }
                //已选择的数据
                if (!empty($_SESSION['check_p']['check_money'])) {
                    $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
                }
                $tgbzuser = M('askhelp_order')->where(array('id' => $data['pid']))->find();//根据id查找
                $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
                $pagecount = M('help_order')->where('id!=parent_id')->where(array('matching' => 0, 'user_name' => $data['user2']))->where($map)->count();
                $p = getpage($pagecount, 20);
                $list = M('help_order')->where('id!=parent_id')->where(array('matching' => 0, 'user_name' => $data['user2']))->where($map)->order('addtime DESC')->limit($p->firstRow, $p->listRows)->select();
                foreach ($list as &$val) {
                    $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
                    if ($val['user_parent']) {//有值时
                        $theparent = explode(',', $val['user_parent']);
                        $count = count($theparent);
                        $count = $count - 1;
                        $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
                    } else {
                        $val['user_parents'] = "--";
                    }
                }
                $show = $p->show();
            } else {
                if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
                    $_SESSION['check_p']['check_id'] = ",";
                    $_SESSION['check_p']['check_money'] = 0;
                }
                //已选择的数据
                if (!empty($_SESSION['check_p']['check_money'])) {
                    $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
                }
                $tgbzuser = M('askhelp_order')->where(array('id' => $data['pid']))->find();//根据id查找
                $map['user_id'] = array('neq', $tgbzuser['user_id']);//不查找自己的卖出订单信息
                $pagecount = M('help_order')->where('id!=parent_id')->where(array('matching' => 0))->where($map)->count();
                $p = getpage($pagecount, 20);
                $list = M('help_order')->where('id!=parent_id')->where(array('matching' => 0))->where($map)->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
                foreach ($list as &$val) {
                    $val['user_parent'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_parent');
                    if ($val['user_parent']) {//有值时
                        $theparent = explode(',', $val['user_parent']);
                        $count = count($theparent);
                        $count = $count - 1;
                        $val['user_parents'] = M('user')->where(array('user_id' => $theparent[$count]))->getField('user_name');
                    } else {
                        $val['user_parents'] = "--";
                    }
                }
                $show = $p->show();
            }
        }
        $this->assign('list', $list);
        $this->assign('tgbzuser', $tgbzuser);
        $this->assign('page', $show);
        $this->assign('check_id', $_SESSION['check_p']['check_id']);
        $this->assign('check_money', $_SESSION['check_p']['check_money']);
        $this->display('Wealth/output_list');
    }

    /*
    卖出订单匹配
    */
    public function rematching()
    {
        $data = I('post.');
        $arr = explode(',', I('post.arrid'));
        $arr = array_filter($arr);
        rsort($arr);
        // if($data['arrzs']<$data['amount']){
        //     $this->error('买入总金额不能低于卖出金额',U('/Adminlmcq/Wealth/saleOrder'));
        // }else{
        $input = M('help_order')->where(['id' => ['in', $arr]])->select();//依次查询选中的买入订单信息
        $output = M('askhelp_order')->where(['id' => $data['pid']])->select();//查询卖出订单信息
        $allyufumoney = M('help_order')->where(['id' => ['in', $arr], 'order_type' => '1'])->sum('amount');//勾选的预付款总金额
        $allsalemoney = M('askhelp_order')->where(['id' => $data['pid']])->getField('amount');//选择匹配的卖出订单额度
        if ($allyufumoney > $allsalemoney) {
            $this->error('买入预付总金额不能高于匹配的卖出金额', U('/Adminlmcq/Wealth/saleOrder'));
        } else {
            $num = auto_match_l($output, $input);
            if (!empty($num)) {
                $this->success('订单匹配成功', U('/Adminlmcq/Wealth/saleOrder'));//匹配成功后跳转到买入订单列表页
            } else {
                $this->error('订单匹配失败');
            }

        }
        //dump($output);dump($input);die;
        // }
    }

    /*
    交易中订单
    */
    public function transactionOrder()
    {
        $wallet = M('match_order');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_name'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'create_time' => array('neq', ''),
                    'status' => array('in', [0, 1]),
                ];
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('create_time asc')->select();
                //获取系统设置打款时间限制值和收款时间限制值
                $paytime1 = M('config')->where('id=1')->getField('pay_time_max');//预付款打款时间限制
                $paytime2 = M('config')->where('id=1')->getField('pay_time_max');//非预付款打款时间限制
                $gaintime = M('config')->where('id=1')->getField('pay_time_max');
                foreach ($list as &$val) {
                    //判断买入订单类型
                    $buytype = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_type');
                    if ($buytype == '1') {//预付款类
                        if (time() > strtotime($val['create_time']) + $paytime1 * 3600 && $val['payed_time'] == null) {//超出时间未打款时
                            $val['totime'] = '超时未打款';
                            $val['theway'] = 1;
                            $thestate = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_status');
                            if ($thestate == 0) {//账户已经冻结时
                                $val['dong'] = 1;
                            } else {
                                $val['dong'] = 2;
                            }
                        } elseif ($val['payed_time'] != null) {//如果打款方已经打过款了,判断收款方是否确定收款
                            if ($val['receive_time'] == null && strtotime($val['payed_time']) + $gaintime * 3600 < time()) {//超出时间未收款时
                                $val['sktime'] = '超时未收款';
                                $val['theway'] = 2;
                                $thestate = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_status');
                                if ($thestate == 0) {//账户已经冻结时
                                    $val['dong'] = 1;
                                } else {
                                    $val['dong'] = 2;
                                }
                            }
                        }
                    } else {//非预付款类
                        if (time() > strtotime($val['create_time']) + $paytime2 * 3600 && $val['payed_time'] == null) {//超出时间未打款时
                            $val['totime'] = '超时未打款';
                            $val['theway'] = 1;
                            $thestate = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_status');
                            if ($thestate == 0) {//账户已经冻结时
                                $val['dong'] = 1;
                            } else {
                                $val['dong'] = 2;
                            }
                        } elseif ($val['payed_time'] != null) {//如果打款方已经打过款了,判断收款方是否确定收款
                            if ($val['receive_time'] == null && strtotime($val['payed_time']) + $gaintime * 3600 < time()) {//超出时间未收款时
                                $val['sktime'] = '超时未收款';
                                $val['theway'] = 2;
                                $thestate = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_status');
                                if ($thestate == 0) {//账户已经冻结时
                                    $val['dong'] = 1;
                                } else {
                                    $val['dong'] = 2;
                                }
                            }
                        }
                    }
                    $val['buy_order_number'] = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_number');
                    $val['sale_order_number'] = M('AskhelpOrder')->where(array('id' => $val['sale_order_id']))->getField('order_number');
                    $val['buy_user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                    $val['sale_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
                }
            } else {
                //先根据手机号找到买入方id
                $thebuyid = M('user')->where(array('user_phone' => $data['user_name']))->getField('user_id');
                $map = [
                    'buy_id' => $thebuyid,
                    'status' => array('in', [0, 1]),
                    'create_time' => array('neq', ''),
                ];
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('create_time asc')->select();
                //获取系统设置打款时间限制值和收款时间限制值
                $paytime1 = M('config')->where('id=1')->getField('pay_time_limit1');//预付款打款时间限制
                $paytime2 = M('config')->where('id=1')->getField('pay_time_limit2');//非预付款打款时间限制
                $gaintime = M('config')->where('id=1')->getField('gain_time_limit');
                foreach ($list as &$val) {
                    //判断买入订单类型
                    $buytype = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_type');
                    if ($buytype == '1') {//预付款类
                        if (time() > strtotime($val['create_time']) + $paytime1 * 3600 && $val['payed_time'] == null) {//超出时间未打款时
                            $val['totime'] = '超时未打款';
                            $val['theway'] = 1;
                            $thestate = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_status');
                            if ($thestate == 0) {//账户已经冻结时
                                $val['dong'] = 1;
                            } else {
                                $val['dong'] = 2;
                            }
                        } elseif ($val['payed_time'] != null) {//如果打款方已经打过款了,判断收款方是否确定收款
                            if ($val['receive_time'] == null && strtotime($val['payed_time']) + $gaintime * 3600 < time()) {//超出时间未收款时
                                $val['sktime'] = '超时未收款';
                                $val['theway'] = 2;
                                $thestate = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_status');
                                if ($thestate == 0) {//账户已经冻结时
                                    $val['dong'] = 1;
                                } else {
                                    $val['dong'] = 2;
                                }
                            }
                        }
                    } else {//非预付款类
                        if (time() > strtotime($val['create_time']) + $paytime2 * 3600 && $val['payed_time'] == null) {//超出时间未打款时
                            $val['totime'] = '超时未打款';
                            $val['theway'] = 1;
                            $thestate = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_status');
                            if ($thestate == 0) {//账户已经冻结时
                                $val['dong'] = 1;
                            } else {
                                $val['dong'] = 2;
                            }
                        } elseif ($val['payed_time'] != null) {//如果打款方已经打过款了,判断收款方是否确定收款
                            if ($val['receive_time'] == null && strtotime($val['payed_time']) + $gaintime * 3600 < time()) {//超出时间未收款时
                                $val['sktime'] = '超时未收款';
                                $val['theway'] = 2;
                                $thestate = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_status');
                                if ($thestate == 0) {//账户已经冻结时
                                    $val['dong'] = 1;
                                } else {
                                    $val['dong'] = 2;
                                }
                            }
                        }
                    }
                    $val['buy_order_number'] = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_number');
                    $val['sale_order_number'] = M('AskhelpOrder')->where(array('id' => $val['sale_order_id']))->getField('order_number');
                    $val['buy_user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                    $val['sale_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
                }
            }
        } else {
            //全部列表
            $map = [
                'create_time' => array('neq', ''),
                'status' => array('in', [0, 1]),
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('create_time asc')->select();
            //获取系统设置打款时间限制值和收款时间限制值
            $paytime1 = M('config')->where('id=1')->getField('pay_time_max');//预付款打款时间限制
            $paytime2 = M('config')->where('id=1')->getField('pay_time_max');//非预付款打款时间限制
            $gaintime = M('config')->where('id=1')->getField('pay_time_max');//收款时间
            foreach ($list as &$val) {
                //判断买入订单类型
                $buytype = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_type');
                if ($buytype == '1') {//预付款类
                    if (time() > strtotime($val['create_time']) + $paytime1 * 3600 && $val['payed_time'] == null) {//超出时间未打款时
                        $val['totime'] = '超时未打款';
                        $val['theway'] = 1;
                        $thestate = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_status');
                        if ($thestate == 0) {//账户已经冻结时
                            $val['dong'] = 1;
                        } else {
                            $val['dong'] = 2;
                        }
                    } elseif ($val['payed_time'] != null) {//如果打款方已经打过款了,判断收款方是否确定收款
                        if ($val['receive_time'] == null && strtotime($val['payed_time']) + $gaintime * 3600 < time()) {//超出时间未收款时
                            $val['sktime'] = '超时未收款';
                            $val['theway'] = 2;
                            $thestate = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_status');
                            if ($thestate == 0) {//账户已经冻结时
                                $val['dong'] = 1;
                            } else {
                                $val['dong'] = 2;
                            }
                        }
                    }
                } else {//非预付款类
                    if (time() > strtotime($val['create_time']) + $paytime2 * 3600 && $val['payed_time'] == null) {//超出时间未打款时
                        $val['totime'] = '超时未打款';
                        $val['theway'] = 1;
                        $thestate = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_status');
                        if ($thestate == 0) {//账户已经冻结时
                            $val['dong'] = 1;
                        } else {
                            $val['dong'] = 2;
                        }
                    } elseif ($val['payed_time'] != null) {//如果打款方已经打过款了,判断收款方是否确定收款
                        if ($val['receive_time'] == null && strtotime($val['payed_time']) + $gaintime * 3600 < time()) {//超出时间未收款时
                            $val['sktime'] = '超时未收款';
                            $val['theway'] = 2;
                            $thestate = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_status');
                            if ($thestate == 0) {//账户已经冻结时
                                $val['dong'] = 1;
                            } else {
                                $val['dong'] = 2;
                            }
                        }
                    }
                }
                $val['buy_order_number'] = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_number');
                $val['sale_order_number'] = M('AskhelpOrder')->where(array('id' => $val['sale_order_id']))->getField('order_number');
                $val['buy_user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                $val['sale_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
            }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        $this->display('Wealth/transaction_order');
    }

    /*
    超时未打款投诉列表
    */
    public function nomoneypay()
    {
        //实例化对象
        $payorder = M('MatchOrder');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_phone'])) {
                //全部列表
                $map = [
                    'create_time' => array('neq', ''),
                ];
                $count = $payorder->where(array('status' => '3'))->where($map)->count();
                $p = getpage($count, 15);
                $list = $payorder->where(array('status' => '3'))->where($map)->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
                foreach ($list as &$val) {
                    $val['user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                    $val['gain_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
                }
            } else {
                $map = [
                    'create_time' => array('neq', ''),
                ];
                $where['buy_name'] = array('like', $data['user_phone']);
                $where['sale_name'] = array('like', $data['user_phone']);
                $where['_logic'] = 'or';
                $map['_complex'] = $where;
                $count = $payorder->where(array('status' => '3'))->where($map)->count();
                $p = getpage($count, 15);
                $list = $payorder->where(array('status' => '3'))->where($map)->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
                foreach ($list as &$val) {
                    $val['user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                    $val['gain_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
                }
            }
        } else {
            //全部列表
            $map = [
                'create_time' => array('neq', ''),
            ];
            $count = $payorder->where(array('status' => '3'))->where($map)->count();
            $p = getpage($count, 15);
            $list = $payorder->where(array('status' => '3'))->where($map)->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
            foreach ($list as &$val) {
                $val['user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                $val['gain_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
            }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/nomoneypay');
    }

    /*
    冻结打款方(买入者)的账户[不用冻结,定时任务已冻结过;这里只需把买入的单子转换为抢单池订单]
    */
    public function tobeget()
    {
        $theid = I('get.id');
        //修改匹配订单表状态
        $result = M('MatchOrder')->where(array('id' => $theid))->save(['buy_id' => '']);//设置买入用户ID为空,用户抢到单子后在设置用户ID
        $matchinfo = M('MatchOrder')->where(array('id' => $theid))->find();
        //修改买入订单状态
        $helporderinfo = M('HelpOrder')->where(array('id' => $matchinfo['buy_order_id']))->find();
        $result1 = M('HelpOrder')->where(array('id' => $matchinfo['buy_order_id']))->save(['snatch_pool' => '1', 'parent_id' => $helporderinfo['id'], 'parent_amount' => $helporderinfo['amount']]);//修改订单的父级id和总金额
        if ($result1 && $result) {
            $this->success('抢单池订单设置成功');
        } else {
            $this->error('抢单池订单设置失败');
        }
    }

    /*
    超时未收款投诉列表
    */
    public function uncollected()
    {
        //实例化对象
        $payorder = M('PayedOrder');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_phone'])) {
                //全部列表
                $count = $payorder->where(array('status' => '4'))->count();
                $p = getpage($count, 15);
                $list = $payorder->where(array('status' => '4'))->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
                foreach ($list as &$val) {
                    $val['user_phone'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_phone');
                    $val['gain_user_phone'] = M('user')->where(array('user_id' => $val['gain_user_id']))->getField('user_phone');
                }
            } else {

                $where['user_name'] = array('like', $data['user_phone']);
                $where['gain_user_name'] = array('like', $data['user_phone']);
                $where['_logic'] = 'or';
                $map['_complex'] = $where;
                $count = $payorder->where(array('status' => '4'))->where($map)->count();
                $p = getpage($count, 15);
                $list = $payorder->where(array('status' => '4'))->where($map)->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
                foreach ($list as &$val) {
                    $val['user_phone'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_phone');
                    $val['gain_user_phone'] = M('user')->where(array('user_id' => $val['gain_user_id']))->getField('user_phone');
                }
            }
        } else {
            //全部列表
            $count = $payorder->where(array('status' => '4'))->count();
            $p = getpage($count, 15);
            $list = $payorder->where(array('status' => '4'))->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
            foreach ($list as &$val) {
                $val['user_phone'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_phone');
                $val['gain_user_phone'] = M('user')->where(array('user_id' => $val['gain_user_id']))->getField('user_phone');
            }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/uncollected');
    }

    /*
    冻结收款方(卖出者)账户[定时任务已冻结过,这里只需帮助确认收款即可]
    */
    public function tosureget()
    {
        $theid = I('get.id');
        $config = M('config')->find(1);
        $m = M();
        $m->startTrans();
        try {
            //设置支付表的状态
            $result = M('PayedOrder')->where(array('id' => $theid))->save(['status' => '2', 'end_time' => time()]);
            $matchid = M('PayedOrder')->where(array('id' => $theid))->getField('match_id');
            $matchinfo = M('MatchOrder')->where(array('id' => $matchid))->find();
            //修改买入/卖出/匹配订单表的状态,同时增加利息记录
            $result1 = M('MatchOrder')->where(array('id' => $matchid))->save(['status' => '2', 'receive_time' => date('Y-m-d H:i:s', time())]);
            $result2 = M('AskhelpOrder')->where(array('id' => $matchinfo['sale_order_id']))->save(['status' => '2', 'matching' => '2']);
            $result3 = M('HelpOrder')->where(array('id' => $matchinfo['buy_order_id']))->save(['matching' => '2']);
            $data['user_id'] = $matchinfo['buy_id'];
            $data['buy_order'] = $matchinfo['buy_order_id'];
            $data['benjin'] = $matchinfo['amount'];
            $pamount = M('HelpOrder')->where(array('id' => $matchinfo['buy_order_id']))->getField('parent_amount');
            if ($config['order_limit1'] <= $pamount && $pamount <= $config['order_limit2']) {//A区间
                $data['amount'] = $matchinfo['amount'] * $config['interest_price1'] / 100;
            }
            if ($config['order_limit3'] <= $pamount && $pamount <= $config['order_limit4']) {//B区间
                $data['amount'] = $matchinfo['amount'] * $config['interest_price2'] / 100;
            }
            if ($config['order_limit5'] <= $pamount && $pamount <= $config['order_limit6']) {//C区间
                $data['amount'] = $matchinfo['amount'] * $config['interest_price3'] / 100;
            }
            $data['addtime'] = time();
            $data['status'] = '0';
            $data['statustow'] = '0';
            $result4 = M('interest')->add($data);
            //发放动态奖金,判断是否开启烧伤;先判断总单子是否交易完毕
            $porderid = M('HelpOrder')->where(array('id' => $matchinfo['buy_order_id']))->getField('parent_id');
            $allstatus = M('HelpOrder')->where(array('parent_id' => $porderid))->getField('matching', true);
            if (!in_array('0', $allstatus) && !in_array('1', $allstatus)) {//总单子已交易完成
                if ($config['dynamic_burn'] == 0) {//烧伤制度关闭
                    //查询买入方的一代/三代/五代,并判断五代是否是静态会员,是静态会员就不给动态奖励
                    $allparentid = M('user')->where(array('user_id' => $matchinfo['buy_id']))->getField('user_parent');
                    $allparent = array_reverse(explode(',', $allparentid));
                    $oneparent = $allparent[0];
                    $threeparent = $allparent[2];
                    $fiveparent = $allparent[4];
                    if ($oneparent) {//一代存在时,判断一代是否是动态会员
                        //$parentstatus=M('user')->where(array('user_id'=>$oneparent))->getField('vip_status');
                        //if ($parentstatus==2) {
                        $dongtaimoney = $pamount * $config['reward_rate1'] / 100;
                        $duihuanmoney = $dongtaimoney * $config['to_turn'] / 100;
                        $dongtaimoney = $dongtaimoney - $duihuanmoney;
                        $userwallet = M('wallet')->where(array('user_id' => $oneparent))->find();
                        M('wallet')->where(array('user_id' => $oneparent))->setInc('change_amount', $dongtaimoney);
                        M('wallet')->where(array('user_id' => $oneparent))->setInc('exchange_amount', $duihuanmoney);
                        //增加钱包变动记录
                        $parentuser = M('user')->where(array('id' => $oneparent))->find();
                        $data1['user_id'] = $oneparent;
                        $data1['user_name'] = $parentuser['user_name'];
                        $data1['user_phone'] = $parentuser['user_phone'];
                        $data1['amount'] = $dongtaimoney;
                        $data1['old_amount'] = $userwallet['change_amount'];
                        $data1['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                        $data1['change_date'] = time();
                        $data1['log_note'] = "一代动态奖金获取";
                        $data1['wallet_type'] = '2';
                        M('wallet_log')->add($data1);
                        $data2['user_id'] = $oneparent;
                        $data2['user_name'] = $parentuser['user_name'];
                        $data2['user_phone'] = $parentuser['user_phone'];
                        $data2['amount'] = $duihuanmoney;
                        $data2['old_amount'] = $userwallet['exchange_amount'];
                        $data2['remain_amount'] = $userwallet['exchange_amount'] + $duihuanmoney;
                        $data2['change_date'] = time();
                        $data2['log_note'] = "一代动态奖金获取,部分转入兑换钱包";
                        $data2['wallet_type'] = '3';
                        M('wallet_log')->add($data2);
                        //}
                    }
                    if ($threeparent) {//三代存在时,判断三代是否是静态会员
                        //$parentstatus=M('user')->where(array('user_id'=>$threeparent))->getField('vip_status');
                        //if ($parentstatus==2) {
                        $dongtaimoney = $pamount * $config['reward_rate2'] / 100;
                        $duihuanmoney = $dongtaimoney * $config['to_turn'] / 100;
                        $dongtaimoney = $dongtaimoney - $duihuanmoney;
                        $userwallet = M('wallet')->where(array('user_id' => $threeparent))->find();
                        M('wallet')->where(array('user_id' => $threeparent))->setInc('change_amount', $dongtaimoney);
                        M('wallet')->where(array('user_id' => $threeparent))->setInc('exchange_amount', $duihuanmoney);
                        //增加钱包变动记录
                        $parentuser = M('user')->where(array('id' => $threeparent))->find();
                        $data3['user_id'] = $threeparent;
                        $data3['user_name'] = $parentuser['user_name'];
                        $data3['user_phone'] = $parentuser['user_phone'];
                        $data3['amount'] = $dongtaimoney;
                        $data3['old_amount'] = $userwallet['change_amount'];
                        $data3['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                        $data3['change_date'] = time();
                        $data3['log_note'] = "三代动态奖金获取";
                        $data3['wallet_type'] = '2';
                        M('wallet_log')->add($data3);
                        $data4['user_id'] = $threeparent;
                        $data4['user_name'] = $parentuser['user_name'];
                        $data4['user_phone'] = $parentuser['user_phone'];
                        $data4['amount'] = $duihuanmoney;
                        $data4['old_amount'] = $userwallet['exchange_amount'];
                        $data4['remain_amount'] = $userwallet['exchange_amount'] + $duihuanmoney;
                        $data4['change_date'] = time();
                        $data4['log_note'] = "三代动态奖金获取,部分转入兑换钱包";
                        $data4['wallet_type'] = '3';
                        M('wallet_log')->add($data4);
                        //}
                    }
                    if ($fiveparent) {//五代存在时,判断五代是否是动态会员
                        $parentstatus = M('user')->where(array('user_id' => $fiveparent))->getField('vip_status');
                        if ($parentstatus == 2) {
                            $dongtaimoney = $pamount * $config['reward_rate3'] / 100;
                            $duihuanmoney = $dongtaimoney * $config['to_turn'] / 100;
                            $dongtaimoney = $dongtaimoney - $duihuanmoney;
                            $userwallet = M('wallet')->where(array('user_id' => $fiveparent))->find();
                            M('wallet')->where(array('user_id' => $fiveparent))->setInc('change_amount', $dongtaimoney);
                            M('wallet')->where(array('user_id' => $fiveparent))->setInc('exchange_amount', $duihuanmoney);
                            //增加钱包变动记录
                            $parentuser = M('user')->where(array('id' => $fiveparent))->find();
                            $data5['user_id'] = $fiveparent;
                            $data5['user_name'] = $parentuser['user_name'];
                            $data5['user_phone'] = $parentuser['user_phone'];
                            $data5['amount'] = $dongtaimoney;
                            $data5['old_amount'] = $userwallet['change_amount'];
                            $data5['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                            $data5['change_date'] = time();
                            $data5['log_note'] = "五代动态奖金获取";
                            $data5['wallet_type'] = '2';
                            M('wallet_log')->add($data5);
                            $data6['user_id'] = $fiveparent;
                            $data6['user_name'] = $parentuser['user_name'];
                            $data6['user_phone'] = $parentuser['user_phone'];
                            $data6['amount'] = $duihuanmoney;
                            $data6['old_amount'] = $userwallet['exchange_amount'];
                            $data6['remain_amount'] = $userwallet['exchange_amount'] + $duihuanmoney;
                            $data6['change_date'] = time();
                            $data6['log_note'] = "五代动态奖金获取,部分转入兑换钱包";
                            $data6['wallet_type'] = '3';
                            M('wallet_log')->add($data6);
                        }
                    }
                } else {//烧伤制度开启
                    //查询买入方的一代/三代/五代,并判断五代是否是静态会员,是静态会员就不给动态奖励
                    $allparentid = M('user')->where(array('user_id' => $matchinfo['buy_id']))->getField('user_parent');
                    $allparent = array_reverse(explode(',', $allparentid));
                    $oneparent = $allparent[0];
                    $threeparent = $allparent[2];
                    $fiveparent = $allparent[4];
                    if ($oneparent) {//一代存在时,判断一代是否是动态会员
                        //$parentstatus=M('user')->where(array('user_id'=>$oneparent))->getField('vip_status');
                        //if ($parentstatus==2) {//是动态会员
                        //查询最近一次买入金额
                        $lastmoney = M('HelpOrder')->where(array('user_id' => $oneparent))->order('addtime desc')->getField('parent_amount');
                        $basemoney = min($lastmoney, $pamount);
                        $dongtaimoney = $basemoney * $config['reward_rate1'] / 100;
                        $duihuanmoney = $dongtaimoney * $config['to_turn'] / 100;
                        $dongtaimoney = $dongtaimoney - $duihuanmoney;
                        $userwallet = M('wallet')->where(array('user_id' => $oneparent))->find();
                        M('wallet')->where(array('user_id' => $oneparent))->setInc('change_amount', $dongtaimoney);
                        M('wallet')->where(array('user_id' => $oneparent))->setInc('exchange_amount', $duihuanmoney);
                        //增加钱包变动记录
                        $parentuser = M('user')->where(array('id' => $oneparent))->find();
                        $data1['user_id'] = $oneparent;
                        $data1['user_name'] = $parentuser['user_name'];
                        $data1['user_phone'] = $parentuser['user_phone'];
                        $data1['amount'] = $dongtaimoney;
                        $data1['old_amount'] = $userwallet['change_amount'];
                        $data1['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                        $data1['change_date'] = time();
                        $data1['log_note'] = "一代动态奖金获取";
                        $data1['wallet_type'] = '2';
                        M('wallet_log')->add($data1);
                        $data2['user_id'] = $oneparent;
                        $data2['user_name'] = $parentuser['user_name'];
                        $data2['user_phone'] = $parentuser['user_phone'];
                        $data2['amount'] = $duihuanmoney;
                        $data2['old_amount'] = $userwallet['exchange_amount'];
                        $data2['remain_amount'] = $userwallet['exchange_amount'] + $duihuanmoney;
                        $data2['change_date'] = time();
                        $data2['log_note'] = "一代动态奖金获取,部分转入兑换钱包";
                        $data2['wallet_type'] = '3';
                        M('wallet_log')->add($data2);
                        //}
                    }
                    if ($threeparent) {//三代存在时,判断三代是否是静态会员
                        //$parentstatus=M('user')->where(array('user_id'=>$threeparent))->getField('vip_status');
                        //if ($parentstatus==2) {
                        //查询最近一次买入金额
                        $lastmoney = M('HelpOrder')->where(array('user_id' => $threeparent))->order('addtime desc')->getField('parent_amount');
                        $basemoney = min($lastmoney, $pamount);
                        $dongtaimoney = $basemoney * $config['reward_rate2'] / 100;
                        $duihuanmoney = $dongtaimoney * $config['to_turn'] / 100;
                        $dongtaimoney = $dongtaimoney - $duihuanmoney;
                        $userwallet = M('wallet')->where(array('user_id' => $threeparent))->find();
                        M('wallet')->where(array('user_id' => $threeparent))->setInc('change_amount', $dongtaimoney);
                        M('wallet')->where(array('user_id' => $threeparent))->setInc('exchange_amount', $duihuanmoney);
                        //增加钱包变动记录
                        $parentuser = M('user')->where(array('id' => $threeparent))->find();
                        $data3['user_id'] = $threeparent;
                        $data3['user_name'] = $parentuser['user_name'];
                        $data3['user_phone'] = $parentuser['user_phone'];
                        $data3['amount'] = $dongtaimoney;
                        $data3['old_amount'] = $userwallet['change_amount'];
                        $data3['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                        $data3['change_date'] = time();
                        $data3['log_note'] = "三代动态奖金获取";
                        $data3['wallet_type'] = '2';
                        M('wallet_log')->add($data3);
                        $data4['user_id'] = $threeparent;
                        $data4['user_name'] = $parentuser['user_name'];
                        $data4['user_phone'] = $parentuser['user_phone'];
                        $data4['amount'] = $duihuanmoney;
                        $data4['old_amount'] = $userwallet['exchange_amount'];
                        $data4['remain_amount'] = $userwallet['exchange_amount'] + $duihuanmoney;
                        $data4['change_date'] = time();
                        $data4['log_note'] = "三代动态奖金获取,部分转入兑换钱包";
                        $data4['wallet_type'] = '3';
                        M('wallet_log')->add($data4);
                        //}
                    }
                    if ($fiveparent) {//五代存在时,判断五代是否是静态会员
                        $parentstatus = M('user')->where(array('user_id' => $fiveparent))->getField('vip_status');
                        if ($parentstatus == 2) {
                            //查询最近一次买入金额
                            $lastmoney = M('HelpOrder')->where(array('user_id' => $fiveparent))->order('addtime desc')->getField('parent_amount');
                            $basemoney = min($lastmoney, $pamount);
                            $dongtaimoney = $basemoney * $config['reward_rate3'] / 100;
                            $duihuanmoney = $dongtaimoney * $config['to_turn'] / 100;
                            $dongtaimoney = $dongtaimoney - $duihuanmoney;
                            $userwallet = M('wallet')->where(array('user_id' => $fiveparent))->find();
                            M('wallet')->where(array('user_id' => $fiveparent))->setInc('change_amount', $dongtaimoney);
                            M('wallet')->where(array('user_id' => $fiveparent))->setInc('exchange_amount', $duihuanmoney);
                            //增加钱包变动记录
                            $parentuser = M('user')->where(array('id' => $fiveparent))->find();
                            $data5['user_id'] = $fiveparent;
                            $data5['user_name'] = $parentuser['user_name'];
                            $data5['user_phone'] = $parentuser['user_phone'];
                            $data5['amount'] = $dongtaimoney;
                            $data5['old_amount'] = $userwallet['change_amount'];
                            $data5['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                            $data5['change_date'] = time();
                            $data5['log_note'] = "五代动态奖金获取";
                            $data5['wallet_type'] = '2';
                            M('wallet_log')->add($data5);
                            $data6['user_id'] = $fiveparent;
                            $data6['user_name'] = $parentuser['user_name'];
                            $data6['user_phone'] = $parentuser['user_phone'];
                            $data6['amount'] = $duihuanmoney;
                            $data6['old_amount'] = $userwallet['exchange_amount'];
                            $data6['remain_amount'] = $userwallet['exchange_amount'] + $duihuanmoney;
                            $data6['change_date'] = time();
                            $data6['log_note'] = "五代动态奖金获取,部分转入兑换钱包";
                            $data6['wallet_type'] = '3';
                            M('wallet_log')->add($data6);
                        }
                    }
                }
            }
            $m->commit();
        } catch (PDOException $exc) {
            $m->rollback();
        }
        if ($result && $result1 && $result2 && $result3 && $result4) {
            $this->success('确认收款成功');
        } else {
            $this->error('确认收款失败');
        }
    }

    /*
    未收到款投诉
    */
    public function ungetmoney()
    {
        //实例化对象
        $matchorder = M('MatchOrder');
        $payorder = M('PayedOrder');

            //全部列表
            $count = $matchorder->where(array('status' => '3'))->count();
            $p = getpage($count, 15);
            $list = $matchorder->where(array('status' => '3'))->limit($p->firstRow, $p->listRows)->order('create_time desc')->select();
            foreach ($list as &$val) {
                $val['user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                $val['gain_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
                $val['buy_order_number'] = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_number');
                $val['gain_order_number'] = M('AskhelpOrder')->where(array('id' => $val['sale_order_id']))->getField('order_number');
                $val['amount'] = $payorder->where(array('match_id' => $val['id']))->getField('amount');
                $val['img_payed'] = $payorder->where(array('match_id' => $val['id']))->getField('img_payed');
                $val['create_time'] = $payorder->where(array('match_id' => $val['id']))->getField('create_time');
                $val['end_time'] = $payorder->where(array('match_id' => $val['id']))->getField('end_time');
            }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/ungetmoney');
    }

    /*
    *确认收款,并冻结卖出方账户
    */
    public function confirmreceipt()
    {
        $theid = I('get.id');
        $config = M('config')->find(1);
        $m = M();
        $m->startTrans();
        try {
            //冻结卖出方账户buyOrdertow
            $gainuserid = M('MatchOrder')->where(array('id' => $theid))->getField('sale_id');
            $result5 = M('user')->where(array('user_id' => $gainuserid))->setField('user_status', '0');
            //添加冻结原因
            M('user')->where(array('user_id' => $gainuserid))->setField('cold_resone', '假投诉');
            //设置支付表的状态
            $result = M('PayedOrder')->where(array('match_id' => $theid))->save(['status' => '2', 'end_time' => time()]);
            // $matchid=M('PayedOrder')->where(array('id'=>$theid))->getField('match_id');
            $list = M('MatchOrder')->where(array('id' => $theid))->find();
            //修改买入/卖出/匹配订单表的状态,同时增加利息记录
            $result1 = M('MatchOrder')->where(array('id' => $theid))->save(['status' => '2', 'receive_time' => date('Y-m-d H:i:s', time())]);
            $result2 = M('AskhelpOrder')->where(array('id' => $list['sale_order_id']))->save(['status' => '2', 'matching' => '2']);
            //判断卖出总订单是否已经交易完成
            $psaleorderid = M('AskhelpOrder')->where(array('id' => $list['sale_order_id']))->getField('parent_id');
            $allsalestatus = M('AskhelpOrder')->where(array('parent_id' => $psaleorderid))->where('order_type!=1')->getField('matching', true);
            if (!in_array('0', $allsalestatus) && !in_array('1', $allsalestatus)) {//总单子已交易完成
                //修改总单子的状态
                M('AskhelpOrder')->where(array('id' => $psaleorderid))->save(['matching' => '2', 'status' => '2']);
            }
            $result3 = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->save(['matching' => '2']);
            $thehelpinfo = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->find();
            if ($thehelpinfo['order_type'] == 0) {//是激活购买订单
                $yufutype = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->find();
                if ($yufutype) {//单子还存在时
                    $wallet = M('wallet')->where(array('user_id' => $yufutype['user_id']))->find();
                    $person = M('user')->where(array('user_id' => $yufutype['user_id']))->find();
                    $add['user_id'] = $yufutype['user_id'];
                    $add['user_name'] = $person['user_name'];
                    $add['user_phone'] = $person['user_phone'];
                    $add['amount'] = $yufutype['amount'];
                    $add['old_amount'] = $wallet['static_amount'];
                    $add['remain_amount'] = $wallet['static_amount'] + $yufutype['amount'];
                    $add['change_date'] = time();
                    $add['log_note'] = "激活购买股权";
                    $add['wallet_type'] = '1';
                    M('wallet_log')->add($add);
                }
                $yuyue = M('wallet')->where(array('user_id' => $yufutype['user_id']))->setInc(array('static_amount' => $yufutype['amount']));
                if ($yuyue) {
                    $user = M('user')->where(array('user_id' => $yufutype['user_id']))->find();
                    if($user['is_active'] == 0){
                        M('user')->where(array('user_id'=>$list['buy_order_id']))->data(array('is_active'=>1))->save();
                    }
                    $m->commit();
                    $this->success('确认收款成功');
                } else {
                    $this->error('确认收款失败');
                }
            } else {//是预约购买订单
                $yufutype = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->find();
                if ($yufutype) {//单子还存在时
                    $data['user_id'] = $list['buy_id'];
                    $data['buy_order'] = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('parent_id');//记录总订单的id
                    $pamount = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('parent_amount');
                    $amount = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('amount');
                    $data['benjin'] = $amount;//订单的金额
                    $data['amount'] = $amount * $config['interest_price'] / 100;//利息部分
                    $data['allamount'] = $data['benjin'] + $data['amount'];//本金+利息
                    $data['addtime'] = time();
                    $data['status'] = '1';
                    $data['statustow'] = '1';
                    $result4 = M('interest')->add($data);
                    $porderid = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('parent_id');
                    $allstatus = M('HelpOrder')->where(array('parent_id' => $porderid))->where('order_type!=0')->getField('matching', true);
                    if (!in_array('0', $allstatus) && !in_array('1', $allstatus)) {//总单子已交易完成
                        //修改总单子的状态
                        M('HelpOrder')->where(array('id' => $porderid))->save(['matching' => '2', 'status' => '1']);
                    }
                    //查询买入方的一代至七代
                    $allparentid = M('user')->where(array('user_id' => $list['buy_id']))->getField('user_parent');
                    $allparent = array_reverse(explode(',', $allparentid));
                    $oneparent = $allparent[0];
                    $towparent = $allparent[1];
                    $threeparent = $allparent[2];
                    if ($oneparent) {//一代存在时
                        $push = [
                            'user_parent' => array('like', array('%' . ',' . $oneparent, $oneparent), 'OR'),    //直推人数
                        ];
                        $push2 = [
                            'user_parent' => array('like', array($oneparent . ',' . '%', '%' . ',' . $oneparent, '%' . ',' . $oneparent . ',' . '%', $oneparent), 'OR'),   //团队人数
                        ];
                        $directpush = M('user')->where($push)->where(array('is_active=1'))->count();//直推
                        $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                        $theviplecel = getvipleveltow($directpush, $myteams);
                        //判断一代是否已激活
                        $isactive = M('user')->where(array('user_id' => $oneparent))->getField('is_active');
                        if ($isactive == 1) {
                            $dongtaimoney = $amount * $config['reward_rate1'] / 100;
                            $userwallet = M('wallet')->where(array('user_id' => $oneparent))->find();
                            M('wallet')->where(array('user_id' => $oneparent))->setInc('static_amount', $dongtaimoney);
                            //增加钱包变动记录
                            $parentuser = M('user')->where(array('user_id' => $oneparent))->find();
                            $data1['user_id'] = $oneparent;
                            $data1['user_name'] = $parentuser['user_name'];
                            $data1['user_phone'] = $parentuser['user_phone'];
                            $data1['amount'] = $dongtaimoney;
                            $data1['old_amount'] = $userwallet['static_amount'];
                            $data1['remain_amount'] = $userwallet['static_amount'] + $dongtaimoney;
                            $data1['change_date'] = time();
                            $data1['log_note'] = "一代股权奖励";
                            $data1['wallet_type'] = '1';
                            M('wallet_log')->add($data1);
                        }
                    }
                    if ($towparent) {//二代存在时
                        $push = [
                            'user_parent' => array('like', array('%' . ',' . $towparent, $towparent), 'OR'),    //直推人数
                        ];
                        $push2 = [
                            'user_parent' => array('like', array($towparent . ',' . '%', '%' . ',' . $towparent, '%' . ',' . $towparent . ',' . '%', $towparent), 'OR'),   //团队人数
                        ];
                        $directpush = M('user')->where($push)->where(array('is_active=1'))->count();//直推
                        $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                        $theviplecel = getvipleveltow($directpush, $myteams);
                        //判断二代是否激活
                        $isactive = M('user')->where(array('user_id' => $towparent))->getField('is_active');
                        if ($isactive == 1) {
                            $dongtaimoney = $amount * $config['reward_rate2'] / 100;
                            $userwallet = M('wallet')->where(array('user_id' => $towparent))->find();
                            M('wallet')->where(array('user_id' => $towparent))->setInc('static_amount', $dongtaimoney);
                            //增加钱包变动记录
                            $parentuser = M('user')->where(array('user_id' => $towparent))->find();
                            $data2['user_id'] = $towparent;
                            $data2['user_name'] = $parentuser['user_name'];
                            $data2['user_phone'] = $parentuser['user_phone'];
                            $data2['amount'] = $dongtaimoney;
                            $data2['old_amount'] = $userwallet['change_amount'];
                            $data2['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                            $data2['change_date'] = time();
                            $data2['log_note'] = "二代股权奖励";
                            $data2['wallet_type'] = '1';
                            M('wallet_log')->add($data2);
                        }
                    }
                    if ($threeparent) {//三代存在时
                        $push = [
                            'user_parent' => array('like', array('%' . ',' . $threeparent, $threeparent), 'OR'),    //直推人数
                        ];
                        $push2 = [
                            'user_parent' => array('like', array($threeparent . ',' . '%', '%' . ',' . $threeparent, '%' . ',' . $threeparent . ',' . '%', $threeparent), 'OR'),   //团队人数
                        ];
                        $directpush = M('user')->where($push)->where(array('is_active=1'))->count();//直推
                        $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                        $theviplecel = getvipleveltow($directpush, $myteams);
                        $isactive = M('user')->where(array('user_id' => $threeparent))->getField('is_active');
                        if ($isactive == 1) {
                            $dongtaimoney = $amount * $config['reward_rate3'] / 100;
                            $userwallet = M('wallet')->where(array('user_id' => $threeparent))->find();
                            M('wallet')->where(array('user_id' => $threeparent))->setInc('static_amount', $dongtaimoney);
                            //增加钱包变动记录
                            $parentuser = M('user')->where(array('user_id' => $threeparent))->find();
                            $data3['user_id'] = $threeparent;
                            $data3['user_name'] = $parentuser['user_name'];
                            $data3['user_phone'] = $parentuser['user_phone'];
                            $data3['amount'] = $dongtaimoney;
                            $data3['old_amount'] = $userwallet['change_amount'];
                            $data3['remain_amount'] = $userwallet['change_amount'] + $dongtaimoney;
                            $data3['change_date'] = time();
                            $data3['log_note'] = "三代股权奖励";
                            $data3['wallet_type'] = '1';
                            M('wallet_log')->add($data3);
                        }
                    }
//                    }
                }
            }
            $m->commit();
            if ($result4) {
                $this->success('确认收款成功');
            } else {
                $this->error('确认收款失败');
            }
        } catch (PDOException $exc) {
            $m->rollback();
        }
        //$result3因同一其他子单子超时未打款而删除该子单子,造成帮助表中已经没有了该单子,从而造成确认收款失败
    }

    /*
    驳回交易,并冻结买入方账户
    */
    public function reject()
    {
        $theid = I('request.id');
        $payuserid = M('PayedOrder')->where(array('match_id' => $theid))->getField('user_id');
        $m = M();
        $m->startTrans();
        try {
            //根据匹配订单信息,还原卖出单子,并删除买入单子和匹配记录
            $matchinfo = M('MatchOrder')->where(array('id' => $theid))->find();
            $result2 = M('AskhelpOrder')->where(array('id' => $matchinfo['sale_order_id']))->save(['matching' => '0', 'status' => '0']);
            if(!$result2){
                $m->rollback();
            }
            //根据买入单子找到总的订单编号,修改订单总额
            $buyorder = M('HelpOrder')->where(array('id' => $matchinfo['buy_order_id']))->find();
            if ($buyorder) {
                if ($buyorder['parent_id'] != 0) {
                    $result3 = M('HelpOrder')->where(array('id' => $buyorder['parent_id']))->setDec('parent_amount', $buyorder['amount']);//删除总订单的金额为当前订单的金额
                    if (!$result3) {
                        $m->rollback();
                    }
                }
                $resul = M('HelpOrder')->where(array('id' => $buyorder['parent_id']))->setDec('amount', $buyorder['amount']);//删除总订单的金额为当前订单的金额
                if(!$resul){
                    $m->rollback();
                }
                //判断所有子订单是否交易完,交易完就修改总订单状态
                //0是激活购买
                if ($buyorder['order_type'] == 0) {
                    $res = M('HelpOrder')->where(array('id' => $buyorder['id']))->delete();
                } else {//预约购买
                    $where['parent_id'] = $buyorder['parent_id'];
                    $where['status'] = array('neq',1);
                    $where['matching'] = array('neq',2);
                    $where['order_type'] = array('neq', 0);
                    $where['id'] = array('neq', $buyorder['id']);
                    $flag = M('HelpOrder')->where($where)->select();//有未完成的订单
//                    $flag;die;
                    if (!$flag) {
                        M('HelpOrder')->where(array('id' => $buyorder['parent_id']))->save(['matching' => '2', 'status' => '1']);
                    }
                    $res = M('HelpOrder')->where(array('id' => $buyorder['id']))->delete();
                }
            }
            //冻结买入方账户
            $result1 = M('user')->where(array('user_id' => $payuserid))->setField('user_status', '0');
//            dump($result1);die;
            if(!$result1){
                $m->rollback();
            }
            //添加冻结原因
            M('user')->where(array('user_id' => $payuserid))->setField('cold_resone', '假打款');
            //删除交易订单
            $result = M('PayedOrder')->where(array('match_id' => $theid))->delete();
            if(!$result){
                $m->rollback();
            }
            $result5 =  M('MatchOrder')->where(array('id' => $theid))->delete();
            if (!$result5) {
                $m->rollback();
            }

            if ($res && $result && $result1 && $result2 && $result5) {
                $this->success('驳回交易成功');
            } else {
                $m->rollback();
                $this->error('驳回交易失败');
            }
            $m->commit();
        } catch (PDOException $exc) {
            $m->rollback();
        }
    }

    /*
    订单还原  最终超时未打款的话就订单还原
    */
    public function backorder()
    {
        $theid = I('get.id');//获取匹配订单序号
        $userxx = M('match_order')->where(array('id' => $theid))->find();
        if ($theid <> '' && $userxx['id'] <> '') {
            M('match_order')->where(array('id' => $theid))->delete();//删除匹配的订单
            $buyorderinfo = M('help_order')->where(array('id' => $userxx['buy_order_id']))->find();
            M('help_order')->where(array('id' => $buyorderinfo['parent_id']))->setDec('amount', $buyorderinfo['amount']);
            M('help_order')->where(array('parent_id' => $buyorderinfo['parent_id']))->setDec('parent_amount', $buyorderinfo['amount']);
            M('help_order')->where(array('id' => $userxx['buy_order_id']))->delete();
            M('askhelp_order')->where(array('id' => $userxx['sale_order_id']))->save(['matching' => 0]);
            $this->success('还原成功!');
        } else {
            $this->error('订单不存在!');
        }
    }

    /*
    删除已经匹配的订单
    */
    public function delmatchorder()
    {
        $theid = I('get.id');//获取匹配订单序号  M('help_order')->where(array('id' => $givehelp))->delete()&&M('askhelp_order')->where(array('id' => $gethelp))->delete()
        $givehelp = M('match_order')->where(array('id' => $theid))->getField('buy_order_id');
        $gethelp = M('match_order')->where(array('id' => $theid))->getField('sale_order_id');
        //还原订单
        if (M('match_order')->where(array('id' => $theid))->delete() && M('help_order')->where(array('id' => $givehelp))->save(['matching' => '0', 'status' => '0']) && M('askhelp_order')->where(array('id' => $gethelp))->save(['matching' => '0', 'status' => '0'])) {    //删除匹配的订单,买入和卖出订单都会一并删除
            $this->success('匹配的订单删除成功!');
        } else {
            $this->error('匹配的订单删除失败!');
        }
    }

    /*
    交易成功订单
    */
    public function successfulOrder()
    {
        $wallet = M('match_order');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['user_name'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'status' => array('eq', 2),
                ];
                //$map['type'] = 2;
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('receive_time desc')->select();
                foreach ($list as &$val) {
                    $val['buy_order_number'] = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_number');
                    $val['sale_order_number'] = M('AskhelpOrder')->where(array('id' => $val['sale_order_id']))->getField('order_number');
                    $val['buy_user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                    $val['sale_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
                }
            } else {
                //先根据手机号获取买入方id
                $thebuyid = M('user')->where(array('user_phone' => $data['user_name']))->getField('user_id');
                $map = [
                    'buy_id' => $thebuyid,
                    'status' => array('eq', 2),
                ];
                //dump($map);die;
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('receive_time desc')->select();
                foreach ($list as &$val) {
                    $val['buy_order_number'] = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_number');
                    $val['sale_order_number'] = M('AskhelpOrder')->where(array('id' => $val['sale_order_id']))->getField('order_number');
                    $val['buy_user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                    $val['sale_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
                }
            }
        } else {
            //全部列表
            $map = [
                'status' => array('eq', 2),
            ];
            //$map['type'] = 2;
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('receive_time desc')->select();
            foreach ($list as &$val) {
                $val['buy_order_number'] = M('HelpOrder')->where(array('id' => $val['buy_order_id']))->getField('order_number');
                $val['sale_order_number'] = M('AskhelpOrder')->where(array('id' => $val['sale_order_id']))->getField('order_number');
                $val['buy_user_phone'] = M('user')->where(array('user_id' => $val['buy_id']))->getField('user_phone');
                $val['sale_user_phone'] = M('user')->where(array('user_id' => $val['sale_id']))->getField('user_phone');
            }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        $this->display('Wealth/successful_order');
    }

    /*
    交易成功记录删除
    */
    public function deleteorder()
    {
        $theid = I('get.id');//获取匹配订单序号
        $userxx = M('match_order')->where(array('id' => $theid))->find();
        if ($theid <> '' && $userxx['id'] <> '') {
            M('match_order')->where(array('id' => $theid))->delete();//删除匹配的订单
            M('help_order')->where(array('id' => $userxx['buy_order_id']))->delete();//根据订单编号删除买入的订单记录
            M('askhelp_order')->where(array('id' => $userxx['sale_order_id']))->delete();//根据订单编号删除卖出的订单记录
            $this->success('删除成功!');
        } else {
            $this->error('订单不存在!');
        }
    }

    /*
    买入订单拆分列表
    */
    public function buysplitOrder()
    {
        //实例化对象
        $wallet = M('help_order');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['start']) || empty($data['end'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'order_type' => '2',//必须是非预付款订单
                    'matching' => array('eq', 0),
                ];
                //$map['type'] = 2;
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
                // foreach ($list as &$val) {
                //     $val['order_number']='P'.$val['id'];
                // }
            } else {
                $time1 = $data['start'];
                $time2 = $data['end'];
                //dump($time1);dump($time2);die;
                $map = [
                    // 'user_name'=>$data['user_name'],
                    'order_type' => '2',//必须是非预付款订单
                    'matching' => array('eq', 0),
                ];
                //在['addtime' => ['between', "$time1,$time2"]]中,$time1与$time2必须用双引号括起来,单引号无效,会出错
                $count = $wallet->where($map)->where(['addtime' => ['between', "$time1,$time2"]])->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->where(['addtime' => ['between', "$time1,$time2"]])->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
                // foreach ($list as &$val) {
                //     $val['order_number']='P'.$val['id'];
                // }
            }
        } else {
            //全部列表
            $map = [
                'order_type' => '2',//必须是非预付款订单
                'matching' => array('eq', 0),
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            // foreach ($list as &$val) {
            //     $val['order_number']='P'.$val['id'];
            // }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        $this->display('Wealth/buysplit_order');
    }

    /*
    买入订单拆分
    */
    public function tosplitOrderag()
    {
        $data = I('post.');
        $p_user = M('help_order')->where(array('id' => $data['pid']))->find();
        if (!preg_match('/^[0-9,]{1,100}$/', I('post.arrid'))) {
            $this->error('格式不对!数字之间请用英文逗号隔开');
            die;
        }
        $arr = explode(',', I('post.arrid'));
        for ($i = 0; $i < count($arr); $i++) {
            if ($arr[$i] < 100) {
                $this->error('拆分的最低面额不能低于100!');
                die;
            }
        }
        if (array_sum($arr) <> $p_user['amount']) {
            $this->error('拆分金额不对!拆分额度之和不等于总额度');
            die;
        }
        $p_user1 = M('help_order')->where(array('id' => $data['pid']))->find();
        $pipeits = 0;
        foreach ($arr as $value) {
            if ($value <> '') {
                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
                // $data2['order_number']=$themaxorder+1;//订单编号增加1
                $data2['user_id'] = $p_user1['user_id'];
                $data2['user_name'] = $p_user1['user_name'];
                $data2['user_truename'] = $p_user1['user_truename'];
                $data2['user_phone'] = $p_user1['user_phone'];
                $data2['amount'] = $value;
                $data2['order_number'] = date('YmdHis', time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $data2['order_type'] = $p_user1['order_type'];
                //$data2['pay_type'] = $p_user1['pay_type'];
                // $data2['pay_count_id'] = $p_user1['pay_count_id'];
                $data2['matching'] = $p_user1['matching'];
                $data2['status'] = $p_user1['status'];
                $data2['parent_amount'] = $p_user1['parent_amount'];
                $data2['parent_id'] = $p_user1['parent_id'];
                $data2['addtime'] = $p_user1['addtime']; //添加时间还是按照原始的添加时间进行记录
                $data2['is_good'] = $p_user1['is_good'];
                $varid = M('help_order')->add($data2);
                $pipeits++;
            }
        }
        M('help_order')->where(array('id' => $data['pid']))->delete();
        $this->success('拆分成功!共拆分成' . $pipeits . '条订单!');
    }

    /*
    卖出订单拆分列表
    */
    public function salesplitOrder()
    {
        //实例化对象
        $wallet = M('askhelp_order');
        if (IS_POST) {
            $data = I('post.');
            if (empty($data['start']) || empty($data['end'])) {
                // $this->error('请输入用户账号查询');
                //全部列表
                $map = [
                    'matching' => array('eq', 0),
                ];
                //$map['type'] = 2;
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
                // foreach ($list as &$val) {
                //     $val['order_number']='G'.$val['id'];
                // }
            } else {
                // $reg = '/^1[3|4|5|6|7|8|9][0-9]{9}$/';
                // if(preg_match($reg,$data['user_phone'])==0 && !is_numeric($data['user_phone'])){
                //     $this->error('请输入正确用户账号查询');
                // }
                // $time1=strtotime($data['start']);
                // $time2=strtotime($data['end']);
                $time1 = $data['start'];
                $time2 = $data['end'];
                //dump($time1);dump($time2);die;
                $map = [
                    // 'user_name'=>$data['user_name'],
                    'matching' => array('eq', 0),
                ];
                //在['addtime' => ['between', "$time1,$time2"]]中,$time1与$time2必须用双引号括起来,单引号无效,会出错
                $count = $wallet->where($map)->where(['addtime' => ['between', "$time1,$time2"]])->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->where(['addtime' => ['between', "$time1,$time2"]])->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
                // foreach ($list as &$val) {
                //     $val['order_number']='G'.$val['id'];
                // }
            }
        } else {
            //全部列表
            $map = [
                'matching' => array('eq', 0),
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            // foreach ($list as &$val) {
            //     $val['order_number']='G'.$val['id'];
            // }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        $this->display('Wealth/salesplit_order');
    }

    /*
    卖出订单拆分
    */
    public function tosplitOrder()
    {
        $data = I('post.');
        $p_user = M('askhelp_order')->where(array('id' => $data['pid']))->find();
        if (!preg_match('/^[0-9,]{1,100}$/', I('post.arrid'))) {
            $this->error('格式不对!数字之间请用英文逗号隔开');
            die;
        }
        $arr = explode(',', I('post.arrid'));
        for ($i = 0; $i < count($arr); $i++) {
            if ($arr[$i] < 100) {
                $this->error('拆分的最低面额不能低于100!');
                die;
            }
        }
        if (array_sum($arr) <> $p_user['amount']) {
            $this->error('拆分金额不对!拆分额度之和不等于总额度');
            die;
        }
        $p_user1 = M('askhelp_order')->where(array('id' => $data['pid']))->find();
        $pipeits = 0;
        foreach ($arr as $value) {
            if ($value <> '') {
                // $themaxorder=M('help_order')->max('order_number');//获取订单字段中的最大值
                // $data2['order_number']=$themaxorder+1;//订单编号增加1
                $data2['user_id'] = $p_user1['user_id'];
                $data2['user_name'] = $p_user1['user_name'];
                $data2['user_truename'] = $p_user1['user_truename'];
                $data2['user_phone'] = $p_user1['user_phone'];
                $data2['amount'] = $value;
                $data2['order_number'] = date('YmdHis', time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $data2['wallet_type'] = $p_user1['wallet_type'];
                $data2['get_way'] = $p_user1['get_way'];
                $data2['account_number'] = $p_user1['account_number'];
                // $data2['pay_count_id'] = $p_user1['pay_count_id'];
                $data2['matching'] = $p_user1['matching'];
                $data2['status'] = $p_user1['status'];
                $data2['parent_amount'] = $p_user1['parent_amount'];
                $data2['parent_id'] = $p_user1['parent_id'];
                $data2['addtime'] = $p_user1['addtime']; //添加时间还是按照原始的添加时间进行记录
                $varid = M('askhelp_order')->add($data2);
                $pipeits++;
            }
        }
        //判断拆分的是否是总订单,总订单的话就不要删除
        if ($p_user['order_type'] == '2') {//是子订单
            M('askhelp_order')->where(array('id' => $data['pid']))->delete();
        }
        $this->success('拆分成功!共拆分成' . $pipeits . '条订单!');
    }

    /*
    抢单池列表
    */
    public function snatchpool()
    {
        if (IS_POST) {
            $thename = I('request.user_name');
            if ($thename) {
                $count = M('help_order')->where(array('user_name' => $thename, 'status' => 0, 'snatch_pool' => 1))->count();
                $p = getpage($count, 15);
                $list = M('help_order')->where(array('user_name' => $thename, 'status' => 0, 'snatch_pool' => 1))->select();//待支付且状态为可抢单,用混抢完单后一定记得把订单状态改为正常订单
                foreach ($list as &$val) {
                    $val['order_number'] = 'P' . $val['id'];
                }
            } else {
                $count = M('help_order')->where(array('status' => 0, 'snatch_pool' => 1))->count();
                $p = getpage($count, 15);
                $list = M('help_order')->where(array('status' => 0, 'snatch_pool' => 1))->select();//待支付且状态为可抢单,用混抢完单后一定记得把订单状态改为正常订单
                foreach ($list as &$val) {
                    $val['order_number'] = 'P' . $val['id'];
                }
            }
        } else {
            $count = M('help_order')->where(array('status' => 0, 'snatch_pool' => 1))->count();
            $p = getpage($count, 15);
            $list = M('help_order')->where(array('status' => 0, 'snatch_pool' => 1))->select();//待支付且状态为可抢单,用混抢完单后一定记得把订单状态改为正常订单
            foreach ($list as &$val) {
                $val['order_number'] = 'P' . $val['id'];
            }
        }
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        $this->display('Wealth/snatch_pool');
    }

    /*
    抢单池订单删除
    */
    public function snatchpooldel()
    {
        $theid = I('request.id');
        $orderrel = M('help_order')->where(array('id' => $theid))->find();
        if ($theid <> '' && $orderrel['id'] <> '') {
            //根据id找到卖出的订单编号,更改卖出的订单状态,改为待匹配
            $gethelp = M('match_order')->where(array('buy_order_id' => $theid))->getField('sale_order_id');
            $result = M('askhelp_order')->where(array('id' => $gethelp))->save(['matching' => 0]);
            $result1 = M('match_order')->where(array('buy_order_id' => $theid))->delete();
            $result2 = M('help_order')->where(array('id' => $orderrel['id']))->delete();
            if ($result && $result1 && $result2) {
                $this->success('删除成功!');
            } else {
                $this->error('删除失败!');
            }
        } else {
            $this->error('订单不存在!');
        }
    }

    /*
    抢单池订单改为正常订单
    */
    public function tobenormal()
    {
        $theid = I('request.id');
        $orderrel = M('help_order')->where(array('id' => $theid))->find();
        if ($theid <> '' && $orderrel['id'] <> '') {
            //根据id找到卖出的订单编号,更改卖出的订单状态,改为待匹配
            $gethelp = M('match_order')->where(array('buy_order_id' => $theid))->getField('sale_order_id');
            $result = M('askhelp_order')->where(array('id' => $gethelp))->save(['matching' => 0]);
            //删除匹配记录
            $result1 = M('match_order')->where(array('buy_order_id' => $theid))->delete();
            //修改买入订单状态,改成待匹配和正常订单
            $result2 = M('help_order')->where(array('id' => $orderrel['id']))->save(['matching' => 0, 'snatch_pool' => 0]);
            if ($result && $result1 && $result2) {
                $this->success('更改成功!');
            } else {
                $this->error('更改失败!');
            }
        } else {
            $this->error('订单不存在!');
        }
    }

    /*
    邀请码购买订单
    */
    public function codeOrder()
    {
        //实例化对象
        $wallet = M('UserCodeOrder');//邀请码购买表
        if (IS_POST) {
            $data = I('request.');
            // dump($data);die;
            if (!$data['user_phone']) {
                $map = [
                    'status' => ['in', ['0', '2']],
                    'is_del' => 0,
                ];
                $count = $wallet->where($map)->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
                foreach ($list as &$val) {
                    $val['username'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_name');
                    $val['phone'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_phone');
                }
            } else {
                // $reg = '/^1[3|4|5|6|7|8|9][0-9]{9}$/';
                // if(preg_match($reg,$data['user_phone'])==0 || !is_numeric($data['user_phone'])){
                //     $this->error('请输入正确用户账号查询');
                //     //die("<script charset='UTF-8'>alert('请输入正确用户账号查询');history.back(-1);</script>");
                // }else{
                $map = [
                    'status' => ['in', ['0', '2']],
                    'is_del' => 0,
                ];
                $theuserid = M('user')->where(array('user_name' => $data['user_phone']))->getField('user_id');
                $count = $wallet->where($map)->where(array('user_id' => $theuserid))->count();
                $p = getpage($count, 15);
                $list = $wallet->where($map)->where(array('user_id' => $theuserid))->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
                foreach ($list as &$val) {
                    $val['username'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_name');
                    $val['phone'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_phone');
                }
                // }
            }
        } else {
            //全部列表
            //$map['status'] = 0;
            $map = [
                'status' => ['in', ['0', '2']],
                'is_del' => 0,
            ];
            $count = $wallet->where($map)->count();
            $p = getpage($count, 15);
            $list = $wallet->where($map)->limit($p->firstRow, $p->listRows)->order('addtime asc')->select();
            foreach ($list as &$val) {
                $val['username'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_name');
                $val['phone'] = M('user')->where(array('user_id' => $val['user_id']))->getField('user_phone');
            }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        //展示页面
        $this->display('Wealth/code_order');
    }

    /*
    邀请码充值
    */
    public function topay()
    {
        $id = I('request.id');
        $userid = M('user_code_order')->where(array('id' => $id))->getField('user_id');
        $count = M('user_code_order')->where(array('id' => $id))->getField('number');
        $num = 0;
        for ($i = 0; $i < $count; $i++) { //根据用户购买数量,依次添加邀请码
            $data['user_id'] = $userid;
            $data['code'] = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);//从1到99999999中随机选一个数,不是六位数时左侧补0
            $data['addtime'] = time();
            $result = M('user_active_code')->data($data)->add();
            if ($result) {//添加成功时$num值加一
                $num++;
            }
        }
        if ($num == $count) {//检测邀请码充值数目是否够数
            //改变邀请码充值表中的状态值
            $rel = M('user_code_order')->where(array('id' => $id))->save(['status' => '1', 'givetime' => time()]);
            if ($rel) {
                $this->success('邀请码充值成功!');
            } else {
                $this->error('充值成功!但订单状态修改失败');
            }
        } else {
            $this->error('邀请码充值失败!');
        }

    }

    /*
    邀请码不充值
    */
    public function nopay()
    {
        $id = I('request.id');
        //dump($id);die;
        $result = M('user_code_order')->where(array('id' => $id))->save(['status' => '2', 'givetime' => time()]);
        if ($result) {
            // $this->ajaxReturn(1);
            $this->success('拒绝成功!');
        } else {
            $this->error('拒绝失败!');
        }
    }

    /*
    邀请码不充值后可以删除该记录
    */
    public function orderdel()
    {
        $id = I('request.id');
        //dump($id);die;
        $result = M('user_code_order')->where(array('id' => $id))->save(['is_del' => '1']);
        if ($result) {
            // $this->ajaxReturn(1);
            $this->success('删除成功!');
        } else {
            $this->error('删除失败!');
        }
    }

    //手动快速匹配
    public function fastmatching()
    {
        $help = M('HelpOrder');
        $sale = M('AskhelpOrder');
        $type = I('request.type');
        if (!stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值
            $_SESSION['check_p']['check_id'] = ",";
            $_SESSION['check_p']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p']['check_money'])) {
            $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
        }
        if (!stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值
            $_SESSION['check_p2']['check_id'] = ",";
            $_SESSION['check_p2']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p2']['check_money'])) {
            $this->assign('check_array2', explode(",", $_SESSION['check_p']['check_id']));
        }
        if (empty($type)) {//预付款
            // 查询总记录数
            $count = $help->where('order_type=1 and matching=0')->count();
            $p = getpage($count, 50);
            //查询数据
            $list = $help->where('order_type=1 and matching=0')->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
            foreach ($list as &$val) {
                $addtime = strtotime($val['addtime']);
                $val['hourse'] = intval((time() - $addtime) / 3600);
            }
            $buymoney = $help->where('order_type=1 and matching=0')->sum('amount');
        } else {//非预付款
            // 查询总记录数
            $count = $help->where('order_type=2 and matching=0')->count();
            $p = getpage($count, 50);
            //查询数据
            $list = $help->where('order_type=2 and matching=0')->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
            foreach ($list as &$val) {
                $addtime = strtotime($val['addtime']);
                $val['hourse'] = intval((time() - $addtime) / 3600);
            }
            $buymoney = $help->where('order_type=2 and matching=0')->sum('amount');
        }
        //查询卖出未匹配订单
        $nochilds = array();
        $askparentid = M('AskhelpOrder')->where('order_type=1 and matching=0')->getField('id', true);
        for ($i = 0; $i < count($askparentid); $i++) {
            $saleresult = M('AskhelpOrder')->where(array('parent_id' => $askparentid[$i], 'order_type' => '2'))->find();
            if (!$saleresult) {
                $nochilds[] = $askparentid[$i];
            }
        }
        //$nochilds=json_encode($nochilds);
        // 查询总记录数
        $where['_string'] = 'order_type=2 AND matching=0';
        if ($nochilds) {
            $where['id'] = array('in', $nochilds);
            $where['_logic'] = 'or';
        }
        $count2 = $sale->where($where)->count();
        $p2 = getpage($count2, 50);
        //查询数据
        $listone = $sale->where($where)->order('addtime asc')->limit($p2->firstRow, $p2->listRows)->select();
        foreach ($listone as &$value) {
            $addtime2 = strtotime($value['addtime']);
            $value['hoursee'] = intval((time() - $addtime2) / 3600);
        }
        $salemoney = $sale->where($where)->sum('amount');
        //买入勾选记录
        if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
            $_SESSION['check_p']['check_id'] = ",";
            $_SESSION['check_p']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p']['check_money'])) {
            $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
        }
        //卖出勾选记录
        if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
            $_SESSION['check_p2']['check_id'] = ",";
            $_SESSION['check_p2']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p2']['check_money'])) {
            $this->assign('check_array2', explode(",", $_SESSION['check_p2']['check_id']));
        }
//        dump($list);die;
        $this->assign('list', $list);
        $this->assign('listone', $listone);
        $this->assign('page', $p->show());//预付和非预付公用
        $this->assign('page2', $p2->show());//未匹配卖出订单数
        $this->assign('buymoney', $buymoney);
        $this->assign('salemoney', $salemoney);
        $this->assign('check_id', $_SESSION['check_p']['check_id']);
        $this->assign('check_money', $_SESSION['check_p']['check_money']);
        $this->assign('check_id2', $_SESSION['check_p2']['check_id']);
        $this->assign('check_money2', $_SESSION['check_p2']['check_money']);
        $this->display('Wealth/fastmatching');
    }

    //手动确认匹配
    public function fastmatchone()
    {
        $data = I('post.');
        $arr = explode(',', I('post.arrid'));
        $arr = array_filter($arr);//检测数组中值是否为空,为空就舍弃,键名不变
        rsort($arr);//对数组中的元素进行将序排列,键名改变
        $arr2 = explode(',', I('post.arrid2'));
        $arr2 = array_filter($arr2);//检测数组中值是否为空,为空就舍弃,键名不变
        rsort($arr2);//对数组中的元素进行将序排列,键名改变
        if (empty($arr) || empty($arr2)) {
            $this->ajaxReturn(['status' => '0', 'message' => '请勾选匹配项']);
        } else {
            //获取买入和卖出用户的手机号
            $buyphone = array();
            $salephone = array();
            for ($i = 0; $i < count($arr); $i++) {
                $buyphone[] = M('help_order')->where(array('id' => $arr[$i]))->getField('user_phone');
            }
            for ($j = 0; $j < count($arr2); $j++) {
                $salephone[] = M('askhelp_order')->where(array('id' => $arr2[$j]))->getField('user_phone');
            }
            if (array_intersect($buyphone, $salephone)) {
                //$this->error('同一用户的帮助和得到订单不能相互匹配',U('/Adminlmcq/Wealth/fastmatching'));
                $this->ajaxReturn(['status' => '0', 'message' => '同一用户的帮助和得到订单不能相互匹配']);
            } else {
                $output = M('askhelp_order')->where(['id' => ['in', $arr2]])->select();//依次查询选中的卖出订单信息
                $input = M('help_order')->where(['id' => ['in', $arr]])->select();//查询买入订单信息
//                dump($output);dump($input);die;
                $num = auto_match_c($input, $output);
                //$this->success('订单匹配成功',U('/Adminlmcq/Wealth/fastmatching'));//匹配成功后跳转到买入订单列表页
                $this->ajaxReturn(['status' => '1', 'message' => '订单匹配成功']);
            }
        }
    }

    //申请提现列表
    public function tixian()
    {
        $list = M('tixian_log t')->join('mf_user u on t.user_id = u.user_id')->where(array('t.status' => 0))->select();
        $this->assign('list', $list);
        $this->display('Wealth/tixian');
    }

    //同意提现
    public function tixian_succ()
    {
        $tid = I('request.tid');
        $res = M('tixian_log')->where(['id' => $tid])->save(['status' => 1]);
        if ($res) {
            $this->success('同意提现操作成功');
        }
    }//拒绝提现

    public function tixian_err()
    {
        $tid = I('request.tid');
        $res = M('tixian_log')->where(['id' => $tid])->save(['status' => 2]);
        if ($res) {
            $log = M('tixian_log')->where(['id' => $tid])->find();
            $res1 = 0;
            if ($log['type' == 1]) {
                $res1 = M('wallet')->where(['user_id' => $log['user_id']])->setInc('static_amount', $log['amount']);
            } else {
                $res1 = M('wallet')->where(['user_id' => $log['user_id']])->setInc('change_amount', $log['amount']);
            }
            if ($res1) {
                $this->error('拒绝提现操作成功');
            }
        }
        $this->error('拒绝提现操作成功');
    }

    public function del_help()
    {
        $tid = I('request.tid');
        $res = M('help_order')->where(['id' => $tid])->delete();
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function del_ask()
    {
        $tid = I('request.tid');
        $res = M('askhelp_order')->where(['id' => $tid])->delete();
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    //按时间搜索快速匹配
    public function search()
    {
        $search = I('post.search');
        $time = I('post.time');
        $beg = strtotime(substr($time, 0, 10));
        $end = strtotime(substr($time, 13));
        $help = M('HelpOrder');
        $sale = M('AskhelpOrder');
        $type = I('request.type');
        if (!stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值
            $_SESSION['check_p']['check_id'] = ",";
            $_SESSION['check_p']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p']['check_money'])) {
            $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
        }
        if (!stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值
            $_SESSION['check_p2']['check_id'] = ",";
            $_SESSION['check_p2']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p2']['check_money'])) {
            $this->assign('check_array2', explode(",", $_SESSION['check_p']['check_id']));
        }
        if (empty($type)) {//预付款
            // 查询总记录数
            $count = $help->where('order_type=1 and matching=0')->count();
            $p = getpage($count, 50);
            //查询数据
            $list = $help->where('order_type=1 and matching=0')->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
            foreach ($list as &$val) {
                $addtime = strtotime($val['addtime']);
                $val['hourse'] = intval((time() - $addtime) / 3600);
            }
            $buymoney = $help->where('order_type=1 and matching=0')->sum('amount');
        } else {//非预付款
            // 查询总记录数
            $count = $help->where('order_type=2 and matching=0')->count();
            $p = getpage($count, 50);
            //查询数据
            $list = $help->where('order_type=2 and matching=0')->order('addtime asc')->limit($p->firstRow, $p->listRows)->select();
            foreach ($list as &$val) {
                $addtime = strtotime($val['addtime']);
                $val['hourse'] = intval((time() - $addtime) / 3600);
            }
            $buymoney = $help->where('order_type=2 and matching=0')->sum('amount');
        }
        //查询卖出未匹配订单
        $nochilds = array();
        $askparentid = M('AskhelpOrder')->where('order_type=1 and matching=0')->getField('id', true);
        for ($i = 0; $i < count($askparentid); $i++) {
            $saleresult = M('AskhelpOrder')->where(array('parent_id' => $askparentid[$i], 'order_type' => '2'))->find();
            if (!$saleresult) {
                $nochilds[] = $askparentid[$i];
            }
        }
        //$nochilds=json_encode($nochilds);
        // 查询总记录数
        $where['_string'] = 'order_type=2 AND matching=0';
        if ($nochilds) {
            $where['id'] = array('in', $nochilds);
            $where['_logic'] = 'or';
        }
        $count2 = $sale->where($where)->count();
        $p2 = getpage($count2, 50);
        //查询数据
        $listone = $sale->where($where)->order('addtime asc')->limit($p2->firstRow, $p2->listRows)->select();
        foreach ($listone as &$value) {
            $addtime2 = strtotime($value['addtime']);
            $value['hoursee'] = intval((time() - $addtime2) / 3600);
        }
        $salemoney = $sale->where($where)->sum('amount');
        //买入勾选记录
        if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
            $_SESSION['check_p']['check_id'] = ",";
            $_SESSION['check_p']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p']['check_money'])) {
            $this->assign('check_array', explode(",", $_SESSION['check_p']['check_id']));
        }
        //卖出勾选记录
        if (stristr($_SERVER['HTTP_REFERER'], ACTION_NAME)) {//初始化选中值,筛选后session池清空重新选择
            $_SESSION['check_p2']['check_id'] = ",";
            $_SESSION['check_p2']['check_money'] = 0;
        }
        //已选择的数据
        if (!empty($_SESSION['check_p2']['check_money'])) {
            $this->assign('check_array2', explode(",", $_SESSION['check_p2']['check_id']));
        }
        $newlsit = [];
        if ($search == 1) {
            foreach ($list as $k => $v) {
                $time = strtotime($v['addtime']);
                if ($beg < $time && $time < $end) {
                    array_push($newlsit, $v);
                }
            }
        } else {
            foreach ($listone as $k => $v) {
                $time = strtotime($v['addtime']);
                if ($beg < $time && $time < $end) {
                    array_push($newlsit, $v);
                }
            }
        }
        foreach ($newlsit as $k => $v) {
            if ($v['order_type'] == 1) {
                $newlsit[$k]['order_type'] = '预付款';
            } else {
                $newlsit[$k]['order_type'] = '非预付款';
            }
        }
//        $newlsit = json_encode($newlsit);
        $this->ajaxReturn(['status' => $search, 'data' => $newlsit]);
//        $this->assign('list',$list);
//        $this->assign('listone',$listone);
//        $this->assign('page', $p->show());//预付和非预付公用
//        $this->assign('page2', $p2->show());//未匹配卖出订单数
//        $this->assign('buymoney',$buymoney);
//        $this->assign('salemoney',$salemoney);
//        $this->assign('check_id', $_SESSION['check_p']['check_id']);
//        $this->assign('check_money', $_SESSION['check_p']['check_money']);
//        $this->assign('check_id2', $_SESSION['check_p2']['check_id']);
//        $this->assign('check_money2', $_SESSION['check_p2']['check_money']);
//        $this->display('Wealth/fastmatching');
    }
}