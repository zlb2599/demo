<?php

/* * *********************************************************************************
 * Copyright (c) 2005-2011
 * All rights reserved.
 *
 * File:
 * Author:xigua
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

    public function index() {

        $this->display("change.index.index");
    }

    /*
     * 兑换列表
     */

    public function lists() {
        $this->log->info($_GET);
        #1.用户
        $guid = $_SESSION['user'][0]['u_guid'];
        $Change = M("c_change");


        ##分页数据
        /* ------------------参数条件---------------------- */

        ##最近两年的年份和最近日期
        $month1 = time() - 30 * 24 * 60 * 60;  //最近一个月
        $month2 = time() - 90 * 24 * 60 * 60;  //最近三个月
        $Date_Y = date("Y"); //今年内
        $Date_Y1 = date('Y', time()) - 1; //去年
        $Date_Y2 = date('Y', time()) - 2; //前年
        $this->log->info($month1.",".$month2.",".$Date_Y.",".$Date_Y1.",".$Date_Y2);
        $pagep = isset($_GET['p']) ? $_GET['p'] : 1;
        $ctype = $_GET['ctype'];   //类型

        switch ($ctype) {
            case 1:
                $where1 = " and change_type=1";
                break;
            default:
                $where1 = '';
        }
        $this->log->info($where1);
        $ttype = $_GET['ttype'] ? $_GET['ttype'] : 1;  //时间
        $this->log->info($ttype);
        switch ($ttype) {
            case 1:
                $dtime = " and change_date>='" . date("Y-m-d H:i:s", $month1) . "'";
                break;
            case 2:
                $dtime = " and change_date>='" . date("Y-m-d H:i:s", $month2) . "'";
                break;
            case 3:
                $dtime = " and change_date>='" . "$Date_Y-01-01 00：00：00" . "'";
                break;
            case 4:
                $dtime = " and change_date>='" . "$Date_Y1-01-01 00：00：00" . "' and change_date<='" . "$Date_Y-01-01 00：00：00" . "'";
                break;
            case 5:
                $dtime = " and change_date>='" . "$Date_Y2-01-01 00：00：00" . "' and change_date<='" . "$Date_Y1-01-01 00：00：00" . "'";
                break;
            default:
                $dtime = '';
        }
        $this->log->info($dtime);
        canpointCommon::logDegub("change_list_canshu=ctype:" . $ctype . ",ttype:$ttype", 'Change');

        /* ------------------条件结束---------------------- */

        $where = "change_uid='$guid'";
        if ($where1) {
            $where.=$where1;
        }
        if ($dtime) {
            $where.=$dtime;
        }
        $this->log->info($where);
        $count = $Change->where($where)->count();
        canpointCommon::logDegub("change_list_totalsql=" . $Change->getLastSql(), 'Change');

        $p = new Page($count, $this->listRows);
        $list = $Change->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
//        $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
//        $page = $p->show();
        if (isMobile()) {
            $CountP = ceil($count / $p->listRows);
            $p->setConfig('theme', "%upPage% <li><a>{$pagep}/{$CountP}</a></li> %downPage%");
            $page = $p->Mshow();
        } else {
            $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
            $page = $p->show();
        }


        $this->assign('ttype', $ttype);
        $this->assign('ctype', $ctype);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign("Date_Y1", $Date_Y1); //去年年份
        $this->assign("Date_Y2", $Date_Y2); //前年年份
        $this->display("change.index.lists");
    }

    /*
     * 兑换
     */

    public function change() {
        #1.用户表
        $guid = $_SESSION['user'][0]['u_guid'];
        $Change = D("Change");

        #2.用户信息
        $info = $Change->getInfo($guid, 'u_rpoint,u_qdou');
        $this->log->info($info);
        #3.全品币减  全品豆加
        $rpoint = $_POST['rpoint']; //要兑换的全品币数量
        $this->log->info($rpoint);
        if (!is_numeric($rpoint)) {
            exit("数量有误");
        }
        if ($rpoint>$info['u_rpoint']) {
            exit("全品币不足");
        }
        $qpb = $info['u_rpoint'] - $rpoint;
        $qpd = $info['u_qdou'] + $rpoint * 100;
        $this->log->info('qpb=' . $qpb . ",qpd=" . $qpd);
        canpointCommon::logDegub("change_last_qpb=" . $qpb . ",change_last_qpd=" . $qpd, 'Change');

        #3.3 兑换上限
        $dousum = M("c_change")->where("change_uid=$guid")->sum("change_inc");
        $dou = $dousum + $rpoint * 100;
        $this->log->info($dou);
        if ($dou > 10000) {
            exit("每年可兑换的全品豆上限为10000个。");
        }
        #4.更新兑换后的全品币和全品豆
        $arr = array('u_rpoint' => $qpb, 'u_qdou' => $qpd);
        $this->log->info($arr);
        $re = $Change->updByguid($guid, $arr);

        #增加u_coinlog记录表
        if ($re) {
            ##全品币数组
            $data['uid'] = $guid;
            $data['uname'] = $guid;
            $data['action'] = "exchange";
            $data['note'] = "兑换";
            $data['val'] = "-" . floatval($rpoint);
            $data['flag'] = 3;
            $data['date'] = date("Y-m-d H:i:s", time());
            $result = $Change->addcoinlog($data);
            $this->log->info($data);
            if ($result) {
                ##全品豆
                $data1['uid'] = $guid;
                $data1['uname'] = $guid;
                $data1['action'] = "exchange";
                $data1['note'] = "兑换";
                $data1['val'] = floatval($rpoint * 100);
                $data1['flag'] = 2;
                $data1['date'] = date("Y-m-d H:i:s", time());
                $result2 = $Change->addcoinlog($data1);
                $this->log->info($data1);
                ##兑换记录
                if ($result2) {
                    $num = date("Y") . date("m") . date("d") . "101403";
                    $arr = array('change_num' => $num, 'change_uid' => $guid, 'change_type' => 1, 'change_dec' => $rpoint, 'change_inc' => $rpoint * 100, 'change_date' => date("Y-m-d H:i:s"));
                    $this->log->info($arr);
                    $Change->addchange($arr);

                    //全品币兑换成功—发通知
                    $messge = array(
                        'sender_uid' => 1, 'recipient_uid' => $guid,
                        'action_type' => 1, 'message_content' => "您的全品币余额减少了" . $rpoint . " 元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                    );
                    curl_post($messge);
                } else {
                    canpointCommon::logError("To add an u_coinlog error, see change_coinlog_addsql", 'Change');
                }
                $_SESSION['user']['dec_rpoint'] = $rpoint;
                $_SESSION['user']['inc_qdou'] = $rpoint * 100;
                $_SESSION['user']['num'] = $num;
                header("location:/Change/Index/changeok");
            } else {
                canpointCommon::logError("To add an u_coinlog error, see change_coinlog_addsql", 'Change');
            }
        } else {
            canpointCommon::logError("Update user error, see change_updsql", 'Change');
        }
    }

    /*
     * 兑换成功
     */

    public function changeok() {
        #1.用户表
        $guid = $_SESSION['user'][0]['u_guid'];
        $Change = D("Change");

        #2.用户信息
        $info = $Change->getInfo($guid, 'u_rpoint,u_qdou');
        
        $this->assign('info', $info);
        $this->assign('dec_rpoint', number_format($_SESSION['user']['dec_rpoint'], 2));
        $this->assign('num', $_SESSION['user']['num']);
        $this->assign('inc_qdou', $_SESSION['user']['inc_qdou']);
        $this->display("change.index.changeok");
    }

}
