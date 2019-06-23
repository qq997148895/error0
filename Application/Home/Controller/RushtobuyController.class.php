<?php

namespace Home\Controller;

use Common\Controller\HomeBaseController;
use http\Env\Request;
use Think\Controller;

class RushtobuyController extends HomeBaseController
{

    /**
     * 初始化页面  抢购页面
     */
    public function rob()
    {
        $uid=session('user_id');
        //查询数据库   stock_coupon
        $coupon = M('stock_coupon')->find();
        $config = M('config')->find();
        $num = M('wallet')->where(array('user_id'=>$uid))->getField('order_byte');
        $this->assign('num', $num);
        $this->assign('coupon', $coupon);
        $this->assign('config', $config);
        $this->display('Index/rushtobuy');

    }

    //积分兑换
    public function dui(){
        $uid = session('user_id');
        $num = I('post.num');
        $config = M('config')->find();//配置表
        $jifen = $num * $config['draw'];
        $wallet = M('wallet')->where(array('user_id'=>$uid))->find();
        $user = M('user')->where(array('user_id'=>$uid))->find();
        if($jifen > $wallet['change_amount']){
            $this->ajaxreturn(['status' => '0', 'message' => '兑换失败，积分不足！']);
        }
        $res = M('wallet')->where(array('user_id'=>$uid))->setDec('change_amount',$jifen);
        if($res){
            $res = M('wallet')->where(array('user_id'=>$uid))->setInc('order_byte',$num);
            $wallet_log1 = array(
                'user_id' => $uid,
                'user_name' => $user['user_name'],
                'user_phone' => $user['user_phone'],
                'amount' => '-' . $jifen,
                'old_amount' => $wallet['change_amount'],
                'remain_amount' => $wallet['change_amount'] + $jifen,
                'change_date' => time(),
                'log_note' => '积分兑换股权增值券',
                'wallet_type' => 2,
            );
            $result1 = M('WalletLog')->data($wallet_log1)->add(); //积分记录
            $wallet_log1 = array(
                'user_id' => $uid,
                'user_name' => $user['user_name'],
                'user_phone' => $user['user_phone'],
                'amount' => '+' . $num,
                'old_amount' => $wallet['static_amount'],
                'remain_amount' => $wallet['static_amount'] + $num,
                'change_date' => time(),
                'log_note' => '积分兑换股权增值券',
                'wallet_type' => 4,
            );
            $result1 = M('WalletLog')->data($wallet_log1)->add(); //股权增值券记录
            $this->ajaxreturn(['status' => '1', 'message' => '兑换成功']);
        }else{
            $this->ajaxreturn(['status' => '0', 'message' => '兑换失败']);
        }
    }

