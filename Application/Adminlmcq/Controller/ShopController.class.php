<?php

namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;

/*
 * 商城控制器
 */

class  ShopController extends AdminlmcqBaseController
{

    // 商城后台首页（分类列表）
    public function index(){
        $model = M('shop_leibie');
        $list = $model->page($_GET['p'],10)->select();
        $this->assign("list",$list);
        $count = $model->count();
        $page = new \Think\Page($count,10);
        $show = $page->show();
        $openstate=M('config')->where('id=1')->getField('shop_openclose');
        $this->assign('openstate',$openstate);
        $this->assign("page",$show);
        $this->display();
    }
    //商城状态修改
    function shopopen(){
        if(IS_POST){
            $model=M('config');
            $data['shop_openclose'] = I("post.openstate");
            if(empty($data['shop_openclose'])){
                $this->ajaxReturn("参数值获取失败！");
            }
            if (M('config')->where('id=1')->save($data)) {
                if ($data['shop_openclose']==2) {
                    $this->ajaxReturn("商城关闭成功");
                }else{
                    $this->ajaxReturn("商城开启成功");
                }
            }else{
                $this->ajaxReturn("商城状态修改失败");
            }
        }
    }
    //添加类别名称
    function toadd(){
        if(IS_POST){
            $model=M('shop_leibie');
            $data['name'] = I("post.lassname");
            if(empty($data['name'])){
                $this->ajaxReturn("请输入addProject类别名称！");
            }
            if ($model->where(array("name"=>$data['name']))->find()) {
                $this->ajaxReturn("类别已存在！");
            }
            $data['addtime'] = time();
            $re = $model->add($data);
            $this->ajaxReturn("1");
        }
    }
    //修改分类
    function edit(){
        if(IS_POST){
            $model = M("shop_leibie");
            $data['name'] = I("post.name");
            if(empty($data['name'])){
                $this->ajaxReturn("请输入类别名称！");
            }
            if($model->where(array("name"=>$data['name']))->find()){
                $this->ajaxReturn("类别已存在！");
            }
            $id = I("post.id");
            $re = $model->where(array("id"=>$id))->save($data);
            $this->ajaxReturn("1");
        }
    }
    //删除分类
    function del(){
        if(IS_POST){
            $id = I("id");
            if(!empty($id)){
                $model = M("shop_leibie");
                $re = $model->where(array("id"=>$id))->delete();
                if($re){
                    $this->ajaxReturn("1");
                }else{
                    $this->ajaxReturn("删除失败！");   
                }

            }
        }
    }
    //添加商品
    public function addProject()
    {
        $list = M('shop_leibie')->select();
        $this->assign("ssid", session_id());
        $this->assign('list', $list);
        $this->display();
    }
    //添加商品处理
    public function saveProject()
    {
        $uploads = new \Think\Upload();// 实例化上传类
        $uploads->maxSize = 31457280;// 设置附件上传大小
        $uploads->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Uploads/'; // 设置附件上传根目录
        $uploads->savePath = '/Pic/'; // 设置附件上传目录

        // 上传文件
        $info = $uploads->uploadOne($_FILES['imagepath1']);
        if (!$info) {// 上传错误提示错误信息
            $this->error($uploads->getError());
        } else {// 上传成功 获取上传文件信息
            $_POST['imagepath1'] = '/Uploads' . $info['savepath'] . $info['savename'];
        }

        //详情图片
        $info = $uploads->uploadOne($_FILES['image_declare1']);
        if (!$info) {// 上传错误提示错误信息
            $this->error($uploads->getError());
        } else {// 上传成功 获取上传文件信息
            $_POST['image_declare1'] = '/Uploads' . $info['savepath'] . $info['savename'];
        }
        $info = $uploads->uploadOne($_FILES['image_declare2']);
        if (!$info) {// 上传错误提示错误信息
            $this->error($uploads->getError());
        } else {// 上传成功 获取上传文件信息
            $_POST['image_declare2'] = '/Uploads' . $info['savepath'] . $info['savename'];
        }
        $info = $uploads->uploadOne($_FILES['image_declare3']);
        if (!$info) {// 上传错误提示错误信息
            $this->error($uploads->getError());
        } else {// 上传成功 获取上传文件信息
            $_POST['image_declare3'] = '/Uploads' . $info['savepath'] . $info['savename'];
        }if ($_POST['goods_price']<1000){
        $this->error('商品价格必须大于1000且是整数！');
    }

        $_POST['addtime'] = time();
        $_POST['status'] = 0;
        $_POST['user_id'] = session('admin_id');
        $_POST['goods_id'] = $this->orderNum();
        $res = M('goods')->data($_POST)->add();
        if ($res) {
            $this->success('商品添加成功');
        } else {
            $this->error($res);
        }
    }

