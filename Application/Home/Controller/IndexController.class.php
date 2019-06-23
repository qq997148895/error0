<?php
/**
 * Author: zj
 * Date: 2017/3/23
 * 首页数据展示
 */
namespace Home\Controller;

use Think\Controller;

use Common\Controller\HomeBaseController;
use Home\Logic\fishPoll\allpollshow;


class IndexController extends HomeBaseController
{
    //签到页
    public function signIn(){
        $this->signInHandler();
//		$this->display('Index/signin');
    }
    /*
     * 签到
     */
    public function signInHandler(){
        $signin = M('user_signin');
        $userid=$_SESSION ['user_id'];

        $list=M('user_signin')->where(array('user_id'=>$userid))->order('sign_time DESC')->select();//查询集合 根据时间排序
        if ( sizeof($list)>1) {
            //不是第一次签到  获取第二个数据  和时间戳进行比较
            $singinfo = $list[0];
            $singdata = $singinfo['sign_time'];
                if (date('Y-m-d') - strtotime("$singdata") > 24 * 60 * 60) {
                //user表中的连续签到设置为0
                $news['continuous_sign'] = 0;
                D('user')->where(array('user_id' => $userid))->save($news);
            }
        }
        $user = M('user')->where(array('user_id'=>$userid))->find();
        $time=$signin->where(array('user_id'=>$userid))->getField('sign_time',true);
        $count=$signin->where(array('user_id'=>$userid))->count();
        $j = date("t"); //获取当前月份天数
        $start_time = strtotime(date('Y-m-01')); //获取本月第一天时间戳
        $array = array();
        $aa = $this->getweek($array[0][0]);
        for($i=0;$i<$j;$i++){
            $array[][] = date('Y-m-d',$start_time+$i*86400); //每隔一天赋值给数组
        }
        if ($aa == 1) {
            array_unshift($array,[""]);
        } elseif ($aa == 2) {
            array_unshift($array,[""], [""]);
        } elseif ($aa == 3) {
            array_unshift($array,[""], [""], [""]);
        } elseif ($aa == 4) {
            array_unshift($array,[""], [""],[""], [""]);
        } elseif ($aa == 5) {
            array_unshift($array,[""], [""],[""], [""], [""]);
        } elseif ($aa == 6) {
            $array[0][0] = "";
            $array[1][0] = "";
            $array[2][0] = "";
            $array[3][0] = "";
            $array[4][0] = "";
            $array[5][0] = "";
            array_unshift($array,[""], [""],[""], [""], [""], [""]);
        }
        foreach ($array as $k=>$v) {
            if(in_array($v[0], $time)) {
                $array[$k][0] = trim(strrchr($v[0], '-'),'-');
                $array[$k]['sign'] = 0;
            } else {
                $array[$k][0] = trim(strrchr($v[0], '-'),'-');
                $array[$k]['sign'] = 1;
            }
        }

        //查goods表的图片
        $listgoods=M('goods')->where(array('isadmin'=>2))->select();
        $this->assign('listgoods',$listgoods);//商品图片列表
        $this->assign('count',$user['continuous_sign']);
        $this->assign('array' , $array);
        $this->display('Index/signIn');
    }
    //持久化签到记录
    public function signInTo(){
        $userid=$_SESSION ['user_id'];
        $signin = M('user_signin');
        $date=date("y-m-d");
        $result=$signin->where(array('user_id'=>$userid,'sign_time'=>$date))->find();
        if (!$result){
            $signin->add(array('user_id'=>$userid,'sign_time'=>$date));
        }else{
            $this->ajaxReturn(['status'=>0,'message'=>'今天已经签到过了']);
        }
        if($signin){
            //user表要持久化一下  首先判断与上次签到时间是否大于24*60*60
            //首先查询集合 是否是第一次签到 是第一次
            $list=M('user_signin')->where(array('user_id'=>$userid))->order('sign_time DESC')->select();//查询集合 根据时间排序
            if ( sizeof($list)>1){
                //不是第一次签到  获取第二个数据  和时间戳进行比较
                $singinfo=$list[1];
                $singdata=$singinfo['sign_time'];
                if(strtotime("$date")- strtotime("$singdata")>24*60*60){
                    //user表中的连续签到设置为1
                    $news['continuous_sign'] =1;
                    D('user')->where(array('user_id'=>$userid))->save($news);
                }else{
                    // 需要在原来的基础上加1
                    $user = M('user')->where(array('user_id'=>$userid))->find();
                    $news['continuous_sign'] =$user['continuous_sign']+1;
                    D('user')->where(array('user_id'=>$userid))->save($news);
                }
            }else{
                //第一次签到
                $news['continuous_sign'] =1;
                D('user')->where(array('user_id'=>$userid))->save($news);
            }
            $user1 = M('user')->where(array('user_id'=>$userid))->find();
            $tian=$user1['continuous_sign'];
            $this->ajaxReturn(['status'=>1,'message'=>'签到成功','tian'=>$tian]);
        }else{
            $this->ajaxReturn(['status'=>0,'message'=>'签到失败']);
        }
    }
    //抢购
    public function rushtobuy(){
        $this->display('Index/rushtobuy');
    }