    //免费抢购股权增值券
    public function robCoupon()
    {
        $coupon = M('stock_coupon')->find();//股权增值券抢购表
        $userid = session('user_id');
        $type = I('post.type');
        $config = M('config')->find();//配置表
        $user = M('user')->where(array('user_id' => $userid))->find();//会员表
        $oldamount = M('wallet')->where(array('user_id' => $userid))->find(); //原来余额
        //免费抢购
        if ($type == 1) {
            $now = date('Y-m-d H:i:s', time());
//            dump(substr($now,11,2));
            $time = substr($now, 11, 2);
            $oldtime = substr($coupon['up_time'], 0, 13);
            $newtime = substr($now, 0, 13);
            //判断是否是在早上10点。下午3点。晚上8点
            if ($time == 10 || $time == 15 ||$time == 20) {
                //判断该用户有没有抢过  抢过就不能抢了
//                if ($oldtime == $newtime) {
                    $key_arr = explode(',', $coupon['idlist']);
                    if (in_array($userid, $key_arr)) {
                        //抢过
                        $this->ajaxreturn(['status' => '0', 'message' => '你已抢过！请下次参与']);
                    }
//                }else {
                    //没抢过
                    M('stock_coupon')->where(array('id'=>1))->data(array('up_time'=>$now))->save();
                    $coupon = M('stock_coupon')->find();//股权增值券抢购表
                    //分为两种 平均  和随机  首先查询分配方式
                    if ($coupon['isaverage'] == 1) {
                        if ($coupon['couponnumber'] > 0) {
                            //平均分配  不用调用工具类 抢到的值是固定的
                            $couponValue = $coupon['aneragevalue']; //抢到的个数
                            //抢到后 要维护股权表 钱包表
                            //维护日志表
                            //首先维护stock_coupon表 数量  和 用户id
                            $news1['couponnumber'] = $coupon['couponnumber'] - $couponValue;
                            if (!empty($coupon['idlist'])) {
                                $news1['idlist'] = $coupon['idlist'] . ',' . $userid;//抢过的用户id
                            } else {
                                $news1['idlist'] = $userid;
                            }
                            //更新数据
                            $result1 = D('stock_coupon')->where(array('id' => 1))->save($news1);
                            D('stock_coupon')->where(array('id' => 1))->setDec('robnumber');
                            //维护钱包表  在原来的基础上加上现在的值
                            $news2['order_byte'] = $oldamount['order_byte'] + $couponValue;
                            $result2 = D('wallet')->where(array('user_id' => $userid))->save($news2);
                            if ($result1 && $result2) {
                                //维护日志表  wallet_log
                                $wallet_log = M('wallet_log');
                                $wallet_log_info['user_id'] = $user['user_id'];//用户id
                                $wallet_log_info['user_name'] = $user['user_name'];//用户名
                                $wallet_log_info['user_phone'] = $user['user_phone'];//手机号
                                $wallet_log_info['amount'] = $couponValue;//资金变动数量
                                $wallet_log_info['old_amount'] = $oldamount['order_byte'];//原来余额
                                $wallet_log_info['remain_amount'] = $oldamount['order_byte'] + $couponValue;//现在余额  （原来余额+ 资金变动）
                                $wallet_log_info['change_date'] = time();//变动时间
                                $wallet_log_info['log_note'] = '股权增值券抢购';//信息描述
                                $wallet_log_info['wallet_type'] = 4;//变动类型
                                $wallet_log->add($wallet_log_info);
                                $this->ajaxreturn(['status' => '1', 'message' => '恭喜你抢到：' . $couponValue]);
                            } else {
                                $this->ajaxreturn(['status' => '0', 'message' => '系统异常！请重试']);
                            }
                        } else {
                            $this->ajaxreturn(['status' => '0', 'message' => '增值券已经抢完！']);
                        }
                    } else {
                        //随机分配
                        //抢到后 要维护股权表  config表
                        if ($coupon['couponnumber'] > 0) {
                            //抢到后 要维护股权表 钱包表
                            $list = $this->distribute_red_bages($coupon['couponnumber']);
                            $couponValue = $list[0];//抢到的个数
                            //首先维护stock_coupon表
                            $news1 = $coupon['couponnumber'] - $couponValue;
                            //最后一次必须要大于0  否则把当前的数量给他
                            if ($news1 >= 0) {
                                $news3['couponnumber'] = $coupon['couponnumber'] - $couponValue;
                                if (!empty($coupon['idlist'])) {
                                    $news3['idlist'] = $news3['idlist'] . ',' . $userid;//所有上级
                                } else {
                                    $news3['idlist'] = $userid;
                                }
                                //更新数据
                                $result1 = D('stock_coupon')->where(array('id' => 1))->save($news3);
                                D('stock_coupon')->where(array('id' => 1))->setDec('robnumber');
                                //维护钱包表  在基础上加抢到的 数量*金额
                                $news2['order_byte'] = $oldamount['order_byte'] + $couponValue;
//                    dump($couponValue*$config['paidan_price']);//抢到的金额

                                $result2 = D('wallet')->where(array('user_id' => $userid))->save($news2);
                                if ($result1 && $result2) {
                                    //维护日志表
                                    $wallet_log = M('wallet_log');
                                    $wallet_log_info['user_id'] = $user['user_id'];//用户id
                                    $wallet_log_info['user_name'] = $user['user_name'];//用户名
                                    $wallet_log_info['user_phone'] = $user['user_phone'];//手机号
                                    $wallet_log_info['amount'] = '+' . $couponValue;//资金变动数量
                                    $wallet_log_info['old_amount'] = $oldamount['order_byte'];//原来余额
                                    $wallet_log_info['remain_amount'] = $oldamount['order_byte'] + $couponValue;//现在余额  （原来余额+ 资金变动）
                                    $wallet_log_info['change_date'] = time();//变动时间
                                    $wallet_log_info['log_note'] = '股权增值券';//信息描述
                                    $wallet_log_info['wallet_type'] = 3;//变动类型
                                    $wallet_log->add($wallet_log_info);
                                    $this->ajaxreturn(['status' => '1', 'message' => '恭喜你抢到！' . $couponValue]);
                                } else {
                                    $this->ajaxreturn(['status' => '0', 'message' => '系统异常！请重试']);
                                }
                            } else {
                                //最后一个红包就是剩余的数量
                                $couponValue = $coupon['couponnumber'];
                                $news4['couponnumber'] = 0;
                                $result1 = D('stock_coupon')->save($news4);
                                //维护钱包表
                                $news2['order_byte'] = $oldamount['order_byte'] + $couponValue * $config['paidan_price'];
                                $result2 = D('wallet')->where(array('user_id' => $userid))->save($news2);
                                if ($result1 && $result2) {
                                    //维护日志表
                                    $wallet_log = M('wallet_log');
                                    $wallet_log_info['user_id'] = $user['user_id'];//用户id
                                    $wallet_log_info['user_name'] = $user['user_name'];//用户名
                                    $wallet_log_info['user_phone'] = $user['user_phone'];//手机号
                                    $wallet_log_info['amount'] = '+' . $couponValue;//资金变动数量
                                    $wallet_log_info['old_amount'] = $oldamount['order_byte'];//原来余额
                                    $wallet_log_info['remain_amount'] = $oldamount['order_byte'] + $couponValue;//现在余额  （原来余额+ 资金变动）
                                    $wallet_log_info['change_date'] = time();//变动时间
                                    $wallet_log_info['log_note'] = '股权增值券';//信息描述
                                    $wallet_log_info['wallet_type'] = 3;//变动类型
                                    $wallet_log->add($wallet_log_info);
                                    $this->ajaxreturn(['status' => '1', 'message' => '恭喜你抢到！' . $couponValue * $config['paidan_price']]);
                                } else {
                                    $this->ajaxreturn(['status' => '0', 'message' => '系统异常！请重试']);
                                }
                            }
                        } else {
                            $this->ajaxreturn(['status' => '0', 'message' => '增值券已经抢完！']);
                        }
//                    }
                }
            }
            else {
                $this->ajaxreturn(['status' => '0', 'message' => '请在规定的时间内抢购']);
            }
        }
        //积分抢购
        if ($type == 2) {
            $res = M('wallet')->where('user_id', $userid)->setDec('change_amount', $config['draw']);
            if ($res) {
                //分为两种 平均  和随机  首先查询分配方式
                if ($coupon['isaverage'] == 1) {
                    if ($coupon['couponnumber'] > 0) {
                        //平均分配  不用调用工具类 抢到的值是固定的
                        $couponValue = $coupon['aneragevalue']; //抢到的个数
                        //抢到后 要维护股权表 钱包表
                        //维护日志表
                        //首先维护stock_coupon表 数量  和 用户id

                        $news1['couponnumber'] = $coupon['couponnumber'] - $couponValue;
                        //更新数据
                        $result1 = D('stock_coupon')->where(array('id' => 1))->save($news1);
                        D('stock_coupon')->where(array('id' => 1))->setDec('robnumber');
                        //维护钱包表  在原来的基础上加上现在的值
                        $news2['order_byte'] = $oldamount['order_byte'] + $couponValue;
                        $result2 = D('wallet')->where(array('user_id' => $userid))->save($news2);
                        if ($result1 && $result2) {
                            //维护日志表  wallet_log
                            $wallet_log = M('wallet_log');
                            $wallet_log_info['user_id'] = $user['user_id'];//用户id
                            $wallet_log_info['user_name'] = $user['user_name'];//用户名
                            $wallet_log_info['user_phone'] = $user['user_phone'];//手机号
                            $wallet_log_info['amount'] = $couponValue;//资金变动数量
                            $wallet_log_info['old_amount'] = $oldamount['order_byte'];//原来余额
                            $wallet_log_info['remain_amount'] = $oldamount['order_byte'] + $couponValue;//现在余额  （原来余额+ 资金变动）
                            $wallet_log_info['change_date'] = time();//变动时间
                            $wallet_log_info['log_note'] = '股权增值券抢购';//信息描述
                            $wallet_log_info['wallet_type'] = 4;//变动类型
                            $wallet_log->add($wallet_log_info);
                            $this->ajaxreturn(['status' => '1', 'message' => '恭喜你抢到：' . $couponValue]);
                        } else {
                            $this->ajaxreturn(['status' => '0', 'message' => '系统异常！请重试']);
                        }
                    } else {
                        $this->ajaxreturn(['status' => '0', 'message' => '增值券已经抢完！']);
                    }
                } else {
                    //随机分配
                    //抢到后 要维护股权表  config表
                    if ($coupon['couponnumber'] > 0) {
                        //抢到后 要维护股权表 钱包表
                        $list = $this->distribute_red_bages($coupon['couponnumber']);
                        $couponValue = $list[0];//抢到的个数
                        //首先维护stock_coupon表
                        $news1 = $coupon['couponnumber'] - $couponValue;
                        //最后一次必须要大于0  否则把当前的数量给他
                        if ($news1 >= 0) {
                            $news3['couponnumber'] = $coupon['couponnumber'] - $couponValue;
                            //更新数据
                            $result1 = D('stock_coupon')->where(array('id' => 1))->save($news3);
                            D('stock_coupon')->where(array('id' => 1))->setDec('robnumber');
                            //维护钱包表  在基础上加抢到的 数量*金额
                            $news2['order_byte'] = $oldamount['order_byte'] + $couponValue;
//                    dump($couponValue*$config['paidan_price']);//抢到的金额

                            $result2 = D('wallet')->where(array('user_id' => $userid))->save($news2);
                            if ($result1 && $result2) {
                                //维护日志表
                                $wallet_log = M('wallet_log');
                                $wallet_log_info['user_id'] = $user['user_id'];//用户id
                                $wallet_log_info['user_name'] = $user['user_name'];//用户名
                                $wallet_log_info['user_phone'] = $user['user_phone'];//手机号
                                $wallet_log_info['amount'] = '+' . $couponValue;//资金变动数量
                                $wallet_log_info['old_amount'] = $oldamount['order_byte'];//原来余额
                                $wallet_log_info['remain_amount'] = $oldamount['order_byte'] + $couponValue;//现在余额
                                $wallet_log_info['change_date'] = time();//变动时间
                                $wallet_log_info['log_note'] = '股权增值券';//信息描述
                                $wallet_log_info['wallet_type'] = 3;//变动类型
                                $wallet_log->add($wallet_log_info);
                                $this->ajaxreturn(['status' => '1', 'message' => '恭喜你抢到！' . $couponValue * $config['paidan_price']]);
                            } else {
                                $this->ajaxreturn(['status' => '0', 'message' => '系统异常！请重试']);
                            }
                        } else {
                            //最后一个红包就是剩余的数量
                            $couponValue = $coupon['couponnumber'];
                            $news4['couponnumber'] = 0;
                            $result1 = D('stock_coupon')->save($news4);
                            //维护钱包表
                            $news2['order_byte'] = $oldamount['order_byte'] + $couponValue * $config['paidan_price'];
                            $result2 = D('wallet')->where(array('user_id' => $userid))->save($news2);
                            if ($result1 && $result2) {
                                //维护日志表
                                $wallet_log = M('wallet_log');
                                $wallet_log_info['user_id'] = $user['user_id'];//用户id
                                $wallet_log_info['user_name'] = $user['user_name'];//用户名
                                $wallet_log_info['user_phone'] = $user['user_phone'];//手机号
                                $wallet_log_info['amount'] = '+' . $couponValue;//资金变动数量
                                $wallet_log_info['old_amount'] = $oldamount['order_byte'];//原来余额
                                $wallet_log_info['remain_amount'] = $oldamount['order_byte'] + $couponValue;//现在余额  （原来余额+ 资金变动）
                                $wallet_log_info['change_date'] = time();//变动时间
                                $wallet_log_info['log_note'] = '股权增值券';//信息描述
                                $wallet_log_info['wallet_type'] = 3;//变动类型
                                $wallet_log->add($wallet_log_info);
                                $this->ajaxreturn(['status' => '0', 'message' => '请到股权查看！']);
                            } else {
                                $this->ajaxreturn(['status' => '0', 'message' => '系统异常！请重试']);
                            }
                        }
                    } else {
                        $this->ajaxreturn(['status' => '0', 'message' => '增值券已经抢完！']);
                    }
                }
            } else {
                $this->ajaxreturn(['status' => '0', 'message' => '积分不足！']);
            }
        }
    }

