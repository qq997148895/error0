<?php
namespace Home\Controller;

use Common\Controller\HomeBaseController;
use Think\Controller;
class TransferController extends HomeBaseController
{

    //商家入驻
    public  function merchantEntry(){
        //首先判断用户是否是商家
        $userid = session('user_id');
        $userinfo=M('user')->where(array('user_id'=>$userid))->find();
        if ($userinfo['user_ismerchant']==1){
            //是则返回你已是商家
            $merchantinfo=M('merchant')->where(array('user_id'=>$userid))->find();
            $this->assign('userinfo',$userinfo);
            $this->assign('merchant',$merchantinfo);
            $this->display('Transfer/merchantinfo');

        }else{
            //不是商家  商家入驻需要显示入驻所需股权值，输入商家名称，点击成为商家按钮，线下付款，总后台审核，通过成为商家
            $stock_enter=M('config')->find();
            $this->assign('stock_enter',$stock_enter);
            $this->display('Transfer/merchantEntry');
        }
    }
    //申请入驻
    public  function apply(){
        $userid = session('user_id');
        $userinfo=M('user')->where(array('user_id'=>$userid))->find();
        $walletinfo=M('wallet')->where(array('user_id'=>$userid))->find();
        $config=M('config')->find();
        //首先判断股权制是否足够 不足够则不能成为商家代理
        if ($walletinfo['static_amount']>$config['stock_enter']){
            //可以成为代理
            //商家申请入驻 user表中的状态 （若审核不通过则改为0）  merchant 中的数据
            if (IS_POST) {
                $merchant_name=I('post.merchant_name');//商家名称
                if($merchant_name==''){//商家名称
                    $this->ajaxreturn(['status'=>'0','message'=>'商家名称不能为空！']);
                }

                $user['user_ismerchant'] = 1;
                $result1=M('user')->where(array('user_id'=>$userid))->data($user)->save();

                $merchant=M('merchant');
                $merchantinfo['user_id']=$userinfo['user_id'];//商家id
                $merchantinfo['merchant_name']=$merchant_name;//商家名称
                $merchantinfo['merchant_truename']=$userinfo['user_truename'];//商家真实姓名
                $merchantinfo['merchant_phone']=$userinfo['user_phone'];//商家手机号
                $merchantid=$this->gteCodeNum();
                $merchantinfo['merchant_id']=$merchantid;//商家编号
                $merchantinfo['merchant_status']=0;//审核状态待审核
                $merchantinfo['jointime']=date('Y-m-d',time());//入驻时间
                $result2 = $merchant->add($merchantinfo);
                if ($result1&&$result2){
                    //操作成功   提示 审核中  并且维护钱包日志表 钱包表
                    //维护钱包表
                    $news2['static_amount']=$walletinfo['static_amount']-$config['stock_enter'];
                    D('wallet')->where(array('user_id'=>$userid))->save($news2);
                    //维护钱包日志表  wallet_log
                    $wallet_log=M('wallet_log');
                    $wallet_log_info['user_id']=$userinfo['user_id'];//用户id
                    $wallet_log_info['user_name']=$userinfo['user_name'];//用户名
                    $wallet_log_info['user_phone']=$userinfo['user_phone'];//手机号
                    $wallet_log_info['amount']='-'.$config['stock_enter'];//资金变动数量
                    $wallet_log_info['old_amount']=$walletinfo['static_amount'];//原来余额
                    $wallet_log_info['remain_amount']=$walletinfo['static_amount']-$config['stock_enter'];//现在余额  （原来余额+ 资金变动）
                    $wallet_log_info['change_date']=time();//变动时间
                    $wallet_log_info['log_note']='入驻成为商家';//信息描述
                    $wallet_log_info['wallet_type']=1;//变动类型
                    $wallet_log->add($wallet_log_info);
                    $this->ajaxreturn(['status'=>'1','message'=>'审核中！']);
                }else{
                    //操作失败  请重试
                    $this->ajaxreturn(['status'=>'0','message'=>'操作失败，请重试！']);
                }


            }

        }else{
            //否则不可成为代理
            $this->ajaxreturn(['status'=>'0','message'=>'资金不足请充值！']);
        }



    }

    public function gteCodeNum() {
     return 'SJ'.time().rand(8);
    }

