<?php

/* * *********************************************************************************
 * Copyright (c) 2005-2011
 * All rights reserved.
 *
 * File:
 * Author:gaohaifeng
 * Editor:
 * Email:haifeng@hnusoft.com
 * Tel:
 * Version:
 * Description:
 * ********************************************************************************* */
?>
<?php

use Common\Controller\BaseController;

class RechargeAction extends BaseController {

    public function card() {
        //用户信息
        $uid = $_SESSION['user'][0]['u_hphone'] ? $_SESSION['user'][0]['u_hphone'] : $_SESSION['user'][0]['u_email'];

        $this->assign('uid', $uid);
        $this->display("payment.recharge.card");
    }

    public function csuccess() {
        $guid = $_SESSION['user'][0]['u_guid'];
        $cardnum = $_SESSION['charge']['card'];
        $ka_type = $_SESSION['charge']['ka_type'];

        $stu = M("canpoint_user." . $this->table_name);
        if ($ka_type == 1) {
            $field = "u_rpoint";
        } else {
            $field = "u_fpoint";
        }
        $nowprice = $stu->where("u_guid=" . $guid)->getField($field);
        $this->log->info($field . "_" . $nowprice);
        canpointCommon::logDegub("recharge_getRpointOrFpoint=" . $stu->getLastSql(), 'Payment');
        
        $this->log->info($cardnum);
       
        if (strlen($cardnum) > 12 && strlen($cardnum)!=16) {
            /* ---------------------------旧卡---------------------------------- */
            $pcard = substr($cardnum, 0, 15);   ##卡号
            $k_part = substr($pcard, 0, 10);
            $Scard = M($k_part);
            $price = $Scard->where("k_no=$pcard")->getField("ka_price");

            $card = $pcard;
        }else if (strlen($cardnum)==16) {
            /* ---------------------------实物卡---------------------------------- */
             $card = $cardnum;
            $Scard = M("canpoint_card.cardv3");
            $price = $Scard->where("card_no=$cardnum")->getField("card_price");

        }  else {

            if (substr($cardnum, 0, 3) == 'bta') {
                $price = M("canpoint_card.cardv2")->where("card_no='" . $cardnum . "'")->getField("card_price");
                $price = intval($price / 100);
            } else {
                $Card_index = substr($cardnum, -2);
                $Scard = M("card_" . $Card_index);
                $price = $Scard->where("ka_num=$cardnum")->getField("ka_price");
            }


            $card = $cardnum;
        }
        
        $this->assign('nowprice', $nowprice);
        $this->assign('ka_type', $ka_type);
        $this->assign('price', $price);
        $this->assign('card', $card);
        $this->display("payment.recharge.csuccess");
    }

    public function online() {
        //用户信息
        $uid = $_SESSION['user'][0]['u_hphone'] ? $_SESSION['user'][0]['u_hphone'] : $_SESSION['user'][0]['u_email'];
        $guid = $_SESSION['user'][0]['u_guid'];
        //获取用户可用红包
        $active_model = new ActiveModel();
        $hb_list = $active_model->getHongBao($guid);

        $this->assign('hb_list', $hb_list);
        $this->assign("guid", $guid);
        $this->assign("uid", $uid);
        $this->display("payment.recharge.online");
    }

    public function osuccess() {
        $this->display("payment.recharge.osuccess");
    }

    /*
     * 支付宝
     */