    //兑换商品选择地址
    public function dui(){
        $uid = session('user_id');
        $gid = I('request.id');
        $address=M('user_ship_address')->where(array('uid'=>$uid,'is_del'=>'0'))->order('is_default DESC')->select();
        foreach ($address as &$val) {
            $val['longaddress']=$val['address_pca'].$val['address_detailed'];//总地址,省/市/县/详细 的拼接
        }
        $this->assign('gid',$gid);
        $this->assign('address',$address);
        $this->display('Index/address');
    }

    //兑换商品下单
    public function duihuan(){
        $uid = session('user_id');
        $gid = I('post.gid');
        $aid = I('post.aid');
        $config=M('config')->where(array('id'=> 1))->find();
        $goods = M('goods')->where(array('id'=> $gid))->find(); //商品信息
        $address = M('user_ship_address')->where(array('id'=> $aid))->find();   //地址信息
        if($goods['goods_number'] < 1){
            $this->ajaxReturn(['status' => '0','message'=> '商品库存不足']);
        }
        $res = M('wallet')->where(array('user_id'=>$uid))->setDec('change_amount',20);
        if($res){
            $data['user'] = $uid;
            $data['user_phone'] = $address['phone'];
            $data['user_name'] = $address['name'];
            $data['order'] = $this->orderNum();
            $data['project'] = $goods['goods_name'];
            $data['count'] = 1;
            $data['sumprice'] = 20;
            $data['addtime'] = date('y-m-d H:i:s',time());
            $data['zt'] = 2;
            $data['address'] = $address['address_pca'].$address['address_detailed'];
            $data['project_id'] = $goods['id'];
            $data['type'] = 3;
            $res = M('shop_orderform')->data($data)->add(); //添加订单
            if($res){
                M('goods')->where(array('id'=>$gid))->setDec('goods_number'); //减少商品数量
                M('goods')->where(array('id'=>$gid))->setInc('goods_sell');  //增加商品销量
                $this->ajaxReturn(['status' => '1','message'=> '兑换成功']);
            }else{
                $this->ajaxReturn(['status' => '0','message'=> '生成订单失败']);
            }
        }else{
            $this->ajaxReturn(['status' => '0','message'=> '积分扣除失败']);
        }
    }

    //产生一个10位的 不重复订单号
    public function orderNum()
    {
        //组合一个10位的字符串
        $order_num = date('His') . rand(1000, 9999);
        $condition = M('shop_orderform')->where(array('order'=>$order_num))->find();
        if ($condition) {
            return $this->orderNum();
        } else {
            return $order_num;
        }
    }

