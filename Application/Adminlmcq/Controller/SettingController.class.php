<?php

namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;
use Models\ConfigModel;
/*
 * 设置控制器
 * Author:chenmengchen
 * Date:2017/03/31
 */
class SettingController extends AdminlmcqBaseController
{
    /*
     * 涨幅率设置
     */
    public function setRate()
    {
        if(IS_POST){
            //获取接受的数据
            $data = I('post.');
            if($data['min_interest'] > $data['max_interest'] ){
                $this->error('最小涨幅率不能大于最大涨幅率！');
            }
            //查询币价
            //$jhc_price = $this->config['jhc_price'];
            $jhc_price=jhc_price();
            //构建数据
            $record['min_interest'] = $data['min_interest'];
            $record['max_interest'] = $data['max_interest'];
            $record['add_time'] = time()+60*60*24;
            $interest=rand($data['min_interest']*10,$data['max_interest']*10)/10;
            $current_jhc=$jhc_price*$interest/100+$jhc_price;
            $record['current_jhc_price']=$current_jhc;
            $morningTime = strtotime(date("Y-m-d"))+ 24*3600;
            $nextMorning = strtotime(date("Y-m-d")) + 48*3600-1;
            $todayISInterest = M('interest') -> where(['add_time'=>['between',$morningTime.','.$nextMorning]]) -> find();
            $config=new ConfigModel();
            if(empty($todayISInterest)){
                $config->editJhc($current_jhc);
                $result = M('interest')->add($record);
                $msg="添加成功！";
                $msg1="添加失败！";
            }else{
                $config->editJhc($current_jhc);
                $result = M('interest')->where(array('id'=>$todayISInterest['id']))->save($record);
                $msg="修改成功！";
                $msg1="修改失败！";
            }
            if($result){
                $this->success($msg);
            }else{
                $this->error($msg1);
            }
        }else{
            //获取最后一条数据
            $data = M('interest')->order('id desc')->limit(1)->find();
            $config_price=$this->config['jhc_price'];
            //分配数据
            $this->assign('data',$data);
            $this->assign('price',$config_price);
            $this->display('Setting/set_rate');
        }
    }