    public function recharge() {
        #1.当前登录人用户信息
        $guid = $_SESSION['user'][0][u_guid];

        ##2.充值信息
        $post = $_POST;
        canpointCommon::logDegub("recharge_post=" . serialize($post), 'Payment');
        $this->log->info($post);
        if ($post['account']) {
            //$uid = getUserDidByAccount($post['account']);
            $Pay = D("Payment");
            if ($Pay->isTphone($post['account']) || $Pay->isEmail($post['account'])) {
                $uid = getUserDidByAccount($post['account']);
            } else {
                $uid = $Pay->getGuidByUid($post['account']);
            }

            $post['account'] = $uid;

            #被充值人姓名
            $table_index = substr($uid, -2);
            $table_name = "user_" . $table_index;
            $u_name = M("canpoint_user." . $table_name)->where("u_guid=$uid")->getField("u_name");
        } else {
            $post['account'] = $guid;
        }

        //使用红包
        if (!empty($post['hb_id'])) {
            $hb_id = $post['hb_id'];
            $now_time = time();
            $active_model = new ActiveModel();
            //
            $hb_info = $active_model->getHongBaoById($hb_id);
            if (empty($hb_info) || $hb_info['uid'] != $guid)
                exit('红包不存在');
            if ($hb_info['status'] != 1)
                exit('红包已使用');
            if ($now_time >= $hb_info['end_time'])
                exit('红包已过期');
            if ($post['account'] < $hb_info['money'] * 10)
                exit('金额选择错误');
        }

        canpointCommon::logDegub("alipay:post_account=" . $post['account'], 'Payment');
        $this->log->info($post['account']);
        /* --------------------------第三方的订单数据表----------------------------------- */
        ###3.订单编号生成
        $pay_db = M('t_order_sn');
        $lastid = $pay_db->where("id>0")->limit(1)->order('id desc')->find();
        $Card = "PO" . str_pad($lastid[id] + 1, 10, "0", STR_PAD_LEFT);
        $addtime = date("Y-m-d H:i:s");
        canpointCommon::logDegub("payment_cardnum=" . $Card, 'Payment');
        $this->log->info($Card);
        ####4.订单信息
        $pay_order = array(
            'orderId' => $Card,
            'memberid' => $post['account'],
            'loginname' => $guid,
            'ordername' => '全品学堂在线充值',
            'orderbaby' => '全品学堂在线充值',
            'payorno' => 1,
            'buyer_email' => '',
            'tele' => '',
            'money' => $post['coin'],
            'addtime' => $addtime,
            'paytime' => '',
            'dealId' => '',
            'paytype' => $post['paytype']
        );
        //红包
        if (!empty($hb_info)) {
            //红包ID
            $pay_order['bonus_id'] = $hb_info['id'];
            //红包价格
            $pay_order['bonus'] = $hb_info['money'];
            //订单价格
            $pay_order['money'] = $pay_order['money'] - $hb_info['money'];
            $pay_order['money'] = sprintf("%.2f", $pay_order['money']);
        }
        $this->log->info($pay_order);

        if ($post) {
            $id = $pay_db->add($pay_order);  //订单生成表
            canpointCommon::logDegub("payment_cardnum=" . $Card, 'Payment');
            //验证订单是否成功
            if (empty($id))
                exit('订单生成失败');
            if (!empty($post['hb_id'])) {
                $hb_status = 3;
                $active_model->updateStatus($post['hb_id'], $hb_status, $id);
            }

            #####5.添加激活记录信息
            $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
            $data = array();
            $data['k_id'] = $Card;
            $data['k_type'] = 4;
            $data['k_mianzi'] = $post['coin'];
            $data['k_jifen'] = $post['coin'];
            $data['k_userid'] = $post['account'];
            $data['k_uname'] = $u_name;
            $data['k_ip'] = $_SERVER['REMOTE_ADDR'];
            $data['ka_fdiqu'] = $this->IP($_SERVER['REMOTE_ADDR']);
            $data['ka_rdate'] = date("Y-m-d H:i:s");
            $NSQ->add($data); //充值记录
            $this->log->info($data);
            $_SESSION['pay_jifen'] = $data['k_jifen'];
        }
        canpointCommon::logDegub("payment_paytype=" . $post['paytype'], 'Payment');  ##支付类型
        canpointCommon::logDegub("payment_money=" . $post['coin'], 'Payment');              ##支付金额
        $this->log->info($post['paytype']);
        $this->log->info($post['coin']);
        if ($post['paytype'] == 1) {//表示支付宝充值
            //支付类型
            $payment_type = "1";
            //服务器异步通知页面路径
            // $notify_url = "http://pay.canpoint.net/payment/recharge/post_payarr";
            //需http://格式的完整路径，不能加?id=123这类自定义参数
            //页面跳转同步通知页面路径
            // $return_url = "http://pay.canpoint.net/payment/recharge/get_payarr";
            //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
            //卖家支付宝帐户
            $seller_email = 'canpointedu@163.com';  #2015-12-09
//            $seller_email = 'canpointbook@126.com';
            //商户订单号//必填
            $out_trade_no = $Card;
            //订单名称//必填
            $subject = '全品学堂在线充值';

            //付款金额
            $total_fee = $pay_order['money'];

            $body = '';
            //商品展示地址
            $show_url = '';
            //需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html
            //防钓鱼时间戳
            $anti_phishing_key = "";
            //若要使用请调用类文件submit中的query_timestamp函数
            //客户端的IP地址
            $exter_invoke_ip = "";
            //非局域网的外网IP地址，如：221.0.0.1
            if (isMobile()) {
                header("Content-type:text/html;charset=utf-8");
                canpointCommon::logDegub("is_mobile=1", '.web');
                $this->log->info("is_mobile");
                $notify_url = "http://pay.canpoint.net/payment/recharge/web_post_payarr";
                $return_url = "http://pay.canpoint.net/payment/recharge/web_get_payarr";
                require_once './Application/CPLib/webpay/alipayapi.php';
            } else {
                $notify_url = "http://pay.canpoint.net/payment/recharge/post_payarr";
                $return_url = "http://pay.canpoint.net/payment/recharge/get_payarr";
                require_once './Application/CPLib/pay/alipayapi.php';
            }
        } elseif ($post['paytype'] == 2) {
            $_SESSION['uid'] = $post['account'];
            canpointCommon::logDegub("unionpay_saveuid=" . $_SESSION['uid'], 'recharge');
            $coin = $pay_order['money'];
            require_once 'Application/CPLib/unionpay/example/front.php';
        } elseif ($post['paytype'] == 3) {

            // require_once 'Application/CPLib/wxpay/native.php';
            $_SESSION['uid'] = $post['account'];
            canpointCommon::logDegub("wxpay_saveuid=" . $_SESSION['uid'], 'recharge');
            $this->log->info($_SESSION['uid']);

            require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
            require_once "Application/CPLib/wxpay/WxPay.NativePay.php";
            require_once 'Application/CPLib/wxpay/log.php';




//模式二
            /**
             * 流程：
             * 1、调用统一下单，取得code_url，生成二维码
             * 2、用户扫描二维码，进行支付
             * 3、支付完成之后，微信服务器会通知支付成功
             * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
             */
            $notify = new NativePay();
            $input = new WxPayUnifiedOrder();
            $input->SetBody("全品学堂在线充值");
            // $input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));
            $input->SetOut_trade_no($Card);
            $input->SetTotal_fee($pay_order['money'] * 100);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetGoods_tag("全品学堂在线充值");
            $input->SetNotify_url("http://pay.canpoint.net/payment/wei/notify");
           // $input->SetNotify_url("http://weixin.canpoint.net");
            $input->SetTrade_type("NATIVE");
            $input->SetProduct_id("123456789");
            $result = $notify->GetPayUrl($input);
            $url2 = $result["code_url"];


            /* 订单状态 */
//                 require_once "Application/CPLib/wxpay/lib/WxPay.Data.php";
//                if (isset($Card) && $Card != "") {
//                    $out_trade_no = $Card;
//                    $input = new WxPayOrderQuery();
//                    $input->SetOut_trade_no($out_trade_no);
//                    print_r(WxPayApi::orderQuery($input));
//                    
//                }
            $this->assign('price', $pay_order['money']);
            $this->assign('ordernum', $Card);
            $this->assign('url2', $url2);
            $this->display('payment.recharge.wxrecharge');
        }
    }