    /*
    方法主要功能：拼手气红包（个数不定）
    一个参数
        参数一： 红包总金额(按分计算)
    */
    public function distribute_red_bages($sum)
    {
        $sum = $sum;
        $i = 0;
        while ($sum > 0) {
            $temp = rand(1, $sum);//红包值
            $sum -= $temp;
            $arr[$i++] = $temp;
        }
        //check($arr);
        return $arr;
    }

    /*
方法主要功能：均分红包
两个参数：
    参数一： 红包总金额
    参数二： 均分个数
*/
    public function average_red_bages($sum, $num)
    {
        $res = $sum / $num;
        for ($i = 0; $i < $num; $i++) {
            $arr[$i] = $res;
        }
        //check($arr);
        return $arr;
    }


    //商家入驻
    public function merchantEntry()
    {
        //首先判断用户是否是商家
        $userid = session('user_id');
        $userinfo = M('user')->where(array('user_id' => $userid))->find();
        if ($userinfo['user_ismerchant'] == 1) {
            //是则返回你已是商家
            $merchantinfo = M('merchant')->where(array('user_id' => $userid))->find();
            $this->assign('userinfo', $userinfo);
            $this->assign('merchant', $merchantinfo);
            $this->display('Transfer/merchantinfo');

        } else {
            //不是商家  商家入驻需要显示入驻所需股权值，输入商家名称，点击成为商家按钮，线下付款，总后台审核，通过成为商家
            $stock_enter = M('config')->find();
            $this->assign('stock_enter', $stock_enter);
            $this->display('Transfer/merchantEntry');
        }
    }

