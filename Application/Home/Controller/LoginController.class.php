<?php
//作者(修改): zhudeyi
//日期: 3/20/2017
//目的: 登陆注册
namespace Home\Controller;

use Think\Controller;
use Common\Controller\SendSmsController;

class LoginController extends Controller
{
    public function start(){
        $code=I('request.code');
        $this->assign('code',$code);
        $this->display('Login/login');
    }
     //平台制度
    public function system(){
        $list=M('news')->where(array('type=2'))->order('date desc')->select();
        $this->assign('list',$list);
    	$this->display('Index/system');
    }
     //新手指南
    public function novice_guide(){
        $list=M('news')->where(array('type=3'))->order('date desc')->select();
        $this->assign('list',$list);
    	$this->display('Index/novice_guide');
    }
     //联系我们
    public function contact_us(){
        $list=M('news')->where(array('type=4'))->order('date desc')->select();
        $this->assign('list',$list);
    	$this->display('Index/contact_us');
    }
    //制度详情
    public function novice_details(){
        $theid=I('request.id');
        $list=M('news')->where(['id'=>$theid])->select();
        $this->assign('list',$list);
    	$this->display('Index/novice_details');
    }
    //忘记密码
    public function forget_password(){
    	$this->display('Index/forget_password');
    }
    //首页
    public function index(){
    	$this->display('/index');
    }
    //财富币
    public function fortune(){
    	$this->display('Index/fortune');
    }
    //财务管理
    public function management(){
    	$this->display('Index/management');
    }
    //兑换中心
    public function exchange_center(){
    	$this->display('Index/exchange_center');
    }
    //会员激活
    public function user_activation(){
    	$this->display('Index/user_activation');
    }
    //会员激活详情
    public function jh_details(){
    	$this->display('Index/jh_details');
    }
    //激活码
    public function activation_code(){
    	$this->display('Index/activation_code');
    }
    //买入红酒
    public function buy_alcohol(){
    	$this->display('Index/buy_alcohol');
    }
    //卖出红酒
    public function sell_alcohol(){
    	$this->display('Index/sell_alcohol');
    }
     //抢单池
    public function robbing(){
    	$this->display('Index/robbing');
    }
     //商城
    public function shop(){
    	$this->display('Index/shop');
    }
     //商品详情
    public function shop_details(){
    	$this->display('Index/shop_details');
    }
     //新闻公告
    public function news(){
    	$this->display('Index/news');
    }
     //新闻公告详情
    public function news_details(){
    	$this->display('Index/news_details');
    }
     //预约订单
    public function reserve(){
    	$this->display('Index/reserve');
    }
      //我的
    public function usercenter(){
    	$this->display('Index/usercenter');
    }
      //个人资料
    public function personal_data(){
    	$this->display('Index/personal_data');
    }
     //修改密码
    public function change_password(){
    	$this->display('Index/change_password');
    }
     //会员注册
    public function user_register(){
    	$this->display('Index/user_register');
    }
     //推荐链接
    public function recommend(){
    	$this->display('Index/recommend');
    }
     //留言回复
    public function message(){
    	$this->display('Index/message');
    }
     //留言记录
    public function message_list(){
    	$this->display('Index/message_list');
    }
     //买入数据统计
    public function buy_data(){
    	$this->display('Index/buy_data');
    }
     //卖出数据统计
    public function sell_data(){
    	$this->display('Index/sell_data');
    }

