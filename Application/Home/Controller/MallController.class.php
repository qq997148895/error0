<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use Common\Controller\HomeBaseController;

/**
 * 空模块，主要用于显示404页面，请不要删除
 */
class MallController extends HomeBaseController
{
    //商城首页
    public function shop()
    {
        $uid = session('user_id');
        $name = I('request.name');
        if ($name) {
            $seach['merchant_name'] = array('like', '%' . $name . '%');
            $malllist = M('merchant')->where(array('merchant_status' => 1))->where($seach)->select();//店铺信息
            $this->assign('name', $name);
        } else {
            $malllist = M('merchant')->where(array('merchant_status' => 1))->select();//店铺信息
        }
        $goods = [];
        if ($malllist) {
            $havemer = 1;
            foreach ($malllist as $k => &$v) {
                $goods = M('goods')->where(array('user_id' => $v['user_id']))->order('goods_sell desc')->limit(3)->select(); //商品信息
                $malllist[$k]['goods'] = $goods;
            }
        } else {
            $havemer = 0;
        }
        $this->assign('have', $havemer);
        $this->assign('malllist', $malllist);
        $this->display('Shop/shop');
    }

    //商品列表
    public function shop_list()
    {
        $uid = I('request.id');
        $goods = M('goods')->where(array('user_id' => $uid))->select(); //商品信息
        $mall = M('merchant')->where(array('user_id' => $uid))->find();//店铺信息
        $this->assign('goods', $goods);
        $this->assign('mall', $mall);
        $this->display('Shop/shop_list');
    }

    //商品详情
    public function shop_details()
    {
        $gid = I('request.id');
        $goods = M('goods')->where(array('id' => $gid))->find(); //商品信息
        $this->assign('goods', $goods);
        $this->display('Shop/shop_details');
    }

    //购物车
    public function shop_cart()
    {
        $uid = session('user_id');
        $map['goods_num'] = array('GT', 0);
        $carlist = M('car')->where(array('user_id' => $uid))->where($map)->select();
        $flag = 0;
        if ($carlist) {
            $flag = 1;
            foreach ($carlist as $k => $v) {
                $goods = M('goods')->where(array('id' => $v['goods_id']))->find();
                $carlist[$k]['goods_name'] = $goods['goods_name'];
                $carlist[$k]['goods_price'] = $goods['goods_price'];
                $carlist[$k]['goods_details'] = $goods['goods_details'];
                $carlist[$k]['imagepath1'] = $goods['imagepath1'];
                $carlist[$k]['goods_oldprice'] = $goods['goods_oldprice'];
                $carlist[$k]['goods_number'] = $goods['goods_number'];
            }
        }
//        dump($carlist);die;
        $this->assign('flag', $flag);
        $this->assign('carlist', $carlist);
        $this->display('Shop/shop_cart');
    }