    public function risk(){
        $this->display('Help/risk');
    }
    //首页
    public  function index(){
        $user=M('user');
        $userid = session('user_id');
        $config=M('config')->find(1);
        //公告
        $notices = M('news')->where(['type' => 1])->select();
        //单独获取第一张和最后一张图片
        $thefirstimg=M('indeximg')->order('id asc')->getField('imgpath');
        $thelastimg=M('indeximg')->order('id desc')->getField('imgpath');
        //获取首页轮播图
        $runimg=M('indeximg')->order('id asc')->select();
        //用户基本信息
        $userinfo=M('user')->where(array('user_id'=>$userid))->find();
        //获取用户VIP等级
        $push=[
            'user_parent'=>array('like',array('%'.','.$userid,$userid),'OR'),
        ];
        $push2=[
            //'user_parent'=>array('like','%'.$user_id.'%'),
            'user_parent'=>array('like',array($userid.','.'%','%'.','.$userid,'%'.','.$userid.','.'%',$userid),'OR'),
        ];
        $directpush=$user->where($push)->where(array('is_active=1'))->count();
        $myteams=$user->where($push2)->where(array('is_active=1'))->count();
        $userinfo['user_level']=getviplevel($directpush,$myteams);
        //获取激活码和排单币(宝石)数量
        $userinfo['active_num']=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->count();
        $walletinfo=M('wallet')->where(array('user_id'=>$userid))->find();
        //$notice=M('news')->where(array('type=1'))->getField('content');
        //获取系统总人数
        $allusers=$config['register_number']+M('user')->count();
        //获取推送/通知/申诉的新动态数
        $number1=M('user_notice')->where(array('user_id'=>$userid,'is_see'=>'1'))->count();
        $number2=M('feedback')->where(array('user_id'=>$userid,'is_see'=>'1'))->count();
        $map=[
            'date'=>array('gt',$userinfo['user_lastsee_time']),
        ];
        $number3=M('news')->where('type = 1')->where($map)->count();
        $allnews=$number3+$number2+$number1;
        $walletinfo['static_amount'] = $walletinfo['static_amount'] * $config['stock_price'];

        //冻结钱包管理 到期后转入股权钱包
        $freeze = M('interest')->where(array('user_id'=>$userid,'status'=>1))->select();
        foreach ($freeze as $v){
            $enndtime = strtotime(date('Y-m-d',$v['addtime'])) + $config['frozen_time'] * 86400;
            if($enndtime < time()){
                $res1 = M('interest')->where(array('id'=>$v['id']))->save(array('status'=>2));
                $res2 = M('wallet')->where(array('user_id'=>$userid))->setInc('static_amount',$v['allamount']);
                $wallet = M('wallet')->where(array('user_id'=>$userid))->find();
                $wallet_log1 = array(
                    'user_id' => $userid,
                    'user_name' => $userinfo['user_name'],
                    'user_phone' => $userinfo['user_phone'],
                    'amount' => '+' . $v['allamount'],
                    'old_amount' => $wallet['static_amount'],
                    'remain_amount' => $wallet['static_amount'] + $v['allamount'],
                    'change_date' => time(),
                    'log_note' => '买入股权',
                    'wallet_type' => 1,
                );
                $res3 = M('WalletLog')->data($wallet_log1)->add();
            }
        }
        //查goods表的图片
        $listgoods=M('goods')->where(array('isadmin'=>1))->select();//查询是商家添加的产品
        $this->assign('listgoods',$listgoods);//商品图片列表
        $this->assign('allnews',$allnews);
        $this->assign('userinfo',$userinfo);
        $this->assign('config',$config);
        $this->assign('walletinfo',$walletinfo);
        //$this->assign('notice',$notice);
        $this->assign('allusers',$allusers);
        $this->assign('thefirstimg',$thefirstimg);
        $this->assign('thelastimg',$thelastimg);
        $this->assign('list',$runimg);//轮播图
        $this->assign('notices',$notices);
        $this->display('Index/index');
    }
    /*
    财务管理静态钱包与动态钱包明细列表
    */
    public function finance(){
        $userid=session('user_id');
        // $type=I('request.type');
        // if (!$type) {//查询静态钱包变动信息
        //     $list=M('wallet_log')->where(array('user_id'=>$userid,'wallet_type'=>1))->order('change_date desc')->select();
        // }else{//查询动态钱包变动信息
        //     $list=M('wallet_log')->where(array('user_id'=>$userid,'wallet_type'=>2))->order('change_date desc')->select();
        // }
        $list=M('wallet_log')->where(array('user_id'=>$userid,'wallet_type'=>1))->order('change_date desc')->select();
        $count=M('wallet_log')->where(array('user_id'=>$userid,'wallet_type'=>1))->count();
        foreach ($list as &$val) {
            $val['order_id']='J'.$val['id'];
            $val['change_date']=date('Y-m-d H:i:s',$val['change_date']);
        }
        $list2=M('wallet_log')->where(array('user_id'=>$userid,'wallet_type'=>2))->order('change_date desc')->select();
        $count2=M('wallet_log')->where(array('user_id'=>$userid,'wallet_type'=>2))->count();
        foreach ($list2 as &$val) {
            $val['order_id']='D'.$val['id'];
            $val['change_date']=date('Y-m-d H:i:s',$val['change_date']);
        }
        $this->assign('list',$list);
        $this->assign('count',$count);
        $this->assign('count2',$count2);
        $this->assign('list2',$list2);
        $this->display('Index/management');
    }
    /*
    会员下级用户的买入与卖出订单详情
    */
    public function myuserinfo(){
        $userid=I('request.userid');//得到下级玩家的id
        //卖出红酒(接受帮助订单列表)
        $list=M('askhelp_order')->distinct(true)->where('user_id='.$userid)->field('parent_id,parent_amount,addtime,user_name,user_truename')->order('addtime desc')->select();//获取用户下单的订单编号
        //dump($list);die;
        foreach ($list as &$val) {
            //订单已匹配金额
            $map=[
                'parent_id' => $val['parent_id'],
                'matching' => array('neq',0),
            ];
            $val['matchok']=M('askhelp_order')->where($map)->sum('amount');
            //判断订单匹配状态
            $matching1=M('askhelp_order')->where(array('parent_id'=>$val['parent_id']))->select();
            $num1=0;  $num2=0; $num3=0;
            foreach ($matching1 as &$value) {
                if ($value['matching']==0) {
                    $num1=$num1+1;
                }elseif ($value['matching']==1) {
                    $num2=$num2+1;
                }elseif ($value['matching']==2) {
                    $num3=$num3+1;
                }
            }
            if ($num1==0&&$num2==0) {//订单已完成时
                $val['state']=1;//原始订单已完成
            }elseif ($num2!=0) {//有订单正在交易中
                $val['state']=2;//原始订单正在交易中
            }elseif ($num1!=0&&$num3!=0) {//有已完成也有未匹配订单时,订单处于交易中
                $val['state']=2;//原始订单正在交易中
            }else{
                $val['state']=3;//原始订单都未匹配
            }
        }
        //买入红酒(提供帮助订单列表)
        $list2=M('help_order')->distinct(true)->where('user_id='.$userid)->field('parent_id,parent_amount,addtime,user_name,user_truename')->order('addtime desc')->select();//获取用户下单的订单编号
        // dump($list2);die;
        foreach ($list2 as &$val) {
            //订单已匹配金额
            $map=[
                'parent_id' => $val['parent_id'],
                'matching' => array('neq',0),
            ];
            $val['matchok']=M('help_order')->where($map)->sum('amount');
            //判断订单匹配状态
            $matching2=M('help_order')->where(array('parent_id'=>$val['parent_id']))->select();
            $num1=0;  $num2=0; $num3=0;
            foreach ($matching2 as &$value) {
                if ($value['matching']==0) {
                    $num1=$num1+1;
                }elseif ($value['matching']==1) {
                    $num2=$num2+1;
                }elseif ($value['matching']==2) {
                    $num3=$num3+1;
                }
            }
            if ($num1==0&&$num2==0) {//订单已完成时
                $val['state']=1;//原始订单已完成
            }elseif ($num2!=0) {//有订单正在交易中
                $val['state']=2;//原始订单正在交易中
            }elseif ($num1!=0&&$num3!=0) {//有已完成也有未匹配订单时,订单处于交易中
                $val['state']=2;//原始订单正在交易中
            }else{
                $val['state']=3;//原始订单都未匹配
            }
        }
        $this->assign('list',$list);
        $this->assign('list2',$list2);
        $this->display('Index/jh_details');
    }
    /*
    抢单池-->展示超时未打款的提供帮助订单
    */
    public function snatch_pool(){
        $list=M('help_order')->where(array('snatch_pool'=>1))->select();
        $this->assign('list',$list);
        $this->display('Index/robbing');
    }
    /*
    抢单池抢单,ajax调用此接口,并返回相关信息
    */
    public function tosnatch(){
        $userid=session('user_id');
        $theid=I('request.orderid');
        if (M('user')->where(array('user_id'=>$userid))->getField('info_perfected')=='0') {
            $this->ajaxReturn(array('status' => '0', 'message' => '抢单前请先完善个人资料!'));
            die();
        }
        //提供帮助最多可未匹配订单数量
        $maxunorder=M('config')->where('id=1')->getField('unfinished_ordernumber');
        $nowunorder=M('help_order')->where(array('user_id'=>$userid,'matching'=>0))->count();//处于待匹配状态
        if ($nowunorder>=$maxunorder) {
            $this->ajaxReturn(array('status' => '0', 'message' => '待匹配订单数量已达上线,无法进行抢单!'));
            die();
        }
        $thetype=M('help_order')->where(array('id'=>$theid))->getField('snatch_pool');
        if ($thetype==1) {//订单还未被抢时,可以抢单
            $result=M('user')->where(array('user_id'=>$userid))->find();
            $data['user_id']=$userid;
            $data['user_name']=$result['user_name'];
            $data['user_truename']=$result['user_truename'];
            $data['user_phone']=$result['user_phone'];
            $data['snatch_pool']=0;//更改为普通订单
            if (M('help_order')->where(array('id'=>$theid))->save($data)) {
                //修改匹配表中的信息,更新匹配时间和提供帮助者ID与昵称
                $rel['buy_id']=$userid;
                $rel['buy_name']=$result['user_name'];
                $rel['status']='0';
                $rel['create_time']=date('Y-m-d H:i:s',time());
                M('match_order')->where(array('buy_order_id'=>$theid))->save($rel);
                $this->ajaxReturn(['status'=>1,'message'=>'恭喜,抢单成功!']);
            }else{
                $this->ajaxReturn(['status'=>0,'message'=>'抱歉,抢单失败!']);
            }
        }else{
            $this->ajaxReturn(['status'=>0,'message'=>'抱歉,此单已被抢!']);
        }
    }
    /*
    买入总额实时数据统计
    */
    public function buydata(){
        //累计买入总金额
        $config=$this->config;
        $money=M('help_order')->sum('amount');
        $allmoney=$config['helpfor_number']+$money;
        $allmoney=number_format($allmoney,2);
        $endtime=date('Y-m-d',time());
        $nowmonth=date('Y.m',time());
        $lastmonth1=date("Y.m",strtotime("-1 month"));
        $lastmonth2=date("Y.m",strtotime("-2 month"));
        $lastmonth3=date("Y.m",strtotime("-3 month"));
        $lastmonth4=date("Y.m",strtotime("-4 month"));
        $lastmonth5=date("Y.m",strtotime("-5 month"));
        //本月买入量
        $num6=M('help_order')->where('DATE_FORMAT( addtime, "%Y%m" ) = DATE_FORMAT( CURDATE( ) , "%Y%m" )')->sum('amount');
        if (!$num6) {
            $num6=0;
        }
        //上一月买入量
        $num5=M('help_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 1 MONTH),"%Y-%m")')->sum('amount');
        if (!$num5) {
            $num5=0;
        }
        //上上月买入量
        $num4=M('help_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 2 MONTH),"%Y-%m")')->sum('amount');
        if (!$num4) {
            $num4=0;
        }
        //上上上月买入量
        $num3=M('help_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 3 MONTH),"%Y-%m")')->sum('amount');
        if (!$num3) {
            $num3=0;
        }
        //上上上上月买入量
        $num2=M('help_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 4 MONTH),"%Y-%m")')->sum('amount');
        if (!$num2) {
            $num2=0;
        }
        //上上上上上月买入量
        $num1=M('help_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 5 MONTH),"%Y-%m")')->sum('amount');
        if (!$num1) {
            $num1=0;
        }
        // $this->assign('num6',$num6);
        // $this->assign('num5',$num5);
        // $this->assign('num4',$num4);
        // $this->assign('num3',$num3);
        // $this->assign('num2',$num2);
        // $this->assign('num1',$num1);
        $num1=$num1/100000000;
        $num2=$num2/100000000;
        $num3=$num3/100000000;
        $num4=$num4/100000000;
        $num5=$num5/100000000;
        $num6=$num6/100000000;
        $this->assign('endtime',$endtime);
        $this->assign('config',$config);
        $xdata=[$lastmonth5,$lastmonth4,$lastmonth3,$lastmonth2,$lastmonth1,$nowmonth];
        $this->assign('xdata',json_encode($xdata));
        $ydata=[$num1,$num2,$num3,$num4,$num5,$num6];
        $this->assign('ydata',json_encode($ydata));
        $this->assign('allmoney',$allmoney);
        $this->display('Index/buy_data');
    }
    /*
    卖出总额实时数据统计
    */
    public function selldata(){
        $config=$this->config;
        //$money=M('askhelp_order')->sum('amount');
        $allmoney=$config['askforhelp_number'];
        $allmoney=number_format($allmoney,2);
        $endtime=date('Y-m-d',time());
        $nowmonth=date('Y.m',time());
        $lastmonth1=date("Y.m",strtotime("-1 month"));
        $lastmonth2=date("Y.m",strtotime("-2 month"));
        $lastmonth3=date("Y.m",strtotime("-3 month"));
        $lastmonth4=date("Y.m",strtotime("-4 month"));
        $lastmonth5=date("Y.m",strtotime("-5 month"));
        //本月买入量
        $num6=M('askhelp_order')->where('DATE_FORMAT( addtime, "%Y%m" ) = DATE_FORMAT( CURDATE( ) , "%Y%m" )')->sum('amount');
        if (!$num6) {
            $num6=0;
        }
        //上一月买入量
        $num5=M('askhelp_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 1 MONTH),"%Y-%m")')->sum('amount');
        if (!$num5) {
            $num5=0;
        }
        //上上月买入量
        $num4=M('askhelp_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 2 MONTH),"%Y-%m")')->sum('amount');
        if (!$num4) {
            $num4=0;
        }
        //上上上月买入量
        $num3=M('askhelp_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 3 MONTH),"%Y-%m")')->sum('amount');
        if (!$num3) {
            $num3=0;
        }
        //上上上上月买入量
        $num2=M('askhelp_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 4 MONTH),"%Y-%m")')->sum('amount');
        if (!$num2) {
            $num2=0;
        }
        //上上上上上月买入量
        $num1=M('askhelp_order')->where('date_format(addtime,"%Y-%m")=date_format(DATE_SUB(curdate(), INTERVAL 5 MONTH),"%Y-%m")')->sum('amount');
        if (!$num1) {
            $num1=0;
        }
        // $this->assign('num6',$num6);
        // $this->assign('num5',$num5);
        // $this->assign('num4',$num4);
        // $this->assign('num3',$num3);
        // $this->assign('num2',$num2);
        // $this->assign('num1',$num1);
        $num1=$num1/100000000;
        $num2=$num2/100000000;
        $num3=$num3/100000000;
        $num4=$num4/100000000;
        $num5=$num5/100000000;
        $num6=$num6/100000000;
        $this->assign('endtime',$endtime);
        $this->assign('config',$config);
        $xdata=[$lastmonth5,$lastmonth4,$lastmonth3,$lastmonth2,$lastmonth1,$nowmonth];
        $this->assign('xdata',json_encode($xdata));
        $ydata=[$num1,$num2,$num3,$num4,$num5,$num6];
        $this->assign('ydata',json_encode($ydata));
        $this->assign('allmoney',$allmoney);
        $this->display('Index/sell_data');
    }
    /*
    提现操作
    */
    public function cashwithdrawal(){
        $userid=session('user_id');
        $cashamount=M('wallet')->where(array('user_id'=>$userid))->getField('cash_amount');
        $this->assign('cashamount',$cashamount);
        $this->display('Index/cash_withdrawal');
    }
    /*
    提现,AJAX调用
    */
    // public function cashmoney(){
    //     $userid=session('user_id');
    //     $amount=I('request.amount');
    //     $password=I('request.password');
    //     $walletinfo=M('wallet')->where(array('user_id'=>$userid))->find();
    //     $userinfo=M('user')->where(array('user_id'=>$userid))->find();
    //     if ($amount>$walletinfo['cash_amount']) {
    //         $this->ajaxReturn(['status'=>'0','message'=>'提现金额大于可提现额度']);
    //         die;
    //     }
    //     if ($userinfo['user_secpwd']!=md5($password)) {
    //         $this->ajaxReturn(['status'=>'0','message'=>'二级密码填写错误']);
    //         die;
    //     }
    //     $m=M();
    //     $m->startTrans();
    //     try{
    //         //修改用户钱包金额
    //         $result=M('wallet')->where(array('user_id'=>$userid))->setInc('static_amount',$amount);
    //         $result1=M('wallet')->where(array('user_id'=>$userid))->setDec('cash_amount',$amount);
    //         //记录钱包变动信息
    //         $data['user_id']=$userid;
    //         $data['user_name']=$userinfo['user_name'];
    //         $data['user_phone']=$userinfo['user_phone'];
    //         $data['amount']=$amount;
    //         $data['old_amount']=$walletinfo['static_amount'];
    //         $data['remain_amount']=$amount+$walletinfo['static_amount'];
    //         $data['change_date']=time();
    //         $data['log_note']="本金和利息提现到静态钱包";
    //         $data['wallet_type']='1';
    //         $result2=M('wallet_log')->add($data);
    //         $data1['user_id']=$userid;
    //         $data1['user_name']=$userinfo['user_name'];
    //         $data1['user_phone']=$userinfo['user_phone'];
    //         $data1['amount']='-'.$amount;
    //         $data1['old_amount']=$walletinfo['cash_amount'];
    //         $data1['remain_amount']=$walletinfo['cash_amount']-$amount;
    //         $data1['change_date']=time();
    //         $data1['log_note']="本金和利息提现到静态钱包";
    //         $data1['wallet_type']='5';
    //         $result3=M('wallet_log')->add($data1);
    //         $m->commit();
    //     }catch (\PDOException $e){
    //         $m->rollback();
    //     }
    //     if ($result&&$result1&&$result2&&$result3) {
    //         $this->ajaxReturn(['status'=>'1','message'=>'提现成功']);
    //     }else{
    //         $this->ajaxReturn(['status'=>'0','message'=>'提现失败']);
    //     }
    // }
    /*
    商城
    */
    public function shopinfo(){
        $list=M('ShopProject')->where(array('zt'=>'1'))->order('id desc')->select();
        $this->assign('list',$list);
        $this->display('Index/shop');
    }
    /*
    商品详情
    */
    public function shopdetails(){
        $shopid=I('request.id');
        $list=M('ShopProject')->where(array('id'=>$shopid))->find();
        $this->assign('list',$list);
        $this->display('Index/shop_details');
    }
    /*
    团队
    */
    public function Team(){
        $User = M('user');
        $user_id=$_SESSION ['user_id'];
        //用户基本信息
        $userinfo=M('user')->where(array('user_id'=>$user_id))->find();
        //获取用户VIP等级
        $push=[
            'user_parent'=>array('like',array('%'.','.$user_id,$user_id),'OR'),
        ];
        $push2=[
            //'user_parent'=>array('like','%'.$user_id.'%'),
            'user_parent'=>array('like',array($user_id.','.'%','%'.','.$user_id,'%'.','.$user_id.','.'%',$user_id),'OR'),
        ];
        $directpush=$User->where($push)->count();//直推人数
        //dump($directpush);die;
        $myteams=$User->where($push2)->count();//团队人数
        $userinfo['user_level']=getviplevel($directpush,$myteams);
        //$myteamnum=$User->where($push2)->where(array('is_active=1'))->select();//团队成员
        $myteamnum=$User->where($push)->select();//直推成员
        //dump($myteamnum);die;
        foreach ($myteamnum as &$val) {
            $push3=[
                'user_parent'=>array('like','%'.$val['user_id']),
            ];
            $push4=[
                'user_parent'=>array('like','%'.$val['user_id'].'%'),
            ];
            $val['myteams']=$User->where($push4)->count();//团队人数
            //获取用户VIP等级
            $directpush1=$User->where($push3)->count();
            $myteams1=$User->where($push4)->count();
            $val['userlist_level']=getviplevel($directpush1,$myteams1);
        }
        $this->assign('myteamnum',$myteamnum);
        $this->assign('directpush',$directpush);
        $this->assign('userinfo',$userinfo);
        $this->assign('myteams',$myteams);
        $this->display('Team/team');
    }
    /*
    推广
    */
    public function Promotion(){
        $User = M('user');
        $userid=$_SESSION ['user_id'];
        $userInfo = $User->where(array('user_id' => $userid))->find();
        if($userInfo){
            if($userInfo['user_reg_code'] == '' || empty($userInfo['user_reg_code'])){
                $userregcode = $this->qrcode($userid);
                $result=M('user')->where(array('user_id'=>$userid))->save(array('user_reg_code' => $userregcode));
            }else{
                $userregcode=$User->where(array('user_id'=>$userid))->getField('user_reg_code');
            }
            if($userInfo['user_link'] == '' || empty($userInfo['user_link'])){
                $userlink=$_SERVER['SERVER_NAME'].'/Home/Login/register/pid/'.$userid;
                $result4=M('user')->where(array('user_id'=>$userid))->save(array('user_link' => $userlink));
            }else{
                $userlink=$User->where(array('user_id'=>$userid))->getField('user_link');
            }
        }
        $this->assign('userlink',$userlink);
        $this->assign('userregcode',$userregcode);
        $this->display('Promotion/promotion');
    }

