<?php

/**
 * Author: zj
 * Date: 2017/3/20 0020
 */

namespace Home\ Logic;

use Think\Controller;

class Trade extends Controller
{

	    public function checkNew1($data)
    {
        //其他验证
        $rules = array(
            //array('parent', 'require', '推荐人不能为空！'),
            array('user_name', 'require', '用户名不能为空！'),
            array('user_name','','帐号名称已经存在！',0,'unique',1),
            array('user_phone', 'require', '手机号不能为空！'),
            array('user_phone', '/(^1[3|4|5|7|8|9|6][0-9]{9}$)/', '请输入正确的手机号码！', -6),
            array('user_password', 'require', '密码不能为空！'),
            array('reuser_password', 'require', '确认密码不能为空！'),
            array('user_password', '/^[a-z0-9]{6,16}$/', '密码必须是6~16位字母,数字组合！',0,'regex'),
            array('reuser_password', '/^[a-z0-9]{6,16}$/', '确认密码必须是6~16位字母,数字组合！',0,'regex'),
            array('user_password', 'reuser_password', '确认密码不正确', 0, 'confirm'), // 验证确认密码是否和密码一致
            array('user_secpwd', 'require', '二级密码不能为空！'),
            array('reuser_secpwd', 'require', '确认二级密码不能为空！'),
            array('user_secpwd', '/^[a-z0-9]{6,16}$/', '密码必须是6~16位字母,数字组合！',0,'regex'),
            array('reuser_secpwd', '/^[a-z0-9]{6,16}$/', '密码必须是6~16位字母,数字组合！',0,'regex'),
            array('user_secpwd', 'reuser_secpwd', '确认密码不正确', 0, 'confirm'), // 验证确认密码是否和密码一致
        );
        $result = D('user')->validate($rules)->create();
        if (!$result) {
            $this->error(D('user')->getError());
        }
        //验证通过，返回true
        return true;
    }

    /*
     * 构建新用户数据
     */
    public function userInfo($data)
    {

        $code_num=$this->code_num();


        $record['user_name'] = $data['user_name'];
        $record['user_phone'] = $data['user_phone'];
        $record['user_password'] = md5($data['user_pwd']);
        $record['user_secpwd'] = md5($data['user_pwd']);
        $record['user_parent'] = $data['user_parent'];
        $record['user_gender'] = $data['sex'];
        $record['user_truename'] = $data['user_truename'];
    //    $record['user_weixin'] = $data['user_weixin'];
    //    $record['user_zhifubao'] = $data['user_zhifubao'];
        $record['user_add_time'] = time();
        $record['user_regip'] = getIp();
        $record['user_reg_code'] = $code_num;
        $record['user_headimg'] = '/Public/Home/img/portait-man.png';
        return $record;
    }
    /*
     * 构建钱包数据
     */
    public function walletInfo($result1)
    {
        $wallet['fish_amount'] = 300;
        $wallet['fish_avalible'] = 300;
        $wallet['user_id'] = (int)$result1;
        return $wallet;
    }
    /*
     * 构建用户钱包日志
     */
    public function userLog($user_id)
    {
        $user_log['user_id'] = $user_id;
        $user_log['amount'] = -330;
        $user_log['change_date'] = time();
        $user_log['log_note'] = '注册新用户消耗熊猫300只';
        $user_log['type'] = '11';
        return $user_log;
    }

    /*
     * 构建目标用户钱包日志
     */
    public function targetLog($user_id)
    {
        $target_log['user_id'] = $user_id;
        $target_log['amount'] = 300;
        $target_log['change_date'] = time();
        $target_log['log_note'] = '注册获取熊猫300只';
        $target_log['type'] = '9';
        return $target_log;
    }
    /*
     * 构建会员关系数据
     */
    public function friends($user_id1,$user_id2)
    {
        $friends['user_id'] = $user_id1;
        $friends['parent_id'] = $user_id2;
        $f = D('Friends')->data($friends)->add();
        return  $f;
    }

    /**
     * 检查用户当天的出售额度
     * @param  [number] $amount [用户当前卖出数量]
     * @return [bool]
     */
    public function checkDayTrade($amount)
    {
        $user_id = $_SESSION['user_id'];
        $lands = M('land')->where(['user_id' => $user_id])->select();
        $num = 0;
        $pl=0;//普通土地
        $jl=0;//金土地
        foreach ($lands as $land) {
            if ($land['land_id']>10){
                $jl++;
            }else{
                $pl++;
            }
        }
        $num = ($pl*150)+($jl*500);
        $timezreo = strtotime(getZreoTime());
        $trade = M('trade_info')->where(['s_user_id' => $user_id])
                                ->where('create_time >'.$timezreo)
                                ->sum('amount');
        $trade = $trade ? $trade : 0;
        $num = $num - $trade;
        if ($num<=0){
            $this->ajaxReturn(['status'=>'1','message'=>'你今天的出售额度已用完']);
            die();
         //   $this->error('你今天的出售额度已用完');
        }
        if ($num - $amount < 0){
            $this->ajaxReturn(['status'=>'1','message'=>'今天的出售额度还剩 '. $num]);
            die();
        //    $this->error('今天的出售额度还剩 '. $num);
        }
    }

