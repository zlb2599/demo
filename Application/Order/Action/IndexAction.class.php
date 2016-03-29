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
?><?php

use Common\Controller\BaseController;
use Think\Page;
use Think\AjaxPage;

class IndexAction extends BaseController {

    public function __construct() {
        parent::__construct();
        $this->guid = $_SESSION['user'][0]['u_guid'];
    }

    /*
     * 订单列表
     */

    public function index() {
        $this->log->info($_GET);
        #用户guid
        $guid = $_SESSION['user'][0]['u_guid'];
        $pageP = isset($_GET['p']) ? $_GET['p'] : 1;
        ##最近两年的年份和最近日期
        $month1 = time() - 30 * 24 * 60 * 60;  //最近一个月
        $month2 = time() - 90 * 24 * 60 * 60;  //最近三个月
        $month6 = time() - 180 * 24 * 60 * 60;  //最近三个月
        $this->log->info($month1 . "," . $month2 . "," . $month6);
        //        $Date_Y = date("Y"); //今年内
        //        $Date_Y1 = date('Y', time()) - 1; //去年
        //        $Date_Y2 = date('Y', time()) - 2; //前年
        ###分页数据
        /* -------------查询条件---------------- */
        $ttype = $_GET['ttype'] ? $_GET['ttype'] : 1;  //时间
        $this->log->info($ttype);
        switch ($ttype) {
            case 1:
                $dtime = " and order_time_create>='" . date("Y-m-d H:i:s", $month1) . "'";
                break;
            case 2:
                $dtime = " and order_time_create>='" . date("Y-m-d H:i:s", $month2) . "'";
                break;
            case 3:
                $dtime = " and order_time_create>='" . date("Y-m-d H:i:s", $month6) . "'";
                break;
            //            case 3:
            //                $dtime = " and order_time_create>='" . "$Date_Y-01-01 00：00：00" . "'";
            //                break;
            //            case 4:
            //                $dtime = " and order_time_create>='" . "$Date_Y1-01-01 00：00：00" . "' and order_time_create<='" . "$Date_Y-01-01 00：00：00" . "'";
            //                break;
            //            case 5:
            //                $dtime = " and order_time_create>='" . "$Date_Y2-01-01 00：00：00" . "' and order_time_create<='" . "$Date_Y1-01-01 00：00：00" . "'";
            //                break;
            default:
                $dtime = '';
        }
        $this->log->info($dtime);
        $stype = $_GET['stype'];  //状态
        switch ($stype) {
            case 1:   //未付款
                $stat = " and order_status=1";
                break;
            case 2:   //已付款
                $stat = " and order_status=2";
                break;
            case 3:  //已取消
                $stat = " and order_status=3";
                break;
            default:
                $stat = '';
        }
        $this->log->info($stat);
        $where = "order_guid='" . $guid . "' and order_recycle=1";
        $this->log->info($where);
        //搜索条件
        $key = $_POST['key'];
        $this->log->info($key);
        if ($key) {
            $where .= " and order_num like '%" . $key . "%'";
        }

        //时间
        if ($dtime) {
            $where .= $dtime;
        }

        //状态
        if ($stat) {
            $where .= $stat;
        }
        $this->log->info($where);
        /* -------------条件结束---------------- */


        $count = M("bill_order_info")->where($where)->count();
        $p = new Page($count, 10);
        $list = M("bill_order_info")->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
        foreach ($list as $key => $vo) {
            if ($vo['order_type'] == 5) {
                $list[$key]['isEc'] = 5;   #高考猜题
            } elseif ($vo['order_type'] == 4) {
                $list[$key]['isEc'] = 4;   #实物兑换
            } else {
                $list[$key]['isEc'] = 1;   #微课
            }
        }

        if (isMobile()) {
            $CountP = ceil($count / 10);
            $p->setConfig('theme', "%upPage% <li><a>{$pageP}/{$CountP}</a></li> %downPage%");
            $page = $p->Mshow();
        } else {
            $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
            $page = $p->show();
        }

        ####待付款总数
        $nopay = D("Order")->obligation($guid);
        $this->log->info($nopay);

        #####该年级下学科
        //        $cate_01 = $_SESSION['user'][0]['u_xueduan'];
        $table_index = substr($guid, -2);
        $table_name = "user_" . $table_index;
        $cate_01 = M("canpoint_user." . $table_name)->where("u_guid='$guid'")->getField("u_xueduan");
        $this->log->info($cate_01);
        $this->guess($cate_01);


        $this->assign("ttype", $ttype); //学段
        $this->assign("stype", $stype); //去年年份
        $this->assign("cate1", $cate_01); //学段
        $this->assign("Date_Y1", $Date_Y1); //去年年份
        $this->assign("Date_Y2", $Date_Y2); //前年年份
        $this->assign("nopay", $nopay);   //待付款总数
        $this->assign("page", $page);
        $this->assign("list", $list);
        $this->display("order.index.index");
    }