    //登录首页
    public function login(){
        if(IS_POST){
            //字段验证
            $rules = array(
                array('user_phone','require','登录名不能为空!'),
                array('user_password','require','密码不能为空!'),
                array('user_code','require','验证码不能为空!'),
            );
            $user = M('User');
            if($user->validate($rules)->create()==false){
                $this->ajaxReturn(['status'=>'0','message'=>$user->getError()]);
                die();
            }
            if(!$this->check_verify (I('post.user_code'))){
                $this->ajaxReturn(['status' =>'0', 'message'=>'验证码错误,请刷新验证码！']);
                die();
            }
            $data = I('post.');
            //用户是否存在
            $where['user_phone'] = $data['user_phone'];
            $where['user_name'] = $data['user_phone'];
            $where['_logic'] = 'or';
            $user2 = M('User')->where($where)->find();
            if(empty($user2)){
                $this->ajaxReturn(['status'=>'0','message'=>'该用户不存在!']);
                die();
            }
            if($user2['user_status'] == 0){
                $this->ajaxReturn(['status'=>'0','message'=>'该用户已被冻结!']);
                die();
            }
//            if($user2['is_active'] == 0){
//                $this->ajaxReturn(['status'=>'0','message'=>"该用户未激活,请联系邀请人激活后方可登录!"]);
//                die();
//            }
            //密码是否正确
            if(md5($data['user_password'])!=$user2['user_password']){
                $this->ajaxReturn(['status'=>'0','message'=>'密码不正确!']);
                die();
            }

           //  //获取用户VIP等级
           //  $push=[
           //      'user_parent'=>array('like',array('%'.','.$user2['user_id'],$user2['user_id']),'OR'),
           //  ];
           //  $push2=[
           //      'user_parent'=>array('like',array($user2['user_id'].','.'%','%'.','.$user2['user_id'],'%'.','.$user2['user_id'].','.'%',$user2['user_id']),'OR'),
           //  ];
           //  $directpush=M('user')->where($push)->where(array('is_active=1'))->count();
           //  $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();
           //  $theviplecel=getviplevel($directpush,$myteams);
          	// $config=M('config')->find(1);
          	// if ($theviplecel=="VIP1") {//Vip1账户7天之内未排单,自动改为未激活状态
           //      //判断用户是否激活
           //      if($user2['is_active']==1){
           //          echo 1;die;
           //          $buyordertime=M('HelpOrder')->where(array('user_id'=>$user2['user_id'],'user_parent_id'=>'0'))->order('addtime desc')->getField('addtime');
           //          if (empty($buyordertime)) {
           //              $maxtime=strtotime($user2['user_active_time']);
           //          }else{
           //              $maxtime=max(strtotime($user2['user_active_time']),strtotime($buyordertime));
           //          } 
           //          //查询用户近七天有没有排单
           //          if ($maxtime+$config['active_limit1']*24*3600<time()) {
           //              $maxtime=date('Y-m-d H:i:s',$maxtime);
           //              $maxtime2=date('Y-m-d H:i:s',time());
           //              $where=[
           //                  'addtime'=>array('egt',$maxtime),
           //                  'addtime'=>array('elt',$maxtime2),
           //              ];
           //              if (!M('HelpOrder')->where(array('user_id'=>$user2['user_id'],'user_parent_id'=>'0'))->where($where)->find()) {
           //                  M('user')->where(array('user_id'=>$user2['user_id']))->save(['is_active'=>'0']);//设置为未激活,需要重新激活
           //              }
           //          }
           //      }else{
           //          echo 2;die;
           //      }
           //  }else{//vip2及以上
           //      //判断用户是否激活
           //      if($user2['user_status']==1){
           //          $buyordertime=M('HelpOrder')->where(array('user_id'=>$user2['user_id'],'user_parent_id'=>'0'))->order('addtime desc')->getField('addtime');
           //          if (empty($buyordertime)) {
           //              $maxtime=strtotime($user2['user_active_time']);
           //          }else{
           //              $maxtime=max(strtotime($user2['user_active_time']),strtotime($buyordertime));
           //          } 
           //          //查询用户近20天有没有排单
           //          if ($maxtime+$config['active_limit2']*24*3600<time()) {
           //              $maxtime=date('Y-m-d H:i:s',$maxtime);
           //              $maxtime2=date('Y-m-d H:i:s',time());
           //              $where=[
           //                  'addtime'=>array('egt',$maxtime),
           //                  'addtime'=>array('elt',$maxtime2),
           //              ];
           //              if (!M('HelpOrder')->where(array('user_id'=>$user2['user_id'],'user_parent_id'=>'0'))->where($where)->find()) {
           //                  M('user')->where(array('user_id'=>$user2['user_id']))->save(['user_status'=>'0']);//设置为封号,需要重解封
           //                  M('user')->where(array('user_id'=>$user2['user_id']))->save(['cold_resone'=>$config['active_limit2'].'天内未买单']);//封号原因
           //              }
           //          }
           //      }
           //  }
           
            session('user_id',$user2['user_id']);
            session('user_name',$user2['user_name']);
            session('logintime',time());//可以用于显示
            $this->ajaxReturn(['status'=>'1','message'=>'登录成功']);
        }else{
            //展示登录页面
            $this->display('Login/login');
        }
    }
    //验证码检测
    function check_verify($code){
        $verify = new \Think\Verify();
        return $verify->check($code);
    }
    //生成验证码
    function verify() {
        //ob_start();
        ob_clean();  //解决收不到验证码问题
        $config =    array(
                'fontSize'    =>    16,    // 驗證碼字體大小
                'length'      =>    5,     // 驗證碼位數
                'useCurve'    =>    false, // 關閉驗證碼雜點
        );
        $Verify = new \Think\Verify($config);
        $Verify->codeSet = '0123456789';
        $Verify->entry();
    }
    //激活用户
    // public function activeUser(){
    //     if(IS_POST){
    //         $data = I('post.');
    //         $user = M('User')->where(array('user_name'=>$data['user_name']))->find();
    //         if(!empty($user['user_parent'])){
    //             if(strpos($user['user_parent'],',')===false){
    //                 //只有一个上级
    //                 $user_parent_id = $user['user_parent'];
    //             }else{
    //                 $user_parent_arr = array_reverse(explode(',',$user['user_parent']));
    //                 $user_parent_id = $user_parent_arr[0];
    //             }
    //             //查找激活码是否正确
    //             $map['user_id'] = $user_parent_id;
    //             $map['code'] = $data['code'];
    //             $map['is_used'] = 0;
    //             $active_code = M('UserActiveCode')->where($map)->find();
    //             if(empty($active_code)){
    //                 //激活码不正确
    //                 $this->ajaxReturn(['status'=>'0','message'=>'激活码不正确!']);
    //             }else{
    //                 //激活成功,激活码失效,
    //                 $map1['user_id'] = $user_parent_id;
    //                 $map1['code'] = $data['code'];
    //                 M('UserActiveCode')->where($map)->setField('is_used',1);
    //                 M('User')->where(array('user_name'=>$data['user_name']))->setField('is_active',1);
    //                 //记录用户信息,跳转到首页
    //                 session('user_id',$user['user_id']);
    //                 session('logintime',time());
    //                 $this->ajaxReturn(['status'=>'1']);
    //             }
    //         }else{
    //             //没有上级,可以直接登录
    //             session('user_id',$user['user_id']);
    //             session('logintime',time());
    //             $this->ajaxReturn(['status'=>'1']);
    //         }
    //     }
    // }