    public function post_payarr() {
        $pay_db = D('t_order_sn');

        $info = I();
        canpointCommon::logDegub("recharge_post_payarr_info=" . serialize($info), 'Payment');
        canpointCommon::logDegub(serialize($info), "pay", 'Payment');
        $this->log->info($info);

        $pay_order = array(
            'payorno' => 2,
            'buyer_email' => $info['buyer_email'],
            'tele' => $info['buyer_id'],
            'paytime' => $info['notify_time'],
            'dealId' => $info['trade_no']
        );
        $this->log->info($pay_order);

        //订单的信息
        $is_pay = $pay_db->where("orderId='" . $info['out_trade_no'] . "'")->find();

        if (isMobile()) {
            canpointCommon::logDegub("alipay_return=1", 'Payment');
            require_once './Application/CPLib/webpay/notify_url.php';
        } else {
            canpointCommon::logDegub("alipay_return=2", 'Payment');
            require_once './Application/CPLib/pay/notify_url.php';
        }

        canpointCommon::logDegub("recharge_alipay=" . serialize($is_pay), 'Payment');
        $this->log->info($is_pay);
        $this->log->info($notify_yz);
        if ($notify_yz) {
            $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
            $data1 = array();
            $data1['k_type'] = 3;
            $re = $NSQ->where("k_id='$info[out_trade_no]'")->save($data1);

            if ($re) {
                $fen = $NSQ->where("k_id='" . $info[out_trade_no] . "'")->select();
                canpointCommon::logDegub("recharge_select_fen=" . $NSQ->getLastSql(), 'Payment');

                $data['uid'] = $fen[0]['k_userid'];
                $data['uname'] = $fen[0]['k_userid'];
                $data['action'] = "chongzhi1";
                $data['note'] = "充值";
                $data['val'] = $fen[0]['k_jifen'];
                $data['flag'] = 3;
                $data['date'] = date("Y-m-d H:i:s", time());
                $this->log->info($data);
                $log = M("u_coinlog");
                $log->add($data);
                $this->cash($fen[0]['k_userid'], $fen[0]['k_jifen']);
                canpointCommon::logDegub("recharge_coinlog_add=" . $log->getLastSql(), 'Payment');
                
                $this->log->info("user_action_purch,recharge,pc_ Alipay,".$fen[0]['k_userid'].",".date("Y-m-d H:i:s").",".$fen[0]['k_jifen'].",".$info['out_trade_no']);
            }

            //支付宝充值成功—发通知
            $messge = array(
                'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
            );
            curl_post($messge);
        }
    }

