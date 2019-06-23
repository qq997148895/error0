<?php

/**
 * Created by PhpStorm.
 * User: Adminlmcqistrator
 * Date: 2017/3/20 0020
 * Time: 09:26
 */

namespace Common\Controller;

use Think\Controller;

// 后台控制初始化
class AdminlmcqBaseController extends Controller
{
    protected $config;
    public function __controller()
    {
        parent::__construct();
    }
    
    public function _initialize()
    {
        header("Content-Type:text/html; charset=utf-8");
        if (!isset($_SESSION ['admin_name'])) {
            $this->success('请先登录', '/Adminlmcq/Login/index');
            die;
        }
        $this->checkAdminSession();
        $this->config = $this->config();
    }

    public function checkAdminSession()
    {
        //设置超时为10分
        $nowtime = time();
        $s_time = $_SESSION['logintime'];
        if (($nowtime - $s_time) > 6000000) {
            session_unset();
            session_destroy();
            $this->error('当前用户登录超时，请重新登录', U('/Adminlmcq/Login/index'));
        } else {
            $_SESSION['logintime'] = $nowtime;
        }
    }

    function check_verify($code)
    {
        $verify = new \Think\Verify ();
        return $verify->check($code);
    }
    //读取配置表
    public function config()
    {
       $config= M('config')->find();
       return $config;
    }




}