    //前台找回密码
    public function resetPassword(){
        if(IS_POST){
            $rules = array(
                array('user_name','require','用户名不能为空!'),
                array('user_truename','require','用户真实姓名不能为空!'),
                array('user_phone','require','手机号不能为空!'),
                array('user_phone','/(^1[3|4|5|7|8|9|6][0-9]{9}$)/','手机号不正确!'),
                array('phone_code','require','短信验证码不能为空!'),
                array('password','require','新密码不能为空!'),
                array('repassword','require','确认密码不能为空!'),
                array('password', '/^[a-z0-9]{6,16}$/', '密码必须是6~16位字母,数字组合！',0,'regex'),
                array('repassword', '/^[a-z0-9]{6,16}$/', '确认密码必须是6~16位字母,数字组合！',0,'regex'),
                array('repassword','password','两次密码不一致!',0,'confirm')
            );
            $user = M('User');
            if($user->validate($rules)->create()==false){
                $this->ajaxReturn(['status'=>'0','message'=>$user->getError()]);
                die();
            }
            $data = I('post.');
            $user2 = M('User')->where(['user_name'=>$data['user_name']])->find();
            if(empty($user2)){
                $this->ajaxReturn(['status'=>'0','message'=>'该用户不存在!']);
                die();
            }
            //检测真实姓名写填是否正确
            // if ($user2['user_truename']!=$data['user_truename']) {
            //     $this->ajaxReturn(['status'=>'0','message'=>'真实姓名填写错误!']);
            //     die();
            // }
            if ($user2['user_phone']!=$data['user_phone']) {
                $this->ajaxReturn(['status'=>'0','message'=>'请填写注册手机号码!']);
                die();
            }
            //短信验证码
            $res = $this->checkSms($user2['user_phone'],$data['phone_code']);
            if ($res['res']!=true){
                $this->ajaxReturn(['status'=>'0','message'=>$res['msg']]);
                die();
            }
            if($user2['user_password']!=md5($data['password'])){
                $res = M('User')
                    ->where(['user_name'=>$data['user_name']])
                    ->setField('user_password',md5($data['password']));
                if($res){
                    $this->ajaxReturn(['status'=>'1','message'=>'修改成功!']);
                }else{
                    $this->ajaxReturn(['status'=>'0','message'=>'修改失败,请联系管理员!']);
                }
            }else{
                $this->ajaxReturn(['status'=>'1','message'=>'修改成功!']);
            }
        }else{
            $this->display('Index/forget_password');
        }
    }

