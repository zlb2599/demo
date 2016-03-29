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
use Think\Page;
use Think\AjaxPage;

class IndexAction extends BaseController {
    /*
     * 财富充值列表
     */

    public function index() {
        #用户guid
        $guid = $_SESSION['user'][0]['u_guid'];
        $log = M('ka_jihuolog');
        $where = "k_userid='$guid' and k_type<>4";

        /* -----------------------------条件参数---------------------------------------- */
        ##最近两年的年份和最近日期
        $month1 = time() - 30 * 24 * 60 * 60;  //最近一个月
        $month2 = time() - 90 * 24 * 60 * 60;  //最近三个月
        $Date_Y = date("Y"); //今年内
        $Date_Y1 = date('Y', time()) - 1; //去年
        $Date_Y2 = date('Y', time()) - 2; //前年

        $ttype = $_GET['ttype'] ? $_GET['ttype'] : 1;  //时间
        $this->log->info($ttype);
        canpointCommon::logDegub("payment_list_ttype=" . $ttype, 'Payment');
        switch ($ttype) {
            case 1:
                $dtime = " and ka_rdate>='" . date("Y-m-d H:i:s", $month1) . "'";
                break;
            case 2:
                $dtime = " and ka_rdate>='" . date("Y-m-d H:i:s", $month2) . "'";
                break;
            case 3:
                $dtime = " and ka_rdate>='" . "$Date_Y-01-01 00：00：00" . "'";
                break;
            case 4:
                $dtime = " and ka_rdate>='" . "$Date_Y1-01-01 00：00：00" . "' and ka_rdate<='" . "$Date_Y-01-01 00：00：00" . "'";
                break;
            case 5:
                $dtime = " and ka_rdate>='" . "$Date_Y2-01-01 00：00：00" . "' and ka_rdate<='" . "$Date_Y1-01-01 00：00：00" . "'";
                break;
            default:
                $dtime = '';
        }
        if ($dtime) {
            $where.=$dtime;
        }

        $ktype = $_GET['ktype'];
        $this->log->info($ktype);
        canpointCommon::logDegub("payment_list_ktype=" . $ktype, 'Payment');
        if ($ktype) {
            $where.=" and k_type=$ktype";
        }

        $key = $_POST['key'];  //搜索
        canpointCommon::logDegub("payment_list_key=" . $key, 'Payment');
        if ($key) {
            $where.=" and k_id like '%" . $key . "%'";
        }
        $this->log->info($where);
        canpointCommon::logDegub("payment_list_where=" . $where, 'Payment');
        /* -----------------------------参数结束---------------------------------------- */


        ###分页
        $count = $log->where($where)->count();    //计算总数
        $this->log->info($log->getLastSql());
        canpointCommon::logDegub("payment_list_totalsql=" . $log->getLastSql(), 'Payment');
        $p = new Page($count, 10);
        $list = $log->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
        $page = $p->show();            //分页的导航条的输出变量

        $this->assign("page", $page);
        $this->assign("list", $list); //数据循环变量
        $this->assign("Date_Y1", $Date_Y1); //去年
        $this->assign("Date_Y2", $Date_Y2); //前年
        $this->assign("ktype", $ktype); //去年
        $this->assign("ttype", $ttype); //前年
        $this->display("payment.index.index");
    }

    /*
     * 充值卡充值
     */