    /*
     * 手机网站支付宝支付
     */

    public function web_post_payarr() {

        $pay_db = D('t_order_sn');

        $info = I();
        canpointCommon::logDegub("notify_recharge_post_payarr_info=" . serialize($info), '.web');
        canpointCommon::logDegub(serialize($info), "pay", '.web');
        $this->log->info($info);

        $pay_order = array(
            'payorno' => 2,
            'buyer_email' => $info['buyer_email'],
            'tele' => $info['buyer_id'],
            'paytime' => $info['notify_time'],
            'dealId' => $info['trade_no']
        );
        $this->log->info($pay_order);

        //订单的信息
        $is_pay = $pay_db->where("orderId='" . $info['out_trade_no'] . "'")->find();

        require_once './Application/CPLib/webpay/notify_url.php';

        canpointCommon::logDegub("notify_recharge_alipay=" . serialize($is_pay), '.web');
        $this->log->info($is_pay);
        $this->log->info($notify_yz);
        if ($notify_yz) {
            $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
            $data1 = array();
            $data1['k_type'] = 3;
            $re = $NSQ->where("k_id='$info[out_trade_no]'")->save($data1);

            if ($re) {
                $fen = $NSQ->where("k_id='" . $info[out_trade_no] . "'")->select();
                canpointCommon::logDegub("notify_recharge_select_fen=" . $NSQ->getLastSql(), '.web');

                $data['uid'] = $fen[0]['k_userid'];
                $data['uname'] = $fen[0]['k_userid'];
                $data['action'] = "chongzhi1";
                $data['note'] = "充值";
                $data['val'] = $fen[0]['k_jifen'];
                $data['flag'] = 3;
                $data['date'] = date("Y-m-d H:i:s", time());
                $log = M("u_coinlog");
                $log->add($data);
                $this->cash($fen[0]['k_userid'], $fen[0]['k_jifen']);
                canpointCommon::logDegub("notify_recharge_coinlog_add=" . $log->getLastSql(), '.web');
                $this->log->info($data);
            }

            $this->log->info("user_action_purch,recharge,pc_ Alipay,".$fen[0]['k_userid'].",".date("Y-m-d H:i:s").",".$fen[0]['k_jifen'].",".$info['out_trade_no']);
            //支付宝充值成功—发通知
            $messge = array(
                'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
            );
            curl_post($messge);
        }
    }

