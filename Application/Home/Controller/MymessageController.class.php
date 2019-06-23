<?php
//作者(修改): zhudeyi
//日期: 3/20/2017
//目的: 登陆注册
namespace Home\Controller;

use Think\Controller;
use Common\Controller\HomeBaseController;
use Home\Model\UserModel;
use Common\Controller\SendSmsController;
class MymessageController extends HomeBaseController
{	
	//收货地址
	public function personal_address(){
        $userid=$_SESSION['user_id'];
//        $shopid=I('request.shopid');
        $address=M('user_ship_address')->where(array('uid'=>$userid,'is_del'=>'0'))->order('is_default DESC')->select();
        foreach ($address as &$val) {
            $val['longaddress']=$val['address_pca'].$val['address_detailed'];//总地址,省/市/县/详细 的拼接
        }

        $this->assign('address',$address);

		$this->display('Personal/personal_address');
	}
	//新建收货地址
	public function personal_address_add(){
		$this->display('Personal/personal_address_add');
	}
	//编辑地址
	public function personal_address_edit(){
        $id=I('request.id');
        $addressinfo=M('user_ship_address')->where(array('id'=>$id))->find();
        $this->assign('address',$addressinfo);
		$this->display('Personal/personal_address_edit');
	}
	
	public function response(){
		$this->display('Personal/response');
	}
	/*
	用户基本信息 ----->头像图片下显示用户昵称(用户昵称是唯一的)
	*/
	public function myinfo(){
		$User=M('user');
		//$userid=I('request.id');
		$userid=$_SESSION ['user_id'];
		$data=$User->where(array('user_id'=>$userid))->find();
		//dump($data);die;
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
        $directpush=$User->where($push)->where(array('is_active=1'))->count();
        //dump($directpush);die;
        $myteams=$User->where($push2)->where(array('is_active=1'))->count();
        $userinfo['user_level']=getviplevel($directpush,$myteams);
		$this->assign('data',$data);
		$this->assign('userinfo',$userinfo);
		$this->display('Personal/personal');
	}
	/*
   	上传头像
    */
    public function upfile(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     18145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        //$upload->saveName = '';
        //$upload->rootPath  =     '../Uploads/'; // 设置附件上传根目录   将文件保存在Uploads文件下的images中
        $upload->rootPath  =     '../public/Uploads/'; // 设置附件上传根目录    将文件保存在Public文件下的images中
        $upload->savePath  =     '/Pic/'; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if(!$info) {
            $this->error($upload->getError());
            exit;
        }else{// 上传成功
            // dump($info);
            foreach($info as $file){
              //$data['datas']= '../Uploads/images/'.$file['savePath'].$file['savename'];  文件路径,存储在数据库中
              $data['datas']= '/Uploads/Pic/'.date('Y-m-d',time()).'/'.$file['savename'];//文件路径,存储在数据库中
            }

            //dump($data);die;
            echo $data['datas'];
        }        
    }
    /*
   	上传头像
    */
   public function upload(){
   		$userid=$_SESSION ['user_id'];
	   	$file['user_headimg'] = I('request.file');
	   	//dump($file);die;
	   	$files = M('user')->where(array('user_id'=>$userid))->save($file);
	   	if ($files) {
	   		$this->ajaxReturn(['status' => '1', 'message' => '修改成功!']);
	   	}
   }
	/*
	账户与安全
	*/
	public function account(){
		$User=D('user');
		$userid=$_SESSION ['user_id'];
		$phone=$User->where(array('user_id'=>$userid))->getField('user_phone');
		$this->assign('data',$phone);
		$this->display('Index/set_up');
	}
	/*
	获取短信验证码
	*/
	public function getcode(){
		$phone=I('request.phone');
		$this->sendSms($phone);
	}
	/*
	修改登录密码
	*/
	public function changepassword(){
		$User=M('user');
		$userid=$_SESSION ['user_id'];
		if (IS_POST) {
			$code=I('request.code');
			$password=I('request.password');
			$phone=I('request.phone');
			$repassword=I('request.repassword');
			$oldpass=M('user')->where(array('user_id'=>$userid))->getField('user_password');
            $zfpass=M('user')->where(array('user_id'=>$userid))->getField('user_secpwd');
			if(I('request.type') == 1){
			    if(md5($password) == $oldpass){
                    $this->ajaxReturn(['status' => '2', 'message' => '不能和原密码一致!']);
                }
            }else{
                if(md5($password) == $zfpass){
                    $this->ajaxReturn(['status' => '2', 'message' => '不能和原密码一致!']);
                }
            }
			//$oldname=M('user')->where(array('user_id'=>$userid))->getField('user_name');
			if ($password!==$repassword) {//密码不一致
				$this->ajaxReturn(['status' => '2', 'message' => '两次输入密码不一致!']);
			}else{
				$thecode=$this->checkSms($phone,$code);//校验验证码
				if ($thecode['res']) {
				    if(I('request.type') == 1){
                        $data['user_password']=md5($password);//md5加密
				        $result=$User->where(['user_id'=>$userid])->save($data);
                        //$result=$User->where(array('user_id'=>$userid))->save($password);
                        if ($result) {
                            $this->ajaxReturn(['status' => '0', 'message' => '登录密码修改成功!请重新登录']);
                        }else{
                            $this->ajaxReturn(['status' => '1', 'message' => '登录密码修改失败!']);
                        }
                    }else{
                        $data['user_secpwd']=md5($password);//md5加密
				        $result=$User->where(['user_id'=>$userid])->save($data);
                        //$result=$User->where(array('user_id'=>$userid))->save($password);
                        if ($result) {
                            $this->ajaxReturn(['status' => '2', 'message' => '资金密码修改成功!']);
                        }else{
                            $this->ajaxReturn(['status' => '1', 'message' => '资金密码修改失败!']);
                        }
				    }
				}else{
					$this->ajaxReturn(['status' => '1', 'message' => $thecode['msg']]);
				}
			}
		}else{
			$thephone=$User->where(array('user_id'=>$userid))->getField('user_phone');
			$this->assign('phone',$thephone);
			$this->assign('id',$userid);
		}
		$this->display('Personal/password-update');
	}
	/*
	修改二级密码
	*/
	public function changepass(){
		$User=D('user');
		$userid=$_SESSION ['user_id'];
		if (IS_POST) {
			$phone=I('request.phone');
			$code=I('request.thecode');
			$password1=I('request.newpass');
			$repassword=I('request.repass');
			$oldpass=M('user')->where(array('user_id'=>$userid))->getField('user_secpwd');
			if ($password1!==$repassword) {
				$this->ajaxReturn(['status' => '2', 'message' => '两次密码输入不一致!']);
			}elseif(md5($password1) == $oldpass){
				$this->ajaxReturn(['status' => '2', 'message' => '不能和原密码一致!']);
			}else{
				$password['user_secpwd']=md5($password1);//md5加密
				$thecode=$this->checkSms($phone,$code);//校验验证码
				if ($thecode['res']) {
					$result=$User->where(array('user_id'=>$userid))->save($password);
					if ($result) {
						$this->ajaxReturn(['status' => '0', 'message' => '二级密码修改成功!']);
					}else{
						$this->ajaxReturn(['status' => '1', 'message' => '二级密码修改失败!']);
					}
				}else{
					$this->ajaxReturn(['status' => '1', 'message' => $thecode['msg']]);
				}
			}
		}else{
			$thephone=$User->where(array('user_id'=>$userid))->getField('user_phone');
			$this->assign('phone',$thephone);
			$this->assign('id',$userid);
		}
		$this->display('Personal/password-two-update');
	}

