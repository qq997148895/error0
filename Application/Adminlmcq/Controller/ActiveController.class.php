<?php
namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;


class ActiveController extends AdminlmcqBaseController {
  
    //大转盘设置
    public function turn(){
		$model = M('turn');
		$res = $model->find();
		if($res){
			$res['turn_num'] = json_decode($res['turn_num']);
			$res['turn_v'] = json_decode($res['turn_v']);
		}
		
		if(IS_POST){
			
			$data = I("post.");
			
			$data['turn_num'] = json_encode($data['turn_num']);
			$data['turn_v'] = json_encode($data['turn_v']);
			if($res){
				$model->where(array('id'=>$res['id']))->save($data);
				$res = $model->find();
				if($res){
					$res['turn_num'] = json_decode($res['turn_num']);
					$res['turn_v'] = json_decode($res['turn_v']);
				}
			}else{
				if(!empty($data)){
					$model->add($data);
				}
			}
            $this->success('修改成功!', '/Adminlmcq/active/turn', 3);
		}
		$this->assign('result',$res);
    	$this->display('active/turn');
    }
    
    //大转盘参与记录列表
    public function turn_log(){

        $data = I('post.user_name');
        $map=array();
        $list1=array();
        $user_arr = array();
        if ($data){
            $where['user_name']  = array('like', '%'.$data.'%');
            $where['user_phone']  = array('like','%'.$data.'%');
            $where['user_truename']  = array('like','%'.$data.'%');
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $list1 = M('user')->order('user_id')->where($map)->select();
        }

        $map2=array();
        if(!empty($list1)){
        	foreach ($list1 as $i => $val) {
        		$user_arr[]=$val['user_id'];
        	}
            $map2['uid']  = array('in', $user_arr);
        }


        $count = M('turn_log')->where($map2)->count();
        $p = getpage($count, 10);
        $list =  M('turn_log')->where($map2)->limit($p->firstRow, $p->listRows)->order('addtime desc')->select();

        foreach($list as $k=>$v){
        	$user = M('user')->where(array('user_id'=>$v['uid']))->find();
        	$user_name='';
        	if($user){
        	    $user_name=$user['user_name'];
        	}
            $list[$k]['user_name'] =  $user_name;
        }

        $this->assign('page', $p->show());
        $this->assign('list',$list);
        $this->display("active/turn_log");

    }

    //大竞猜设置
    public function cai(){
		$model = M('cai');
		$res = $model->find();
        $tongji=$this->tongji();
		if(IS_POST){
			$data = I("post.");
			if($res){
				$send_info=array(
					'send_nums'=>0,
					'send_money'=>0,
                    'huishou_money'=>0,
					);
				$need_updata=0;
				//判断是否需要更新中奖信息
				if(($data['cai_num1']<>$res['cai_num1'])||($data['cai_num2']<>$res['cai_num2'])||($data['cai_num3']<>$res['cai_num3'])||($data['cai_num4']<>$res['cai_num4'])){
					$friday = strtotime("Sunday");//本周五开始时间
			    	$time1=date("Y-m-d 20:30:00",$friday);
			    	$time2=strtotime($time1)-3600;

					if(time()>=$time2 && time()<=strtotime($time1)){
					    $need_updata=1;
					}else{
                        $this->success('不到公布竞猜时间，禁止更新中奖号码信息!', '/Adminlmcq/active/cai', 3);
                        exit();
					}
				}
				$model->where(array('id'=>$res['id']))->save($data);
				$res = $model->find();

				//更新竞猜用户信息
                if($need_updata){
                	$send_info=$this->up_cai_log($res['cai_num1'],$res['cai_num2'],$res['cai_num3'],$res['cai_num4'],$res['all_money'],$res['id']);
                }

				if($send_info['send_nums']){
                    $this->success('修改成功,本期中奖'.$send_info['send_nums'].'人，累计发放奖励'.$send_info['send_money'].'熊猫!', '/Adminlmcq/active/cai', 5);
				}else{
                    $this->success('修改成功!', '/Adminlmcq/active/cai', 3);
				}
			}else{
				if(!empty($data)){
					$model->add($data);
                    $this->success('添加成功!', '/Adminlmcq/active/cai', 3);
				}
			}
		}

		$this->assign('tongji0',$tongji[0]);
		$this->assign('tongji1',$tongji[1]);
		$this->assign('tongji2',$tongji[2]);
		$this->assign('tongji3',$tongji[3]);
		$this->assign('tongji4',$tongji[4]);

		$this->assign('result',$res);
    	$this->display('active/cai');
    }
    

