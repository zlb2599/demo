<?php
/* * 
 * 功能：支付宝页面跳转同步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 * ************************页面功能说明*************************
 * 该页面可在本机电脑测试
 * 可放入HTML等美化页面的代码、商户业务逻辑程序代码
 * 该页面可以使用PHP开发工具调试，也可以使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyReturn
 */

require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <?php
//计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        if ($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];

            //支付宝交易号
            $trade_no = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];
            $table_index = substr($is_pay['memberid'], -2);
            $study = D("canpoint_user.user_" . $table_index);

            if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                if ($is_pay['payorno'] == 1) {
                 
                    $pay_db->where("orderId='" . $info['out_trade_no'] . "'")->save($pay_order);
                    $num = $info['total_fee'];  //充值的金额
                    if (!empty($is_pay['bonus'])) {
                    	$num += $is_pay['bonus'];
                    }
                    $oldmny = $study->where("u_guid='" . $is_pay['memberid'] . "'")->getField('u_rpoint');  //老金额

                    $mony['u_rpoint'] = $oldmny + $num;  //充值后新金额

                    if ($study->where("u_guid='" . $is_pay['memberid'] . "'")->save($mony)) {
                        canpointCommon::logDegub("recharge_user_sql=" . $study->getLastSql());

//                        $data['uid'] = $is_pay['memberid'];
//                        $data['uname'] = $is_pay['memberid'];
//                        $data['action'] = "chongzhi1";
//                        $data['note'] = "充值";
//                        $data['val'] = $num;
//                        $data['flag'] = 3;
//                        $data['date'] = date("Y-m-d H:i:s", time());
//                        $log = M("u_coinlog");
//                        $log->add($data);
                        $active_model = new ActiveModel();
                        $active_model->updateStatus($is_pay['bonus_id'], 2, $is_pay['id']);
                        //请不要修改或删除
                        $_SESSION['now_rpoint'] = $oldmny + $num;
                        $notify_yz = 1;
                    } else {
                        canpointCommon::logError("User failed recharge Please check recharge_user_sql");
                    }
                }
            } else {
                canpointCommon::logError("recharge_mesg_Paid trade_status=" . $_GET['trade_status']);
                // echo "trade_status=" . $_GET['trade_status'];
            }
        } else {
            //验证失败
            canpointCommon::logError("recharge_mesg=Verify failure1");
            //如要调试，请看alipay_notify.php页面的verifyReturn函数
            //  echo "验证失败";
        }
        ?>
        <title>支付宝即时到账交易接口</title>
    </head>
    <body>
    </body>
</html>