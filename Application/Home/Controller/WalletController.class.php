<?php

namespace Home\Controller;
use Common\Controller\HomeBaseController;
//用于大转盘ajax请求钱包
class WalletController extends HomeBaseController{
	/**
	 * 钱包
	 */
	public function userWallet(){
		$user_id = session('user_id');
		$wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
		$this->assign('wallet',$wallet);
		$this->display('Wallet/wallet');
	}


	/**
	 * 购买拍单币
	 */
	public function buyOrderBite(){
		$user_id = session('user_id');
		$user = M('User')->where(['user_id'=>$user_id])->find();
		if(IS_POST){
			$data = I('post.');
			if(empty($data['amount'])){
				$this->ajaxReturn(array('status' => '0', 'message' => '买入数量不能为空!'));
				die();
			}
			if($data['amount']<50){
				$this->ajaxReturn(array('status' => '0', 'message' => '买入数量必须大于或等于50!'));
				die();
			}
			if(($data['amount']%10)!=0){
				$this->ajaxReturn(array('status' => '0', 'message' => '买入数量必须是10的倍数!'));
				die();
			}
			if($data['amount']!=$data['price']){
				$this->ajaxReturn(array('status' => '0', 'message' => '价格不正确!'));
				die();
			}
			//图片上传
			if($_FILES['file']['error']==0){
				//上传图片成功
				$filename2 = $_FILES['file'];
				$result = upload($filename2);
				if($result['status']==0){
					$this->ajaxReturn(array('status' => '0', 'message' => $result['message']));
					die();
				}
				$data['img_evidence'] = $result['message'];
			}

			$order_log['user_id'] = $user_id;
			$order_log['user_name'] = $user['user_name'];
			$order_log['user_phone'] = $user['user_phone'];
			$order_log['number'] = $data['amount'];
			$order_log['price'] = $data['price'];
			$order_log['img_evidence'] = $data['img_evidence'];
			$order_log['addtime'] = time();
			$res = M('UserBiteOrder')->data($order_log)->add();
			if($res){
				$this->ajaxReturn(array('status' => '1', 'message' => '提交成功,待后台审核!'));
				die();
			}else{
				$this->ajaxReturn(array('status' => '0', 'message' => '提交失败!'));
				die();
			}
		}else{
			$msg = I('get.msg');
			$this->assign('msg',$msg);//错误信息
			$config = $this->config;
			$code = $config['sys_gain_code'];//平台二维码
			$this->assign('code',$code);
			$this->display('Index/single_cur_buy');
		}
	}



	/**
	 * 红心值兑换排单币
	 */
	public function redChangeBite(){
		$user_id = session('user_id');
		$user = M('User')->where(['user_id'=>$user_id])->find();
		$wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
		if(IS_POST){
			$data = I('post.');
			if(empty($data['amount'])){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量不能为空!'));
				die();
			}
			if($data['amount']<10){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量必须大于等于10!'));
				die();
			}
			if(($data['amount']%10)!=0){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量必须是10的倍数!'));
				die();
			}
			if($data['amount']>$wallet['red_amount']){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量超出!'));
				die();
			}
			$m = M();
			$m->startTrans();
			try{
				//该用户减少红心值
				M('Wallet')->where(['user_id'=>$user_id])->setDec('red_amount',$data['amount']);
				$log = array(
						'user_id'=>$user_id,
						'user_name'=>$user['user_name'],
						'user_phone'=>$user['user_phone'],
						'amount'=>'-'.$data['amount'],
						'remain_amount'=>$wallet['red_amount']-$data['amount'],
						'change_date'=>time(),
						'log_note'=>'红心值兑换排单币',
						'wallet_type'=>4
				);
				M('WalletLog')->data($log)->add();
				//该用户增加排单币
				M('Wallet')->where(['user_id'=>$user_id])->setInc('order_byte',$data['order_bite']);
				$log2 = array(
						'user_id'=>$user_id,
						'user_name'=>$user['user_name'],
						'user_phone'=>$user['user_phone'],
						'amount'=>$data['order_bite'],
						'remain_amount'=>$wallet['order_byte']+$data['order_bite'],
						'change_date'=>time(),
						'log_note'=>'红心值兑换排单币',
						'wallet_type'=>2
				);
				M('WalletLog')->data($log2)->add();
				$m->commit();
			}catch (\PDOException $e){
				$m->rollback();
			}
			$this->ajaxReturn(array('status' => '1', 'message' => '兑换成功!'));
			die();
		}else{
			$wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
			$this->assign('red_amount',$wallet['red_amount']);
			$this->display('Index/red_heart');
		}
	}