    //系统配置文件
    public function set_setting()
    {
        $settings = include( dirname( APP_PATH ) . '/Application/Common/Conf/settings.php' );
        if(IS_POST){
            if(isset( $_POST['close_start'])||isset( $_POST['close_end'])){
                $close_start=strtotime($_POST['close_start']);
                $close_end=strtotime($_POST['close_end']);
                if($close_start > $close_end){
                    $this->error('保存失败！请检查系统维护时间设置');
                }else{
                    $_POST['close_start']=$close_start;
                    $_POST['close_end']=$close_end;
                }
            }
            //获取接受的数据
            foreach( $settings as $k=>$v ){
                if( isset( $_POST[$k] ) ){
                    $settings[$k] = $_POST[$k];
                }
            }
            $file_length = file_put_contents( dirname( APP_PATH ) . '/Application/Common/Conf/settings.php', '<?php return ' . var_export( $settings, true ) . '; ?>' );
            
            if( $file_length ){
                $this->success('保存成功！');
            } else {
                $this->error('保存失败！请检查文件权限');
            }

        }else{
            foreach( $settings as $k=>$v ){
				if($k=='close_start' || $k=='close_end'){
					if(!empty($v)){
						$v=date('Y-m-d H:i:s',$v);
					}
				}
                $this->assign( $k, $v );
            }
            $this->display();
        }
    }
    //jhc币设置
    public function jhc_set()
    {
        if(IS_POST){
            $jhc=I('post.jhc_price');
            if(empty($jhc) || !is_numeric($jhc)){
                $this->error('单价为空或类型错误');
            }
            $time = strtotime(date('Y-m-d').' 23:59:59');
            $jhc_id=M('interest')->where('add_time <= '.$time)->order('id desc')->getField('id');
            $price=new ConfigModel();
            if(empty($jhc_id)){
                M('interest')->add(['current_jhc_price'=>$jhc,'add_time'=>time()]);
                $price->editJhc($jhc);
            }else{
                M('interest')->where(['id'=>$jhc_id])->save(['current_jhc_price'=>$jhc]);
            }
            $this->success('修改成功');
            exit;
        }
        $time = strtotime(date('Y-m-d').' 23:59:59');
        $jhc_price=M('interest')->where('add_time <= '.$time)->order('id desc')->getField('current_jhc_price');
        $this->assign('jhc_price',$jhc_price);
        $this->display();
    }
    //抽奖设置
    public function luckdraw(){
        $list=M('prize')->where('id=1')->find();
        // $list['prize_name']=explode(',', $list['prize_name']);
        // $list['prize_level']=explode(',', $list['prize_level']);
        if (IS_POST) {
            $rules=[
                ['prize_value','require','单次抽奖消耗金额不能为空'],
                ['prize_name1','require','一等奖奖品金额/名称不能为空'],
                ['prize_level1','require','一等奖获奖概率不能为空'],
                ['prize_name2','require','二等奖奖品金额/名称不能为空'],
                ['prize_level2','require','二等奖获奖概率不能为空'],
                ['prize_name3','require','三等奖奖品金额/名称不能为空'],
                ['prize_level3','require','三等奖获奖概率不能为空'],
                ['prize_name4','require','四等奖奖品金额/名称不能为空'],
                ['prize_level4','require','四等奖获奖概率不能为空'],
                ['prize_name5','require','五等奖奖品金额/名称不能为空'],
                ['prize_level5','require','五等奖获奖概率不能为空'],
                ['prize_name6','require','六等奖奖品金额/名称不能为空'],
                ['prize_level6','require','六等奖获奖概率不能为空'],
                ['prize_level7','require','七等奖获奖概率不能为空'],
            ];
            $config=M('prize');
            if(!$config->validate($rules)->create()){
                $this->error($config->getError());
            }elseif (!preg_match("/^[1-9][0-9]*$/", I('request.prize_value'))) {
                $this->error('单次抽奖消耗金额只能是正整数');
            }elseif(!is_numeric(I('request.prize_level1')) || I('request.prize_level1')<0 || I('request.prize_level1')>100){
                $this->error('一等奖获奖概率只能取0-100之间的数');
            }elseif (!is_numeric(I('request.prize_level2')) || I('request.prize_level2')<=0 || I('request.prize_level2')>100) {
                $this->error('二等奖获奖概率只能取0-100之间的数');
            }elseif (!is_numeric(I('request.prize_level3')) || I('request.prize_level3')<=0 || I('request.prize_level3')>100) {
                $this->error('三等奖获奖概率只能取0-100之间的数');
            }elseif(!is_numeric(I('request.prize_level4')) || I('request.prize_level4')<=0 || I('request.prize_level4')>100){
                $this->error('四等奖获奖概率只能取0-100之间的数');
            }elseif (!is_numeric(I('request.prize_level5')) || I('request.prize_level5')<=0 || I('request.prize_level5')>100) {
                $this->error('五等奖获奖概率只能取0-100之间的数');
            }elseif (!is_numeric(I('request.prize_level6')) || I('request.prize_level6')<=0 || I('request.prize_level6')>100) {
                $this->error('六等奖获奖概率只能取0-100之间的数');
            }elseif (!is_numeric(I('request.prize_level7')) || I('request.prize_level7')<=0 || I('request.prize_level7')>100) {
                $this->error('七等奖获奖概率只能取0-100之间的数');
            }else{
                $data = I('post.');
                $editCon=M('prize')->where('id=1')->save($data);
                if($editCon){
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }
        }else{
            $this->assign('list',$list);
            $this->display();
        }
    }
    //参数设置
    public function parameter()
    {
        if(IS_POST){
           $rules=[
               ['stock_give','require','股权增值券赠送比例不能为空'],
               ['stock_enter','require','商家入驻需要消耗的股权值不能为空'],
               ['stock_price','require','股权价值不能为空'],
               ['paidna_expend','require','买入消耗股权增值券比例不能为空'],
               ['paidan_max','require','排单上限不能为空'],
               ['paidan_divide','require','排单股权倍数不能为空'],
               ['pay_time_max','require','收款时间限制不能为空'],
               ['pay_time_min','require','提前打款有奖时间不能为空'],
               ['pay_time_award','require','提前打款奖励百分比不能为空'],
               ['reward_rate1','require','第一代推荐奖励不能为空'],
               ['reward_rate2','require','第二代推荐奖励不能为空'],
               ['reward_rate3','require','第三代动态奖励不能为空'],
               ['collect_time','require','卖出到账时间不能为空'],
               ['frozen_time','require','冻结时间不能为空'],
               ['convertible_equity','require','积分兑换股权扣除比例不能为空'],
               ['buy_goods','require','积分兑换商品扣除比例不能为空'],
               ['interest_price','require','卖出收益比例不能为空'],
               ['equity_ratio','require','注册单股权扣除比例不能为空'],
               ['repurchase_proportion','require','复购单商家得到比例不能为空'],
               ['draw','require','积分抢购扣除积分不能为空'],
           ];
            $config=M('config');
            if(!$config->validate($rules)->create()){
                $this->error($config->getError());
            }else{
                $data = I('post.');
                $editCon=M('config')->where('id=1')->save($data);
                if($editCon){
                    $add['time'] = date('y-m-d H:i:s',time());
                    $add['stock_price'] = $data['stock_price'];
                    $res = M('stock_price')->data($add)->add();
                    if($res){
                        $this->success('修改成功');
                    }else{
                        $this->error('修改失败');
                    }
                }else{
                    $this->error('修改失败');
                }
            }
        }else{
            $list=$this->config;
            $this->assign('list',$list);
            $this->display();
        }
    }
    //公告设置--问题申诉
    public function bulletin()
    {
        //实例化对象
        $payorder = M('feedback');
        if(IS_POST){
            $data = I('post.');
            if(empty($data['user_phone'])){
                //全部列表
                $count= $payorder->where(array('is_del'=>0))->count();
                $p = getpage($count, 15);
                $list = $payorder->where(array('is_del'=>0))->limit($p->firstRow, $p->listRows)->order('addtime desc')->select();
                foreach ($list as &$val) {
                    $val['username']=M('user')->where(array('user_id'=>$val['user_id']))->getField('user_name');
                    $val['phone']=M('user')->where(array('user_id'=>$val['user_id']))->getField('user_phone');
                }
            }else{
                $map=[
                    'b.user_name'  => array('like',"%".$data['user_phone']."%"),
                    'a.is_del' =>'0',
                ];
                $count = $payorder->alias('a')
                                    ->join('mf_user b ON b.user_id=a.user_id')
                                    ->where($map)
                                    ->count();
                $p = getpage($count, 15);
                $list = $payorder->alias('a')
                                    ->join('mf_user b ON b.user_id=a.user_id')
                                    ->where($map)
                                    ->limit($p->firstRow, $p->listRows)
                                    ->order('addtime desc')
                                    ->select();
                foreach ($list as &$val) {
                    $val['username']=M('user')->where(array('user_id'=>$val['user_id']))->getField('user_name');
                    $val['phone']=M('user')->where(array('user_id'=>$val['user_id']))->getField('user_phone');
                }
            }
        }else{
            //全部列表
            $count= $payorder->where(array('is_del'=>0))->count();
            $p = getpage($count, 15);
            $list = $payorder->where(array('is_del'=>0))->limit($p->firstRow, $p->listRows)->order('addtime desc')->select();
            foreach ($list as &$val) {
                $val['username']=M('user')->where(array('user_id'=>$val['user_id']))->getField('user_name');
                $val['phone']=M('user')->where(array('user_id'=>$val['user_id']))->getField('user_phone');
            }
        }
        //分配数据
        $this->assign('page', $p->show());
        $this->assign('list', $list);
        //展示页面
        $this->display('Setting/bulletin');
    }
    //问题已沟通
    public function feedok(){
        $theid=I('request.id');
        $list=M('feedback')->where(array('id'=>$theid))->find();
        $list['user_name']=M('user')->where(array('user_id'=>$list['user_id']))->getField('user_name');
        $list['user_truename']=M('user')->where(array('user_id'=>$list['user_id']))->getField('user_truename');
        $this->assign('list',$list);
        $this->display('Setting/liuyanans');
    }
    public function tohuifu(){
        $theid=I('request.xuhao');
        $content=I('request.content2');
        $data['category']=$content;
        $data['backtime']=time();
        $data['status']='1';
        if (M('feedback')->where(array('id'=>$theid))->save($data)) {
            $this->ajaxReturn(['status'=>'1','message'=>'回复成功!']);
        }else{
            $this->ajaxReturn(['status'=>'0','message'=>'回复失败!']);
        }
    }
    //反馈问题删除
    public function feeddelete(){
        $theid=I('request.id');
        if (M('feedback')->where(array('id'=>$theid))->save(['is_del'=>1])) {
            $this->success('删除成功!');
        }else{
            $this->error('删除失败!');
        }
    }
    //图片上传
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
              $data['datas']= '/Uploads/Pic/'.date('Y-m-d',time()).'/'.$file['savename'];   //文件路径,存储在数据库中
            }
            //dump($data);die;
            echo $data['datas'];
        }        
    }
    //首页轮播图
    public function indexrunimg(){
      $list=M('indeximg')->select();
      $this->assign('list',$list);
      $this->display('Setting/indexrunimg');
    }
    //保存到数据库
    public function tobeok(){
      $imagepath=I('request.photo');
      $data['imgpath']=$imagepath;
      if (M('indeximg')->add($data)) {
        $this->ajaxReturn(['status'=>'1','message'=>'上传成功!']);
      }else{
        $this->ajaxReturn(['status'=>'0','message'=>'上传失败!']);
      }
    }
    //删除图片
    public function todeleteimg(){
      $theid=I('request.theid');
      $link=M('indeximg')->where(array('id'=>$theid))->getField('imgpath');
      unlink($link);
      if (M('indeximg')->where(array('id'=>$theid))->delete()) {
        $this->ajaxReturn(['status'=>'1','message'=>'删除成功!']);
      }else{
        $this->ajaxReturn(['status'=>'0','message'=>'删除失败!']);
      }
    }
    //首页跑马灯
    public function runhouse(){
      $runhouse=M('config')->getField('run_house');
      if (IS_POST) {
        $result=M('config')->where('id=1')->save(['run_house'=>$_POST['content']]);
        if ($result) {
          $this->success('修改成功!');
        }else{
          $this->error('修改失败!');
        }
      }else{
        $this->assign('runhouse',$runhouse);
        $this->display('Setting/runhouse');
      }
    }
    //用户注册协议
    public function userregister(){
      $userregister=M('agreement')->where('id=1')->getField('content');
      if (IS_POST) {
        $result=M('agreement')->where('id=1')->save(['content'=>$_POST['content']]);
        if ($result) {
          $this->success('修改成功!');
        }else{
          $this->error('修改失败!');
        }
      }else{
        $this->assign('userregister',$userregister);
        $this->display('Setting/userregister');
      }
    }
    //投资风险协议
    public function investmentrisk(){
      $investmentrisk=M('agreement')->where('id=2')->getField('content');
      if (IS_POST) {
        $result=M('agreement')->where('id=2')->save(['content'=>$_POST['content']]);
        if ($result) {
          $this->success('修改成功!');
        }else{
          $this->error('修改失败!');
        }
      }else{
        $this->assign('investmentrisk',$investmentrisk);
        $this->display('Setting/investmentrisk');
      }
    }
}
