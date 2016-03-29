<?php

/*
 * ********************************************************************************* Copyright (c) 2005-2015 All rights reserved. File: Author:jxj Editor: Email:jinxiaojia@aliyun.com Tel: Version: Description: *********************************************************************************
 */
?>
<?php

use Common\Controller\BaseController;
use Think\Page;
use Think\AjaxPage;

class GoodsAction extends BaseController {

    public function __construct() {
        parent::__construct();
        $this->guid = $_SESSION['user'][0]['u_guid'];
    }

    public function order() {
        $this->log->info($_GET);
        // 1.获取货物id
        $guid = $_SESSION ['user'] [0] ['u_guid'];
        $goods_id = $_GET ['id'];
        canpointCommon::logDegub("goods_index_order:goods_id=" . $goods_id, "goods");
        
        $return_urls = 'http://pay.canpoint.net/shop/goods/order/id/' . $goods_id;
        $return_urls = str_replace('+', '_', str_replace('/', '-', base64_encode($return_urls)));
        $this->log->info($return_urls);
        if (!$guid) {
            header("location:http://e.canpoint.net/login/index/index/url/$return_urls");
        } else {
            $orderlog = M('bill_order_info');
            $sstlog = M('bill_order_plist');
            $goods = D("Goods");


            // 2.是否已经生成订单
            $num = $goods->has_Order_Bygid($guid, $goods_id);
            $this->log->info($num);
            if ($num) {
                $Card = $num;
            } else {
                // 2.生成订单号
                $Card = $goods->get_neworder_id();
                $this->log->info($Card);
                $goods_info = $goods->get_goods_byid($goods_id);

                // 3.订单表order_info的数组且加入订单
                $newid = $goods->add_ArrayOrderInfo($Card, $guid, $goods_info);
                
                ##6。同步到后台发货
                $u_post = array();
                $Shop = D("Shop");
                $order_userinfo = $Shop->getUserByGuid($guid, "u_guid,u_nickname,u_hphone,u_name,u_code,u_address,u_fpoint,u_rpoint,u_qdou");
                $orderInfo = $Shop->getByOrderid($Card);

                $aa = $goods->add_ecshop($u_post, $goods_info, $order_userinfo, $orderInfo);
            }
            $this->log->info($newid.",".$num);
            if ($newid || $num) {
                header("location:/shop/goods/pay?card=$Card");
            } else {
                echo "加入订单失败，库存不足";
                exit();
            }
        }
    }

    public function pay() {
        header("Cache-Control:no-cache,must-revalidate,no-store"); // 这个no-store加了之后，Firefox下有效
        header("Pragma:no-cache");
        header("Expires:-1");
        
        $guid = $_SESSION ['user'] [0] ['u_guid'];
        $Card = $_GET['card'];
        $this->log->info($Card);
        $orderlog = M('bill_order_info');
        canpointCommon::logDegub("goods_goods_pay:card=" . $Card, 'goods');


        // 1.该条订单数据
        $Shop = D("Shop");
        //2. 订单详细
        $orderInfo = $Shop->getByOrderid($Card);
        if (!$orderInfo['order_num']) {
            exit('操作不正确！');
        }
        $goods = D("Goods");
        $goods_info = $goods->get_goods_byid($orderInfo['order_keid']);

        // 3.用户全品币和全品券全品豆
        $info = $Shop->getUserByGuid($guid, "u_guid,u_hphone,u_name,u_code,u_address,u_fpoint,u_rpoint,u_qdou");

        // 4.用户、订单 全品豆
        $total_point = (int) $info ['u_qdou'];

        $total_price = (int) $orderInfo ['order_dou'];
        $this->log->info($total_point);
        $this->log->info($total_price);
        canpointCommon::logDegub("shop_goods_pay:user_point=" . $total_point, 'goods');
        canpointCommon::logDegub("shop_goods_pay:order_totalprice=" . $total_price, 'goods');

        
        canpointCommon::logDegub("shop_goods_pay:order_needfpoint=" . $total_price . ",order_needrpoint=" . $need_fpoint, 'goods');

        $this->assign('goods_info', $goods_info); // #商品详细
        $this->assign('orderInfo', $orderInfo); //订单
        $this->assign('total_price', (int) $total_price); // #订单总金额

        $this->assign('Card', $Card);
        if ($total_point < $total_price) {
            $this->display("shop.goods.payno");
        } else {
            $this->display("shop.goods.pay");
        }
    }