	/**
	 * 静态或动态钱包详情
	 */
	public function walletDetail(){
		$type = I('get.type');
		$user_id = session('user_id');
		$wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
		$wallet_log = M('WalletLog')
				->where(['user_id'=>$user_id,'wallet_type'=>$type])
				->order('change_date desc')
				->select();
		$static = $wallet['static_amount'] + $wallet['static_amout2'];
		$this->assign('static', $static);
		$this->assign('wallet_log',$wallet_log);
		$this->assign('wallet',$wallet);
		if($type==5){
			//静态钱包页面
			$this->display('Index/static_purse');
		}
		if($type==6){
			//动态钱包页面
			$this->display('Index/dynamic_wallet');
		}
		if($type==3){
			//计算用户可卖出的倍增钱包金额,总金额-正在生息的累加和
			$time = time();
			$count = M('DoubleLog')
					->where("end_time > $time")//正在生息
					->sum('grant_amount_interest');
			$active_amount = $wallet['double_amount'] - $count;
			$this->assign('double_active',$active_amount);
			//倍增钱包页面
			$this->display('Index/multiplier_purse');
		}

	}

	/**
	 * 动态钱包转入到倍增钱包
	 */
	public function changeToDouble(){
		$user_id = session('user_id');
		$user = M('User')->where(['user_id'=>$user_id])->find();
		$wallet = M('Wallet')->where(['user_id'=>$user_id])->find();
		if(IS_POST){
			$data = I('post.');
			if(empty($data['amount'])){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量不能为空!'));
				die();
			}
			if(empty($data['sec_pwd'])){
				$this->ajaxReturn(array('status' => '0', 'message' => '交易密码不能为空!'));
				die();
			}
			if($data['amount']<500){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量必须大于等于500!'));
				die();
			}
			if(($data['amount']%100)!=0){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量必须是100的倍数!'));
				die();
			}
			if($data['amount']>$wallet['change_amount']){
				$this->ajaxReturn(array('status' => '0', 'message' => '数量超出!'));
				die();
			}
			if(md5($data['sec_pwd'])!=$user['user_secpwd']){
				$this->ajaxReturn(array('status' => '0', 'message' => '支付密码不正确!'));
				die();
			}
			if($wallet['change_is_freeze']==1){
				$this->ajaxReturn(array('status' => '0', 'message' => '动态钱包已经冻结!'));
				die();
			}
			$m = M();
			$m->startTrans();
			try{
				//该用户减动态钱包
				M('Wallet')->where(['user_id'=>$user_id])->setDec('change_amount',$data['amount']);
				$log = array(
						'user_id'=>$user_id,
						'user_phone'=>$user['user_phone'],
						'amount'=>'-'.$data['amount'],
						'remain_amount'=>$wallet['change_amount']-$data['amount'],
						'change_date'=>time(),
						'log_note'=>'动态钱包转入倍增钱包',
						'wallet_type'=>6
				);
				M('WalletLog')->data($log)->add();
				//该用户增加倍增钱包
				M('Wallet')->where(['user_id'=>$user_id])->setInc('double_amount',$data['amount']);
				$log2 = array(
						'user_id'=>$user_id,
						'user_phone'=>$user['user_phone'],
						'amount'=>$data['amount'],
						'remain_amount'=>$wallet['double_amount']+$data['amount'],
						'change_date'=>time(),
						'log_note'=>'动态钱包转入倍增钱包',
						'wallet_type'=>3
				);
				M('WalletLog')->data($log2)->add();
				$log3 = array(
					'user_id'=>$user_id,
						'amount'=>$data['amount'],
						'add_time'=>time(),
						'end_time'=>time()+30*86400
				);
				M('DoubleLog')->data($log3)->add();
				$m->commit();
			}catch (\PDOException $e){
				$m->rollback();
			}
			$this->ajaxReturn(array('status' => '1', 'message' => '提交成功!'));
			die();
		}else{
			$this->assign('wallet',$wallet);
			$this->display('Index/dynamic_wallet_sell');
		}
	}

