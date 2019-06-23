<?php

namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;

/*
 * 新闻控制器
 */
class NewsController extends AdminlmcqBaseController
{
    /*
     * 新闻列表
     * Author:chenmengchen
     * Date:2017/03/29
     */
    public function newsList()
    {   
        //实例化对象
        $News = M('news');
        $type=I('request.type');
        if (empty($type)) {
            // 查询总记录数
            $count = $News->where(['id'=>['gt',0]])->count();
            $p = getpage($count, 25);
            //查询数据
            $list = $News->where(['id'=>['gt',0] , 'type'=>['in' , '1,4']])->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        }
        if ($type==1) {
            // 查询总记录数
            $count = $News->where(['id'=>['gt',0]])->where(array('type'=>'1'))->count();
            $p = getpage($count, 25);
            //查询数据
            $list = $News->where(['id'=>['gt',0]])->where(array('type'=>'1'))->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        }
        // if ($type==2) {
        //     // 查询总记录数
        //     $count = $News->where(['id'=>['gt',0]])->where(array('type'=>'2'))->count();
        //     $p = getpage($count, 25);
        //     //查询数据
        //     $list = $News->where(['id'=>['gt',0]])->where(array('type'=>'2'))->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        // }
        // if ($type==3) {
        //     // 查询总记录数
        //     $count = $News->where(['id'=>['gt',0]])->where(array('type'=>'3'))->count();
        //     $p = getpage($count, 25);
        //     //查询数据
        //     $list = $News->where(['id'=>['gt',0]])->where(array('type'=>'3'))->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        // }
        if ($type==4) {
            // 查询总记录数
            $count = $News->where(['id'=>['gt',0]])->where(array('type'=>'4'))->count();
            $p = getpage($count, 25);
            //查询数据
            $list = $News->where(['id'=>['gt',0]])->where(array('type'=>'4'))->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        }
        //分配数据
        $this->assign('list', $list);
        $this->assign('page', $p->show());
        $this->display('News/news_list');
    }

    /*
     * 添加新闻
     * Author:chenmengchen
     * Date:2017/03/29
     */
    public function newsAdd()
    {
        if(IS_POST){
            $uploads = new \Think\Upload();// 实例化上传类    
            $uploads->maxSize   =     3145728 ;// 设置附件上传大小    
            $uploads->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
            $uploads->savePath  =      '/Pic/'; // 设置附件上传目录    
            // 上传文件     
            $info   =   $uploads->uploadOne($_FILES['imagepath']); 
            if(!$info) {// 上传错误提示错误信息    
                $this->error($uploads->getError());
            }else{// 上传成功 获取上传文件信息    
                $_POST['new_img'] = '/Uploads'.$info['savepath'].$info['savename'];
            }
            
            $_POST['date'] = time();
            $re = M('news')->add($_POST);
            if($re){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->display('News/news_add');
        }
    }
    
    /*
     * 新闻修改
     * Author:chenmengchen
     * Date:2017/03/30
     */
    public function newsEdit()
    {
        if (IS_POST) {
            //获取提交的数据
            //$data = I('post.');
            // $uploads = new \Think\Upload();// 实例化上传类    
            // $uploads->maxSize   =     3145728 ;// 设置附件上传大小    
            // $uploads->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
            // $uploads->savePath  =      '/Pic/'; // 设置附件上传目录    
            // // 上传文件     
            // $info   =   $uploads->uploadOne($_FILES['imagepath']); 
            // if(!$info) {// 上传错误提示错误信息    
            //     $this->error($uploads->getError());
            // }else{// 上传成功 获取上传文件信息    
            //     $_POST['new_img'] = '/Uploads'.$info['savepath'].$info['savename'];      
            // }
            //构建数据
            $news['title'] = $_POST['title'];
            $news['type'] = $_POST['type'];
            $news['new_img'] = $_POST['erweima'];
            $news['content'] = $_POST['content'];
            $news['date'] = time();
            //更新数据
            $result = D('news')->where(array('id'=>$_POST['id']))->save($news);
            if ($result) {
                $this->success('修改成功！', '/Adminlmcq/news/newsList', 3);
            } else {
                $this->error('修改失败！');
            }
        }else{
            //获取要修改的新闻id
            $news_id = I('get.id');
            //实例化对象
            $News = D('news');
            //查询新闻内容
            $data = $News->where(array('id'=>$news_id))->find();
            //分配数据
            $this->assign('data', $data);
            //展示模板
            $this->display('News/news_edit');
        }
    }
    //上传缩略图
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
    /*
     * 新闻删除
     * Author:chenmengchen
     * Date:2017/03/30
     */
    public function newsDel()
    {
        //获取新闻id
        $news_id = I('get.id');
        //删除数据
        $result = M('news')->where(array('id' => $news_id))->delete();
        if($result){
            $this->success('删除成功！');
        }
    }
}