<?php

/* * *********************************************************************************
 * Copyright (c) 2005-2011
 * All rights reserved.
 *
 * File:
 * Author:dushasha
 * Editor:
 * Email:1845825214@qq.com
 * Tel:
 * Version:
 * Description:
 * ********************************************************************************* */
?>
<?php

use Common\Controller\BaseController;
use Think\Page;
use Think\AjaxPage;

class IndexAction extends BaseController {

    public function order() {

        //1.获取购买的视频id
        $guid = $_SESSION['user'][0]['u_guid'];
        $keid = $_GET['kid'];
        $this->log->info($keid);
        canpointCommon::logDegub("shop_index_order:kid=" . $keid, "Shop");

        $return_urls = 'http://pay.canpoint.net/shop/index/order/kid/' . $keid;
        $return_urls = str_replace('+', '_', str_replace('/', '-', base64_encode($return_urls)));
         $this->log->info($return_urls);
        if (!$guid) {
            header("location:http://e.canpoint.net/login/index/index/url/$return_urls");
        } else {
            $orderlog = M('bill_order_info');
            $sstlog = M('bill_order_plist');
            $Shop = D("Shop");
            //1.1微课是否已购买过
            $time = time();
            $is_buy = M("zi_new_ke")->where("ke_id=$keid and u_guid=$guid and  ($time<=ke_endtime and $time>=ke_starttime and (state =1 or state =2))  or (state = 3 and  $time > (ke_stattime + 3600)) ")->count();
            if ($is_buy) {
                exit("微课已购买");
            }


            //3.是否已经生成订单
            $num = $Shop->hasOrderByKid($guid, $keid);

            if ($num) {
                $Card = $num;

                //4.加入订单的名字—默认视频的名字
                $keList = $Shop->getFieldByKid($keid, "id,title,x_url,x_price");
            } else {
                //2.生成订单号
                $Card = $Shop->getCardByLastId();

                //4.加入订单的名字—默认视频的名字
                $keList = $Shop->getFieldByKid($keid, "id,title,x_url,x_price");

                //4.1 订单总金额和用户总资产
                $info = $Shop->getUserByGuid($guid, "u_fpoint,u_rpoint");
                $this->log->info($info);
                $total_point = $info['u_fpoint'] + $info['u_rpoint'];
                $total_price = $keList['x_price'];
                 $this->log->info($total_point.",".$total_price);
                canpointCommon::logDegub("shop_index_pay:user_point=" . $total_point, 'Shop');
                canpointCommon::logDegub("shop_index_pay:order_totalprice=" . $total_price, 'Shop');


                //4.2.所需消费的全品币和全品券
                if ($total_point < $total_price) {

                    ##4.1 用户总金额小于订单金额（余额不足）
                    $need_rpoint = 0;
                    $need_fpoint = 0;
                } elseif ($info['u_fpoint'] < $total_price && $info['u_fpoint'] != 0) {

                    ##4.2 总金额足够—用户全品券不为0且少于订单金额
                    $need_fpoint = $info['u_fpoint'];
                    $need_rpoint = $total_price - $info['u_fpoint'];
                } elseif ($info['u_fpoint'] >= $total_price) {

                    ##4.3 总金额足够—用户全品券足于支付订单金额
                    $need_fpoint = $total_price;
                    $need_rpoint = 0;
                } elseif ($info['u_fpoint'] == 0 and $info['u_rpoint'] >= $total_price) {

                    ##4.4 总金额足够—用户全品券为0，则全品币支付
                    $need_fpoint = 0;
                    $need_rpoint = $total_price;
                }
                $this->log->info($need_fpoint.",".$need_rpoint);
                //5.订单表order_info的数组且加入订单
                $newid = $Shop->getArrayOrderInfo($Card, $guid, $keList,$need_fpoint,$need_rpoint);

                //订单微课表order_plist的数组
                $re = $Shop->getArrayOrderPlist($Card, $guid, $keList);
            }

            //3.订单总金额和用户总资产
            $total_point = $info['u_fpoint'] + $info['u_rpoint'];
            $total_price = $orderList['order_price'];
            $this->log->info($total_point.",".$total_price);
            canpointCommon::logDegub("shop_index_pay:user_point=" . $total_point, 'Shop');
            canpointCommon::logDegub("shop_index_pay:order_totalprice=" . $total_price, 'Shop');


            //4.所需消费的全品币和全品券
            if ($total_point < $total_price) {

                ##4.1 用户总金额小于订单金额（余额不足）
                $need_rpoint = 0;
                $need_fpoint = 0;
            } elseif ($info['u_fpoint'] < $total_price && $info['u_fpoint'] != 0) {

                ##4.2 总金额足够—用户全品券不为0且少于订单金额
                $need_fpoint = $info['u_fpoint'];
                $need_rpoint = $total_price - $info['u_fpoint'];
            } elseif ($info['u_fpoint'] >= $total_price) {

                ##4.3 总金额足够—用户全品券足于支付订单金额
                $need_fpoint = $total_price;
                $need_rpoint = 0;
            } elseif ($info['u_fpoint'] == 0 and $info['u_rpoint'] >= $total_price) {

                ##4.4 总金额足够—用户全品券为0，则全品币支付
                $need_fpoint = 0;
                $need_rpoint = $total_price;
            }

            $_SESSION['shop']['card'] = $Card;
            header("location:/shop/index/pay");
        }
    }

