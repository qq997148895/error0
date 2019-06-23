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
 * 用户钱包日志
 */
class WalletLogModel extends Model
{
    /**
     * 返回用户当前的钱包日志，默认10条分页，可传入参数.
     * @param  [int]  $user_id [用户ID]
     * @return {[array]          [返回一维数组]
     */
    public function getWalletInfo($user_id, $limit=10)
    {
        return $this->where(['user_id' => $user_id])->select();
    }

    /**
     * 添加用户的收入支出日志
     * @param  [array]  $data  [日志数据]
     * @return {[int]          [影响行数]||[null]
     */
    public function addWalletLog($data)
    {
        return $this->add($data);
    }


}
