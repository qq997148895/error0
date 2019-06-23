<?php
/**
 * Author: Yun
 * Created: Atom.
 * Date: 2017/3/30
 * 交易买入卖出的API类
 */
namespace Api\Controller;

use Home\Model\TradeInfoModel;
use Common\Controller\HomeBaseController;

class TradeController extends HomeBaseController
{
    public $trade_id;
    public function __construct()
    {
        parent::__construct();
        //判断页面参数.
        if(!isset($_GET["id"]) || !is_numeric($_GET["id"])){
            echo json_encode(['code'=>0, 'msg'=>'非法操作'], 320);
            die();
        }else{
            $this->trade_id = $_GET["id"];
        }
    }
    //前台点击确认收款
    public function get()
    {
        $trade = new TradeInfoModel();
        echo json_encode($trade->getMoney(session('user_id'), $this->trade_id), 320);
    }
    //前台点击确认付款
    public function give()
    {
        $trade = new TradeInfoModel();
        echo json_encode($trade->giveMoney(session('user_id'), $this->trade_id), 320);
    }
    //前台点击取消交易
    public function close()
    {
        $trade = new TradeInfoModel();
        echo json_encode($trade->returnMoney(session('user_id'), $this->trade_id), 320);
    }

}
