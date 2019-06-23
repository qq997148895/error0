<?php

namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;
use Adminlmcq\Controller\UserController;
/*
 * 首页控制器
 * Author:chenmengchen
 * Date:2017/03/28
 */

class IndexController extends AdminlmcqBaseController
{
    /*
     * 首页展示
     */
    public function index()
    {
        $this->display('Index/main');
    }
    public function top()
    {
        $this->display('Index/top');
    }
    public function left()
    {
        $this->display('Index/left');
    }
    public function wellcome(){
        $username=session('admin_name');
        //查询平台用户信息
        $user=M('user');
        $userinfo['allofus']=$user->count();
        $userinfo['activeofus']=$user->where(array('is_active=1'))->count();
        $userinfo['coldofus']=$user->where(array('user_status=0'))->count();
        $now1=date('Y-m-d',time());
        $now2=date('Y-m-d',time());
        //$time1=strtotime('$now1 0:0:0');
        //$time2=strtotime('$now2 23:59:59');
        $time1=strtotime(date($now1),time());
        $time2=$time1+24*3600-1;
        $userinfo['todayus']=$user->where(array("user_add_time>='$time1' and user_add_time<='$time2'"))->count();
        $userinfo['todayactive']=$user->where('to_days(user_active_time) = to_days(now())')->count();
        $lasttime1=$time1-24*3600;
        $lasttime2=$time2-24*3600;
        $lasttime1=date('Y-m-d H:i:s',$lasttime1);
        $lasttime2=date('Y-m-d H:i:s',$lasttime2);
        $userinfo['lastus']=$user->where(array("user_add_time>='$lasttime1' and user_add_time<='$lasttime2'"))->count();
        $userinfo['lastactive']=$user->where(array("user_active_time>='$lasttime1' and user_active_time<='$lasttime2'"))->count();
        $this->assign('userinfo',$userinfo);
        $this->assign('username',$username);
        //查询系统订单信息M('HelpOrder')->where(array('parent_id=id'))->where('to_days(now()) - to_days(addtime) <= 1')->count();
        $orderinfo['unmatchporder']=M('HelpOrder')->where(array('matching=0 and order_type!=0'))->sum('amount');
        $orderinfo['unmatchporder']=empty($orderinfo['unmatchporder'])?0:$orderinfo['unmatchporder'];
        $orderinfo['unmatchgorder']=M('AskhelpOrder')->where(array('matching=0 and order_type!=1'))->sum('amount');
        $orderinfo['unmatchgorder']=empty($orderinfo['unmatchgorder'])?0:$orderinfo['unmatchgorder'];
        $orderinfo['matchporder']=M('HelpOrder')->where(array('matching=1 and order_type!=0 or matching=2 and order_type!=0'))->sum('amount');
        $orderinfo['matchporder']=empty($orderinfo['matchporder'])?0:$orderinfo['matchporder'];
        $orderinfo['matchgorder']=M('AskhelpOrder')->where(array('matching=1 and order_type!=1 or matching=2 and order_type!=1'))->sum('amount');
        $orderinfo['matchgorder']=empty($orderinfo['matchgorder'])?0:$orderinfo['matchgorder'];
        $orderinfo['buyallnum']=count(M('HelpOrder')->distinct(true)->getField('parent_id',true));
        $orderinfo['saleallnum']=count(M('AskhelpOrder')->distinct(true)->getField('parent_id',true));
        $orderinfo['todayordernum']=M('HelpOrder')->where(array('parent_id=id'))->where('to_days(addtime) = to_days(now())')->count();
        $orderinfo['yesterdaynum']=M('HelpOrder')->where(array('parent_id=id'))->where('to_days(now()) - to_days(addtime) = 1')->count();
        $orderinfo['monthnum']=M('HelpOrder')->where(array('parent_id=id'))->where('date_format(addtime,"%Y%m") = date_format(curdate(),"%Y%m")')->count();
        $orderinfo['sixmonthnum']=M('HelpOrder')->where(array('parent_id=id'))->where('addtime between date_sub(now(),interval 6 month) and now()')->count();
        $orderinfo['yearmonthnum']=M('HelpOrder')->where(array('parent_id=id'))->where('addtime between date_sub(now(),interval 12 month) and now()')->count();
        $this->assign('orderinfo',$orderinfo);
        $this->display('Index/index');
    }
}