	//宝石转让
    public function transfer(){
    	$M = M('wallet');
	    $me = session('user_id');
	    $userinfo=M('user')->where(array('user_id'=>$me))->find();
	    $activecode=M('user_active_code');
		$count=$activecode->where(array('user_id'=>$me,'is_used'=>'0'))->count();//邀请码剩余数量
    	if(IS_POST){
    		$phone=I('request.phone');//对方账户的账户名,根据账户名找对方的userid
			$count=I('request.num');//邀请码转让数量
			$pass=I('request.user_secpwd');//安全密码
			$toname = M('user')->where(array('user_phone'=>$phone))->getField('user_name');
			//我的数据
	    	$where = array(['u.user_id'=>$me]);
	    	$medata = $M->alias('w')
	    			->join('mf_user u ON w.user_id = u.user_id')
	    			->where($where)
	    			->find();
	    	//验证输入数据
			if($phone==''){//此处为得到用户输入的手机号
				$this->ajaxreturn(['status'=>'0','message'=>'对方手机号不能为空！']);
	    	}
	    	if($count==''){
				$this->ajaxreturn(['status'=>'0','message'=>'宝石币数量不能为空！']);
	    	}
	    	if($pass==''){
				$this->ajaxreturn(['status'=>'0','message'=>'交易密码不能为空！']);
	    	}
	    	$where = array(['u.user_phone'=>$phone]);
	    	//转给人数据
	    	$data = $M->alias('w')
	    			->join('mf_user u ON w.user_id = u.user_id')
	    			->where($where)
	    			->find();
	    	//dump($data);die;
	    	if($phone!==$data['user_phone']){//验证用户输入的推荐人手机号
	    		$this->ajaxreturn(['status'=>'0','message'=>'该用户不存在！']);
	    	}else{
	    		if($phone==$medata['user_phone']){
	    			$this->ajaxreturn(['status'=>'0','message'=>'宝石币不能转给自己！']);
	    		}
	    		if($count>$medata['order_byte']){
	    			$this->ajaxreturn(['status'=>'0','message'=>'转让宝石币不得大于拥有宝石币']);
	    		}
	    		if($count<=0){
	    			$this->ajaxreturn(['status'=>'0','message'=>'转让宝石币数不得少于等于0个']);
	    		}
	    		if($medata['user_secpwd']!=md5($pass)){
	    			$this->ajaxreturn(['status'=>'0','message'=>'交易密码不正确']);
	    		}else{
	    			//我的宝石币
	    			$result=M('wallet')->where(array('user_id'=>$me))->setDec('order_byte',$count);
	    			$result2=M('wallet')->where(array('user_id'=>$data['user_id']))->setInc('order_byte',$count);
	    			$bite = M('user_bite_transfer');
	    			$transfer['from_user_name'] = $userinfo['user_name'];
	    			$transfer['to_user_name'] = $data['user_name'];
	    			$transfer['number'] = $count;
	    			$transfer['addtime'] = time();
	    			$result3 = $bite->add($transfer);
	    			if($result&&$result2&&$result3){
		    			$this->ajaxreturn(['status'=>'1','message'=>'宝石转让成功！']);
	    			}else{
	    				$this->ajaxreturn(['status'=>'0','message'=>'宝石转让失败！']);
	    			}
	    		}
	    	}
	    }else{
	    	$bytenum=M('wallet')->where(array('user_id'=>$me))->getField('order_byte');
	    	$this->assign('bytenum',$bytenum);
	    	$this->assign('count',$count);
	        $this->display('Transfer/transfer');
	    }
    }

    public function order_byte(){
    	echo '拍单币';
    	$this->display('Transfer/order_byte');
    }

    //宝石币/激活码转让记录
    public function transferRecord(){
    	$user_name = session('user_name');//session('user_name')
    	$M = M('user_bite_transfer');
    	$Activation = M('user_active_log');
    	if(empty($user_name)){
    		$this->ajaxreturn(['status'=>'0','message'=>'获取用户失败！']);
    	}
    	// $where = array(
    	// 	'from_user_name'=>$user_name,
    	// 	'to_user_name'=>$user_name
    	// );
    	//宝石币
    	$where['from_user_name'] = $user_name;
    	$where['to_user_name'] = $user_name;
    	$where['_logic'] = 'or';
    	$data = $M->where($where)->count();
    	if($data<=1){
    		$result = '';
    		$this->assign('result',$result);
    	}else{
    		$result = $M->where($where)->order('addtime DESC')->select();
    		foreach ($result as  &$val) {
    			$val['addtime']=date('Y-m-d',$val['addtime']);
    		}
    		$this->assign('result',$result);
    	}
    	//激活码
    	$here['from_user_name'] = $user_name;
    	$here['to_user_name'] = $user_name;
    	$here['_logic'] = 'or';
    	$data1 = $Activation->where($here)->count();
    	if($data1<=1){
    		$result1 = '';
    		$this->assign('result1',$result1);
    	}else{
    		$result1 = $Activation->where($where)->order('addtime DESC')->select();
    		//var_dump($result1);die;
    		foreach ($result1 as  &$val) {
    			$val['addtime']=date('Y-m-d',$val['addtime']);
    		}
    		$this->assign('result1',$result1);
    	}
    	$this->display('Transfer/transfer-record');
    }