    public function get_payarr() {
        $pay_db = D('t_order_sn');
        $info = I();
        canpointCommon::logDegub("recharge_get_payarr_info=" . serialize($info), 'Payment');
        $this->log->info($info);
        $pay_order = array(
            'payorno' => 2, 'buyer_email' => $info['buyer_email'], 'tele' => $info['qid'],
            'paytime' => $info['notify_time'], 'dealId' => $info['trade_no']
        );
        $this->log->info($pay_order);
        $is_pay = $pay_db->where("orderId='" . $info['out_trade_no'] . "'")->find();
        if (isMobile()) {
            canpointCommon::logDegub("alipay_return=1", 'Payment');
            require_once './Application/CPLib/webpay/return_url.php';
        } else {
            canpointCommon::logDegub("alipay_return=2", 'Payment');
            require_once './Application/CPLib/pay/return_url.php';
        }

        canpointCommon::logDegub("recharge_alipay=" . serialize($is_pay), 'Payment');
        $this->log->info($is_pay);
        $this->log->info($notify_yz);
        if ($notify_yz) {
            $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
            $data1 = array();
            $data1['k_type'] = 3;
            $re = $NSQ->where("k_id='$info[out_trade_no]'")->save($data1);


            if ($re) {
                $fen = $NSQ->where("k_id='" . $info[out_trade_no] . "'")->select();
                canpointCommon::logDegub("recharge_select_fen=" . $NSQ->getLastSql(), 'Payment');

                $data['uid'] = $fen[0]['k_userid'];
                $data['uname'] = $fen[0]['k_userid'];
                $data['action'] = "chongzhi1";
                $data['note'] = "充值";
                $data['val'] = $fen[0]['k_jifen'];
                $data['flag'] = 3;
                $data['date'] = date("Y-m-d H:i:s", time());
                $log = M("u_coinlog");
                $log->add($data);
                $this->cash($fen[0]['k_userid'], $fen[0]['k_jifen']);
                canpointCommon::logDegub("recharge_coinlog_add=" . $log->getLastSql(), 'Payment');
                $this->log->info($data);
                $this->log->info("success:".$log->getLastSql());
            }

            //支付宝充值成功—发通知
            $messge = array(
                'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
            );
            curl_post($messge);
        }
        $total_qpb = $_SESSION['pay_jifen'];
        $this->assign('now_rpoint', $_SESSION['now_rpoint']);
        $this->assign('total_qpb', $total_qpb);
        $this->assign('total_fee', $info['total_fee']);
        $this->assign('out_trade_no', $info['out_trade_no']);
        $this->display("payment.recharge.osuccess");
    }

    /*
     * 手机网站支付宝支付
     */

    public function web_get_payarr() {

        $pay_db = D('t_order_sn');
        $info = I();
        canpointCommon::logDegub("return_recharge_get_payarr_info=" . serialize($info), '.web');
        $this->log->info($info);
        $pay_order = array(
            'payorno' => 2, 'buyer_email' => $info['buyer_email'], 'tele' => $info['qid'],
            'paytime' => $info['notify_time'], 'dealId' => $info['trade_no']
        );
        $this->log->info($pay_order);
        $is_pay = $pay_db->where("orderId='" . $info['out_trade_no'] . "'")->find();
        $this->log->info($is_pay);

        require_once './Application/CPLib/webpay/return_url.php';
        canpointCommon::logDegub("return_recharge_alipay=" . serialize($is_pay), '.web');
        $this->log->info($notify_yz);
        if ($notify_yz) {
            $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
            $data1 = array();
            $data1['k_type'] = 3;
            $re = $NSQ->where("k_id='$info[out_trade_no]'")->save($data1);


            if ($re) {
                $fen = $NSQ->where("k_id='" . $info[out_trade_no] . "'")->select();
                canpointCommon::logDegub("return_recharge_select_fen=" . $NSQ->getLastSql(), '.web');

                $data['uid'] = $fen[0]['k_userid'];
                $data['uname'] = $fen[0]['k_userid'];
                $data['action'] = "chongzhi1";
                $data['note'] = "充值";
                $data['val'] = $fen[0]['k_jifen'];
                $data['flag'] = 3;
                $data['date'] = date("Y-m-d H:i:s", time());
                $log = M("u_coinlog");
                $log->add($data);
                $this->cash($fen[0]['k_userid'], $fen[0]['k_jifen']);
                canpointCommon::logDegub("return_recharge_coinlog_add=" . $log->getLastSql(), '.web');
                $this->log->info($data);
                 $this->log->info("success:".  $log->getLastSql());
            }

            //支付宝充值成功—发通知
            $messge = array(
                'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
            );
            curl_post($messge);
        }
        $total_qpb = $_SESSION['pay_jifen'];
        $this->assign('now_rpoint', $_SESSION['now_rpoint']);
        $this->assign('total_fee', $info['total_fee']);
        $this->assign('total_qpb', $total_qpb);
        $this->assign('out_trade_no', $info['out_trade_no']);
        $this->display("payment.recharge.osuccess");
    }