    //前台退出登录
    public function logout(){
        //记录每次的退出时间
        $user=session('user_id');
        M('user')->where(array('user_id'=>$user))->save(['user_lastlogin_time'=>time()]);
        session_unset();
        session_destroy();
        $this->redirect('/Home/Login/start');
    }
    //忘记密码
    public function forgetPassword(){
        if(IS_POST){
            $rules = array(
                // array('user_name','require','用户名不能为空!'),
                // array('user_truename','require','用户真实姓名不能为空!'),
                array('user_phone','require','手机号不能为空!'),
                array('user_phone','/(^1[3|4|5|7|8|9|6][0-9]{9}$)/','手机号不正确!'),
                array('phone_code','require','短信验证码不能为空!'),
                array('password','require','新密码不能为空!'),
                array('repassword','require','确认密码不能为空!'),
                array('user_password', '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/', '密码必须是6~18位字母,数字组合！',0,'regex'),
                array('reuser_password', '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/', '确认密码必须是6~18位字母,数字组合！',0,'regex'),
                array('repassword','password','两次密码不一致!',0,'confirm')
            );
            $user = M('User');
            if($user->validate($rules)->create()==false){
                $this->ajaxReturn(['status'=>'0','message'=>$user->getError()]);
                die();
            }
            $data = I('post.');
            $user2 = M('User')->where(['user_phone'=>$data['user_phone']])->find();
            // if(empty($user2)){
            //     $this->ajaxReturn(['status'=>'0','message'=>'该用户不存在!']);
            //     die();
            // }
            //检测真实姓名写填是否正确
            // if ($user2['user_truename']!=$data['user_truename']) {
            //     $this->ajaxReturn(['status'=>'0','message'=>'真实姓名填写错误!']);
            //     die();
            // }
            if ($user2['user_phone']!=$data['user_phone']) {
                $this->ajaxReturn(['status'=>'0','message'=>'请填写注册手机号码!']);
                die();
            }
            //短信验证码
            $res = $this->checkSms($user2['user_phone'],$data['phone_code']);
            if ($res['res']!=true){
                $this->ajaxReturn(['status'=>'0','message'=>$res['msg']]);
                die();
            }
            if($user2['user_password']!=md5($data['password'])){
                $res = M('User')
                    ->where(['user_phone'=>$data['user_phone']])
                    ->setField('user_password',md5($data['password']));
                if($res){
                    $this->ajaxReturn(['status'=>'1','message'=>'修改成功!']);
                }else{
                    $this->ajaxReturn(['status'=>'0','message'=>'修改失败,请联系管理员!']);
                }
            }else{
                $this->ajaxReturn(['status'=>'1','message'=>'修改成功!']);
            }
        }else{
            $this->display('Login/forgetPassword');
        }
    }

//注册手机验证码发送
    // function yzm(){
    //     ini_set('session.gc_maxlifetime', "3600"); // 秒
    //     ini_set("session.cookie_lifetime","3600"); // 秒
    //     $phone=I('post.user_phone');
    //     $smsnum=rand(100000,999999);
    //     $_SESSION['smsnum'.$phone]=$smsnum.'zc '.time();
    //     require_once($_SERVER['DOCUMENT_ROOT'].'/../ThinkPHP/Library/Vendor/Sendsms/sendsms.php');
    //     $send=new \Sendsms();
    //     if($phone)$mes=$send->my_send($phone,"您本次注册的验证码为【".$smsnum."】,请尽快完成注册!");
    //     $aa=substr($mes,7,1);
    //     $_SESSION['smsphone']=$phone;
    //     $this->ajaxReturn($aa);
    // }
    