    /*
	我的邀请码-转让
	*/
	public function Activation_transfer()
	{
		$userid=$_SESSION['user_id'];
		$activecode=D('user_active_code');
		$userinfo=M('user')->where(array('user_id'=>$userid))->find();
		//$count=$activecode->where(array('user_id'=>$userid,'is_used'=>'0'))->count();//邀请码剩余数量
		if (IS_POST) {
			$userid=$_SESSION['user_id'];
			$userphone=I('request.tophone');//对方账户的账户名,根据账户名找对方的userid
			$count=I('request.tonumber');//邀请码转让数量
			$pass=I('request.topass');//安全密码
			if (!$userphone) {
				$this->ajaxReturn(['status' => '0', 'message' => '请填写对方手机号!']);
			}elseif (!$count) {
				$this->ajaxReturn(['status' => '0', 'message' => '请填写转出数量!']);
			}elseif (!$pass) {
				$this->ajaxReturn(['status' => '0', 'message' => '请填写安全密码!']);
			}else{
				//根据用户名查找对方id
				$foruserid=M('user')->where(array('user_phone'=>$userphone))->getField('user_id');
				$touserinfo=M('user')->where(array('user_id'=>$foruserid))->find();
				//dump($touserinfo);die;
				$thepassword=M('user')->where(array('user_id'=>$userid))->getField('user_secpwd');//用户安全密码
				if (!$foruserid) {//找不到对方id时
					$this->ajaxReturn(['status' => '0', 'message' => '用户不存在!']);
				}else{
					if (md5($pass)==$thepassword) {//判断用户密码输入是否正确
						$allcount=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->count();
						if ($allcount<$count) {//邀请码剩余量不足
							$this->ajaxReturn(['status' => '0', 'message' => '账户邀请码剩余量不足!']);
						}else{
							//根据转让数量得到相应的行数据
							$data=M('user_active_code')->where(array('user_id'=>$userid,'is_used'=>'0'))->order('id DESC')->getField('code',true);
							for ($i=0; $i < $count; $i++) { 
								$rel['user_id']=$foruserid;
								$rel['code']=$data[$i];
								$rel['addtime']=time();
								M('user_active_code')->data($rel)->add();//给对方账户添加邀请码
								//根据用户id和code值,将相应的is_used值设置为1(已失效)				
								M('user_active_code')->where(array('user_id'=>$userid,'code'=>$data[$i]))->save(['is_used'=>'1']);
							}
							//记录转账记录
							$Activation = M('user_active_log');
			    			$transfer['from_user_name'] = $userinfo['user_name'];
			    			$transfer['to_user_name'] = $touserinfo['user_name'];
			    			$transfer['number'] = $count;
			    			$transfer['addtime'] = time();
			    			$result3 = $Activation->add($transfer);
			    			if($result3){
			    				$this->ajaxReturn(['status' => '1', 'message' => '邀请码转让成功!']);
			    			}	
						}
					}else{
						$this->ajaxReturn(['status' => '0', 'message' => '安全密码填写错误!']);
					}
				}
			}
		}else{
			$this->display('Transfer/transfer');
		}
	}
	//显示昵称
	public function Nickname(){
		$userid=$_SESSION ['user_id'];
		if (IS_POST) {
			$phone = I("request.phone");
			if(!empty($phone)){
				$user_name = M('user')->where(array('user_phone'=>$phone))->getField('user_name');
				if ($user_name) {
					$this->ajaxReturn(['status' => '1', 'message' => $user_name]);
				}else{
					$this->ajaxReturn(['status' => '0', 'message' => '']);
				}
			}						
			//$this->assign('user_truename',$user_truename);
		}
		$this->display('Transfer/transfer');
	}
}