<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/5
 * Time: 18:40
 */
namespace Models;
use Think\Model;
class ConfigModel extends Model
{
    //修改金币
    public function editJhc($value)
    {
       return $this->where(['id'=>1])->save(['jhc_price'=>$value]);
    }
    //修改配置
    public function editConfig($data)
    {
        return $this->where(['id'=>1])->save($data);
    }
}