<?php

namespace Adminlmcq\Controller;

use Common\Controller\AdminlmcqBaseController;

/*
 * 轮播图管理
 * Author:chenmengchen
 * Date:2017/03/31
 */

class BannerController extends AdminlmcqBaseController
{
    public function banner_list(){
        $list = M('banner')->select();
        $this->assign('list', $list);
        $this->display('Banner/banner_list');
    }
    public function banner_add(){
        if(IS_POST){
        $data = I('post');

        }
        $this->display('Banner/banner_add');
    }
    public function banner_edit(){
        $bid = I('request.id');
        $banner = M('banner')->wehre('id',$bid)->find();
        $this->assign('banner', $banner);
        $this->display('Banner/banner_edit');
    }
}