    //产生一个10位的 不重复商品单号
    public function orderNum()
    {
        //组合一个10位的字符串
        $order_num = date('His') . rand(1000, 9999);
        $condition = M('goods')->where('goods_id=' . $order_num)->find();
        if ($condition) {
            return $this->orderNum();
        } else {
            return $order_num;
        }
    }

    //产品列表
    public function listProject()
    {
        $uid = session('user_id');
//        dump($uid);die;
        $goods = M('goods')->where(array('isadmin'=>array('GT','0')))->page($_GET['p'], 12)->order('addtime DESC')->select();
        foreach ($goods as $k) {
            if ($k['imagepath1']) {
                $k['image1'] = 1;
            } else {
                $k['image1'] = 0;
            }
        }
        $page = new \Think\Page($count, 12);
        $show = $page->show();
        //var_dump($show);die;
        $this->assign("goods", $goods);
        $this->assign("page", $show);
        $this->display('listProject');
    }
    //订单列表
    public function listOrderform(){
        $model = M('shop_orderform');
        $list=[];
        $map=[
            'zt'=>array('in',[0,1,2]),
            'is_del'=>0,
        ];
        $re = $model->where($map)->page($_GET['p'],12)->order('id DESC')->select();
        foreach ($re as $v){
            $goods=M('goods')->where(array('id'=>$v['project_id']))->find();
            if ($goods['isadmin']==1){
                array_push($list,$v);
            }
        }
        $count=count($list);
//        $count = $model->where($map)->count();
        $page = new \Think\Page($count,12);
        $show = $page->show();
        foreach($list as &$v){
            $v['username'] = M('user')->where(array('user_id'=>$v['user']))->getField('user_name');
            $v['phone'] = M('user')->where(array('user_id'=>$v['user']))->getField('user_phone');
        }
    //条件根据订单的商品id  查询商品是不是isadmin（显示isadmin）
        $this->assign("page",$show);
        $this->assign('list',$list);
        $this->display();
    }
    //产品详情
    public function project()
    {
        $id = I('get.id');
        $goods = M('goods')->where(array('id' => $id))->find();
        $this->assign('arr', $goods);
        $this->display('project');
    }
    //产品update
    public function updateProject()
    {
        $id = I('get.id');
        if ($_FILES['imagepath1']['name']) {
            $uploads = new \Think\Upload();// 实例化上传类
            $uploads->maxSize = 3145728;// 设置附件上传大小
            $uploads->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $uploads->savePath = '/Pic/'; // 设置附件上传目录
            // 上传文件
            $info = $uploads->uploadOne($_FILES['imagepath1']);
            if (!$info) {// 上传错误提示错误信息
                $this->error($uploads->getError());
            } else {// 上传成功 获取上传文件信息
                $_POST['imagepath1'] = '/Uploads' . $info['savepath'] . $info['savename'];
            }
        }
        if(!$_POST['isadmin']){
            $_POST['isadmin'] = 0;
        }
        $res = M('goods')->where(array('id' => $id))->save($_POST);
        if ($res) {
            $this->success('商品修改成功！', '/Adminmer/Shop/listProject', 3);
        } else {
            $this->error('商品修改失败！');
        }
    }

