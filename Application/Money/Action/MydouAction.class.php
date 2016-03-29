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

class MydouAction extends BaseController {

    public function index(){
         #用户guid
        $guid=$_SESSION['user'][0]['u_guid'];
        
        ##全品币详细分页
        $Coinlog=M("u_coinlog");
        $pageP=isset($_GET['p'])?$_GET['p']:1;
        $this->log->info($pageP);
        $count = $Coinlog->where("flag=2 and uname=$guid")->count();
        $this->log->info($count);
        $p = new Page($count, $this->listRows);
        $list = $Coinlog->where("flag=2 and val!=0 and  uname=$guid")->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();

//        $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
//        $page = $p->show();


        if(isMobile())
        {
            $CountP=ceil($count/$p->listRows);
            $p -> setConfig('theme', "%upPage% <li><a>{$pageP}/{$CountP}</a></li> %downPage%");
            $page  = $p->Mshow();
        }
        else
        {
            $p->setConfig('theme', '%first%  %upPage%  %linkPage%  %downPage% %end%');
            $page = $p->show();
        }


        ###里程碑
        $retu=D("Money")->qdou_milepost($guid);
        $this->log->info($retu);
        //print_r($retu);
        
        $this->assign('retu',$retu);
        $this->assign('page',$page);
        $this->assign('list',$list);
        $this->display("money.mydou.index");
    }

}