    /**
     * 确认订单
     */
    function order_confirm() {
        header("Cache-Control:no-cache,must-revalidate,no-store"); // 这个no-store加了之后，Firefox下有效
        header("Pragma:no-cache");
        header("Expires:-1");
        #接受参数
        $guid = $_SESSION ['user'] [0] ['u_guid'];
        $u_formCard = I('card');
        $Card = $u_formCard;
        $u_realname = I('recipients');
        $u_phone = I('tel');
        $u_code = I('code');
        $u_address = I('address');
        $u_post = array();
        unset($_SESSION['shop']['u_post']);
        #判断参数
        if ($u_formCard) {
            $u_post['u_formCard'] = $u_formCard;

            if (isset($u_realname)) {
                $u_post['u_realname'] = $u_realname;
            } else {
                echo "用户名不能为空";
                exit();
            }
            if (isset($u_phone)) {
                if (preg_match("/1[34578]{1}\d{9}$/", $u_phone)) {
                    $u_post['u_phone'] = $u_phone;
                } else {
                    echo "手机号不合法";
                    exit();
                }
            }

            if (isset($u_address)) {
                $u_post['u_address'] = $u_address;
            } else {
                echo "邮寄地址不能为空";
                exit();
            }
            $u_post['u_code'] = $u_code;

            #s所有数据合法
            if (count($u_post) > 3) {
                $_SESSION['shop']['u_post'] = $u_post;

                // 1.该条订单数据
                $Shop = D("Shop");
                //2. 订单详细
                $orderInfo = $Shop->getByOrderid($Card);

                $goods = D("Goods");
                $goods_info = $goods->get_goods_byid($orderInfo['order_keid']);

                $total_price = (int) $orderInfo ['order_dou'];

                $this->assign('goods_info', $goods_info); // #商品详细
                $this->assign('orderInfo', $orderInfo); //订单
                $this->assign('total_price', (int) $total_price); // #订单总金额
                $this->assign('u_post', $u_post); // #订单总金额
                $this->display("shop.goods.confirm");
            }
        } else {
            echo "发生未知错误";
            exit();
        }
    }

