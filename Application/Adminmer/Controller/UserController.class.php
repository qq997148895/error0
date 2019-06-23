<?php

namespace Adminmer\Controller;

use Common\Controller\AdminmerBaseController;

/*
 * 用户控制器
 */

class  UserController extends AdminmerBaseController
{
    public function toactive(){
        $theid=I('request.id');
        $userstatus=M('user')->where(array('user_id'=>$theid))->getField('is_active');
        if ($userstatus==1) {
            $this->error('账户已激活,请勿重复激活');
        }else{
            $activetime=date('Y-m-d H:i:s',time());
            if (M('user')->where(array('user_id'=>$theid))->save(['is_active'=>'1','user_active_time'=>$activetime])) {
                $this->success('账户激活成功');
            }else{
                $this->error('账户激活失败');
            }
        }
    }
    /*
    * 会员列表
    * Author:chenmengchen
    * Date:2017/03/28
    */
    public function userList()
    {
        $user_id=session('user_id');
        $User = M('user');
        $config=M('config')->find(1);
        $data = I('post.user_name');
        $map=array();
        if ($data){
            $where['user_phone']  = array('like',$data);
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
        }
        $count = $User->where($map)->count();
        $p = getpage($count, 15);

        $push2=[
            //'user_parent'=>array('like','%'.$user_id.'%'),
            'user_parent'=>array('like',array($user_id.','.'%','%'.','.$user_id,'%'.','.$user_id.','.'%',$user_id),'OR'),
        ];

        $list = $User->where($map)->where($push2)->limit($p->firstRow, $p->listRows)->select();
//        $list = $User->where($map)->limit($p->firstRow, $p->listRows)->select();
        foreach($list as $k=>$v){
            //获得用户的直接介绍人
            if(!empty($v['user_parent']) && isset($v['user_parent'])){
                $user_parents = array_reverse(explode(',',$v['user_parent']));
                $user_parent = $user_parents[0];
                $parent = D('User')->where(['user_id'=>$user_parent])->find();
                $list[$k]['user_parent'] = $parent['user_name'];
            }else{
                $list[$k]['user_parent'] = '--';
            }
            //获取用户VIP等级
            $push=[
                'user_parent'=>array('like',array('%'.','.$list[$k]['user_id'],$list[$k]['user_id']),'OR'),    //直推人数
            ];
            $push2=[
                'user_parent'=>array('like',array($list[$k]['user_id'].','.'%','%'.','.$list[$k]['user_id'],'%'.','.$list[$k]['user_id'].','.'%',$list[$k]['user_id']),'OR'),   //团队人数
            ];
            $directpush=$User->where($push)->where(array('is_active=1'))->count();
            $myteams=$User->where($push2)->where(array('is_active=1'))->count();
            $list[$k]['user_level']=getviplevel($directpush,$myteams);
            $wallet = M('wallet')->where(array('user_id' => $v['user_id']))->find();
            //添加钱包的字段
            $list[$k]['static_amount'] = $wallet['static_amount'];
            $list[$k]['change_amount'] = $wallet['change_amount'];
            //$list[$k]['exchange_amount']= $wallet['exchange_amount'];
            $list[$k]['order_byte'] = $wallet['order_byte'];
            $list[$k]['change_is_freeze'] = $wallet['change_is_freeze'];
        }
        $this->assign('page', $p->show());
        //分配数据
        $this->assign('list', $list);
        $this->display('User/user_list');
    }
    
