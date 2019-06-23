<?php

/**
 * 交易中心
 * Author: chenmengchen
 * Date: 2017/3/27
 */

namespace Home\Controller;

use Common\Controller\HomeBaseController;
use Common\Controller\SendSmsController;

class OrderCenterController extends HomeBaseController
{
    /**
     *ajax调取,计算用户排单所需的排单币数量
     */
    public function needpaidan()
    {
        $ordermoney = I('request.allmoney');
        if (($ordermoney % 1000) != 0) {
            $this->ajaxReturn(array('status' => '0', 'message' => '股权买入数量须是1000的倍数!'));
            die();
        } else {
            $number = $ordermoney / 1000;
            $this->ajaxReturn(array('status' => '1', 'message' => $number));
        }
    }

    /**
     * 买入 --提供帮助
     */
    public function buyOrder()
    {
        $user_id = session('user_id');
        $user = M('User')->where(array('user_id' => $user_id))->find();
        $wallet = M('Wallet')->where(['user_id' => $user_id])->find();
        $config = $this->config;

        //获取用户VIP等级
//        $push=[
//            'user_parent'=>array('like',array('%'.','.$userid,$userid),'OR'),
//        ];
//        $push2=[
//            'user_parent'=>array('like',array($userid.','.'%','%'.','.$userid,'%'.','.$userid.','.'%',$userid),'OR'),
//        ];
//        $directpush=M('User')->where($push)->where(array('is_active=1'))->count();
//        $myteams=M('User')->where($push2)->where(array('is_active=1'))->count();
//        $user['user_level']=getviplevel($directpush,$myteams);
        if (IS_POST) {
            $data = I('post.');

            $myalipay = M('user_ali_number')->where(array('user_id' => $user_id, 'del' => '0'))->count();
            $mybankcard = M('user_idcard')->where(array('user_id' => $user_id, 'del' => '0'))->count();

//            if (empty($data['allmoney'])) {
//                $this->ajaxReturn(array('status' => '0', 'message' => '帮助金额不能为空!'));
//                die();
//            }
            if (empty($data['thepass'])) {
                $this->ajaxReturn(array('status' => '0', 'message' => '交易密码不能为空!'));
                die();
            }
//            if (($data['allmoney'] % $config['paidan_divide']) != 0) {
//                $this->ajaxReturn(array('status' => '0', 'message' => '帮助金额必须是1000的倍数!'));
//                die();
//            }
            //支付密码是否正确
            if (md5($data['thepass']) != $user['user_secpwd']) {
                $this->ajaxReturn(array('status' => '0', 'message' => '交易密码不正确!'));
                die();
            }
            // if ($data['allmoney']<$user['last_buy_amount']) {
            //     $this->ajaxReturn(array('status' => '0', 'message' => '买入红酒金额不能低于上一次的买入金额!'));
            //     die();
            // }

            //激活购买
            if ($data['buy'] == 1) {
                if ($data['num'] > 1000){
                    $this->ajaxReturn(array('status' => '0', 'message' => '激活购买股权数量最大为1000!'));
                }
                if ($data['num']%200){
                    $this->ajaxReturn(array('status' => '0', 'message' => '激活购买股权数量需为200的倍数!'));
                }
                $add['user_id'] = $user_id;
                $add['user_name'] = $user['user_name'];
                $add['user_truename'] = $user['user_truename'];
                $add['user_phone'] = $user['user_phone'];
                $add['amount'] = $data['num'];
                $add['parent_amount'] = $data['num'];//最原始订单总金额
                $order_number = date('YmdHis', time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//年月日+5位随机数字,不够5位左填充0
                $add['order_number'] = $order_number;
                $add['order_type'] = 0;
                $add['addtime'] = date('Y-m-d H:i:s', time());
                $add['buy'] = $data['buy'];
                $add['pay'] = $data['pay'];
                $add['money'] = $config['stock_price'];
                $res = M('HelpOrder')->data($add)->add();
                if ($res) {
                    $this->ajaxReturn(array('status' => '1', 'message' => '激活购买成功!'));
                } else {
                    $this->ajaxReturn(array('status' => '0', 'message' => '激活购买失败!'));
                }
            } else {
                if ($user['is_active'] == 0) {
                    $this->ajaxReturn(array('status' => '0', 'message' => '请先激活账户!'));
                    die();
                }
                //买入之前判断上一次买入的订单预付款是否已经交易完
                $buycount1 = M('help_order')->where(array('user_id' => $user_id))->where(array('order_type' => 1, 'status' => 0))->select();
                if ($buycount1) {
                    $this->ajaxReturn(array('status' => '0', 'message' => '您有未完成的预付款订单,请完成后再来!'));
                }
                //拍单币是否足够
                $needgemstone = $data['num'] * $config['paidna_expend'] / 100;
                if ($wallet['order_byte'] < $needgemstone) {
                    $this->ajaxReturn(array('status' => '0', 'message' => '排单币剩余不足,请先购买!'));
                    die();
                }
                //直推2人以下，可以排单1000-5000
                //。。2人到3人，可以排单1000-10000
                //。。4。。8，。。。。。。1000-15000
                //。。9人及以上。。。。。。1000-20000
                $allparentid = M('user')->where(array('user_id' =>$user_id ))->getField('user_parent');//返回一个数组  查看上级
                $allparent = array_reverse(explode(',', $allparentid));//以相反的顺序返回数组
                if ($data['num'] < 1000){
                    $this->ajaxReturn(array('status' => '0', 'message' => '买入股权数量需大于1000!'));
                }
                if (sizeof($allparent)<2){
                    if ($data['num']>5000){
                        $this->ajaxReturn(array('status' => '0', 'message' => '买入股权数量需小于5000!'));
                    }
                }
                if (sizeof($allparent) < 4 && sizeof($allparent) >= 2){
                    if ($data['num']>10000){
                        $this->ajaxReturn(array('status' => '0', 'message' => '买入股权数量需小于10000!'));
                    }
                }
                if (sizeof($allparent) < 9 && sizeof($allparent)>= 4){
                    if ($data['num']>15000){
                        $this->ajaxReturn(array('status' => '0', 'message' => '买入股权数量需小于15000!'));
                    }
                }
                if (sizeof($allparent) >= 9){
                    if ($data['num']>20000){
                        $this->ajaxReturn(array('status' => '0', 'message' => '买入股权数量需小于20000!'));
                    }
                }
                $m = M();
                $m->startTrans();
                //排单购买
                try {
                    //减少拍单币
                    // $oldorderbyte=M('Wallet')->where(array('user_id'=>$user_id))->getField('order_byte');
                    $oldorderbyte = $wallet['order_byte'] - $needgemstone;
                    $result = M('Wallet')->where(array('user_id' => $user_id))->save(['order_byte' => $oldorderbyte]);
                    $wallet_log1 = array(
                        'user_id' => $user_id,
                        'user_name' => $user['user_name'],
                        'user_phone' => $user['user_phone'],
                        'amount' => '-' . $needgemstone,
                        'old_amount' => $wallet['order_byte'],
                        'remain_amount' => $oldorderbyte,
                        'change_date' => time(),
                        'log_note' => '消耗股权增值券',
                        'wallet_type' => 4,
                    );
                    $result1 = M('WalletLog')->data($wallet_log1)->add();
                    //生成订单记录
                    $order['user_id'] = $user_id;
                    $order['user_name'] = $user['user_name'];
                    $order['user_truename'] = $user['user_truename'];
                    $order['user_phone'] = $user['user_phone'];
                    $order['amount'] = $data['num'];
                    $order['parent_amount'] = $data['num'];//最原始订单总金额
                    $order_number = date('YmdHis', time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//年月日+5位随机数字,不够5位左填充0
                    $order['order_number'] = $order_number;
                    $order['order_type'] = 0;
                    $order['addtime'] = date('Y-m-d H:i:s', time());
                    $order['buy'] = $data['buy'];
                    $order['pay'] = $data['pay'];
                    $order['money'] = $config['stock_price'];
                    $result2 = M('HelpOrder')->data($order)->add();
                    //$id = M('help_order')->max('id');//获取数据表中最大的ID值
                    $id = M('HelpOrder')->where(array('order_number' => $order_number))->getField('id');
                    $add_order['parent_id'] = $id;
                    $result3 = M('help_order')->where(array('id' => $id))->save($add_order);
                    //拆分成预付款和非预付款订单
                    $yufuamount = $data['num'] * $config['paidan_yufu'] / 100;
                    $feiyufuamount = $data['num'] * (100 - $config['paidan_yufu']) / 100;
                    $message = M('HelpOrder')->where('id=' . $id)->find();
                    $record1 = $message;
                    $record1['amount'] = $yufuamount;
                    $record1['order_type'] = '1';
                    $record1['order_number'] = date('YmdHis', time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $record1['buy'] = $data['buy'];
                    $record1['pay'] = $data['pay'];
                    $record1['money'] = $config['stock_price'];
                    unset($record1['id']);
                    $result5 = M('HelpOrder')->add($record1);
                    $record2 = $message;
                    unset($record2['id']);
                    $record2['amount'] = $feiyufuamount;
                    $record2['order_type'] = '2';
                    $record2['order_number'] = date('YmdHis', time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $record2['buy'] = $data['buy'];
                    $record2['pay'] = $data['pay'];
                    $record2['money'] = $config['stock_price'];
                    $result6 = M('HelpOrder')->add($record2);
                    //设置最近一次买入金额
                    $result4 = M('User')->where(array('user_id' => $user_id))->setField('last_buy_amount', $data['allmoney']);
                    $m->commit();
                } catch (\PDOException $e) {
                    $m->rollback();
                }
                //首页
                if ($result && $result1 && $result2 && $result3 && $result5 && $result6) {
                    $this->ajaxReturn(array('status' => '1', 'message' => '预约购买成功!'));
                } else {
                    $this->ajaxReturn(array('status' => '0', 'message' => '预约购买失败!'));
                }
            }
        } else {
            // $this->assign('themax',$themax);
            $this->assign('user', $user);
            $this->assign('config', $config);//检测平台申请功能是否关闭,关闭时无法进入进行排单;用弹出层提示关闭消息
            $this->display('Help/help');
        }
    }

    /*
    *买入-帮助订单信息
    */
    public function buy_matching_list()
    {
        $userid = session('user_id');
        $config = M('config')->find(1);
        $finish = M('match_order')->where(array('status'=>2))->select();
//        $list1 = M('HelpOrder')->where(array('buy' => 1, 'user_id' => $userid,'matching'=>array('NEQ',2)))->order('id','DESC')->select();//激活购买订单
        $list1 = M('HelpOrder')->where(array('buy' => 1, 'user_id' => $userid))->order('id','DESC')->select();//激活购买订单
        foreach ($list1 as $k=>$v) {
            foreach ($finish as $kk=>$vv){
                if($vv['buy_order_id'] == $v['id']){
                    $endtime = strtotime($vv['receive_time']) + 60 * 60 * 24 * 2;//两天
//                    $endtime = strtotime($vv['receive_time']) + 60;
                    if($v['matching'] == 2){
                        if($endtime < time()){
                            unset($list1[$k]);
                        }
                    }
                }
            }
        }
//        $list2 = M('HelpOrder')->where(array('user_id' => $userid,'matching'=>array('NEQ',2)))->where(array('order_type' => array('NEQ', 0)))->order('id','DESC')->select();//预约购买订单
        $list2 = M('HelpOrder')->where(array('user_id' => $userid))->where(array('order_type' => array('NEQ', 0)))->order('id','DESC')->select();//预约购买订单
        foreach ($list2 as $k=>$v) {
            foreach ($finish as $kk=>$vv){
                if($vv['buy_order_id'] == $v['id']){
                    $endtime = strtotime($vv['receive_time']) + 60 * 60 * 24 * 2;//两天
//                    $endtime = strtotime($vv['receive_time']) + 60;
                    if($v['matching'] == 2){
                        if($endtime < time()){
                            unset($list2[$k]);
                        }
                    }
                }
            }
        }
//        $list3 = M('askhelp_order')->where(array('user_id' => $userid,'order_type'=>1,'matching'=>array('NEQ',2)))->select();//卖出订单
        $list3 = M('askhelp_order')->where(array('user_id' => $userid,'order_type'=>1))->order('addtime DESC')->select();//卖出订单
        $freeze = M('interest')->where(array('user_id' => $userid))->select();//冻结
        foreach ($freeze as &$v){
            foreach ($list2 as $k=>$vv){
                if($v['buy_order'] == $vv['id']){
                    $nowtime = time();
                    $endtime = $v['addtime'] + $config['frozen_time'] * 86400;
                    if($endtime > $nowtime){
                        $list2[$k]['matching'] = '3';
                    }
                }
            }
        }
        $this->assign('list1', $list1);
        $this->assign('list2', $list2);
        $this->assign('list3', $list3);
        $this->assign('config', $config);
        $this->display('Order/order');
    }

    /*
    *买入订单详情 上部显示总订单信息,下部显示子订单信息
    */
    public function buyinfoing()
    {
        $thehelpid = I('request.id');//
        $config = M('config')->find(1);
        $helporder = M('HelpOrder')->where(array('id' => $thehelpid))->find();
        if ($helporder['order_type'] == '0') {//总单子

            $saleid = M('MatchOrder')->where(array('buy_order_id' => $helporder['id']))->getField('sale_order_id');
            $saleuserid = M('AskhelpOrder')->where(array('id' => $saleid))->getField('user_id');
            $helporder['salename'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_name');
            $helporder['saletruename'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_truename');
            $helporder['saleuserphone'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_phone');
            //获取卖出方的支付宝信息和银行卡信息
            $helporder['alipay'] = M('user_ali_number')->where(array('user_id' => $saleuserid, 'del' => '0'))->getField('ali_num');
            $helporder['banknumber'] = M('user_idcard')->where(array('user_id' => $saleuserid, 'del' => '0'))->select();
//            dump($helporder);die;
            if ($helporder['matching'] == '2') {
                $helporder['style'] = M('interest')->where(array('buy_order' => $helporder['id']))->getField('coldday');
                $helporder['shengstyle'] = $config['principal_cold'] - $helporder['style'];
            }
            $map = [
                'parent_id' => $thehelpid,
                'order_type' => array('neq', 0),
                'matching' => array('in', [1, 2]),
            ];
            $helporder['matched'] = M('HelpOrder')->where($map)->sum('amount');
            if (empty($helporder['matched'])) {
                $helporder['matched'] = 0;
            }
            //获取总订单下已匹配和已完成的子单子信息
            $listchild = M('HelpOrder')->where($map)->select();
            foreach ($listchild as &$val) {//获取对方信息
                $saleid = M('MatchOrder')->where(array('buy_order_id' => $val['id']))->getField('sale_order_id');
                $saleuserid = M('AskhelpOrder')->where(array('id' => $saleid))->getField('user_id');
                $val['salename'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_name');
                $val['saletruename'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_truename');
                $val['saleuserphone'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_phone');
                //获取卖出方的支付宝信息和银行卡信息
                $val['alipay'] = M('user_ali_number')->where(array('user_id' => $saleuserid, 'del' => '0'))->getField('ali_num');
                $val['banknumber'] = M('user_idcard')->where(array('user_id' => $saleuserid, 'del' => '0'))->select();

            }
        } else {//子单子
            //判断子单子是否已经匹配了
            if ($helporder['matching'] != '0') {
                $saleid = M('MatchOrder')->where(array('buy_order_id' => $helporder['id']))->getField('sale_order_id');
                $saleuserid = M('AskhelpOrder')->where(array('id' => $saleid))->getField('user_id');
                $helporder['salename'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_name');
                $helporder['saletruename'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_truename');
                $helporder['saleuserphone'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_phone');
                $helporder['saleuserxin'] = M('user')->where(array('user_id' => $saleuserid))->getField('user_reputation');
                $helporder['alipay'] = M('user_ali_number')->where(array('user_id' => $saleuserid, 'del' => '0'))->getField('ali_num');
                $helporder['banknumber'] = M('user_idcard')->where(array('user_id' => $saleuserid, 'del' => '0'))->select();

                if ($helporder['matching'] == 1) {
                    if ($helporder['status'] == 1) {
                        $helporder['paytype'] = "已支付";
                    } else {
                        $helporder['paytype'] = "待支付";
                    }
                } else {
                    $helporder['paytype'] = "已完成";
                }
            }
        }
        $pay = M('askhelp_order')->where(array('id' => $saleid))->find();
        if ($pay['pay'] == 1) {
            $pay['payfor'] = M('user_idcard')->where(array('user_id' => $pay['user_id']))->getField('id_card');
        } else {
            $pay['payfor'] = M('user_ali_number')->where(array('user_id' => $pay['user_id']))->getField('ali_num');
        }
//        dump($pay);die;
        $money = $helporder['amount'] * $config['stock_price'];
        $this->assign('money', $money);
        $this->assign('pay', $pay);
        $this->assign('helporder', $helporder);
        $this->assign('listchild', $listchild);
//        dump($listchild);die;
        $this->assign('config', $config);
        $this->display('Order/order-help-details');
    }

    /*
    去打款
    */
    public function make_money()
    {
        $userid = session('user_id');
        $config = M('config')->find(1);
        $theid = I('request.id');
        $list = M('MatchOrder')->where(array('buy_order_id' => $theid))->find();
        $list['buyordernumber'] = M('HelpOrder')->where(array('id' => $theid))->getField('order_number');
        $list['buyordertime'] = M('HelpOrder')->where(array('id' => $theid))->getField('addtime');
        //判断订单是预付款还是非预付款订单
        $theordertype = M('HelpOrder')->where(array('id' => $theid))->getField('order_type');
        //最晚打款时间
        $list['endtime'] = $config['pay_time_max'] * 3600 + strtotime($list['create_time']);
        $list['endtime'] = $list['endtime'] * 1000;
        if ($list['status'] == 1) {//已打过款
            $list['pay_img'] = M('PayedOrder')->where(array('match_id' => $list['id']))->getField('img_payed');
            $list['endtime'] = strtotime($list['payed_time']);
            $list['endtime'] = $list['endtime'] * 1000;
        }
        $saleinfo = M('user')->where(array('user_id' => $list['sale_id']))->find();
        $push = [
            'user_parent' => array('like', array('%' . ',' . $list['sale_id'], $list['sale_id']), 'OR'),
        ];
        $push2 = [
            'user_parent' => array('like', array($list['sale_id'] . ',' . '%', '%' . ',' . $list['sale_id'], '%' . ',' . $list['sale_id'] . ',' . '%', $list['sale_id']), 'OR'),
        ];
        $directpush = M('user')->where($push)->where(array('is_active=1'))->count();
        $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();
        $saleinfo['saleuserlevel'] = getviplevel($directpush, $myteams);
        //获取卖出方收款信息
        $list['alipay'] = M('user_ali_number')->where(array('user_id' => $list['sale_id'], 'del' => '0'))->getField('ali_num');
        $list['bankcard'] = M('user_idcard')->where(array('user_id' => $list['sale_id'], 'del' => '0'))->select();
        //领导人昵称和联系方式
        $saleuserleader = M('user')->where(array('user_id' => $list['sale_id']))->getField('user_parent');
        if (!empty($saleuserleader)) {
            $saleuserleaders = array_reverse(explode(',', $saleuserleader));
            $saleinfo['saleuser_leadername'] = M('user')->where(array('user_id' => $saleuserleader[0]))->getField('user_name');
            $saleinfo['saleuser_leaderphone'] = M('user')->where(array('user_id' => $saleuserleader[0]))->getField('user_phone');
        } else {
            $saleinfo['saleuser_leadername'] = "";
            $saleinfo['saleuser_leaderphone'] = "";
        }
        $pay = M('askhelp_order')->where(array('id' => $list['sale_order_id']))->find();
        $pay['payforbank'] = M('user_idcard')->where(array('user_id' => $pay['user_id']))->getField('id_card');
        $pay['payforali'] = M('user_ali_number')->where(array('user_id' => $pay['user_id']))->getField('ali_num');
//        dump($list);die;
        $money = $list['amount'] * $config['stock_price'];
        $this->assign('money', $money);
        $this->assign('pay', $pay);
        $this->assign('saleinfo', $saleinfo);
        $this->assign('list', $list);
        $this->assign('config', $config);
        $this->display('Order/order-certificate');
    }

    /*
    隐藏手机号码
    */
    public function hide_phone($hidephone)
    {
        $restr = substr_replace($hidephone, '****', 3, 5);
        return $restr;
    }

    /*
    * 买入方投诉(自己未打款时不能投诉)
    */
    public function buycomplaint()
    {
        $userid = session('user_id');
        $config = M('config')->find(1);
        $matchid = I('request.matchid');
        $matchinfo = M('MatchOrder')->where(array('id' => $matchid))->find();
        $payedtime = strtotime($matchinfo['payed_time']);
        if ($matchinfo['status'] == 0) {
            $this->ajaxReturn(['status' => '0', 'message' => '您还未打款,暂无法投诉']);
        } elseif ($matchinfo['status'] == 2) {
            $this->ajaxReturn(['status' => '0', 'message' => '对方已确认收款,投诉无效']);
        } elseif ($payedtime + $config['gain_time_limit'] * 3600 >= time()) {
            $this->ajaxReturn(['status' => '0', 'message' => '收款时间未超时,投诉无效']);
        } else {
            $result = M('PayedOrder')->where(array('match_id' => $matchid))->setField('status', '4');
            if ($result) {
                $this->ajaxReturn(['status' => '1', 'message' => '投诉成功']);
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '投诉失败,您可能已经投诉过了']);
            }
        }
    }

    //上传打款凭证
    public function upfile()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 18145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        //$upload->saveName = '';
        //$upload->rootPath  =     '../Uploads/'; // 设置附件上传根目录   将文件保存在Uploads文件下的images中
        $upload->rootPath = '../public/Uploads/'; // 设置附件上传根目录    将文件保存在Public文件下的images中
        $upload->savePath = '/Pic/'; // 设置附件上传（子）目录
        // 上传文件
        $info = $upload->upload();
        if (!$info) {
            $this->error($upload->getError());
            exit;
        } else {// 上传成功
            // dump($info);
            foreach ($info as $file) {
                //$data['datas']= '../Uploads/images/'.$file['savePath'].$file['savename'];  文件路径,存储在数据库中
                $data['datas'] = '/Uploads/Pic/' . date('Y-m-d', time()) . '/' . $file['savename'];   //文件路径,存储在数据库中
            }
            //dump($data);die;
            echo $data['datas'];
        }
    }

    /*
    我已打款,预付款和非预付款都在12小时内打款的，奖励额外1%算动态资产
    */
    public function besurepayed()
    {
        $userid = session('user_id');
        $config = M('config')->find(1);
        $userinfo = M('user')->where(array('user_id' => $userid))->find();
        $matchid = I('request.matchid');
        $photo = I('request.photo');
        if (M('MatchOrder')->where(array('id' => $matchid))->getField('status') == 1) {//如果已经打过款
            $this->ajaxReturn(['status' => '0', 'message' => '您已打过款,不可重复确认打款!']);
        }
        if (empty($photo)) {
            $this->ajaxReturn(['status' => '0', 'message' => '请上传打款凭证']);
        } else {
            $m = M();
            $m->startTrans();
            try {
                $create = M('MatchOrder')->where(array('id' => $matchid))->find();
                //判断打款的订单是不是用户代替下级玩家支付的订单
                $theuserparentid = M('HelpOrder')->where(array('id' => $create['buy_order_id']))->getField('user_parent_id');
                if ($theuserparentid != $userid) {//不是
                    $buyparentorderid = M('HelpOrder')->where(array('id' => $create['buy_order_id']))->getField('parent_id');//总订单的id值
                    $map = [
                        'id' => array('neq', $create['buy_order_id']),
                    ];
                    $buybrother = M('HelpOrder')->where("parent_id='$buyparentorderid' and order_type!=0")->where($map)->getField('id', true);
                    for ($i = 0; $i < count($buybrother); $i++) {
                        //判断订单中有没有超时不打款的订单
                        $theuserparent = M('HelpOrder')->where(array('id' => $buybrother[$i]))->getField('user_parent_id');
                        if ($theuserparent == 0) {//不是超时不打款
                            $thematchorder = M('MatchOrder')->where(array('buy_order_id' => $buybrother[$i]))->find();
                            if ($thematchorder) {
                                //判断是否已支付
                                if ($thematchorder['status'] == 1 || $thematchorder['status'] == 2) {//支付了
                                    //判断是否是12小时内打款
                                    if (strtotime($thematchorder['payed_time']) - strtotime($thematchorder['create_time']) <= 12 * 3600) {
                                        $number = 1;
                                    } else {
                                        $number = 0;
                                        break;
                                    }
                                } else {
                                    $number = 0;
                                    break;
                                }
                            } else {
                                $number = 0;
                                break;
                            }
                        } else {//是超时不打款
                            $number = 0;
                            break;
                        }
                    }
                    //修改卖出订单状态
                    $result3 = M('AskhelpOrder')->where(array('id' => $create['sale_order_id']))->setField('status', '1');
                    //修改买入订单和匹配订单状态,同时增加支付记录
                    $result = M('MatchOrder')->where(array('id' => $matchid))->save(['status' => '1', 'payed_time' => date('Y-m-d H:i:s', time())]);
                    $result1 = M('HelpOrder')->where(array('id' => $create['buy_order_id']))->setField('status', '1');
                    $data1['user_id'] = $userid;
                    $data1['user_name'] = $userinfo['user_name'];
                    $gainuserinfo = M('user')->where(array('user_id' => $create['sale_id']))->find();
                    $data1['gain_user_id'] = $gainuserinfo['user_id'];
                    $data1['gain_user_name'] = $gainuserinfo['user_name'];
                    $data1['match_id'] = $matchid;
                    $data1['amount'] = $create['amount'];
                    $data1['img_payed'] = $photo;
                    $data1['status'] = '1';
                    $data1['create_time'] = time();
                    $result2 = M('PayedOrder')->add($data1);
                    $createtow = M('MatchOrder')->where(array('id' => $matchid))->find();
                    if ($number == 1 && strtotime($createtow['create_time']) - strtotime($createtow['create_time']) <= 12 * 3600) {//都完成,并且都是在12小时之内打款的
                        //给予奖励,奖励到动态钱包
                        $baseordermoney = M('HelpOrder')->where(array('id' => $create['buy_order_id']))->getField('parent_amount');
                        $jiangli = $baseordermoney * $config['threehours_inner'] / 100;
                        $oldstatic_amount = M('wallet')->where(array('user_id' => $userid))->getField('change_amount');
                        //奖励到静态钱包  //确认收款后才有奖励
//                        M('wallet')->where(array('user_id' => $userid))->setInc('change_amount', $jiangli);
                        //添加钱包变动记录
                        $data['user_id'] = $userid;
                        $data['amount'] = $jiangli;
                        $data['user_phone'] = $userinfo['user_phone'];
                        $data['user_name'] = $userinfo['user_name'];
                        $data['old_amount'] = $oldstatic_amount;
                        $data['remain_amount'] = $oldstatic_amount + $jiangli;
                        $data['change_date'] = time();
                        $data['log_note'] = "12小时之内打款奖励" . $config['threehours_inner'] . '%';
                        $data['wallet_type'] = '2';
//                        M('wallet_log')->add($data);
                    }
                } else {//是的
                    //修改卖出订单状态
                    $result3 = M('AskhelpOrder')->where(array('id' => $create['sale_order_id']))->setField('status', '1');
                    //修改买入订单和匹配订单状态,同时增加支付记录
                    $result = M('MatchOrder')->where(array('id' => $matchid))->save(['status' => '1', 'payed_time' => date('Y-m-d H:i:s', time())]);
                    $result1 = M('HelpOrder')->where(array('id' => $create['buy_order_id']))->setField('status', '1');
                    $data1['user_id'] = $userid;
                    $data1['user_name'] = $userinfo['user_name'];
                    $gainuserinfo = M('user')->where(array('user_id' => $create['sale_id']))->find();
                    $data1['gain_user_id'] = $gainuserinfo['user_id'];
                    $data1['gain_user_name'] = $gainuserinfo['user_name'];
                    $data1['match_id'] = $matchid;
                    $data1['amount'] = $create['amount'];
                    $data1['img_payed'] = $photo;
                    $data1['status'] = '1';
                    $data1['create_time'] = time();
                    $result2 = M('PayedOrder')->add($data1);
                }
                $m->commit();
            } catch (PDOException $exc) {
                $m->rollback();
            }
            if ($result && $result1 && $result2 && $result3) {
                //给收款方发短信,通知收款
                $phone2 = $gainuserinfo['user_phone'];
                $sale_order_number = M('AskhelpOrder')->where(array('id' => $create['sale_order_id']))->getField('order_number');
                $content2 = "亲爱的会员,您的订单号为" . $sale_order_number . "的排单，对方已打款，请确认收款";
                (new SendSmsController())->sendSms($phone2, $content2);
                $this->ajaxReturn(['status' => '1', 'message' => '确认打款成功']);
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '确认打款失败']);
            }
        }
    }

    /**
     * 卖出
     */
    public function saleOrder()
    {
        $user_id = session('user_id');
        $wallettype = I('request.wallettype');
        if (empty($wallettype)) {
            $wallettype = 1;
        }
        $user = M('User')->where(array('user_id' => $user_id))->find();
        $wallet = M('Wallet')->where(['user_id' => $user_id])->find();
        $config = $this->config;
        if (IS_POST) {
            $data = I('post.');
            if ($user['info_perfected'] == 0) {
                $this->ajaxReturn(array('status' => '0', 'message' => '请先完善个人资料!'));
                die();
            }
//			$myalipay=M('user_ali_number')->where(array('user_id'=>$user_id,'del'=>'0'))->count();
//            $mybankcard=M('user_idcard')->where(array('user_id'=>$user_id,'del'=>'0'))->count();
//            if($myalipay==0&&$mybankcard==0){
//                $this->ajaxReturn(array('status' => '0', 'message' => '请先完善收款方式!'));
//                die();
//            }
            if ($user['is_active'] == 0) {
                $this->ajaxReturn(array('status' => '0', 'message' => '请先激活账户!'));
                die();
            }
            if (empty($data['allmoney'])) {
                $this->ajaxReturn(array('status' => '0', 'message' => '卖出金额不能为空!'));
                die();
            }
            if (empty($data['thepass'])) {
                $this->ajaxReturn(array('status' => '0', 'message' => '交易密码不能为空!'));
                die();
            }
            //支付密码是否正确
            if (md5($data['thepass']) != $user['user_secpwd']) {
                $this->ajaxReturn(array('status' => '0', 'message' => '交易密码不正确!'));
                die();
            }
            //剩余股权数量
            if($wallet['static_amount'] < $data['allmoney']){
                $this->ajaxReturn(array('status' => '0', 'message' => '卖出股权不能超过当前股权!'));
            }
//            卖出股权应是200的倍数
            if($data['allmoney'] % 200){
                $this->ajaxReturn(array('status' => '0', 'message' => '卖出股权需是200的倍数!'));
            }
            $m = M();
            $m->startTrans();
            try {
                //减少股权数量
                $oldorderbyte = M('Wallet')->where(array('user_id' => $user_id))->getField('static_amount');
                $oldorderbyte = $oldorderbyte - $data['allmoney'];
                $result = M('Wallet')->where(array('user_id' => $user_id))->setDec('static_amount', $data['allmoney']);
                $wallet_log1 = array(
                    'user_id' => $user_id,
                    'user_name' => $user['user_name'],
                    'user_phone' => $user['user_phone'],
                    'amount' => '-' . $data['allmoney'],
                    'old_amount' => $wallet['static_amount'],
                    'remain_amount' => $wallet['static_amount'] - $data['allmoney'],
                    'change_date' => time(),
                    'log_note' => '卖出股权',
                    'wallet_type' => 1,
                );
                $result1 = M('WalletLog')->data($wallet_log1)->add();

                //生成订单记录
                $order['user_id'] = $user_id;
                $order['user_name'] = $user['user_name'];
                $order['user_truename'] = $user['user_truename'];
                $order['user_phone'] = $user['user_phone'];
                $order['amount'] = $data['allmoney'];
//                $order['wallet_type'] = $data['sellSelect'];
                $order['parent_amount'] = $data['allmoney'];//最原始订单总金额
                $order_number = date('YmdHis', time()) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//年月日+5位随机数字,不够5位左填充0
                $order['order_number'] = $order_number;
                $order['order_type'] = '1';//总的卖出订单
                $order['pay'] = $data['pay'];
                // if ($data['selectid']==1) {
                //     $order['get_way'] = 1;
                //     $order['account_number']=M('user_ali_number')->where(array('user_id'=>$user_id))->getField('ali_num');
                // }else{
                //     $order['get_way'] = 2;
                //     $order['account_number']=M('user_idcard')->where(array('user_id'=>$user_id,'card_kaihu'=>$data['selectid']))->getField('id_card');
                // }
                $order['addtime'] = date('Y-m-d H:i:s', time());
                $result2 = M('askhelp_order')->data($order)->add();
                //$id = M('askhelp_order')->max('id');//获取数据表中最大的ID值
                $id = M('askhelp_order')->where(array('order_number' => $order_number))->getField('id');
                $add_order['parent_id'] = $id;
                $result3 = M('askhelp_order')->where(array('id' => $id))->save($add_order);
                $m->commit();
            } catch (\PDOException $e) {
                $m->rollback();
            }
            if ($result && $result1 && $result2 && $result3) {
                $this->ajaxReturn(array('status' => '1', 'message' => '卖出股权下单成功!'));
            } else {
                $this->ajaxReturn(array('status' => '0', 'message' => '卖出股权下单失败!'));
            }
        } else {
            //查询用户银行卡信息
            $listbank = M('user_idcard')->where(array('user_id' => $user_id))->getField('card_kaihu', true);
            $this->assign('wallet', $wallet);
            $this->assign('listbank', $listbank);
            $this->assign('config', $config);
            $this->assign('wallettype', $wallettype);//提现钱包类别
            $this->display('Sell/sell');
        }
    }

    /**
     * 卖出匹配记录
     */
    public function sell_matching_list()
    {
        $userid = session('user_id');
        $config = M('config')->find(1);
        $askhelporder = M('AskhelpOrder');
        //所有订单
        $alllist = $askhelporder->where("user_id='$userid' and order_type!=1 and matching!=0 or user_id='$userid' and order_type=1")->order('addtime desc,order_type asc')->select();
        foreach ($alllist as &$val) {
            if ($val['order_type'] == 1) {//总单子
                $map = [
                    'parent_id' => $val['id'],
                    'order_type' => array('neq', 1),
                    'matching' => array('in', [1, 2]),
                ];
                $val['matched'] = M('AskhelpOrder')->where($map)->sum('amount');
                if (empty($val['matched'])) {
                    $val['matched'] = 0;
                }
            } else {//子单子
                $buyorderid = M('MatchOrder')->where(array('sale_order_id' => $val['id']))->getField('buy_order_id');
                $val['complaint'] = M('MatchOrder')->where(array('sale_order_id' => $val['id']))->getField('status');
                //判断收款状态,已打款但未投诉时显示收款倒计时
                if ($val['complaint'] == 1) {//已打款但未投诉
                    $thepayedtime = M('MatchOrder')->where(array('sale_order_id' => $val['id']))->getField('payed_time');
                    $val['endtime'] = strtotime($thepayedtime) + $config['gain_time_limit'] * 3600;
                    $val['endtime'] = $val['endtime'] * 1000;
                }
                if ($val['complaint'] == 0) {//还未打款,显示收款倒计时
                    //判断买入的是预付款还是非预付款
                    $buyordertype = M('HelpOrder')->where(array('id' => $buyorderid))->getField('order_type');
                    if ($buyordertype == 1) {//预付款
                        $thecreattime = M('MatchOrder')->where(array('sale_order_id' => $val['id']))->getField('create_time');
                        $val['endtime'] = strtotime($thecreattime) + $config['pay_time_limit1'] * 3600;
                        $val['endtime'] = $val['endtime'] * 1000;
                    } else {//非预付款
                        $thecreattime = M('MatchOrder')->where(array('sale_order_id' => $val['id']))->getField('create_time');
                        $val['endtime'] = strtotime($thecreattime) + $config['pay_time_limit2'] * 3600;
                        $val['endtime'] = $val['endtime'] * 1000;
                    }
                }
                $val['theordertype'] = M('HelpOrder')->where(array('id' => $buyorderid))->getField('order_type');
                $val['theisgood'] = M('HelpOrder')->where(array('id' => $buyorderid))->getField('is_good');
                $thebuyparentid = M('HelpOrder')->where(array('id' => $buyorderid))->getField('user_parent_id');
                if ($thebuyparentid == '0') {//不是上级
                    $val['buyuser_name'] = M('HelpOrder')->where(array('id' => $buyorderid))->getField('user_name');
                } else {//是上级
                    $val['buyuser_name'] = M('user')->where(array('user_id' => $thebuyparentid))->getField('user_name');
                }
            }
        }
        //交易中订单
        $ontranslist = $askhelporder->where(array('user_id' => $userid, 'matching' => '1'))->order('addtime desc')->select();
        foreach ($ontranslist as &$vv) {
            $vv['complaint'] = M('MatchOrder')->where(array('sale_order_id' => $vv['id']))->getField('status');
        }
        //未匹配订单
        $unmatched = $askhelporder->where(array('user_id' => $userid, 'matching' => '0'))->order('addtime desc')->select();
        //已完成订单
        $isfinished = $askhelporder->where(array('user_id' => $userid, 'matching' => '2'))->order('addtime desc')->select();
        foreach ($isfinished as &$value) {
            $buyorderidtow = M('MatchOrder')->where(array('sale_order_id' => $value['id']))->getField('buy_order_id');
            $value['theordertype'] = M('HelpOrder')->where(array('id' => $buyorderidtow))->getField('order_type');
            $value['theisgood'] = M('HelpOrder')->where(array('id' => $buyorderidtow))->getField('is_good');
        }
        $this->assign('alllist', $alllist);
        $this->assign('ontranslist', $ontranslist);
        $this->assign('unmatched', $unmatched);
        $this->assign('isfinished', $isfinished);
        $this->assign('config', $config);
        $this->display('Order/order-get');
    }

    /*
    *卖出订单匹配信息详情
    */
    public function saleinfoing()
    {
        $userid = session('user_id');
        $thesaleid = I('request.id');
        $config = M('config')->find(1);
        $askhelporder = M('AskhelpOrder')->where(array('id' => $thesaleid))->find();
        // if ($askhelporder['get_way']==2) {
        //     $askhelporder['thebank']=M('user_idcard')->where(array('user_id'=>$userid,'id_card'=>$askhelporder['account_number']))->getField('card_kaihu');
        // }
        if ($askhelporder['order_type'] == '1') {//总的卖出单子
            $map = [
                'parent_id' => $thesaleid,
//                'order_type'=>array('neq',1),
                'matching' => array('in', [1, 2]),
            ];
            $askhelporder['matched'] = M('AskhelpOrder')->where($map)->sum('amount');
            if (empty($askhelporder['matched'])) {
                $askhelporder['matched'] = 0;
            }
            $map = [
                'parent_id' => $thesaleid,
//                'id'=>array('neq',$thesaleid),
                'matching' => array('in', [1, 2]),
            ];
            //获取总订单下已匹配和已完成的子单子信息
            $listchild = M('AskhelpOrder')->where($map)->select();
//            $listchild = M('AskhelpOrder')->where($map)->where(array('id'=>array('neq',$thesaleid))->select();
            foreach ($listchild as &$val) {//获取对方信息
                //获取买入者信息
                $thebuyid = M('MatchOrder')->where(array('sale_order_id' => $val['id']))->getField('buy_order_id');
                $complaint = M('MatchOrder')->where(array('sale_order_id' => $val['id']))->getField('status');
                $thebuyuser = M('HelpOrder')->where(array('id' => $thebuyid))->getField('user_parent_id');
                if ($thebuyuser == '0') {//证明还不是上级负责交易
                    $thebuyorderinfo = M('HelpOrder')->where(array('id' => $thebuyid))->find();
                    $val['buyuser_name'] = $thebuyorderinfo['user_name'];
                    $val['buyuser_truename'] = $thebuyorderinfo['user_truename'];
                    $val['buyuser_phone'] = $thebuyorderinfo['user_phone'];
                    $val['reputation'] = M('user')->where(array('user_id' => $thebuyorderinfo['user_id']))->getField('user_reputation');
                    //获取对方VIP等级
                    $push = [
                        'user_parent' => array('like', array('%' . ',' . $thebuyorderinfo['user_id'], $thebuyorderinfo['user_id']), 'OR'),    //直推人数
                    ];
                    $push2 = [
                        'user_parent' => array('like', array($thebuyorderinfo['user_id'] . ',' . '%', '%' . ',' . $thebuyorderinfo['user_id'], '%' . ',' . $thebuyorderinfo['user_id'] . ',' . '%', $thebuyorderinfo['user_id']), 'OR'),   //团队人数
                    ];
                    $directpush = M('user')->where($push)->where(array('is_active=1'))->count();
                    $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();
                    $val['buyuserlevel'] = getviplevel($directpush, $myteams);
                    //获取支付宝账户和银行卡信息
                    $val['buyuser_alipay'] = M('user_ali_number')->where(array('user_id' => $thebuyorderinfo['user_id'], 'del' => '0'))->getField('ali_num');
                    $val['buyuser_banknum'] = M('user_idcard')->where(array('user_id' => $thebuyorderinfo['user_id'], 'del' => '0'))->select();
                    //领导人昵称和联系方式
                    $buyuserleader = M('user')->where(array('user_id' => $thebuyorderinfo['user_id']))->getField('user_parent');
                    if (!empty($buyuserleader)) {
                        $buyuserleaders = array_reverse(explode(',', $buyuserleader));
                        $val['buyuser_leadername'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_name');
                        $val['buyuser_leaderphone'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_phone');
                    } else {
                        $val['buyuser_leadername'] = "";
                        $val['buyuser_leaderphone'] = "";
                    }
                }

                if ($val['matching'] == 1) {
                    if ($val['status'] == 1) {
                        if ($complaint == 3) {
                            $val['saletype'] = "交易中(已投诉)";
                        } else {
                            $val['saletype'] = "交易中(待确认)";
                        }
                    } else {
                        $val['saletype'] = "交易中(待付款)";
                    }
                } else {
                    $val['saletype'] = "已完成";
                }
            }
        } else {//子单子
            //获取匹配信息
            if ($askhelporder['matching'] != '0') {//已匹配时
                //获取买入者信息
                $thebuyid = M('MatchOrder')->where(array('sale_order_id' => $askhelporder['id']))->getField('buy_order_id');
                $complaint = M('MatchOrder')->where(array('sale_order_id' => $askhelporder['id']))->getField('status');
                $thebuyuser = M('HelpOrder')->where(array('id' => $thebuyid))->getField('user_parent_id');
                if ($thebuyuser == '0') {//证明还不是上级负责交易
                    $thebuyorderinfo = M('HelpOrder')->where(array('id' => $thebuyid))->find();
                    $askhelporder['buyuser_name'] = $thebuyorderinfo['user_name'];
                    $askhelporder['buyuser_truename'] = $thebuyorderinfo['user_truename'];
                    $askhelporder['buyuser_phone'] = $thebuyorderinfo['user_phone'];
                    $askhelporder['reputation'] = M('user')->where(array('user_id' => $thebuyorderinfo['user_id']))->getField('user_reputation');
                    //获取对方VIP等级
                    $push = [
                        'user_parent' => array('like', array('%' . ',' . $thebuyorderinfo['user_id'], $thebuyorderinfo['user_id']), 'OR'),    //直推人数
                    ];
                    $push2 = [
                        'user_parent' => array('like', array($thebuyorderinfo['user_id'] . ',' . '%', '%' . ',' . $thebuyorderinfo['user_id'], '%' . ',' . $thebuyorderinfo['user_id'] . ',' . '%', $thebuyorderinfo['user_id']), 'OR'),   //团队人数
                    ];
                    $directpush = M('user')->where($push)->where(array('is_active=1'))->count();
                    $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();
                    $askhelporder['buyuserlevel'] = getviplevel($directpush, $myteams);
                    //获取支付宝账户和银行卡信息
                    $askhelporder['buyuser_alipay'] = M('user_ali_number')->where(array('user_id' => $thebuyorderinfo['user_id'], 'del' => '0'))->getField('ali_num');
                    $askhelporder['buyuser_banknum'] = M('user_idcard')->where(array('user_id' => $thebuyorderinfo['user_id'], 'del' => '0'))->select();
                    //领导人昵称和联系方式
                    $buyuserleader = M('user')->where(array('user_id' => $thebuyorderinfo['user_id']))->getField('user_parent');
                    if (!empty($buyuserleader)) {
                        $buyuserleaders = array_reverse(explode(',', $buyuserleader));
                        $askhelporder['buyuser_leadername'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_name');
                        $askhelporder['buyuser_leaderphone'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_phone');
                    } else {
                        $askhelporder['buyuser_leadername'] = "";
                        $askhelporder['buyuser_leaderphone'] = "";
                    }
                }
                if ($askhelporder['matching'] == 1) {
                    if ($askhelporder['status'] == 1) {
                        if ($complaint == 3) {
                            $askhelporder['saletype'] = "交易中(已投诉)";
                        } else {
                            $askhelporder['saletype'] = "交易中(待确认)";
                        }
                    } else {
                        $askhelporder['saletype'] = "交易中(待付款)";
                    }
                } else {
                    $askhelporder['saletype'] = "已完成";
                }
            }
        }
//        dump($askhelporder);die;
        $i=0;
        $child = [];
        foreach ($listchild as $k=>$v){
            $i = $i+1;
            $listchild[$k]['money'] = $v['amount'] * $config['stock_price'];
            if($i > 1){
                array_push($child,$v);
            }
        }
        if($i == 1){
            $this->assign('listchild', $listchild);
        }else{
            foreach ($child as $k=>$v){
                $child[$k]['money'] = $v['amount'] * $config['stock_price'];
            }
            $this->assign('listchild', $child);
        }
        $money = $askhelporder['amount'] * $config['stock_price'];
        $this->assign('money', $money);
        $this->assign('askhelporder', $askhelporder);
        $this->assign('config', $config);
        $this->display('Order/order-get-details');
    }

    /*
    *点赞功能,ajax调用此接口
    */
    public function thumbsup()
    {
        $userid = session('user_id');
        $thesaleid = I('request.saleorderid');
        //根据卖出订单找到买入订单信息
        $buyorderid = M('MatchOrder')->where(array('sale_order_id' => $thesaleid))->getField('buy_order_id');
        $thebuyorderinfo = M('HelpOrder')->where(array('id' => $buyorderid))->find();
        if (empty($thebuyorderinfo)) {
            $this->ajaxReturn(['status' => '0', 'message' => '对方买入交易非法,不可为他点赞']);
        } else {
            $m = M();
            $m->startTrans();
            try {
                if ($thebuyorderinfo['user_parent_id'] != '0') {//是下级玩家未付款,上级代付时
                    //为上级添加10点信誉值,并给予上级预约金额的1%作为动态奖励
                    $userinfo = M('user')->where(array('user_id' => $thebuyorderinfo['user_parent_id']))->find();
                    $walletinfo = M('wallet')->where(array('user_id' => $thebuyorderinfo['user_parent_id']))->find();
                    //添加信誉值记录
                    $result = M('user')->where(array('user_id' => $thebuyorderinfo['user_parent_id']))->setInc('user_reputation', 10);
                    $data['user_id'] = $userinfo['user_id'];
                    $data['user_name'] = $userinfo['user_name'];
                    $data['user_phone'] = $userinfo['user_phone'];
                    $data['amount'] = '10';
                    $data['old_amount'] = $userinfo['user_reputation'];
                    $data['remain_amount'] = $userinfo['user_reputation'] + 10;
                    $data['change_date'] = time();
                    $data['log_note'] = "点赞增加10点信誉值";
                    $data['wallet_type'] = '5';
                    $result1 = M('wallet_log')->add($data);
                    //添加动态奖金记录
                    $result2 = M('wallet')->where(array('user_id' => $thebuyorderinfo['user_parent_id']))->setInc('change_amount', $thebuyorderinfo['amount'] / 100);
                    $data1['user_id'] = $userinfo['user_id'];
                    $data1['user_name'] = $userinfo['user_name'];
                    $data1['user_phone'] = $userinfo['user_phone'];
                    $data1['amount'] = $thebuyorderinfo['amount'] / 100;
                    $data1['old_amount'] = $walletinfo['change_amount'];
                    $data1['remain_amount'] = $walletinfo['change_amount'] + $thebuyorderinfo['amount'] / 100;
                    $data1['change_date'] = time();
                    $data1['log_note'] = "点赞增加订单金额的1%作为动态奖励";
                    $data1['wallet_type'] = '2';
                    $result3 = M('wallet_log')->add($data1);
                    //修改点赞状态
                    $result4 = M('HelpOrder')->where(array('id' => $buyorderid))->setField('is_good', '1');
                } else {//未超时未打款
                    //为买入方添加10点信誉值,并给予总订单的1%作为动态奖励
                    $userinfo = M('user')->where(array('user_id' => $thebuyorderinfo['user_id']))->find();
                    $walletinfo = M('wallet')->where(array('user_id' => $thebuyorderinfo['user_id']))->find();
                    //添加信誉值记录
                    $result = M('user')->where(array('user_id' => $thebuyorderinfo['user_id']))->setInc('user_reputation', 10);
                    $data['user_id'] = $userinfo['user_id'];
                    $data['user_name'] = $userinfo['user_name'];
                    $data['user_phone'] = $userinfo['user_phone'];
                    $data['amount'] = '10';
                    $data['old_amount'] = $userinfo['user_reputation'];
                    $data['remain_amount'] = $userinfo['user_reputation'] + 10;
                    $data['change_date'] = time();
                    $data['log_note'] = "点赞增加10点信誉值";
                    $data['wallet_type'] = '5';
                    $result1 = M('wallet_log')->add($data);
                    //添加动态奖金记录
                    $result2 = M('wallet')->where(array('user_id' => $thebuyorderinfo['user_id']))->setInc('change_amount', $thebuyorderinfo['parent_amount'] / 100);
                    $data1['user_id'] = $userinfo['user_id'];
                    $data1['user_name'] = $userinfo['user_name'];
                    $data1['user_phone'] = $userinfo['user_phone'];
                    $data1['amount'] = $thebuyorderinfo['parent_amount'] / 100;
                    $data1['old_amount'] = $walletinfo['change_amount'];
                    $data1['remain_amount'] = $walletinfo['change_amount'] + $thebuyorderinfo['parent_amount'] / 100;
                    $data1['change_date'] = time();
                    $data1['log_note'] = "点赞增加总订单金额的1%作为动态奖励";
                    $data1['wallet_type'] = '2';
                    $result3 = M('wallet_log')->add($data1);
                    //修改点赞状态
                    $result4 = M('HelpOrder')->where(array('id' => $buyorderid))->save(['is_good' => '1']);
                }
                $m->commit();
            } catch (\PDOException $e) {
                $m->rollback();
            }
            if ($result && $result1 && $result2 && $result3 && $result4) {
                $this->ajaxReturn(['status' => '1', 'message' => '点赞成功']);
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '点赞失败']);
            }
        }
    }

    /*
    *奖金列表
    */
    public function order_bonus()
    {
        $userid = session('user_id');
        $this->display('Order/order-bonus');
    }

    /*
    去收款
    */
    public function make_money_get()
    {
        $userid = session('user_id');
        $theid = I('request.id');
        $config = M('config')->find(1);
        $list = M('MatchOrder')->where(array('sale_order_id' => $theid))->find();
        $list['buy_truename'] = M('user')->where(array('user_id' => $list['buy_id']))->getField('user_truename');
        if ($list['status'] == 1) {//对方已打款,显示倒计时
            $list['endtime'] = strtotime($list['payed_time']) + $config['pay_time_max'] * 3600;
            $list['endtime'] = $list['endtime'] * 1000;
        }
        if ($list['status'] == 2) {//已确认收款,显示收款时间
            $list['endtime'] = strtotime($list['receive_time']);
            $list['endtime'] = $list['endtime'] * 1000;
        }
        $list['pay_img'] = M('PayedOrder')->where(array('match_id' => $list['id']))->getField('img_payed');
        $list['saleordernum'] = M('AskhelpOrder')->where(array('id' => $theid))->getField('order_number');
        $list['saleorderbuy'] = M('AskhelpOrder')->where(array('id' => $theid))->getField('addtime');
        //获取买入方信息
        $buyinfo = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->find();
        if ($buyinfo['user_parent_id'] == 0) {
            $buyuserinfo = M('user')->where(array('user_id' => $buyinfo['user_id']))->find();
            $push = [
                'user_parent' => array('like', array('%' . ',' . $buyinfo['user_id'], $buyinfo['user_id']), 'OR'),    //直推人数
            ];
            $push2 = [
                'user_parent' => array('like', array($buyinfo['user_id'] . ',' . '%', '%' . ',' . $buyinfo['user_id'], '%' . ',' . $buyinfo['user_id'] . ',' . '%', $buyinfo['user_id']), 'OR'),   //团队人数
            ];
            $directpush = M('user')->where($push)->where(array('is_active=1'))->count();
            $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();
            $buyuserinfo['buyuserlevel'] = getviplevel($directpush, $myteams);
            //获取支付宝账户和银行卡信息
            $buyuserinfo['buyuser_alipay'] = M('user_ali_number')->where(array('user_id' => $buyinfo['user_id'], 'del' => '0'))->getField('ali_num');
            $buyuserinfo['buyuser_banknum'] = M('user_idcard')->where(array('user_id' => $buyinfo['user_id'], 'del' => '0'))->select();
            //领导人昵称和联系方式
            $buyuserleader = M('user')->where(array('user_id' => $buyinfo['user_id']))->getField('user_parent');
            if (!empty($buyuserleader)) {
                $buyuserleaders = array_reverse(explode(',', $buyuserleader));
                $buyuserinfo['buyuser_leadername'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_name');
                $buyuserinfo['buyuser_leaderphone'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_phone');
            } else {
                $buyuserinfo['buyuser_leadername'] = "";
                $buyuserinfo['buyuser_leaderphone'] = "";
            }
        } else {
            $buyuserinfo = M('user')->where(array('user_id' => $buyinfo['user_parent_id']))->find();
            $push = [
                'user_parent' => array('like', array('%' . ',' . $buyinfo['user_parent_id'], $buyinfo['user_parent_id']), 'OR'),    //直推人数
            ];
            $push2 = [
                'user_parent' => array('like', array($buyinfo['user_parent_id'] . ',' . '%', '%' . ',' . $buyinfo['user_parent_id'], '%' . ',' . $buyinfo['user_parent_id'] . ',' . '%', $buyinfo['user_parent_id']), 'OR'),   //团队人数
            ];
            $directpush = M('user')->where($push)->where(array('is_active=1'))->count();
            $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();
            $buyuserinfo['buyuserlevel'] = getviplevel($directpush, $myteams);
            //获取支付宝账户和银行卡信息
            $buyuserinfo['buyuser_alipay'] = M('user_ali_number')->where(array('user_id' => $buyinfo['user_parent_id'], 'del' => '0'))->getField('ali_num');
            $buyuserinfo['buyuser_banknum'] = M('user_idcard')->where(array('user_id' => $buyinfo['user_parent_id'], 'del' => '0'))->select();
            //领导人昵称和联系方式
            $buyuserleader = M('user')->where(array('user_id' => $buyinfo['user_parent_id']))->getField('user_parent');
            if (!empty($buyuserleader)) {
                $buyuserleaders = array_reverse(explode(',', $buyuserleader));
                $buyuserinfo['buyuser_leadername'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_name');
                $buyuserinfo['buyuser_leaderphone'] = M('user')->where(array('user_id' => $buyuserleaders[0]))->getField('user_phone');
            } else {
                $buyuserinfo['buyuser_leadername'] = "";
                $buyuserinfo['buyuser_leaderphone'] = "";
            }
        }
        $money = $list['amount'] * $config['stock_price'];
        $this->assign('money', $money);
        $this->assign('buyuserinfo', $buyuserinfo);
        $this->assign('list', $list);
        $this->assign('config', $config);
        $this->display('Order/order-confirm');
    }

    /*
    卖出方投诉(投诉分为两种情况  1:对方超时未打款投诉   2:对方假打款投诉)
    */
    public function salecomplaint()
    {
        $userid = session('user_id');
        $matchid = I('request.matchid');
        $config = M('config')->find(1);
        $list = M('MatchOrder')->where(array('id' => $matchid))->find();
        if ($list['status'] == 2) {//已确认收款
            $this->ajaxReturn(['status' => '0', 'message' => '您已确认收款,不可投诉!']);
        } elseif ($list['status'] == 3) {//已投诉过
            $this->ajaxReturn(['status' => '0', 'message' => '您已投诉过!请等待后台处理']);
        } elseif ($list['status'] == 0) {//对方未打款
            $this->ajaxReturn(['status' => '0', 'message' => '对方还未打款,暂不可投诉']);
        } else {//对方已打款,不过是假打款
            $result = M('MatchOrder')->where(array('id' => $matchid))->setField('status', '3');
            if ($result) {
                $this->ajaxReturn(['status' => '1', 'message' => '投诉成功!请等待后台处理']);
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '投诉失败,您可能已经投诉过了']);
            }
        }
    }

    /*
    确认收款
    */
    public function besuregeted()
    {
        $userid = session('user_id');

        $matchid = I('request.matchid');
        $config = M('config')->find(1);
        $list = M('MatchOrder')->where(array('id' => $matchid))->find();

        switch ($list['status']) {
            case '0':
                $this->ajaxReturn(['status' => '0', 'message' => '对方还未打款,您不可确认收款']);
                break;
            case '2':
                $this->ajaxReturn(['status' => '0', 'message' => '您已确认收过款,不可重复确认收款']);
                break;
            case '3':
                $this->ajaxReturn(['status' => '0', 'message' => '您已举报对方假打款,不可确认收款']);
                break;
            default:
                break;
        }
        if ($list['status'] == 1) {//对方已打款
            $list['payed_time'] = strtotime($list['payed_time']);
            $yufutype = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->find();
            if ($yufutype) {//单子还存在时
                //激活购买
                if ($yufutype['buy'] == 1) {
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
                    M('help_order')->where(array('id'=>$list['buy_order_id']))->data(array('matching'=>2))->save();
                    $res = M('askhelp_order')->where(array('id'=>$list['sale_order_id']))->data(array('matching'=>2,'status'=>2))->save();
                    M('match_order')->where(array('buy_order_id'=>$list['buy_order_id']))->data(array('receive_time'=>date('Y-m-d H:i:s',time()),'status'=>2))->save();
                    $yuyue = M('wallet')->where(array('user_id' => $yufutype['user_id']))->setInc(array('static_amount' => $yufutype['amount']));
                    if ($res) {
                        $user = M('user')->where(array('user_id'=>$list['buy_order_id']))->find();
                        if($user['is_active'] == 0){
                            M('user')->where(array('user_id'=>$list['buy_order_id']))->data(array('is_active'=>1))->save();
                        }
                        $this->ajaxReturn(['status' => '1', 'message' => '确认收款成功']);
                    } else {
                        $this->ajaxReturn(['status' => '0', 'message' => '确认收款失败']);
                    }
                }
            }
            //预约购买
            $m = M();
            $m->startTrans();
            try {
                //修改卖出/买入/匹配/支付订单的状态,同时添加利息记录
                $result = M('AskhelpOrder')->where(array('id' => $list['sale_order_id']))->save(['status' => '2', 'matching' => '2']);
                //判断卖出总订单是否已经交易完成
                $psaleorderid = M('AskhelpOrder')->where(array('id' => $list['sale_order_id']))->getField('parent_id');
                $allsalestatus = M('AskhelpOrder')->where(array('parent_id' => $psaleorderid))->where('order_type!=1')->getField('matching', true);
                if (!in_array('0', $allsalestatus) && !in_array('1', $allsalestatus)) {//总单子已交易完成
                    //修改总单子的状态
                    M('AskhelpOrder')->where(array('id' => $psaleorderid))->save(['matching' => '2', 'status' => '2']);
                }
                $result1 = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->save(['matching' => '2']);
                $result2 = M('MatchOrder')->where(array('id' => $matchid))->save(['status' => '2', 'receive_time' => date('Y-m-d H:i:s', time())]);
                $result3 = M('PayedOrder')->where(array('match_id' => $matchid))->save(['status' => '2', 'end_time' => time()]);
                $thehelpinfo = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->find();
                if ($yufutype) {//单子还存在时
                    if ($list['payed_time'] + $config['pay_time_min'] * 3600 > time()) {
                        //正常利息+诚信奖
                        $data['user_id'] = $list['buy_id'];
                        $data['buy_order'] = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('id');//记录订单的id
                        $pamount = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('parent_amount');
                        $amount = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('amount');
                        $data['benjin'] = $amount;//订单的金额
                        $data['amount'] = $amount * ($config['interest_price'] + $config['pay_time_award']) / 100;//利息部分
                        $data['allamount'] = $data['benjin'] + $data['amount'];//本金+利息
                        $data['addtime'] = time();
                        $data['status'] = '1';
                        $data['statustow'] = '1';
                        $result4 = M('interest')->add($data);
                    } else {
                        //正常利息
                        $data['user_id'] = $list['buy_id'];
                        $data['buy_order'] = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('id');//记录订单的id
                        $pamount = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('parent_amount');
                        $amount = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('amount');
                        $data['benjin'] = $amount;//订单的金额
                        $data['amount'] = $amount * $config['interest_price'] / 100;//利息部分
                        $data['allamount'] = $data['benjin'] + $data['amount'];//本金+利息
                        $data['addtime'] = time();
                        $data['status'] = '1';
                        $data['statustow'] = '1';
                        $result4 = M('interest')->add($data);
                    }
                    //发放动态奖金,判断是否开启烧伤;先判断总单子是否交易完毕
                    $porderid = M('HelpOrder')->where(array('id' => $list['buy_order_id']))->getField('parent_id');
                    $allstatus = M('HelpOrder')->where(array('parent_id' => $porderid))->where('order_type!=0')->getField('matching', true);
                    if (!in_array('0', $allstatus) && !in_array('1', $allstatus)) {//总单子已交易完成
                        //修改总单子的状态
                        M('HelpOrder')->where(array('id' => $porderid))->save(['matching' => '2', 'status' => '1']);
                    }
                    //查询买入方的一代至三代
                    $allparentid = M('user')->where(array('user_id' => $list['buy_id']))->getField('user_parent');
                    $allparent = array_reverse(explode(',', $allparentid));
                    $oneparent = $allparent[0];
                    $towparent = $allparent[1];
                    $threeparent = $allparent[2];
                    //不要删
                    if ($oneparent) {//一代存在时,判断一代是否是VIP1及以上等级
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
//                            if ($theviplecel == 1 && $isactive == 1) {
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
                    if ($towparent) {//二代存在时,判断二代是否是VIP2及以上等级
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
                        if ($isactive == 1 && $directpush >=2) {
                            $dongtaimoney = $amount * $config['reward_rate2'] / 100;
                            $userwallet = M('wallet')->where(array('user_id' => $towparent))->find();
                            M('wallet')->where(array('user_id' => $towparent))->setInc('static_amount', $dongtaimoney);
                            //增加钱包变动记录
                            $parentuser = M('user')->where(array('user_id' => $towparent))->find();
                            $data2['user_id'] = $towparent;
                            $data2['user_name'] = $parentuser['user_name'];
                            $data2['user_phone'] = $parentuser['user_phone'];
                            $data2['amount'] = $dongtaimoney;
                            $data2['old_amount'] = $userwallet['static_amount'];
                            $data2['remain_amount'] = $userwallet['static_amount'] + $dongtaimoney;
                            $data2['change_date'] = time();
                            $data2['log_note'] = "二代股权奖励";
                            $data2['wallet_type'] = '1';
                            M('wallet_log')->add($data2);
                        }
                    }
                    if ($threeparent) {//三代存在时,判断三代是否是VIP3及以上等级
                        $push = [
                            'user_parent' => array('like', array('%' . ',' . $threeparent, $threeparent), 'OR'),    //直推人数
                        ];
                        $push2 = [
                            'user_parent' => array('like', array($threeparent . ',' . '%', '%' . ',' . $threeparent, '%' . ',' . $threeparent . ',' . '%', $threeparent), 'OR'),   //团队人数
                        ];
                        $directpush = M('user')->where($push)->where(array('is_active=1'))->count();//直推
                        $myteams = M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                        $theviplecel = getvipleveltow($directpush, $myteams);
                        //判断三代是否激活
                        $isactive = M('user')->where(array('user_id' => $threeparent))->getField('is_active');
                        if ($isactive == 1 && $directpush >=5) {
                            $dongtaimoney = $amount * $config['reward_rate3'] / 100;
                            $userwallet = M('wallet')->where(array('user_id' => $threeparent))->find();
                            M('wallet')->where(array('user_id' => $threeparent))->setInc('static_amount', $dongtaimoney);
                            //增加钱包变动记录
                            $parentuser = M('user')->where(array('user_id' => $threeparent))->find();
                            $data3['user_id'] = $threeparent;
                            $data3['user_name'] = $parentuser['user_name'];
                            $data3['user_phone'] = $parentuser['user_phone'];
                            $data3['amount'] = $dongtaimoney;
                            $data3['old_amount'] = $userwallet['static_amount'];
                            $data3['remain_amount'] = $userwallet['static_amount'] + $dongtaimoney;
                            $data3['change_date'] = time();
                            $data3['log_note'] = "三代股权奖励";
                            $data3['wallet_type'] = '1';
                            M('wallet_log')->add($data3);
                        }
                    }
                }
                $m->commit();
            } catch (PDOException $exc) {
                $m->rollback();
            }
            if ($result) {//$result1因同一其他子单子超时未打款而删除该子单子,造成帮助表中已经没有了该单子,从而造成确认收款失败
                $this->ajaxReturn(['status' => '1', 'message' => '确认收款成功']);
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '确认收款失败']);
            }
        }
    }

    /*
    *卖出红酒待匹配记录
    */
    public function sell_wait_list()
    {
        $userid = session('user_id');
        $list = M('AskhelpOrder')->where(array('user_id' => $userid, 'matching' => '0'))->order('addtime desc')->select();
        $this->assign('list', $list);
        $this->display('Index/sell_wait_list');
    }

    /*
    *买入红酒待匹配记录
    */
    public function buy_wait_list()
    {
        $userid = session('user_id');
        $list = M('HelpOrder')->where(array('user_id' => $userid, 'matching' => '0'))->order('addtime desc')->select();
        $this->assign('list', $list);
        $this->display('Index/buy_wait_list');
    }

    /*
    提现,AJAX调用
    */
    public function cashmoney()
    {
        $userid = session('user_id');
        $buyorderid = I('request.buyorderid');
        $config = M('config')->find(1);
        $userinfo = M('user')->where(array('user_id' => $userid))->find();
        $walletinfo = M('wallet')->where(array('user_id' => $userid))->find();
        //判断是否已经提过现了,提过后不能再提,目的是防止网速卡顿
        $cashstatus = M('interest')->where(array('user_id' => $userid, 'buy_order' => $buyorderid))->find();
        if (empty($cashstatus)) {
            $this->ajaxReturn(['status' => '0', 'message' => '该订单不存在']);
        }
        if ($cashstatus['status'] == '2') {
            $this->ajaxReturn(['status' => '0', 'message' => '您已提现,请勿重复操作']);
        }
        $m = M();
        $m->startTrans();
        try {
            //修改利息表状态
            $result = M('interest')->where(array('user_id' => $userid, 'buy_order' => $buyorderid))->save(['turntime' => time(), 'status' => '2']);
            //修改用户钱包金额
            $result1 = M('wallet')->where(array('user_id' => $userid))->setInc('static_amount', $cashstatus['allamount']);
            //记录钱包变动信息
            $data['user_id'] = $userid;
            $data['user_name'] = $userinfo['user_name'];
            $data['user_phone'] = $userinfo['user_phone'];
            $data['amount'] = $cashstatus['allamount'];
            $data['old_amount'] = $walletinfo['static_amount'];
            $data['remain_amount'] = $cashstatus['allamount'] + $walletinfo['static_amount'];
            $data['change_date'] = time();
            $data['log_note'] = "本金+利息提现到静态钱包";
            $data['wallet_type'] = '1';
            $result2 = M('wallet_log')->add($data);
            //增加10点信誉值
            $result3 = M('user')->where(array('user_id' => $userid))->setInc('user_reputation', 10);
            $data2['user_id'] = $userinfo['user_id'];
            $data2['user_name'] = $userinfo['user_name'];
            $data2['user_phone'] = $userinfo['user_phone'];
            $data2['amount'] = '10';
            $data2['old_amount'] = $userinfo['user_reputation'];
            $data2['remain_amount'] = $userinfo['user_reputation'] + 10;
            $data2['change_date'] = time();
            $data2['log_note'] = "完成订单增加10点信誉值";
            $data2['wallet_type'] = '5';
            $result4 = M('wallet_log')->add($data2);
            $m->commit();
        } catch (\PDOException $e) {
            $m->rollback();
        }
        if ($result && $result1 && $result2 && $result3 && $result4) {
            $this->ajaxReturn(['status' => '1', 'message' => '提现成功']);
        } else {
            $this->ajaxReturn(['status' => '0', 'message' => '提现失败']);
        }
    }

    //超时封号
    public function fenghao(){
        $uid = session('user_id');
        M('user')->where(array('user_id'=>$uid))->data(array('user_status'=>0,'cold_resone'=>'超时'))->save();
    }
}