    function payok() {
        // 		header ( "Cache-Control:no-cache,must-revalidate,no-store" ); // 这个no-store加了之后，Firefox下有效
        // 		header ( "Pragma:no-cache" );
        // 		header ( "Expires:-1" );
        $guid = $_SESSION ['user'] [0] ['u_guid'];
        //$Card = $_SESSION ['shop'] ['card'];
        $u_formCard = I('card');
        $Card = $u_formCard;
        #判断参数
        if ($u_formCard) {

            $u_post = $_SESSION['shop']['u_post'];
            if ($u_post) {

                // 1.该条订单数据
                $Shop = D("Shop");
                $orderInfo = $Shop->getByOrderid($u_post['u_formCard']);
                $order_userinfo = $Shop->getUserByGuid($guid, "u_guid,u_nickname,u_hphone,u_name,u_code,u_address,u_fpoint,u_rpoint,u_qdou");

                $goods = D("Goods");
                $goods_info = $goods->get_goods_byid($orderInfo['order_keid']);
                $total_price = (int) $orderInfo ['order_dou'];

                #判断库存
                //				if(!$goods_info['goods_number'])
                //				{
                //					echo "库存不够";
                //					exit();
                //				}
                #判断用户全品豆
                $total_point = (int) $order_userinfo['u_qdou'];
                $total_price = (int) $orderInfo ['order_dou'];
                if ($total_point < $total_price) {
                    echo "全品豆不够了";
                    exit();
                }
                #扣除全品豆
                if ($orderInfo['order_status'] != 2) {#刷新页面不重复扣分
                    canpointCommon::logDegub("shop_goods_payok:order_totalprice=" . $u_post['u_formCard'], 'goods');
                    canpointCommon::logDegub("shop_goods_payok:order_totalprice=" . $total_price, 'goods');
                    $Sajax = D("Sajax");
                    ##1.需要更新成的全品币和券
                    $data['u_qdou'] = $order_userinfo['u_qdou'] - $total_price;
                    ##2。全品豆减少记录
                    $Sajax->addCoinlog($guid, 'goumai', '兑换', $total_price, 2, 1);
                    ##3。更新用户财富
                    $re = $Sajax->updateUser($guid, $data);
                    ##4。发信
                    $messge = array(
                        'sender_uid' => 1,
                        'recipient_uid' => $guid,
                        'action_type' => 1,
                        'message_content' => "您的全品豆余额减少了" . $total_price . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                    );
                    curl_post($messge);

                    canpointCommon::logDegub("shop_goods_payok:order_totalprice=" . $re, 'goods');

                    ##5。更新订单状态
                    $orderlog = M('bill_order_info');
                    $order['order_status'] = 2;
                    $order['order_time_pay'] = date('Y-m-d H:i:s');
                    $res = $orderlog->where("order_num='" . $u_post['u_formCard'] . "'")->save($order);

                    canpointCommon::logDegub("shop_goods_payok:order_totalprice=" . $res, 'goods');


                    ##6。同步到后台发货
                    //$goods->add_ecshop($u_post,$goods_info,$order_userinfo,$orderInfo);
                    ##7.更新后台物流信息和时间
                    $goods->updEcOrder($u_post);
                }
                ##7。清理SESSION
                // 				unset($_SESSION['shop']);

                $this->assign('goods_info', $goods_info); // #商品详细
                $this->assign('orderInfo', $orderInfo); //订单
                $this->assign('total_price', (int) $total_price); // #订单总金额
                $this->assign('u_post', $u_post); // #订单总金额
                $this->display("shop.goods.payok");
            }
        }
    }

    /**
     * 确认商品 地址
     */
    public function confirm() {
        $data['id'] = (int) $_GET['id'];

        $m = D('Goods');
        $goods_info = $m->get_goods_byid($data['id']);
        if (!$goods_info) {
            $this->redirect('/goods/index/goodslist');
        }
        $user = $m->getUserByGuid($this->guid);
        if ($user['u_qdou'] < $goods_info['shop_price']) {
            $this->assign('goods_info', $goods_info);
            $this->display("shop.goods.payno");
            exit;
        }

        $data['addr'] = $m->getAddressByGuid($this->guid);
        if (!$data['addr']) {
            $this->redirect('/shop/goods/addr/id/' . $data['id']);
        }
        $this->assign('data', $data);
        $this->assign('goods_info', $goods_info); // #商品详细

        $this->display("shop.goods.confirm");
    }

    /**
     * 地址
     */
    public function addr() {
        $data['id'] = $id = $_GET['id'];
        $m = D('Goods');
        $goods_info = $m->get_goods_byid($id);
        if (!$goods_info) {
            $this->redirect('/goods/index/goodslist');
        }
        $data['addr'] = $m->getAddressByGuid($this->guid);

        $this->assign('data', $data);
        $this->assign('goods_info', $goods_info); // #商品详细
        $this->display("shop.goods.pay");
    }

