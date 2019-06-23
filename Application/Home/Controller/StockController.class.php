<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Common\Controller\HomeBaseController;
/**
 * 空模块，主要用于显示404页面，请不要删除
 */
class StockController extends HomeBaseController{
	public function stockright(){
        $userid = session('user_id');
        //个人信息
        $userinfo=M('user')->where(array('user_id'=>$userid))->find();
        //参数设置
        $config=M('config')->find(1);
        //钱包信息
        $walletinfo=M('wallet')->where(array('user_id'=>$userid))->find();
        $walletinfo['stock_money'] = $walletinfo['static_amount'] * $config['stock_price'];
        //冻结钱包
        $freeze = M('interest')->where(array('user_id'=>$userid))->select();
        $freezemoney = 0;
        foreach ($freeze as &$v){
            $nowtime = time();
            $endtime = $v['addtime'] + $config['frozen_time'] * 86400;
            if($endtime > $nowtime){
                $freezemoney = $freezemoney + $v['allamount'];
            }
        }
        $freezemoney = $freezemoney * $config['stock_price'];
        $walletinfo['freeze'] = $freezemoney;
        //股权价格k线图
        $stocklist = M('stock_price')->select();
        $time = [];
        $price = [];
        $num = 0;
        foreach ($stocklist as $k){
            $num = $num + 1;
            $date = date('Y-m-d',strtotime($k['time']));
            array_push($time,$date);
            array_push($price,$k['stock_price']);
        }
        $num = ceil(3 / $num * 100);
        $price = json_encode($price);
        $time = json_encode($time);
//        dump($time);die;
        $this->assign('num',$num);
        $this->assign('time',$time);
        $this->assign('price',$price);
        $this->assign('stocklist',$stocklist);
        $this->assign('config',$config);
        $this->assign('walletinfo',$walletinfo);
        $this->assign('userinfo',$userinfo);
		$this->display('Stockright/stockright');
	}
}
