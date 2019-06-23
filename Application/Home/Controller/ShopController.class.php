<?php
//作者(修改): zhudeyi
//日期: 3/20/2017
//目的: 登陆注册
namespace Home\Controller;

use Think\Controller;
use Common\Controller\HomeBaseController;
use Home\Model\UserModel;
class ShopController extends HomeBaseController
{
	/**
     * 商品列表
     */
    public function shoplist(){
        //展示所有上架的产品
        $shop_project = D('ShopProject')
            ->where(['zt'=>1])
            ->order('addtime DESC')
            ->select();
        foreach ($shop_project as &$val) {
        	$val['class']=M('shop_leibie')->where(array('id'=>$val['pid']))->getField('name');//获取商品类别名
        }
        // dump($shop_project);die;
        $this->assign('shop',$shop_project);
        $this->display('Index/shop');
    }
    /*
    商品详情
    */
    public function shopinfo(){
    	$shopid=I('request.shopid');//获取商品ID
        //dump($shopid);die;
    	$shop_project = D('ShopProject')
            ->where(array('zt'=>'1','id'=>$shopid))
            ->find();
        $shop_project['class']=M('shop_leibie')->where(array('id'=>$shop_project['pid']))->getField('name');//获取商品类别名
        $map=[
            'zt'=>array('in',[0,1,2]),
        ];
        $shop_project['number']=M('shop_orderform')->where('DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= date(addtime)')->where(array('project_id'=>$shop_project['id']))->where($map)->sum('count');
        //dump(M('shop_orderform')->getLastSql());die;
        $this->assign('shop',$shop_project);
        $this->display('Index/shop_details');
    }
    /*
    客服电话   商品详情页点击客服时调用
    */
    public function shopkefu(){
    	$phone=M('config')->where('1=1')->getField('kfphone');
    	$this->assign('phone',$phone);
    	$this->display('Index/shop_kefu');
    }
    /*
    订单确认页
    */
    public function shoptoorder(){
    	$userid=$_SESSION['user_id'];//用户id
    	//查询用户默认地址,初次显示默认地址
    	$address=M('user_ship_address')->where(array('uid'=>$userid,'is_default'=>'1','is_del'=>'0'))->find();//默认收货地址只有一条
    	$address['longaddress']=$address['address_pca'].$address['address_detailed'];//总地址,省/市/县/详细 的拼接
    	$shopid=I('request.shopid');
    	$shop_project = D('ShopProject')
            ->where(array('zt'=>'1','id'=>$shopid))
            ->find();
        $shop_project['class']=M('shop_leibie')->where(array('id'=>$shop_project['pid']))->getField('name');//获取商品类别名
        $shop_project['allprice']=$shop_project['price'];
        //dump($address);dump($shop_project);die;
        $this->assign('address',$address);//用户默认收货地址
        $this->assign('shop',$shop_project);
        $this->display('Index/confirm_order');
    }
    /*
    ajax调用,增加或减少购买数量时,改变总价格
    */
    public function allprice(){
    	$shop=D('shop_project');
    	$newnum=I('request.num');//原始购买总数量
    	//$oldprice=I('request.oldprice');//原始购买总价格
    	$way=I('request.way');//1:代表增加购买数量   2:代表减少购买数量
    	$shopid=I('request.shopid');//商品ID
        // dump($shopid);die;
    	if ($way==1) {//增加购买数量
    		//$newnum=$oldnum+1;
    		$shopnums=$shop->where(array('id'=>$shopid))->getField('nums');//商品总数量
    		$shopnum=$shop->where(array('id'=>$shopid))->getField('num');//商品已兑换数量
    		if ($newnum>$shopnums-$shopnum) {//当购买总数量大于商品剩余量,抛出提示
    			$this->ajaxReturn(['status' => '1', 'message' => '购买量超出库存量,请酌情购买!']);
                // $this->ajaxReturn(['status' => '1', 'message' => $shopnums]);
    		}else{//未超出时,返回购买数量和总价格
    			$price=$shop->where(array('id'=>$shopid))->getField('price');
    			$allprice=$newnum*$price;
    			$this->ajaxReturn(['newnum' => $newnum, 'allprice' => $allprice]);
    		}
    	}else{//减少购买数量
    		if ($newnum==0) { //最低买一个,不能再减少
    			$this->ajaxReturn(['status' => '2', 'message' => '已是最低购买量,不可再减少!']);
    		}else{
    			//$newnum=$oldnum-1;
    			$price=$shop->where(array('id'=>$shopid))->getField('price');
    			$allprice=$newnum*$price;
    			$this->ajaxReturn(['newnum' => $newnum, 'allprice' => $allprice]);
    		}
    	}
    }
    /*
    选择收货地址
    */
    public function choiceaddress(){
    	$userid=$_SESSION['user_id'];
        $shopid=I('request.shopid');
    	$address=M('user_ship_address')->where(array('uid'=>$userid,'is_del'=>'0'))->order('is_default DESC')->select();
    	foreach ($address as &$val) {
    		$val['longaddress']=$val['address_pca'].$val['address_detailed'];//总地址,省/市/县/详细 的拼接
    	}
        $this->assign('shopid',$shopid);
    	$this->assign('address',$address);
    	$this->display('Index/contacts');
    }
    /*
    选择地址后通过ajax调用此接口,修改用户默认地址,页面不刷新
    */
    public function addressok(){
    	$addressid=I('request.addreid');
    	$userid=$_SESSION['user_id'];
        $first=M('user_ship_address')->where(array('uid'=>$userid,'is_del'=>0))->save(['is_default'=>0]);
        $second=M('user_ship_address')->where(array('uid'=>$userid,'is_del'=>0,'id'=>$addressid))->save(['is_default'=>1]);
        if ($second) {
            $this->ajaxReturn(['status' => '1', 'message' => '默认地址设置成功']);
        }else{
            $this->ajaxReturn(['status' => '2', 'message' => '默认地址设置失败']);
        }
    }
    /*
    删除地址,ajax调用
    */
    public function addressdelete(){
        $addressid=I('request.id');
        $userid=$_SESSION['user_id'];
        $second=M('user_ship_address')->where(array('uid'=>$userid,'is_del'=>0,'id'=>$addressid))->save(['is_del'=>1]);
        if ($second) {
            $this->ajaxReturn(['status' => '1', 'message' => '地址删除成功']);
        }else{
            $this->ajaxReturn(['status' => '2', 'message' => '地址删除失败']);
        }
    }
    /*
    添加收货地址
    */
    public function toaddaddress(){
        $shopid=I('request.shopid');
        $userid=$_SESSION['user_id'];
        $this->assign('shopid',$shopid);
        $this->display('Index/tj_add');
    }
    /*
    添加收货地址,ajax调用此接口
    */
    public function ajaxaddress(){
        $userid=$_SESSION['user_id'];
        $record=I('request.');
        // $type=I('request.type');
        if ($record['shouhuoname']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写收货人姓名']);
        }elseif ($record['shouhuophone']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写联系电话']);
        }elseif ($record['shouhuopace']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写省市信息']);
        }elseif ($record['shouhuoinfo']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写详细地址']);
        }else{
            if (M('user_ship_address')->where(array('uid'=>$userid,'name'=>$record['shouhuoname'],'phone'=>$record['shouhuophone'],'address_pca'=>$record['shouhuopace'],'address_detailed'=>$record['shouhuoinfo'],'is_del'=>0))->find()) {
                $this->ajaxReturn(['status' => '2', 'message' => '添加的收货地址信息已存在,请勿重复添加']);
            }else{
                if ($record['is_default']==1) {
                    $new['is_default']=0;
                    D('user_ship_address')->where(array('uid'=>$userid,'is_del'=>0,'is_default'=>1))->save($new);
                    $data['uid']=$userid;
                    $data['name']=$record['shouhuoname'];
                    $data['phone']=$record['shouhuophone'];
                    $data['address_pca']=$record['shouhuopace'];
                    $data['address_detailed']=$record['shouhuoinfo'];
                    $data['is_default']=$record['is_default'];
                    $data['created_at']=date('Y-m-d H:i:s',time());
                    if (M('user_ship_address')->data($data)->add()) {
                        $this->ajaxReturn(['status' => '1', 'message' => '收货地址添加成功','shopid'=>$record['shopid']]);
                    }else{
                        $this->ajaxReturn(['status' => '2', 'message' => '收货地址添加失败']);
                    }
                }else{
                    $data['uid']=$userid;
                    $data['name']=$record['shouhuoname'];
                    $data['phone']=$record['shouhuophone'];
                    $data['address_pca']=$record['shouhuopace'];
                    $data['address_detailed']=$record['shouhuoinfo'];
                    $data['is_default']=$record['is_default'];
                    $data['created_at']=date('Y-m-d H:i:s',time());
                    if (M('user_ship_address')->data($data)->add()) {
                        $this->ajaxReturn(['status' => '1', 'message' => '收货地址添加成功','shopid'=>$record['shopid']]);
                    }else{
                        $this->ajaxReturn(['status' => '2', 'message' => '收货地址添加失败']);
                    }
                }

            }
        }
    }

