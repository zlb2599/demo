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

class MyfpointAction extends BaseController {

    public function index() {


        #用户guid
        $guid = $_SESSION['user'][0]['u_guid'];

        //1.全品券—赠送卡每年只能充值10次
        $Rse = D('p_fpoint');
        $cztime = $Rse->where("u_id='" . $guid . "' and p_cr='系统赠送卡'")->limit(1)->order("id desc")->getField('u_rdate');
        $this->log->info($cztime);

        //2.若最新一次充值不在今年 则今年充值次数归0
        if (date("Y", strtotime($cztime)) != date("Y", time())) {
            $table="canpoint_user.user_".substr($guid,-2);
            $cishu['u_czhi'] = 0;
            M($table)->where("u_guid=$guid")->save($cishu);

        }


        ##全品币详细分页
        $Coinlog = M("u_coinlog");
        $pageP = isset($_GET['p']) ? $_GET['p'] : 1;
        $this->log->info($pageP);
        $count = $Coinlog->where("flag=4 and uname=$guid")->count();
        $p = new Page($count, $this->listRows);
        $list = $Coinlog->where("flag=4 and uname=$guid")->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();

//        $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
//        $page = $p->show();


        if (isMobile()) {
            $CountP = ceil($count / $p->listRows);
            $p->setConfig('theme', "%upPage% <li><a>{$pageP}/{$CountP}</a></li> %downPage%");
            $page = $p->Mshow();
        } else {
            $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
            $page = $p->show();
        }


        ##赠送卡充值次数
        $info = D("Money")->getUserByGuid($guid, 'u_czhi');
        $this->log->info($info);
        $yczhi = $info['u_czhi'];  ##已充次数
        $this->log->info($yczhi);
        $nchi = 10 - $info['u_czhi']; ##未充次数
        $this->log->info($nchi);
        $this->assign('yzhi', $yczhi);
        $this->assign('nzhi', $nchi);
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display("money.myfpoint.index");
    }

}
