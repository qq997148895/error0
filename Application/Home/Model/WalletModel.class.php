<?php
/**
 * Created by PhpStorm.
 * User: Adminlmcqistrator
 * Date: 2017/3/21 0021
 * Time: 17:07
 */

namespace Home\Model;

use Think\Model;

/**
 * 用户钱包类
 */
class WalletModel extends Model
{
    /**
     * 返回用户当前的钱包信息
     * @param  [int]  $user_id [用户ID]
     * @return {[array]          [返回一维数组]
     */
    public function getWalletInfo($user_id)
    {
        return $this -> where(['user_id' => $user_id]) -> find();
    }

    /**
     * 用户的收入支出后更新数据库
     * @param  [int]  $user_id [用户ID]
     * @return {[bool]          [返回成功或失败]
     */
     public function saveWallet($user_id, $data)
    {
        return $this->where(['user_id' => $user_id])->save($data);
    }
    
    /*
     * 添加钱包数据
     * @param  [int]  $user_id [用户ID]
     * @return {[bool]          [返回成功或失败]
     */
    public function addWallet($record)
    {
        return $this->add($record);
    }
    
    /*
     * 添加系统钱包
     */
    public function systemWallet()
    {
        return $result1 = M('total_fish')->where(1)->setInc('total_fish_amount',30);
    }
    
    /*
     * 注册新用户后更新
     * @param  [int]  $user_id [用户ID]
     * @return {[bool]          [返回成功或失败]
     */
    public function updateWallet($user_id)
    {
        $result1 = $this->where(['user_id' => $user_id])->setDec('fish_amount',330);
        $result2 = $this->where(['user_id' => $user_id])->setDec('fish_avalible',330);
        return $result1 && $result2;
    }

}