    //加入购物车
    public function add_cart()
    {
        $data['user_id'] = session('user_id');
        $data['goods_id'] = I('post.id');
        $data['type'] = I('post.type');
        if (I('post.type') == '') {
            $data['type'] = 2;
        }
        $data['goods_num'] = 1;
        $res1 = M('car')->where(array('user_id' => $data['user_id'], 'goods_id' => $data['goods_id'], 'type' => $data['type']))->find();
        if ($res1) {
            //购物车存在该商品时
            $res2 = M('car')->where(array('user_id' => $data['user_id'], 'goods_id' => $data['goods_id']))->setInc('goods_num');
            if ($res2) {
                $this->ajaxReturn(['status' => '1', 'message' => '加入购物车成功!']);
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '加入购物车失败!']);
            }
        } else {
            //购物车没有该商品时
            $res2 = M('car')->data($data)->add();
            if ($res2) {
                $this->ajaxReturn(['status' => '1', 'message' => '加入购物车成功!']);
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '加入购物车失败!']);
            }
        }
    }

    //购物车管理
    public function cart_edit()
    {
        $uid = session('user_id');
        $type = I('post.type');
        $cid = I('post.id');
        if ($type == 1) {
            //删除
            $res = M('car')->where(array('user_id' => $uid, 'id' => $cid))->delete();
            if ($res) {
                $this->ajaxReturn(['status' => '1']);
            } else {
                $this->ajaxReturn(['status' => '0']);
            }
        }
        if ($type == 2) {
            //增加
            $res = M('car')->where(array('user_id' => $uid, 'id' => $cid))->setInc('goods_num');
            if ($res) {
                $this->ajaxReturn(['status' => '1']);
            } else {
                $this->ajaxReturn(['status' => '0']);
            }
        }
        if ($type == 3) {
            //减少
            $res = M('car')->where(array('user_id' => $uid, 'id' => $cid))->setDec('goods_num');
            if ($res) {
                $this->ajaxReturn(['status' => '1']);
            } else {
                $this->ajaxReturn(['status' => '0']);
            }
        }
    }

    //立即购买
    public function buy()
    {
        $uid = session('user_id');
        $li = 0;
        $gid = I('request.id');
        $type = I('request.type');
        $address = M('user_ship_address')->where(array('uid' => $uid, 'is_del' => '0'))->order('is_default DESC')->select();
        foreach ($address as &$val) {
            $val['longaddress'] = $val['address_pca'] . $val['address_detailed'];//总地址,省/市/县/详细 的拼接
        }
        $this->assign('lis', $li);
        $this->assign('gid', $gid);
        $this->assign('type', $type);
        $this->assign('address', $address);
        $this->display('Shop/address');
    }

    //购物车购买
    public function carbuy()
    {
        $uid = session('user_id');
        $list = htmlspecialchars_decode(I('list'));
        $li = json_decode($list, true);
        $gid = 0;
        $address = M('user_ship_address')->where(array('uid' => $uid, 'is_del' => '0'))->order('is_default DESC')->select();
        foreach ($address as &$val) {
            $val['longaddress'] = $val['address_pca'] . $val['address_detailed'];//总地址,省/市/县/详细 的拼接
        }
        $type = 0;
        $this->assign('type', $type);
        $this->assign('lis', $li);
        $this->assign('gid', $gid);
        $this->assign('address', $address);
        $this->display('Shop/address');
    }

    //下单
    public function pay()
    {
        $uid = session('user_id');
        $gid = I('post.gid');
        if ($gid == 0) {
            $lis = htmlspecialchars_decode(I('post.j'));
            $list = json_decode($lis, true);
            $aid = I('post.aid');
            foreach ($list as $ligood1) {
                $goods = M('goods')->where(array('id' => $ligood1['id']))->find();
                if ($goods['goods_number'] < 1 || $goods['goods_number'] - $ligood1['number'] < 0) {
                    $this->ajaxReturn(['status' => '0', 'message' => $goods['goods_name'] . '库存不足']);
                }
            }
            foreach ($list as $ligood) {
                //注册单
                $goods = M('goods')->where(array('id' => $ligood['id']))->find();
                $address = M('user_ship_address')->where(array('id' => $aid))->find();
                $data['user'] = $uid;
                $data['user_phone'] = $address['phone'];
                $data['user_name'] = $address['name'];
                $data['order'] = $this->orderNum();
                $data['project'] = $goods['goods_name'];
                $data['count'] = $ligood['number'];
                $data['sumprice'] = $goods['goods_price'] * $ligood['number'];
                $data['addtime'] = date('y-m-d H:i:s', time());
                $data['zt'] = 0;
                $data['address'] = $address['address_pca'] . $address['address_detailed'];
                $data['project_id'] = $goods['id'];
                $data['type'] = 1;
                $res = M('shop_orderform')->data($data)->add();
                if ($res) {
                    $new['goods_number'] = $goods['goods_number'] - $ligood['number'];//库存
                    $new['goods_sell'] = $goods['goods_sell'] + $ligood['number'];//销量
                    M('goods')->where(array('id' => $goods['id']))->save($new);//减少库存 增加销量
                    //购物车减少   判断买了多少 若全部买了  则  删除该商品  否则改变数量
                    $cargoods = M('car')->where(array('goods_id' => $ligood['id']))->find();
                    if ($cargoods['goods_num'] - $ligood['number'] <= 0) {
                        //全部买了  购物车删除该商品
                        M('car')->where(array('goods_id' => $ligood['id']))->delete();
                    } else {
                        //改变数量
                        $news['goods_num'] = $cargoods['goods_num'] - $ligood['number'];
                        M('car')->where(array('goods_id' => $ligood['id']))->save($news);
                    }
                } else {
                    $this->ajaxReturn(['status' => '0', 'message' => '购买失败，请重试']);
                }
            }
            $this->ajaxReturn(['status' => '1']);
        } else {
            $aid = I('post.aid');
            $goods = M('goods')->where(array('id' => $gid))->find();
            $address = M('user_ship_address')->where(array('id' => $aid))->find();
            $user = M('user')->where(array('user_id' => $uid))->find();
            if ($goods['goods_number'] >= 1) {  //库存足够
                $data['user'] = $uid;
                $data['user_phone'] = $address['phone'];
                $data['user_name'] = $address['name'];
                $data['order'] = $this->orderNum();
                $data['project'] = $goods['goods_name'];
                $data['count'] = 1;
                $data['sumprice'] = $goods['goods_price'];
                $data['addtime'] = date('y-m-d H:i:s', time());
                $data['zt'] = 0;
                $data['address'] = $address['address_pca'] . $address['address_detailed'];
                $data['project_id'] = $goods['id'];
                $data['type'] = 1;
                $res = M('shop_orderform')->data($data)->add();
                if ($res) {
                    M('goods')->where(array('id' => $goods['id']))->setDec('goods_number');//减少库存
                    M('goods')->where(array('id' => $goods['id']))->setInc('goods_sell');//增加销量
                    $this->ajaxReturn(['status' => '1']);
                } else {
                    $this->ajaxReturn(['status' => '0', 'message' => '购买失败，请重试']);
                }
            } else {
                $this->ajaxReturn(['status' => '0', 'message' => '购买失败，库存不足']);
            }
        }
    }

    //产生一个10位的 不重复订单号
    public function orderNum()
    {
        //组合一个10位的字符串
        $order_num = date('His') . rand(1000, 9999);
        $condition = M('shop_orderform')->where(array('order' => $order_num))->find();
        if ($condition) {
            return $this->orderNum();
        } else {
            return $order_num;
        }
    }

    //我的商城订单
    public function shop_order()
    {
        $uid = session('user_id');
        $list = M('shop_orderform')->where(array('user' => $uid))->order('id desc')->select();
        foreach ($list as $k => $v) {
            $img = M('goods')->where(array('id' => $v['project_id']))->find();
            $list[$k]['img'] = $img['imagepath1'];
            $list[$k]['goods_name'] = $img['goods_name'];
        }
        $this->assign('list', $list);
        $this->display('Shop/shop_order');
    }

    //订单详情页面
    public function orderdetail()
    {
        $uid = session('user_id');
        $oid = I('request.id');
        $order = M('shop_orderform')->where(array('id' => $oid))->find();//订单信息
        $goods = M('goods')->where(array('id' => $order['project_id']))->find(); //商品信息
        if ($goods['isadmin'] == 0) {//商户
            $touser = M('user')->where(array('user_id' => $goods['user_id']))->find(); //卖家信息
            $zfb = M('user_ali_number')->where(array('user_id' => $goods['user_id']))->find();//卖家支付宝信息
            $card = M('user_idcard')->where(array('user_id' => $goods['user_id']))->find();//卖家银行卡信息
        } else {
            $admin = M('admin_zf')->find(1);
            $zfb['ali_num'] = $admin['ali_num'];
            $card['id_card'] = $admin['id_card'];
            $touser['user_name'] = $admin['name'];
        }
        $this->assign('zfb', $zfb);
        $this->assign('card', $card);
        $this->assign('order', $order);
        $this->assign('goods', $goods);
        $this->assign('touser', $touser);
        $this->display('Shop/order-buy-details');
    }

    //确认打款
    public function make_money()
    {
        $uid = session('user_id');
        $oid = I('post.oid');
        $data['img'] = I('post.photo');
        $data['zt'] = 1;
        $res = M('shop_orderform')->where(array('id' => $oid))->data($data)->save();
        if ($res) {
            $this->ajaxReturn(['status' => 1, 'message' => '打款成功！']);
        } else {
            $this->ajaxReturn(['status' => 0, 'message' => '打款失败！']);
        }
    }
}