    /**
     * 修改地址
     */
    public function editddress(){

        $userid=$_SESSION['user_id'];
        $record=I('request.');
        // $type=I('request.type');
        if ($record['shouhuoname']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写收货人姓名']);
        }elseif ($record['shouhuophone']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写联系电话']);
        }elseif ($record['shouhuopace']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写省市信息']);
        }elseif ($record['shouhuoinfo']=="") {
            $this->ajaxReturn(['status' => '2', 'message' => '请填写详细地址']);
        }else{
                if ($record['is_default']==1) {
                    $new['is_default']=0;
                    D('user_ship_address')->where(array('uid'=>$userid,'is_del'=>0,'is_default'=>1))->save($new);
                    $data['uid']=$userid;
                    $data['name']=$record['shouhuoname'];
                    $data['phone']=$record['shouhuophone'];
                    $data['address_pca']=$record['shouhuopace'];
                    $data['address_detailed']=$record['shouhuoinfo'];
                    $data['is_default']=$record['is_default'];
                    $data['updated_at']=date('Y-m-d H:i:s',time());
                    if (D('user_ship_address')->where(array('id'=>$record['id']))->save($data)) {
                        $this->ajaxReturn(['status' => '1', 'message' => '修改成功','shopid'=>$record['shopid']]);
                    }else{
                        $this->ajaxReturn(['status' => '2', 'message' => '收货地址修改失败']);
                    }
                }else{
                    $data['uid']=$userid;
                    $data['name']=$record['shouhuoname'];
                    $data['phone']=$record['shouhuophone'];
                    $data['address_pca']=$record['shouhuopace'];
                    $data['address_detailed']=$record['shouhuoinfo'];
                    $data['is_default']=$record['is_default'];
                    $data['updated_at']=date('Y-m-d H:i:s',time());
                    if (D('user_ship_address')->where(array('id'=>$record['id']))->save($data)) {
                        $this->ajaxReturn(['status' => '1', 'message' => '修改成功','shopid'=>$record['shopid']]);
                    }else{
                        $this->ajaxReturn(['status' => '2', 'message' => '收货地址修改失败']);
                    }
                }
        }
    }
    /*
    提交订单,下单成功;弹出支付详情页面
    */
    public function summitorder(){
    	$data['user']=$_SESSION['user_id'];
    	$data['user_phone']=M('user_ship_address')->where(array('uid'=>$_SESSION['user_id'],'is_del'=>0,'is_default'=>1))->getField('phone');//收货人联系方式
    	$data['user_name']=M('user_ship_address')->where(array('uid'=>$_SESSION['user_id'],'is_del'=>0,'is_default'=>1))->getField('name');//收货人联系电话
    	$data['order']=$this->getrandstr(18);//18位订单编号
    	$data['project']=M('shop_project')->where(array('id'=>I('request.shopid')))->getField('name');//商品名称
    	$data['count']=I('request.num');//商品购买数量
    	$data['sumprice']=I('request.money');//总价格
    	$data['addtime']=date('Y-m-d H:i:s',time());
    	$data['zt']=4;//下单后还未支付,取值4
        $sheng=M('user_ship_address')->where(array('uid'=>$_SESSION['user_id'],'is_del'=>0,'is_default'=>1))->getField('address_pca');
        $addinfo=M('user_ship_address')->where(array('uid'=>$_SESSION['user_id'],'is_del'=>0,'is_default'=>1))->getField('address_detailed');
    	$data['address']=$sheng.$addinfo;//页面显示的总收货地址
    	$data['note']=I('request.beizhu');//备注信息
    	$data['project_id']=I('request.shopid');//商品ID
        //检测当前用户有没有收货地址,没有时不可下单
        if (M('user_ship_address')->where(array('uid'=>$_SESSION['user_id'],'is_del'=>0,'is_default'=>1))->find()) {
            if (M('shop_orderform')->data($data)->add()) {//下单成功时
                $_SESSION['ordernum']=$data['order'];//订单编号存放在session中
                $this->ajaxReturn(['status' => '0', 'message' => $data['order']]);
            }else{
                $this->ajaxReturn(['status' => '1', 'message' => '下单失败,请重新下单!']);
            }
        }else{//没有收货地址
            $this->ajaxReturn(['status' => '1', 'message' => '您还未添加或未设置默认收货地址,暂无法下单']);
        }
    }
    /*
    输入安全密码进行支付用动态钱包和倍增钱包中的钱进行支付
    */
    public function buyend(){
        $orderid=$_SESSION['ordernum'];
        $userid=$_SESSION['user_id'];
        $this->assign('orderid',$orderid);
        $this->display('Index/shop_buy');
    }
    /*
    安全密码检测,默认优先用动态钱包中的金额进行支付,其次是倍增钱包
    */
    public function salfepass(){
        $userid=$_SESSION['user_id'];
        $ordernum=I('request.order');
        $salfpass=I('request.salfpass');
        $payway=I('request.payway');
        // echo($payway);die;
        $usersalfe=M('user')->where(array('user_id'=>$userid))->getField('user_secpwd');
        if ($usersalfe==md5($salfpass)) {
            //扣除钱包余额
            if ($payway==1) {//动态钱包
                $userjfc=M('wallet')->where(array('user_id'=>$userid))->getField('change_amount');
                $orderjfc=M('shop_orderform')->where(array('order'=>$ordernum))->getField('sumprice');
                $changestatus=M('wallet')->where(array('user_id'=>$userid))->getField('change_is_freeze');
                if ($changestatus==1 || $userjfc<$orderjfc) {//动态奖金冻结或余额不足时,用倍增钱包
                    $this->ajaxReturn(['status' => '1', 'message' => '动态奖金已被冻结!无法购买']);
                }elseif ($userjfc<$orderjfc) {
                    $this->ajaxReturn(['status' => '1', 'message' => '动态奖金不足!无法购买']);
                }else{
                    $userjfc=$userjfc-$orderjfc;
                    if (M('wallet')->where(array('user_id'=>$userid))->save(['change_amount'=>$userjfc])) {
                        //修改订单状态
                        M('shop_orderform')->where(array('order'=>$ordernum))->save(['zt'=>0]);
                        $projectid=M('shop_orderform')->where(array('order'=>$ordernum))->getField('project_id');
                        //修改商品销量数,在销售完时,下架商品
                        $yuannums=M('shop_project')->where(array('id'=>$projectid))->getField('nums');
                        $yuannum=M('shop_project')->where(array('id'=>$projectid))->getField('num');
                        $xiannum=$yuannum+I('request.num');
                        if ($xiannum==$yuannums) {
                            M('shop_project')->where(array('id'=>$projectid))->save(['num'=>$xiannum]);
                            M('shop_project')->where(array('id'=>$projectid))->save(['zt'=>'0']);
                        }else{
                            M('shop_project')->where(array('id'=>$projectid))->save(['num'=>$xiannum]);
                        }
                        //在wallet_log表中添加动态钱包使用明细
                        $projectname=M('shop_orderform')->where(array('order'=>$ordernum))->getField('project');
                        $data['user_id']=$userid;
                        $data['user_name']=M('user')->where(array('user_id'=>$userid))->getField('user_name');
                        $data['user_phone']=M('user')->where(array('user_id'=>$userid))->getField('user_phone');
                        $data['amount']=$orderjfc;
                        $data['remain_amount']=$userjfc;
                        $data['change_date']=time();
                        $data['log_note']="购买".$projectname;
                        $data['wallet_type']=6;
                        M('wallet_log')->data($data)->add();
                        $this->ajaxReturn(['status' => '0', 'message' => '支付成功!']);
                    }else{
                        $this->ajaxReturn(['status' => '1', 'message' => '动态奖金扣除失败!购买无效']);
                    }
                }
            }else{//倍增钱包
                $doubeljfc=getActiveDouble($userid);
                $orderjfc=M('shop_orderform')->where(array('order'=>$ordernum))->getField('sumprice');
                if ($doubeljfc<$orderjfc) {
                    $this->ajaxReturn(['status' => '1', 'message' => '倍增钱包可用金额不足!无法购买']);
                }else{
                    $doubeljfc=$doubeljfc-$orderjfc;
                    if (M('wallet')->where(array('user_id'=>$userid))->save(['double_amount'=>$doubeljfc])) {
                        //修改订单状态
                        M('shop_orderform')->where(array('order'=>$ordernum))->save(['zt'=>0]);
                        $projectid=M('shop_orderform')->where(array('order'=>$ordernum))->getField('project_id');
                        //修改商品销量数,在销售完时,下架商品
                        $yuannums=M('shop_project')->where(array('id'=>$projectid))->getField('nums');
                        $yuannum=M('shop_project')->where(array('id'=>$projectid))->getField('num');
                        $xiannum=$yuannum+I('request.num');
                        if ($xiannum==$yuannums) {
                            M('shop_project')->where(array('id'=>$projectid))->save(['num'=>$xiannum]);
                            M('shop_project')->where(array('id'=>$projectid))->save(['zt'=>'0']);
                        }else{
                            M('shop_project')->where(array('id'=>$projectid))->save(['num'=>$xiannum]);
                        }
                        //在wallet_log表中添加动态钱包使用明细
                        $projectname=M('shop_orderform')->where(array('order'=>$ordernum))->getField('project');
                        $data['user_id']=$userid;
                        $data['user_name']=M('user')->where(array('user_id'=>$userid))->getField('user_name');
                        $data['user_phone']=M('user')->where(array('user_id'=>$userid))->getField('user_phone');
                        $data['amount']=$orderjfc;
                        $data['remain_amount']=$doubeljfc;
                        $data['change_date']=time();
                        $data['log_note']="购买".$projectname;
                        $data['wallet_type']=3;
                        M('wallet_log')->data($data)->add();
                        //判断是否动用了不可使用的倍增金额,是的话就把产生的利息扣除
                        isTakeOutIntrest($userid,$orderjfc);
                        $this->ajaxReturn(['status' => '0', 'message' => '支付成功!']);
                    }else{
                        $this->ajaxReturn(['status' => '1', 'message' => '倍增钱包扣除失败!购买无效']);
                    }
                }
            }
        }else{
            $this->ajaxReturn(['status' => '1', 'message' => '支付密码输入错误!']);
        }
    }
    /*
    生成订单编号
    */
    function getrandstr($length){
		$str="123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$len=strlen($str)-1;
		$randstr="";
		for($i=0;$i<$length;$i++){
			$num=mt_rand(0,$len);
			$randstr.=$str[$num];
		}
		return $randstr;
	}
    /*
    下单后将进行支付    END!
    */
    public function userpay(){
        //支付只考虑jfc币的扣除,无支付宝与微信支付
    }
}
?>