    //检测推荐人
    public function checkuser(){
        if(IS_POST){
            $phone = I('request.userphone');
            $truename = M('user')->where(array('user_phone'=>$phone))->getField('user_truename');
            //dump($truename);die;
            $pusername=substr_replace($truename,'*', 0,3);
            if($truename){
              $this->ajaxReturn(['status' => '1', 'message' => $pusername]);
            }else{
                $this->ajaxReturn(['status' => '0', 'message' => '']);
            }
        }
    }
    /*
    隐藏推荐人姓氏
    */
    public function hide_name($hidename){
        $restr=substr_replace($hidename,'*', 0,3);
        return $restr;
    }

    //前台注册
    public function register() {
        header("Content-type: text/html; charset=utf-8");
        $pid=I('request.pid');
        if ($pid) {
            $pname=M('user')->where(array('user_id'=>$pid))->getField('user_truename');
            $pusername=substr_replace($pname,'*', 0,3);
            $puserphone=M('user')->where(array('user_id'=>$pid))->getField('user_phone');
        }
        if(IS_POST){
            //字段验证
            $rules = array(
                // array('user_parent','require','推荐人手机号不能为空'),
//                array('user_name','require','用户名不能为空!'),
//                array('user_name','','帐号名称已经存在！',0,'unique',1),
                array('user_phone', 'require', '手机号不能为空！'),
                array('user_phone', '/(^1[3|4|5|7|8|9|6][0-9]{9}$)/', '请输入正确的手机号码！',0,'regex'),
                array('sms_code','require','短信验证码不能为空!'),
                array('user_code','require','验证码不能为空!'),
//                array('user_truename','require','真实姓名不能为空!'),
                array('user_password', 'require', '密码不能为空！'),
                array('check_user_password', 'require', '确认密码不能为空！'),
                // array('user_password', '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/', '密码必须是6~18位字母,数字组合！',0,'regex'),
                // array('reuser_password', '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/', '确认密码必须是6~18位字母,数字组合！',0,'regex'),
                array('user_password', 'check_user_password', '两次密码不一致', 0, 'confirm'), // 验证确认密码是否和密码一致
                // array('user_reg_code','require','用户邀请码不能为空!'),
                // array('user_parent', 'require', '推荐人不能为空！'),
            );
            $user = M('User');
            if($user->validate($rules)->create()==false){
                $this->ajaxReturn(['status'=>'0','message'=>$user->getError()]);
                die();
            }
            $data = I ( 'post.' );
            //手机验证码是否正确
            $res = $this->checkSms($data['user_phone'],$data['sms_code']);
            if($res['res']==false){
                $this->ajaxReturn(['status'=>'0','message'=>$res['msg']]);
            }
            //一个手机号只能注册两个账户
            $user_count = M('User')->where(array('user_phone'=>$data['user_phone']))->count();
            if($user_count > 1){
                $this->ajaxReturn(['status'=>'0','message'=>'每个手机号最多只能注册1个账户!']);
            }

            if($data['parentid'] && $data['parentid'] != ''){
                //判断推荐人是否存在,并且账号是激活状态且未封号
                $user_parent = M('User')->where(array('user_id'=>$data['parentid'],'is_active'=>1,'user_status'=>1))->find();
                if(empty($user_parent)){
                    $this->ajaxReturn(['status'=>'0','message'=>'推荐人不存在或还未激活或已被封号!']);
                }
                //获取推荐人id
                if(!empty($user_parent['user_parent'])){
                    $record['user_parent'] = $user_parent['user_parent'].','.$user_parent['user_id'];//所有上级
                }else{
                    $record['user_parent'] = $user_parent['user_id'];
                }
            }
    
            //验证激活码是否存在并正确
            // $user_reg_code = M('User')->where(array('user_reg_code'=>$data['user_reg_code']))->find();
            // if(empty($user_reg_code)){
            // 	$this->ajaxReturn(['status'=>'0','message'=>'激活码错误或激活码不存在!']);
            // }
            //开始事务
            $m = M();
            $m->startTrans();
            try
            {
                //验证通过，构建数据
                $record['user_name'] = $data['user_name'];
                $record['user_phone'] = $data['user_phone'];
                $record['user_truename'] = '';
                $record['user_password'] = md5($data['user_password']);
                // $record['user_sex']=$data['user_sex'];
                $record['user_add_time'] = time();
                $record['info_perfected']=0;//注册后个人资料还处于未完善状态
                //插入数据
                $result1 = M('User')->add($record);
                //生成推广二维码
                $theid=M('user')->where(array('user_phone'=>$data['user_phone']))->getField('user_id');
                $theqrcode['user_reg_code'] = $this->qrcode($theid);
                $result3=M('user')->where(array('user_id'=>$theid))->save($theqrcode);
                //生成推广链接
                $link['user_link']=$_SERVER['SERVER_NAME'].'/Home/Login/register/pid/'.$theid;
                $result4=M('user')->where(array('user_id'=>$theid))->save($link);
                //同步钱包,钱包表中添加用户信息
                $wallet['user_id'] = $theid;
                $wallet['addtime'] = time();
                $result2 = M('Wallet')->add($wallet);
                $m->commit();
            } catch (PDOException $exc){
                //事务回滚
                $m->rollback();
            }
            if($data['parentid'] && $data['parentid'] != ''){
                //每次注册完,自动检测推荐人会员状态,如果是静态会员就把推荐人从静态会员变为动态会员
                $this->unrefreezeChange($data['parentid']);
            }
            if ($result1 && $result2 && $result3 && $result4) {
                $this->ajaxReturn(['status'=>'1','message'=>'注册成功!','pid'=>$data['parentid']]);
            }else{
                $this->ajaxReturn(['status'=>'0','message'=>'注册失败！']);
            }
        }else{
          	$this->assign('pid',$pid);
            $this->assign('pusername',$pusername);
            $this->assign('puserphone',$puserphone);
            $this->display( 'Login/register' );
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
    //每次注册完,自动检测父亲的会员状态,如果是静态会员就把推荐人从静态会员变为动态会员
    public function unrefreezeChange($parent_user_id){
        $wallet = M('user')
            ->where(['user_id'=>$parent_user_id])
            ->find();
        if($wallet['vip_status']==1){
            M('user')->where(['user_id'=>$parent_user_id])->setField('vip_status',2);
        }
    }

    /**
     * 校验验证码
     * @param $phone
     * @param $sms_code
     * @return array
     */
    function checkSms($phone,$sms_code){

        $data = M('UserSmsCode')
            ->where(['phone'=>$phone])
            ->order('created_at desc')
            ->find();
        if (empty($data)) {
            return ['msg' => '手机验证码未发送', 'res' => false];
        }

        if (time()>($data['created_at']+300)){//有效时间300秒
            return ['msg' => '短信验证码已过期', 'res' => false];
        }

        if ($data['sms_code']!=$sms_code) {
            return ['msg' => '短信验证码不正确', 'res' => false];
        }
        return ['res' => true];
    }

    /**
     * 发送手机验证码
     */
    function sendSms(){
        $phone = I('post.user_phone');
        //验证手机号
        if (empty($phone)) {
            $this->ajaxReturn(array('status' => '0', 'message' => '手机号不能为空'));
            die();
        }
        $data = M('user');
        $where = array(['user_phone'=>I('post.user_phone')]);
        $s = $data->where($where)->count();
        if($s>=1){
            $this->ajaxReturn(['status'=>'0','message'=>'一个手机号码只能注册一个账号,请勿重复注册']);
            }
        $reg = '/^1[3456789][0-9]{9}$/';
        if (preg_match($reg, $phone) == 0) {
            $this->ajaxReturn(array('status' => '0', 'message' => '手机号不正确'));
            die();
        }
        //控制发送次数   24小时内最多发送30次验证码
        $map['created_at'] = array('EGT',time() - 86400);
        $phone_count = D('UserSmsCode')
            ->where(['phone'=>$phone])
            ->where($map)
            ->count();

        if ($phone_count > 30) {
            $this->ajaxReturn(array('status' => '0', 'message' => '发送验证码次数超限'));
            die();
        }
        //生成验证码
        $code = mt_rand(1000, 9999);
        $content = $content = '你的验证码为' . $code . ', 有效时间为5分钟';
        $data = array(
            'phone'=>$phone,
            'sms_code'=>$code,
            'created_at'=>time()
        );
        $result = M('UserSmsCode')->data($data)->add();
        if($result){
            $send_res = (new SendSmsController())->sendSms($phone, $content);
            if ($send_res) {
                $this->ajaxReturn(array('status' => '1', 'message' => '发送成功!'));
            } else {
                $this->ajaxReturn(array('status' => '0', 'message' => '发送失败!'));
            }
        }
    }

    //找回密码短信
    function sendSmsZhmm(){
        $phone = I('post.user_phone');
        //验证手机号
        if (empty($phone)) {
            $this->ajaxReturn(array('status' => '0', 'message' => '手机号不能为空'));
            die();
        }
        $reg = '/^1[3456789][0-9]{9}$/';
        if (preg_match($reg, $phone) == 0) {
            $this->ajaxReturn(array('status' => '0', 'message' => '手机号不正确'));
            die();
        }
        //控制发送次数   24小时内最多发送30次验证码
        $map['created_at'] = array('EGT',time() - 86400);
        $phone_count = D('UserSmsCode')
            ->where(['phone'=>$phone])
            ->where($map)
            ->count();

        if ($phone_count > 30) {
            $this->ajaxReturn(array('status' => '0', 'message' => '发送验证码次数超限'));
            die();
        }
        //生成验证码
        $code = mt_rand(1000, 9999);
        $content = $content = '你的验证码为' . $code . ', 有效时间为5分钟';
        $data = array(
            'phone'=>$phone,
            'sms_code'=>$code,
            'created_at'=>time()
        );
        $result = M('UserSmsCode')->data($data)->add();
        if($result){
            $send_res = (new SendSmsController())->sendSms($phone, $content);
            if ($send_res) {
                $this->ajaxReturn(array('status' => '1', 'message' => '发送成功!'));
            } else {
                $this->ajaxReturn(array('status' => '0', 'message' => '发送失败!'));
            }
        }
    }

}
