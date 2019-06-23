<?php
/**
 * Created by Atom.
 * User: yum
 * Date: 2017/3/20 0020
 * Time: 10:07
 */

namespace Home\Model;

use Think\Model;
// use Home\Model\ProduceggsLogModel;
/**
 * 用户信息类
 */
class UserModel extends Model
{
    /**
     * 返回用户的所有信息
     * @param  [int]   用户id
     * @return {[array]}     [一维数组]
     */
    public function getUserInfo($user_id)
    {
        return $this->where(['user_id' => $user_id])->find();
    }
    
    /*
     * 添加数据
     * @param  {[array]}   提交数据
     * @return [bool]   返回成功或失败
     */
    public function userAdd($record)
    {
        return $this->add($record);
    }
    /*
     * 更新数据
     */
    public function saveUserInfo($user_id,$data)
    {
        return $this->where(array('user_id' => $user_id))->save($data);
    }

    /**
     * [getEggs 收获当日一块池塘的生产的蛋]
     * @param  [int] $user_id [用户ID]
     * @param  [int] $land_id [池塘ID]
     * @return [int]          [返回此次收蛋数量或0]
     */
    // public function getEggs($user_id, $land_id)
    // {
    //     //查询表是否当天有此用户和此地的收益
    //     //如有则
    //     //1. 将收益加到钱包，
    //     //2. 将收益加到土地的总收益记录
    //     //3. 将此条记录到日志，
    //     //4. 再写到收蛋表
    //     //5. 写到 用户的每日生产日志表
    //     $p_map['user_id'] = $user_id;
    //     $p_map['land_id'] = $land_id;
    //     $p_map['type'] = 0;
    //     $p_map['add_time'] = array('egt', timezreo());
    //     // dump($p_map);die;
    //     // $friends = D('Receipts_eggs')->where($f_map)->select();
    //     $produceggs = D('LandTodayProduceggs')->where($p_map)->find();
    //     // array(6) {
    //     //   ["id"] => string(4) "1062"
    //     //   ["user_id"] => string(2) "22"
    //     //   ["land_id"] => string(1) "3"
    //     //   ["eggs"] => string(4) "0.00"
    //     //   ["add_time"] => string(10) "1522169999"
    //     //   ["type"] => string(1) "0"
    //     // }
    //     // dump($produceggs);die;
    //     if (!$produceggs || $produceggs['eggs']<=0){
    //         return false;
    //     }

    //     $m = M();
    //     $m->startTrans();
    //     try {
    //         //将当前用户点过收获的记录类型改变，0为未，1为收
            
    //         $produceggs['type'] = 1;
    //         // dump($produceggs);die;
    //        $re1 =  D('LandTodayProduceggs')->where($p_map)->save($produceggs);

    //         //将收益加到钱包
    //         $re2 = D('Wallet')->where(['user_id'=>$user_id])->setInc('egg_amount', $produceggs['eggs']);
    //         //将收益加到土地的总数量
    //         $re3 = D('Land')->where(['user_id'=>$user_id, 'land_id'=>$land_id])->setInc('total_egg_produce', $produceggs['eggs']);

    //         //钱包收益蛋的数量记录写到日志数据表
    //         $w_data = [
    //             'user_id'       => $user_id,
    //             'amount'        => $produceggs['eggs'],
    //             'change_date'   => time(),
    //             'log_note'      => '收获'.$produceggs['eggs'].'只猫仔',
    //             'type'          => 5
    //         ];
    //         $re4 = D('WalletLog')->add($w_data);

    //         //将此次收蛋写到收蛋表让上级打扫使用
    //         $r_data = [
    //             'user_id'       => $user_id,
    //             'land_id'       => $land_id,
    //             'eggs'          => $produceggs['eggs'],
    //             'add_time'      => time()
    //         ];

    //         $re5 = D('ReceiptsEggs')->add($r_data);
    //         //将此次收蛋加到生产日志表让用户查询使用
    //         $proc = new ProduceggsLogModel;
    //         $proc->addProc($user_id, $produceggs['eggs']);

    //         $m->commit();
    //     } catch (PDOException $e) {
    //         $m->rollback();
    //         return false;
    //     }
    //     return $produceggs['eggs'];
    // }

    /**
     * [sweepEgg 打扫功能]
     * @param  [int] $user_id      [用户ID]
     * @param  [array] $friends_id [所有好友的ID]
     * @return [int]               [用户收蛋数量或0]
     */
    