    //申请入驻
    public function apply()
    {
        $userid = session('user_id');
        $userinfo = M('user')->where(array('user_id' => $userid))->find();
        //首先判断股权制是否足够 不足够则不能成为商家代理

        //商家申请入驻 user表中的状态 （若审核不通过则改为0）  merchant 中的数据
        if (IS_POST) {
            $merchant_name = I('post.merchant_name');//商家名称
            if ($merchant_name == '') {//商家名称
                $this->ajaxreturn(['status' => '0', 'message' => '商家名称不能为空！']);
            }

            $user['user_ismerchant'] = 1;
            $result1 = M('user')->where(array('user_id' => $userid))->data($user)->save();

            $merchant = M('merchant');
            $merchantinfo['user_id'] = $userinfo['user_id'];//商家id
            $merchantinfo['merchant_name'] = $merchant_name;//商家名称
            $merchantinfo['merchant_id'] = $userinfo['user_id'];//商家编号
            $merchantinfo['merchant_status'] = 0;//审核状态：待审核
            $merchantinfo['jointime'] = date('Y-m-d', time());//入驻时间
            $result2 = $merchant->add($merchantinfo);
            if ($result1 && $result2) {
                //操作成功   提示 审核中

                $this->ajaxreturn(['status' => '1', 'message' => '审核中！']);
            } else {
                //操作失败  请重试
                $this->ajaxreturn(['status' => '0', 'message' => '操作失败，请重试！']);
            }
        }
    }
}