    /*
    公告
    */
    public function Announcement(){
        $News = M('news');
        $userid=$_SESSION ['user_id'];
        $announcement = $News->where('type=4')->select();
        //var_dump($announcement);die;
        $this->assign('alist',$announcement);
        $this->display('announcement/announcement');
    }
    /*
    公告详情
    */
    public function Ann_details(){
        $News = M('news');
        $ann_id=I('id');
        $condition['id'] = $ann_id;
        $details = $News->where($condition)->find();
        //var_dump($details);die;
        $this->assign('details',$details);
        $this->display('announcement/announcement-details');
    }

    /*
    申诉
    */
    public function Feedback(){
        $Feedback=M('feedback');
        $userid=$_SESSION ['user_id'];
        if(IS_POST){
            $img1 = I('request.img1');
            $img2 = I('request.img2');
            $img3 = I('request.img3');
            $title = I('request.title');
            $content = I('request.content');
            if($title == ''||$content == ''){
                $this->ajaxReturn(['status' => '0', 'message' => '请完整填写申诉信息!']);
            }else{
                $data['title']=$title;
                $data['content']=$content;
                $data['user_id']=$userid;
                $data['img1']=$img1;
                $data['img2']=$img2;
                $data['img3']=$img3;
                $data['addtime']=time();
                $result=$Feedback->where(['user_id'=>$userid])->add($data);
                if ($result) {
                    $this->ajaxReturn(['status' => '0', 'message' => '提交成功!']);
                }else{
                    $this->ajaxReturn(['status' => '1', 'message' => '提交失败!']);
                }
            }

        }
        $this->display('Appeal/appeal');
    }