    public function detail() {
//        $this->log->info($_GET);
//        $order_id = $_GET['id'];
//        $p = $_GET['p'] ? (int) $_GET['p'] : 1;
//        #单条订单信息
//        $m = D("Canpoint");
//        //
//        $order = $m->getOrderByID($order_id, $this->guid);
//        $this->log->info($order);
//        $mEc = D('Goods');
//        $data['ec'] = $mEc->getOrderByID($order['order_num']);
//        if ($data['ec']) {
//            $data['ec']['id'] = $order['id'];
//        }
//        $data['goods'] = $mEc->getGoodsByOrderID($data['ec']['order_id'], $p, 10);
//        foreach ($data['goods'] as $v) {
//            $gid[$v['goods_id']] = $v['goods_id'];
//        }
//        $data['list'] = $mEc->getResByID($gid);
//        $this->assign("data", $data);
//        $this->display('order.index.detail');

        $Order = D("Order");
        $order_id = $_GET['id'];
        $info = M("bill_order_info")->where("id=$order_id")->find();
        
        if ($info['order_type'] == 4 || $info['order_type'] == 5) {

            #1.订单信息
            $list = M("canpoint_goods.order_info")->where("order_sn='" . $info['order_num'] . "'")->field("order_id,order_sn,shipping_status,consignee,tel,address")->find();

            #2.订单的商品
            $goods_list = M("canpoint_goods.order_goods")->where("order_id=" . $list['order_id'])->field("goods_id,goods_number,goods_price")->select();
            foreach ($goods_list as $key=>$vo) {
                #3.订单的商品列表
                $data = M("canpoint_goods.goods")->where("goods_id=" . $vo['goods_id'])->field("goods_name,goods_thumb")->find();
                $goods_list[$key]['goods_name'] = $data['goods_name'];
                $goods_list[$key]['goods_thumb'] = $data['goods_thumb'];
            }
            //3.支付方式
            if($info['order_pay_way']==2){
                $url="/shop/buy/pay/id/".  canpointCommon::encrypt($info['order_num']);
            }elseif($info['order_pay_way']==3){
                $url="/shop/buy/wx_pay/id/".canpointCommon::encrypt($info['order_num']);
            }
            
            $this->assign('url', $url);
            $this->assign('goods_list', $goods_list);
            $this->assign('info', $info);
            $this->assign('list', $list);
           
            $this->display("order.index.detail1");
        } else {

            if ($info['order_type'] == 2) {
                $packid = canpointCommon::encrypt($info['order_packageid'], 'Order');
                $this->log->info($packid);
            }
            $count = $Order->getKeTotal($info['order_num']);
            $this->log->info($count);
            ##订单下的视频进行分页
            $p = new Page($count, 10);
            $field = 'plist_productid,plist_productname,plist_price';
            $plist = M("bill_order_plist")->where("plist_ordernum='" . $info[order_num] . "'")->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->field($field)->select();
            $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
            $page = $p->show();

            //$plist=$Order->getKeByOnum($info['order_num'],'plist_productid,plist_productname,plist_price');
            foreach ($plist as $key => $v) {
                $keid .= $v['plist_productid'] . ",";
            }
            $keid = substr($keid, 0, -1);
            $this->log->info($keid);
            $kelist = $Order->getKeById($keid, 'id,cate_01,cate_02,x_url');
            $this->log->info($kelist);
            foreach ($plist as $key => $vo) {
                foreach ($kelist as $k => $v) {

                    if ($vo['plist_productid'] == $v['id']) {
                        $plist[$key]['cate_01'] = $Order->get_xd($v['cate_01']);
                        $plist[$key]['cate_02'] = $Order->get_xk($v['cate_02']);
                        $plist[$key]['image'] = $Order->getimgURL($v['x_url'], '200x134');
                        $plist[$key]['keid'] = canpointCommon::encrypt($v['id']);
                    }
                }
            }

            #####该年级下学科
            //        $cate_01 = $_SESSION['user'][0]['u_xueduan'];
            $table_index = substr($guid, -2);
            $table_name = "user_" . $table_index;
            $cate_01 = M("canpoint_user." . $table_name)->where("u_guid='$guid'")->getField("u_xueduan");
            $this->log->info($cate_01);
            $this->guess($cate_01);

            $this->assign('packid', $packid);
            $this->assign("cate1", $cate_01); //学段
            $this->assign('page', $page);
            $this->assign('info', $info);
            $this->assign('plist', $plist);
            $this->display("order.index.detail");
        }
    }