    //大竞猜参与记录列表
    public function cai_log(){
    	$isok=array('未中奖','已中奖','已发放');
        $map2=array();
        $count = M('cai_log')->where($map2)->count();
        $p = getpage($count, 20);
        $list =  M('cai_log')->where($map2)->limit($p->firstRow, $p->listRows)->order('addtime desc')->select();

        foreach($list as $k=>$v){
        	$user = M('user')->where(array('user_id'=>$v['uid']))->find();
        	$user_name='';
        	if($user){
        	    $user_name=$user['user_name'];
        	}
            $list[$k]['user_name'] =  $user_name;
            $list[$k]['isok'] =  $isok[$v['isok']];
        }

        $this->assign('page', $p->show());
        $this->assign('list',$list);
        $this->display("active/cai_log");
    }


    //大竞猜当前各位竞猜比例
    public function tongji(){
		$lastFri = strtotime("last Sunday");//上周五开始时间
		$friday = strtotime("Sunday");//本周五开始时间
    	$time1=date("Y-m-d 20:30:00",$lastFri);
    	$time2=date("Y-m-d 20:30:00",$friday);
        $map['addtime'] = array('between', array(strtotime($time1), strtotime($time2)));

        $list =  M('cai_log')->where($map)->select();	

        $data=array(
        	'0'=>0,
        	'1'=>array(
        		'0'=>0,
        		'1'=>0,
        		'2'=>0,
        		'3'=>0,
        		'4'=>0,
        		'5'=>0,
        		'6'=>0,
        		'7'=>0,
        		'8'=>0,
        		'9'=>0,
        		),
        	'2'=>array(
        		'0'=>0,
        		'1'=>0,
        		'2'=>0,
        		'3'=>0,
        		'4'=>0,
        		'5'=>0,
        		'6'=>0,
        		'7'=>0,
        		'8'=>0,
        		'9'=>0,
        		),
        	'3'=>array(
        		'0'=>0,
        		'1'=>0,
        		'2'=>0,
        		'3'=>0,
        		'4'=>0,
        		'5'=>0,
        		'6'=>0,
        		'7'=>0,
        		'8'=>0,
        		'9'=>0,
        		),
        	'4'=>array(
        		'0'=>0,
        		'1'=>0,
        		'2'=>0,
        		'3'=>0,
        		'4'=>0,
        		'5'=>0,
        		'6'=>0,
        		'7'=>0,
        		'8'=>0,
        		'9'=>0,
        		),
        );

        if(!empty($list)){
        	$data[0]=count($list);
        	foreach ($list as $i => $val) {
	        	for($m=1;$m<=4;$m++){
	        		$cai='cai_num'.$m;
	        		$data[$m][$val[$cai]]++;
	        	}
        	}
        }

        return $data;
    }


    public function up_cai_log($cai_num1,$cai_num2,$cai_num3,$cai_num4,$all_money,$id){
        $settings = include(APP_PATH . '/../Application/Common/Conf/settings.php');
        $all_money2=$all_money*$settings['cai_send_pre']/100;//计算实际需要发放的最大总金额
        $all_money3=$all_money-$all_money2;//计算系统回收

		$lastFri = strtotime("last Sunday");//上周五开始时间
		$friday = strtotime("Sunday");//本周五开始时间
    	$time1=date("Y-m-d 20:30:00",$lastFri);
    	$time2=date("Y-m-d 20:30:00",$friday);
    	$map['isok']=0;
        $map['addtime'] = array('between', array(strtotime($time1), strtotime($time2)));

        $list =  M('cai_log')->where($map)->select();	
		$send_info=array(
			'send_nums'=>0,
			'send_money'=>0,
            'huishou_money'=>0,
			);

        //各级中号码的人
        $win=array(
            '0'=>array(),
            '1'=>array(),
            '2'=>array(),
            '3'=>array(),
            '4'=>array(),
            );
		//奖金分成比例
		$per=array(
			'0'=>0,
			'1'=>0.1,
			'2'=>0.2,
			'3'=>0.3,
			'4'=>0.4,
			);

        if(!empty($list)){
        	foreach ($list as $i => $val) {
        		$dengji=0;
	        	for($m=1;$m<=4;$m++){
	        		$cai='cai_num'.$m;
	        		if($val[$cai]==$$cai){
	        			$dengji++;
	        		}
	        	}
                $win[$dengji][]=$val['id'];
        	}

            //各等级奖励发放
            for($n=1;$n<=4;$n++){
                $win_list=$win[$n];
                if(!empty($win_list)){
                    $nums=count($win_list);//该等级中奖人数
                    foreach ($win_list as $j => $v) {
                        $send_money=(int)($all_money2*$per[$n]/$nums);
                        $send_money = $send_money?$send_money:1;//控制最少奖励一个
                        if($settings['cai_send_max']){
                            $send_money = ($send_money>$settings['cai_send_max'])?$settings['cai_send_max']:$send_money;//控制单注最高奖励
                        }
                        $send_info['send_nums']++;
                        $send_info['send_money']+=$send_money;
                        $data['get_money']=$send_money;
                        $data['isok']=1;
                        M('cai_log')->where(array('id'=>$v))->save($data);
                    }
                }
            }

            //系统奖池资金扣除
            $huishou_money=$send_info['send_money']+$all_money3;
            $real_money=$all_money-$huishou_money;//系统奖池剩余金额
            $real_money = $real_money>0?$real_money:0;

            $send_info['huishou_money']=$huishou_money;
            M('cai')->where(array('id'=>$id))->save(array('all_money'=>$real_money));

        }
            return $send_info;
    }