	//修改密码短信
    function upPasswordSendSms(){
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

    /*
	收款方式 银行卡/支付宝
	*/
	public function paystyle(){
		$user_idcard=M('user_idcard');
        $userid=$_SESSION ['user_id'];
        $count = $user_idcard->where(['user_id'=>$userid])->count();
        $con = M('user_ali_number')->where(['user_id'=>$userid])->count();
        if(empty($count)&&empty($con)){
        	$truename = M('user')->where(['user_id'=>$userid])->getField('user_truename');
	        $this->assign('truename',$truename);
	        //dump($truename);die;
        	$this->display('Personal/pay-withdraw');
        }else{
        	//获取银行卡信息
        	$bank = $user_idcard->where(['user_id'=>$userid])->select();
        	//获取支付宝信息
        	$Alinum = M('user_ali_number');
        	$alinum = $Alinum->where(['user_id'=>$userid])->find();
        	//var_dump($alinum);die;
        	$this->assign('alinuminfo',$alinum);
        	$this->assign('bankinfo',$bank);
        	$this->display('Personal/pay-information');
        }
	}

	/*
	收款方式 添加银行卡
	*/
	public function addbank(){
		$user_idcard=M('user_idcard');
        $userid=$_SESSION ['user_id'];
        $count = $user_idcard->where(['user_id'=>$userid])->count();
        if(IS_POST){
	       	if($count>=3){
	        	$this->ajaxReturn(['status' => '2', 'message' => '最多可添加三张银行卡!']);
	        	die();
	        }
           $user_truename = I('request.user_truename');
           $id_card = I('request.id_card');
           $card_kaihu = I('request.card_kaihu');
           $card_address = I('request.card_address');
           if(empty($user_truename)||empty($id_card)||empty($card_kaihu)){
           		$this->ajaxReturn(['status' => '0', 'message' => '请填写完整信息!']);
           		die();
           }
           $push=[
	            'card_kaihu'=>array('like','%'.$card_kaihu.'%'),
	        ];
           $bankname = $user_idcard->where(array($push,['user_id'=>$userid]))->select();
           if($bankname){
           		$this->ajaxReturn(['status' => '0', 'message' => '同一个银行只能添加一张银行卡!']);
           		die();
           }
           $data['user_truename']=$user_truename;
           $data['id_card']=$id_card;
           $data['card_kaihu']=$card_kaihu;
           $data['card_address']=$card_address;
           $data['user_id']=$userid;
           $data['add_time']=time();
           $result=$user_idcard->where(['user_id'=>$userid])->add($data);
           if ($result) {
                    $this->ajaxReturn(['status' => '1', 'message' => '提交成功!']);
                }else{
                    $this->ajaxReturn(['status' => '0', 'message' => '提交失败!']);
                }
        }
        $truename = M('user')->where(['user_id'=>$userid])->getField('user_truename');
	    $this->assign('truename',$truename);
        $this->display('Personal/pay-withdraw');
	}

	/*
	收款方式 添加支付宝
	*/
	public function Alinum(){
		$Alinum = M('user_ali_number');
        $userid = $_SESSION ['user_id'];
        $count = $Alinum->where(['user_id'=>$userid])->count();
        if(IS_POST){
        	if($count>=1){
	        	$this->ajaxReturn(['status' => '2', 'message' => '最多可添加一个支付宝账号!']);
	        	die();
	        }
           $name = I('request.name');
           $alinum = I('request.alinum');
           if(empty($name)||empty($alinum)){
           		$this->ajaxReturn(['status' => '0', 'message' => '请填写完整信息!']);
           		die();
           }
           $data['name']=$name;
           $data['ali_num']=$alinum;
           $data['user_id']=$userid;
           $data['add_time']=time();
           $result=$Alinum->where(['user_id'=>$userid])->add($data);
           if ($result) {
                    $this->ajaxReturn(['status' => '1', 'message' => '提交成功!']);
                }else{
                    $this->ajaxReturn(['status' => '0', 'message' => '提交失败!']);
                }
        }
	}

	 /*
	银行卡信息
	*/
	public function bankinfo(){
		$user_idcard=M('user_idcard');
        $userid=$_SESSION ['user_id'];
        $bank = $user_idcard->where(['user_id'=>$userid])->select();
        var_dump($bank);die;
		$this->display('Personal/pay-withdraw-update');
	}


	###############################################################################################
	###############################################################################################
	
	/*
	删除银行卡
	*/
	public function delbank(){
		$id=I('request.theid');//银行卡表的序号值
		$bank=D('user_idcard');
		$result = $bank->where(array('id'=>$id))->delete(); 
		if ($result) {
			$this->ajaxReturn(['status' => '1', 'message' => '银行卡删除成功!']);
		}else{
			$this->ajaxReturn(['status' => '2', 'message' => '银行卡删除失败!']);
		}
	}
	/*
	删除支付宝账号
	*/
	public function delalinum(){
		$id=I('request.theid');//支付宝表的序号值
		$bank=D('user_ali_number');
		$result = $bank->where(array('id'=>$id))->delete(); 
		if ($result) {
			$this->ajaxReturn(['status' => '1', 'message' => '支付宝删除成功!']);
		}else{
			$this->ajaxReturn(['status' => '2', 'message' => '支付宝删除失败!']);
		}
	}
	// /*
	// 我的邀请码中上传交易凭证图
	// */
	// public function upimames(){

	// }
	/*
	我的激活码-平台申请
	*/
	public function Activationcode(){
		$userid=$_SESSION['user_id'];
		$activecode=D('user_active_code');
		$count=$activecode->where(array('user_id'=>$userid,'is_used'=>'0'))->count();//邀请码剩余数量
		$qrcode=M('config')->where('1=1')->getField('sys_gain_code');//商家收款码图片路径
		if (IS_POST) {
			$recold=I('request.');
			if (!$recold['img1']) {
				$this->ajaxReturn(['status' => '2', 'message' => '请上传交易凭证!']);
			}elseif (!$recold['buynum']) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写购买数量!']);
			}elseif (!$recold['allprice']) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写交易总价格!']);
			}elseif ($recold['buynum']<=0) {
				$this->ajaxReturn(['status' => '2', 'message' => '邀请码单次至少购买一张!']);
			}elseif ($recold['allprice']<$recold['buynum']*100) {
				$this->ajaxReturn(['status' => '2', 'message' => '交易总价格不得小于应付价格!']);
			}else{
				$data['img_evidence'] = $recold['img1'];//图片地址路径
				$data['user_id']=$_SESSION['user_id'];
				$data['number']=$recold['buynum'];
				$data['price']=$recold['allprice'];  //单位:元
				// $data['img_evidence']=$recold['img'];  //凭证图片路径
				$data['addtime']=time();
				if (M('user_code_order')->data($data)->add()) {//将记录登记到表中
					$this->ajaxReturn(['status' => '1', 'message' => '提交成功!请等待后台处理...']);
				}else{
					$this->ajaxReturn(['status' => '2', 'message' => '提交失败,请重新提交!']);
				}
			}
		}else{
			$this->assign('count',$count);
			$this->assign('erweima',$qrcode);
		}
		$this->display('Index/my_pin');
	}
	/*
	我的邀请码-转让
	*/
	public function transfer(){
		$userid=$_SESSION['user_id'];
		$activecode=D('user_active_code');
		$count=$activecode->where(array('user_id'=>$userid,'is_used'=>'0'))->count();//邀请码剩余数量
		if (IS_POST) {
			$userid=$_SESSION['user_id'];
			$username=I('request.toname');//对方账户的手机号,根据手机号找对方的userid
			$count=I('request.tonumber');//邀请码转让数量
			$pass=I('request.topass');//安全密码
			if (!$username) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写用户名!']);
			}elseif (!$count) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写转出数量!']);
			}elseif (!$pass) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写安全密码!']);
			}else{
				$foruserid=M('user')->where(array('user_name'=>$username))->getField('user_id');
				$thepassword=M('user')->where(array('user_id'=>$userid))->getField('user_secpwd');//用户安全密码
				if (!$foruserid) {//找不到对方id时
					$this->ajaxReturn(['status' => '2', 'message' => '填写的用户名不存在!']);
				}else{
					if (md5($pass)==$thepassword) {//判断用户密码输入是否正确
						$allcount=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->count();
						if ($allcount<$count) {//邀请码剩余量不足
							$this->ajaxReturn(['status' => '2', 'message' => '账户邀请码剩余量不足!']);
						}else{
							//根据转让数量得到相应的行数据
							$data=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->getField('code',$count);
							for ($i=0; $i < count($data); $i++) { 
								$rel['user_id']=$foruserid;
								$rel['code']=$data[$i];
								$rel['addtime']=time();
								M('user_active_code')->data($rel)->add();//给对方账户添加邀请码
								//根据用户id和code值,将相应的is_used值设置为1(已失效)
								M('user_active_code')->where(array('user_id'=>$userid,'code'=>$data[$i]))->save(['is_used'=>'1']);
							}
							$this->ajaxReturn(['status' => '1', 'message' => '邀请码转让成功!']);
						}
					}else{
						$this->ajaxReturn(['status' => '2', 'message' => '安全密码填写错误!']);
					}
				}
			}
		}else{
			$this->assign('count',$count);
		}
		$this->display('Index/my_pin_sell');
	}
	/*
	激活码详情
	*/
	public function Activateinfo(){
		$userid=$_SESSION['user_id'];
		$data=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->order('addtime DESC,id DESC')->select();
		$this->assign('data',$data);
		$this->display('Index/pin_list');
	}
	/*
	我的数字钱包
	*/
	public function mywallet(){
		$userid=$_SESSION['user_id'];
		// $type=I('request.type');//收款码类型  1:支付宝  2:微信
		// if ($type==1) {//查支付宝
		// 	$data=M('user_number_wallet')->field('id,money_code_img,add_time')->where(array('user_id'=>$userid,'type'=>1,'del'=>0))->order('add_time DESC')->select();
		// }else{//查微信
		// 	$data=M('user_number_wallet')->field('id,money_code_img,add_time')->where(array('user_id'=>$userid,'type'=>2,'del'=>0))->order('add_time DESC')->select();
		// }
		$data=M('user_number_wallet')->field('id,money_code_img,add_time,type')->where(array('user_id'=>$userid,'del'=>0))->order('type ASC,add_time DESC')->select();
		$this->assign('data',$data);
		$this->display('Index/number_wallet');
	}
	/*
	我的数字钱包----删除收款二维码
	*/
	public function qrcodedelete(){
		$theid=I('request.theid');//获取相应的序号
		if (M('user_number_wallet')->where(array('id'=>$theid))->save(['del'=>1])) {
			//获取图片地址路径,然后删除图片
			$thelink=M('user_number_wallet')->where(array('id'=>$theid))->getField('money_code_img');
			unlink($thelink);
			$this->ajaxReturn(['status' => '1', 'message' => '删除成功!']);
		}else{
			$this->ajaxReturn(['status' => '2', 'message' => '删除失败!']);
		}
	}
	
	/*
	我的数字钱包 ----添加收款码图片
	*/
	public function qrcodeadd(){
		if (IS_POST) {
			$data['user_id']=$_SESSION['user_id'];
                $filename2 = I('request.img1');
                $filename3 = I('request.img2');
                $type=I('request.type');
                if (!$filename2&&!$filename3) {
                	$this->ajaxReturn(['status' => '2', 'message' => '请上传至少一张图片!']);
                }
                if ($filename2&&$filename3) {//两张都有
                	$data['money_code_img'] = $filename2;//图片地址路径
	                $data['addtime']=time();
	                $data['type']=1;//收款码类型  1:支付宝  2:微信
	                $data2['money_code_img'] = $filename3;//图片地址路径
	                $data2['add_time']=time();
	                $data2['type']=2;//收款码类型  1:支付宝  2:微信
	                $data2['user_id']=$_SESSION['user_id'];
		            if(M('user_number_wallet')->data($data)->add()&&M('user_number_wallet')->data($data2)->add()) {
		            	$this->ajaxReturn(['status' => '1', 'message' => '支付宝与微信收款码添加成功!','type1'=>$type]);
		            }elseif (M('user_number_wallet')->data($data)->add()&&!M('user_number_wallet')->data($data2)->add()) {
		            	$this->ajaxReturn(['status' => '1', 'message' => '支付宝收款码添加成功!微信收款码添加失败','type1'=>$type]);
		            }elseif (!M('user_number_wallet')->data($data)->add()&&M('user_number_wallet')->data($data2)->add()) {
		            	$this->ajaxReturn(['status' => '1', 'message' => '微信收款码添加成功!支付宝收款码添加失败','type1'=>$type]);
		            }else{
		            	$this->ajaxReturn(['status' => '2', 'message' => '支付宝与微信收款码添加失败!']);
		            }
                }elseif ($filename2&&!$filename3) {//仅有支付宝
	                $data['money_code_img'] = $filename2;//图片地址路径
	                $data['add_time']=time();
	                $data['type']=1;//收款码类型  1:支付宝  2:微信
		            if (M('user_number_wallet')->data($data)->add()) {
		            	$this->ajaxReturn(['status' => '1', 'message' => '支付宝收款码添加成功!','type1'=>$type]);
		            }else{
		            	$this->ajaxReturn(['status' => '2', 'message' => '支付宝收款码添加失败!']);
		            }
                }else{//仅有微信
	                $data['money_code_img'] = $filename3;//图片地址路径
	                $data['add_time']=time();
	                $data['type']=2;//收款码类型  1:支付宝  2:微信
		            if (M('user_number_wallet')->data($data)->add()) {
		            	$this->ajaxReturn(['status' => '1', 'message' => '微信收款码添加成功!','type1'=>$type]);
		            }else{
		            	$this->ajaxReturn(['status' => '2', 'message' => '微信收款码添加失败!']);
		            }
                }
		}else{
			$type = I('get.type');
			if($type){
				if($type==2){
					$this->assign('type',$type);//调用方式
				}
			}
			$this->display('Index/add_num_wallet');
		}
	}
	/*商城订单*/
	public function shoporderlist(){
		$userid=$_SESSION['user_id'];
		$map=[
			'zt'=>array('in',[0,1,2]),
		];
		$data=M('shop_orderform')->where(array('user'=>$userid,'is_del'=>0))->where($map)->order('addtime DESc')->select();
		foreach ($data as &$val) {
			$val['img']=M('shop_project')->where(array('id'=>$val['project_id']))->getField('imagepath');
		}
		$this->assign('data',$data);
		$this->display('Index/shop_order_list');
	}
	/*确认收货*/
	public function getproject(){
		$theid=I('request.addreid');
		if (M('shop_orderform')->where(array('id'=>$theid))->save(['zt'=>2])) {
			$this->ajaxReturn(['status' => '1', 'message' => '确认收货成功!']);
		}else{
			$this->ajaxReturn(['status' => '2', 'message' => '确认收货失败!']);
		}
	}
	/*
	删除商城订单
	*/
	public function orderdelete(){
		$theid=I('request.addreid');
		if (M('shop_orderform')->where(array('id'=>$theid))->save(['is_del'=>1])) {
			$this->ajaxReturn(['status' => '1', 'message' => '订单删除成功!']);
		}else{
			$this->ajaxReturn(['status' => '2', 'message' => '订单删除失败!']);
		}
	}
	/*
	个人中心--退出登录
	*/
	public function loginout(){
		session(null);
		$this->ajaxReturn(['status' => '1', 'message' => '退出成功!']);
	}
	/*
	邀请好友
	*/
	public function askgoodfirends(){
		$userid=$_SESSION['user_id'];
		$thecode=M('user')->where(array('user_id'=>$userid))->getField('user_reg_code');
		$this->assign('code',$thecode);
		$this->display('Index/inviting');
	}
	/*
	帮助中心
	*/
	public function helpcenter(){
		$userid=$_SESSION['user_id'];
		$this->display('Index/help_center');
	}
	public function helpcentertwo(){
		if (IS_POST) {
			$data['content']=I('request.forhelp');
			$data['user_id']=$_SESSION['user_id'];
			$data['addtime']=time();
			if (!$data['content']) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写反馈问题!']);
			}else{
				if (M('feedback')->data($data)->add()) {
					$this->ajaxReturn(['status' => '1', 'message' => '问题反馈成功!']);
				}else{
					$this->ajaxReturn(['status' => '2', 'message' => '问题反馈失败!']);
				}
			}
		}
	}
	/*
	关于(系统公告)
	*/
	public function sysabout(){
		$userid=$_SESSION['user_id'];
		// $map=[
		// 	'createtime'=>array('between',[time()-864000,time()]),
		// ];
		// $info=M('user_notice')->where(array('user_id'=>$userid))->where($map)->order('createtime DESC')->select();
		$info=M('mf_sys_notice')->where(array('user_id'=>$userid))->order('createtime DESC')->select();
		//var_dump($info);die;
		$this->assign('notice',$notice);
		$this->display('Message/message');
	}
	/*
	消息
	*/
	public function myabout(){
		$userid=$_SESSION['user_id'];
		// $map=[
		// 	'createtime'=>array('between',[time()-864000,time()]),
		// ];
		// $info=M('user_notice')->where(array('user_id'=>$userid))->where($map)->order('createtime DESC')->select();
		// 通知消息,仅查看最近一周的
		$map=[
			'createtime'=>array('egt',time()-7*86400),
		];
		$tongzhi = M('user_notice')->where(array('user_id'=>$userid))->where($map)->order('createtime DESC')->select();
		//标记已读
		M('user_notice')->where(array('user_id'=>$userid))->save(['is_see'=>'2']);
		//推送消息,仅查看最近一周的
		$where=[
			'date'=>array('egt',time()-7*86400),
		];
		$tuisong = M('news')->where('type = 1')->where($where)->order('date desc')->select();
		//记录用户最近一次查看推送消息的时间
		M('user')->where(array('user_id'=>$userid))->save(['user_lastsee_time'=>time()]);
		foreach ($tuisong as &$val) {
			$val['date']=date('Y-m-d',$val['date']);
		}
		//申诉消息
		$feedback = M('feedback')->where(array('user_id'=>$userid))->order('addtime desc')->select();
		//标记已读
		M('feedback')->where(array('user_id'=>$userid))->save(['is_see'=>'2']);
		//var_dump($feedback);die;
		$this->assign('tongzhi',$tongzhi);
		$this->assign('tuisong',$tuisong);
		$this->assign('feedback',$feedback);
		$this->display('Message/message');
	}
	/*
	下级用户(激活和未激活的都显示)
	*/
	public function unactiveuser(){
		$userid=$_SESSION['user_id'];
		$map=[
			'user_parent' =>array('like','%'.$userid),
		];
		$list=M('user')->where($map)->order('user_add_time desc')->select();
		$this->assign('list',$list);
		$this->display('Index/user_activation');
	}
	/*
	点击激活按钮弹出弹窗,显示用户的账户名(用户的账户名唯一)
	*/
	// public function activeshow(){
	// 	$userid=I('request.userid');
	// 	$username=M('user')->where(array('user_id'=>$userid))->getField('user_name');
	// 	$this->assign('userid',$userid);
	// 	$this->assign('username',$username);
	// 	$this->display('Index/activelive');
	// }
	/*
	输入激活码,激活当前用户账号;ajax调用部分
	*/
	public function toactive(){
		$userid=$_SESSION['user_id'];
		$theid=I('request.touserid');//待激活用户ID
		$useractive=M('user')->where(array('user_id'=>$theid))->getField('is_active');
		if ($useractive=='1') {
			$this->ajaxReturn(['status' =>'0', 'message' =>'该账户已激活过,不可重复激活']);
		}
		$config=M('config')->find();
		if ($config['active_switch']=='0') {
			$this->ajaxReturn(['status' =>'0', 'message' =>'今日激活已满，请明日再来!']);
		}
		//查询当前推荐人当天已激活下家量,如果已达到7个,提示限量
		$map=[
			'user_parent' =>array('like','%'.$userid),
			'is_active' =>1,
		];
		$sumperson=M('user')->where($map)->where('to_days(user_active_time) = to_days(now())')->count();
		$canbeactive=M('config')->where('id=1')->getField('activeperson');
		if ($sumperson>=$canbeactive) {
			$this->ajaxReturn(['status' =>'0', 'message' =>'今日激活已满，请明日再来!']);
		}else{
			//检测用户是否有可用的验证码
			$isunused=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->count();
			if ($isunused<1) {//如果没有
				$this->ajaxReturn(['status' =>'0', 'message' =>'您的激活码不足,请充值激活码']);
			}else{
				$thedate=date('Y-m-d H:i:s',time());
				$activeuser=M('user')->where(array('user_id'=>$theid))->save(['is_active'=>1,'user_active_time'=>$thedate]);
				$canbeused=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->order('addtime asc')->find();
				$allisused=M('user_active_code')->where(array('id'=>$canbeused['id']))->setField('is_used','1');
				if ($activeuser&&$allisused) {
					//更新推荐下级的时间
	                $now = time();
	                M('User')->where(array('user_id'=>$userid))->setField('user_recomand_time',$now);
	                //用户累计推荐人数加一
	                $grantlowernumber=M('user')->where(array('user_id'=>$userid))->getField('grant_lower_number');
	                $grantlowernumber=$grantlowernumber+1;
	                M('user')->where(array('user_id'=>$userid))->setField('grant_lower_number',$grantlowernumber);
					//系统注册总人数加一,注册时已经加上人数了,激活就不用再加
					// $activenumber=M('config')->where('id=1')->getField('register_number');
					// $activenumber=$activenumber+1;
					// M('config')->where('id=1')->setField('register_number',$activenumber);
					$this->ajaxReturn(['status' =>'1', 'message' =>'用户账户激活成功!']);
				}else{
					$this->ajaxReturn(['status' =>'0', 'message' =>'用户账户激活失败!']);
				}
			}
		}
	}
	/*
	用户预约订单设置
	*/
	public function appointment(){
		$userid=session('user_id');
		$user=M('user');
		//检测用户开启预约订单状态/预约时间间隔/预约排单金额
		$open=$user->where(array('user_id'=>$userid))->find();
		//查询用户历史预约订单信息
		// $list=M('help_order')->distinct(true)->where(array('appointment'=>1,'user_id'=>$userid))->field('parent_id,addtime,parent_amount,matching')->order('addtime desc')->select();
		// foreach ($list as &$val) {
		// 	//获取原始订单拆分的子订单个数,为1时证明订单未拆分
		// 	$count=M('help_order')->where(array('parent_id'=>$val['parent_id']))->count();
		// 	$num1=0;  $num2=0;
		// 	$unfinished=M('help_order')->where(array('parent_id'=>$val['parent_id']))->field('matching')->select();
		// 	for ($i=0; $i < count($unfinished); $i++) { 
		// 		if ($unfinished[$i]==0) {
		// 			$num1=$num1+1;
		// 		}elseif ($unfinished[$i]==2) {
		// 			$num2=$num2+1;
		// 		}
		// 	}
		// 	if ($num1==$count) {//均都是未完成时,该订单就是未完成
		// 		$val['matching']=0;
		// 	}elseif ($num2==$count) {//均都是已完成时,该订单就已完成
		// 		$val['matching']=2;
		// 	}else{//否则就是交易中
		// 		$val['matching']=1;
		// 	}
		// }
		//根据时间间隔计算排单日,并显示排单状态
		if ($open['appoint']==1) {//开启时,计算间隔日期
			//根据开启时间,找到开启时间之前最近的一次买入时间,以此为基础进行间隔日期计算
			$open['open_appoint']=date('Y-m-d H:i:s',$open['open_appoint']);
			$map=[
				'addtime'=>['lt',$open['open_appoint']],
			];
			$lasttime=M('HelpOrder')->where($map)->where(array('user_id'=>$userid))->order('addtime desc')->getField('addtime');
			//最多十个
			$baseday="";
			for ($i=0; $i < 11; $i++) {
				if (empty($baseday)) {//第一个日期
				 	$list[$i]['createday']=strtotime($lasttime)+$open['appoint_day']*24*3600;
				 	$list[$i]['createmoney']=$open['appoint_money'];
				 	if ($list[$i]['createday']<=time()) {
				 		$list[$i]['createstatus']=1;
				 	}else{
				 		$list[$i]['createstatus']=2;
				 	}
				 	$baseday=$list[$i]['createday'];//时间戳
				}else{
					$list[$i]['createday']=$baseday+$open['appoint_day']*24*3600;
					$list[$i]['createmoney']=$open['appoint_money'];
					if ($list[$i]['createday']<=time()) {
				 		$list[$i]['createstatus']=1;
				 	}else{
				 		$list[$i]['createstatus']=2;
				 	}
				 	$baseday=$list[$i]['createday'];//时间戳
				}
			}
		}
		$this->assign('open',$open);
		$this->assign('list',$list);
		$this->display('Index/reserve');
	}
	/*
	ajax调用,预约状态开启/关闭操作
	*/
	public function opencolseappoint(){
		$userid=$_SESSION['user_id'];
		$user=M('user');
		$openstate=$user->where(array('user_id'=>$userid))->getField('appoint');
		if ($openstate==1) {//开启改为关闭
			$user->where(array('user_id'=>$userid))->setField('appoint','0');
			$this->ajaxReturn(['status'=>'2','message'=>'']);
		}else{//关闭改为开启
			//先查询10天之内用户有没有拍单,没拍单就不能开启
			$listdaytime=date('Y-m-d H:i:s',time()-10*24*3600);
			$where['addtime']=array('gt',$listdaytime);
			if (!M('HelpOrder')->where($where)->where(array('user_id'=>$userid))->find()) {
				$this->ajaxReturn(['status'=>'3','message'=>'您10天之内未排单,暂不可开启预约排单']);
			}
			//最初开启未选择时间间隔时,默认七天一单,一单1000元
			$user->where(array('user_id'=>$userid))->setField('appoint','1');
			$user->where(array('user_id'=>$userid))->setField('open_appoint',time());
			if ($user->where(array('user_id'=>$userid))->getField('appoint_day')=='0') {
				$user->where(array('user_id'=>$userid))->save(['appoint_day'=>'7','appoint_money'=>'1000']);
			}
			//重新计算间隔日期
			$open=$user->where(array('user_id'=>$userid))->find();
			//根据开启时间,找到开启时间之前最近的一次买入时间,以此为基础进行间隔日期计算
			$open['open_appoint']=date('Y-m-d H:i:s',$open['open_appoint']);
			$map['addtime']=array('lt',$open['open_appoint']);
			$lasttime=M('HelpOrder')->where($map)->where(array('user_id'=>$userid))->order('addtime desc')->getField('addtime');
			//最多十个
			$baseday="";
			for ($i=0; $i < 11; $i++) {
				if (empty($baseday)) {//第一个日期
				 	$list[$i]['createday']=strtotime($lasttime)+$open['appoint_day']*24*3600;
				 	$list[$i]['createmoney']=$open['appoint_money'];
				 	if ($list[$i]['createday']<=time()) {
				 		$list[$i]['createstatus']=1;
				 	}else{
				 		$list[$i]['createstatus']=2;
				 	}
				 	$baseday=$list[$i]['createday'];//时间戳
				}else{
					$list[$i]['createday']=$baseday+$open['appoint_day']*24*3600;
					$list[$i]['createmoney']=$open['appoint_money'];
					if ($list[$i]['createday']<=time()) {
				 		$list[$i]['createstatus']=1;
				 	}else{
				 		$list[$i]['createstatus']=2;
				 	}
				 	$baseday=$list[$i]['createday'];//时间戳
				}
			}
			foreach ($list as &$val) {
				$val['createday']=date('Y-m-d',$val['createday']);
				if ($val['createstatus']==1) {
					$val['createstatus']='已执行';
				}else{
					$val['createstatus']='等待执行';
				}
			}
			$this->ajaxReturn(['status'=>1,'message'=>$list]);
		}
	}
	/*
	预约排单间隔和排单金额修改
	*/
	public function appointchange(){
		$userid=session('user_id');
		$interval=I('request.thenum');
		$money=I('request.themoney');
		$openstate=M('user')->where(array('user_id'=>$userid))->getField('appoint');
		if ($openstate==1) {//已经开启预约排单时
			if ($interval==0 || !$money || !is_int($money/1000)) {
				$this->ajaxReturn(['status'=>2,'message'=>'请选择间隔天数,且预约金额必须是1000的整数倍!']);
				die;
			}
			//判断修改的天数是否合格
			$open=M('user')->where(array('user_id'=>$userid))->find();
			$open['open_appoint']=date('Y-m-d H:i:s',$open['open_appoint']);
			$map['addtime']=array('lt',$open['open_appoint']);
			$lasttime=M('HelpOrder')->where($map)->where(array('user_id'=>$userid))->order('addtime desc')->getField('addtime');
			if (strtotime($lasttime)+$interval*24*3600<time()) {//更新的排单日期低于当前时间时,不让修改
				$this->ajaxReturn(['status'=>2,'message'=>'您最近一次排单时间为'.$lasttime.'暂不可选择此间隔段进行预约排单!']);
			}
			$rel1=M('user')->where(array('user_id'=>$userid))->setField('appoint_day',$interval);
			$rel2=M('user')->where(array('user_id'=>$userid))->setField('appoint_money',$money);
			if ($rel1 || $rel2) {
				$newopen=M('user')->where(array('user_id'=>$userid))->find();
				//从新计算预约排单日期
				//最多十个
				$baseday="";
				for ($i=0; $i < 11; $i++) {
					if (empty($baseday)) {//第一个日期
					 	$list[$i]['createday']=strtotime($lasttime)+$newopen['appoint_day']*24*3600;
					 	$list[$i]['createmoney']=$newopen['appoint_money'];
					 	if ($list[$i]['createday']<=time()) {
					 		$list[$i]['createstatus']=1;
					 	}else{
					 		$list[$i]['createstatus']=2;
					 	}
					 	$baseday=$list[$i]['createday'];//时间戳
					}else{
						$list[$i]['createday']=$baseday+$newopen['appoint_day']*24*3600;
						$list[$i]['createmoney']=$newopen['appoint_money'];
						if ($list[$i]['createday']<=time()) {
					 		$list[$i]['createstatus']=1;
					 	}else{
					 		$list[$i]['createstatus']=2;
					 	}
					 	$baseday=$list[$i]['createday'];//时间戳
					}
				}
				foreach ($list as &$val) {
					$val['createday']=date('Y-m-d',$val['createday']);
					if ($val['createstatus']==1) {
						$val['createstatus']='已执行';
					}else{
						$val['createstatus']='等待执行';
					}
				}
				$this->ajaxReturn(['status'=>1,'message'=>'修改成功!','information'=>$list]);
			}else{
				$this->ajaxReturn(['status'=>2,'message'=>'修改失败!']);
			}
		}else{//未开启时,不能修改
			$this->ajaxReturn(['status'=>2,'message'=>'预约排单功能暂未开启,无法修改!']);
		}
	}

	// 个人资料
	public function personalData(){
		echo '个人资料';
        $uid=session('user_id');
        $userinfo=M('user')->where(array('user_id'=>$uid))->find();

        $aliinfo=M('user_ali_number')->where(array('user_id'=>$uid))->find();

        $idcardinfo=M('user_idcard')->where(array('user_id'=>$uid))->find();

        $this->assign('userinfo',$userinfo);
        $this->assign('aliinfo',$aliinfo);
        $this->assign('idcardinfo',$idcardinfo);
        $status=$userinfo['info_perfected'];
        if ($status==1){
            $this->display('Personal/personal_data');
        }else{
            $this->display('Personal/updataPersonal_data');
        }

	}
    //修改个人资料
    public function updataPersonalData(){
        echo '个人资料';
        $uid=session('user_id');
        $userinfo=M('user')->where(array('user_id'=>$uid))->find();
        $aliinfo=M('user_ali_number')->where(array('user_id'=>$uid))->find();
        $idcardinfo=M('user_idcard')->where(array('user_id'=>$uid))->find();
        $this->assign('userinfo',$userinfo);
        $this->assign('aliinfo',$aliinfo);
        $this->assign('idcardinfo',$idcardinfo);
        $this->display('Personal/updataPersonal_data');
    }
    //保存个人资料 只能修改一次 同一个支付宝 银行账号 只能绑定一个账号
    public function savePersonalData(){
//        echo '保存个人资料';
        $u=M('user');
        $uid=session('user_id');
        if (IS_POST) {
            $user_name=I('post.user_name');//用户名
            $id_card=I('post.id_card');//银行卡号
            $user_phone=I('post.user_phone');//手机号
            $card_kaihu=I('post.card_kaihu');//开户行
            $ali_num=I('post.ali_num');//支付宝
            $user_truename=I('post.user_truename');//真实姓名
            $user_sex=I('post.user_sex');//性别
            $user_secpwd_old=I('post.user_secpwd_old');//密码
            $user_secpwd=I('post.user_secpwd');//确认密码
            $rules = array(
                array('user_name','require','用户名不能为空!'),
                array('user_name','','用户名已经存在！',0,'unique',1),
                array('user_phone', 'require', '手机号不能为空！'),
                array('user_phone', '/(^1[3|4|5|7|8|9|6][0-9]{9}$)/', '请输入正确的手机号码！',0,'regex'),
                array('id_card', 'require', '银行卡号不能为空！'),
                array('id_card', '/([\d]{4})([\d]{4})([\d]{4})([\d]{4})([\d]{0,})?/', '请输入正确的银行卡号！',0,'regex'),
                array('ali_num', 'require', '支付宝号不能为空！'),
                array('ali_num', '/^(?:\w+\.?)*\w+@(?:\w+\.)+\w+|\d{9,11}$/', '请输入正确的支付宝号！',0,'regex'),
                array('user_truename','require','真实姓名不能为空!'),
                array('card_kaihu','require','开户行名称不能为空!'),
                array('user_sex','require','性别!'),
            );
            if($u->validate($rules)->create()==false){
                $this->error($u->getError());
            }

            if ($user_secpwd_old!=$user_secpwd){
                $this->ajaxreturn(['status'=>'0','info'=>'两次密码不一样']);
            }

            //开始事务
            $m = M();
            $m->startTrans();
            try {
                $user['user_secpwd'] = md5($user_secpwd_old);
                $user['user_name'] = $user_name;
                $user['user_phone'] = $user_phone;
                $user['user_truename'] = $user_truename;
                $user['user_sex'] = $user_sex;
                $user['info_perfected'] = 1;
                $rules1=M('user')->where(array('user_id'=>$uid))->data($user)->save();
                $ali['ali_num']=$ali_num;
                $ali['name']=$user_truename;
                $ali['user_id']=$uid;
                $ali['add_time']=time();
                $ali['del']=0;
                $rules2=M('user_ali_number')->data($ali)->add();
                $idcard['card_kaihu']=$card_kaihu;
                $idcard['id_card']=$id_card;
                $idcard['user_id']=$uid;
                $idcard['user_truename']=$user_truename;
                $idcard['add_time']=time();
                $idcard['del']=0;
                $rules3=M('user_idcard')->data($idcard)->add();
                $m->commit();
            } catch (PDOException $exc) {
                //事务回滚
                $m->rollback();
            }

            if ($rules1  && $rules2  && $rules3 ) {
                $this->ajaxreturn(['status'=>'1','info'=>'修改成功']);
            }else{
                $this->ajaxreturn(['status'=>'0','info'=>'修改失败']);
            }
        }

    }
	//激活码
	public function cdkey(){
		echo '激活码';
		$this->display('Personal/cdkey');
	}
}
?>