    //产品删除
    public function delProject(){
        $data = I('post.');
        $re = M('Project')->delProject($data);
        if($re){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }

    //修改产品状态
    public function ztProject(){
        $id = I('get.id');
        $zt = I('get.zt');
        if($zt == 0){
            D('Project')->ztProject($id,$zt);
        }elseif($zt == 1){
            D('Project')->ztProject($id,$zt);
        }elseif($zt == 2){
            D('Project')->ztProject($id,$zt);
        }else{
            alert('提交数据zt状态出错');
        }
        $this->redirect('listProject');
    }

    //删除产品订单
    public function delOrderform(){
        $data = I('post.');
        $re = M('shop_orderform')->where($data)->save(['is_del'=>1]);
        if($re){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }

    //修改发货状态
    public function delivery(){
        $id = I('post.id');
        $result = M('shop_orderform')->where(array('id'=>$id))->getField('zt');
        if($result == '0'){
            $re = M('shop_orderform')->where(array('id'=>$id))->save(array('zt'=>'1'));
        }elseif($result == '1'){
            $re = M('shop_orderform')->where(array('id'=>$id))->save(array('zt'=>'0'));
        }
        if($re){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }
    /*
    确认发货
    */
    public function fahuook(){
        $data = I('post.id');
        $re = M('shop_orderform')->where(array('id'=>$data))->save(array('zt'=>'1'));
        if($re){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }
    // 取消订单
    /*
    表 shop_orderfrom
    字段
        id          主键
        user        user_id
        user_phone  收货人电话
        user_name   收获人名称
        project     商品名称
        count      购买数量
        sumprice    单价
        addtime     购买时间
        zt          状态
        address     收货地址
        note        备注

    */
    public function editOrderform(){
        
        $data['id'] = I('get.id');
        $order = M('shopOrderform');
        $re = $order->where($data)->find();
        // dump($re);die;
        
        $m = M();
        $m->startTrans();
        // 修改订单状态为订单取消
        if($re['zt'] == 3){
            $this->error('我早就取消了！');
        }
        if($re['zt'] == 0){
            $orderzt['zt'] = 3;//订单取消
            $re1 = M('shop_orderform')->where($data)->setField($orderzt);
            // echo M('shop_orderform')->_sql();die;
            // dump($re1);die;
        }else{
            $this->error('已发货，不可取消！');
        }
        
        // 向用户钱包中增加熊猫
        $userwallet['user_id'] = $re['user'];//用户id
        $userwallet['fish_amount'] = $re['count']*$re['sumprice']+$re['count']*$re['sumprice']/10;
        $userwallet['fish_avalible'] = $re['count']*$re['sumprice']+$re['count']*$re['sumprice']/10;
        $wallet = M('wallet');
        $re2 = $wallet->where(array('user_id'=>$userwallet['user_id']))->setInc('fish_amount',$userwallet['fish_amount']);
        $re3 = $wallet->where(array('user_id'=>$userwallet['user_id']))->setInc('fish_avalible',$userwallet['fish_avalible']);
// dump($re2);die;
// dump($re3);die;
        // 记录日志

        $wallet_log['user_id'] = $re['user'];
        $wallet_log['amount'] = $userwallet['fish_amount'];
        $wallet_log['change_date'] = time();
        $wallet_log['log_note'] = "平台取消订单，退熊猫".$userwallet['fish_amount']."只";
        $wallet_log['type'] = 8;
        $log = M('wallet_log');
        $re4 = $log->add($wallet_log);
        if($re1 && $re2 && $re3 && $re4 !== false){

            $m->commit();
            $this->success('操作成功！');
        }else{
            $m->rollback();
            $this->error('操作失败！');
        }

    }

    function show_mes($data){
        if($data){
                $this->success("操作成功！");
            }else{
                $this->error("操作失败！");
            }
    }
    
    function ajax_mes($data){
        if($data){
                $this->ajaxReturn("1");
            }else{
                $this->ajaxReturn("0");
            }
    }

    //4.8.3商家三级分销系统；一代10%，二代3%（推荐2个人），三代5%（推荐5个人），推荐十个人升级股东无限代1%。
    //（商家在后台为每一个商品添加不同的分销比例）（总后台可以控制消耗的股权值和商家后台可以控制三级分销系统的返利比例）
    //4.8.4商家有独立后台可以设置（奖金制度（每一个商品都可以单独设置制度）
    //（主要设置每一个商品的分销比例，推荐的下级买哪一个商品就按照该商品设置的分销比例进行返利），上传商品，修改商品价格数量等参数，查看会员但不能修改会员信息）
    //（奖金制度，商品信息（名称，图片，价格，数量等）商家后台可以控制）
    //4.8.5商品买卖流程：选择商家进入购买产品-（产品价位1000-20000）选择商品例如一千元的商品，
    //选择商品之后，跳转付款页面，商家收款方式支付宝转账，会员转账之后上传汇款凭证，商家确认，然后购买商品成功，
    //（赠送股权增值卷比例按照购买商品的金额的50%赠送，例：一万元的商品赠送50张，两万元的商品赠送100张）
    //（产品价位总后台可以控制，总后台修改之后可以对商家后台的商品价位进行控制）
    //确认付款
    //判断有多少代   比例按照商品的比例 如果没有设置比例 按照参数比例（积分钱包）  购买成功之后  赠送买家增值券
    function receipt(){
        $uid = session('user_id');
        $data = I('post.id');//订单id
        $order = M('shop_orderform')->where(array('id' => $data))->find(); //订单
        $goods = M('goods')->where(array('id' => $order['project_id']))->find(); //商品
        $merchant = M('user')->where(array('user_id'=>$goods['user']))->find(); //商家
        $buyer = M('user')->where(array('user_id'=>$order['user']))->find(); //买家
        $config = M('config')->where(array('id'=>1))->find(); //股权增值权 配置表

        $allparentid = M('user')->where(array('user_id' =>$buyer['user_id'] ))->getField('user_parent');//返回一个数组
        $allparent = array_reverse(explode(',', $allparentid));//以相反的顺序返回数组
        $oneparent = $allparent[0];//一代
        $towparent = $allparent[1];//二代
        $threeparent = $allparent[2];//三代
        $fourparent = $allparent[3];//四代
        //判断商品有没有设置自己的比例  有按照自己的比例  没有按照默认比例
        $one=$goods['one'];
        $two=$goods['two'];
        $three=$goods['three'];
        $m = M();
        $m->startTrans();
        try{

            if ($one!=0&&$two!=0&&$three!=0){//有自己的比例
                //然后判断  一代  二代  三代   四代是否存在
                if ($oneparent){//当一代存在时  返$one%
                    $onewallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                    //增加积分钱包
                    $news['change_amount']=$onewallet['change_amount']+$order['sumprice']*($one/100);
                    $res = M('wallet')->where(array('user_id'=>$oneparent))->save($news);
                    $user = M('user')->where(array('user_id'=>$oneparent))->find();
                    if($res){
                        $wallet_log=M('wallet_log');
                        $wallet_log_info['user_id']=$oneparent;//用户id
                        $wallet_log_info['user_name']=$user['user_name'];//用户名
                        $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                        $wallet_log_info['amount']='+'.$order['sumprice']*($one/100);//资金变动数量
                        $wallet_log_info['old_amount']=$onewallet['change_amount'];//原来余额
                        $wallet_log_info['remain_amount']=$onewallet['change_amount']+$order['sumprice']*($one/100);//现在余额  （原来余额+ 资金变动）
                        $wallet_log_info['change_date']=time();//变动时间
                        $wallet_log_info['log_note']='购买商品一代奖励';//信息描述
                        $wallet_log_info['wallet_type']=2;//变动类型  积分
                        $wallet_log->add($wallet_log_info);
                    }
                }
                if ($towparent){//当二代存在时  返$two%  (邀请2个成为二级代理)
                    $push = [ 'user_parent' => array('like', array('%' . ',' . $towparent, $towparent), 'OR')];   //直推人数
                    $directpush = M('user')->where($push)->where(array('is_active'=>1))->count();//直推
                    if ($directpush>=2){
                        $twowallet=M('wallet')->where(array('user_id'=>$towparent))->find();
                        //增加积分钱包
                        $news['change_amount']=$twowallet['change_amount']+$order['sumprice']*($two/100);
                        $res = M('wallet')->where(array('user_id'=>$towparent))->save($news);
                        $user = M('user')->where(array('user_id'=>$towparent))->find();
                        if($res){
                            $wallet_log=M('wallet_log');
                            $wallet_log_info['user_id']=$towparent;//用户id
                            $wallet_log_info['user_name']=$user['user_name'];//用户名
                            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                            $wallet_log_info['amount']='+'.$order['sumprice']*($two/100);//资金变动数量
                            $wallet_log_info['old_amount']=$twowallet['change_amount'];//原来余额
                            $wallet_log_info['remain_amount']=$twowallet['change_amount']+$order['sumprice']*($two/100);//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date']=time();//变动时间
                            $wallet_log_info['log_note']='购买商品二代奖励';//信息描述
                            $wallet_log_info['wallet_type']=2;//变动类型  积分
                            $wallet_log->add($wallet_log_info);
                        }
                    }
                }
                if ($threeparent){//当三代层在时 返$three% （邀请5个成为三级代理）
                    $push = [ 'user_parent' => array('like', array('%' . ',' . $threeparent, $threeparent), 'OR')];   //直推人数
                    $directpush = M('user')->where($push)->where(array('is_active'=>1))->count();//直推
                    if ($directpush>=5){
                        $threewallet=M('wallet')->where(array('user_id'=>$threeparent))->find();
                        //增加积分钱包
                        $news['change_amount']=$threewallet['change_amount']+$order['sumprice']*($three/100);
                        $res = M('wallet')->where(array('user_id'=>$threeparent))->save($news);
                        $user = M('user')->where(array('user_id'=>$threeparent))->find();
                        if($res){
                            $wallet_log=M('wallet_log');
                            $wallet_log_info['user_id']=$threeparent;//用户id
                            $wallet_log_info['user_name']=$user['user_name'];//用户名
                            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                            $wallet_log_info['amount']='+'.$order['sumprice']*($three/100);//资金变动数量
                            $wallet_log_info['old_amount']=$threewallet['change_amount'];//原来余额
                            $wallet_log_info['remain_amount']=$threewallet['change_amount']+$order['sumprice']*($three/100);//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date']=time();//变动时间
                            $wallet_log_info['log_note']='购买商品三代奖励';//信息描述
                            $wallet_log_info['wallet_type']=2;//变动类型  积分
                            $wallet_log->add($wallet_log_info);
                        }
                    }
                }
                if ($fourparent){//当四代存在时 返1%  （邀请10个人 成为无限代）
                    $push = [ 'user_parent' => array('like', array('%' . ',' . $fourparent, $fourparent), 'OR')];   //直推人数
                    $directpush = M('user')->where($push)->where(array('is_active'=>1))->count();//直推
                    if ($directpush>=10){
                        $fourwallet=M('wallet')->where(array('user_id'=>$fourparent))->find();
                        //增加积分钱包
                        $news['change_amount']=$fourwallet['change_amount']+$order['sumprice']*0.01;
                        $res = M('wallet')->where(array('user_id'=>$fourparent))->save($news);
                        $user = M('user')->where(array('user_id'=>$fourparent))->find();
                        if($res){
                            $wallet_log=M('wallet_log');
                            $wallet_log_info['user_id']=$fourparent;//用户id
                            $wallet_log_info['user_name']=$user['user_name'];//用户名
                            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                            $wallet_log_info['amount']='+'.$order['sumprice']*$config['infinite']/100;//资金变动数量
                            $wallet_log_info['old_amount']=$fourwallet['change_amount'];//原来余额
                            $wallet_log_info['remain_amount']=$fourwallet['change_amount']+$order['sumprice']*$config['infinite']/100;//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date']=time();//变动时间
                            $wallet_log_info['log_note']='购买商品无限代奖励';//信息描述
                            $wallet_log_info['wallet_type']=2;//变动类型  积分
                            $wallet_log->add($wallet_log_info);
                        }
                    }
                }

            }
            else{//默认比例

                $mer_config= M('mer_config')->where(array('user_id' => $goods['user_id']))->find();
                $one=$mer_config['reward_rate1'];
                $two=$mer_config['reward_rate2'];
                $three=$mer_config['reward_rate3'];

                //然后判断  一代  二代  三代   四代是否存在
                if ($oneparent){//当一代存在时  返$one%
                    if ($oneparent){//当一代存在时  返$one%
                        $onewallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                        //增加积分钱包
                        $news['change_amount']=$onewallet['change_amount']+$order['sumprice']*($one/100);
                        $res=M('wallet')->where(array('user_id'=>$oneparent))->save($news);
                        $user = M('user')->where(array('user_id'=>$oneparent))->find();
                        if($res){
                            $wallet_log=M('wallet_log');
                            $wallet_log_info['user_id']=$oneparent;//用户id
                            $wallet_log_info['user_name']=$user['user_name'];//用户名
                            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                            $wallet_log_info['amount']='+'.$order['sumprice']*($one/100);//资金变动数量
                            $wallet_log_info['old_amount']=$onewallet['change_amount'];//原来余额
                            $wallet_log_info['remain_amount']=$onewallet['change_amount']+$order['sumprice']*($one/100);//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date']=time();//变动时间
                            $wallet_log_info['log_note']='购买商品一代奖励';//信息描述
                            $wallet_log_info['wallet_type']=2;//变动类型  积分
                            $wallet_log->add($wallet_log_info);
                        }
                    }
                }
                if ($towparent){//当二代存在时  返$two%  (邀请2个成为二级代理)
                    $push = [ 'user_parent' => array('like', array('%' . ',' . $towparent, $towparent), 'OR')];   //直推人数
                    $directpush = M('user')->where($push)->where(array('is_active'=>1))->count();//直推
                    if ($directpush>=2){
                        $twowallet=M('wallet')->where(array('user_id'=>$towparent))->find();
                        //增加积分钱包
                        $news['change_amount']=$twowallet['change_amount']+$order['sumprice']*($two/100);
                        $res = M('wallet')->where(array('user_id'=>$towparent))->save($news);
                        $user = M('user')->where(array('user_id'=>$towparent))->find();
                        if($res){
                            $wallet_log=M('wallet_log');
                            $wallet_log_info['user_id']=$towparent;//用户id
                            $wallet_log_info['user_name']=$user['user_name'];//用户名
                            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                            $wallet_log_info['amount']='+'.$order['sumprice']*($two/100);//资金变动数量
                            $wallet_log_info['old_amount']=$twowallet['change_amount'];//原来余额
                            $wallet_log_info['remain_amount']=$twowallet['change_amount']+$order['sumprice']*($two/100);//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date']=time();//变动时间
                            $wallet_log_info['log_note']='购买商品二代奖励';//信息描述
                            $wallet_log_info['wallet_type']=2;//变动类型  积分
                            $wallet_log->add($wallet_log_info);
                        }
                    }
                }
                if ($threeparent){//当三代层在时 返$three% （邀请5个成为三级代理）
                    $push = [ 'user_parent' => array('like', array('%' . ',' . $threeparent, $threeparent), 'OR')];   //直推人数
                    $directpush = M('user')->where($push)->where(array('is_active'=>1))->count();//直推
                    if ($directpush>=5){
                        $threewallet=M('wallet')->where(array('user_id'=>$threeparent))->find();
                        //增加积分钱包
                        $news['change_amount']=$threewallet['change_amount']+$order['sumprice']*($three/100);
                        $res = M('wallet')->where(array('user_id'=>$threeparent))->save($news);
                        $user = M('user')->where(array('user_id'=>$threeparent))->find();
                        if($res){
                            $wallet_log=M('wallet_log');
                            $wallet_log_info['user_id']=$threeparent;//用户id
                            $wallet_log_info['user_name']=$user['user_name'];//用户名
                            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                            $wallet_log_info['amount']='+'.$order['sumprice']*($three/100);//资金变动数量
                            $wallet_log_info['old_amount']=$threewallet['change_amount'];//原来余额
                            $wallet_log_info['remain_amount']=$threewallet['change_amount']+$order['sumprice']*($three/100);//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date']=time();//变动时间
                            $wallet_log_info['log_note']='购买商品三代奖励';//信息描述
                            $wallet_log_info['wallet_type']=2;//变动类型  积分
                            $wallet_log->add($wallet_log_info);
                        }
                    }

                }
                if ($fourparent){//当四代存在时 返1%  （邀请10个人 成为无限代）
                    $push = [ 'user_parent' => array('like', array('%' . ',' . $fourparent, $fourparent), 'OR')];   //直推人数
                    $directpush = M('user')->where($push)->where(array('is_active'=>1))->count();//直推
                    if ($directpush>=10){
                        $fourwallet=M('wallet')->where(array('user_id'=>$fourparent))->find();
                        //增加积分钱包
                        $news['change_amount']=$fourwallet['change_amount']+$order['sumprice']*0.01;
                        $res = M('wallet')->where(array('user_id'=>$fourparent))->save($news);
                        $user = M('user')->where(array('user_id'=>$threeparent))->find();
                        if($res){
                            $wallet_log=M('wallet_log');
                            $wallet_log_info['user_id']=$fourparent;//用户id
                            $wallet_log_info['user_name']=$user['user_name'];//用户名
                            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
                            $wallet_log_info['amount']='+'.$order['sumprice']*$config['infinite']/100;//资金变动数量
                            $wallet_log_info['old_amount']=$fourwallet['change_amount'];//原来余额
                            $wallet_log_info['remain_amount']=$fourwallet['change_amount']+$order['sumprice']*$config['infinite']/100;//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date']=time();//变动时间
                            $wallet_log_info['log_note']='购买商品无限代奖励';//信息描述
                            $wallet_log_info['wallet_type']=2;//变动类型  积分
                            $wallet_log->add($wallet_log_info);
                        }
                    }
                }
            }
            $buyerwallet=M('wallet')->where(array('user_id'=>$buyer['user_id']))->find();
            //送增值券(总金额/股权增值权价值*0.5)paidan_price  送买家
//            $newOrderByte=$buyerwallet['order_byte'] + $order['sumprice'] / $config['paidan_price'] / 2;
            $song = intval($order['sumprice'] / $config['paidan_price'] / 2);
            $newOrderByte=$buyerwallet['order_byte'] + $song;
            $reswallet = M('wallet')->where(array('user_id'=>$buyer['user_id']))->data(array('order_byte'=>$newOrderByte))->save();
            if($reswallet){
//                记录静态钱包变动信息
                $add['user_id']=$buyer['user_id'];
                $add['user_name']=$buyer['user_name'];
                $add['user_phone']=$buyer['user_phone'];
                $add['amount']='+'.$song;
                $add['old_amount']=$buyerwallet['order_byte'];
                $add['remain_amount']=$newOrderByte;
                $add['change_date']=time();
                $add['log_note']="买商品赠送股权增值券";
                $add['wallet_type']="4";
                M('wallet_log')->add($add);
            }
            $res = M('shop_orderform')->where(array('id'=>$order['id']))->data(array('zt'=>2))->save();
            if(!$res){
                $m->rollback();
                echo 0;
            }
            //判断用户有没有激活 没有就需要激活
            if ($order['type']==1){
                //是不是注册单
                if ($buyer['is_active']==0){
                    //激活
                    M('user')->where(array('user_id'=>$buyer['user_id']))->data(array('is_active'=>1))->save();
                }
            }
            $m->commit();
            echo 1;
        }catch (PDOException $exc){
            $m->rollback();
            echo 0;
        }
    }
    






}