	//静态钱包
	public function StaticWallet(){
	    $uid = session('user_id');
        $user = M('wallet')->where(array('user_id'=>$uid))->find();
        $config=M('config')->find(1);
        $user['static_amount'] = $user['static_amount'] * $config['stock_price'];
        $this->assign('config',$config);
        $this->assign('user',$user);
		$this->display('Wallet/static_wallet');
	}

	//动态钱包
	public function DynamicWallet(){
        $uid = session('user_id');
        $user = M('wallet')->where(array('user_id'=>$uid))->find();
        $this->assign('user',$user);
		$this->display('Wallet/dynamic_wallet');
	}

	//冻结钱包
	public function FrozenWallet(){
        $userid = session('user_id');
        $freezelsit =[];
        $freezemoney = 0;
        //参数设置
        $config=M('config')->find(1);
        //冻结钱包
        $freeze = M('interest')->where(array('user_id'=>$userid,'status'=>1))->select();
        foreach ($freeze as &$v){
            $nowtime = time();
            $endtime = $v['addtime'] + $config['frozen_time'] * 86400;
            if($endtime > $nowtime){
                $freezemoney = $freezemoney + $v['allamount'];
                array_push($freezelsit,$v);
            }
        }
        $freezemoney = $freezemoney * $config['stock_price'];
        $this->assign('freeze',$freeze);
        $this->assign('freezelsit',$freezelsit);
        $this->assign('freezemoney',$freezemoney);
        $this->assign('config',$config);
		$this->display('Wallet/frozen_wallet');
	}

	//股权增值券
	public function MallWallet(){
        $uid = session('user_id');
        $user = M('wallet')->where(array('user_id'=>$uid))->find();
        $log = M('wallet_log')->where(array('user_id'=>$uid,'wallet_type'=>4))->select();
        $this->assign('log',$log);
        $this->assign('user',$user);
		$this->display('Wallet/mall_wallet');
	}

	//静态钱包明细
	public function staticWalletDetail(){
        $uid = session('user_id');
        $log = M('wallet_log')->where(array('user_id'=>$uid,'wallet_type'=>1))->order('id desc')->select();
        $this->assign('log',$log);
		$this->display('Wallet/static_wallet_detail');
	}

	//动态钱包明细
	public function DynamicWalletDetail(){
        $uid = session('user_id');
        $log = M('wallet_log')->where(array('user_id'=>$uid,'wallet_type'=>2))->order('id desc')->select();
        $this->assign('log',$log);
		$this->display('Wallet/dynamic_wallet_detail');
	}