    function IP($ip = '', $file = 'UTFWry.dat') {

        $_ip = array();
        if (isset($_ip [$ip])) {
            return $_ip [$ip];
        } else {
            // import ("ORG.Net.IpLocation");
            $iplocation = new canpointIpLocation($file);
            $location = $iplocation->getlocation($ip);
            $location['country'] = iconv("GB2312", "UTF-8", $location['country']);
            $location['area'] = iconv("GB2312", "UTF-8", $location['area']);
            $_ip [$ip] = $location ['country'] . $location ['area'];
        }
        return $_ip [$ip];
    }

    /*
     * 银联支付—前台回调URL
     */

    public function front_notify() {
        require_once 'Application/CPLib/unionpay/example/front_notify.php';
        canpointCommon::logDegub("recharge_unionpay_stat=" . $stat, 'Payment');
        if ($stat == 1) {
            $pay_db = D('t_order_sn');
            $info = $arr_ret;
            canpointCommon::logDegub(serialize($arr_ret), 'unionpay_post', 'Payment');

//            $uid = $_SESSION['uid'];   ##被充值人的guid
            //1.订单信息
            $is_pay = $pay_db->where("orderId='" . $info['orderNumber'] . "'")->find();

            $uid = $is_pay['memberid'];   ##被充值人的guid
            canpointCommon::logDegub("unionpay_uid=" . $uid, 'Payment');

            //2.订单未付款 2015052200001000690053636589  201505221150474619462
            if ($is_pay['payorno'] == 1) {
                //3.订单支付状态修改
                $pay_order = array('payorno' => 2, 'buyer_email' => $info['cardNumber'], 'paytime' => $info['respTime']);
                $pay_db->where("orderId='" . $info['orderNumber'] . "'")->save($pay_order);
                canpointCommon::logDegub("unionpay_upd_order_stat=" . $pay_db->getLastSql(), 'Payment');

                //4.用户全品币增加
                $table_index = substr($uid, -2);
                $study = M("canpoint_user.user_" . $table_index);
                $num = $info['settleAmount'] * 0.01;  //充值的金额
                $oldmny = $study->where("u_guid='" . $is_pay['memberid'] . "'")->getField('u_rpoint');  //老金额

                $mony['u_rpoint'] = $oldmny + $num;  //充值后新金额

                if ($study->where("u_guid='" . $is_pay['memberid'] . "'")->save($mony)) {
                    canpointCommon::logDegub("unionpay_user_upd=" . $study->getLastSql(), 'Payment');
                    $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
                    $data1 = array();
                    $data1['k_type'] = 3;
                    $re = $NSQ->where("k_id='" . $info['orderNumber'] . "'")->save($data1);

                    if ($re) {
                        $fen = $NSQ->where("k_id='" . $info['orderNumber'] . "'")->select();

                        $data['uid'] = $fen[0]['k_userid'];
                        $data['uname'] = $fen[0]['k_userid'];
                        $data['action'] = "chongzhi1";
                        $data['note'] = "充值";
                        $data['val'] = $fen[0]['k_jifen'];
                        $data['flag'] = 3;
                        $data['date'] = date("Y-m-d H:i:s", time());
                        $log = M("u_coinlog");
                        $log->add($data);

                        //网银充值成功—发通知
                        $messge = array(
                            'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                            'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                        );
                        curl_post($messge);
                    }
                }
            }
            $this->assign('total_fee', $arr_ret['orderAmount'] * 0.01);
            $this->assign('out_trade_no', $arr_ret['orderNumber']);
            $this->display("payment.recharge.osuccess");
        }
    }

    /*
     * 银联支付—后台回调URL
     */