    /*
     * 订单提交功能页面
     */

    public function pay() {

        header("Cache-Control:no-cache,must-revalidate,no-store"); //这个no-store加了之后，Firefox下有效
        header("Pragma:no-cache");
        header("Expires:-1");

        $guid = $_SESSION['user'][0]['u_guid'];
        $Card = $_SESSION['shop']['card'];
        $orderlog = M('bill_order_info');
        $this->log->info($Card);
        canpointCommon::logDegub("shop_index_pay:card=" . $Card, 'Shop');

        //1.该条订单数据
        $Shop = D("Shop");
        $orderList = $Shop->getByOrderid($Card);

        //1.1 订单中的微课
        $keList = $Shop->getKeByOrder($Card);

        //2.用户全品币和全品券
        $info = $Shop->getUserByGuid($guid, "u_rpoint");

        //3.订单总金额和用户总资产
        $u_rpoint = $info['u_rpoint'];
        
        $total_price = $orderList['order_price'];

        $this->log->info($u_rpoint.",".$total_price);
        canpointCommon::logDegub("shop_index_pay:user_point=" . $total_point, 'Shop');
        canpointCommon::logDegub("shop_index_pay:order_totalprice=" . $total_price, 'Shop');


        //4.所需消费的全品币和全品券
        if ($u_rpoint < $total_price) {

            ##4.1 用户总金额小于订单金额（余额不足）
            $need_rpoint = 0;
//            $need_fpoint = 0;
        }
//        elseif ($info['u_fpoint'] < $total_price && $info['u_fpoint'] != 0) {
//
//            ##4.2 总金额足够—用户全品券不为0且少于订单金额
//            $need_fpoint = $info['u_fpoint'];
//            $need_rpoint = $total_price - $info['u_fpoint'];
//        } elseif ($info['u_fpoint'] >= $total_price) {
//
//            ##4.3 总金额足够—用户全品券足于支付订单金额
//            $need_fpoint = $total_price;
//            $need_rpoint = 0;
//        } elseif ($info['u_fpoint'] == 0 and $info['u_rpoint'] >= $total_price) {
//
//            ##4.4 总金额足够—用户全品券为0，则全品币支付
//            $need_fpoint = 0;
//            $need_rpoint = $total_price;
//        }
        $this->log->info($need_rpoint);
        canpointCommon::logDegub("shop_index_pay:order_needfpoint=" . $need_rpoint . ",order_needrpoint=" . $need_fpoint, 'Shop');

        $this->assign('keList', $keList);  ##订单总金额
        $this->assign('total_price', $total_price);  ##订单总金额
        $this->assign('need_rpoint', number_format($u_rpoint, 2));  ##订单需要的全品币
//        $this->assign('need_fpoint', number_format($need_fpoint, 2));  ##订单需要的全品券
        $this->assign('Card', $Card);
        $this->display("shop.index.pay");
    }