    /**
     * @param $userid  用户ID
     * @return mixed   返回根据用户ID获取用户钱包的信息
     * 0代表鱼卵，1代表鱼
     */
    public function check($data, $type, $user_id,$turn_lock)
    {

        $settings = include(APP_PATH . '/../Application/Common/Conf/settings.php');
        //判断是否负数
        if ($data['count'] < 0) {
            $this->ajaxReturn(['status'=>'1','message'=>'交易数量不能为负数！']);
            die();
        //    die("<script>alert('交易数量不能为负数！');history.go(-1);</script>");
        }
        //判断是否为整数
        if(!is_numeric($data['count'])||strpos($data['count'],'.')!==false){
            $this->ajaxReturn(['status'=>'1','message'=>'出售数量必须是正整数！']);
            die();
        //    die("<script>alert('出售数量必须是正整数！');history.go(-1);</script>");
        }
        //判断出售数量是否符合要求
        if ($data['count'] % $settings['min_nums_limit'] !== 0){
            $this->ajaxReturn(['status'=>'1','message'=>'出售数量必须为'.$settings['min_nums_limit'].'的倍数！']);
            die();
        //    die("<script>alert('出售数量必须为".$settings['min_nums_limit']."的倍数！');history.go(-1);</script>");
        }

        //查询用户钱包
        $info = D('wallet')->where(array('user_id' => $user_id))->find();
        //判断出售鱼还是鱼卵
        if ($type == 0) {
            $count = $info['egg_amount'];
        } else {
            $count = $info['fish_avalible'];
        }
        //如果开垦完毕不收取手续费
        $lands = M('land')->where(['user_id' => $user_id])->count();
        if($lands<15){
            if($turn_lock==1){
                if ($data['count'] > $count-($data['count']*($settings['turn_need_pre']+20)/100)) {
                    $this->ajaxReturn(['status'=>'1','message'=>'可交易数量不足！']);
                    die();
                //    die("<script>alert('可交易数量不足！');history.go(-1);</script>");
                }
            }else{
                if ($data['count'] > $count-($data['count']*$settings['turn_need_pre']/100)) {
                    $this->ajaxReturn(['status'=>'1','message'=>'可交易数量不足！']);
                    die();
                //    die("<script>alert('可交易数量不足！');history.go(-1);</script>");
                }
            }
        }else{
            if($turn_lock==1){
                if ($data['count'] > $count-($data['count']*($settings['turn_need_pre']+20)/100)) {
                    $this->ajaxReturn(['status'=>'1','message'=>'可交易数量不足！']);
                    die();
                //    die("<script>alert('可交易数量不足！');history.go(-1);</script>");
                }
            }else{
                if ($data['count'] > $count-($data['count']*$settings['turn_need_pre']/100)) {
                    $this->ajaxReturn(['status'=>'1','message'=>'可交易数量不足！']);
                    die();
                //    die("<script>alert('可交易数量不足！');history.go(-1);</script>");
                }
            }
        }
        //判断目标用户名是否存在
        $userinfo = M('user')->where(array('user_name' => $data['target_name']))->find();
        if (!$userinfo) {
            $this->ajaxReturn(['status'=>'1','message'=>'目标账号不存在！']);
            die();
        //    die("<script>alert('目标账号不存在！');history.go(-1);</script>");
        }
        //判断目标用户名与真实姓名是否相符
        if ($userinfo['user_truename'] != $data['user_truename']) {
            $this->ajaxReturn(['status'=>'1','message'=>'目标账号用户名与真实姓名不符！']);
            die();
        //    die("<script>alert('目标账号用户名与真实姓名不符！');history.go(-1);</script>");
        }
        //目标用户不能是自己
        if($userinfo['user_id'] == $_SESSION['user_id']){
            $this->ajaxReturn(['status'=>'1','message'=>'目标账号不能是自己！']);
            die();
        //    die("<script>alert('目标账号不能是自己！');history.go(-1);</script>");
        }




/*
         //验证吗是否正确
        if($data['smsnum']!=$_SESSION['smsnum']||$data['smsnum']==''||$data['phone']!=$_SESSION['smsphone']||$data['phone']==''){
            $this->ajaxReturn(['status'=>'1','message'=>'手机号或验证码错误！']);
            die();
        //    die("<script>alert('手机号或验证码错误！');history.go(-1);</script>");
       }
*/
        //当前用户名信息
        $user_info = M('user')->where(array('user_id' => $_SESSION['user_id']))->find();

        if(!empty($user_info['user_weixin'])){
            if($data['user_weixin']!=$user_info['user_weixin']){
                $this->ajaxReturn(['status'=>'1','message'=>'微信号错误！']);
                die();
            }
        }

         //二级密码是否正确
        if(MD5($data['user_secpwd'])!=$user_info['user_secpwd']){
            $this->ajaxReturn(['status'=>'1','message'=>'二级密码错误！']);
            die();
        //    die("<script>alert('二级密码错误！');history.go(-1);</script>");
       }
        return 1;
    }

    /**
     * 开通渔场（废弃？）
     * @para $user_id 用户Id
     */
    // public function activate_user($user_id)
    // {
    //     #记录用户用户渔场记录
    //     $tem = [];
    //     $tem['user_id'] = $user_id;
    //     //初始鱼卵的产量为0，鱼产量为300
    //     $tem['fish_amount'] = 300;
    //     $tem['total_egg_produce'] = 0;
    //     $res = M('land')->add($tem);
    //     #记录渔场日志
    //     //渔场的id
    //     $id = 2;
    //     $log = [];
    //     $log['user_id'] = $tem['user_id'];
    //     $log['land_id'] = $id;
    //     $log['date'] = time();
    //     //初始鱼卵的产量为0，鱼产量为300
    //     $log['egg_produce'] = 0;
    //     $log['fish_produce'] = 300;
    //     $res2 = M('land_log')->add($tem);
    //     if ($res) {
    //         $this->success('开通渔场成功');
    //     } else {
    //         $this->error('开通失败');
    //     }
    // }

    public function code_num(){
        $code_num=mt_rand(100000,999999);
        $count=M('user')->where(array('user_reg_code'=>$code_num))->count();
        if($count){
            return  $this->code_num();
        }else{
            return $code_num;
        }
    }

}
