<?php
namespace Adminlmcq\Model;

use Think\Model;

/*
 * 新闻管理模型
 * Author:chenmengchen
 * Date:2017/03/30
 */
class NewsModel extends Model
{
    /*
     * 查询数据
     */
    public function getNews($p,$news_id)
    {
        if(!empty($news_id)){
            return $this->order('id DESC')->where(array('id' =>$news_id))->find();
        }else{
            return $this->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        }
        
    }
    /*
     * 查询总数
     */
    public function getCount()
    {
        return $this->count();
    }
    
    /*
     * 更新新闻
     */
    public function saveNews($news_id,$news)
    {
        return $this->where(array('id' => $news_id))->save($news);
    }
}