    public function payok() {
        $guid = $_SESSION['user'][0]['u_guid'];

//        //1.获取上一地址url
//        $pre_url = $_SERVER['HTTP_REFERER'];
//        if ($pre_url != 'http://pay.canpoint.net/shop/index/pay') {
//            exit("来源地址错误！");
//        }
//        canpointCommon::logDegub("shop_index_payok:pre_url=" . $pre_url, 'Shop');
        //2.订单号
        $Shop = D("Shop");
        $order = $_GET['order'];
        $this->log->info($order);
        canpointCommon::logDegub("shop_index_payok:order=" . $order, 'Shop');

        //3.订单的状态
        $orderList = $Shop->getByOrderid($order, "order_status,id", 'Shop');

        if ($orderList['order_status'] == 2) {
            $this->assign('orderid', $orderList['id']);
            $this->assign('card', $order);
            $this->display("shop.index.payok");
        }
    }

    public function payerror() {
        $this->display("shop.index.payerror");
    }

    /* -------------------------------------本类Ajax方法--------------------------------------------------- */

//    /*
//     * 删除视频
//     */
//
//    public function delet() {
//        $flag = $_POST['flag'];
//        if ($flag == 1) {
//            //登录状态删除数据库数据
//            $guid = $_SESSION['user'][0]['u_guid'];
//            $retu = $this->delet_shop($keid, $guid);
//            if ($retu) {
//                $json = array('info' => '', 'status' => 'y');
//            } else {
//                $json = array('info' => '删除失败', 'status' => 'n');
//            }
//        } elseif ($flag == 2) {
//            //未登录删除COOKIE
//            $this->delet_cookie($keid);
//            $json = array('info' => '', 'status' => 'y');
//        }
//        echo json_encode($json);
//    }
//
//    /*
//     * 未登录—删除cookie中的购物车内容
//     * @param $keid 视频id
//     */
//
//    public function delet_cookie($keid) {
//        $kearr = explode(',', $keid);
//        $cookiearr = explode(',', $_COOKIE['shop_keid']);
//        $_COOKIE['shop_keid'] = array_diff($cookiearr, $kearr);
//    }
//
//    /*
//     * 登录—删除ke_shop的购物车内容
//     * @param $keid 视频id
//     */
//
//    public function delet_shop($keid, $guid) {
//        //删除cookie
//        $this->delet_cookie($keid);
//
//        //删除表数据
//        $kearr = explode(',', $keid);
//        $re = M("ke_shop")->where("(keid in ($keid)) and (guid=$guid)")->delete();
//        return $re;
//    }

    /*
     * 订单支付
     */

