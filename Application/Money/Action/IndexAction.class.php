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

class IndexAction extends BaseController {

    public function index() {
        //1.用户guid
        $guid = $_SESSION['user'][0]['u_guid'];
        $Money=D("Money");
        
        //购物车数量
        $cartnums=$Money->cartNums($guid);
        $this->log->info($cartnums);
        
         ###待付款总数
        $nopay = D("Order/Order")->obligation($guid);
        $this->log->info($nopay);
        $this->assign('nopay',$nopay);
        $this->assign('cartnums', $cartnums); //购物车数量
        $this->display("money.index.index");
    }

}