    /*
     * 订单去付款
     */

    public function gotopay() {
        $_SESSION['shop']['card'] = $_GET['order'];
        header("location:/shop/index/pay");
    }

    public function recycle() {
        #用户guid
        $guid = $_SESSION['user'][0]['u_guid'];
        $where = "order_guid='" . $guid . "' and order_recycle=2";


        ##分页数据
        $key = $_POST['key'];  //搜索
        if ($key) {
            $where .= " and order_num like '%" . $key . "%'";
        }
        $this->log->info($where);

        $count = M("bill_order_info")->where($where)->count();
        $p = new Page($count, $this->listRows);
        $list = M("bill_order_info")->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
        $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
        $page = $p->show();

        #####该年级下学科
        //        $cate_01 = $_SESSION['user'][0]['u_xueduan'];

        $table_index = substr($guid, -2);
        $table_name = "user_" . $table_index;
        $cate_01 = M("canpoint_user." . $table_name)->where("u_guid='$guid'")->getField("u_xueduan");
        $this->guess($cate_01);

        $this->assign("cate1", $cate_01); //学段
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display("order.index.recycle");
    }

    /*
     * 订单还原
     */

    public function back() {
        $billid = $_GET['id'];
        canpointCommon::logDegub("order_back=id:$billid", 'Order');
        $re = M("bill_order_info")->where("id=$billid")->save(array('order_recycle' => 1));
        if ($re) {
            canpointCommon::logDegub("order_back_sql=" . M("bill_order_info")->getLastSql(), 'Order');
            header("location:/order/Index/index");
        }
    }

    /*
     * 猜你喜欢
     */

    function guess($cate1) {
        $this->log->info($cate1);
        canpointCommon::logDegub("order_guess=cate_01:$cate1", 'Order');
        //该学段下的学科
        $Order = D("Order");
        $cate2 = $Order->getXueKe($cate1);
        $cate_02 = $cate2[0]['id'];
        //第一个学科的推荐
        $kearr = M("zi_ke")->where("cate_01=$cate1 and cate_02=$cate_02")->order("ke_xliang desc")->limit(10)->select();
        $this->log->info(M("zi_ke")->getLastSql());
        canpointCommon::logDegub("order_guess_sql=" . M("zi_ke")->getLastSql(), 'Order');
        foreach ($kearr as $key => $v) {
            $kearr[$key]['image'] = $Order->getimgURL($v['x_url'], '200x134');
            $kearr[$key]['nj'] = $Order->get_nianji($v['cate_00']);
            $kearr[$key]['xk'] = $Order->get_xk($v['cate_02']);
            $kearr[$key]['keid'] = canpointCommon::encrypt($v['id']);
        }
        $this->assign('kearr', $kearr);
        $this->assign('cate2', $cate2);
    }

    /*     * **************************************本类Ajax********************************************** */

    /*
     * 从订单删除进入回收站
     */