    /**
     * 添加地址
     */
    public function addAddress() {
        $id = (int) $_POST['id'];
        if (!canpointCommon::checkInput($_POST, 'post')) {
            $re['msg'] = '请输入合法数据';
            exit(json_encode($re));
        }
        if (!$this->guid) {
            $re['msg'] = '请登录';
            exit(json_encode($re));
        }
        $data['guid'] = $this->guid;
        $data['name'] = trim($_POST['uname']);
        $data['tel'] = (int) $_POST['utel'];
        $data['address'] = trim($_POST['uaddress']);
        if (!$data['name']) {
            $re['msg'] = '请输入收货人姓名！';
            exit(json_encode($re));
        }
        if (!$data['tel'] || strlen($data['tel']) != 11) {
            $re['msg'] = '请输入手机号！';
            exit(json_encode($re));
        }
        if (!$data['address']) {
            $re['msg'] = '请输入收货地址！';
            exit(json_encode($re));
        }
        $m = D('Goods');
        $addr = $m->getAddressByGuid($this->guid);
        if ($addr) {
            $result = $m->updateAddressByGuid($data);
        } else {
            $result = $m->addAddress($data);
        }


        if ($result) {
            $re['msg'] = '地址添加成功!';
            $re['status'] = 1;
            $re['url'] = '/shop/goods/confirm/id/' . $id;
            exit(json_encode($re));
        } else {
            $re['msg'] = 'error!';
            exit(json_encode($re));
        }
    }

    /**
     * 兑换
     */
    public function buy() {
        $id = (int) $_GET['id'];
        if (!$this->guid) {
            $ds['msg'] = "请登陆再兑换！";
            $this->assign('ds', $ds);
            $this->display("shop.goods.error");
            exit;
        }
        $m = D('Goods');
        $goods = $m->get_goods_byid($id);
        $this->assign('goods_info', $goods);
        if (!$goods) {
            $ds['msg'] = "对不起！您所选的商品不存在！";
            $this->assign('ds', $ds);
            $this->display("shop.goods.error");
            exit;
        }
        $user = $m->getUserByGuid($this->guid);
        if ($user['u_qdou'] < $goods['shop_price']) {
            $ds['msg'] = "对不起！你的全品豆不足！";
            $this->assign('ds', $ds);
            $this->display("shop.goods.error");
            exit;
        }
        if ($goods['goods_number'] <= 0) {
            $ds['msg'] = "对不起！您所选的商品已经售罄！";
            $this->assign('ds', $ds);
            $this->display("shop.goods.error");
            exit;
        }

        //订单号
        $order_id = $m->get_neworder_id();
        //生成订单
        $r = $m->add_ArrayOrderInfo($order_id, $this->guid, $goods);
        //用户信息
        $user = $m->getUserByGuid($this->guid);
        //收获地址
        $address = $m->getAddressByGuid($this->guid);


        $u_post['u_realname'] = $address['name'];
        $u_post['u_address'] = $address['address'];
        $u_post['u_code'] = $user['u_code'];
        $u_post['u_phone'] = $address['tel'];

        $Shop = D("Shop");
        //订单详情
        $orderInfo = $Shop->getByOrderid($order_id);
        //同步到ECShop
        $aa = $m->add_ecshop($u_post, $goods, $user, $orderInfo);

        $m->updateQdouByGuid($this->guid, $goods['shop_price']);
        //日志
        $coinLog['uid'] = $coinLog['uname'] = $this->guid;
        $coinLog['action'] = 'exchange';
        $coinLog['note'] = '兑换';
        $coinLog['val'] = -$goods['shop_price'];
        $coinLog['flag'] = 2;
        $coinLog['date'] = date('Y-m-d H:i:s');
        $m->addCoinLog($coinLog);

        $this->redirect('/shop/goods/success/card/' . $order_id);
    }

    /**
     * 兑换成功
     */
    public function success() {
        $id = $_GET['card'];
        $m = D('Goods');
        $orderInfo = D('Shop')->getByOrderid($id);
        if ($this->guid != $orderInfo['order_guid']) {
            $this->redirect('http://pay.canpoint.net/order/index/index');
        }
        $goods_info = $m->get_goods_byid($orderInfo['order_keid']);
        $this->assign('orderInfo', $orderInfo);
        $this->assign('goods_info', $goods_info);
        $this->display('shop.goods.success');
    }

}

?>