	//转赠
    public function give(){
        $uid = session('user_id');
        $data = I('post.');
        $user = M('user')->where(array('user_id'=>$uid))->find();
        $touser = M('user')->where(array('user_phone'=>$data['phone']))->find();
        if(!$touser){
            $this->ajaxReturn(array('status'=>0,'message'=>'请输入正确的会员手机号'));
        }
        $wallet = M('wallet')->where(array('user_id'=>$uid))->find();
        if($data['num'] > $wallet['static_amount']){
            $this->ajaxReturn(array('status'=>0,'message'=>'股权钱包数量小于转赠数量，不能转赠'));
        }
        if(md5($data['thepass']) != $user['user_secpwd']) {
            $this->ajaxReturn(array('status' => '0', 'message' => '交易密码不正确!'));
            die();
        }
        //自己
        $wallet_log1 = array(
            'user_id' => $uid,
            'user_name' => $user['user_name'],
            'user_phone' => $user['user_phone'],
            'amount' => '-' . $data['num'],
            'old_amount' => $wallet['static_amount'],
            'remain_amount' => $wallet['static_amount'] - $data['num'],
            'change_date' => time(),
            'log_note' => '转赠股权',
            'wallet_type' => 1,
        );
        $result1 = M('WalletLog')->data($wallet_log1)->add();
        $res = M('wallet')->where(array('user_id'=>$uid))->setDec('static_amount',$data['num']);
        if(!$res){
            $this->ajaxReturn(array('status'=>0,'message'=>'扣除股权钱包余额失败，请重试'));
        }
        //接收的人
        $towallet = M('wallet')->where(array('user_id'=>$touser['user_id']))->find();
        $wallet_log1 = array(
            'user_id' => $touser['user_id'],
            'user_name' => $touser['user_name'],
            'user_phone' => $touser['user_phone'],
            'amount' => '+' . $data['num'],
            'old_amount' => $towallet['static_amount'],
            'remain_amount' => $towallet['static_amount'] + $data['num'],
            'change_date' => time(),
            'log_note' => '接收转赠股权',
            'wallet_type' => 1,
        );
        $result1 = M('WalletLog')->data($wallet_log1)->add();
        $res1 = M('wallet')->where(array('user_id'=>$touser['user_id']))->setInc('static_amount',$data['num']);
        if(!$res1){
            $this->ajaxReturn(array('status'=>0,'message'=>'增加股权钱包余额失败，请重试'));
        }else{
            $this->ajaxReturn(array('status'=>1,'message'=>'转赠成功'));
        }
    }
    //兑换
    public function exchange(){
        $uid = session('user_id');
        $data = I('post.');
        $user = M('user')->where(array('user_id'=>$uid))->find();
        $wallet = M('wallet')->where(array('user_id'=>$uid))->find();
        $config= M('config')->where(array('id'=>1))->find();
        if($user['user_secpwd'] !== md5($data['thepass'])){
            $this->ajaxReturn(array('status'=>0,'msg'=>'资金密码不正确，请重试'));
        }
        if($data['type'] == 1){//股对积分

            if($data['allmoney'] =='' ){
                $this->ajaxReturn(array('status'=>0,'msg'=>'兑换积分数量不能为空'));
            }
            if($data['allmoney'] <1 ){
                $this->ajaxReturn(array('status'=>0,'msg'=>'兑换积分数量必须大于1'));
            }
            if( $data['allmoney']/$config['stock_price'] * (1 + $config['convertible_equity'] / 100) > $wallet['static_amount']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'股权不足，不能兑换'));
            }
            $money = round($data['allmoney']/$config['stock_price'] * (1 + $config['convertible_equity'] / 100),2);
            $res = M('wallet')->where(array('user_id'=>$uid))->setDec('static_amount',$money);//股权减少
            $res1 = M('wallet')->where(array('user_id'=>$uid))->setInc('change_amount',$data['allmoney']);//积分增加
            //股权
            $wallet_log=M('wallet_log');
            $wallet_log_info['user_id']=$user['user_id'];//用户id
            $wallet_log_info['user_name']=$user['user_name'];//用户名
            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
            $wallet_log_info['amount']='-'.$money;//资金变动数量
            $wallet_log_info['old_amount']=$wallet['static_amount'];//原来余额
            $wallet_log_info['remain_amount']=$wallet['static_amount']-$money;//现在余额  （原来余额+ 资金变动）
            $wallet_log_info['change_date']=time();//变动时间
            $wallet_log_info['log_note']='股权兑换积分';//信息描述
            $wallet_log_info['wallet_type']=1;//变动类型  积分
            $wallet_log->add($wallet_log_info);
            //积分
            $wallet_log=M('wallet_log');
            $wallet_log_info['user_id']=$user['user_id'];//用户id
            $wallet_log_info['user_name']=$user['user_name'];//用户名
            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
            $wallet_log_info['amount']='+'.$data['allmoney'];//资金变动数量
            $wallet_log_info['old_amount']=$wallet['change_amount'];//原来余额
            $wallet_log_info['remain_amount']=$wallet['change_amount']+$data['allmoney'];//现在余额  （原来余额+ 资金变动）
            $wallet_log_info['change_date']=time();//变动时间
            $wallet_log_info['log_note']='股权兑换积分';//信息描述
            $wallet_log_info['wallet_type']=2;//变动类型  积分
            $wallet_log->add($wallet_log_info);
            if(!$res){
                $this->ajaxReturn(array('status'=>0,'msg'=>'股权钱包余额失败，请重试'));
            }else{
                $this->ajaxReturn(array('status'=>1,'msg'=>'兑换成功'));
            }
        }
        if($data['type'] == 2){

            if($data['allmoney'] =='' ){
                $this->ajaxReturn(array('status'=>0,'msg'=>'兑换股权数量不能为空'));
            }
            if($data['allmoney'] <1 ){
                $this->ajaxReturn(array('status'=>0,'msg'=>'兑换股权数量必须大于1'));
            }
            //首先判断兑换的积分够不够  股权数量*股权价值=积分
            if( $data['allmoney']*$config['stock_price'] * (1 + $config['convertible_equity'] / 100) > $wallet['change_amount']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'积分钱包不足，不能兑换'));
            }
            $money = round($data['allmoney']*$config['stock_price'] * (1 + $config['convertible_equity'] / 100),2);
            $res = M('wallet')->where(array('user_id'=>$uid))->setDec('change_amount',$money);//百分5的手续费 积分减少
            $res1 = M('wallet')->where(array('user_id'=>$uid))->setInc('static_amount',$data['allmoney']);//股权增加