    //扣除兑换钱包金额
    public function unuserpay(){
        $userid=I('request.id');
        if (IS_POST) {
            $theuserid=I('request.username');
            $thenumber=I('request.numbers');
            $allmoney=M('wallet')->where(array('user_id' =>$theuserid))->getField('exchange_amount');
            //检测用户输入的合法性
            if (!$thenumber) {
                $this->ajaxReturn(['status' =>2,'message' => '请输入扣除金额']);
            }elseif (!preg_match('/^([0-9]+(.[0-9]{1,2})?)$/', $thenumber)) {
                $this->ajaxReturn(['status' =>2,'message' => '扣除金额必须是数字,不能为负数,且小数点后最多两位有效数字']);
            }elseif ($thenumber>$allmoney) {
                $this->ajaxReturn(['status' =>2,'message' => '扣除金额不能大于兑换钱包余额']);
            }else{
                $allmoney=$allmoney-$thenumber;
                if (M('Wallet')->where(array('user_id'=>$theuserid))->save(['exchange_amount'=>$allmoney])) {
                    $this->ajaxReturn(['status' =>1,'message' => '兑换钱包扣除成功']);
                }else{
                    $this->ajaxReturn(['status' =>2,'message' => '兑换钱包扣除失败']);
                }
            }
        }else{
            $username=M('user')->where(array('user_id'=>$userid))->getField('user_name');
            $this->assign('userid',$userid);
            $this->assign('username',$username);
            $this->display('User/unuser_pay');
        }
    }
    //赠送兑换钱包金额
    public function userpay(){
        $userid=I('request.id');
        if (IS_POST) {
            $theuserid=I('request.username');
            $thenumber=I('request.numbers');
            $allmoney=M('wallet')->where(array('user_id' =>$theuserid))->getField('exchange_amount');
            //检测用户输入的合法性
            if (!$thenumber) {
                $this->ajaxReturn(['status' =>2,'message' => '请输入赠送金额']);
            }elseif (!preg_match('/^([0-9]+(.[0-9]{1,2})?)$/', $thenumber)) {
                $this->ajaxReturn(['status' =>2,'message' => '赠送金额必须是数字,不能为负数,且小数点后最多两位有效数字']);
            }else{
                $allmoney=$allmoney+$thenumber;
                if (M('Wallet')->where(array('user_id'=>$theuserid))->save(['exchange_amount'=>$allmoney])) {
                    $this->ajaxReturn(['status' =>1,'message' => '兑换钱包赠送成功']);
                }else{
                    $this->ajaxReturn(['status' =>2,'message' => '兑换钱包赠送失败']);
                }
            }
        }else{
            $username=M('user')->where(array('user_id'=>$userid))->getField('user_name');
            $this->assign('userid',$userid);
            $this->assign('username',$username);
            $this->display('User/user_pay');
        }
    }
    /*
     * 会员修改  
     * Author:chenmengchen
     elseif (I('request.user_password')) {//填写了登录密码,检测输入格式
                if (!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,16}$/', I('request.user_password'))) {
                    $this->error('登录密码必须是6~16位字母与数字组合!');
                }
            }elseif (I('request.user_secpwd')) {//填写了二级密码,检测输入格式
                if (!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,16}$/', I('request.user_secpwd'))) {
                    $this->error('二级密码必须是6~16位字母与数字组合!');
                }
            }
     * Date:2017/03/28
     */
    public function userEdit()
    {
        if (IS_POST)
        {
            $rules1=[
                //['user_phone','require','注册手机号不能为空'],
                ['user_reputation','require','信誉值不能为空'],
                // ['user_truename','require','真实姓名不能为空'],
                // ['user_province','require','省份地址不能为空'],
                // ['user_wechat','require','微信号不能为空'],
                // ['user_alipay','require','支付宝账号不能为空'],
                // ['user_bank','require','银行卡号不能为空'],
                // ['user_bank_kaihu','require','开户行信息不能为空'],
            ];
            $rules2=[
                ['static_amount','require','静态钱包值不能为空'],
                ['change_amount','require','动态钱包值不能为空'],
            ];
            // $result3=[
            //     ['user_bank','require','银行卡号不能为空'],
            // ];
            $result4=[
                ['ali_num','require','支付宝账号不能为空'],
            ];
            $config1=M('user');
            $config2=M('wallet');
            //$config3=M('user_idcard');
            $config4=M('user_ali_number');
            if(!$config1->validate($rules1)->create()){
                $this->error($config1->getError());
            }elseif (!$config2->validate($rules2)->create()) {
                $this->error($config2->getError());
            }elseif (!$config4->validate($rules4)->create()) {
                $this->error($config4->getError());
            }elseif (!preg_match('/^([0-9]+(.[0-9]{1,2})?)$/', I('request.static_amount'))) {
                $this->error('静态钱包金额不可为负数,且小数点后最多两位有效数字');
            }elseif (!preg_match('/^([0-9]+(.[0-9]{1,2})?)$/', I('request.change_amount'))) {
                $this->error('动态钱包金额不可为负数,且小数点后最多两位有效数字');
            }elseif (!preg_match('/^(0|[1-9][0-9]*)$/', I('request.user_reputation'))) {
                $this->error('信誉值不可为负数');
            }else{
                $user_id = I('post.user_id');
                $data['user_phone'] = I('request.user_phone');
                $theoldphone=M('user')->where(array('user_id'=>$user_id))->getField('user_phone');
                if (!empty($data['user_phone'])) {
                    if (!preg_match('/^1[3456789][0-9]{9}$/', I('request.user_phone'))) {
                        $this->error('手机号不正确');
                    }elseif ($theoldphone!=$data['user_phone']) {
                        if (M('user')->where(array('user_phone'=>$data['user_phone']))->find()) {
                            $this->error('填写的手机号已存在,请更换手机号');
                        }
                    }
                }
                $data['user_truename'] = I('request.user_truename');
                if (I('request.user_password')) {
                    $data['user_password']=md5(I('request.user_password'));
                }
                if (I('request.user_secpwd')) {
                    $data['user_secpwd']=md5(I('request.user_secpwd'));
                }
                $data['user_wechat'] = I('request.user_wechat');
                $data['user_reputation'] = I('request.user_reputation');
                $data['info_perfected'] = '1';//后台编辑时可以直接排单
                $rel['static_amount'] = I('request.static_amount');
                $rel['change_amount'] = I('request.change_amount');
                $record['ali_num'] = I('request.ali_num');
                if (!empty($record['ali_num'])) {//有值时
                    if (M('user_ali_number')->where(array('user_id'=>$user_id,'del'=>'0'))->find()) {
                        $record['add_time']=time();
                        $result=M('user_ali_number')->where(array('user_id'=>$user_id))->save($record);
                    }else{
                        $ali['name']=M('user')->where(array('user_id'=>$user_id))->getField('user_name');
                        $ali['user_id']=$user_id;
                        $ali['ali_num']=$record['ali_num'];
                        $ali['add_time']=time();
                        $result=M('user_ali_number')->add($ali);
                    }
                }
                if (M('user')->where(array('user_id' => $user_id))->save($data) || M('wallet')->where(array('user_id'=>$user_id))->save($rel) || $result){
                    $this->success('修改成功!', '', 3);
                }else{
                    $this->error('修改失败!', '', 3);
                }
            }
        }else{
            $id = I('get.user');
            $user=M('User')->where(['user_id'=>$id])->find();
            //获得用户的直接介绍人
            if(!empty($user['user_parent']) && isset($user['user_parent'])){
                $user_parents = array_reverse(explode(',',$user['user_parent']));
                $user_parent = $user_parents[0];
                $parent = D('User')->where(['user_id'=>$user_parent])->find();
                $user['user_parent'] = $parent['user_phone'];
            }else{
                $user['user_parent'] = '--';
            }
            //从钱包中获取用户的静态钱包/动态钱包/兑换钱包
            $user['static_amount']=M('wallet')->where(array('user_id'=>$id))->getField('static_amount');
            $user['change_amount']=M('wallet')->where(array('user_id'=>$id))->getField('change_amount');
            $user['ali_num']=M('user_ali_number')->where(array('user_id'=>$id))->getField('ali_num');
            $this->assign('user',$user);
            $this->display('User/user_edit');
        }
    }
    //重置支付密码
    public function resetPayPwd()
    {
        $user_id=I('get.user_id');
        if(empty($user_id)){
            $this->error('参数错误');
        }
        $is_exit=M('user')->where(['user'=>$user_id])->find();
        if(empty($is_exit)){
            $this->error('用户不存在');
        }
        M('user')->where(['user_id'=>$user_id])->save(['user_secpwd'=>md5('123456')]);
        $this->success('重置成功,新密码是123456', '',3);
    }
    /*
     * 会员添加
     * Author:chenmengchen
     * Date:2017/03/31
     */
    public function userAdd()
    {
        if(IS_POST){
            //字段验证
            $rules = array(
                array('user_name','require','会员昵称不能为空!'),
                array('user_name','','会员昵称已经存在！',0,'unique',1),
                // array('user_phone', 'require', '手机号不能为空！'),
                // array('user_phone', '/(^1[3|4|5|7|8|9|6][0-9]{9}$)/', '请输入正确的手机号码！',0,'regex'),
                array('user_truename','require','真实姓名不能为空!'),
                array('user_password', 'require', '密码不能为空！'),
                array('user_secpwd', 'require', '二级密码不能为空！'),
                array('user_password', '/^[a-z0-9]{6,16}$/', '密码必须是6~16位字母,数字组合！',0,'regex'),
                array('user_secpwd', '/^[a-z0-9]{6,16}$/', '二级密码必须是6~16位字母,数字组合！',0,'regex'),
                //array('user_password', 'check_user_password', '两次密码不一致', 0, 'confirm'), // 验证确认密码是否和密码一致
                //array('user_reg_code','require','用户邀请码不能为空!'),
                // array('user_parent', 'require', '推荐人不能为空！'),
            );
            $user = M('User');
            if($user->validate($rules)->create()==false){
                $this->error($user->getError());
            }
            $data = I ( 'post.' );
            //填写推荐人时,判断推荐人是否存在,并且账号是激活状态且未封号
            if (!empty($data['user_parent'])) {
                $user_parent = M('User')->where(array('user_phone'=>$data['user_parent'],'is_active'=>1,'user_status'=>1))->find();
                if(empty($user_parent)){
                    $this->error('推荐人不存在或还未激活或已被封号!');
                }
            }
            //开始事务
            $m = M();
            $m->startTrans();
            try
            {   
                if (empty($data['user_parent'])) {//推荐人为空时
                    $record['user_parent'] ="";
                }else{
                    //获取推荐人id
                    if(!empty($user_parent['user_parent'])){
                        $record['user_parent'] = $user_parent['user_parent'].','.$user_parent['user_id'];//所有上级
                    }else{
                        $record['user_parent'] = $user_parent['user_id'];
                    }
                }
                //验证通过，构建数据
                $record['user_name'] = $data['user_name'];
                $record['user_phone'] = $data['user_phone'];
                $record['user_truename'] = $data['user_truename'];
                $record['user_password'] = md5($data['user_password']);
                $record['user_secpwd'] = md5($data['user_secpwd']);
                $record['is_active']=1; //默认直接激活
                $record['user_active_time']=date('Y-m-d H:i:s',time());
                $record['user_add_time'] = time();
                $record['user_reputation']=$data['user_reputation'];
                $record['user_wechat']=$data['user_wechat'];
                $record['info_perfected']=0;//注册后个人资料还处于未完善状态
                //插入数据
                $result1 = M('User')->add($record);
                //生成推广二维码
                $theid=M('user')->where(array('user_name'=>$data['user_name']))->getField('user_id');
                $theqrcode['user_reg_code'] = $this->qrcode($theid);
                $result3=M('user')->where(array('user_id'=>$theid))->save($theqrcode);
                //生成推广链接
                $link['user_link']=$_SERVER['SERVER_NAME'].'/Home/Login/register/pid/'.$theid;
                $result4=M('user')->where(array('user_id'=>$theid))->save($link);
                //同步钱包,钱包表中添加用户信息
                $wallet['user_id'] = M('User')->where(array('user_name' => $data['user_name']))->getField('user_id');
                $wallet['addtime'] = time();
                $wallet['static_amount']=$data['static_amount'];
                $wallet['change_amount']=$data['change_amount'];
                $result2 = M('Wallet')->add($wallet);
                $m->commit();
            } catch (PDOException $exc){
                //事务回滚
                $m->rollback();
            }
            if ($result1 && $result2 && $result3 && $result4) {
                $this->success('添加会员成功！','/Adminmer/User/userAdd',2);
            }else{
                $this->error('添加会员失败！');
            }
        }else{
            //显示页面
            $this->display('User/user_add');
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
    /**
     * 冻结动态钱包
     */

    public function refreezeChange(){
        $user_id = I('get.user_id');
        $wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
        if($wallet['change_is_freeze']==0){
            $res = M('Wallet')->where(['user_id'=>$user_id])->setField('change_is_freeze',1);
            if($res){
                $this->success('冻结成功!');
            }else{
                $this->error('冻结失败!');
            }
        }
    }

    /**
     * 解封动态钱包
     */

    public function unrefreezeChange(){
        $user_id = I('get.user_id');
        $wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
        if($wallet['change_is_freeze']==1){
            $res = M('Wallet')->where(['user_id'=>$user_id])->setField('change_is_freeze',0);
            if($res){
                $this->success('解封成功!');
            }else{
                $this->error('解封失败!');
            }
        }
    }


    /*
     * 会员封号
     * Author:chenmengchen
     * Date:2017/03/31
     */
    public function userCold()
    {
        //获取要封号账号的id
        $user_id = I('get.user');
        //判断状态
        $status = D('Home/user')->getUserInfo($user_id)['user_status'];
        if($status == 0){
            $this->error('账号已处于冻结状态！');
        }
        $record['user_status'] = 0;
        $result = D('Home/user')->saveUserInfo($user_id,$record);
        if($result){
            $this->success('封号成功！');
        }
    }
    
    /*
     * 会员解封
     * Author:chenmengchen
     * Date:2017/03/31
     */
    public function unCold()
    {
        //获取要封号账号的id
        $user_id = I('get.user');
        //判断状态
        $status = D('Home/user')->getUserInfo($user_id)['user_status'];
        if ($status == 1) {
            $this->error('账号未处于冻结状态！');
        }
        //构建数据
//        M('user')->user_status = 0;
        $record['user_status'] = 1;
        //更新数据
        $result = D('Home/user')->saveUserInfo($user_id, $record);
        if ($result) {
            $this->success('解封成功！');
        }
    }
    
    /*
     * 会员删除
     * Author:chenmengchen
     * Date:2017/03/28
     */
    public function userDel()
    {
        //实例化user对象
        $User = M('user');
        //获取要删除账号的id
        $user_id = I('get.id');
        //获取该用户信息
        $userinfo = $User->where(array('user_id' => $user_id))->find();
        //删除账号
        if ($userinfo <> '' && $userinfo['user_id'] <> ''&& $userinfo['user_name'] <> 'admin@qq.com'){
            M('user')->where(array('user_id' => $user_id))->delete();
            $this->success('删除成功!');
        }else{
            $this->success('删除失败!'); 
        }
    }
    

    /*
     * 管理员列表
     * Author:chenmengchen
     * Date:2017/03/28
     */
    public function adminList()
    {
        //实例化对象
        $User = M('admin');
        //查询数据
        $count = $User->count();
        $p = getpage($count, 20);
        $list = $User->order('admin_id')->limit($p->firstRow, $p->listRows)->select();
        //分配数据
        $this->assign('list', $list);
        $this->assign('page', $p->show());
        //页面展示
        $this->display('User/admin_list');
    }
    
    /*
     * 添加管理员
     * Author:chenmengchen
     * Date:2017/03/28
     */
    public function adminAdd()
    {
        if(IS_POST){
            //获取提交的数据
            $data = I('post.');
            //判断数据是否为空
            if(empty($data)){
                $this->error('数据不能为空！');
            }
            //判断用户名是否已存在
            $admin_names = M('admin')->getfield('admin_name',true);
            if(in_array($data['admin_name'],$admin_names)){
                $this->error('用户名已存在！');
            }
            //判断两次密码是否相同
            if($data['admin_pwd'] != $data['confirm_pwd']){
                $this->error('两次密码不同！');
            }
            //构建数据
            $info['admin_name'] = $data['admin_name'];
            $info['tel'] = $data['tel'];
            $info['admin_pwd'] = md5($data['admin_pwd']);
            $info['create_time'] = time();
            $info['status'] = 1;
            //插入数据
            $result = M('admin')->add($info);
            if($result){
                $this->success('添加成功！','/Adminmer/User/adminlist');
            }else{
                $this->error('添加失败！');
            }
        }else{
            $this->display('admin_add');
        }
    }
    
    /*
     * 管理员修改
     * Author:chenmengchen
     * Date:2017/03/28
     */
    public function adminEdit()
    {
        if(IS_POST){
            $uid = session('user_id');
            //获取提交的数据
            $data = I('post.');
            if(empty($data['old_pwd'])){
                $this->error('原密码不能为空!');
            }
            //查询当前管理员的信息
            $info = M('user')->where(array('user_id' => $uid))->find();
            //验证原密码是否正确
            if(md5($data['old_pwd']) != $info['user_password']){
                $this->error('原密码不正确!');
            }

            if(empty($data['new_pwd']) && empty($data['confirm_pwd']) ){
                $data['new_pwd']=$data['old_pwd'];
                $data['confirm_pwd']=$data['old_pwd'];
            }
            //判断两次新密码是否相同
            if($data['new_pwd'] != $data['confirm_pwd']){
                $this->error('两次新密码输入不一致!');
            }
            //将数据插入数据库
            M('admin')->admin_pwd = md5($data['new_pwd']);
            M('admin')->tel = ($data['tel']);
            $result = M('user')->where(array('user_id' => $uid))->save(array('user_password'=>md5($data['new_pwd'])));
            if($result){
                $this->success('修改成功！','/Adminmer/User/userlist');
            }
        }else{
            //获取待修改管理员的id
            $admin_id = I('get.admin_id');
            if (!$admin_id) {
                $this->error('非法操作!');
            }
            $this->userdata = M('user')->where(array('user_id' => I('get.admin_id')))->find();
            $this->display('User/admin_edit');
        }
    }
    
    /*
     * 管理员删除
     * Author:chenmengchen
     * Date:2017/03/28
     */
    public function adminDel()
    {
        //实例化对象
        $admin = M('admin');
        //获取要删除管理员的id
        $admin_id = I('get.admin_id');
        //查询管理员信息
        $info = $admin->where(array('admin_id' => $admin_id))->find();
        //删除管理员
        if ($admin_id <> '' && $info['admin_name'] <> ''&& $info['admin_name'] <> 'admin'){
            M('admin')->where(array('admin_id' => $admin_id))->delete();
            $this->success('删除成功!', '/Adminmer/User/adminlist');
        } else {
            $this->error('删除失败!');
        }
    }

    //用户数据统计
    public function user_tongji()
    {
        //1.统计用户总数
        $nums0=0;
        $nums0=M('user')->where(1)->count('user_id');

        //统计总jhc币数
        $jhc_amount = M('Wallet w')
            ->join('mf_user u on u.user_id=w.user_id')
            ->sum('w.jhc_amount');
        $data=array(
            'nums0'=>$nums0,
            'jhc_amount'=>$jhc_amount
            );
        return $data;
    }


    /*
   * 团队关系图
   * Author:chenmengchen
   * Date:2017/03/28
   */
    public function userTeam()
    {
        if(IS_POST){
            //查询所有最高级用户
            $getuser=I('request.getuser');
            if (empty($getuser)) {
                $users = M('User')->where(['user_parent'=>''])->select();
                if($users){
                    foreach($users as $v){
                        $base = $this->getTreeBaseInfo($v['user_phone']);
                        $znote = $this->getLower($v['user_phone']);
                        $znote [] = $base;
                        $data[] = $znote;
                    }
                    $data1 = [];
                    if($data){
                        foreach($data as $val){
                            foreach($val as $value){
                                $data1[] = $value;
                            }
                        }
                    }
                    echo json_encode ( array ("status" => 0,"data" => $data1,"parameter"=>'') );
                }
            }else{
                $base = $this->getTreeBaseInfo($getuser);
                $znote = $this->getLower($getuser);
                $znote [] = $base;
                echo json_encode ( array ("status" => 0,"data" => $znote, "parameter"=>$getuser) );
            }
        }else{
            // $base = $this->getTreeBaseInfo($getuser);
            // $znote = $this->getLower($getuser);
            // $znote [] = $base;
            // echo json_encode ( array ("status" => 0,"data" => $znote, "parameter"=>$getuser) );
            //查询所有最高级用户
            $this->display('User/user_team');
        }

    }
    //会员关系图查询会员及其下级玩家信息
    public function getTree() {
        $getuser = I('request.user1');//用户名
        if(empty($getuser)){
            echo json_encode ( array ("status" => 1,"data" => '请输入会员账号' ) );
        }else{
            $base = $this->getTreeBaseInfo($getuser);
            $znote = $this->getLower($getuser);
            $znote [] = $base;
            echo json_encode ( array ("status" => 0,"data" => $znote ) );
        }
    }
    //会员列表点击团队按钮调取
    public function getTreetow(){
        $getuser = I('request.user1');//用户名
        $this->assign('getuser',$getuser);
        $this->display('User/user_team');
    }
    public function getTreeBaseInfo($id) {
        //$id是会员名称
        if (!$id)
            return ;
        //获得用户信息
        $r = M ("User")->where (['user_phone'=>$id])->find();
        //$arr = $this->countLower($id);
        $add_time = date('Y/m/d H:i:s',$r ['user_add_time']);

        if ($r){
            //获得用户pid
            $user_parent = array_reverse(explode(',',$r['user_parent']));
            $pid = $user_parent[0];
            //获得团队下猴子的数量
            $name = $r ['user_name'] . "[" .$this->sfjhff($r['user_status']).",". $r ['user_phone'] . "," . $add_time . "]";
            return array (
                "id" => $r ['user_id'],
                "pId" => $pid,
                "name" =>$name
            );
        }
        return;
    }

    public function countLower($name){
        //$name是该级别的用户名
        $user_parent = D('User')->where(['user_name'=>$name])->find();
        $pid = $user_parent['user_id'];
        //查询所有user_parent包含pid的用户即可
        $users = D('User')->select();
        if(!empty($users)){
            $data = array();
            foreach($users as $user){
                $arr = explode(',',$user['user_parent']);
                $is = in_array($pid,$arr);
                if($is){
                    $data[] = $user;
                }
            }
            $count = count($data);
            return $count+1;
        }

    }

    //获得所有子集的信息
    public function getLower($name){
        //$name是该级别的用户名
        $user_parent = D('User')->where(['user_phone'=>$name])->find();
        $pid = $user_parent['user_id'];
        //查询所有user_parent包含pid的用户即可
        $users = D('User')->select();
        if(!empty($users)){
            $data = array();
            foreach($users as $user){
                $arr = explode(',',$user['user_parent']);
                $is = in_array($pid,$arr);
                if($is){
                    $data[] = $user;
                }
            }
            $data2 = array();
            foreach($data as $v){
                $data2[] = $this->getTreeBaseInfo($v['user_phone']);
            }
            return $data2;
        }

    }

    //获得用户的状态信息
    public function sfjhff($r) {
        $a = array("封号","正常用户","未激活");
        return $a[$r];
    }

    public function code_num(){
        $code_num=mt_rand(100000,999999);
        $count=M('user')->where(array('user_reg_code'=>$code_num))->count();
        if($count){
           return  $this->code_num();
        }else{
            return $code_num;
        }
    }
    //会员管理->信息统计
    public function imformessioncount(){
        $today = date('Y-m-d', time());
        $tomorrow = date('Y-m-d',strtotime("$today +1 day"));
        $todayuser= M('user')->where(array("UNIX_TIMESTAMP('$today 00:00')<user_add_time and user_add_time<UNIX_TIMESTAMP('$tomorrow 00:00')"))->count();
        $countuser = M('user')->count();
        $tgbz_jb = M('HelpOrder')->where('order_type=0')->SUM('amount');
        $parentmoney=0;
        $parentids=M('AskhelpOrder')->where(array('order_type'=>'1'))->getField('id',true);
        for ($i=0; $i < count($parentids); $i++) { 
            if (!M('AskhelpOrder')->where(array('parent_id'=>$parentids[$i],'order_type'=>'2'))->find()) {
                $parentmoney=$parentmoney+M('askhelp_order')->where(array('id'=>$parentids[$i]))->getField('amount');
            }
        }
        $jsbz_jb = M('AskhelpOrder')->where(array('order_type'=>'2'))->SUM('amount');//子订单金额合
        $jsbz_jb = $jsbz_jb+$parentmoney;
        $this->assign("today",$today);
        $this->assign("todayuser",$todayuser);
        $this->assign("countuser",$countuser);
        $this->assign("tgbz_jb",$tgbz_jb);
        $this->assign("jsbz_jb",$jsbz_jb);
        $this->display("User/imformessioncount");
    }
    //会员管理 ->排单币管理
    public function platoon(){
        $count=M('wallet_log')->where(array('wallet_type'=>'6'))->count();
        $p=getpage($count,15);
        $list=M('wallet_log')->where(array('wallet_type'=>'6'))->limit($p->firstRow,$p->listRows)->order('change_date desc')->select();
        $this->assign('page',$p->show());
        $this->assign('list',$list);
        $this->display('User/platoon');
    }
    //排单币充值
    public function tobuy(){
        $theusername=I('request.username');
        $thenumber=I('request.number');
        $theuserid=M('user')->where(array('user_phone'=>$theusername))->getField('user_id');
        if ($theuserid) {
            $userinfo=M('user')->where(array('user_id'=>$theuserid))->find();
            $allmoney=M('wallet')->where(array('user_id' =>$theuserid))->getField('order_byte');
            //检测用户输入的合法性
            if (!$thenumber) {
                $this->ajaxReturn(['status' =>0,'message' => '请输入充值数量']);
            }elseif (!preg_match('/^[1-9]\d*$/', $thenumber)) {
                $this->ajaxReturn(['status' =>0,'message' => '充值数量必须是数字,且不能为负数']);
            }else{
                $allmoneytow=$allmoney+$thenumber;
                $result=M('Wallet')->where(array('user_id'=>$theuserid))->save(['order_byte'=>$allmoneytow]);
                //记录排单币变动信息
                $data['user_id']=$theuserid;
                $data['user_name']=$userinfo['user_name'];
                $data['user_phone']=$userinfo['user_phone'];
                $data['amount']=$thenumber;
                $data['old_amount']=$allmoney;
                $data['remain_amount']=$allmoneytow;
                $data['change_date']=time();
                $data['log_note']="后台充值排单币";
                $data['wallet_type']='6';
                $result2=M('wallet_log')->add($data);
                if ($result&&$result2) {
                    $this->ajaxReturn(['status' =>1,'message' => '排单币充值成功']);
                }else{
                    $this->ajaxReturn(['status' =>0,'message' => '排单币充值失败']);
                }
            }
        }else{
            $this->ajaxReturn(['status' =>0,'message' => '您输入的用户手机号不存在!']);
        }
    }
    //排单币扣除
    public function untopay(){
        $theusername=I('request.username');
        $thenumber=I('request.number');
        $theuserid=M('user')->where(array('user_phone'=>$theusername))->getField('user_id');
        if ($theuserid) {
            $userinfo=M('user')->where(array('user_id'=>$theuserid))->find();
            $allmoney=M('wallet')->where(array('user_id' =>$theuserid))->getField('order_byte');
            //检测用户输入的合法性
            if (!$thenumber) {
                $this->ajaxReturn(['status' =>0,'message' => '请输入扣除数量']);
            }elseif (!preg_match('/^[1-9]\d*$/', $thenumber)) {
                $this->ajaxReturn(['status' =>0,'message' => '扣除数量必须是数字,且不能为负数']);
            }elseif ($thenumber>$allmoney) {
                $this->ajaxReturn(['status' =>0,'message' => '扣除数量不能大于原始剩余数量']);
            }else{
                $allmoneytow=$allmoney-$thenumber;
                $result=M('Wallet')->where(array('user_id'=>$theuserid))->save(['order_byte'=>$allmoneytow]);
                //记录排单币变动信息
                $data['user_id']=$theuserid;
                $data['user_name']=$userinfo['user_name'];
                $data['user_phone']=$userinfo['user_phone'];
                $data['amount']='-'.$thenumber;
                $data['old_amount']=$allmoney;
                $data['remain_amount']=$allmoneytow;
                $data['change_date']=time();
                $data['log_note']="后台扣除排单币";
                $data['wallet_type']='6';
                $result2=M('wallet_log')->add($data);
                if ($result&&$result2) {
                    $this->ajaxReturn(['status' =>1,'message' => '排单币扣除成功']);
                }else{
                    $this->ajaxReturn(['status' =>0,'message' => '排单币扣除失败']);
                }
            }
        }else{
            $this->ajaxReturn(['status' =>0,'message' => '您输入的用户手机号不存在!']);
        }
    }
    //会员管理 ->激活码管理
    public function activecode(){
        $count=M('wallet_log')->where(array('wallet_type'=>'7'))->count();
        $p=getpage($count,15);
        $list=M('wallet_log')->where(array('wallet_type'=>'7'))->limit($p->firstRow,$p->listRows)->order('change_date desc')->select();
        $this->assign('page',$p->show());
        $this->assign('list',$list);
        $this->display('User/activecode');
    }
    //激活码充值
    public function activebuy(){
        $theusername=I('request.username');
        $thenumber=I('request.number');
        $theuserid=M('user')->where(array('user_phone'=>$theusername))->getField('user_id');
        if ($theuserid) {
            $userinfo=M('user')->where(array('user_id'=>$theuserid))->find();
            $oldactivesum=M('user_active_code')->where(array('user_id' =>$theuserid,'is_used'=>'0'))->count();
            //检测用户输入的合法性
            if (!$thenumber) {
                $this->ajaxReturn(['status' =>0,'message' => '请输入充值数量']);
            }elseif (!preg_match('/^[1-9]\d*$/', $thenumber)) {
                $this->ajaxReturn(['status' =>0,'message' => '充值数量必须是数字,且不能为负数']);
            }else{
                $allmoneytow=$oldactivesum+$thenumber;
                for ($i=0; $i < $thenumber; $i++) { 
                    $data1['user_id']=$userinfo['user_id'];
                    $data1['code']=$this->getrandstr(32);
                    $data1['addtime']=time();
                    $result=M('user_active_code')->data($data1)->add();
                }
                //记录排单币变动信息
                $data['user_id']=$theuserid;
                $data['user_name']=$userinfo['user_name'];
                $data['user_phone']=$userinfo['user_phone'];
                $data['amount']=$thenumber;
                $data['old_amount']=$oldactivesum;
                $data['remain_amount']=$allmoneytow;
                $data['change_date']=time();
                $data['log_note']="后台充值邀请码";
                $data['wallet_type']='7';
                $result2=M('wallet_log')->add($data);
                if ($result&&$result2) {
                    $this->ajaxReturn(['status' =>1,'message' => '邀请码充值成功']);
                }else{
                    $this->ajaxReturn(['status' =>0,'message' => '邀请码充值失败']);
                }
            }
        }else{
            $this->ajaxReturn(['status' =>0,'message' => '您输入的用户手机号不存在!']);
        }
    }
    /*
    生成开户码
    */
    function getrandstr($length){
        $str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $len=strlen($str)-1;
        $randstr="";
        for($i=0;$i<$length;$i++){
            $num=mt_rand(0,$len);
            $randstr.=$str[$num];
        }
        return $randstr;
    }
    //激活码扣除
    public function activecall(){
        $theusername=I('request.username');
        $thenumber=I('request.number');
        $theuserid=M('user')->where(array('user_phone'=>$theusername))->getField('user_id');
        if ($theuserid) {
            $userinfo=M('user')->where(array('user_id'=>$theuserid))->find();
            $oldactivesum=M('user_active_code')->where(array('user_id' =>$theuserid,'is_used'=>'0'))->count();
            //检测用户输入的合法性
            if (!$thenumber) {
                $this->ajaxReturn(['status' =>0,'message' => '请输入扣除数量']);
            }elseif (!preg_match('/^[1-9]\d*$/', $thenumber)) {
                $this->ajaxReturn(['status' =>0,'message' => '扣除数量必须是数字,且不能为负数']);
            }elseif ($thenumber>$oldactivesum) {
                $this->ajaxReturn(['status' =>0,'message' => '扣除数量不能大于原始剩余数量']);
            }else{
                $allmoneytow=$oldactivesum-$thenumber;
                for ($i=0; $i < $thenumber; $i++) { 
                    $tochange=M('user_active_code')->where(array('user_id'=>$theuserid,'is_used'=>'0'))->order('addtime asc')->find();//每次只取一行
                    $result=M('user_active_code')->where(array('id'=>$tochange['id']))->save(['is_used'=>'1']);
                }
                //记录排单币变动信息
                $data['user_id']=$theuserid;
                $data['user_name']=$userinfo['user_name'];
                $data['user_phone']=$userinfo['user_phone'];
                $data['amount']='-'.$thenumber;
                $data['old_amount']=$oldactivesum;
                $data['remain_amount']=$allmoneytow;
                $data['change_date']=time();
                $data['log_note']="后台扣除邀请码";
                $data['wallet_type']='7';
                $result2=M('wallet_log')->add($data);
                if ($result&&$result2) {
                    $this->ajaxReturn(['status' =>1,'message' => '邀请码扣除成功']);
                }else{
                    $this->ajaxReturn(['status' =>0,'message' => '邀请码扣除失败']);
                }
            }
        }else{
            $this->ajaxReturn(['status' =>0,'message' => '您输入的用户手机号不存在!']);
        }
    }
}