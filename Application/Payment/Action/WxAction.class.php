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
use Think\Controller;

class WxAction extends Controller {
    
   
    public function notify(){
        echo 1;
       // $this->logDegub("wxpay=7777", '.wxpay');
        //canpointCommon::logDegub("wxpay=7777", '.wxpay'); 
    }
    
//    public function logDegub($msg, $ext = "") {
//        $logFile = "/alidata1/www_v3/pay.canpoint.net/Application/Runtime/" . date('Y-m-d') . '.txt' . $ext;
//        //$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . '	{in file:' . $_SERVER ['REQUEST_URI'] . "}\r\n";
//        $msg = date('[Y-m-d H:i:s]') . '	' . $msg . "\r\n";
//        file_put_contents($logFile, $msg, FILE_APPEND);
//    }

}
?>