    public function sweepEgg($user_id,$friends_id)
    {
        if(!$friends_id){
            return false;
        }

        $settings = include(APP_PATH . '/../Application/Common/Conf/settings.php');
        //烧伤开关判断
        if(!empty($settings['shaoshang'])){
            //如果今日未拆分，不允许打扫
            $self_map['user_id'] = $user_id;
            $self_map['add_time'] = array('egt', timezreo());
            $self_geteggs = M('land_today_produceggs')->where($self_map)->sum('eggs');
            if(empty($self_geteggs)){
                return false;
            }else{
                $selfgeteggs=num2point($self_geteggs*$settings['shaoshang']/100);
            }
            $user_Wallet_Info = M('Wallet') -> where(['user_id' => $user_id]) -> find();
            if($user_Wallet_Info['sweep_time'] < timezreo()){
                $user_Wallet_Info['sweep']=0;
            }
            //如果可收获数量已满，直接打扫失败
            if($selfgeteggs <= $user_Wallet_Info['sweep']){
                return false;
            }   
        }
        // 1. 找到所有下级的本日蛋收益,
        $f_map['user_id'] = array('in', $friends_id);
        $f_map['add_time'] = array('egt', timezreo());
        $friends = D('Receipts_eggs')->where($f_map)->select();
        $friends_eggs = 0;
        $sweep_log = [];
        // 2. 把所有条数写到数组方便添加到打扫记录
        foreach ($friends as $key => $friend) {
            $sweep_egg =$friend['eggs'];
            $sweep_egg=num2point($sweep_egg * C('receipts_raito'));   
            $friends_eggs += $sweep_egg;
            //烧伤处理
            if(!empty($settings['shaoshang'])){
                if($friends_eggs > ($selfgeteggs-$user_Wallet_Info['sweep'])){
                    //添加最后一次数据的记录
                    $sweep_egg=num2point($sweep_egg-($friends_eggs-$selfgeteggs+$user_Wallet_Info['sweep']));
                    $friends_eggs=num2point($selfgeteggs-$user_Wallet_Info['sweep']);

                    $sweep_log[$key]['user_id']    = $user_id;
                    $sweep_log[$key]['friend_id']  = $friend['user_id'];
                    $sweep_log[$key]['sweep_eggs'] = $sweep_egg;
                    $sweep_log[$key]['sweep_time'] = time();
                    $sweep_log[$key]['status']     = 0;

                    break;
                }
            }

            $sweep_log[$key]['user_id']    = $user_id;
            $sweep_log[$key]['friend_id']  = $friend['user_id'];
            $sweep_log[$key]['sweep_eggs'] = $sweep_egg;
            $sweep_log[$key]['sweep_time'] = time();
            $sweep_log[$key]['status']     = 0;
        }
        // var_dump($friends);
        // var_dump($friends_eggs);
        $m = M();
        $m->startTrans();
        try {
            // 3. 把所有蛋收益*0.1加给当前用户
            D('Wallet')->where(['user_id'=>$user_id])->setInc('egg_amount', $friends_eggs);
            M('Wallet') -> where(['user_id' => $user_id]) -> save(array('sweep'=>($user_Wallet_Info['sweep']+$friends_eggs),'sweep_time'=>time()));
            $userWalletInfo = D('Wallet') -> where(['user_id' => $user_id]) -> find();
            // 4. 钱包收益记录写到数据库
            $w_data = [
                'user_id'       => $user_id,
                'amount'        => $userWalletInfo['fish_amount'],
                'change_date'   => time(),
                'log_note'      => '打扫获得'.$friends_eggs.'只猫仔',
                'type'          => 5,
            ];
            D('WalletLog')->add($w_data);
            // 5. 把打扫记录写到数据库
            D('SweepLog')->addAll($sweep_log);
            $m->commit();
        } catch (PDOException $e) {
            $m->rollback();
            return false;
        }
        return $friends_eggs;
    }

    
    //未添加烧伤的旧的方式，留作备份参考
    // public function sweepEgg($user_id,$friends_id)
    // {
    //     if(!$friends_id){
    //         return false;
    //     }
    //     // 1. 找到所有下级的本日蛋收益,
    //     $f_map['user_id'] = array('in', $friends_id);
    //     $f_map['add_time'] = array('egt', timezreo());
    //     $friends = D('Receipts_eggs')->where($f_map)->select();
    //     $friends_eggs = 0;
    //     $sweep_log = [];
    //     // 2. 把所有条数写到数组方便添加到打扫记录
    //     foreach ($friends as $key => $friend) {
    //         $sweep_egg =$friend['eggs'];
    //         $sweep_egg=num2point($sweep_egg * C('receipts_raito'));   

    //         $friends_eggs += $sweep_egg;
    //         $sweep_log[$key]['user_id']    = $user_id;
    //         $sweep_log[$key]['friend_id']  = $friend['user_id'];
    //         $sweep_log[$key]['sweep_eggs'] = $sweep_egg;
    //         $sweep_log[$key]['sweep_time'] = time();
    //         $sweep_log[$key]['status']     = 0;
    //     }
    //     // var_dump($friends);
    //     // var_dump($friends_eggs);

    //     $m = M();
    //     $m->startTrans();
    //     try {
    //         // 3. 把所有蛋收益*0.1加给当前用户
    //         D('Wallet')->where(['user_id'=>$user_id])->setInc('egg_amount', $friends_eggs);

    //         $userWalletInfo = D('Wallet') -> where(['user_id' => $user_id]) -> find();
    //         // 4. 钱包收益记录写到数据库
    //         $w_data = [
    //             'user_id'       => $user_id,
    //             'amount'        => $userWalletInfo['fish_amount'],
    //             'change_date'   => time(),
    //             'log_note'      => '打扫获得'.$friends_eggs.'只猫仔',
    //             'type'          => 5,
    //         ];
    //         D('WalletLog')->add($w_data);
    //         // 5. 把打扫记录写到数据库
    //         D('SweepLog')->addAll($sweep_log);
    //         $m->commit();
    //     } catch (PDOException $e) {
    //         $m->rollback();
    //         return false;
    //     }
    //     return $friends_eggs;
    // }




}
