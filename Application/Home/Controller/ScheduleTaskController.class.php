<?php
/**
 * Created by PhpStorm.
 * User: Adminlmcqistrator
 * Date: 2017/3/31 0031
 * Time: 15:06
 */
namespace Home\Controller;
use Think\Controller;
use Home\Controller\OrderCenterController;

class ScheduleTaskController extends Controller
{
  	//每天在抢购时间之前清空已经抢购列表字段
    //请购时间每天早上10点,下午15点,晚上20点！在这三个时间点之前执行
    public function clean(){
        $res = M('stock_coupon')->where(array('id'=>1))->data(array('idlist'=>''))->save();
        echo $res;
    }

    /**
     * 安卓下载apk文件
     */
    public function uploadsApk(){
        header("Content-type:text/html;charset=utf-8");
        $file_name = "yms1.0.apk";
        $file_path="./Public/Home/apk/yms1.0.apk";
        //首先要判断给定的文件存在与否
        if(!file_exists($file_path)){
            echo '文件不存在';
            die;
        }
        $fp=fopen($file_path,"r");//开启文件句柄
        $file_size=filesize($file_path);
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".$file_size);
        Header("Content-Disposition: attachment; filename=".$file_name);
        $buffer=1024;
        $file_count=0;
        //向浏览器返回数据
        //feof($fp)测试文件指针是否到了文件结束的位置
        $file_con = '';
        while(!feof($fp) && $file_count<$file_size){
            $file_con .= fread($fp,$buffer);
            $file_count+=$buffer;
        }
        echo $file_con;
        fclose($fp);
    }
    //苹果下载APK文件
    public function uploadsApk2(){
        header("Content-type:text/html;charset=utf-8");
        $file_name = "yms1.0.ipa";
        $file_path="./Public/Home/apk/yms1.0.ipa";
        //首先要判断给定的文件存在与否
        if(!file_exists($file_path)){
            echo '文件不存在';
            die;
        }
        $fp=fopen($file_path,"r");//开启文件句柄
        $file_size=filesize($file_path);
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".$file_size);
        Header("Content-Disposition: attachment; filename=".$file_name);
        $buffer=1024;
        $file_count=0;
        //向浏览器返回数据
        //feof($fp)测试文件指针是否到了文件结束的位置
        $file_con = '';
        while(!feof($fp) && $file_count<$file_size){
            $file_con .= fread($fp,$buffer);
            $file_count+=$buffer;
        }
        echo $file_con;
        fclose($fp);
    }
  	//下载页面
    public function xiazai(){
        $this->display('Index/xiazai');
    }
    //定时任务,每分钟执行一次
    public function run(){
        //$this->doubleInterest();

        $this->freezeUser1();

        $this->freezeUser2();

        // $this->freezeUser3();

        //$this->dayStaticIntrest();

        //$this->giveStaticIntrest();

        $this->autoMatchOrder();
    }

   /**
    * 每天凌晨执行,增加利息表中的冻结天数
    */
    public function addinterestday(){
        $config = M('Config')->find(1);
        //查询冻结天数小于15天的利息记录
        $map=[
            'coldday'=>array('lt',$config['principal_cold']),
            'statustow'=>'1',//不可提现状态
            'status'=>'1',//还未进行提现操作
        ];
        $list=M('interest')->where($map)->select();
        foreach($list as &$v){
            $m = M();
            $m->startTrans();
            try{
                M('interest')->where(array('id'=>$v['id']))->setInc('coldday',1);
                M('interest')->where(array('id'=>$v['id']))->setInc('runnum',1);
                M('interest')->where(array('id'=>$v['id']))->save(['runtime'=>time()]);
                //判断冻结天数是否满足,满足时修改可提现状态
                $thecoldday=M('interest')->where(array('id'=>$v['id']))->getField('coldday');
                if ($thecoldday>=$config['principal_cold']) {
                    M('interest')->where(array('id'=>$v['id']))->setField('statustow','2');//改为可提现状态
                }
                $m->commit();
            }catch (\PDOException $e){
                $m->rollback();
            }
        }
    }