    /**
     * 反馈列表
     */
    public function FeedbackList(){
        $userid=$_SESSION ['user_id'];
        $feedback= M('feedback');
        $list = $feedback->where(array('user_id' => $userid))->select();
        $this->assign('list', $list);
        $this->display('Appeal/appeal_list');
    }
    /**
     * 反馈详情
     */
    public function FeedbackDetails(){
        $id=I('id');
      //根据id查询 反馈记录 返回前段页面
        $feedback=M('feedback')->where(array('id'=>$id))->find();
        $this->assign('feedback', $feedback);
        $this->display('Appeal/details');

    }

    /*
    激活账户
    */
    public function Activation(){
        $Code = M('user_active_code');
        $userid=$_SESSION ['user_id'];
        if(IS_POST){
            $code_count = $Code->where(['user_id'=>$userid])->where(array('is_used=0'))->count();
            //var_dump($code_count);die;
            if($code_count == 0){
                $this->ajaxReturn(['status' => '0', 'message' => '请联系推荐人购买激活码!']);
            }else{
                $thedate=date('Y-m-d H:i:s',time());
                $activeuser=M('user')->where(array('user_id'=>$theid))->save(['is_active'=>1,'user_active_time'=>$thedate]);
                $canbeused=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->order('addtime asc')->find();
                $allisused=M('user_active_code')->where(array('id'=>$canbeused['id']))->setField('is_used','1');
                //修改激活状态
                $staus = M('user')->where(['user_id'=>$userid])->setField('is_active','1');
                //更新用户激活时间
                $now = time();
                $time = M('user')->where(['user_id'=>$userid])->setField('user_recomand_time',$now);
                $this->ajaxReturn(['status' =>'1', 'message' =>'用户账户激活成功!']);

            }
        }else{
            $this->display('Personal/personal');
        }
    }
    /*
    激活账户
    */
    public function savename(){
        $userid=$_SESSION ['user_id'];
        if(IS_POST){
            $nickname = I('request.nickname');
            //dump($nickname);die;
            $data['user_name'] = $nickname;
            $name = M('user')->where(['user_id'=>$userid])->save($data);
            if($name){
                $this->ajaxReturn(['status' => '1', 'message' => '修改成功!']);
            }else{
                $this->ajaxReturn(['status' => '0', 'message' => '修改失败!']);
            }
        }
    }