    public function delbill() {
        $billid = $_GET['id'];
        $this->log->info($billid);
        canpointCommon::logDegub("order_delbill=id:$billid", 'Order');
        //是否是实物订单
        $order_list = M("bill_order_info")->where("id=$billid")->field("order_num,order_keid")->find();
        $this->log->info($order_list['order_num']);
        if (strpos($order_list['order_num'], 'G') !== false) {
            $res = M("bill_order_info")->where("id=$billid")->delete();
            if ($res) {
                //删除成功后库存加1
                $Mgoods = M('canpoint_goods.goods');
                $re = $Mgoods->where("goods_id=" . $order_list['order_keid'])->setInc('goods_number', 1);
                $msg = array(
                    'status' => 'y', 'info' => '删除成功'
                );
                echo json_encode($msg);
            } else {
                $msg = array(
                    'status' => 'n', 'info' => '删除失败'
                );
                echo json_encode($msg);
            }
        } else {
            $re = M("bill_order_info")->where("id=$billid")->save(array('order_recycle' => 2));
            if ($re) {
                canpointCommon::logDegub("order_order_delbill_sql=" . M("bill_order_info")->getLastSql(), 'Order');
                $msg = array(
                    'status' => 'y', 'info' => '删除成功'
                );
            } else {
                canpointCommon::logError("order_order_delbill_sql=" . M("bill_order_info")->getLastSql(), 'Order');
                $msg = array(
                    'status' => 'n', 'info' => M("qp_bill_order_info")->getLastSql()
                );
            }
            echo json_encode($msg);
        }
    }

    /*
     * 从回收站彻底删除
     */

    public function delrecy() {
        $billid = $_GET['id'];
        $guid = $_SESSION['user'][0]['u_guid'];
        $this->log->info($billid);
        canpointCommon::logDegub("order_delrecy=id:$billid", 'Order');
        $order_num = M("bill_order_info")->where("id=$billid")->getField("order_num");
        $this->log->info($order_num);
        $re = M("bill_order_info")->where("id=$billid and order_guid=$guid")->delete();
        if ($re) {
            $this->log->info(M("bill_order_info")->getLastSql());
            canpointCommon::logDegub("order_delrecy_sql=" . M("bill_order_info")->getLastSql(), 'Order');

            $res = M("bill_order_plist")->where("plist_ordernum='" . $order_num . "' and plist_guid=$guid")->delete();
            if ($res) {
                $msg = array(
                    'status' => 'y', 'info' => '删除成功'
                );
            } else {
                $msg = array(
                    'status' => 'n', 'info' => M("qp_bill_order_info")->getLastSql()
                );
            }
        } else {
            canpointCommon::logError("order_delrecy_sql=" . M("bill_order_info")->getLastSql(), 'Order');
            $msg = array(
                'status' => 'n', 'info' => M("qp_bill_order_info")->getLastSql()
            );
        }
        echo json_encode($msg);
    }

    /*     * ***************************************Ajax***************************************************** */
    /*
     * 猜你喜欢ajax返回数据
     */

    public function like() {
        $this->log->info($_GET);
        $cate2 = $_GET['xk'];
        $cate0 = $_GET['nj'];
        canpointCommon::logDegub("order_ajaxlike=xk:$cate2,nj:$cate0", 'Order');
        $Order = D("Order");

        $count = M("zi_ke")->where("cate_01=$cate0 and cate_02=$cate2")->order("ke_xliang desc")->count();
        $kearr = M("zi_ke")->where("cate_01=$cate0 and cate_02=$cate2")->order("ke_xliang desc")->limit(10)->select();

        canpointCommon::logDegub("order_ajaxlike_sql=" . M("zi_ke")->getLastSql(), 'Order');
        foreach ($kearr as $key => $v) {
            $kearr[$key]['image'] = $Order->getimgURL($v['x_url'], '200x134');
            $kearr[$key]['nj'] = $Order->get_nianji($v['cate_00']);
            $kearr[$key]['xk'] = $Order->get_xk($v['cate_02']);
            $kearr[$key]['keid'] = canpointCommon::encrypt($v['id']);
        }

        $this->assign('count', $count);
        $this->assign('kearr', $kearr);
        $this->display("order.index.like");
    }

}