    public function cardpay() {

        if ($_SESSION['user'][0]['u_guid']) {

            $guid = $_SESSION['user'][0]['u_guid']; ##用户guid
            $card = $_POST['cardnum'];                ##卡号
            $this->log->info($card);
            canpointCommon::logDegub("payment_pay_card=" . $card, 'Payment');
            $_SESSION['charge']['card'] = $card;
            
           
            ##用户表
            $stu = M("canpoint_user." . $this->table_name);
            $SS = D("Card")->getUserByGuid($guid, $this->table_name);
            
            /* -------------------每天输错次数----------------------- */
            $ErrorList = D("Card")->getErrorList($guid);
           
            canpointCommon::logDegub("payment_pay_getuser=" . M("card_error_num")->getLastSql(), 'Payment');
            $Time = time();
            if (($ErrorList['num'] >= 5) && $Time < $ErrorList['time'] + 60 * 60 * 24) {
                exit("错误次数过多，充值通道锁定24小时。");
            }

            /* -----------------------------第三种卡qp_cardv2------------------------------------------ */
            $card_begin = substr($card, 0, 3);
            if ($card_begin == 'bta') {

                $cardModel = D("Card");
                $Scard = M("canpoint_card.cardv2");

                //1.全品券—赠送卡每年只能充值10次
                $Rse = D('p_fpoint');
                $cztime = $Rse->where("u_id='" . $guid . "' and p_cr='系统赠送卡'")->limit(1)->order("id desc")->getField('u_rdate');
                $this->log->info($Rse->getLastSql());
                canpointCommon::logDegub("$guid_payment_getcztime=" . $Rse->getLastSql(), 'Payment');

                //2.若最新一次充值不在今年 则今年充值次数归0
                if (date("Y", strtotime($cztime)) != date("Y", time())) {
                    $cishu['u_czhi'] = 0;
                    $stu->where("u_guid=$guid")->save($cishu);
                    $this->log->info($stu->getLastSql());
                    canpointCommon::logDegub("payment_table_user_upduczhi=" . $stu->getLastSql(), 'Payment');
                }

                //3.今年充值超过10次则提示错误
                if ($SS['u_czhi'] >= 10) {
                    exit("对不起！赠送卡每年只能充10张。");
                }

                //4.卡被使用
                $ifuse = $Scard->where("card_no='" . $card . "' and card_isused=1")->count();
                if ($ifuse) {
                    $this->log->info($Scard->getLastSql());
                    canpointCommon::logError("payment_newcard_ifuse=" . $Scard->getLastSql(), 'Payment');
                    exit('学习卡号已经被使用');
                }


                //5.卡是否过期
                $lastTime = $Scard->where("card_no='" . $card . "'")->getField("card_enddate");
                $nowtime = time();
                if ($nowtime >= strtotime($lastTime)) {
                    exit('学习卡已过期');
                }

                //6.学习卡错误
                $have = $Scard->where("card_no='" . $card . "'")->count();
                $this->log->info($Scard->getLastSql());
                canpointCommon::logDegub("payment_geterrornum=" . $Scard->getLastSql(), 'Payment');
                if (!$have) {
                    ##输入错误记录数
                    $ErrorCount = M("card_error_num")->where("u_guid=$guid")->count();
                    if ($ErrorCount) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    exit("学习卡密码错误");
                }


                /*                 * ***********************7.没有错误情况**************************** */

                //7.1 删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                    $this->log->info(M("card_error_num")->getLastSql());
                    canpointCommon::logDegub("guid:payment_delet_error_num=" . M("card_error_num")->getLastSql(), 'Payment');
                }


                //7.2 充值卡信息—添加充值记录
                $Kalist = $Scard->where("card_no='" . $card . "' and (card_isused=0 or card_isused='')")->find();
                $Kalist['card_price'] = intval($Kalist['card_price'] / 100);
                if (!is_array($Kalist)) {
                    exit('卡状态出错');
                }

                $cardModel->addjihuolog($card, $Kalist['card_price'], $guid, $_SESSION['user'][0]['u_nickname'], get_client_ip());


                //7.3 新卡全部为全品券—表fpoint添加记录
                $cardModel->addfpoint($guid, $Kalist['card_price'], $SS['u_fpoint'], $_SESSION['user'][0]['u_name']);


                //7.4 用户本年充值次数加1 并且全品券增加
                $stu->where("u_guid=$guid")->setInc('u_czhi', 1);
                $re = $stu->where("u_guid=$guid")->setInc('u_fpoint', $Kalist['card_price']);
                $this->log->info($stu->getLastSql());
                canpointCommon::logDegub("payment_incczhi=" . $stu->getLastSql(), 'Payment');
                //7.5 u_coinlog记录表增加
                if ($re) {
                    $cardModel->addcoin($guid, $Kalist['card_price']);

                    //7.6 更新新表状态
                    $save = array(
                        'card_isused' => 1,
                        'card_guid' => $guid,
                        'card_guip' => get_client_ip(),
                        'card_usedtime' => date("Y-m-d H:i:s")
                    );
                    $cardRetu = $Scard->where("card_no='" . $card . "'")->save($save);
                    if ($cardRetu) {
                        //新卡充值全品券—发通知
                        $messge = array(
                            'sender_uid' => 1, 'recipient_uid' => $guid,
                            'action_type' => 1, 'message_content' => "您的全品券金额增加了" . $Kalist['card_price'] . " 元 <a target='_blank' href='http://pay.canpoint.net/money/myfpoint/index' >查看详情</a>"
                        );
                        curl_post($messge);

                        header("location:/payment/recharge/csuccess");
                    }
                }
                exit;
            }
            /* -------------------------------------------------------------------------------------- */
           
            if (strlen($card) > 12 && strlen($card)!==16 ) {
                /* ---------------------------旧卡---------------------------------- */
                $CardModel = D("Card");

                $pcard = substr($card, 0, 15);   ##卡号

                $ppw = substr($card, 15);        ##密码
                $this->log->info($pcard . "," . $ppw);
                canpointCommon::logDegub("payment_pay=cardnum:" . $pcard . ",pass:" . $ppw, 'Payment');

                $info['c_uid'] = $guid;
                $info['c_ucard'] = $pcard;
                $info['c_upass'] = $ppw;

                //1.卡批次
                $k_part = substr($pcard, 0, 10);
                $stus = D("Card")->getKaList($k_part);
                if (!$stus) {
                    ##输入错误记录数
                    $ErrorCount = D("Card")->getErrorList($guid);
                    if (is_array($ErrorCount)) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    exit("卡号不正确，请重新输入");
                }

                //2.卡状态是否被使用
                $Spp = $CardModel->getListByCard($k_part, $pcard);

                $_SESSION['charge']['ka_type'] = $Spp['ka_type'];   ##卡类型
                if ($Spp['ka_use'] == 2) {
                    exit("卡已经被使用");
                }

                //3.卡密码是否正确
                $C_pass = strtoupper($info['c_upass']);

                if ($Spp['ka_pass'] != $C_pass) {
                    exit("卡密码错误！");
                }

                //4.卡是否过期
                $Scard = M($k_part);
                $n = date("Y-m-d");
                //$lpp = $Scard->where("k_no='$info[c_ucard]' and ka_stat='2' and ka_use='1' and ka_jhlinedate<'$n'")->select();
                $lpp = $Scard->where("k_no='$info[c_ucard]' and ka_stat='2' and ka_use='1' and '2015-12-31'<'$n'")->select(); #过期时间
                $this->log->info($Scard->getLastSql());
                canpointCommon::logDegub("payment_pay_kalinetime_sql=" . $Scard->getLastSql(), 'Payment');
                if ($lpp) {
                    exit("卡已经过期");
                }


                /* -----------------------------5.没有错误情况------------------------------------ */
                //5.1删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                    canpointCommon::logDegub("payment_pay_delerrornum=" . M("card_error_num")->getLastSql(), 'Payment');
                }


                if ($Spp['ka_type'] == 1) {//储蓄卡
                    /* ------------储蓄卡—全品币------------ */
                    //5.2 激活日志
                    $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
                    $data = array();
                    $data['k_id'] = $info['c_ucard'];
                    $data['k_type'] = $Spp['ka_type'];
                    $data['k_mianzi'] = $Spp['ka_price'];
                    $data['k_jifen'] = $Spp['ka_point'];
                    $data['k_userid'] = $guid;
                    $data['k_uname'] = $_SESSION['user'][0]['u_name'];
                    $data['k_ip'] = $_SERVER['REMOTE_ADDR'];
                    $data['ka_diqu'] = $Spp['ka_saree'];
                    $data['ka_agen1'] = $Spp['ka_agentid'];
                    $data['ka_agen2'] = $Spp['ka_agentid'];
                    $data['ka_fdiqu'] = IP($_SERVER['REMOTE_ADDR']);
                    $data['ka_rdate'] = date("Y-m-d H:i:s");
                    $NSQ->add($data); //重值记录
                    $this->log->info($NSQ->getLastSql());
                    canpointCommon::logDegub("payment_pay_addjihuolog=" . $NSQ->getLastSql(), 'Payment');

                    //5.3 记录卡目录激活
                    $KA = array();
                    $KA['id'] = $Spp['id'];
                    $KA['ka_uid'] = $guid;
                    $KA['ka_uname'] = $_SESSION['user'][0]['u_name'];
                    $KA['ka_uip'] = $_SERVER['REMOTE_ADDR'];
                    $KA['ka_jhdate'] = date("Y-m-d H:i:s");
                    $KA['ka_use'] = '2';
                    $KA['ka_aree'] = IP($_SERVER['REMOTE_ADDR']);
                    $Scard->save($KA);
                    $this->log->info($Scard->getLastSql());
                    canpointCommon::logDegub("payment_pay_updkastat=" . $Scard->getLastSql(), 'Payment');

                    //5.4全品币 表rpoint记录
                    $rpoint = array();
                    $Rse = M('p_rpoint');
                    $rpoint['type'] = "官网充值";
                    $rpoint['u_leixing'] = "1";
                    $rpoint['u_id'] = $guid;
                    $rpoint['u_name'] = $_SESSION['user'][0]['u_name'];
                    $rpoint['p_point'] = $Spp['ka_point'];
                    $rpoint['p_oldpoint'] = $SS['u_rpoint'];
                    $rpoint['p_newpoint'] = $Spp['ka_point'] + $SS['u_rpoint'];
                    $rpoint['p_cr'] = "系统储值卡";
                    $rpoint['u_rdate'] = date("Y-m-d H:i:s");
                    $rpoint['u_beizhu'] = "官网充值用户[" . $_SESSION['user'][0]['u_name'] . "(" . $_SESSION['user'][0]['u_id'] . ")]为用户-〉[" . $SS['u_name'] . "(" . $SS['u_guid'] . ")]";
                    $Rse->add($rpoint);
                    canpointCommon::logDegub("payment_table_rpoint_add=" . $Rse->getLastSql(), 'Payment');

                    //5.5 用户本年充值次数加1 并且全品券增加
                    $stu->where("u_guid=$uid")->setInc('u_czhi', 1);
                    $this->log->info($stu->getLastSql());
                    canpointCommon::logDegub("payment_incczhi=" . $stu->getLastSql(), 'Payment');
                    $studyr = array();
                    $sum = $SS['u_rpoint'] + $Spp['ka_point']; //储值积分累加
                    $studyr['u_rpoint'] = $sum;
                    $studyr['u_zjifen'] = $sum;
                    $stu->where("u_guid=$guid")->save($studyr);
                    canpointCommon::logDegub("payment_table_user_incrpoint=" . $stu->getLastSql(), 'Payment');

                    //5.6 u_coinlog记录
                    $data1['uid'] = $guid;
                    $data1['uname'] = $guid;
                    $data1['action'] = "chongzhi";
                    $data1['note'] = "充值";
                    $data1['val'] = $Spp['ka_point'];
                    $data1['flag'] = 3;
                    $data1['date'] = date("Y-m-d", time());
                    $log = M("u_coinlog");
                    $re = $log->add($data1);
                    canpointCommon::logDegub("payment_table_user_incrpoint=" . $stu->getLastSql(), 'Payment');
                    if ($re) {

                        //旧全品币学习卡充值—发通知
                        $messge = array(
                            'sender_uid' => 1, 'recipient_uid' => $guid,
                            'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $Spp['ka_point'] . " 元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                        );
                        curl_post($messge);

                        header("location:/payment/recharge/csuccess");
                    }
                } else {//赠送卡
                    /* ----------------------------全品券--------------------------------- */

                    //1.赠送卡每年只能充值10次
                    $Rse = M('p_fpoint');
                    $cztime = $Rse->where("u_id='" . $guid . "' and p_cr='系统赠送卡'")->limit(1)->order("id desc")->getField('u_rdate');


                    //2.若最新一次充值不在今年 则今年充值次数归0
                    if (date("Y", strtotime($cztime)) != date("Y", time())) {
                        $cishu['u_czhi'] = 0;
                        $stu->where("u_guid=$guid")->save($cishu);
                        canpointCommon::logDegub("payment_table_user_upduczhi=" . $stu->getLastSql(), 'Payment');
                    }
                    if ($SS['u_czhi'] >= 10) {
                        exit("对不起！赠送卡每年只能充10张。");
                    } else {
                        //3.卡激活日志
                        $NSQ = M('ka_jihuolog');
                        $data = array();
                        $data['k_id'] = $info['c_ucard'];
                        $data['k_type'] = $Spp['ka_type'];
                        $data['k_mianzi'] = $Spp['ka_price'];
                        $data['k_jifen'] = $Spp['ka_point'];
                        $data['k_userid'] = $SS['u_guid'];
                        $data['k_uname'] = $SS['u_name'];
                        $data['k_ip'] = $_SERVER['REMOTE_ADDR'];
                        $data['ka_diqu'] = $Spp['ka_saree'];
                        $data['ka_agen1'] = $Spp['ka_agentid'];
                        $data['ka_agen2'] = $Spp['ka_agentid'];
                        $data['ka_fdiqu'] = IP($_SERVER['REMOTE_ADDR']);
                        $data['ka_rdate'] = date("Y-m-d H:i:s");
                        $NSQ->add($data); //充值记录
                        canpointCommon::logDegub("payment_pay_addjihuolog=" . $NSQ->getLastSql(), 'Payment');

                        //4.记录卡目录激活
                        $KA = array();
                        $KA['id'] = $Spp['id'];
                        $KA['ka_uid'] = $SS['u_guid'];
                        $KA['ka_uname'] = $SS['u_name'];
                        $KA['ka_uip'] = $_SERVER['REMOTE_ADDR'];
                        $KA['ka_jhdate'] = date("Y-m-d H:i:s");
                        $KA['ka_use'] = '2';
                        $KA['ka_aree'] = IP($_SERVER['REMOTE_ADDR']);
                        $Scard->save($KA);
                        canpointCommon::logDegub("payment_pay_updkastat=" . $Scard->getLastSql(), 'Payment');

                        //5.全品券卡充值记录rpoint
                        $rpoint = array();
                        $rpoint['type'] = "官网充值";
                        $rpoint['u_leixing'] = "1";
                        $rpoint['u_id'] = $SS['u_guid'];
                        $rpoint['u_name'] = $SS['u_name'];
                        $rpoint['p_point'] = $Spp['ka_point'];
                        $rpoint['p_oldpoint'] = $SS['u_fpoint'];
                        $rpoint['p_newpoint'] = $Spp['ka_point'] + $SS['u_fpoint'];
                        $rpoint['p_cr'] = "系统赠送卡";
                        $rpoint['u_rdate'] = date("Y-m-d H:i:s");
                        $rpoint['u_beizhu'] = "官网充值用户[" . $_SESSION['user'][0]['u_name'] . "(" . $_SESSION['user'][0]['u_guid'] . ")]为用户-〉[" . $SS['u_name'] . "(" . $SS['u_guid'] . ")]";
                        $Rse->add($rpoint);
                        canpointCommon::logDegub("$guid_payment_table_fpoint_add=" . $Rse->getLastSql(), 'Payment');

                        //6.个人全品券的增加
                        $studyr = array();
                        $sum = $SS['u_fpoint'] + $Spp['ka_point']; //赠送积分累加
                        $czhi = $SS['u_czhi'] + 1; //充值次数加1
                        $studyr['u_fpoint'] = $sum;
                        $studyr['u_czhi'] = $czhi;
                        $stu->where("u_guid=$guid")->save($studyr);
                        canpointCommon::logDegub("payment_table_user_incfpoint=" . $stu->getLastSql(), 'Payment');

                        //7.u_coinlog表
                        $data1['uid'] = $guid;
                        $data1['uname'] = $guid;
                        $data1['action'] = "chongzhi";
                        $data1['note'] = "充值";
                        $data1['val'] = $Spp['ka_point'];
                        $data1['flag'] = 4;
                        $data1['date'] = date("Y-m-d", time());
                        $log = M("u_coinlog");
                        $re = $log->add($data1);
                        canpointCommon::logDegub("payment_table_coinlog_add=" . $log->getLastSql(), 'Payment');
                        if ($re) {

                            //旧全品券学习卡充值—发通知
                            $messge = array(
                                'sender_uid' => 1, 'recipient_uid' => $guid,
                                'action_type' => 1, 'message_content' => "您的全品券金额增加了" . $Spp['ka_point'] . " 元 <a target='_blank' href='http://pay.canpoint.net/money/myfpoint/index' >查看详情</a>"
                            );
                            curl_post($messge);

                            header("location:/payment/recharge/csuccess");
                        }
                    }
                }
            } elseif (strlen($card) == 16) {
                
                /* ---------------------------实物卡---------------------------------- */
                ##实物卡表
                $Scard = M("canpoint_card.cardv3");
                $_SESSION['charge']['ka_type'] =1;
                $_SESSION['charge']['card']=$card;
                //1.卡被使用
                $ifuse = $Scard->where("card_no='" . $card . "' and card_isused=1")->count();
                if ($ifuse) {
                    $this->log->info("card is used");
                    exit('学习卡号已经被使用');
                }

                //5.学习卡错误
                $have = $Scard->where("card_no=$card")->count();
                if (!$have) {
                    $this->log->info("card is error");
                    ##输入错误记录数
                    $ErrorCount = M("card_error_num")->where("u_guid=$guid")->count();
                    if ($ErrorCount) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    exit("学习卡密码错误");
                }

                //6.卡是否过期—不同批次卡过期时间不同
                $lastTime = $Scard->where("card_no='" . $card . "'")->getField("card_enddate");
                $nowtime = time();
                if ($nowtime >= strtotime($lastTime)) {
                    exit('学习卡已过期');
                }

                /*                 * ***********************7.没有错误情况**************************** */

                //7.1 删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                }


                //7.2 充值卡信息—添加充值记录
                $Kalist = $Scard->where("card_no=$card and (card_isused=0 or card_isused='')")->find();

                if (!is_array($Kalist)) {
                    exit('卡状态出错');
                }
                $NSQ = M('ka_jihuolog');

                $data = array();
                $data['k_id'] = $card;
                $data['k_type'] = 1;
                $data['k_mianzi'] = $Kalist['card_price'];
                $data['k_jifen'] = null;
                $data['k_userid'] = $guid;
                $data['k_uname'] = $_SESSION['user'][0]['u_nickname'];
                $data['k_ip'] = $_SERVER['REMOTE_ADDR'];
                $data['ka_diqu'] = null;
                $data['ka_agen1'] = 'quanpin';
                $data['ka_agen2'] = 'quanpin';
                $data['ka_fdiqu'] = IP($_SERVER['REMOTE_ADDR']);
                $data['ka_rdate'] = date("Y-m-d H:i:s");
                $NSQ->add($data);
                $this->log->info($NSQ->getLastSql());
                canpointCommon::logDegub("$guid_payment_pay_addjihuolog=" . $NSQ->getLastSql(), 'Payment');

                //7.3 实物卡全部为全品币—表rpoint添加记录
                $Rse = D('p_rpoint');
                $rpoint = array();
                $rpoint['type'] = "官网充值";
                $rpoint['u_leixing'] = "1";
                $rpoint['u_id'] = $guid;
                $rpoint['u_name'] = $_SESSION['user'][0]['u_name'];
                $rpoint['p_point'] = null;
                $rpoint['p_oldpoint'] = $Kalist['card_price'];
                $rpoint['p_newpoint'] = $Kalist['card_price'] + $SS['u_rpoint'];
                $rpoint['p_cr'] = "实物卡充值";
                $rpoint['u_rdate'] = date("Y-m-d H:i:s");
                $rpoint['u_beizhu'] = "官网充值用户[" . $_SESSION['user'][0]['u_name'] . "(" . $_SESSION['user'][0]['u_guid'] . ")]为用户-〉[" . $SS['u_name'] . "(" . $SS['u_guid'] . ")]";
                $Rse->add($rpoint);
                $this->log->info("lastid=" . $Rse->getLastInsID());

                //7.4 用户全品币增加
                $re = $stu->where("u_guid=$guid")->setInc('u_rpoint', $Kalist['card_price']);
                $this->log->info($stu->getLastSql());
                
                //7.5 u_coinlog记录表增加
                if ($re) {
                    $data1['uid'] = $guid;
                    $data1['uname'] = $guid;
                    $data1['action'] = "chongzhi";
                    $data1['note'] = "充值";
                    $data1['val'] = $Kalist['card_price'];
                    $data1['flag'] = 3;
                    $data1['date'] = date("Y-m-d H:i:s", time());
                    $log = M("u_coinlog");
                    $log->add($data1);
                    $this->log->info($stu->getLastSql());
                    //7.6 更新新表状态
                    $updarr = array('card_isused' => 1, 'card_guid' => $guid, 'card_guip' => get_client_ip(), 'card_usedtime' => date("Y-m-d H:i:s"));
                    $cardRetu = $Scard->where("card_no=$card")->save($updarr);
                  
                   
                    if ($cardRetu) {
                        //实物卡充值全品币—发通知
                        $messge = array(
                            'sender_uid' => 1, 'recipient_uid' => $guid,
                            'action_type' => 1, 'message_content' => "您的全品币金额增加了" . $Kalist['card_price'] . " 元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                        );
                        curl_post($messge);

                        header("location:/payment/recharge/csuccess");
                    }
                }
            } else {
                /* ---------------------------新卡---------------------------------- */
                $_SESSION['charge']['ka_type'] = 2;
                ##新卡表
                $Card_index = substr($_POST['cardnum'], -2);
                $Scard = M("card_" . $Card_index);

                //1.全品券—赠送卡每年只能充值10次
                $Rse = D('p_fpoint');
                $cztime = $Rse->where("u_id='" . $guid . "' and p_cr='系统赠送卡'")->limit(1)->order("id desc")->getField('u_rdate');
                $this->log->info($Rse->getLastSql());
                canpointCommon::logDegub("$guid_payment_getcztime=" . $Rse->getLastSql(), 'Payment');

                //2.若最新一次充值不在今年 则今年充值次数归0
                if (date("Y", strtotime($cztime)) != date("Y", time())) {
                    $cishu['u_czhi'] = 0;
                    $stu->where("u_guid=$guid")->save($cishu);
                    $this->log->info($stu->getLastSql());
                    canpointCommon::logDegub("payment_table_user_upduczhi=" . $stu->getLastSql(), 'Payment');
                }

                //3.今年充值超过10次则提示错误
                if ($SS['u_czhi'] >= 10) {
                    exit("对不起！赠送卡每年只能充10张。");
                }

                //4.卡被使用
                $ifuse = $Scard->where("ka_num='" . $card . "' and ka_tag=1")->count();
                if ($ifuse) {
                    canpointCommon::logError("payment_newcard_ifuse=" . $Scard->getLastSql(), 'Payment');
                    exit('学习卡号已经被使用');
                }


                //5.卡是否过期—不同批次卡过期时间不同
                $lastTime = $Scard->where("ka_num='" . $card . "'")->getField("ka_endtime");
                $nowtime = time();
//                if ($Card_index == '01') {
//                    if ($nowtime >= 1446307200) {  //2015-11-01
//                        exit('学习卡已过期');
//                    }
//                } else {
//                    if ($nowtime > 1472572800) { //2016-08-31
//                        exit('学习卡已过期');
//                    }
//                }
                if ($nowtime >= $lastTime) {
                    exit('学习卡已过期');
//                    $json = array('info' => '学习卡已过期', 'status' => 'n');
//                    echo json_encode($json);
//                    exit;
                }

                //6.学习卡错误
                $have = $Scard->where("ka_num=$card")->count();
                $this->log->info($Scard->getLastSql());
                canpointCommon::logDegub("payment_geterrornum=" . $Scard->getLastSql(), 'Payment');
                if (!$have) {
                    ##输入错误记录数
                    $ErrorCount = M("card_error_num")->where("u_guid=$guid")->count();
                    if ($ErrorCount) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    exit("学习卡密码错误");
                }


                /*                 * ***********************7.没有错误情况**************************** */

                //7.1 删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                    canpointCommon::logDegub("$guid:payment_delet_error_num=" . M("card_error_num")->getLastSql(), 'Payment');
                }


                //7.2 充值卡信息—添加充值记录
                $Kalist = $Scard->where("ka_num=$card and (ka_tag=0 or ka_tag='')")->find();

                if (!is_array($Kalist)) {
                    exit('卡状态出错');
                }
                $NSQ = M('ka_jihuolog');

                $data = array();
                $data['k_id'] = $card;
                $data['k_type'] = 2;
                $data['k_mianzi'] = $Kalist['ka_price'];
                $data['k_jifen'] = null;
                $data['k_userid'] = $guid;
                $data['k_uname'] = $_SESSION['user'][0]['u_nickname'];
                $data['k_ip'] = $_SERVER['REMOTE_ADDR'];
                $data['ka_diqu'] = null;
                $data['ka_agen1'] = 'quanpin';
                $data['ka_agen2'] = 'quanpin';
                $data['ka_fdiqu'] = IP($_SERVER['REMOTE_ADDR']);
                $data['ka_rdate'] = date("Y-m-d H:i:s");
                $NSQ->add($data);
                $this->log->info($NSQ->getLastSql());
                canpointCommon::logDegub("$guid_payment_pay_addjihuolog=" . $NSQ->getLastSql(), 'Payment');

                //7.3 新卡全部为全品券—表fpoint添加记录
                $Rse = D('p_fpoint');
                $rpoint = array();
                $rpoint['type'] = "官网充值";
                $rpoint['u_leixing'] = "1";
                $rpoint['u_id'] = $guid;
                $rpoint['u_name'] = $_SESSION['user'][0]['u_name'];
                $rpoint['p_point'] = null;
                $rpoint['p_oldpoint'] = $Kalist['ka_price'];
                $rpoint['p_newpoint'] = $Kalist['ka_price'] + $SS['u_fpoint'];
                $rpoint['p_cr'] = "系统赠送卡";
                $rpoint['u_rdate'] = date("Y-m-d H:i:s");
                $rpoint['u_beizhu'] = "官网充值用户[" . $_SESSION['user'][0]['u_name'] . "(" . $_SESSION['user'][0]['u_guid'] . ")]为用户-〉[" . $SS['u_name'] . "(" . $SS['u_guid'] . ")]";
                $Rse->add($rpoint);
                canpointCommon::logDegub("$guid_payment_table_fpoint_add=" . $Rse->getLastSql(), 'Payment');

                //7.4 用户本年充值次数加1 并且全品券增加
                $stu->where("u_guid=$guid")->setInc('u_czhi', 1);
                $re = $stu->where("u_guid=$guid")->setInc('u_fpoint', $Kalist['ka_price']);
                canpointCommon::logDegub("payment_incczhi=" . $stu->getLastSql(), 'Payment');
                //7.5 u_coinlog记录表增加
                if ($re) {
                    $data1['uid'] = $guid;
                    $data1['uname'] = $guid;
                    $data1['action'] = "chongzhi";
                    $data1['note'] = "充值";
                    $data1['val'] = $Kalist['ka_price'];
                    $data1['flag'] = 4;
                    $data1['date'] = date("Y-m-d", time());
                    $log = M("u_coinlog");
                    $log->add($data1);
                    canpointCommon::logDegub("$guid_payment_table_coinlog_add=" . $log->getLastSql(), 'Payment');
                    //7.6 更新新表状态
                    $cardRetu = $Scard->where("ka_num=$card")->setField("ka_tag", 1);
                    if ($cardRetu) {
                        //新卡充值全品券—发通知
                        $messge = array(
                            'sender_uid' => 1, 'recipient_uid' => $guid,
                            'action_type' => 1, 'message_content' => "您的全品券金额增加了" . $Kalist['ka_price'] . " 元 <a target='_blank' href='http://pay.canpoint.net/money/myfpoint/index' >查看详情</a>"
                        );
                        curl_post($messge);

                        header("location:/payment/recharge/csuccess");
                    }
                }
            }
        }
    }

    /*     * **************************************本类Ajax********************************************** */
    /*
     * 给他人充值
     * @param $account
     */

    public function ajaxGetUname() {
        $this->log->info($_POST);
        $account = $_POST['param'];
        canpointCommon::logDegub("ajaxGetUname=param:$account", 'Payment');

        $Pay = D("Payment");
        if ($Pay->isTphone($account) || $Pay->isEmail($account)) {
            $guid = getUserDidByAccount($account);
        } else {
            $guid = $Pay->getGuidByUid($account);
        }
        $this->log->info($guid);
        // $guid = getUserDidByAccount($account);
        if ($guid) {
            $uname = M('canpoint_user.' . "user_" . substr($guid, -2))->where("u_guid='$guid'")->getField("u_nickname");
            canpointCommon::logDegub("guid=" . $guid, 'Payment');
            $msg = array('status' => 'y', 'info' => "<a class=nick>$uname</a>");
        } else {
            canpointCommon::logError("Guid does not exist Please check ajaxGetUname", 'Payment');
            $msg = array('status' => 'n', 'info' => "帐号不存在");
        }

        echo json_encode($msg);
    }

    /*
     * 检测学习卡
     */

    public function ajaxCheckCard() {
        //1.卡号
        $card = $_POST['param'];
        $this->log->info($card);
        canpointCommon::logDegub("ajaxCheckCard=param:$card", 'Payment');
        $this->log->info($_POST);
        //2.用户guid
        $guid = $_SESSION['user'][0]['u_guid'];
        $this->log->info($guid);
        if ($guid) {
            /* -------------------每天输错次数----------------------- */

            $ErrorList = M("card_error_num")->where("u_guid=$guid")->find();
            $this->log->info($ErrorList);
            $Time = time();
            if (($ErrorList['num'] >= 5) && $Time < $ErrorList['time'] + 60 * 60 * 24) {
                $json = array('info' => '错误次数过多，充值通道锁定24小时。', 'status' => 'n');
                echo json_encode($json);
                exit;
            }

            /* ------------------第三种新卡qp_cardv2------------------------ */
            $card_begin = substr($card, 0, 3);
            $this->log->info($card_begin);
            if ($card_begin == 'bta') {
                $Scard = M("canpoint_card.cardv2");
                //1.卡被使用
                $ifuse = $Scard->where("card_no='" . $card . "' and card_isused=1")->count();
                $this->log->info("ifuse=" . $ifuse);
                if ($ifuse) {
                    $json = array('info' => '学习卡已经被使用', 'status' => 'n');
                    $this->log->info($json);
                    echo json_encode($json);
                    exit;
                }

                //2.卡是否过期
                $lastTime = $Scard->where("card_no='" . $card . "'")->getField("card_enddate");
                $this->log->info("lastTime=" . $lastTime);
                $nowtime = time();
                if ($nowtime >= strtotime($lastTime)) {
                    $json = array('info' => '学习卡已过期', 'status' => 'n');
                    $this->log->info($json);
                    echo json_encode($json);
                    exit;
                }


                //2.学习卡错误
                $have = $Scard->where("card_no='" . $card . "'")->count();
                $this->log->info("have=" . $have);
                if (!$have) {

                    ##输入错误记录数
                    $ErrorCount = M("card_error_num")->where("u_guid=$guid")->count();
                    $this->log->info("ErrorCount=" . $ErrorCount);
                    if ($ErrorCount) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    $json = array('info' => '学习卡密码错误，请重新输入', 'status' => 'n');
                    $this->log->info($json);
                    echo json_encode($json);
                    exit;
                }


                //3.没有错误情况—删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                }

                $json1 = array('info' => '', 'status' => 'y');
                $this->log->info($json1);
                echo json_encode($json1);
                exit;
            }
            /* ------------------------结束----------------------------- */

            $this->log->info(strlen($_POST['param']));
            //实物卡
            if (strlen($_POST['param']) == 16) {
                $this->log->info("this is strlen 16");
                $card = $_POST['param'];
                $Scard = M("canpoint_card.cardv3");
                //1.卡被使用
                $ifuse = $Scard->where("card_no='" . $card . "' and card_isused=1")->count();
                $this->log->info("ifuse=" . $ifuse);
                if ($ifuse) {
                    $json = array('info' => '学习卡已经被使用', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }

                //2.卡是否过期—不同批次卡过期时间不同
                $lastTime = $Scard->where("card_no='" . $card . "'")->getField("card_enddate");
                $this->log->info("lastTime=" . $lastTime);
                $nowtime = time();


                if ($nowtime >= strtotime($lastTime)) {
                    $json = array('info' => '学习卡已过期', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }


                //2.学习卡错误
                $have = $Scard->where("card_no=$card")->count();
                $this->log->info("have=" . $have);
                if (!$have) {

                    ##输入错误记录数
                    $ErrorCount = M("card_error_num")->where("u_guid=$guid")->count();
                    $this->log->info("ErrorCount=" . $ErrorCount);
                    if ($ErrorCount) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    $json = array('info' => '学习卡密码错误，请重新输入', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }


                //3.没有错误情况—删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                }

                $json1 = array('info' => '', 'status' => 'y');
                echo json_encode($json1);
                exit;
            }

            if (strlen($_POST['param']) > 12) {
                $this->log->info("this is oldCard");
                /* -------------------旧卡----------------------- */
                $info['c_ucard'] = substr($_POST['param'], 0, 15);  //卡号
                $info['c_upass'] = substr($_POST['param'], 15);    //密码
                $this->log->info($info);

                $k_part = substr($info['c_ucard'], 0, 10);
                $this->log->info($k_part);
                $Card = M('ka_alist');

                //1.卡批次是否存在
                $stus = $Card->where("k_part='$k_part' and k_ff ='1'")->select();
                if (!$stus) {
                    ##输入错误记录数
                    $ErrorCount = M("card_error_num")->where("u_guid=$guid")->count();
                    if ($ErrorCount) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    $json = array('info' => '卡号不正确，请重新输入', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }

                //2.卡状态是否被使用
                $Scard = M($k_part);
                $Spp = $Scard->where("k_no='$info[c_ucard]'")->find();
                if ($Spp['ka_use'] == 2) {
                    $json = array('info' => '卡已经被使用', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }


                //3.卡是否过期
                $n = date("Y-m-d");
                //$lpp = $Scard->where("k_no='$info[c_ucard]' and ka_use='1' and ka_jhlinedate<'$n'")->select();
                $lpp = $Scard->where("k_no='$info[c_ucard]' and ka_stat='2' and ka_use='1' and '2015-12-31'<'$n'")->select(); #过期时间
                if ($lpp) {
                    $json = array('info' => '卡已经过期', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }


                //4.没有错误情况—删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                }
                $json = array('info' => '', 'status' => 'y');
                echo json_encode($json);
                exit;
            } else {
                $this->log->info("this is newCard");

                /* -------------------新卡----------------------- */
                $Card_index = substr($card, -2);
                $this->log->info("Card_index=" . $Card_index);
                $Scard = M("card_" . $Card_index);
                //1.卡被使用
                $ifuse = $Scard->where("ka_num='" . $card . "' and ka_tag=1")->count();
                $this->log->info("ifuse=" . $ifuse);
                if ($ifuse) {
                    $json = array('info' => '学习卡已经被使用', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }

                //2.卡是否过期—不同批次卡过期时间不同
                $lastTime = $Scard->where("ka_num='" . $card . "'")->getField("ka_endtime");
                $this->log->info("lastTime=" . $lastTime);
                $nowtime = time();
//                if ($Card_index == '01') {
//                    if ($nowtime >= 1446307200) {  //2015-11-01
//                        $json = array('info' => '学习卡已过期', 'status' => 'n');
//                        echo json_encode($json);
//                        exit;
//                    }
//                } else {
//                    if ($nowtime > 1472572800) { //2016-08-31
//                        $json = array('info' => '学习卡已过期', 'status' => 'n');
//                        echo json_encode($json);
//                        exit;
//                    }
//                }

                if ($nowtime >= $lastTime) {
                    $json = array('info' => '学习卡已过期', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }


                //2.学习卡错误
                $have = $Scard->where("ka_num=$card")->count();
                $this->log->info("have=" . $have);
                if (!$have) {

                    ##输入错误记录数
                    $ErrorCount = M("card_error_num")->where("u_guid=$guid")->count();
                    $this->log->info("ErrorCount=" . $ErrorCount);
                    if ($ErrorCount) {
                        D("Card")->updErrorNum($guid); ##记录数加1
                    } else {
                        D("Card")->addErrorNum($guid); ##增加记录数 默认1
                    }
                    $json = array('info' => '学习卡密码错误，请重新输入', 'status' => 'n');
                    echo json_encode($json);
                    exit;
                }


                //3.没有错误情况—删除错误统计数
                if ($ErrorList) {
                    M("card_error_num")->where("u_guid=$guid")->delete();
                }

                $json1 = array('info' => '', 'status' => 'y');
                echo json_encode($json1);
                exit;
            }
        }
    }

}