    public function ajaxPay() {
        //1.获取数据
//        $guid = $_POST['guid'];
//        $card = $_POST['card'];
//        $need_rpoint = $_POST['need_rpoint'];
//        $need_fpoint = $_POST['need_fpoint'];
//        $total_price = $_POST['total_price'];
        $guid = I('post.guid');
        $card = I('post.card');
        $need_rpoint = I('post.need_rpoint');
//        $need_fpoint = I('post.need_fpoint');
        $total_price = I('post.total_price');
        $this->log->info(I('post.'));
        canpointCommon::logDegub("shop_index_ajaxpay:guid=" . $guid . ',card=' . $card . ",total_price=" . $total_price, 'Shop');

        //2.用户全品币和全品券
        $Sajax = D("Sajax");
        $info = $Sajax->getUserByGuid($guid, "u_rpoint");


        //3.订单总金额和用户总资产
        $total_point = $info['u_rpoint'];
        $this->log->info($total_point);
        canpointCommon::logDegub("shop_index_ajaxpay:user_point=" . $total_point, 'Shop');


        //4.所需消费的全品币和全品券
        if ($total_point < $need_rpoint) {

            ##4.1 用户总金额小于订单金额（余额不足）
            $need_rpoint = 0;
            $need_fpoint = 0;
            $json = array('info' => '余额不足', 'status' => 'n');
            echo json_encode($json);
            exit;
        }
//        elseif ($info['u_fpoint'] < $total_price && $info['u_fpoint'] != 0) {
//
//            ##4.2 总金额足够—用户全品券不为0且少于订单金额
//            $need_fpoint = $info['u_fpoint'];
//            $need_rpoint = $total_price - $info['u_fpoint'];
//        } elseif ($info['u_fpoint'] >= $total_price) {
//
//            ##4.3 总金额足够—用户全品券足于支付订单金额
//            $need_fpoint = $total_price;
//            $need_rpoint = 0;
//        } elseif ($info['u_fpoint'] == 0 and $info['u_rpoint'] >= $total_price) {
//
//            ##4.4 总金额足够—用户全品券为0，则全品币支付
//            $need_fpoint = 0;
//            $need_rpoint = $total_price;
//        }
        canpointCommon::logDegub("shop_index_ajaxpay:need_fpoint=" . $need_fpoint . ',need_rpoint=' . $need_rpoint, 'Shop');

        //2.订单和详细表
        $orderlog = M('bill_order_info');
        $sstlog = M('bill_order_plist');

        //3.该订单状态
        $orderStat = $Sajax->getOneFieldByOrderid($card, "order_status");
        $this->log->info($orderStat);
        if ($orderStat != 1 && $orderStat) {
            ##不是未付款状态
            $json = array('info' => '', 'status' => 'n');
            echo json_encode($json);
            exit;
        } else {

            //4.需要更新成的全品币和券
//            $data['u_fpoint'] = $info['u_fpoint'] - $need_fpoint;
            $data['u_rpoint'] = $info['u_rpoint'] - $need_rpoint;

            //4.1全品币或全品券增减记录
//            if ($need_fpoint > 0) {
//                $Sajax->addCoinlog($guid, 'goumai', '购买', $need_fpoint, 4, 1); ##全品券减少记录
//                //购买微课全品券减少—发通知
//                $messge = array(
//                    'sender_uid' => 1, 'recipient_uid' => $guid,
//                    'action_type' => 1, 'message_content' => "您的全品券余额减少了" . $need_fpoint . "元 <a target='_blank' href='http://pay.canpoint.net/money/myfpoint/index' >查看详情</a>"
//                );
//                curl_post($messge);
//            }

            if ($need_rpoint > 0) {
                $Sajax->addCoinlog($guid, 'goumai', '购买', $need_rpoint, 3, 1); ##全品币减少记录
                $messge = array(
                    'sender_uid' => 1, 'recipient_uid' => $guid,
                    'action_type' => 1, 'message_content' => "您的全品币余额减少了" . $need_rpoint . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                );
                curl_post($messge);
            }
            $this->log->info($data);
            //5.更新用户财富
            $re = $Sajax->updateUser($guid, $data);

            if ($re) {

                ##5.1更新订单表
                $order['order_status'] = 2;
                $order['order_time_pay'] = date('Y-m-d H:i:s');
                $res = $orderlog->where("order_num='" . $card . "'")->save($order);

                ##5.2更新订单微课表
                if ($res) {

                    $keList = $sstlog->where("plist_ordernum='" . $card . "'")->field("plist_productid")->select();
                    foreach ($keList as $v) {
                        ##5.3课有效期
                        $youxiaoqi = M("zi_ke")->where("id=" . $v['plist_productid'])->getField("youxiaoqi");
                        $endtime = date("Y-m-d", strtotime("+$youxiaoqi month"));
                        $plist['plist_endtime'] = $endtime;
                        $plist['plist_begintime'] = date("Y-m-d H:i:s");
                        $sstlog->where("plist_productid=" . $v['plist_productid'] . " and plist_ordernum='" . $card . "'")->save($plist);
                    }
                    set_coin('buy', '购买', 'TRUE', 0, $total_price);
                    ##5.4该订单购买的视频id
                    $kid = $orderlog->where("order_num='" . $card . "'")->getField("order_keid");
                    $addnew_ke = file_get_contents("http://my.canpoint.net/Userv2/Zike/addVideBuylData/state/1/ke_id/$kid/dingdan/$card/u_guid/$guid");
                    $this->log->info($addnew_ke);
                    canpointCommon::logDegub("shop_index_ajaxpay:addVideBuylData_getval=" . $addnew_ke, 'new_ke');

//                    #邀请活动购买送豆方法
//                    $nowtime = time();
//                    $starttime = strtotime("2015-07-01 00:00:00");
//                    $endtime = strtotime("2016-07-31 23:59:59");
//
//                    //在活动时间内，且有效邀请码
//                    if (($nowtime >= $starttime && $nowtime <= $endtime)) {
//                        $this->inviteBuy($guid);
//                    }

                    $json = array('info' => $card, 'status' => 'y');
                    echo json_encode($json);
                }
            }
        }
    }