    public function back_notify() {
        require_once 'Application/CPLib/unionpay/example/back_notify.php';
        if ($stat == 1) {
            $pay_db = D('t_order_sn');
            $info = $arr_ret;
            canpointCommon::logDegub("unionpay_post=" . serialize($arr_ret), 'Payment');

//            $uid = $_SESSION['uid'];   ##被充值人的guid
//            canpointCommon::logDegub("unionpay_uid=" . $uid, 'Payment');
            //1.订单信息
            $is_pay = $pay_db->where("orderId='" . $info['orderNumber'] . "'")->find();

            $uid = $is_pay['memberid'];   ##被充值人的guid
            canpointCommon::logDegub("unionpay_uid=" . $uid, 'Payment');

            //2.订单未付款 2015052200001000690053636589  201505221150474619462
            if ($is_pay['payorno'] == 1) {
                //3.订单支付状态修改
                $pay_order = array('payorno' => 2, 'buyer_email' => $info['cardNumber'], 'paytime' => $info['respTime']);
                $pay_db->where("orderId='" . $info['orderNumber'] . "'")->save($pay_order);
                canpointCommon::logDegub("unionpay_upd_order_stat=" . $pay_db->getLastSql(), 'Payment');

                //4.用户全品币增加
                $table_index = substr($uid, -2);
                $study = M("canpoint_user.user_" . $table_index);
                $num = $info['settleAmount'] * 0.01;  //充值的金额
                $oldmny = $study->where("u_guid='" . $is_pay['memberid'] . "'")->getField('u_rpoint');  //老金额
                $mony['u_rpoint'] = $oldmny + $num;  //充值后新金额

                $sture = $study->where("u_guid='" . $is_pay['memberid'] . "'")->save($mony);
                canpointCommon::logDegub("unionpay_user_upd=" . $study->getLastSql(), 'Payment');
                if ($sture !== false) {

                    $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
                    $data1 = array();
                    $data1['k_type'] = 3;
                    $re = $NSQ->where("k_id='" . $info['orderNumber'] . "'")->save($data1);

                    if ($re) {
                        $fen = $NSQ->where("k_id='" . $info['orderNumber'] . "'")->select();

                        $data['uid'] = $fen[0]['k_userid'];
                        $data['uname'] = $fen[0]['k_userid'];
                        $data['action'] = "chongzhi1";
                        $data['note'] = "充值";
                        $data['val'] = $fen[0]['k_jifen'];
                        $data['flag'] = 3;
                        $data['date'] = date("Y-m-d H:i:s", time());
                        $log = M("u_coinlog");
                        $log->add($data);

                        //网银充值成功—发通知
                        $messge = array(
                            'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                            'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                        );
                        curl_post($messge);
                    }
                }
            }
            $this->assign('total_fee', $arr_ret['orderAmount'] * 0.01);
            $this->assign('out_trade_no', $arr_ret['orderNumber']);
            $this->display("payment.recharge.osuccess");
        }
    }

    public function wx_notify1() {
    
        $this->log->info("wx_notify");
        canpointCommon::logDegub("wxpay=7777", '.wxpay'); 
    }

    public function wx_notify() {
        // require_once "./Application/CPLib/wxpay/native_notify.php";
        /* 订单状态 */
        require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
        require_once "Application/CPLib/wxpay/lib/WxPay.Data.php";
        $Card = $_POST['ordernum'];
        $this->log->info($Card);
        if (isset($Card) && $Card != "") {
            $out_trade_no = $Card;

            $input = new WxPayOrderQuery();
            $input->SetOut_trade_no($out_trade_no);
//            print_r(WxPayApi::orderQuery($input));          
            $order = WxPayApi::orderQuery($input);
        }
        $this->log->info($order['trade_state']);
        echo $order['trade_state'];

        //print_r($order);
//        $this->assign('total_fee', $info['total_fee']/100);
//        $this->assign('out_trade_no', $info['out_trade_no']);
    }