    //幸运夺宝
    public function lucky()
    {
        $trea=M('treasure')->order('id desc')->select();
        $this->assign('list',$trea);
        $this->display();
    }
    //添加幸运夺宝
    public function add_lucky()
    {
        if(IS_POST){
            $rules=array(
                array('goods_name','require','商品名称不能为空'),
                array('all_people','require','总人数不能为空'),
                array('pic','require','请上传略缩图'),
                array('status','require','请选择状态'),
                array('status',array('0','1','2'),'状态不正确',2,'in'),
                array('add_time','require','请选择时间')
            );
            $lucky=M('treasure');
            if(!$lucky->validate($rules)->create()){
                $this->error($lucky->getError());
            }else{
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     3145728 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
                // 上传单个文件
                $info   =   $upload->uploadOne($_FILES['pic']);
                if(!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                }else{// 上传成功 获取上传文件信息
                    $request=I('post.');
                   $path= $info['savepath'].$info['savename'];
                   $data['goods_name']= $request['goods_name'];
                   $data['pic']=$path;
                   $data['status']=$request['status'];
                   $data['add_time']=strtotime($request['add_time']);
                   $data['price']=$request['price'];
                   $data['lssue']=date('YmdH',time());
                   $data['category']=$request['category'];
                   $add=$lucky->add($data);
                   if(!$add){
                       $this->error('添加失败');
                   }
                       $this->success('添加成功');
                }
            }
        }else{
            $category=M('shop_leibie')->select();
            $this->assign('category',$category);
            $this->display();
        }

    }
    //修改幸运夺宝
    public function edit_lucky()
    {
        if (IS_POST) {
            $rules = array(
                array('goods_name', 'require', '商品名称不能为空'),
                array('all_people', 'require', '总人数不能为空'),
                array('status', 'require', '请选择状态'),
                array('status', array('0', '1', '2'), '状态不正确', 2, 'in'),
                array('add_time', 'require', '请选择时间'),
                array('id', 'require', '参数错误')
            );
            $lucky = M('treasure');
            if (!$lucky->validate($rules)->create()) {
                $this->error($lucky->getError());
            } else {
                if(!empty($_FILES['pic']['tmp_name'])){
                    $upload = new \Think\Upload();// 实例化上传类
                    $upload->maxSize = 3145728;// 设置附件上传大小
                    $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                    $upload->rootPath = './Uploads/'; // 设置附件上传根目录
                    // 上传单个文件
                    $info = $upload->uploadOne($_FILES['pic']);
                    if (!$info) {// 上传错误提示错误信息
                        $this->error($upload->getError());
                    }
                    $path = $info['savepath'] . $info['savename'];
                    $data['pic'] = $path;
                }
                $request = I('post.');
                $data['goods_name'] = $request['goods_name'];
                $data['status'] = $request['status'];
                $data['add_time'] = strtotime($request['add_time']);
                $data['price'] = $request['price'];
                $data['lssue'] = date('YmdH', time());
                $data['category'] = $request['category'];
                $add = $lucky->where(['id' => $request['id']])->save($data);
                if (!$add) {
                    $this->error('修改失败');
                }
                   return $this->success('修改成功');
            }
        }
        $id = I('get.id/d');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $category = M('shop_leibie')->select();
        $this->assign('category', $category);
        $treasure = M('treasure')->where(['id' => $id])->find();
        $this->assign('edit', $treasure);
        $this->display();
    }
    //开奖
    public function lottery()
    {
        $id=I('get.id');
       $user_list= M('treasure_order as t')
            ->join('mf_user as u on u.user_id=t.user_id')
            ->where(['t.trea_id'=>$id])
            ->select();
       $this->assign('list',$user_list);
        $this->display();
    }
    //指定中奖
    public function lotter_user()
    {
        $id=I('get.id/d');
        $user_id=I('get.userid/d');
        if(empty($id)||empty($user_id)){
            $this->error('参数错误');
        }
       $trea_id= M('treasure_order')->where(['id'=>$id])->getField('trea_id');
        $treasure_status=M('treasure')->where(['id'=>$trea_id])->getField('status');
        if($treasure_status!=0){
            $this->error('活动已结束或已开奖');
        }
        M('treasure_order')->where(['id'=>$id,'user_id'=>$user_id])->save(['status'=>1]);
        M('treasure')->where(['id'=>$trea_id])->save(['status'=>1]);
        $this->success('设置中奖成功');
    }

}