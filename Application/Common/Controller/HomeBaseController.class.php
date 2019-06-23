<?php
/**
 * Created by PhpStorm.
 * User: Adminlmcqistrator
 * Date: 2017/3/20 0020
 * Time: 09:26
 */
namespace Common\Controller;

use Think\Controller;
use Home\Logic\fishPoll\allpollshow;


//前台控制初始化
class HomeBaseController extends Controller
{
    protected $config;
    protected $user;
    protected $wallet;
    public function __empty(){
        $this->redirect('Home/Login/start');
    }

    public function __controller()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        header("Content-Type:text/html; charset=utf-8");
        if (!isset($_SESSION ['user_id'])) {
            $this -> redirect('/Home/Login/start');
        }
        $config = M('Config')->find(1);
        $this->config = $config;
        //判断系统开启状态
        if ($config['systerm_open']=='2') {//关闭时
            $this->redirect('/Home/Login/warning');
        }
        $user_id = session('user_id');
        $user = M('User')->where(['user_id'=>$user_id])->find();
        if ($user['user_status']=='0') {
            session_unset();
            session_destroy();
            $this -> redirect('/Home/Login/start/code/1');
        }
        $this->user = $user;
        $wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
        $this->wallet = $wallet;
        $this->checkAdminSession();
    }

    public function checkAdminSession()
    {
        //设置超时为10分
        $nowtime = time();
        $s_time = $_SESSION['logintime'];
        if (($nowtime - $s_time) > 6000000) {
            session_unset();
            session_destroy();
            $this -> redirect('/Home/Login/start');
        } else {
            $_SESSION['logintime'] = $nowtime;
        }
    }

    /**
     * 校验验证码
     * @param $phone
     * @param $sms_code
     * @return array
     */
    public function checkSms($phone,$sms_code){

        $data = M('UserSmsCode')
            ->where(['phone'=>$phone])
            ->order('created_at desc')
            ->find();
        if (empty($data)) {
            return ['msg' => '手机验证码未发送', 'res' => false];
        }

        if (time()>($data['created_at']+300)){
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
    function sendSms($phone){
        //验证手机号
        if (empty($phone)) {
            $this->ajaxReturn(array('status' => '0', 'message' => '手机号不能为空'));
            die();
        }
        $reg = '/^1[345678][0-9]{9}$/';
        if (preg_match($reg, $phone) == 0) {
            $this->ajaxReturn(array('status' => '0', 'message' => '手机号不正确'));
            die();
        }
        //控制发送次数
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


    /**
     * 生成图片验证码
     */
    public function imgCode(){
        $config = array(
            'expire'      =>   60,
            'fontSize'    =>    30,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
        );
        $Verify = new \Think\Verify($config);
        $Verify->codeSet = '0123456789';//设置字符集,只有数字
        $Verify->entry();
    }


}