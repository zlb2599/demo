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

    public function __construct() {
        parent::__construct();
        $aa = $this->updCashState();
    }

    /*
     * 我的优惠券—所有
     */

    public function index() {
        #用户guid
        $guid = $_SESSION['user'][0]['u_guid'];

        $where = "log_guid=" . $guid;
        $state = I("state");
        $this->log->info($state);
        if ($state) {
            $where.=" and log_state=" . $state;
        } else {
            $where.=" and log_state=0";
        }
         $this->log->info($where);
        $count = M("cash_log")->where($where)->count();
       $this->log->info($count);
       
        $p = new Page($count, 10);
        $list = M("cash_log")->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();

        $page = $p->show();
        $cash_url=M("cash_activename")->where("cash_num=100003")->getField("cash_url");
        $this->log->info($cash_url);
        foreach ($list as $key => $v) {
            $info = M("cash_voucher")->where("cash_price=" . $v['log_price']." and cash_number=".$v['log_number']." and cash_vid=".$v['log_type'])->find();
            if($info['cash_vid']==1){
                $from=$info['cash_from'];
            }elseif($info['cash_vid']==2){
                $from="充值满额送";
            }
            $list[$key]['log_from'] = $from;
            $list[$key]['log_typename'] = $info['cash_type'];
            $list[$key]['log_where'] = $info['cash_where']?$info['cash_where']:$info['cash_price'];
        }
        $this->assign('cash_url', $cash_url);
        $this->assign('page', $page);
        $this->assign('list', $list);

        if ($state == 2) {
            $this->display("cash.index.overindex");
        } elseif ($state == 1) {
            $this->display("cash.index.userindex");
        } else {
            $this->display("cash.index.index");
        }
    }

    /*
     * 过期券的状态修改
     */

    public function updCashState() {
        $guid = $_SESSION['user'][0]['u_guid'];
        if (!$guid) {
            exit;
        }
        //1.活动的截至时间
        $endtime = M("cash_activename")->where("cash_num=100003")->getField("cash_endtime");
        $re = M("cash_log")->where("log_guid=" . $guid . " and (log_endtime<='" . date("Y-m-d H:i:s") . "' OR '" . $endtime . "'<='" . date("Y-m-d H:i:s") . "') and log_state!=1")->save(array("log_state" => 2));
    }

}