    public function wxsuccess() {
        // require_once "./Application/CPLib/wxpay/native_notify.php";

        /* 订单状态 */
        require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
        require_once "Application/CPLib/wxpay/lib/WxPay.Data.php";
        $Card = $_GET['ordernum'];
        $this->log->info($Card);
        if (isset($Card) && $Card != "") {
            $out_trade_no = $Card;
            $input = new WxPayOrderQuery();
            $input->SetOut_trade_no($out_trade_no);
            $order = WxPayApi::orderQuery($input);
            $this->log->info($order);
            if ($order['err_code'] == 'ORDERNOTEXIST') {
                exit('订单不存在');
            }

            if ($order['trade_state'] == 'SUCCESS') {
                $pay_db = D('t_order_sn');
                canpointCommon::logDegub(serialize($order), 'wxpay_post', 'Payment');
                $uid = $_SESSION['uid'];   ##被充值人的guid
                //1.订单信息
                $is_pay = $pay_db->where("orderId='" . $order['out_trade_no'] . "'")->find();
                $this->log->info($is_pay);
                //2.订单未付款   
                if ($is_pay['payorno'] == 1) {
                    //3.订单支付状态修改
                    $pay_order = array('payorno' => 2, 'paytime' => date("Y-m-d H:i:s", strtotime($order['time_end'])));
                    $pay_db->where("orderId='" . $order['out_trade_no'] . "'")->save($pay_order);
                    canpointCommon::logDegub("wxpay_upd_order_stat=" . $pay_db->getLastSql(), 'Payment');
                    $this->log->info("wxpay_upd_order_stat=" . $pay_db->getLastSql());

                    //4.用户全品币增加
                    $table_index = substr($uid, -2);
                    $study = M("canpoint_user.user_" . $table_index);
                    $num = $order['total_fee'] * 0.01;  //充值的金额
                    $oldmny = $study->where("u_guid='" . $is_pay['memberid'] . "'")->getField('u_rpoint');  //老金额
                    canpointCommon::logDegub("wxpay_user_getoldrpoint=" . $study->getLastSql(), 'Payment');
                    $this->log->info("wxpay_user_getoldrpoint=" . $study->getLastSql());
                    //存在红包奖励
                    if (!empty($is_pay['bonus'])) {
                        $num += $is_pay['bonus'];
                    }
                    $mony['u_rpoint'] = $oldmny + $num;  //充值后新金额
                    $this->log->info($mony['u_rpoint']);
                    if ($study->where("u_guid='" . $is_pay['memberid'] . "'")->save($mony)) {
                        //修改红包状态
                        $active_model = new ActiveModel();
                        $active_model->updateStatus($is_pay['bonus_id'], 2, $is_pay['id']);
                        $this->log->info("wxpay_user_upd=" . $study->getLastSql());
                        canpointCommon::logDegub("wxpay_user_upd=" . $study->getLastSql(), 'Payment');
                        $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
                        $data1 = array();
                        $data1['k_type'] = 3;
                        $re = $NSQ->where("k_id='" . $order['out_trade_no'] . "'")->save($data1);

                        if ($re) {
                            $fen = $NSQ->where("k_id='" . $order['out_trade_no'] . "'")->select();

                            $data['uid'] = $fen[0]['k_userid'];
                            $data['uname'] = $fen[0]['k_userid'];
                            $data['action'] = "chongzhi1";
                            $data['note'] = "充值";
                            $data['val'] = $fen[0]['k_jifen'];
                            $data['flag'] = 3;
                            $data['date'] = date("Y-m-d H:i:s", time());
                            $this->log->info($data);
                            $log = M("u_coinlog");
                            $log->add($data);
                            $this->log->info("success:".  $log->getLastSql());
                            $this->cash($fen[0]['k_userid'], $fen[0]['k_jifen']);
                            //网银充值成功—发通知
                            $messge = array(
                                'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                                'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                            );
                            curl_post($messge);
                            $_SESSION['pay_jifen'] = $fen[0]['k_jifen'];
                        }
                    }
                }
            }
            $total_qpb = $_SESSION['pay_jifen'];
            $this->assign('total_fee', $order['total_fee'] * 0.01);
            $this->assign('total_qpb', $total_qpb);
            $this->assign('out_trade_no', $Card);
            $this->display('payment.recharge.osuccess');
        }
    }

    function wx_qrcode() {
        $data = $_G['data'];
        require_once "Application/CPLib/wxpay/qrcode.php?data=$data";
    }

    /**
     * 增加现金券
     */
    public function cash($guid, $price) {
        $this->log->info($guid . "_" . $price);
        $tlist = M("cash_activename")->where("cash_num=100003")->field("cash_endtime,cash_starttime")->find();
        $this->log->info($tlist);

        if (strtotime($tlist['cash_endtime']) > time() && strtotime($tlist['cash_starttime']) <= time() && $price >=100) {
            $this->log->info("充值满额送");
            $num=$price/100;
            //1.添加领取记录
            if(20*$num>=100){
                $cash_price=100;
            }else{
                $cash_price=20*$num;
            }
            $data['log_endtime'] = $tlist['cash_endtime'];

            $data['log_price'] = $cash_price;
            $data['log_guid'] = $guid;
            $data['log_time'] = date("Y-m-d H:i:s");

            $data['log_state'] = 0;  #0未使用   1已使用  2已过期
            $data['log_number'] = 100003;
            $data['log_type'] = 2;
            $this->log->info($data);
            $re = M("cash_log")->add($data);
            $this->log->info(M("cash_log")->getLastSql());
        } else {
            $this->log->info("不符合满100送20");
        }
    }

    

}