//            dump($res);dump($res1);die;
            //股权
            $wallet_log=M('wallet_log');
            $wallet_log_info['user_id']=$user['user_id'];//用户id
            $wallet_log_info['user_name']=$user['user_name'];//用户名
            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
            $wallet_log_info['amount']='+'.$data['allmoney'];//资金变动数量
            $wallet_log_info['old_amount']=$wallet['static_amount'];//原来余额
            $wallet_log_info['remain_amount']=$wallet['static_amount']+$data['allmoney'];//现在余额  （原来余额+ 资金变动）
            $wallet_log_info['change_date']=time();//变动时间
            $wallet_log_info['log_note']='积分兑换股权';//信息描述
            $wallet_log_info['wallet_type']=1;//变动类型  积分
            $wallet_log->add($wallet_log_info);
            //积分
            $wallet_log=M('wallet_log');
            $wallet_log_info['user_id']=$user['user_id'];//用户id
            $wallet_log_info['user_name']=$user['user_name'];//用户名
            $wallet_log_info['user_phone']=$user['user_phone'];//手机号
            $wallet_log_info['amount']='-'.$money;//资金变动数量
            $wallet_log_info['old_amount']=$wallet['change_amount'];//原来余额
            $wallet_log_info['remain_amount']=$wallet['change_amount']-$money;//现在余额  （原来余额+ 资金变动）
            $wallet_log_info['change_date']=time();//变动时间
            $wallet_log_info['log_note']='积分兑换股权';//信息描述
            $wallet_log_info['wallet_type']=2;//变动类型  积分
            $wallet_log->add($wallet_log_info);
            if(!$res){
                $this->ajaxReturn(array('status'=>0,'msg'=>'扣除积分钱包余额失败，请重试'));
            }else{
                $this->ajaxReturn(array('status'=>1,'msg'=>'兑换成功'));
            }
        }
    }
    //提现
    public function tixian(){
        $uid = session('user_id');
        $data = I('post.');
        $user = M('user')->where(array('user_id'=>$uid))->find();
        $wallet = M('wallet')->where(array('user_id'=>$uid))->find();
        if($data['type'] == 1){
            if( $data['allmoney'] > $wallet['static_amount']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'股权钱包资小于提现金额，不能提现'));
            }
            $res = M('wallet')->where(array('user_id'=>$uid))->setDec('static_amount',$data['allmoney']);
            if(!$res){
                $this->ajaxReturn(array('status'=>0,'msg'=>'扣除股权钱包余额失败，请重试'));
            }
        }
        if($data['type'] == 2){
            if( $data['allmoney'] > $wallet['change_amount']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'积分钱包资小于提现金额，不能提现'));
            }
            $res = M('wallet')->where(array('user_id'=>$uid))->setDec('change_amount',$data['allmoney']);
            if(!$res){
                $this->ajaxReturn(array('status'=>0,'msg'=>'扣除积分钱包余额失败，请重试'));
            }
        }
        if($user['user_secpwd'] !== md5($data['thepass'])){
            $this->ajaxReturn(array('status'=>0,'msg'=>'资金密码不正确，请重试'));
        }
        if($data['allmoney'] < 2000 ){
            $this->ajaxReturn(array('status'=>0,'msg'=>'提现金额需大于2000'));
        }
        if($data['allmoney']%100 !== 0){
            $this->ajaxReturn(array('status'=>0,'msg'=>'提现金额必须是100的倍数'));
        }
        $count = M('help_order')->where(array('user_id'=>$uid,'matching'=>0))->count();
//        if(!$count){
//            $this->ajaxReturn(array('status'=>0,'msg'=>'提现时必须有一笔等待匹配的帮助订单'));
//        }
        $add['user_id'] = $uid;
        $add['user_name'] = $user['user_name'];
        $add['user_phone'] = $user['user_phone'];
        $add['amount'] = $data['allmoney'];
        $add['type'] = $data['type'];
        $add['add_time'] = time();
        $res = M('tixian_log')->add($add);
        if($res){
            $this->ajaxReturn(array('status'=>1,'msg'=>'申请提现时成功，请等待后台审核'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'申请提现时失败，请重新申请'));
        }
    }
}
