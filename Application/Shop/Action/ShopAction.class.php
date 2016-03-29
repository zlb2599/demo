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

use Think\Controller;

class ShopAction extends Controller {

    public function updateOrderStatus() {
        $time = date("Y-m-d H:i:s");
        $Order = M("bill_order_info");
        $re = $Order->where("order_type=4 and order_status=1 and order_endtime<'" . $time . "'")->save(array('order_status' => 3));
        echo $Order->getLastSql();
        if ($re !== false) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * 是否购买过视频
     */

    public function IfBuy() 
    {
    	//1.接收参数
        $guid=$_GET['guid'];
        $kid=$_GET['kid'];
        $jcallback=$_GET['jcallback'];
        $this->log->info($_GET);
        //2.对参数进行验证
        //2.1
        if( empty( $guid ) || !is_numeric( $guid ) || strlen( $guid ) != 9 )
        {
        	$data = array();
	        $data['status'] = 0;
	        $data['count'] = 0;
	        $data['data'] = array();
	        echo json_encode($data);
	        return;
        }
        //2.2对kid， 进行验证, 防止客户端出入参数错误，或格式错误，如果错，会引起sql出错
        //待做
 
        //3.
        $arr = array();
        $ordernum_arr = M("bill_order_info")->where("order_guid=$guid and order_status=2")->field("order_num")->select();
        foreach ($ordernum_arr as $v) {
            $order_str.='"' . $v['order_num'] . '",';
        }
        $order_str = substr($order_str, 0, -1);
        $this->log->info($order_str);
        if ($kid) {
            $arr = M("bill_order_plist")->where("plist_ordernum in ($order_str) and plist_productid in ($kid) and plist_guid=$guid")->Distinct(true)->field("plist_productid")->select(); 
        } else {
            $arr = M("bill_order_plist")->where("plist_ordernum in ($order_str) and plist_guid=$guid")->Distinct(true)->field("plist_productid")->select();
        }
        
        
        //4.输出
        $data = array();
        $data['status'] = 1;
        $data['count'] = count($arr);
        $data['data'] = $arr;
        //echo json_encode($data);
        echo $jcallback . "(" . json_encode($data) . ")";
    }

    /*
     * 是否购买过视频
     */

    public function ceshi() {
        $this->IfBuy($_SESSION['user'][0]['u_guid'], '2060,2205');
    }

}
