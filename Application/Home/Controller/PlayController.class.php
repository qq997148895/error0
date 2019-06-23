<?php
/**
 * Author: zj
 * Date: 2017/3/22
 * 好友列表
 */
namespace Home\Controller;

use Common\Controller\HomeBaseController;
use Home\Logic\fishPoll\showFriendMassage;

class PlayController extends HomeBaseController
{
    public function turn(){
        $user_id = session('user_id');
        $play_log = M('LuckyLog')
            ->where(['user_id'=>$user_id])
            ->limit(10)
            ->order('addtime desc')
            ->select();
        $this->assign('list',$play_log);
        $config = M('Prize')->find(1);
        $this->assign('config',$config);
        $this->display('Index/luck_draw');
    }
    /**
     * ajax请求处理
     */
    public function startPlay(){

        $user_id = session('user_id');
        $user = $this->user;
        $wallet = $this->wallet;
        $sec_password = I('post.password');
        //判断游戏是否开始
        $config = M('Prize')->find(1);
        if(!$config['prize_open']){
            $this->ajaxReturn(array('switch'=>0));
        }else{
            //验证支付密码是否正确
            if(empty($sec_password)){
                $this->ajaxReturn(array('switch'=>1,'status'=>0,'message'=>'支付密码不能为空!'));
            }
            if(md5($sec_password)!=$user['user_secpwd']){
                $this->ajaxReturn(array('switch'=>1,'status'=>0,'message'=>'支付密码不正确!'));
            }
            if($wallet['static_amount']<$config['prize_value']){
                $this->ajaxReturn(array('switch'=>1,'status'=>0,'message'=>'静态钱包金额不足!'));
            }
            //开始抽奖
            $result['switch'] = 1;//抽奖功能是否开启
            $prize_arr = array(
                '0' => array('id'=>1,'min'=>227,'max'=>268,'prize'=>'一等奖','v'=>0),
                '1' => array('id'=>2,'min'=>317,'max'=>358,'prize'=>'二等奖','v'=>$config['prize_level2']),
                '2' => array('id'=>3,'min'=>92,'max'=>133,'prize'=>'三等奖','v'=>$config['prize_level3']),
                '3' => array('id'=>4,'min'=>array(47,182),
                    'max'=>array(88,223),'prize'=>'四等奖','v'=>$config['prize_level4']),
                '4' => array('id'=>5,'min'=>137,'max'=>178,'prize'=>'五等奖','v'=>$config['prize_level5']),
                '5' => array('id'=>6,'min'=>272,'max'=>313,'prize'=>'六等奖','v'=>$config['prize_level6']),
                '6' => array('id'=>7,'min'=>2,'max'=>43,'prize'=>'七等奖','v'=>$config['prize_level7']),
            );
            //重新构造数组id和v的一维数组
            //array(1=>v1,2=>v2)
            foreach ($prize_arr as $key => $val) {
                $arr[$val['id']] = $val['v'];
            }
            $rid = $this->getRand($arr); //根据概率获取奖项id
            $res = $prize_arr[$rid-1]; //中奖项

            $min = $res['min'];
            $max = $res['max'];
            if($res['id']==4){ //七等奖
                $i = rand(0,1);//产生一个随机整数
                $result['angle'] = mt_rand($min[$i],$max[$i]);
            }else{
                $result['angle'] = mt_rand($min,$max); //随机生成一个角度
            }
            $result['prize'] = $res['prize'];

            //消耗金币,中奖获得金币
            //获取中奖金币数
            $reward_number = 0;
            $log_note = '';
            switch($rid){
                case 2:
                    $reward_number = $config['prize_name2'];
                    $log_note = '二等奖:'.$reward_number.'元现金';
                    break;
                case 3:
                    $reward_number = $config['prize_name3'];
                    $log_note = '三等奖:'.$reward_number.'元现金';
                    break;
                case 4:
                    $reward_number = mt_rand($config['prize_name4'],$config['prize_name42']);
                    $log_note = '四等奖:'.$reward_number.'元现金';
                    break;
                case 5:
                    $reward_number = $config['prize_name5'];
                    $log_note = '五等奖:'.$reward_number.'个排单币';
                    break;
                case 6:
                    $reward_number = $config['prize_name6'];
                    $log_note = '六等奖:'.$reward_number.'元现金';
                    break;
                case 7:
                    $log_note = '七等奖:谢谢参与';
                    break;
            }
            $m = M();
            $m->startTrans();
            try{
                //减少消耗静态钱包的数量
                $oldamount=M('Wallet')->where(['user_id'=>$user_id])->getField('static_amount');
                M('Wallet')->where(['user_id'=>$user_id])->setDec('static_amount',$config['prize_value']);
                $log = array(
                    'user_id'=>$user_id,
                    'user_name'=>$user['user_name'],
                    'user_phone'=>$user['user_phone'],
                    'old_amount'=>$oldamount,
                    'remain_amount'=>$oldamount-$config['prize_value'],
                    'amount'=>'-'.$config['prize_value'],
                    'change_date'=>time(),
                    'log_note'=>'抽奖消耗静态钱包',
                    'wallet_type'=>1
                );
                M('WalletLog')->data($log)->add();
                //增加抽奖奖励
                if($rid==5){
                    //增加拍单币
                    $oldorderbyte=M('Wallet')->where(['user_id'=>$user_id])->getField('order_byte');
                    M('Wallet')->where(['user_id'=>$user_id])->setInc('order_byte',$reward_number);
                    $log = array(
                        'user_id'=>$user_id,
                        'user_name'=>$user['user_name'],
                        'user_phone'=>$user['user_phone'],
                        'old_amount'=>$oldorderbyte,
                        'amount'=>$reward_number,
                        'remain_amount'=>$oldorderbyte+$reward_number,
                        'change_date'=>time(),
                        'log_note'=>'抽奖获得拍单币',
                        'wallet_type'=>4
                    );
                    M('WalletLog')->data($log)->add();
                }else{
                    //增加静态钱包
                    $oldamounttow=M('Wallet')->where(['user_id'=>$user_id])->getField('static_amount');
                    M('Wallet')->where(['user_id'=>$user_id])->setInc('static_amount',$reward_number);
                    $log = array(
                        'user_id'=>$user_id,
                        'user_name'=>$user['user_name'],
                        'user_phone'=>$user['user_phone'],
                        'old_amount'=>$oldamounttow,
                        'amount'=>$reward_number,
                        'remain_amount'=>$oldamounttow+$reward_number,
                        'change_date'=>time(),
                        'log_note'=>'抽奖获得静态钱包',
                        'wallet_type'=>1
                    );
                    M('WalletLog')->data($log)->add();
                }
                $shuju = array(
                    'user_id'=>$user_id,
                    'user_name'=>$user['user_name'],
                    'log_note'=>$log_note,
                    'addtime'=>time()
                );
                M('LuckyLog')->data($shuju)->add();
                $m->commit();
            }catch (\PDOException $e){
                $result['status'] = 0;
                $result['message'] = '抽奖失败!';
                $m->rollback();
                $this->ajaxReturn($result);
            }
            $result['status'] = 1;
            $this->ajaxReturn($result);
        }

    }
   /**
    *
    * 会根据数组中设置的几率计算出符合条件的id
    */
    public function getRand($proArr) {
        $result = '';
        //概率数组的总概率精度,数组所有数字的和100
        $proSum = array_sum($proArr);

        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);//生成随机数
            if ($randNum <= $proCur) {//随机数字在概率范围内,f返回id值,终止循环
                $result = $key;
                break;
            } else {
                //下次循环总概率精度不包括上一次的,
                $proSum -= $proCur;
            }
        }
        unset ($proArr);

        return $result;
    }


}

