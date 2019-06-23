<?php
/**
 * Author: Yun
 * Created: Atom.
 * Date: 2017/3/30
 * 商城的API类
 */
namespace Api\Controller;
use Common\Controller\HomeBaseController;
use Home\Model\Shop_projectModel;

class ShopController extends HomeBaseController
{
    public $name;
    public $trade_id;
    public function __construct()
    {
        parent::__construct();
    }
    //商品列表
    public function shop_list()
    {
        //判断页面参数.
        if(!isset($_GET["name"])){
            echo json_encode(['code'=>0, 'msg'=>'非法操作'], 320);
            die();
        }else{
            $this->name = $_GET["name"];
        }
        $shop_list = new Shop_projectModel();
        echo json_encode($shop_list->shop_list($this->name), 320);
    }
    //订单列表
    public function shop_order_list()
    {
        $shop_order_list = new Shop_projectModel();
        echo json_encode($shop_order_list->shop_order_list(), 320);
    }
    //前台点击确认收货
    public function get()
    {        header('Content-type:text/html; charset=utf-8');
        //判断页面参数.
        if(!isset($_GET["id"]) || !is_numeric($_GET["id"])){
            echo json_encode(['code'=>0, 'msg'=>'非法操作'], 320);
            die();
        }else{
            $this->trade_id = $_GET["id"];
        }

        $trade = new Shop_projectModel();
        echo json_encode($trade->getShop(session('user_id'), $this->trade_id), 320);
    }


}
