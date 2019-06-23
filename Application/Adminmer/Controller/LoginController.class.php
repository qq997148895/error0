<?php

namespace Adminmer\Controller;

use Think\Controller;
use Common\Controller\SendSmsController;
/*
 * 后台登录控制器
 * Author:chenmengchen
 * Date:2017/03/28
 */

class LoginController extends Controller
{
    /*
     * 登录页面展示
     */
    public function index()
    {
        $this->display('Login/login');
    }

    /*
     * 后台登录
     */
    public function login()
    {
        header("Content-Type:text/html; charset=utf-8");
        if (IS_POST)
        {
            $username = trim(I('post.username'));
            $pwd = trim(I('post.password'));
            $verCode = trim(I('post.vercode'));
            if (!$this->check_verify($verCode))
            {
                die("<script>alert('验证码错误！');history.back(-1);</script>");
            }
            // $smsnum = trim(I('post.smsnum'));
            // $phone = trim(I('post.phone'));
            // $res = $this->checkSms($phone,$smsnum);
            // if(!$res['res']){
            //     die("<script>alert('".$res['msg']."');history.back(-1);</script>");
            // }
//            dump($username);
//            dump($pwd);
            $user = M('user')->where(array('user_phone' => $username,'user_ismerchant'=>1))->find();
//            dump($user);die;
            if (!$user || $user['user_password'] != md5($pwd))
            {
                die("<script>alert('账号或密码错误！');history.back(-1);</script>");
            }else{
                session('user_id', $user['user_id']);
                session('logintime', time());
                $this->success('登录成功','/Adminmer/index/main');
            }
        }
    }

    /*
     * 后台登出
     */
    public function logout()
    {
        session_destroy();
        $this->success('退出成功', '/Adminmer/Login');
    }

    /*
     * 检查验证码
     */
    function check_verify($code)
    {
        $verify = new \Think\Verify();
        return $verify->check($code);
    }

    /*
     * 验证码
     */
    function verify()
    {
        $config = array(
            'fontSize' => 16, // 验证码大小
            'length' => 4, // 验证码位数
            'useCurve' => false,//是否使用混淆曲线,默认是true
            'useNoise'    =>   ture, // 关闭验证码杂点
        );
        $Verify = new \Think\Verify($config);
        $Verify->codeSet = '0123456789';//指定验证码的字符为纯数字
        $Verify->entry();
    }

     /**
     * 发送手机验证码
     */
    function sendSms(){
        $phone = I('post.phone');
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
        // //控制发送次数   24小时内最多发送30次验证码
        // $map['created_at'] = array('EGT',time() - 86400);
        // $phone_count = D('UserSmsCode')
        //     ->where(['phone'=>$phone])
        //     ->where($map)
        //     ->count();

        // if ($phone_count > 30) {
        //     $this->ajaxReturn(array('status' => '0', 'message' => '发送验证码次数超限'));
        //     die();
        // }
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
     * 校验手机验证码
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
}
