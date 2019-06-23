<?php

namespace Adminmer\Controller;

use Think\Controller;

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
//            dump($username);
//            exit;
            $pwd = trim(I('post.password'));
           // $verCode = trim(I('post.verCode'));
            // if (!$this->check_verify($verCode))
            // {
            //     die("<script>alert('验证码错误！');history.back(-1);</script>");
            // }
            $phone=trim(I('post.phone'));
            $sms=trim(I('post.smsnum'));


            $smsnum_arr=explode(' ',$_SESSION['smsnum'.$phone]);
            if(time()-$smsnum_arr[1]>=60){
                $_SESSION['smsnum'.$phone]='';
                die("<script>alert('验证码已失效！');history.back(-1);</script>");
            }


             if(($smsnum_arr[0] <> $sms.'lg')  || ($_SESSION['smsphone'] <> $phone)){
                  $_SESSION['smsnum'.$phone]='';
                  die("<script>alert('手机号或验证码有误！');history.back(-1);</script>");
             }

            $_SESSION['smsnum'.$phone]='';
            $user = M('admin')->where(array('admin_name' => $username))->find();
            if (!$user || $user['admin_pwd'] != md5($pwd))
            {
                die("<script>alert('账号或密码错误！');history.back(-1);</script>");
            }

             if($user['tel']!=$phone){
                 die("<script>alert('管理员手机号码不匹配,禁止登陆！');history.back(-1);</script>");
             }

            session('admin_name', $user['admin_name']);
            //获取当前时间
            $_SESSION['logintime'] = time();
            //更新数据
            M('admin')->last_login = $_SESSION['logintime'];
            //将登陆时间存入数据库
            $result = M('admin')->where(array('admin_name' => $username))->save();
//            dump(M('admin')->_sql());
//            exit;
            if($result){
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
            'useCurve' => false,
            'useNoise'    =>    false, // 关闭验证码杂点
        );
        $Verify = new \Think\Verify($config);
        $Verify->codeSet = '0123456789';
        $Verify->entry();
    }
}
