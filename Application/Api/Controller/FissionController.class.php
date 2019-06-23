<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 15:46
 */

namespace Api\Controller;

use Think\Controller;

//定时任务类
class FissionController extends Controller
{
    protected $config;

    public function __construct()
    {
        $this->config = M('config')->find();
    }

    public function index()
    {
        $config = $this->config;
        $jhc_all = M('wallet')->sum('jhc_amount');
        if ($config['fission_num'] == 0 && $jhc_all <= $config['all_currency']) {
            if ($jhc_all >= $config['one_fission']) {
                M()->startTrans();
                try {
                    M('wallet')->execute('update mf_wallet set jhc_amount=jhc_amount*2');
                    M('config')->where(['id' => 1])->setDec('jhc_price', $config['jhc_price'] / 2);
                    M('config')->where(['id' => 1])->save(['fission_num' => 1]);
                    M()->commit();
                } catch (\Exception $e) {
                    M()->rollback();
                    exit('分裂失败');
                }
            }
        } elseif ($config['fission_num'] == 1 && $jhc_all <= $config['all_currency']) {
            if ($jhc_all >= $config['two_fission']) {
                M()->startTrans();
                try {
                    M('wallet')->execute('update mf_wallet set jhc_amount=jhc_amount*2');
                    M('config')->where(['id' => 1])->setDec('jhc_price', $config['jhc_price'] / 2);
                    M('config')->where(['id' => 1])->save(['fission_num' => 2]);
                    M()->commit();
                } catch (\Exception $e) {
                    M()->rollback();
                    exit('分裂失败');
                }
            }
        } elseif ($config['fission_num'] == 2) {
            if ($jhc_all >= $config['three_fission'] && $jhc_all <= $config['all_currency']) {
                M()->startTrans();
                try {
                    M('wallet')->execute('update mf_wallet set jhc_amount=jhc_amount*2');
                    M('config')->where(['id' => 1])->setDec('jhc_price', $config['jhc_price'] / 2);
                    M('config')->where(['id' => 1])->save(['fission_num' => 2]);
                    M()->commit();
                } catch (\Exception $e) {
                    M()->rollback();
                    exit('分裂失败');
                }
            }
        } else {
            if ($jhc_all >= $config['all_currency']) {
                M('config')->where(['id' => 1])->save(['coinage_status' => 1]);
            }
        }
    }
}