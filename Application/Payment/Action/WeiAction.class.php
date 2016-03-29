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

class WeiAction extends Controller {

    public $log;

    public function _initialize() {

        //parent::__construct();
        header("Content-type:text/html;charset=utf-8");

        $this->log = \Logger::getLogger(__CLASS__);
    }

    public function notify() {


        $simple = file_get_contents("php://input");

        $xml_msg = json_decode(json_encode((array) simplexml_load_string($simple, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        file_put_contents('/alidata1/www_v3/pay.canpoint.net/Application/Runtime/' . date("Y-m-d") . '.txt', $xml_msg['result_code'] . "\n", FILE_APPEND);
        $this->log->info($xml_msg);
        if ($xml_msg['result_code'] == 'SUCCESS') {
            require_once "Application/CPLib/wxpay/lib/WxPay.Api.php";
            require_once "Application/CPLib/wxpay/lib/WxPay.Data.php";
            $Card = $xml_msg['out_trade_no'];
            $this->log->info($Card);
            if (isset($Card) && $Card != "") {
                $out_trade_no = $Card;
                $input = new WxPayOrderQuery();
                $input->SetOut_trade_no($out_trade_no);
                $order = WxPayApi::orderQuery($input);
                $this->log->info($order);
                if ($order['err_code'] == 'ORDERNOTEXIST') {
                    exit('订单不存在');
                }

                if ($order['trade_state'] == 'SUCCESS') {
                    $pay_db = D('t_order_sn');
                    canpointCommon::logDegub(serialize($order), 'wxpay_post', 'Payment');
                    
                    //1.订单信息
                    $is_pay = $pay_db->where("orderId='" . $order['out_trade_no'] . "'")->find();
                    $uid = $is_pay['memberid'];   ##被充值人的guid
                    $this->log->info($is_pay);
                    //2.订单未付款   
                    if ($is_pay['payorno'] == 1) {
                        //3.订单支付状态修改
                        $pay_order = array('payorno' => 2, 'paytime' => date("Y-m-d H:i:s", strtotime($order['time_end'])));
                        $pay_db->where("orderId='" . $order['out_trade_no'] . "'")->save($pay_order);
                        canpointCommon::logDegub("wxpay_upd_order_stat=" . $pay_db->getLastSql(), 'Payment');
                        $this->log->info("wxpay_upd_order_stat=" . $pay_db->getLastSql());

                        //4.用户全品币增加
                        $table_index = substr($uid, -2);
                        $study = M("canpoint_user.user_" . $table_index);
                        $num = $order['total_fee'] * 0.01;  //充值的金额
                        $this->log->info($num);
                        $oldmny = $study->where("u_guid='" . $is_pay['memberid'] . "'")->getField('u_rpoint');  //老金额
                        canpointCommon::logDegub("wxpay_user_getoldrpoint=" . $study->getLastSql(), 'Payment');
                        $this->log->info($study->getLastSql());
                        $this->log->info($oldmny);
                        //存在红包奖励
                        if (!empty($is_pay['bonus'])) {
                            $num += $is_pay['bonus'];
                        }
                        $mony['u_rpoint'] = $oldmny + $num;  //充值后新金额
                        $this->log->info($mony['u_rpoint']);
                        $study_reutrn=$study->where("u_guid='" . $is_pay['memberid'] . "'")->save($mony);
                        $this->log->info($study->getLastSql());
                        if ($study_reutrn) {
                            //修改红包状态
                            $active_model = new ActiveModel();
                            $active_model->updateStatus($is_pay['bonus_id'], 2, $is_pay['id']);
                            $this->log->info("wxpay_user_upd=" . $study->getLastSql());
                            canpointCommon::logDegub("wxpay_user_upd=" . $study->getLastSql(), 'Payment');
                            $NSQ = M('ka_jihuolog');   //这个激活log 需把数据表的部分字段的not null 去掉，否则 此表不会添加数据，内网已改
                            $data1 = array();
                            $data1['k_type'] = 3;
                            $re = $NSQ->where("k_id='" . $order['out_trade_no'] . "'")->save($data1);

                            if ($re) {
                                $fen = $NSQ->where("k_id='" . $order['out_trade_no'] . "'")->select();

                                $data['uid'] = $fen[0]['k_userid'];
                                $data['uname'] = $fen[0]['k_userid'];
                                $data['action'] = "chongzhi1";
                                $data['note'] = "充值";
                                $data['val'] = $fen[0]['k_jifen'];
                                $data['flag'] = 3;
                                $data['date'] = date("Y-m-d H:i:s", time());
                                $this->log->info($data);
                                $log = M("u_coinlog");
                                $log->add($data);
                                $this->cash($fen[0]['k_userid'], $fen[0]['k_jifen']);
                                //网银充值成功—发通知
                                $messge = array(
                                    'sender_uid' => 1, 'recipient_uid' => $fen[0]['k_userid'],
                                    'action_type' => 1, 'message_content' => "您的全品币余额增加了" . $fen[0]['k_jifen'] . "元 <a target='_blank' href='http://pay.canpoint.net/money/myrpoint/index' >查看详情</a>"
                                );
                                curl_post($messge);
                                $_SESSION['pay_jifen'] = $fen[0]['k_jifen'];
                            }
                        }
                    }
                }
            }
           
        }
    }

    /**
     * 增加现金券
     */
   public function cash($guid, $price) {
        $this->log->info($guid . "_" . $price);
        $tlist = M("cash_activename")->where("cash_num=100003")->field("cash_endtime,cash_starttime")->find();
        $this->log->info($tlist);
        
        if($guid==100009540||$guid==100085010){
            $man=0.01;
        }else{
            $man=100;
        }
//        $man=100;
        
        if (strtotime($tlist['cash_endtime']) > time() && strtotime($tlist['cash_starttime']) <= time() && $price >=$man) {
            $this->log->info("充值满额送");
            $num=$price/$man;
            //1.添加领取记录
            if(20*$num>=100){
                $cash_price=100;
            }else{
                $cash_price=20*$num;
            }
            $data['log_endtime'] = $tlist['cash_endtime'];

            $data['log_price'] = $cash_price;
            $data['log_guid'] = $guid;
            $data['log_time'] = date("Y-m-d H:i:s");

            $data['log_state'] = 0;  #0未使用   1已使用  2已过期
            $data['log_number'] = 100003;
            $data['log_type'] = 2;
            $this->log->info($data);
            $re = M("cash_log")->add($data);
            $this->log->info(M("cash_log")->getLastSql());
        } else {
            $this->log->info("不符合满额送");
        }
    }
//    public function logDegub($msg, $ext = "") {
//        $logFile = "/alidata1/www_v3/pay.canpoint.net/Application/Runtime/" . date('Y-m-d') . '.txt' . $ext;
//        //$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . '	{in file:' . $_SERVER ['REQUEST_URI'] . "}\r\n";
//        $msg = date('[Y-m-d H:i:s]') . '	' . $msg . "\r\n";
//        file_put_contents($logFile, $msg, FILE_APPEND);
//    }
}

?>