    /*
     * 自定义生成二维码 -xzz0815
     * 使用vender里第三方类库文件，使用GD库
     */
    public function qrcode($theid){
        $server_name = $_SERVER['SERVER_NAME'];
        $save_path = isset($_GET['save_path'])?$_GET['save_path']:'./Public/qrcode/';  //图片存储的绝对路径
        $web_path = isset($_GET['save_path'])?$_GET['web_path']:'/Public/qrcode/';        //图片在网页上显示的路径
        $qr_data = isset($_GET['qr_data'])?$_GET['qr_data']:'http://'.$server_name.'/Home/Login/register/pid/'.$theid;
        $qr_level = isset($_GET['qr_level'])?$_GET['qr_level']:'H';
        $qr_size = isset($_GET['qr_size'])?$_GET['qr_size']:'10';
        $save_prefix = isset($_GET['save_prefix'])?$_GET['save_prefix']:'ZETA';
        if($filename = createQRcode($save_path,$qr_data,$qr_level,$qr_size,$save_prefix)){
            $pic = $web_path.$filename;
        }
        return $pic;
    }





    //获取当月的第一天和最后一天
    private function getthemonth($date)
    {
        $firstday = date('Y-m-01', strtotime($date));
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
    }
    //获取星期方法
    private function   getweek($date){
        //强制转换日期格式
        $date_str=date('Y-m-d',strtotime($date));

        //封装成数组
        $arr=explode("-", $date_str);

        //参数赋值
        //年
        $year=$arr[0];

        //月，输出2位整型，不够2位右对齐
        $month=sprintf('%02d',$arr[1]);

        //日，输出2位整型，不够2位右对齐
        $day=sprintf('%02d',$arr[2]);

        //时分秒默认赋值为0；
        $hour = $minute = $second = 0;

        //转换成时间戳
        $strap = mktime($hour,$minute,$second,$month,$day,$year);

        //获取数字型星期几
        $number_wk=date("w",$strap);

        //自定义星期数组
//        $weekArr=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");

        //获取数字对应的星期
        return $number_wk;
    }

}