    public function inviteBuy($guid) {
        //1.该用户的注册时间和受邀对象	
        $user_table = "user_" . substr($guid, -2);
        $info = M("canpoint_user.$user_table")->where("u_guid=$guid")->field("u_nickname,u_rdate,code")->find();

        //1.1注册时间
        $rdate = $info['u_rdate'];
        $inv_start = $rdate; #活动的开始
        $inv_end = date('Y-m-d 23:59:59', strtotime(date('Y-m-01', strtotime($rdate)) . ' +1 month -1 day'));  #活动的结束
        //1.2今天的时间
        $now_time = date('Y-m-d H:i:s');
        $today_start = date('Y-m-d 00:00:00'); #今天开始
        $today_end = date('Y-m-d 23:59:59'); #今天结束
        //2.活动只限注册当月
        if ($now_time > $inv_start || $now_time < $inv_end) {
            //2.邀请人
            $invter = $info['code'];

            //3.被邀请人
            $byinvter = $guid;
            $haslogin = M("u_coinlog")->where("date>='" . $today_start . "' and date<='" . $today_end . "' and action like '%inv_reg_buy%' and flag=1 and uid=$invter and fid=$byinvter")->count();

            //4.当天没有登录过
            if (!$haslogin) {
                #4.1给邀请人加学分
                $invterUser = M("canpoint_user.user_" . substr($invter, -2));
                $retu = $invterUser->where("u_guid=$invter")->setInc("u_qdou", 2);
                if ($retu !== false) {
                    #4.2u_coinlog加记录
                    $inv['uid'] = $invter;
                    $inv['uname'] = $invter;
                    $inv['action'] = 'inv_reg_buy';
                    $inv['note'] = '购买贡献';
                    $inv['val'] = 2;
                    $inv['flag'] = 2;
                    $inv['date'] = date("Y-m-d H:i:s");
                    $inv['fid'] = $byinvter;
                    M("u_coinlog")->add($inv);

                    #4.3 活动内容加记录
                    $invterNick = $invterUser->where("u_guid=$invter")->getField("u_nickname");
                    M("inv_reg_notice")->add(array('notice_note' => $invterNick . "获得1个全品豆，来自邀请对象" . $info['u_nickname'] . "的购买。", 'notice_date' => time()));
                }
            }
        }
    }

}