    /**
     * 匹配的订单超时未打款,冻结账号,每分钟执行一次,并把订单交于上级代付,同时删除买入的总订单和利息表中的利息记录
     */
    public function freezeUser1(){
        //所有未打款的匹配信息
        $config = M('Config')->find(1);
        $map=[
            'create_time'=>array('neq',''),
        ];
        $match = M('MatchOrder')
            ->where(['status'=>0])
            ->where($map)
            ->select();//所有匹配中未打款的订单
        foreach($match as $v){
            $now = time();
            //判断匹配的买入订单是预付款还是非预付款订单
            $thebuyorder=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->find();
            if ($thebuyorder['order_type']==1) {//预付款
                if((strtotime($v['create_time'])+$config['pay_time_limit1']*3600)<$now){
                    //判断是否已经交给过上级了,也就是判断是不是上级也没有付款
                    if ($thebuyorder['user_parent_id']!='0') {
                        //封停上级代理和下级玩家账户,删除买入的单子,还原匹配的卖出的单子
                        M('user')->where(array('user_id'=>$thebuyorder['user_parent_id']))->setField('user_status','0');
                        M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');
                        M('user')->where(array('user_id'=>$thebuyorder['user_parent_id']))->setField('cold_resone','超时未打款');
                        M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                        M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                        M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->delete();
                        M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                    }else{//交给上级处理,并删除买入的总单子和非预付款单子
                        //先找上级,上级不存在就删除总订单和所有子订单,并封停账户
                        $allparentid=M('user')->where(array('user_id'=>$thebuyorder['user_id']))->getField('user_parent');
                        $allparent=array_reverse(explode(',', $allparentid));
                        if (empty($allparent[0])) {//没有上级
                            M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');//冻结账户
                            M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                            M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                            M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                            $feiyufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                            for ($i=0; $i < count($feiyufu); $i++) { 
                                $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                if ($matchfeiyufu) {//存在时,判断是否已经支付
                                    if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                        M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                        M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                    }
                                }
                            }
                            //已完成的就不用删了
                            M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id']))->where('matching!=2')->delete();
                        }else{
                            //判断上级是否已冻结,冻结时,也当成没有上级一样处理
                            $userstatus=M('user')->where(array('user_id'=>$allparent[0]))->getField('user_status');
                            if ($userstatus=='0') {
                                M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');//冻结账户
                                M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                                M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                                $feiyufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                                for ($i=0; $i < count($feiyufu); $i++) { 
                                    $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                    if ($matchfeiyufu) {//存在时,判断是否已经支付
                                        if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                            M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                            M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                        }
                                    }
                                }
                                M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id']))->where('matching!=2')->delete();
                            }else{//交于上级处理,删除总订单和非预付款订单
                              	$saleuserid=M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->getField('user_id');
                                if ($saleuserid==$allparent[0]) {//如果是的话,就当没有上级一样处理
                                    M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');//冻结账户
                                    M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                                    M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                    M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                                    $feiyufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                                    for ($i=0; $i < count($feiyufu); $i++) { 
                                        $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                        if ($matchfeiyufu) {//存在时,判断是否已经支付
                                            if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                                M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                                M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                            }
                                        }
                                    }
                                    //已完成的就不用删了
                                    M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id']))->where('matching!=2')->delete();
                                }else{//交于上级
                                    M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->save(['user_parent_id'=>$allparent[0]]);
                                    $theparentname=M('user')->where(array('user_id'=>$allparent[0]))->getField('user_name');
                                    //更近匹配时间
                                    M('MatchOrder')->where(array('id'=>$v['id']))->save(['buy_id'=>$allparent[0],'buy_name'=>$theparentname,'create_time'=>date('Y-m-d H:i:s',time())]);
                                    //给上级发送短信提醒
                                    $log1 = array(
                                        'user_id'=>$allparent[0],
                                        // 'content'=>$buy_order['user_name']."您好,您买入".$buy_order['amount']."的订单,已经匹配成功!",
                                        'content'=>"亲爱的会员,您的下级买入订单".$thebuyorder['order_number']."已转由您负责交易,请在规定时间内完成打款操作.",
                                        'createtime'=>time()
                                    );
                                    M('UserNotice')->data($log1)->add();
                                    //发送短信通知上家
                                    $phone = M('user')->where(array('user_id'=>$allparent[0]))->getField('user_phone');
                                    $content = "亲爱的会员,您的下级买入订单".$thebuyorder['order_number']."已转由您负责交易,请在规定时间内完成打款操作.";
                                    sendSms2($phone, $content);
                                    $feiyufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                                    for ($i=0; $i < count($feiyufu); $i++) { 
                                        $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                        if ($matchfeiyufu) {//存在时,判断是否已经支付
                                            if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                                M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                                M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                            }
                                        }
                                    }
                                    $where=[
                                        'id'=>array('neq',$v['buy_order_id']),
                                        'parent_id'=>array('eq',$thebuyorder['parent_id']),
                                    ];
                                    M('HelpOrder')->where($where)->where('matching!=2')->delete();
                                }
                            }
                        }
                    }
                }
            }else{//非预付款
                if((strtotime($v['create_time'])+$config['pay_time_limit2']*3600)<$now){
                    //判断是否已经交给过上级了,也就是判断是不是上级也没有付款
                    if ($thebuyorder['user_parent_id']!='0') {
                        //封停上级代理和下级玩家账户,删除买入的单子,还原匹配的卖出的单子
                        M('user')->where(array('user_id'=>$thebuyorder['user_parent_id']))->setField('user_status','0');
                        M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');
                        M('user')->where(array('user_id'=>$thebuyorder['user_parent_id']))->setField('cold_resone','超时未打款');
                        M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                        M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                        M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->delete();
                        M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                    }else{//交给上级处理,并删除买入的总单子和预付款单子,和生息记录
                        //先找上级,上级不存在就删除总订单和所有子订单,并封停账户
                        $allparentid=M('user')->where(array('user_id'=>$thebuyorder['user_id']))->getField('user_parent');
                        $allparent=array_reverse(explode(',', $allparentid));
                        if (empty($allparent[0])) {//没有上级
                            M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');//冻结账户
                            M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                            M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                            M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                            $feiyufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                            for ($i=0; $i < count($feiyufu); $i++) { 
                                $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                if ($matchfeiyufu) {//存在时,判断是否已经支付
                                    if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                        M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                        M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                    }
                                }
                            }
                            $yufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'1'))->getField('id');
                            $matchyufu=M('MatchOrder')->where(array('buy_order_id'=>$yufu))->find();
                            if ($matchyufu) {//存在时,判断是否已经支付
                                if ($matchyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                    M('AskhelpOrder')->where(array('id'=>$matchyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                    M('MatchOrder')->where(array('buy_order_id'=>$yufu))->delete();
                                }
                            }
                            if (M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->find()) {//有利息记录时,删除记录
                                M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->delete();
                            }
                            M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id']))->where('matching!=2')->delete();
                            // M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->where('matching!=2')->delete();
                        }else{
                            //判断上级是否已冻结,冻结时,也当成没有上级一样处理
                            $userstatus=M('user')->where(array('user_id'=>$allparent[0]))->getField('user_status');
                            if ($userstatus=='0') {
                                M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');//冻结账户
                                M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                                M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                                $feiyufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                                for ($i=0; $i < count($feiyufu); $i++) { 
                                    $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                    if ($matchfeiyufu) {//存在时,判断是否已经支付
                                        if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                            M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                            M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                        }
                                    }
                                }
                                $yufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'1'))->getField('id');
                                $matchyufu=M('MatchOrder')->where(array('buy_order_id'=>$yufu))->find();
                                if ($matchyufu) {//存在时,判断是否已经支付
                                    if ($matchyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                        M('AskhelpOrder')->where(array('id'=>$matchyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                        M('MatchOrder')->where(array('buy_order_id'=>$yufu))->delete();
                                    }
                                }
                                if (M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->find()) {//有利息记录时,删除记录
                                    M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->delete();
                                }
                                M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id']))->where('matching!=2')->delete();
                                // M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->where('matching!=2')->delete();
                            }else{//交于上级处理,删除总订单和预付款订单
                                $saleuserid=M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->getField('user_id');
                                if ($saleuserid==$allparent[0]) {//是的话就当没有上级
                                    M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('user_status','0');//冻结账户
                                    M('user')->where(array('user_id'=>$thebuyorder['user_id']))->setField('cold_resone','超时未打款');
                                    M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                    M('MatchOrder')->where(array('id'=>$v['id']))->delete();
                                    $feiyufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                                    for ($i=0; $i < count($feiyufu); $i++) { 
                                        $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                        if ($matchfeiyufu) {//存在时,判断是否已经支付
                                            if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                                M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                                M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                            }
                                        }
                                    }
                                    $yufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'1'))->getField('id');
                                    $matchyufu=M('MatchOrder')->where(array('buy_order_id'=>$yufu))->find();
                                    if ($matchyufu) {//存在时,判断是否已经支付
                                        if ($matchyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                            M('AskhelpOrder')->where(array('id'=>$matchyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                            M('MatchOrder')->where(array('buy_order_id'=>$yufu))->delete();
                                        }
                                    }
                                    if (M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->find()) {//有利息记录时,删除记录
                                        M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->delete();
                                    }
                                    M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id']))->where('matching!=2')->delete();
                                    // M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->where('matching!=2')->delete();
                                }else{//交于上级代付
                                    M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->save(['user_parent_id'=>$allparent[0]]);
                                    $theparentname=M('user')->where(array('user_id'=>$allparent[0]))->getField('user_name');
                                    //更近匹配时间
                                    M('MatchOrder')->where(array('id'=>$v['id']))->save(['buy_id'=>$allparent[0],'buy_name'=>$theparentname,'create_time'=>date('Y-m-d H:i:s',time())]);
                                    //给上级发送短信提醒
                                    $log1 = array(
                                        'user_id'=>$allparent[0],
                                        // 'content'=>$buy_order['user_name']."您好,您买入".$buy_order['amount']."的订单,已经匹配成功!",
                                        'content'=>"亲爱的会员,您的下级买入订单".$thebuyorder['order_number']."已转由您负责交易,请在规定时间内完成打款操作.",
                                        'createtime'=>time()
                                    );
                                    M('UserNotice')->data($log1)->add();
                                    //发送短信通知上家
                                    $phone = M('user')->where(array('user_id'=>$allparent[0]))->getField('user_phone');
                                    $content = "亲爱的会员,您的下级买入订单".$thebuyorder['order_number']."已转由您负责交易,请在规定时间内完成打款操作.";
                                    sendSms2($phone, $content);
                                    //查询除该非预付款之外的所有同类非预付款
                                    $paichu=[
                                        'id'=>array('neq',$v['buy_order_id']),
                                    ];
                                    $feiyufu=M('HelpOrder')->where($paichu)->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'2'))->getField('id',true);
                                    for ($i=0; $i < count($feiyufu); $i++) { 
                                        $matchfeiyufu=M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->find();
                                        if ($matchfeiyufu) {//存在时,判断是否已经支付
                                            if ($matchfeiyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                                M('AskhelpOrder')->where(array('id'=>$matchfeiyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                                M('MatchOrder')->where(array('buy_order_id'=>$feiyufu[$i]))->delete();
                                            }
                                        }
                                    }
                                    $yufu=M('HelpOrder')->where(array('parent_id'=>$thebuyorder['parent_id'],'order_type'=>'1'))->getField('id');
                                    $matchyufu=M('MatchOrder')->where(array('buy_order_id'=>$yufu))->find();
                                    if ($matchyufu) {//存在时,判断是否已经支付
                                        if ($matchyufu['status']=='0') {//还未支付,可删除匹配记录,并把卖出订单还原成未匹配
                                            M('AskhelpOrder')->where(array('id'=>$matchyufu['sale_order_id']))->save(['matching'=>'0','status'=>'0']);
                                            M('MatchOrder')->where(array('buy_order_id'=>$yufu))->delete();
                                        }
                                    }
                                    if (M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->find()) {//有利息记录时,删除记录
                                        M('interest')->where(array('buy_order'=>$thebuyorder['parent_id']))->delete();
                                    }
                                    $where=[
                                        'id'=>array('neq',$v['buy_order_id']),
                                        'parent_id'=>array('eq',$thebuyorder['parent_id']),
                                    ];
                                    M('HelpOrder')->where($where)->where('matching!=2')->delete();
                                    // $where2=[
                                    //     'id'=>array('neq',$v['buy_order_id']),
                                    //     'parent_id'=>array('eq',$thebuyorder['parent_id']),
                                    //     'order_type'=>'2',
                                    // ];
                                    // M('HelpOrder')->where($where2)->where('matching!=2')->delete();
                                }
                            }
                        }
                    }
                }
            }
          	//判断还原后的卖出订单是否都是未匹配,如果都是,则修改总卖出订单的状态
            $thesaleorder=M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->getField('parent_id');
            $thematching=M('AskhelpOrder')->where(array('parent_id'=>$thesaleorder,'order_type'=>'2'))->getField('matching',true);
            if (!in_array('1', $thematching)&&!in_array('2', $thematching)) {
                M('AskhelpOrder')->where(array('parent_id'=>$thesaleorder,'order_type'=>'1'))->save(['matching'=>'0']);
              	M('AskhelpOrder')->where(array('parent_id'=>$thesaleorder,'order_type'=>'2'))->delete();
            }
        }
    }
    //发送短信验证码
    function sendSms2($phones, $content)
    {
        $username = urlencode('yms111');
        $password = urlencode('896($xkX');
        //$sign = env('SMS_SIGN', '【国金科技】');
        $sign = "【圆梦社】";
        if (!strpos($content, $sign)) {
            $content .= $sign;
        }
        $content = urlencode(iconv("UTF-8", "gb2312//IGNORE", trim($content)));
        $url = "http://api.1086sms.com/api/send.aspx?username=$username&password=$password&mobiles=$phones&content=$content";
        $ret = file_get_contents($url);
        $ret = urldecode($ret);
        $result = [];
        foreach (explode('&', $ret) as $v) {
            list($key, $value) = explode('=', $v);
            $result[$key] = iconv('gb2312', 'utf-8', $value);
        }
        return $result;
    }
    /**
     * 已打款的账号,超时未确认收款,冻结收款账号,每分钟执行一次,系统自动帮助确认
     */
    public function freezeUser2(){
        //所有已打款的匹配信息
        $config = M('Config')->find(1);
        $match = M('MatchOrder')
            ->where(['status'=>1])
            ->select();
        foreach($match as $v){
            $now = time();
            if((strtotime($v['payed_time'])+$config['gain_time_limit']*3600)<$now){
                //冻结账号
                M('User')->where(['user_id'=>$v['sale_id']])->setField('user_status',0);
                M('User')->where(['user_id'=>$v['sale_id']])->setField('cold_resone','超时未收款');
                //修改卖出/买入/匹配/支付订单的状态,同时添加利息记录
                M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->save(['status'=>'2','matching'=>'2']);
                //判断卖出总订单是否已经交易完成
                $psaleorderid=M('AskhelpOrder')->where(array('id'=>$v['sale_order_id']))->getField('parent_id');
                $allsalestatus=M('AskhelpOrder')->where(array('parent_id'=>$psaleorderid))->where('order_type!=1')->getField('matching',true);
                if (!in_array('0', $allsalestatus)&&!in_array('1', $allsalestatus)) {//总单子已交易完成
                    //修改总单子的状态
                    M('AskhelpOrder')->where(array('id'=>$psaleorderid))->save(['matching'=>'2','status'=>'2']);
                }
                M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->save(['matching'=>'2']);
                M('MatchOrder')->where(array('id'=>$v['id']))->save(['status'=>'2','receive_time'=>date('Y-m-d H:i:s',time())]);
                M('PayedOrder')->where(array('match_id'=>$v['id']))->save(['status'=>'2','end_time'=>time()]);
                //判断买入单子是否是由上级玩家代付的
                $thehelpinfo=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->find();
                if ($thehelpinfo['user_parent_id']!=0) {//是上级代理代付的
                    //判断匹配的买入订单是预付款还是非预付款
                    if ($thehelpinfo['order_type']==1) {//预付款判断卖出总订单是否已经交易完成
                        $yufutype=M('HelpOrder')->where(array('id'=>$list['buy_order_id']))->find();
                        if ($yufutype) {//单子还存在时
                            $data['user_id'] = $thehelpinfo['user_parent_id'];
                            $data['buy_order']=M('HelpOrder')->where(array('id'=>$list['buy_order_id']))->getField('parent_id');//记录总订单的id
                            $pamount=M('HelpOrder')->where(array('id'=>$list['buy_order_id']))->getField('parent_amount');
                            $data['benjin']=$pamount;//总订单的金额
                            $data['amount']=$pamount*$config['interest_price']/100;//利息部分
                            $data['allamount'] = $data['benjin'] + $data['amount'];//本金+利息
                            $data['addtime'] = time();
                            $data['status'] = '1';
                            $data['statustow'] = '1';
                            $result4 = M('interest')->add($data);
                        }
                    }
                }else{//是买入方自己在交易
                    //判断匹配的买入订单是预付款还是非预付款
                    if ($thehelpinfo['order_type']==1) {//预付款
                        $yufutype=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->find();
                        if ($yufutype) {//单子还存在时
                            $data['user_id']=$v['buy_id'];
                            $data['buy_order']=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->getField('parent_id');//记录总订单的id
                            $pamount=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->getField('parent_amount');
                            $data['benjin']=$pamount;//总订单的金额
                            $data['amount']=$pamount*$config['interest_price']/100;//利息部分
                            $data['allamount']=$data['benjin']+$data['amount'];//本金+利息
                            $data['addtime']=time();
                            $data['status']='1';
                            $data['statustow']='1';
                            $result4=M('interest')->add($data);
                            //发放动态奖金,判断是否开启烧伤;先判断总单子是否交易完毕
                            $porderid=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->getField('parent_id');
                            $allstatus=M('HelpOrder')->where(array('parent_id'=>$porderid))->where('order_type!=0')->getField('matching',true);
                            if (!in_array('0', $allstatus)&&!in_array('1', $allstatus)) {//总单子已交易完成
                                //修改总单子的状态
                                M('HelpOrder')->where(array('id'=>$porderid))->save(['matching'=>'2','status'=>'1']);
                                if ($config['dynamic_burn']==0) {//烧伤制度关闭
                                    //查询买入方的一代至七代
                                    $allparentid=M('user')->where(array('user_id'=>$v['buy_id']))->getField('user_parent');
                                    $allparent=array_reverse(explode(',', $allparentid));
                                    $oneparent=$allparent[0];
                                    $towparent=$allparent[1];
                                    $threeparent=$allparent[2];
                                    $fourparent=$allparent[3];
                                    $fiveparent=$allparent[4];
                                    $sixparent=$allparent[5];
                                    $sevenparent=$allparent[6];
                                    if ($oneparent) {//一代存在时,判断一代是否是VIP1及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$oneparent,$oneparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                          'user_parent'=>array('like',array($oneparent.','.'%','%'.','.$oneparent,'%'.','.$oneparent.','.'%',$oneparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        //判断一代是否已激活
                                        $isactive=M('user')->where(array('user_id'=>$oneparent))->getField('is_active');
                                        if ($theviplecel==1&&$isactive==1) {
                                            $dongtaimoney=$pamount*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }elseif ($theviplecel>1) {
                                            $dongtaimoney=$pamount*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }
                                    }
                                    if ($towparent) {//二代存在时,判断二代是否是VIP2及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$towparent,$towparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                          'user_parent'=>array('like',array($towparent.','.'%','%'.','.$towparent,'%'.','.$towparent.','.'%',$towparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=2) {
                                            $dongtaimoney=$pamount*$config['reward_rate2']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$towparent))->find();
                                            M('wallet')->where(array('user_id'=>$towparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$towparent))->find();
                                            $data2['user_id']=$towparent;
                                            $data2['user_name']=$parentuser['user_name'];
                                            $data2['user_phone']=$parentuser['user_phone'];
                                            $data2['amount']=$dongtaimoney;
                                            $data2['old_amount']=$userwallet['change_amount'];
                                            $data2['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data2['change_date']=time();
                                            $data2['log_note']="二代动态奖金获取";
                                            $data2['wallet_type']='2';
                                            M('wallet_log')->add($data2);
                                        }
                                    }
                                    if ($threeparent) {//三代存在时,判断三代是否是VIP3及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$threeparent,$threeparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                          'user_parent'=>array('like',array($threeparent.','.'%','%'.','.$threeparent,'%'.','.$threeparent.','.'%',$threeparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=3) {
                                            $dongtaimoney=$pamount*$config['reward_rate3']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$threeparent))->find();
                                            M('wallet')->where(array('user_id'=>$threeparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$threeparent))->find();
                                            $data3['user_id']=$threeparent;
                                            $data3['user_name']=$parentuser['user_name'];
                                            $data3['user_phone']=$parentuser['user_phone'];
                                            $data3['amount']=$dongtaimoney;
                                            $data3['old_amount']=$userwallet['change_amount'];
                                            $data3['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data3['change_date']=time();
                                            $data3['log_note']="三代动态奖金获取";
                                            $data3['wallet_type']='2';
                                            M('wallet_log')->add($data3);
                                        }
                                    }
                                    if ($fourparent) {//四代存在时,判断四代是否是VIP4及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fourparent,$fourparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                          'user_parent'=>array('like',array($fourparent.','.'%','%'.','.$fourparent,'%'.','.$fourparent.','.'%',$fourparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=4) {
                                            $dongtaimoney=$pamount*$config['reward_rate4']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fourparent))->find();
                                            M('wallet')->where(array('user_id'=>$fourparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fourparent))->find();
                                            $data4['user_id']=$fourparent;
                                            $data4['user_name']=$parentuser['user_name'];
                                            $data4['user_phone']=$parentuser['user_phone'];
                                            $data4['amount']=$dongtaimoney;
                                            $data4['old_amount']=$userwallet['change_amount'];
                                            $data4['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data4['change_date']=time();
                                            $data4['log_note']="四代动态奖金获取";
                                            $data4['wallet_type']='2';
                                            M('wallet_log')->add($data4);
                                        }
                                    }
                                    if ($fiveparent) {//五代存在时,判断四代是否是VIP5及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fiveparent,$fiveparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                          'user_parent'=>array('like',array($fiveparent.','.'%','%'.','.$fiveparent,'%'.','.$fiveparent.','.'%',$fiveparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=5) {
                                            $dongtaimoney=$pamount*$config['reward_rate5']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fiveparent))->find();
                                            M('wallet')->where(array('user_id'=>$fiveparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fiveparent))->find();
                                            $data5['user_id']=$fiveparent;
                                            $data5['user_name']=$parentuser['user_name'];
                                            $data5['user_phone']=$parentuser['user_phone'];
                                            $data5['amount']=$dongtaimoney;
                                            $data5['old_amount']=$userwallet['change_amount'];
                                            $data5['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data5['change_date']=time();
                                            $data5['log_note']="五代动态奖金获取";
                                            $data5['wallet_type']='2';
                                            M('wallet_log')->add($data5);
                                        }
                                    }
                                    if ($sixparent) {//六代存在时,判断六代是否是VIP6及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sixparent,$sixparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                          'user_parent'=>array('like',array($sixparent.','.'%','%'.','.$sixparent,'%'.','.$sixparent.','.'%',$sixparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=6) {
                                            $dongtaimoney=$pamount*$config['reward_rate6']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sixparent))->find();
                                            M('wallet')->where(array('user_id'=>$sixparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sixparent))->find();
                                            $data6['user_id']=$sixparent;
                                            $data6['user_name']=$parentuser['user_name'];
                                            $data6['user_phone']=$parentuser['user_phone'];
                                            $data6['amount']=$dongtaimoney;
                                            $data6['old_amount']=$userwallet['change_amount'];
                                            $data6['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data6['change_date']=time();
                                            $data6['log_note']="六代动态奖金获取";
                                            $data6['wallet_type']='2';
                                            M('wallet_log')->add($data6);
                                        }
                                    }
                                    if ($sevenparent) {//七代存在时,判断七代是否是VIP7
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sevenparent,$sevenparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                          'user_parent'=>array('like',array($sevenparent.','.'%','%'.','.$sevenparent,'%'.','.$sevenparent.','.'%',$sevenparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel==7) {
                                            $dongtaimoney=$pamount*$config['reward_rate7']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sevenparent))->find();
                                            M('wallet')->where(array('user_id'=>$sevenparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sevenparent))->find();
                                            $data7['user_id']=$sevenparent;
                                            $data7['user_name']=$parentuser['user_name'];
                                            $data7['user_phone']=$parentuser['user_phone'];
                                            $data7['amount']=$dongtaimoney;
                                            $data7['old_amount']=$userwallet['change_amount'];
                                            $data7['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data7['change_date']=time();
                                            $data7['log_note']="七代动态奖金获取";
                                            $data7['wallet_type']='2';
                                            M('wallet_log')->add($data7);
                                        }
                                    }
                                }else{//烧伤制度开启
                                    //查询买入方的一代至七代
                                    $allparentid=M('user')->where(array('user_id'=>$v['buy_id']))->getField('user_parent');
                                    $allparent=array_reverse(explode(',', $allparentid));
                                    $oneparent=$allparent[0];
                                    $towparent=$allparent[1];
                                    $threeparent=$allparent[2];
                                    $fourparent=$allparent[3];
                                    $fiveparent=$allparent[4];
                                    $sixparent=$allparent[5];
                                    $sevenparent=$allparent[6];
                                    if ($oneparent) {//一代存在时,判断一代是否是动态会员
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$oneparent,$oneparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($oneparent.','.'%','%'.','.$oneparent,'%'.','.$oneparent.','.'%',$oneparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        //判断一代是否已激活
                                        $isactive=M('user')->where(array('user_id'=>$oneparent))->getField('is_active');
                                        if ($theviplecel==1&&$isactive==1) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$oneparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }elseif ($theviplecel>1) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$oneparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }
                                    }
                                    if ($towparent) {//二代存在时,判断二代是否是VIP2及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$towparent,$towparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($towparent.','.'%','%'.','.$towparent,'%'.','.$towparent.','.'%',$towparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=2) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$towparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate2']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$towparent))->find();
                                            M('wallet')->where(array('user_id'=>$towparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$towparent))->find();
                                            $data2['user_id']=$towparent;
                                            $data2['user_name']=$parentuser['user_name'];
                                            $data2['user_phone']=$parentuser['user_phone'];
                                            $data2['amount']=$dongtaimoney;
                                            $data2['old_amount']=$userwallet['change_amount'];
                                            $data2['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data2['change_date']=time();
                                            $data2['log_note']="二代动态奖金获取";
                                            $data2['wallet_type']='2';
                                            M('wallet_log')->add($data2);
                                        }
                                    }
                                    if ($threeparent) {//三代存在时,判断三代是否是VIP3及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$threeparent,$threeparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($threeparent.','.'%','%'.','.$threeparent,'%'.','.$threeparent.','.'%',$threeparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=3) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$threeparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate3']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$threeparent))->find();
                                            M('wallet')->where(array('user_id'=>$threeparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$threeparent))->find();
                                            $data3['user_id']=$threeparent;
                                            $data3['user_name']=$parentuser['user_name'];
                                            $data3['user_phone']=$parentuser['user_phone'];
                                            $data3['amount']=$dongtaimoney;
                                            $data3['old_amount']=$userwallet['change_amount'];
                                            $data3['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data3['change_date']=time();
                                            $data3['log_note']="三代动态奖金获取";
                                            $data3['wallet_type']='2';
                                            M('wallet_log')->add($data3);
                                        }
                                    }
                                    if ($fourparent) {//四代存在时,判断四代是否是VIP4及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fourparent,$fourparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($fourparent.','.'%','%'.','.$fourparent,'%'.','.$fourparent.','.'%',$fourparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=4) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$fourparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate4']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fourparent))->find();
                                            M('wallet')->where(array('user_id'=>$fourparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fourparent))->find();
                                            $data4['user_id']=$fourparent;
                                            $data4['user_name']=$parentuser['user_name'];
                                            $data4['user_phone']=$parentuser['user_phone'];
                                            $data4['amount']=$dongtaimoney;
                                            $data4['old_amount']=$userwallet['change_amount'];
                                            $data4['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data4['change_date']=time();
                                            $data4['log_note']="四代动态奖金获取";
                                            $data4['wallet_type']='2';
                                            M('wallet_log')->add($data4);
                                        }
                                    }
                                    if ($fiveparent) {//五代存在时,判断五代是否是VIP5及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fiveparent,$fiveparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($fiveparent.','.'%','%'.','.$fiveparent,'%'.','.$fiveparent.','.'%',$fiveparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=5) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$fiveparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate5']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fiveparent))->find();
                                            M('wallet')->where(array('user_id'=>$fiveparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fiveparent))->find();
                                            $data5['user_id']=$fiveparent;
                                            $data5['user_name']=$parentuser['user_name'];
                                            $data5['user_phone']=$parentuser['user_phone'];
                                            $data5['amount']=$dongtaimoney;
                                            $data5['old_amount']=$userwallet['change_amount'];
                                            $data5['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data5['change_date']=time();
                                            $data5['log_note']="五代动态奖金获取";
                                            $data5['wallet_type']='2';
                                            M('wallet_log')->add($data5);
                                        }
                                    }
                                    if ($sixparent) {//六代存在时,判断六代是否是VIP6及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sixparent,$sixparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($sixparent.','.'%','%'.','.$sixparent,'%'.','.$sixparent.','.'%',$sixparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=6) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$sixparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate6']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sixparent))->find();
                                            M('wallet')->where(array('user_id'=>$sixparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sixparent))->find();
                                            $data6['user_id']=$sixparent;
                                            $data6['user_name']=$parentuser['user_name'];
                                            $data6['user_phone']=$parentuser['user_phone'];
                                            $data6['amount']=$dongtaimoney;
                                            $data6['old_amount']=$userwallet['change_amount'];
                                            $data6['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data6['change_date']=time();
                                            $data6['log_note']="六代动态奖金获取";
                                            $data6['wallet_type']='2';
                                            M('wallet_log')->add($data6);
                                        }
                                    }
                                    if ($sevenparent) {//七代存在时,判断七代是否是VIP7及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sevenparent,$sevenparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($sevenparent.','.'%','%'.','.$sevenparent,'%'.','.$sevenparent.','.'%',$sevenparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel==7) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$sevenparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate7']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sevenparent))->find();
                                            M('wallet')->where(array('user_id'=>$sevenparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sevenparent))->find();
                                            $data7['user_id']=$sevenparent;
                                            $data7['user_name']=$parentuser['user_name'];
                                            $data7['user_phone']=$parentuser['user_phone'];
                                            $data7['amount']=$dongtaimoney;
                                            $data7['old_amount']=$userwallet['change_amount'];
                                            $data7['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data7['change_date']=time();
                                            $data7['log_note']="七代动态奖金获取";
                                            $data7['wallet_type']='2';
                                            M('wallet_log')->add($data7);
                                        }
                                    }
                                }
                            }
                        }
                    }else{//非预付款
                        $feiyufutype=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->find();
                        if ($feiyufutype) {//单子还存在时
                            //发放动态奖金,判断是否开启烧伤;先判断总单子是否交易完毕
                            $pamount=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->getField('parent_amount');
                            $porderid=M('HelpOrder')->where(array('id'=>$v['buy_order_id']))->getField('parent_id');
                            $allstatus=M('HelpOrder')->where(array('parent_id'=>$porderid))->where('order_type!=0')->getField('matching',true);
                            if (!in_array('0', $allstatus)&&!in_array('1', $allstatus)) {//总单子已交易完成
                                //修改总单子的状态
                                M('HelpOrder')->where(array('id'=>$porderid))->save(['matching'=>'2','status'=>'1']);
                                if ($config['dynamic_burn']==0) {//烧伤制度关闭
                                    //查询买入方的一代至七代
                                    $allparentid=M('user')->where(array('user_id'=>$v['buy_id']))->getField('user_parent');
                                    $allparent=array_reverse(explode(',', $allparentid));
                                    $oneparent=$allparent[0];
                                    $towparent=$allparent[1];
                                    $threeparent=$allparent[2];
                                    $fourparent=$allparent[3];
                                    $fiveparent=$allparent[4];
                                    $sixparent=$allparent[5];
                                    $sevenparent=$allparent[6];
                                    if ($oneparent) {//一代存在时,判断一代是否是VIP1及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$oneparent,$oneparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($oneparent.','.'%','%'.','.$oneparent,'%'.','.$oneparent.','.'%',$oneparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        //判断一代是否已激活
                                        $isactive=M('user')->where(array('user_id'=>$oneparent))->getField('is_active');
                                        if ($theviplecel==1&&$isactive==1) {
                                            $dongtaimoney=$pamount*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }elseif ($theviplecel>1) {
                                            $dongtaimoney=$pamount*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }
                                    }
                                    if ($towparent) {//二代存在时,判断二代是否是VIP2及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$towparent,$towparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($towparent.','.'%','%'.','.$towparent,'%'.','.$towparent.','.'%',$towparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=2) {
                                            $dongtaimoney=$pamount*$config['reward_rate2']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$towparent))->find();
                                            M('wallet')->where(array('user_id'=>$towparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$towparent))->find();
                                            $data2['user_id']=$towparent;
                                            $data2['user_name']=$parentuser['user_name'];
                                            $data2['user_phone']=$parentuser['user_phone'];
                                            $data2['amount']=$dongtaimoney;
                                            $data2['old_amount']=$userwallet['change_amount'];
                                            $data2['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data2['change_date']=time();
                                            $data2['log_note']="二代动态奖金获取";
                                            $data2['wallet_type']='2';
                                            M('wallet_log')->add($data2);
                                        }
                                    }
                                    if ($threeparent) {//三代存在时,判断三代是否是VIP3及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$threeparent,$threeparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($threeparent.','.'%','%'.','.$threeparent,'%'.','.$threeparent.','.'%',$threeparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=3) {
                                            $dongtaimoney=$pamount*$config['reward_rate3']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$threeparent))->find();
                                            M('wallet')->where(array('user_id'=>$threeparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$threeparent))->find();
                                            $data3['user_id']=$threeparent;
                                            $data3['user_name']=$parentuser['user_name'];
                                            $data3['user_phone']=$parentuser['user_phone'];
                                            $data3['amount']=$dongtaimoney;
                                            $data3['old_amount']=$userwallet['change_amount'];
                                            $data3['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data3['change_date']=time();
                                            $data3['log_note']="三代动态奖金获取";
                                            $data3['wallet_type']='2';
                                            M('wallet_log')->add($data3);
                                        }
                                    }
                                    if ($fourparent) {//四代存在时,判断四代是否是VIP4及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fourparent,$fourparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($fourparent.','.'%','%'.','.$fourparent,'%'.','.$fourparent.','.'%',$fourparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=4) {
                                            $dongtaimoney=$pamount*$config['reward_rate4']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fourparent))->find();
                                            M('wallet')->where(array('user_id'=>$fourparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fourparent))->find();
                                            $data4['user_id']=$fourparent;
                                            $data4['user_name']=$parentuser['user_name'];
                                            $data4['user_phone']=$parentuser['user_phone'];
                                            $data4['amount']=$dongtaimoney;
                                            $data4['old_amount']=$userwallet['change_amount'];
                                            $data4['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data4['change_date']=time();
                                            $data4['log_note']="四代动态奖金获取";
                                            $data4['wallet_type']='2';
                                            M('wallet_log')->add($data4);
                                        }
                                    }
                                    if ($fiveparent) {//五代存在时,判断四代是否是VIP5及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fiveparent,$fiveparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($fiveparent.','.'%','%'.','.$fiveparent,'%'.','.$fiveparent.','.'%',$fiveparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=5) {
                                            $dongtaimoney=$pamount*$config['reward_rate5']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fiveparent))->find();
                                            M('wallet')->where(array('user_id'=>$fiveparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fiveparent))->find();
                                            $data5['user_id']=$fiveparent;
                                            $data5['user_name']=$parentuser['user_name'];
                                            $data5['user_phone']=$parentuser['user_phone'];
                                            $data5['amount']=$dongtaimoney;
                                            $data5['old_amount']=$userwallet['change_amount'];
                                            $data5['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data5['change_date']=time();
                                            $data5['log_note']="五代动态奖金获取";
                                            $data5['wallet_type']='2';
                                            M('wallet_log')->add($data5);
                                        }
                                    }
                                    if ($sixparent) {//六代存在时,判断六代是否是VIP6及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sixparent,$sixparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($sixparent.','.'%','%'.','.$sixparent,'%'.','.$sixparent.','.'%',$sixparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=6) {
                                            $dongtaimoney=$pamount*$config['reward_rate6']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sixparent))->find();
                                            M('wallet')->where(array('user_id'=>$sixparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sixparent))->find();
                                            $data6['user_id']=$sixparent;
                                            $data6['user_name']=$parentuser['user_name'];
                                            $data6['user_phone']=$parentuser['user_phone'];
                                            $data6['amount']=$dongtaimoney;
                                            $data6['old_amount']=$userwallet['change_amount'];
                                            $data6['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data6['change_date']=time();
                                            $data6['log_note']="六代动态奖金获取";
                                            $data6['wallet_type']='2';
                                            M('wallet_log')->add($data6);
                                        }
                                    }
                                    if ($sevenparent) {//七代存在时,判断七代是否是VIP7
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sevenparent,$sevenparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($sevenparent.','.'%','%'.','.$sevenparent,'%'.','.$sevenparent.','.'%',$sevenparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel==7) {
                                            $dongtaimoney=$pamount*$config['reward_rate7']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sevenparent))->find();
                                            M('wallet')->where(array('user_id'=>$sevenparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sevenparent))->find();
                                            $data7['user_id']=$sevenparent;
                                            $data7['user_name']=$parentuser['user_name'];
                                            $data7['user_phone']=$parentuser['user_phone'];
                                            $data7['amount']=$dongtaimoney;
                                            $data7['old_amount']=$userwallet['change_amount'];
                                            $data7['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data7['change_date']=time();
                                            $data7['log_note']="七代动态奖金获取";
                                            $data7['wallet_type']='2';
                                            M('wallet_log')->add($data7);
                                        }
                                    }
                                }else{//烧伤制度开启
                                    //查询买入方的一代至七代
                                    $allparentid=M('user')->where(array('user_id'=>$v['buy_id']))->getField('user_parent');
                                    $allparent=array_reverse(explode(',', $allparentid));
                                    $oneparent=$allparent[0];
                                    $towparent=$allparent[1];
                                    $threeparent=$allparent[2];
                                    $fourparent=$allparent[3];
                                    $fiveparent=$allparent[4];
                                    $sixparent=$allparent[5];
                                    $sevenparent=$allparent[6];
                                    if ($oneparent) {//一代存在时,判断一代是否是vip1及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$oneparent,$oneparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($oneparent.','.'%','%'.','.$oneparent,'%'.','.$oneparent.','.'%',$oneparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        //判断一代是否已激活
                                        $isactive=M('user')->where(array('user_id'=>$oneparent))->getField('is_active');
                                        if ($theviplecel==1&&$isactive==1) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$oneparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }elseif ($theviplecel>1) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$oneparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate1']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$oneparent))->find();
                                            M('wallet')->where(array('user_id'=>$oneparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$oneparent))->find();
                                            $data1['user_id']=$oneparent;
                                            $data1['user_name']=$parentuser['user_name'];
                                            $data1['user_phone']=$parentuser['user_phone'];
                                            $data1['amount']=$dongtaimoney;
                                            $data1['old_amount']=$userwallet['change_amount'];
                                            $data1['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data1['change_date']=time();
                                            $data1['log_note']="一代动态奖金获取";
                                            $data1['wallet_type']='2';
                                            M('wallet_log')->add($data1);
                                        }
                                    }
                                    if ($towparent) {//二代存在时,判断二代是否是VIP2及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$towparent,$towparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($towparent.','.'%','%'.','.$towparent,'%'.','.$towparent.','.'%',$towparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=2) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$towparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate2']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$towparent))->find();
                                            M('wallet')->where(array('user_id'=>$towparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$towparent))->find();
                                            $data2['user_id']=$towparent;
                                            $data2['user_name']=$parentuser['user_name'];
                                            $data2['user_phone']=$parentuser['user_phone'];
                                            $data2['amount']=$dongtaimoney;
                                            $data2['old_amount']=$userwallet['change_amount'];
                                            $data2['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data2['change_date']=time();
                                            $data2['log_note']="二代动态奖金获取";
                                            $data2['wallet_type']='2';
                                            M('wallet_log')->add($data2);
                                        }
                                    }
                                    if ($threeparent) {//三代存在时,判断三代是否是VIP3及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$threeparent,$threeparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($threeparent.','.'%','%'.','.$threeparent,'%'.','.$threeparent.','.'%',$threeparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=3) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$threeparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate3']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$threeparent))->find();
                                            M('wallet')->where(array('user_id'=>$threeparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$threeparent))->find();
                                            $data3['user_id']=$threeparent;
                                            $data3['user_name']=$parentuser['user_name'];
                                            $data3['user_phone']=$parentuser['user_phone'];
                                            $data3['amount']=$dongtaimoney;
                                            $data3['old_amount']=$userwallet['change_amount'];
                                            $data3['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data3['change_date']=time();
                                            $data3['log_note']="三代动态奖金获取";
                                            $data3['wallet_type']='2';
                                            M('wallet_log')->add($data3);
                                        }
                                    }
                                    if ($fourparent) {//四代存在时,判断四代是否是VIP4及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fourparent,$fourparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($fourparent.','.'%','%'.','.$fourparent,'%'.','.$fourparent.','.'%',$fourparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=4) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$fourparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate4']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fourparent))->find();
                                            M('wallet')->where(array('user_id'=>$fourparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fourparent))->find();
                                            $data4['user_id']=$fourparent;
                                            $data4['user_name']=$parentuser['user_name'];
                                            $data4['user_phone']=$parentuser['user_phone'];
                                            $data4['amount']=$dongtaimoney;
                                            $data4['old_amount']=$userwallet['change_amount'];
                                            $data4['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data4['change_date']=time();
                                            $data4['log_note']="四代动态奖金获取";
                                            $data4['wallet_type']='2';
                                            M('wallet_log')->add($data4);
                                        }
                                    }
                                    if ($fiveparent) {//五代存在时,判断五代是否是VIP5及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$fiveparent,$fiveparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($fiveparent.','.'%','%'.','.$fiveparent,'%'.','.$fiveparent.','.'%',$fiveparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=5) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$fiveparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate5']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$fiveparent))->find();
                                            M('wallet')->where(array('user_id'=>$fiveparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$fiveparent))->find();
                                            $data5['user_id']=$fiveparent;
                                            $data5['user_name']=$parentuser['user_name'];
                                            $data5['user_phone']=$parentuser['user_phone'];
                                            $data5['amount']=$dongtaimoney;
                                            $data5['old_amount']=$userwallet['change_amount'];
                                            $data5['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data5['change_date']=time();
                                            $data5['log_note']="五代动态奖金获取";
                                            $data5['wallet_type']='2';
                                            M('wallet_log')->add($data5);
                                        }
                                    }
                                    if ($sixparent) {//六代存在时,判断六代是否是VIP6及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sixparent,$sixparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($sixparent.','.'%','%'.','.$sixparent,'%'.','.$sixparent.','.'%',$sixparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel>=6) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$sixparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate6']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sixparent))->find();
                                            M('wallet')->where(array('user_id'=>$sixparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sixparent))->find();
                                            $data6['user_id']=$sixparent;
                                            $data6['user_name']=$parentuser['user_name'];
                                            $data6['user_phone']=$parentuser['user_phone'];
                                            $data6['amount']=$dongtaimoney;
                                            $data6['old_amount']=$userwallet['change_amount'];
                                            $data6['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data6['change_date']=time();
                                            $data6['log_note']="六代动态奖金获取";
                                            $data6['wallet_type']='2';
                                            M('wallet_log')->add($data6);
                                        }
                                    }
                                    if ($sevenparent) {//七代存在时,判断七代是否是VIP7及以上等级
                                        $push=[
                                            'user_parent'=>array('like',array('%'.','.$sevenparent,$sevenparent),'OR'),    //直推人数
                                        ];
                                        $push2=[
                                            'user_parent'=>array('like',array($sevenparent.','.'%','%'.','.$sevenparent,'%'.','.$sevenparent.','.'%',$sevenparent),'OR'),   //团队人数
                                        ];
                                        $directpush=M('user')->where($push)->where(array('is_active=1'))->count();//直推
                                        $myteams=M('user')->where($push2)->where(array('is_active=1'))->count();//团队
                                        $theviplecel=getvipleveltow($directpush,$myteams);
                                        if ($theviplecel==7) {
                                            //查询最近一次买入金额
                                            $lastmoney=M('HelpOrder')->where(array('user_id'=>$sevenparent,'order_type'=>'0'))->order('addtime desc')->getField('parent_amount');
                                            $basemoney=min($lastmoney,$pamount);
                                            $dongtaimoney=$basemoney*$config['reward_rate7']/100;
                                            $userwallet=M('wallet')->where(array('user_id'=>$sevenparent))->find();
                                            M('wallet')->where(array('user_id'=>$sevenparent))->setInc('change_amount',$dongtaimoney);
                                            //增加钱包变动记录
                                            $parentuser=M('user')->where(array('user_id'=>$sevenparent))->find();
                                            $data7['user_id']=$sevenparent;
                                            $data7['user_name']=$parentuser['user_name'];
                                            $data7['user_phone']=$parentuser['user_phone'];
                                            $data7['amount']=$dongtaimoney;
                                            $data7['old_amount']=$userwallet['change_amount'];
                                            $data7['remain_amount']=$userwallet['change_amount']+$dongtaimoney;
                                            $data7['change_date']=time();
                                            $data7['log_note']="七代动态奖金获取";
                                            $data7['wallet_type']='2';
                                            M('wallet_log')->add($data7);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * 用户45天之内不推荐新用户,转为静态会员,每小时执行一次
     */
    public function freezeUser3(){
        $config = M('Config')->find(1);
        $users = M('User')->select();
        foreach($users as $v){
            if($v['user_recomand_time']==0){
                $start_time = $v['user_add_time'];//会员加入的时间
            }else{
                $start_time = $v['user_recomand_time'];//上一次推荐下级的时间
            }
            $end_time = $start_time + $config['days_recommend_limit']*86400;
            $user_id = $v['user_id'];
            $lower = M('User')
                ->where("user_id != $user_id and user_add_time > $start_time and user_add_time < $end_time and is_active=1")
                ->select();//下级必须激活账户,才能算是推荐成功
            if($lower){
                $count = 0;
                foreach($lower as $m){
                    if(!empty($m['user_parent'])){
                        if(strpos($m['user_parent'],',')){
                            $arr = array_reverse(explode(',',$m['user_parent']));
                            $pid = $arr[0];
                        }else{
                            $pid = $m['user_parent'];
                        }
                    }
                    if($pid==$v['user_id']){
                        //45天之内推荐了下级
                        $count = $count +1;
                    }
                }
                //$wallet = M('Wallet')->where(['user_id'=>$v['user_id']])->find();
                if($count==0){
                    //没有推荐下级,转为静态会员
                    if($v['vip_status']=='2'){
                        M('user')->where(['user_id'=>$v['user_id']])->setField('vip_status','1');
                    }
                }
            }
        }
    }

    /**
    * 判断用户是否开启预约排单,开启时给用户自动排单,每小时执行一次
    */
    public function dayStaticIntrest(){
        $config=M('config')->find(1);
        $userlist=M('user')->where('is_active=1 and user_status=1')->select();//查询已激活且账户正常的用户
        foreach ($userlist as &$val) {
            if ($val['appoint']==1) {//开启了预约排单
                //获取用户最近一次的买入时间
                $lasttime=M('HelpOrder')->distinct(true)->where(array('user_id'=>$val['user_id']))->order('addtime desc')->getField('addtime');
                $lasttime=strtotime($lasttime);//转换为时间戳
                if ($lasttime+$val['appoint_day']*3600*24<time()) {//可以排单了
                    //根据排单额度检测排单币是否充足
                    if ($config['order_limit1']<=$val['appoint_money']&&$val['appoint_money']<=$config['order_limit2']) {
                        $orderbyte=M('wallet')->where(array('user_id'=>$val['user_id']))->getField('order_byte');
                        if ($orderbyte>=1) {
                            //减少拍单币
                            $neworderbyte=$orderbyte-1;
                            M('Wallet')->where(array('user_id'=>$val['user_id']))->save(['order_byte'=>$neworderbyte]);
                            $wallet_log1 = array(
                                'user_id'=>$val['user_id'],
                                'user_name'=>$val['user_name'],
                                'user_phone'=>$val['user_phone'],
                                'amount'=>'-1',
                                'old_amount'=>$orderbyte,
                                'remain_amount'=>$neworderbyte,
                                'change_date'=>time(),
                                'log_note'=>'预约排单消耗排单币',
                                'wallet_type'=>4,
                            );
                            M('WalletLog')->data($wallet_log1)->add();
                            //生成订单记录
                            $order['user_id'] = $val['user_id'];
                            $order['user_name'] = $val['user_name'];
                            $order['user_truename'] = $val['user_truename'];
                            $order['user_phone'] = $val['user_phone'];
                            $order['amount'] = $val['appoint_money'];
                            $order['zffs1'] = '1';
                            $order['zffs2'] = '1';
                            $order['zffs3'] = '1';
                            $order['parent_amount'] = $val['appoint_money'];//最原始订单总金额
                            $order['addtime'] = date('Y-m-d H:i:s',time());
                            $order['appointment']='1';
                            M('HelpOrder')->data($order)->add();
                            $id = M('help_order')->max('id');//获取数据表中最大的ID值
                            $add_order['parent_id']=$id;
                            M('help_order')->where(array('id'=>$id))->save($add_order);
                            //设置最近一次买入金额
                            M('User')->where(array('user_id'=>$val['user_id']))->setField('last_buy_amount',$val['appoint_money']);
                        }
                    }
                    if ($config['order_limit3']<=$val['appoint_money']&&$val['appoint_money']<=$config['order_limit4']) {
                        $orderbyte=M('wallet')->where(array('user_id'=>$val['user_id']))->getField('order_byte');
                        if ($orderbyte>=2) {
                            //减少拍单币
                            $neworderbyte=$orderbyte-2;
                            M('Wallet')->where(array('user_id'=>$val['user_id']))->save(['order_byte'=>$neworderbyte]);
                            $wallet_log1 = array(
                                'user_id'=>$val['user_id'],
                                'user_name'=>$val['user_name'],
                                'user_phone'=>$val['user_phone'],
                                'amount'=>'-2',
                                'old_amount'=>$orderbyte,
                                'remain_amount'=>$neworderbyte,
                                'change_date'=>time(),
                                'log_note'=>'预约排单消耗排单币',
                                'wallet_type'=>4,
                            );
                            M('WalletLog')->data($wallet_log1)->add();
                            //生成订单记录
                            $order['user_id'] = $val['user_id'];
                            $order['user_name'] = $val['user_name'];
                            $order['user_truename'] = $val['user_truename'];
                            $order['user_phone'] = $val['user_phone'];
                            $order['amount'] = $val['appoint_money'];
                            $order['zffs1'] = '1';
                            $order['zffs2'] = '1';
                            $order['zffs3'] = '1';
                            $order['parent_amount'] = $val['appoint_money'];//最原始订单总金额
                            $order['addtime'] = date('Y-m-d H:i:s',time());
                            $order['appointment']='1';
                            M('HelpOrder')->data($order)->add();
                            $id = M('help_order')->max('id');//获取数据表中最大的ID值
                            $add_order['parent_id']=$id;
                            M('help_order')->where(array('id'=>$id))->save($add_order);
                            //设置最近一次买入金额
                            M('User')->where(array('user_id'=>$val['user_id']))->setField('last_buy_amount',$val['appoint_money']);
                        }
                    }
                    if ($config['order_limit5']<=$val['appoint_money']&&$val['appoint_money']<=$config['order_limit6']) {
                        $orderbyte=M('wallet')->where(array('user_id'=>$val['user_id']))->getField('order_byte');
                        if ($orderbyte>=3) {
                            //减少拍单币
                            $neworderbyte=$orderbyte-3;
                            M('Wallet')->where(array('user_id'=>$val['user_id']))->save(['order_byte'=>$neworderbyte]);
                            $wallet_log1 = array(
                                'user_id'=>$val['user_id'],
                                'user_name'=>$val['user_name'],
                                'user_phone'=>$val['user_phone'],
                                'amount'=>'-3',
                                'old_amount'=>$orderbyte,
                                'remain_amount'=>$neworderbyte,
                                'change_date'=>time(),
                                'log_note'=>'预约排单消耗排单币',
                                'wallet_type'=>4,
                            );
                            M('WalletLog')->data($wallet_log1)->add();
                            //生成订单记录
                            $order['user_id'] = $val['user_id'];
                            $order['user_name'] = $val['user_name'];
                            $order['user_truename'] = $val['user_truename'];
                            $order['user_phone'] = $val['user_phone'];
                            $order['amount'] = $val['appoint_money'];
                            $order['zffs1'] = '1';
                            $order['zffs2'] = '1';
                            $order['zffs3'] = '1';
                            $order['parent_amount'] = $val['appoint_money'];//最原始订单总金额
                            $order['addtime'] = date('Y-m-d H:i:s',time());
                            $order['appointment']='1';
                            M('HelpOrder')->data($order)->add();
                            $id = M('help_order')->max('id');//获取数据表中最大的ID值
                            $add_order['parent_id']=$id;
                            M('help_order')->where(array('id'=>$id))->save($add_order);
                            //设置最近一次买入金额
                            M('User')->where(array('user_id'=>$val['user_id']))->setField('last_buy_amount',$val['appoint_money']);
                        }
                    }
                }
            }
        }
    }
    /**
    * 自动进行匹配,每分钟执行一次
    */
    public function autoMatchOrder(){
        $config = M('Config')->find(1);
        if($config['is_man_match']==1){//开启状态:1.开启  2.关闭
            //查询所有需要匹配的买入订单
            $buylist=M('HelpOrder')->where(array('order_type'=>'1','matching'=>'0'))->order('addtime asc')->select();//预付订单
            foreach ($buylist as &$val) {
                //查询接受帮助表中金额相对应的待匹配订单
                $userid=$val['user_id'];
                //优先查询等额匹配
                $firstone=[
                    'matching'=>'0',
                    'amount'=>$val['amount'],
                    'user_id'=>array('neq',$userid),
                ];
                $resultone=M('AskhelpOrder')->where($firstone)->order('addtime asc')->find();
                if ($resultone) {
                    $res=oneMatch($val['id'],$resultone['id']);
                }else{
                    $map2=[
                        'matching'=>'0',
                        'amount'=>array('egt',$val['amount']),
                        'user_id'=>array('neq',$userid),
                    ];
                    $result=M('AskhelpOrder')->where($map2)->order('addtime asc')->find();
                    if ($result) {
                        $res1=oneMatch($val['id'],$result['id']);
                    }
                }
            }
            $map=[
                'addtime'=>array('elt',date('Y-m-d H:i:s',time()-4*86400)),     //默认4天后开始匹配
            ];
            $feibuylist=M('HelpOrder')->where(array('order_type'=>'2','matching'=>'0'))->where($map)->order('addtime asc')->select();//非预付订单
            foreach ($feibuylist as &$value) {
                //查询接受帮助表中金额相对应的待匹配订单
                $userid2=$value['user_id'];
                //优先查询等额匹配
                $firsttow=[
                    'matching'=>'0',
                    'amount'=>$value['amount'],
                    'user_id'=>array('neq',$userid2),
                ];
                $resulttow=M('AskhelpOrder')->where($firsttow)->order('addtime asc')->find();
                if ($resulttow) {
                    $res2=oneMatch($value['id'],$resulttow['id']);
                }else{
                    $map3=[
                        'matching'=>'0',
                        //'amount'=>$value['amount'],
                        'user_id'=>array('neq',$userid2),
                    ];
                    $result1=M('AskhelpOrder')->where($map3)->order('addtime asc')->find();
                    if ($result1) {
                        $res3=oneMatch($value['id'],$result1['id']);
                    }
                }
            }
        }
    }
}

