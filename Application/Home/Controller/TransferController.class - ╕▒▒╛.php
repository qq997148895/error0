<?php
namespace Home\Controller;

use Common\Controller\HomeBaseController;
use Think\Controller;
class TransferController extends HomeBaseController
{
	//宝石转让
    public function transfer(){
    	$M = M('wallet');
	    $me = session('user_id');
	    $userinfo=M('user')->where(array('user_id'=>$me))->find();
	    $activecode=M('user_active_code');
		$count=$activecode->where(array('user_id'=>$me,'is_used'=>'0'))->count();//邀请码剩余数量
    	if(IS_POST){
	    	$post = I('post.');
	    	//我的数据
	    	$where = array(['u.user_id'=>$me]);
	    	$medata = $M->alias('w')
	    			->join('mf_user u ON w.user_id = u.user_id')
	    			->where($where)
	    			->find();
	    	// $rules = array(
	    	// 	array('user_name','require','用户名不能为空！'),
	    	// 	array('num','require','宝石币数量不能为空！'),
	    	// 	array('user_secpwd','require','交易密码不能为空!')
	    	// );
	    	// if($data->validate($rules)->create()==false){
	    	// 	$this->ajaxreturn(['status'=>'0','message'=>$M->getError()]);
	    	// }
	    	if($post['user_name']==''){
				$this->ajaxreturn(['status'=>'0','message'=>'用户名不能为空！']);
	    	}
	    	if($post['num']==''){
				$this->ajaxreturn(['status'=>'0','message'=>'宝石币数量不能为空！']);
	    	}
	    	if($post['user_secpwd']==''){
				$this->ajaxreturn(['status'=>'0','message'=>'交易密码不能为空！']);

	    	}
	    	$where = array(['u.user_name'=>$post['user_name']]);
	    	//转给人数据
	    	$data = $M->alias('w')
	    			->join('mf_user u ON w.user_id = u.user_id')
	    			->where($where)
	    			->find();
	    	if($post['user_name']!=$data['user_name']){
	    		$this->ajaxreturn(['status'=>'0','message'=>'该用户不存在！']);
	    	}else{
	    		if($post['user_name']==$me){
	    			$this->ajaxreturn(['status'=>'0','message'=>'宝石币不能转给自己！']);
	    		}
	    		if($post['num']>$medata['order_byte']){
	    			$this->ajaxreturn(['status'=>'0','message'=>'转让宝石币不得大于拥有宝石币']);
	    		}
	    		if($post['num']<=0){
	    			$this->ajaxreturn(['status'=>'0','message'=>'转让宝石币数不得少于等于0个']);
	    		}
	    		if($medata['user_secpwd']!=md5($post['user_secpwd'])){
	    			$this->ajaxreturn(['status'=>'0','message'=>'交易密码不正确']);
	    		}else{
	    			//我的宝石币
	    			$result=M('wallet')->where(array('user_id'=>$me))->setDec('order_byte',$post['num']);
	    			$result2=M('wallet')->where(array('user_id'=>$data['user_id']))->setInc('order_byte',$post['num']);
	    			$bite = M('user_bite_transfer');
	    			$transfer['from_user_name'] = $userinfo['user_name'];
	    			$transfer['to_user_name'] = I('post.user_name');
	    			$transfer['number'] = I('post.num');
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
			$username=I('request.toname');//对方账户的账户名,根据账户名找对方的userid
			$count=I('request.tonumber');//邀请码转让数量
			$pass=I('request.topass');//安全密码
			if (!$username) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写用户名!']);
			}elseif (!$count) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写转出数量!']);
			}elseif (!$pass) {
				$this->ajaxReturn(['status' => '2', 'message' => '请填写安全密码!']);
			}else{
				//根据用户名查找对方id
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
			    			$transfer['to_user_name'] = $username;
			    			$transfer['number'] = $count;
			    			$transfer['addtime'] = time();
			    			$result3 = $Activation->add($transfer);
			    			if($result3){
			    				$this->ajaxReturn(['status' => '1', 'message' => '邀请码转让成功!']);
			    			}	
						}
					}else{
						$this->ajaxReturn(['status' => '2', 'message' => '安全密码填写错误!']);
					}
				}
			}
		}else{
			$this->display('Transfer/transfer');
		}
	}
}